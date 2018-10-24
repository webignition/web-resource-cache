<?php

namespace App\Tests\Unit\Services;

use App\Entity\CachedResource;
use App\Services\CachedResourceValidator;
use webignition\HttpHeaders\Headers;

class CachedResourceValidatorTest extends \PHPUnit\Framework\TestCase
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
        $resource = \Mockery::mock(CachedResource::class);
        $resource
            ->shouldReceive('getHeaders')
            ->andReturn($resourceHeaders);

        $resource
            ->shouldReceive('getStoredAge')
            ->andReturn($resourceAge);

        $resourceValidator = new CachedResourceValidator($cacheControlMinFresh);

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
                'cacheControlMinFresh' => 0,
                'resourceHeaders' => new Headers([
                    'cache-control' => 'max-age=600',
                ]),
                'resourceAge' => 1,
                'expectedIsFresh' => true,
            ],
            'cache-control: max-age=600; expires in the past' => [
                'cacheControlMinFresh' => 0,
                'resourceHeaders' => new Headers([
                    'cache-control' => 'max-age=600',
                    'expires' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                ]),
                'resourceAge' => 1,
                'expectedIsFresh' => true,
            ],
            'no cache-control, older than service min-fresh, hasExpired=true' => [
                'cacheControlMinFresh' => 0,
                'resourceHeaders' => $this->createHeaders([
                    'age' => 1,
                    'hasExpired' => true,
                ]),
                'resourceAge' => 1,
                'expectedIsFresh' => false,
            ],
            'no cache-control, service min-fresh > resource age, stored age > service min-fresh' => [
                'cacheControlMinFresh' => 10,
                'resourceHeaders' => $this->createHeaders([
                    'age' => 3,
                    'hasExpired' => false,
                ]),
                'resourceAge' => 20,
                'expectedIsFresh' => true,
            ],
        ];
    }

    private function createHeaders($args)
    {
        $headers = \Mockery::mock(Headers::class);

        if (!isset($args['cache-control'])) {
            $args['cache-control'] = [];
        }

        $headers
            ->shouldReceive('get')
            ->with('cache-control')
            ->andReturn($args['cache-control']);

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
