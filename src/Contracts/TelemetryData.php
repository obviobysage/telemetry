<?php

namespace ObvioBySage\Telemetry\Contracts;

interface TelemetryData
{
    /**
     * Shapes the data for a Telemetry point on the implementing object.
     *
     * @return array
     */
    public function telemetryData(): array;
}
