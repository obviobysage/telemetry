<?php

namespace ObvioBySage\Telemetry\Tests;

use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ThreadIdTest extends TestCase
{
    /**
     * @var Telemetry
     */
    protected $obj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obj = new Telemetry;
    }

    #[Test]
    public function it_gets_threadId_from_container_when_set()
    {
        $threadIdData = 'the-thread-id';

        app()->bind(Telemetry::KEY_THREAD_IOC, fn () => $threadIdData);

        $this->assertEquals($threadIdData, $this->obj->threadId());
    }

    #[Test]
    public function it_gets_null_from_container_when_not_set()
    {
        $this->assertNull($this->obj->threadId());
    }

    #[Test]
    public function it_sets_threadId()
    {
        $threadIdData = 'the-thread-id';

        $this->obj->threadId($threadIdData);

        $this->assertEquals(
            $threadIdData,
            app(Telemetry::KEY_THREAD_IOC)
        );
    }
}
