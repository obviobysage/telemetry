<?php

namespace ObvioBySage\Telemetry\Tests;

use Exception;
use Illuminate\Support\Facades\Config;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\TestCase;
use ObvioBySage\Telemetry\Transports\RedisTransport;
use PHPUnit\Framework\Attributes\Test;

class FireTest extends TestCase
{
    /**
     * @var Telemetry
     */
    protected $obj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obj = new Telemetry;
        Config::set('telemetry.enabled', true);
    }

    #[Test]
    public function it_returns_false_when_telemetry_is_not_enabled()
    {
        Config::set('telemetry.enabled', false);

        $this->assertFalse($this->obj->fire());
    }

    #[Test]
    public function it_returns_null_when_missing_event_and_no_notifications()
    {
        $this->assertNull($this->obj->fire());
    }

    #[Test]
    public function it_returns_null_when_invalid_transport_and_no_notifications()
    {
        $this->obj->event('the.event');

        $this->assertNull($this->obj->fire());

    }

    #[Test]
    public function it_notifyExceptions_and_returns_null_when_missing_event()
    {
        $mock = $this->getMockBuilder(Telemetry::class)
            ->onlyMethods(['notifyException'])
            ->getMock();

        $mock->expects($this->once())
            ->method('notifyException')
            ->willReturn(null);

        $this->assertNull($mock->fire());
    }

    #[Test]
    public function it_notifyExceptions_and_returns_null_when_invalid_transport()
    {
        $mock = $this->getMockBuilder(Telemetry::class)
            ->onlyMethods(['notifyException'])
            ->getMock();

        $mock->expects($this->once())
            ->method('notifyException')
            ->willReturn(null);

        $mock->event('the.event');

        $this->assertNull($mock->fire());
    }

    #[Test]
    public function it_should_get_default_transport()
    {
        $transportData = 'theTransport';

        Config::set('telemetry.default_transport', $transportData);

        $mock = $this->getMockBuilder(Telemetry::class)
            ->onlyMethods(['getTransportByConnection'])
            ->getMock();

        $mock->expects($this->once())
            ->method('getTransportByConnection')
            ->with($transportData)
            ->willThrowException(new Exception);

        $mock->event('the.event');

        $this->assertNull($mock->fire());
    }

    #[Test]
    public function it_should_get_supplied_transport()
    {
        $suppliedTransportData = 'suppliedTransport';
        $transportData = 'theTransport';

        Config::set('telemetry.default_transport', $transportData);

        $mock = $this->getMockBuilder(Telemetry::class)
            ->onlyMethods(['getTransportByConnection'])
            ->getMock();

        $mock->expects($this->once())
            ->method('getTransportByConnection')
            ->with($suppliedTransportData)
            ->willThrowException(new Exception);

        $mock->event('the.event');

        $this->assertNull($mock->fire($suppliedTransportData));
    }

    #[Test]
    public function it_should_getPayload_for_transport()
    {
        $payloadData = ['payload' => 'data'];

        Config::set('telemetry.default_transport', 'redis');
        Config::set('telemtry.connections.redis.connection', 'derp');
        Config::set('telemtry.connections.redis.queue', 'derp');

        $mockTransport = $this->getMockBuilder(RedisTransport::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['publish'])
            ->getMock();

        $mockTransport->expects($this->once())
            ->method('publish')
            ->with($payloadData)
            ->willReturn(true);

        $mock = $this->getMockBuilder(Telemetry::class)
            ->onlyMethods(['getTransportByConnection', 'getPayload'])
            ->getMock();

        $mock->expects($this->once())
            ->method('getTransportByConnection')
            ->willReturn($mockTransport);

        $mock->expects($this->once())
            ->method('getPayload')
            ->willReturn($payloadData);

        $mock->event('the.event');

        $this->assertTrue($mock->fire());
    }
}
