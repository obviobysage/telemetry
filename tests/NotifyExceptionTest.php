<?php

namespace ObvioBySage\Telemetry\Tests;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NotifyExceptionTest extends TestCase
{
    /**
     * @var Telemetry
     */
    protected $obj;

    /**
     * @var Exception
     */
    protected $exception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obj = new Telemetry;
        $this->exception = new Exception('Exception Message');
    }

    #[Test]
    public function it_logs_to_channel()
    {
        $loggingData = 'defaultStack';
        Config::set('telemetry.notifications.logging_channel', $loggingData);

        Log::shouldReceive('channel')
            ->once()
            ->with($loggingData)
            ->andReturnSelf()
            ->shouldReceive('warning')
            ->once()
            ->with('Telemetry Error: ' . $this->exception->getMessage());

        $this->callMethod($this->obj, 'notifyException', [$this->exception]);
    }

    #[Test]
    public function it_logs_to_channel_and_throws_exception()
    {
        $loggingData = 'defaultStack';
        Config::set('telemetry.notifications.logging_channel', $loggingData);
        Config::set('telemetry.notifications.throw_transport_exceptions', true);

        Log::shouldReceive('channel')
            ->once()
            ->with($loggingData)
            ->andReturnSelf()
            ->shouldReceive('warning')
            ->once()
            ->with('Telemetry Error: ' . $this->exception->getMessage());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($this->exception->getMessage());

        $this->callMethod($this->obj, 'notifyException', [$this->exception]);
    }
}
