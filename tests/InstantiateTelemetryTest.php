<?php

namespace ObvioBySage\Telemetry\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\Support\ArrayableData;
use ObvioBySage\Telemetry\Tests\Support\AuthUser;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InstantiateTelemetryTest extends TestCase
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
    public function it_sets_known_request_data_types()
    {
        $withBoolean = new Telemetry;
        $withBoolean->withRequestData(true);

        $this->assertTrue($this->getProperty($withBoolean, 'requestData'));

        $withBoolean->withRequestData(false);

        $this->assertFalse($this->getProperty($withBoolean, 'requestData'));

        $arrayData = ['request' => 'data'];
        $withArray = new Telemetry;
        $withArray->withRequestData($arrayData);

        $this->assertIsArray($this->getProperty($withArray, 'requestData'));
        $this->assertEquals(
            $arrayData,
            $this->getProperty($withArray, 'requestData')
        );

        $requestData = new Request;
        $withRequest = new Telemetry;
        $withRequest->withRequestData($requestData);

        $this->assertInstanceOf(
            Request::class,
            $this->getProperty($withRequest, 'requestData')
        );
        $this->assertEquals(
            $requestData,
            $this->getProperty($withRequest, 'requestData')
        );
    }

    #[Test]
    public function it_sets_request_data_and_chains()
    {
        $this->assertInstanceOf(
            Telemetry::class,
            $this->obj->withRequestData(true),
        );
    }

    #[Test]
    public function it_sets_response_data()
    {
        $content = 'response content';
        $status = 204;
        $headers = ['some-header' => ['some-value']];

        $responseData = new Response($content, $status, $headers);
        $this->obj->withResponseData($responseData);

        $this->assertIsArray($this->getProperty($this->obj, 'responseData'));
        $this->assertEquals(
            $content,
            $this->getProperty($this->obj, 'responseData')['content']
        );
        $this->assertEquals(
            $status,
            $this->getProperty($this->obj, 'responseData')['status']
        );
        $this->assertArrayHasKey(
            'some-header',
            $this->getProperty($this->obj, 'responseData')['headers']
        );
    }

    #[Test]
    public function it_sets_response_data_and_chains()
    {
        $this->assertInstanceOf(
            Telemetry::class,
            $this->obj->withResponseData(new Response),
        );
    }

    #[Test]
    public function it_sets_user_data()
    {
        $this->obj->withUserData();

        $this->assertNull($this->getProperty($this->obj, 'withUser'));

        $this->obj->withUserData(false);

        $this->assertFalse($this->getProperty($this->obj, 'withUser'));

        $arrayData = ['some' => 'data'];
        $this->obj->withUserData($arrayData);

        $this->assertIsArray($this->getProperty($this->obj, 'withUser'));
        $this->assertEquals($arrayData, $this->getProperty($this->obj, 'withUser'));

        $arrayableData = new ArrayableData('arrayable', 'data');
        $this->obj->withUserData($arrayableData);

        $this->assertInstanceOf(
            ArrayableData::class,
            $this->getProperty($this->obj, 'withUser')
        );
        $this->assertEquals(
            $arrayableData,
            $this->getProperty($this->obj, 'withUser')
        );

        $telemetryData = new AuthUser;
        $this->obj->withUserData($telemetryData);

        $this->assertInstanceOf(
            AuthUser::class,
            $this->getProperty($this->obj, 'withUser')
        );
        $this->assertEquals(
            $telemetryData,
            $this->getProperty($this->obj, 'withUser')
        );
    }

    #[Test]
    public function it_sets_user_data_and_chains()
    {
        $this->assertInstanceOf(
            Telemetry::class,
            $this->obj->withUserData(true),
        );
    }

    #[Test]
    public function it_sets_event()
    {
        $stringData = 'the-event';
        $this->obj->event($stringData);

        $this->assertIsString($this->getProperty($this->obj, 'event'));
        $this->assertEquals(
            $stringData,
            $this->getProperty($this->obj, 'event'),
        );
    }

    #[Test]
    public function it_sets_event_and_chains()
    {
        $this->assertInstanceOf(
            Telemetry::class,
            $this->obj->event(''),
        );
    }

    #[Test]
    public function it_sets_data_with_array()
    {
        $arrayData = ['some' => 'data'];
        $this->obj->data($arrayData);

        $this->assertIsArray($this->getProperty($this->obj, 'data'));
        $this->assertEquals($arrayData, $this->getProperty($this->obj, 'data'));
    }

    #[Test]
    public function it_sets_data_with_arrayable()
    {
        $arrayableData = new ArrayableData('some', 'data');
        $this->obj->data($arrayableData);

        $this->assertIsArray($this->getProperty($this->obj, 'data'));
        $this->assertEquals(
            ['some' => 'data'],
            $this->getProperty($this->obj, 'data')
        );
    }

    #[Test]
    public function it_sets_data_and_chains()
    {
        $this->assertInstanceOf(
            Telemetry::class,
            $this->obj->data([]),
        );
    }
}
