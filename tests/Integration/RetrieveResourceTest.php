<?php

namespace App\Tests\Integration;

use App\Controller\RequestController;
use App\Entity\CachedResource;
use App\Entity\Callback;
use App\Message\SendResponse;
use App\Model\Response\DecoratedSuccessResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class RetrieveResourceTest extends AbstractEndToEndTestCase
{
    const NGINX_INTEGRATION_HOST = 'nginx-integration';
    const NGINX_INTEGRATION_PORT = 81;

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

    /**
     * @dataProvider retrieveResourceSuccessDataProvider
     *
     * @param string $requestUrlPath
     * @param array $expectedSendResponseData
     */
    public function testRetrieveResourceSuccess(string $requestUrlPath, array $expectedSendResponseData)
    {
        $requestController = self::$container->get(RequestController::class);

        $requestData = [
            'url' => $requestUrlPath,
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

        foreach ($expectedSendResponseData['headers'] as $key => $value) {
            $this->assertArrayHasKey($key, $logData['headers']);
            $this->assertEquals($value, $logData['headers'][$key]);
        }

        $this->assertInternalType('string', $logData['content']);
        $this->assertNotEmpty($logData['content']);
        $this->assertEquals($expectedSendResponseData['content'], $logData['content']);
    }

    public function retrieveResourceSuccessDataProvider(): array
    {
        return [
            'text/html' => [
                'requestUrlPath' => $this->createNginxIntegrationRequestUrl('/example.html'),
                'expectedSendResponseData' => [
                    'headers' => [
                        'content-type' => [
                            'text/html',
                        ],
                    ],
                    'content' => base64_encode($this->loadFixture('/example.html')),
                ],
            ],
            'text/css' => [
                'requestUrlPath' => $this->createNginxIntegrationRequestUrl('/example.css'),
                'expectedSendResponseData' => [
                    'headers' => [
                        'content-type' => [
                            'text/css',
                        ],
                    ],
                    'content' => base64_encode($this->loadFixture('/example.css')),
                ],
            ],
            'application/javascript' => [
                'requestUrlPath' => $this->createNginxIntegrationRequestUrl('/example.js'),
                'expectedSendResponseData' => [
                    'headers' => [
                        'content-type' => [
                            'application/javascript',
                        ],
                    ],
                    'content' => base64_encode($this->loadFixture('/example.js')),
                ],
            ],
            'image/gif' => [
                'requestUrlPath' => $this->createNginxIntegrationRequestUrl('/example.gif'),
                'expectedSendResponseData' => [
                    'headers' => [
                        'content-type' => [
                            'image/gif',
                        ],
                    ],
                    'content' => base64_encode($this->loadFixture('/example.gif')),
                ],
            ],
            'image/jpeg' => [
                'requestUrlPath' => $this->createNginxIntegrationRequestUrl('/example.jpg'),
                'expectedSendResponseData' => [
                    'headers' => [
                        'content-type' => [
                            'image/jpeg',
                        ],
                    ],
                    'content' => base64_encode($this->loadFixture('/example.jpg')),
                ],
            ],
            'image/png' => [
                'requestUrlPath' => $this->createNginxIntegrationRequestUrl('/example.png'),
                'expectedSendResponseData' => [
                    'headers' => [
                        'content-type' => [
                            'image/png',
                        ],
                    ],
                    'content' => base64_encode($this->loadFixture('/example.png')),
                ],
            ],
        ];
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

    private function createNginxIntegrationRequestUrl(string $path): string
    {
        return sprintf(
            'http://%s:%s%s',
            self::NGINX_INTEGRATION_HOST,
            self::NGINX_INTEGRATION_PORT,
            $path
        );
    }

    private function loadFixture(string $path): string
    {
        $fixturePath = sprintf(
            '/app/tests/Fixtures/Integration/Nginx%s',
            $path
        );

        return file_get_contents($fixturePath);
    }
}
