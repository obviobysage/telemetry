<?php

namespace ObvioBySage\Telemetry\Tests\Support;

use ObvioBySage\Telemetry\Contracts\TelemetryIndexResolver;

class IndexResolver implements TelemetryIndexResolver
{
    public function getIndex(string $eventName, array $payload = []): string
    {
        return $eventName . '::' . implode(',', $payload);
    }
}
