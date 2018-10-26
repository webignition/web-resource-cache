<?php

namespace App\Command;

use App\Entity\RetrieveRequest;
use App\Exception\HttpTransportException;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\RebuildableDecoratedResponse;
use App\Model\Response\ResponseInterface;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Resque\Job\RetrieveResourceJob;
use App\Resque\Job\SendResponseJob;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\ResourceRetriever;
use App\Services\ResqueQueueService;
use App\Services\RetrieveRequestManager;
use App\Services\RetryDecider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

class RetrieveResourceCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_RETRIEVE_REQUEST_NOT_FOUND = 2;
    const RETURN_CODE_RETRYING = 3;

    /**
     * @var RetrieveRequestManager
     */
    private $retrieveRequestManager;

    /**
     * @var ResourceRetriever
     */
    private $resourceRetriever;

    /**
     * @var RetryDecider
     */
    private $retryDecider;

    /**
     * @var CachedResourceManager
     */
    private $cachedResourceManager;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var CachedResourceFactory
     */
    private $cachedResourceFactory;

    /**
     * @var int
     */
    private $maxRetries;

    public function __construct(
        RetrieveRequestManager $retrieveRequestManager,
        ResourceRetriever $resourceRetriever,
        RetryDecider $retryDecider,
        CachedResourceManager $cachedResourceManager,
        ResqueQueueService $resqueQueueService,
        CachedResourceFactory $cachedResourceFactory,
        int $maxRetries
    ) {
        parent::__construct();

        $this->retrieveRequestManager = $retrieveRequestManager;
        $this->resourceRetriever = $resourceRetriever;
        $this->retryDecider = $retryDecider;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->resqueQueueService = $resqueQueueService;
        $this->cachedResourceFactory = $cachedResourceFactory;
        $this->maxRetries = $maxRetries;
    }

    protected function configure()
    {
        $this
            ->setName('web-resource-cache:get-resource')
            ->setDescription('Retrieve a resource')
            ->addArgument('request-hash', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var RetrieveRequest $retrieveRequest */
        $retrieveRequest = $this->retrieveRequestManager->find(trim($input->getArgument('request-hash')));
        if (empty($retrieveRequest)) {
            return self::RETURN_CODE_RETRIEVE_REQUEST_NOT_FOUND;
        }

        $requestResponse = null;
        $response = null;
        $httpResponse = null;
        $responseType = null;
        $statusCode = null;
        $hasUnknownFailure = true;

        try {
            $requestResponse = $this->resourceRetriever->retrieve($retrieveRequest);

            $httpResponse = $requestResponse->getResponse();
            $responseType = RetryDecider::TYPE_HTTP;
            $statusCode = $httpResponse->getStatusCode();

            $hasUnknownFailure = false;
        } catch (HttpTransportException $httpTransportException) {
            if ($httpTransportException->isCurlException()) {
                $responseType = RetryDecider::TYPE_CONNECTION;
                $statusCode = $httpTransportException->getTransportErrorCode();
                $hasUnknownFailure = false;
            } elseif ($httpTransportException->isTooManyRedirectsException()) {
                $responseType = RetryDecider::TYPE_HTTP;
                $statusCode = 301;
                $hasUnknownFailure = false;

                $httpResponse = new Response('', 301);
            }
        }

        $requestHash = $retrieveRequest->getHash();

        if ($hasUnknownFailure) {
            $sendResponseJob = new SendResponseJob([
                'response-json' => $this->createResponseJson(new UnknownFailureResponse($requestHash)),
            ]);

            if (!$this->resqueQueueService->contains($sendResponseJob)) {
                $this->resqueQueueService->enqueue($sendResponseJob);
            }

            return self::RETURN_CODE_OK;
        }

        $hasRetryableResponse = $this->retryDecider->isRetryable($responseType, $statusCode);
        if ($hasRetryableResponse && $retrieveRequest->getRetryCount() <= $this->maxRetries) {
            $retrieveRequest->incrementRetryCount();
            $this->retrieveRequestManager->persist($retrieveRequest);

            $this->resqueQueueService->enqueue(new RetrieveResourceJob([
                'request-hash' => $retrieveRequest->getHash(),
            ]));

            return self::RETURN_CODE_RETRYING;
        }

        if (200 === $statusCode) {
            $cachedResource = $this->cachedResourceManager->find($retrieveRequest->getHash());
            if ($cachedResource) {
                $this->cachedResourceFactory->updateResponse($cachedResource, $httpResponse);
            } else {
                $cachedResource = $this->cachedResourceFactory->create($retrieveRequest, $httpResponse);
            }

            $this->cachedResourceManager->update($cachedResource);

            $response = new SuccessResponse($requestHash);
        } else {
            $response = new KnownFailureResponse($requestHash, $responseType, $statusCode);
        }

        $sendResponseJob = new SendResponseJob([
            'response-json' => $this->createResponseJson($response),
        ]);

        if (!$this->resqueQueueService->contains($sendResponseJob)) {
            $this->resqueQueueService->enqueue($sendResponseJob);
        }

        return self::RETURN_CODE_OK;
    }

    private function createResponseJson(ResponseInterface $response): string
    {
        return json_encode(new RebuildableDecoratedResponse($response));
    }
}
