<?php

namespace ObvioBySage\Telemetry\Transports;

use Illuminate\Support\Facades\Redis;
use ObvioBySage\Telemetry\Exceptions\RedisConnectionException;
use ObvioBySage\Telemetry\Contracts\TelemetryTransport;

class RedisTransport implements TelemetryTransport
{
    public function __construct(
        protected null|string $connection = null,
        protected null|string $queue = null,
    ) {}

    /**
     * @inheritDoc
     */
    public function validateConnection(): void
    {
        if (empty($this->connection) === true) {
            throw new RedisConnectionException(
                'Invalid redis connection',
            );
        }

        if (empty($this->queue) === true) {
            throw new RedisConnectionException(
                'Invalid redis queue',
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function publish(array $payload): mixed
    {
        return Redis::connection($this->connection)
            ->rpush(
                $this->queue,
                json_encode($payload)
            );
    }
}
