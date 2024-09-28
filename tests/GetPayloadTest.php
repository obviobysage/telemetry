<?php

namespace ObvioBySage\Telemetry\Tests;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\Support\ArrayableData;
use ObvioBySage\Telemetry\Tests\Support\AuthUser;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GetPayloadTest extends TestCase
{
    /**
     * @var string
     */
    protected $event = 'the-event';

    /**
     * @var Telemetry
     */
    protected $obj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obj = new Telemetry;
        $this->obj->event($this->event);
        Config::set('telemetry.index', false);
    }

    #[Test]
    public function it_returns_payload()
    {
        $env = 'the-env';
        $hostName = gethostname();
        $hostIp = gethostbyname(gethostname());
        Config::set('app.env', $env);

        $result = $this->callMethod($this->obj, 'getPayload');

        $this->assertArrayHasKey(Telemetry::KEY_ENV, $result);
        $this->assertEquals($env, $result[Telemetry::KEY_ENV]);
        $this->assertArrayHasKey(Telemetry::KEY_EVENT, $result);
        $this->assertEquals($this->event, $result[Telemetry::KEY_EVENT]);
        $this->assertArrayHasKey(Telemetry::KEY_EVENT_TS, $result);
        $this->assertEquals(now()->timestamp, $result[Telemetry::KEY_EVENT_TS]);
        $this->assertArrayHasKey(Telemetry::KEY_HOST, $result);
        $this->assertEquals(
            $hostName,
            $result[Telemetry::KEY_HOST][Telemetry::KEY_NAME]
        );
        $this->assertEquals(
            $hostIp,
            $result[Telemetry::KEY_HOST][Telemetry::KEY_IP]
        );
    }

    #[Test]
    public function it_returns_payload_with_request_data()
    {
        $this->obj->withRequestData();

        $result = $this->callMethod($this->obj, 'getPayload');

        $this->assertArrayHasKey(Telemetry::KEY_REQUEST, $result);
        $this->assertEquals(
            'http://localhost',
            $result[Telemetry::KEY_REQUEST]['uri']
        );
        $this->assertEquals(
            'GET',
            $result[Telemetry::KEY_REQUEST]['method']
        );
        $this->assertEmpty($result[Telemetry::KEY_REQUEST]['data']);
        $this->assertEmpty($result[Telemetry::KEY_REQUEST]['headers']);
    }

    #[Test]
    public function it_returns_payload_with_supplied_request_data()
    {
        $requestData = ['post' => 'data'];
        $this->obj->withRequestData($requestData);

        $result = $this->callMethod($this->obj, 'getPayload');

        $this->assertArrayHasKey(Telemetry::KEY_REQUEST, $result);
        $this->assertEquals(
            $requestData,
            $result[Telemetry::KEY_REQUEST]
        );
    }

    #[Test]
    public function it_returns_payload_with_supplied_response_data()
    {
        $content = ['timing' => 'fast'];
        $status = 204;
        $headerKey = 'x-header';
        $headerValue = 'value';
        $headers = [$headerKey => $headerValue];

        $responseData = new Response($content, $status, $headers);
        $this->obj->withResponseData($responseData);

        $result = $this->callMethod($this->obj, 'getPayload');

        $this->assertArrayHasKey(Telemetry::KEY_RESPONSE, $result);
        $this->assertEquals(
            json_encode($content),
            $result[Telemetry::KEY_RESPONSE]['content']
        );
        $this->assertEquals($status, $result[Telemetry::KEY_RESPONSE]['status']);
        $this->assertEquals(
            $headerValue,
            $result[Telemetry::KEY_RESPONSE]['headers'][$headerKey][0]
        );
    }

    #[Test]
    public function it_returns_payload_with_user_data()
    {
        $user = new AuthUser;
        Auth::login($user);

        $attributes = ['email', 'id', 'name'];

        Config::set('telemetry.payloads.user.attributes', $attributes);

        $this->obj->withUserData();

        $result = $this->callMethod($this->obj, 'getPayload');

        $this->assertArrayHasKey(Telemetry::KEY_USER, $result);

        foreach ($attributes as $attribute) {
            $this->assertEquals(
                $user->$attribute,
                $result[Telemetry::KEY_USER][$attribute]
            );
        }
    }

    #[Test]
    public function it_returns_payload_with_thread()
    {
        $threadData = 'the-thread-id';
        app()->bind(Telemetry::KEY_THREAD_IOC, fn () => $threadData);

        $result = $this->callMethod($this->obj, 'getPayload');

        $this->assertArrayHasKey(Telemetry::KEY_THREAD_ID, $result);
        $this->assertEquals($threadData, $result[Telemetry::KEY_THREAD_ID]);
    }

    #[Test]
    public function it_returns_payload_with_data()
    {
        $dataKey = 'something';
        $dataValue = 'here';
        $arrayableKey = 'arrayableKey';
        $arrayableValue = 'arrayableValue';
        $arrayable = new ArrayableData($arrayableKey, $arrayableValue);
        $dataData = [
            $dataKey => $dataValue,
            'arrayable' => $arrayable,
        ];
        $this->obj->data($dataData);

        $result = $this->callMethod($this->obj, 'getPayload');

        $this->assertArrayHasKey(Telemetry::KEY_DATA, $result);
        $this->assertArrayHasKey(
            $dataKey,
            $result[Telemetry::KEY_DATA]
        );
        $this->assertEquals(
            $dataValue,
            $result[Telemetry::KEY_DATA][$dataKey]
        );
        $this->assertArrayHasKey(
            'arrayable',
            $result[Telemetry::KEY_DATA]
        );
        $this->assertEquals(
            $arrayable->toArray(),
            $result[Telemetry::KEY_DATA]['arrayable']
        );
    }

    #[Test]
    public function it_returns_payload_with_obfuscation()
    {
        $obfuscatedKey = 'something';
        $obfuscatedValue = 'here';
        $obfuscatedData = [$obfuscatedKey => $obfuscatedValue];

        Config::set('telemetry.payloads.obfuscated_data_keys', [$obfuscatedKey]);

        $this->obj->data($obfuscatedData);

        $result = $this->callMethod($this->obj, 'getPayload');

        $this->assertArrayHasKey(Telemetry::KEY_DATA, $result);
        $this->assertArrayHasKey(
            $obfuscatedKey,
            $result[Telemetry::KEY_DATA]
        );
        $this->assertEquals(
            str_repeat('*', strlen($obfuscatedValue)),
            $result[Telemetry::KEY_DATA][$obfuscatedKey]
        );
    }

    #[Test]
    public function it_returns_payload_with_index()
    {
        $indexData = 'the-index';
        Config::set('telemetry.index', $indexData);

        $result = $this->callMethod($this->obj, 'getPayload');

        $this->assertArrayHasKey(Telemetry::KEY_METADATA, $result);
        $this->assertEquals(
            $indexData,
            $result[Telemetry::KEY_METADATA]['index']
        );
    }
}
