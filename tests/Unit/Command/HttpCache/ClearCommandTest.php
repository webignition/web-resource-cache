<?php

namespace App\Tests\Unit\Command\HttpCache;

use App\Command\HttpCache\ClearCommand;
use App\Services\Http\Cache;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param Cache $cache
     * @param bool $expectedReturnCode
     *
     * @throws \Exception
     */
    public function testRun(Cache $cache, bool $expectedReturnCode)
    {
        $command = new ClearCommand($cache);

        $this->assertEquals(
            $expectedReturnCode,
            $command->run(new ArrayInput([]), new NullOutput())
        );
    }

    public function runDataProvider()
    {
        return [
            'fail' => [
                'cache' => $this->createHttpCache(false),
                'expectedReturnCode' => 1,
            ],
            'success' => [
                'cache' => $this->createHttpCache(true),
                'expectedReturnCode' => 0,
            ],
        ];
    }

    private function createHttpCache(bool $clearReturnValue): Cache
    {
        $cache = \Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('clear')
            ->andReturn($clearReturnValue);

        return $cache;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
