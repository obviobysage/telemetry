<?php

namespace ObvioBySage\Telemetry\Tests;

use Illuminate\Support\Facades\Config;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\Support\TelemetryVarsData;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GetVarsTest extends TestCase
{
    /**
     * @var Telemetry
     */
    protected $obj;

    /**
     * @var array
     */
    protected $payload = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->obj = new Telemetry;
    }

    #[Test]
    public function it_sets_nothing_when_config_is_empty()
    {
        $this->callMethod($this->obj, 'getVars', [&$this->payload]);

        $this->assertEmpty($this->payload);
    }

    #[Test]
    public function it_sets_something_when_config_includes()
    {
        $varsKey = 'vars';
        $varsValue = 'value';
        $varsData = [$varsKey => $varsValue];

        Config::set('telemetry.payloads.vars', $varsData);

        $this->callMethod($this->obj, 'getVars', [&$this->payload]);

        $this->assertArrayHasKey($varsKey, $this->payload);
        $this->assertEquals($varsData, $this->payload);
    }

    #[Test]
    public function it_merges_when_config_includes()
    {
        $varsKey = 'vars';
        $varsValue = 'value';
        $varsData = [$varsKey => $varsValue];

        Config::set('telemetry.payloads.vars', $varsData);

        $existingData = ['existing' => 'data'];
        $this->payload = $existingData;

        $this->callMethod($this->obj, 'getVars', [&$this->payload]);

        $this->assertEquals(
            array_merge($existingData, $varsData),
            $this->payload
        );
    }

    #[Test]
    public function it_sets_something_when_TelemetryVars()
    {
        $telemetryVarsData = new TelemetryVarsData;

        Config::set('telemetry.payloads.vars', $telemetryVarsData);

        $this->callMethod($this->obj, 'getVars', [&$this->payload]);

        $this->assertEquals(
            $telemetryVarsData->getVars(),
            $this->payload
        );
    }

    #[Test]
    public function it_sets_something_when_TelemetryVars_class()
    {
        Config::set('telemetry.payloads.vars', TelemetryVarsData::class);

        $this->callMethod($this->obj, 'getVars', [&$this->payload]);

        $this->assertEquals(
            (new TelemetryVarsData)->getVars(),
            $this->payload
        );
    }

    #[Test]
    public function it_sets_nothing_when_config_is_not_valid()
    {
        Config::set('telemetry.payloads.vars', 'not-valid');

        $this->callMethod($this->obj, 'getVars', [&$this->payload]);

        $this->assertEmpty($this->payload);

        Config::set('telemetry.payloads.vars', null);

        $this->callMethod($this->obj, 'getVars', [&$this->payload]);

        $this->assertEmpty($this->payload);
    }
}
