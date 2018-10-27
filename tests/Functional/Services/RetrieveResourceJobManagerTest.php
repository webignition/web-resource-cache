<?php

namespace App\Tests\Functional\Services;

use App\Model\RetrieveRequest;
use App\Resque\Job\RetrieveResourceJob;
use App\Resque\Job\SendResponseJob;
use App\Services\RetrieveResourceJobManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use ResqueBundle\Resque\Job;
use webignition\HttpHeaders\Headers;

class RetrieveResourceJobManagerTest extends AbstractFunctionalTestCase
{
    /**
     * @var RetrieveResourceJobManager
     */
    private $resourceJobManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->resourceJobManager = self::$container->get(RetrieveResourceJobManager::class);
    }

    /**
     * @dataProvider containsSuccessDataProvider
     *
     * @param Job[] $jobsToEnqueue
     * @param Job $job
     * @param bool $expectedContains
     */
    public function testContainsSuccess(array $jobsToEnqueue, Job $job, $expectedContains)
    {
        $this->clearRedis();

        foreach ($jobsToEnqueue as $jobToEnqueue) {
            $this->resourceJobManager->enqueue($jobToEnqueue);
        }

        $this->assertEquals($expectedContains, $this->resourceJobManager->contains($job));
    }

    public function containsSuccessDataProvider(): array
    {
        return [
            'not RetrieveResourceJob' => [
                'jobsToEnqueue' => [],
                'job' => new SendResponseJob(),
                'expectedContains' => false,
            ],
            'invalid RetrieveRequest' => [
                'jobsToEnqueue' => [],
                'job' => new RetrieveResourceJob(),
                'expectedContains' => false,
            ],
            'requestHash not match' => [
                'jobsToEnqueue' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            'non-matching-request-hash',
                            'http://example.com/',
                            new Headers([
                                'foo' => 'bar',
                            ])
                        )),
                    ]),
                ],
                'job' => new RetrieveResourceJob([
                    'request-json' => json_encode(new RetrieveRequest(
                        'matching-request-hash',
                        'http://example.com/',
                        new Headers([
                            'foo' => 'bar',
                        ])
                    )),
                ]),
                'expectedContains' => false,
            ],
            'url not match' => [
                'jobsToEnqueue' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            'matching-request-hash',
                            'http://foo.example.com/',
                            new Headers([
                                'foo' => 'bar',
                            ])
                        )),
                    ]),
                ],
                'job' => new RetrieveResourceJob([
                    'request-json' => json_encode(new RetrieveRequest(
                        'matching-request-hash',
                        'http://example.com/',
                        new Headers([
                            'foo' => 'bar',
                        ])
                    )),
                ]),
                'expectedContains' => false,
            ],
            'headers not match' => [
                'jobsToEnqueue' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            'matching-request-hash',
                            'http://example.com/',
                            new Headers([
                                'fizz' => 'buzz',
                            ])
                        )),
                    ]),
                ],
                'job' => new RetrieveResourceJob([
                    'request-json' => json_encode(new RetrieveRequest(
                        'matching-request-hash',
                        'http://example.com/',
                        new Headers([
                            'foo' => 'bar',
                        ])
                    )),
                ]),
                'expectedContains' => false,
            ],
            'match no headers' => [
                'jobsToEnqueue' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            'matching-request-hash',
                            'http://example.com/'
                        )),
                    ]),
                ],
                'job' => new RetrieveResourceJob([
                    'request-json' => json_encode(new RetrieveRequest(
                        'matching-request-hash',
                        'http://example.com/'
                    )),
                ]),
                'expectedContains' => true,
            ],
            'match has headers' => [
                'jobsToEnqueue' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            'matching-request-hash',
                            'http://example.com/',
                            new Headers([
                                'foo' => 'bar',
                            ])
                        )),
                    ]),
                ],
                'job' => new RetrieveResourceJob([
                    'request-json' => json_encode(new RetrieveRequest(
                        'matching-request-hash',
                        'http://example.com/',
                        new Headers([
                            'foo' => 'bar',
                        ])
                    )),
                ]),
                'expectedContains' => true,
            ],
        ];
    }
}
