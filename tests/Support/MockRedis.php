<?php

namespace ObvioBySage\Telemetry\Tests\Support;

class MockRedis
{
    public function rpush(string $queue, string $payload): mixed
    {
        return true;
    }
}
