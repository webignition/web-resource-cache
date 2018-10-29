<?php

namespace App\Command;

use App\Exception\HttpTransportException;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\SuccessResponse;
use App\Model\RetrieveRequest;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\ResourceRetriever;
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
     * @var CachedResourceFactory
     */
    private $cachedResourceFactory;

    /**
     * @var int
     */
    private $maxRetries;

    public function __construct(
        ResourceRetriever $resourceRetriever,
        RetryDecider $retryDecider,
        CachedResourceManager $cachedResourceManager,
        CachedResourceFactory $cachedResourceFactory,
        int $maxRetries
    ) {
        parent::__construct();

        $this->resourceRetriever = $resourceRetriever;
        $this->retryDecider = $retryDecider;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->cachedResourceFactory = $cachedResourceFactory;
        $this->maxRetries = $maxRetries;
    }

    protected function configure()
    {
        $this
            ->setName('web-resource-cache:get-resource')
            ->setDescription('Retrieve a resource')
            ->addArgument('request-json', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $retrieveRequest = RetrieveRequest::createFromJson(trim($input->getArgument('request-json')));
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

        $requestHash = $retrieveRequest->getRequestHash();

        if ($hasUnknownFailure) {
            // Fix in #168
            // Implement dispatching 'send response' message
            // using UnknownFailureResponse as the data object

            return self::RETURN_CODE_OK;
        }

        $hasRetryableResponse = $this->retryDecider->isRetryable($responseType, $statusCode);
        if ($hasRetryableResponse && $retrieveRequest->getRetryCount() <= $this->maxRetries) {
            $retrieveRequest->incrementRetryCount();

            // Fix in #168
            // Implement dispatching 'retrieve resource' message
            // using the retrieve request as the data object

            return self::RETURN_CODE_RETRYING;
        }

        if (200 === $statusCode) {
            $cachedResource = $this->cachedResourceManager->find($requestHash);
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

        // response-json => json_encode(new RebuildableDecoratedResponse($response))

        // Fix in #168
        // Implement dispatching 'send response' message
        // using $response as the data object

        return self::RETURN_CODE_OK;
    }
}
