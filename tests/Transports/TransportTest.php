<?php

namespace ObvioBySage\Telemetry\Tests\Transports;

use Illuminate\Support\Facades\Config;
use ObvioBySage\Telemetry\Exceptions\TransportException;
use ObvioBySage\Telemetry\Tests\Support\AuthUser;
use ObvioBySage\Telemetry\Tests\TestCase;
use ObvioBySage\Telemetry\Transports\RedisTransport;
use ObvioBySage\Telemetry\Transports\Transport;
use PHPUnit\Framework\Attributes\Test;

class TransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('telemetry.connections.redis', [
            'driver' => Transport::DRIVER_REDIS,
        ]);
    }

    #[Test]
    public function it_resolves_tranpsort_from_constructor()
    {
        $connectionData = 'theTransport';

        $mock = $this->getMockBuilder(Transport::class)
            ->onlyMethods(['resolveTransport'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->once())
            ->method('resolveTransport')
            ->with($connectionData)
            ->willReturnSelf();

        $mock->__construct($connectionData);
    }

    #[Test]
    public function it_resolves_redis_transport_from_details()
    {
        $mock = $this->getMockBuilder(RedisTransport::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateConnection'])
            ->getMock();

        $mock->expects($this->once())
            ->method('validateConnection')
            ->willReturnSelf();

        app()->bind(RedisTransport::class, function () use ($mock) {
            return $mock;
        });

        $obj = new Transport('redis');

        $this->assertInstanceOf(
            RedisTransport::class,
            $this->getProperty($obj, 'transport')
        );
    }

    #[Test]
    public function it_throws_exception_when_TelemetryTransport_is_not_resolved()
    {
        app()->bind(RedisTransport::class, function () {
            return new AuthUser;
        });

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage(
            'Telemtry driver "redis" is not a valid TelemetryTransport'
        );

        new Transport('redis');
    }

    #[Test]
    public function it_resolves_configured_transport()
    {
        Config::set('telemetry.connections.unknown', [
            'driver'    => 'unknown',
            'transport' => '\\ObvioBySage\\Telemetry\\Tests\\Support\\AuthUser',
        ]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage(
            'Telemtry driver "unknown" is not a valid TelemetryTransport'
        );

        $obj = new Transport('unknown');

        $this->assertInstanceOf(AuthUser::class, $this->getProperty($obj, 'transport'));
    }

    #[Test]
    public function it_returns_null_when_publish_payload_is_empty()
    {
        $mock = $this->getMockBuilder(RedisTransport::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateConnection'])
            ->getMock();

        $mock->expects($this->once())
            ->method('validateConnection')
            ->willReturnSelf();

        app()->bind(RedisTransport::class, function () use ($mock) {
            return $mock;
        });

        $obj = new Transport('redis');

        $this->assertNull($obj->publish());
    }

    #[Test]
    public function it_returns_from_transports_publish()
    {
        $returnData = 'the-return';

        $mock = $this->getMockBuilder(RedisTransport::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateConnection', 'publish'])
            ->getMock();

        $mock->expects($this->once())
            ->method('validateConnection')
            ->willReturnSelf();

        $mock->expects($this->once())
            ->method('publish')
            ->willReturn($returnData);

        app()->bind(RedisTransport::class, function () use ($mock) {
            return $mock;
        });

        $obj = new Transport('redis');

        $this->assertEquals($returnData, $obj->publish(['publish' => 'data']));
    }
}
