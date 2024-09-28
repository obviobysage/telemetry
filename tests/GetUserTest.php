<?php

namespace ObvioBySage\Telemetry\Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\Support\AuthUser;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GetUserTest extends TestCase
{
    /**
     * @var Telemetry
     */
    protected $obj;

    /**
     * @var AuthUser
     */
    protected $user;

    /**
     * @var array
     */
    protected $payload = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->obj = new Telemetry;

        Config::set('telemetry.payloads.user.included', true);
        Config::set('telemetry.payloads.user.attributes', ['id', 'email', 'name']);

        $this->user = new AuthUser;
        Auth::login($this->user);
    }

    #[Test]
    public function it_sets_nothing_when_config_and_data_are_empty()
    {
        Config::set('telemetry.payloads.user.included', false);
        $this->setProperty($this->obj, 'withUser', false);

        $this->callMethod($this->obj, 'getUser', [&$this->payload]);

        $this->assertArrayNotHasKey(Telemetry::KEY_USER, $this->payload);
    }

    #[Test]
    public function it_sets_something_when_config_includes()
    {
        $this->setProperty($this->obj, 'withUser', null);

        $this->callMethod($this->obj, 'getUser', [&$this->payload]);

        $this->assertArrayHasKey(Telemetry::KEY_USER, $this->payload);
    }

    #[Test]
    public function it_sets_something_when_attribute_is_set()
    {
        Config::set('telemetry.payloads.user.included', false);
        $this->setProperty($this->obj, 'withUser', null);

        $this->callMethod($this->obj, 'getUser', [&$this->payload]);

        $this->assertArrayHasKey(Telemetry::KEY_USER, $this->payload);
    }

    #[Test]
    public function it_sets_nothing_when_no_user()
    {
        Auth::logout();

        $this->callMethod($this->obj, 'getUser', [&$this->payload]);

        $this->assertArrayNotHasKey(Telemetry::KEY_USER, $this->payload);
    }

    #[Test]
    public function it_sets_nothing_when_user_attributse_are_empty()
    {
        Config::set('telemetry.payloads.user.attributes', []);

        $this->callMethod($this->obj, 'getUser', [&$this->payload]);

        $this->assertArrayNotHasKey(Telemetry::KEY_USER, $this->payload);
    }

    #[Test]
    public function it_sets_null_values_for_unknown_user_attributes()
    {
        Config::set(
            'telemetry.payloads.user.attributes',
            ['id', 'email', 'name', 'notReal'],
        );

        $this->callMethod($this->obj, 'getUser', [&$this->payload]);

        $this->assertArrayHasKey(Telemetry::KEY_USER, $this->payload);
        $this->assertEquals(
            $this->user->email,
            $this->payload[Telemetry::KEY_USER]['email']
        );
        $this->assertEquals(
            $this->user->id,
            $this->payload[Telemetry::KEY_USER]['id']
        );
        $this->assertEquals(
            $this->user->name,
            $this->payload[Telemetry::KEY_USER]['name']
        );
        $this->assertNull($this->payload[Telemetry::KEY_USER]['notReal']);
    }

    #[Test]
    public function it_merges_user_callback_data()
    {
        Config::set('telemetry.payloads.user.callback_method', 'callbackData');

        $this->callMethod($this->obj, 'getUser', [&$this->payload]);

        $this->assertArrayHasKey(Telemetry::KEY_USER, $this->payload);
        $this->assertEquals(
            $this->user->email,
            $this->payload[Telemetry::KEY_USER]['email']
        );
        $this->assertEquals(
            $this->user->id,
            $this->payload[Telemetry::KEY_USER]['id']
        );
        $this->assertEquals(
            $this->user->name,
            $this->payload[Telemetry::KEY_USER]['name']
        );
        $this->assertEquals(
            $this->user->extraValue,
            $this->payload[Telemetry::KEY_USER][$this->user->extraKey]
        );
    }
}
