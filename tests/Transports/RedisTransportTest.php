<?php

namespace ObvioBySage\Telemetry\Tests\Transports;

use Illuminate\Support\Facades\Redis;
use ObvioBySage\Telemetry\Exceptions\RedisConnectionException;
use ObvioBySage\Telemetry\Tests\Support\MockRedis;
use ObvioBySage\Telemetry\Tests\TestCase;
use ObvioBySage\Telemetry\Transports\RedisTransport;
use PHPUnit\Framework\Attributes\Test;

class RedisTransportTest extends TestCase
{
    #[Test]
    public function it_validates_connection()
    {
        $this->expectException(RedisConnectionException::class);
        $this->expectExceptionMessage('Invalid redis connection');

        (new RedisTransport())->validateConnection();
    }

    #[Test]
    public function it_validates_queue()
    {
        $this->expectException(RedisConnectionException::class);
        $this->expectExceptionMessage('Invalid redis queue');

        (new RedisTransport('the-connection'))->validateConnection();
    }

    #[Test]
    public function it_publishes()
    {
        $connectionData = 'the-connection';
        $queueData = 'the-queue';
        $payloadData = ['payload' => 'data'];

        $mock = $this->getMockBuilder(MockRedis::class)
            ->onlyMethods(['rpush'])
            ->getMock();

        $mock->expects($this->once())
            ->method('rpush')
            ->with($queueData, json_encode($payloadData))
            ->willReturn(true);

        Redis::shouldReceive('connection')
            ->once()
            ->with($connectionData)
            ->andReturn($mock);

        $obj = new RedisTransport($connectionData, $queueData);

        $this->assertTrue($obj->publish($payloadData));
    }
}
