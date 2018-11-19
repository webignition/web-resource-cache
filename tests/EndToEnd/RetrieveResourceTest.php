<?php

namespace App\Tests\EndToEnd;

use App\Controller\RequestController;
use App\Entity\CachedResource;
use App\Entity\Callback;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class RetrieveResourceTest extends AbstractEndToEndTestCase
{
    /**
     * @var string
     */
    private $callbackResponseLogPath;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    protected function setUp()
    {
        parent::setUp();

        $this->callbackResponseLogPath = self::$container->getParameter('kernel.logs_dir') . '/callback-responses';
        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $this->removeEntities(CachedResource::class);
        $this->removeEntities(Callback::class);
        $this->removeCallbackResponseLogs();
    }

    public function testRetrieveResource()
    {
        $requestController = self::$container->get(RequestController::class);

        $requestData = [
            'url' => 'http://example.com/',
            'callback' => 'http://httpbin/post',
            'log-callback-response' => 1,
        ];

        $controllerResponse = $requestController->requestAction(new Request([], $requestData));

        $requestId = json_decode($controllerResponse->getContent());

        $logFilePath = $this->callbackResponseLogPath . '/' . $requestId . '.json';

        $waitLimitInSeconds = 10;
        $oneSecondInMicroseconds = 1000000;
        $waitLimitInMicroseconds = $waitLimitInSeconds * $oneSecondInMicroseconds;

        $waitTotal = 0;

        while (!file_exists($logFilePath) && $waitTotal < $waitLimitInMicroseconds) {
            usleep(1000);
            $waitTotal += 1000;
        }

        $this->assertLessThan(
            $waitLimitInMicroseconds,
            $waitTotal,
            sprintf('Wait time exceeded %s seconds', $waitLimitInSeconds)
        );

        $this->assertTrue(file_exists($logFilePath), sprintf('File "%s" does not exist', $logFilePath));

        $logData = json_decode(file_get_contents($logFilePath), true);


        $this->assertEquals($requestId, $logData['request_id']);
        $this->assertEquals('success', $logData['status']);
        $this->assertInternalType('array', $logData['headers']);
        $this->assertNotEmpty($logData['headers']);
        $this->assertInternalType('string', $logData['content']);
        $this->assertNotEmpty($logData['content']);
    }

    private function removeCallbackResponseLogs()
    {
        $files = glob($this->callbackResponseLogPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function removeEntities($className)
    {
        $cachedResourceRepository = $this->entityManager->getRepository($className);
        $callbacks = $cachedResourceRepository->findAll();

        foreach ($callbacks as $cachedResource) {
            $this->entityManager->remove($cachedResource);
            $this->entityManager->flush();
        }
    }
}
