<?php

namespace ObvioBySage\Telemetry\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GetRequestDataTest extends TestCase
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

        Config::set('telemetry.payloads.request.included', true);
    }

    #[Test]
    public function it_sets_nothing_when_config_and_data_are_empty()
    {
        Config::set('telemetry.payloads.request.included', false);
        $this->setProperty($this->obj, 'requestData', null);

        $this->callMethod($this->obj, 'getRequestData', [&$this->payload]);

        $this->assertArrayNotHasKey(Telemetry::KEY_REQUEST, $this->payload);
    }

    #[Test]
    public function it_sets_something_when_config_includes()
    {
        Config::set('telemetry.payloads.request.included', true);
        $this->setProperty($this->obj, 'requestData', null);

        $this->callMethod($this->obj, 'getRequestData', [&$this->payload]);

        $this->assertArrayHasKey(Telemetry::KEY_REQUEST, $this->payload);
    }

    #[Test]
    public function it_sets_something_when_attribute_is_set()
    {
        Config::set('telemetry.payloads.request.included', false);
        $this->setProperty($this->obj, 'requestData', true);

        $this->callMethod($this->obj, 'getRequestData', [&$this->payload]);

        $this->assertArrayHasKey(Telemetry::KEY_REQUEST, $this->payload);
    }

    #[Test]
    public function it_sets_something_when_provided_data()
    {
        $requestData = ['post' => 'data'];
        $this->obj->withRequestData($requestData);

        $this->callMethod($this->obj, 'getRequestData', [&$this->payload]);

        $this->assertArrayHasKey(Telemetry::KEY_REQUEST, $this->payload);
        $this->assertEquals($requestData, $this->payload[Telemetry::KEY_REQUEST]);
    }

    #[Test]
    public function it_sets_standard_data_when_provided_data_is_empty()
    {
        $requestData = [];
        $this->obj->withRequestData($requestData);

        $this->callMethod($this->obj, 'getRequestData', [&$this->payload]);

        $this->assertArrayHasKey(Telemetry::KEY_REQUEST, $this->payload);

        foreach (['uri', 'method', 'agent', 'ip', 'headers'] as $key) {
            $this->assertArrayHasKey(
                $key,
                $this->payload[Telemetry::KEY_REQUEST]
            );
        }
    }

    #[Test]
    public function it_sets_something_when_provided_request()
    {
        $requestData = ['post' => 'data'];
        $request = new Request(request: $requestData);
        $request->setMethod('POST');

        $this->obj->withRequestData($request);

        $this->callMethod($this->obj, 'getRequestData', [&$this->payload]);

        $this->assertArrayHasKey(Telemetry::KEY_REQUEST, $this->payload);
        $this->assertEquals(
            'POST',
            $this->payload[Telemetry::KEY_REQUEST]['method']
        );
        $this->assertEquals(
            $requestData,
            $this->payload[Telemetry::KEY_REQUEST]['data']
        );
    }

    #[Test]
    public function it_sets_headers()
    {
        $headerData = [
            'x-some-thing'  => 'data',
            'x-other-thing' => 'more data',
        ];
        $request = new Request;
        $request->headers->set('x-some-thing', 'data');
        $request->headers->set('x-other-thing', 'more data');
        $request->setMethod('POST');

        Config::set(
            'telemetry.payloads.request.headers',
            ['x-some-thing', 'x-other-thing']
        );

        $this->obj->withRequestData($request);

        $this->callMethod($this->obj, 'getRequestData', [&$this->payload]);

        $this->assertEquals(
            $headerData,
            $this->payload[Telemetry::KEY_REQUEST]['headers']
        );

        $headerData = [
            'x-some-thing'  => 'data',
        ];
        $request = new Request;
        $request->headers->set('x-some-thing', 'data');
        $request->headers->set('x-other-thing', 'more data');
        $request->setMethod('POST');

        Config::set(
            'telemetry.payloads.request.headers',
            ['x-some-thing']
        );

        $this->obj->withRequestData($request);

        $this->callMethod($this->obj, 'getRequestData', [&$this->payload]);

        $this->assertEquals(
            $headerData,
            $this->payload[Telemetry::KEY_REQUEST]['headers']
        );
    }
}
