<?php

namespace App\Tests\Unit\Services;

use App\Entity\Resource;
use App\Model\Headers;
use App\Services\ResourceValidator;

class ResourceValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isFreshDataProvider
     *
     * @param int $cacheControlMinFresh
     * @param Headers $resourceHeaders
     * @param int $resourceAge
     * @param bool $expectedIsFresh
     */
    public function testIsFresh(
        int $cacheControlMinFresh,
        Headers $resourceHeaders,
        int $resourceAge,
        bool $expectedIsFresh
    ) {
        $resource = \Mockery::mock(Resource::class);
        $resource
            ->shouldReceive('getHeaders')
            ->andReturn($resourceHeaders);

        $resource
            ->shouldReceive('getStoredAge')
            ->andReturn($resourceAge);

        $resourceValidator = new ResourceValidator($cacheControlMinFresh);

        $this->assertEquals($expectedIsFresh, $resourceValidator->isFresh($resource));
    }

    public function isFreshDataProvider(): array
    {
        return [
            'no resource headers, age=0, no service min-fresh' => [
                'cacheControlMinFresh' => 0,
                'resourceHeaders' => new Headers(),
                'resourceAge' => 0,
                'expectedIsFresh' => true,
            ],
            'no resource headers, age=0, service min-fresh=3600' => [
                'cacheControlMinFresh' => 3600,
                'resourceHeaders' => new Headers(),
                'resourceAge' => 0,
                'expectedIsFresh' => true,
            ],
            'no resource headers, age=120, service min-fresh=3600' => [
                'cacheControlMinFresh' => 3600,
                'resourceHeaders' => new Headers(),
                'resourceAge' => 120,
                'expectedIsFresh' => true,
            ],
            'no resource headers, age=200, service min-fresh=100' => [
                'cacheControlMinFresh' => 100,
                'resourceHeaders' => new Headers(),
                'resourceAge' => 200,
                'expectedIsFresh' => false,
            ],
            'cache-control: no-cache' => [
                'cacheControlMinFresh' => 0,
                'resourceHeaders' => new Headers([
                    'cache-control' => 'no-cache',
                ]),
                'resourceAge' => 1,
                'expectedIsFresh' => false,
            ],
            'cache-control: no-store' => [
                'cacheControlMinFresh' => 0,
                'resourceHeaders' => new Headers([
                    'cache-control' => 'no-store',
                ]),
                'resourceAge' => 1,
                'expectedIsFresh' => false,
            ],
            'cache-control: max-age=600' => [
                'cacheControlMinFresh' => 200,
                'resourceHeaders' => new Headers([
                    'cache-control' => 'max-age=600',
                ]),
                'resourceAge' => 1,
                'expectedIsFresh' => true,
            ],
            'cache-control: max-age=600; expires in the past' => [
                'cacheControlMinFresh' => 200,
                'resourceHeaders' => new Headers([
                    'cache-control' => 'max-age=600',
                    'expires' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                ]),
                'resourceAge' => 1,
                'expectedIsFresh' => true,
            ],
            'no cache-control, has last-modified older than service min-fresh' => [
                'cacheControlMinFresh' => 0,
                'resourceHeaders' => $this->createHeaders([
                    'age' => 1,
                    'hasExpired' => false,
                ]),
                'resourceAge' => 1,
                'expectedIsFresh' => false,
            ],
        ];
    }

    private function createHeaders($args)
    {
        $headers = \Mockery::mock(Headers::class);

        if (!isset($args['cache-control'])) {
            $args['cache-control'] = '';
        }

        $headers
            ->shouldReceive('get')
            ->with('cache-control')
            ->andReturn($args['cache-control']);

        if (isset($args['last-modified'])) {
            $headers
                ->shouldReceive('getLastModified')
                ->andReturn($args['last-modified']);
        }

        if (isset($args['age'])) {
            $headers
                ->shouldReceive('getAge')
                ->andReturn($args['age']);
        }

        if (isset($args['hasExpired'])) {
            $headers
                ->shouldReceive('hasExpired')
                ->andReturn($args['hasExpired']);
        }

        return $headers;
    }
}
