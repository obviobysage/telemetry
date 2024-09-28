<?php

namespace ObvioBySage\Telemetry\Tests;

use Illuminate\Support\Facades\Config;
use ObvioBySage\Telemetry\Contracts\TelemetryIndexResolver;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\Support\ArrayableData;
use ObvioBySage\Telemetry\Tests\Support\IndexResolver;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GetIndexTest extends TestCase
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
    public function it_gets_configured_index()
    {
        $indexData = 'configured-index';
        Config::set('telemetry.index', $indexData);

        $result = $this->callMethod($this->obj, 'getIndex', ['dont-matter']);

        $this->assertIsString($result);
        $this->assertEquals($indexData, $result);
    }

    #[Test]
    public function it_gets_configured_index_with_invalid_resolver()
    {
        $indexData = 'configured-index';
        Config::set('telemetry.index', $indexData);

        app()->bind(
            TelemetryIndexResolver::class,
            // Not an instance of TelemetryIndexResolver...
            ArrayableData::class,
        );

        $result = $this->callMethod($this->obj, 'getIndex', ['dont-matter']);

        $this->assertIsString($result);
        $this->assertEquals($indexData, $result);
    }

    #[Test]
    public function it_gets_index_from_resolver()
    {
        $indexData = 'configured-index';
        Config::set('telemetry.index', $indexData);

        app()->bind(
            TelemetryIndexResolver::class,
            IndexResolver::class,
        );

        $result = $this->callMethod(
            $this->obj,
            'getIndex',
            ['the-event', ['some', 'data']]
        );

        $this->assertIsString($result);
        $this->assertEquals('the-event::some,data', $result);
    }
}
