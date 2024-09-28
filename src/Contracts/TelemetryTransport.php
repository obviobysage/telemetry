<?php

namespace ObvioBySage\Telemetry\Contracts;

interface TelemetryTransport
{
    /**
     * Publishes a payload into storage, usually a queeu of sorts, for the
     * Telemetry index to consume from.
     *
     * @param  array $payload
     * @return mixed
     */
    public function publish(array $payload): mixed;

    /**
     * Validates the connection details provided to the transport.
     *
     * @return void
     */
    public function validateConnection(): void;
}
