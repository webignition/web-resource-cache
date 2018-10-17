<?php

namespace App\Tests\Unit\Command\HttpCache;

use App\Command\HttpCache\ClearCommand;
use App\Services\Http\HttpCache;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param HttpCache $httpCache
     * @param bool $expectedReturnCode
     *
     * @throws \Exception
     */
    public function testRun(HttpCache $httpCache, bool $expectedReturnCode)
    {
        $command = new ClearCommand($httpCache);

        $this->assertEquals(
            $expectedReturnCode,
            $command->run(new ArrayInput([]), new NullOutput())
        );
    }

    public function runDataProvider()
    {
        return [
            'fail' => [
                'httpCache' => $this->createHttpCache(false),
                'expectedReturnCode' => 1,
            ],
            'success' => [
                'httpCache' => $this->createHttpCache(true),
                'expectedReturnCode' => 0,
            ],
        ];
    }

    private function createHttpCache(bool $clearReturnValue): HttpCache
    {
        $httpCache = \Mockery::mock(HttpCache::class);
        $httpCache
            ->shouldReceive('clear')
            ->andReturn($clearReturnValue);

        return $httpCache;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
