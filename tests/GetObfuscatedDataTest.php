<?php

namespace ObvioBySage\Telemetry\Tests;

use Illuminate\Support\Facades\Config;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GetObfuscatedDataTest extends TestCase
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

        $this->payload = [
            'some'  => 'data',
            'lower' => [
                'garbage' => 'nope',
                'some'    => 'other-data-value',
            ],
            'last'  => 'one',
        ];
    }

    #[Test]
    public function it_returns_without_obfuscation_when_data_keys_are_not_set()
    {
        $result = $this->callMethod($this->obj, 'getObfuscatedData', [$this->payload]);

        $this->assertEquals($this->payload, $result);
    }

    #[Test]
    public function it_returns_obfuscated_data()
    {
        Config::set('telemetry.payloads.obfuscated_data_keys', ['last']);

        $result = $this->callMethod($this->obj, 'getObfuscatedData', [$this->payload]);

        $this->assertEquals(
            array_keys($this->payload),
            array_keys($result)
        );
        $this->assertEquals(
            str_repeat('*', strlen($this->payload['last'])),
            $result['last']
        );
    }

    #[Test]
    public function it_returns_obfuscated_data_recursively()
    {
        Config::set('telemetry.payloads.obfuscated_data_keys', ['some']);

        $result = $this->callMethod($this->obj, 'getObfuscatedData', [$this->payload]);

        $this->assertEquals(
            array_keys($this->payload),
            array_keys($result)
        );
        $this->assertEquals(
            str_repeat('*', strlen($this->payload['some'])),
            $result['some']
        );
        $this->assertEquals(
            str_repeat('*', strlen($this->payload['lower']['some'])),
            $result['lower']['some']
        );
    }
}
