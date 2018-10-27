<?php

namespace App\Command;

use App\Model\Response\PresentationDecoratedSuccessResponse;
use App\Model\Response\SuccessResponse;
use App\Services\CachedResourceManager;
use App\Services\ResponseFactory;
use App\Services\ResponseSender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendResponseCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_RESPONSE_NOT_FOUND = 2;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var CachedResourceManager
     */
    private $cachedResourceManager;

    /**
     * @var ResponseSender
     */
    private $responseSender;

    public function __construct(
        ResponseFactory $responseFactory,
        CachedResourceManager $cachedResourceManager,
        ResponseSender $responseSender
    ) {
        parent::__construct();

        $this->responseFactory = $responseFactory;
        $this->cachedResourceManager =$cachedResourceManager;
        $this->responseSender = $responseSender;
    }

    protected function configure()
    {
        $this
            ->setName('web-resource-cache:send-response')
            ->setDescription('Send the response for a request to the given callback URLs')
            ->addArgument('response-json', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $response = $this->responseFactory->createFromJson($input->getArgument('response-json'));

        if (empty($response)) {
            return self::RETURN_CODE_RESPONSE_NOT_FOUND;
        }

        if ($response instanceof SuccessResponse) {
            $cachedResource = $this->cachedResourceManager->find($response->getRequestId());

            if (empty($cachedResource)) {
                return self::RETURN_CODE_RESOURCE_NOT_FOUND;
            }

            $response = new PresentationDecoratedSuccessResponse($response, $cachedResource);
        }

        // Fix in #140
//        foreach ($retrieveRequest->getCallbackUrls() as $callbackUrl) {
//            $this->responseSender->send($callbackUrl, $response);
//        }

        return self::RETURN_CODE_OK;
    }
}
