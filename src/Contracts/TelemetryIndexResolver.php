<?php

namespace ObvioBySage\Telemetry\Contracts;

interface TelemetryIndexResolver
{
    /**
     * Accepts a Telemetry event name, along with the payload data, to allow any
     * application logic to determine the index name of where to index the
     * Telemetry data.
     *
     * @return array
     */
    public function getIndex(string $eventName, array $payload = []): string;
}
