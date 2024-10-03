<?php

namespace ObvioBySage\Telemetry\Contracts;

interface TelemetryVars
{
    /**
     * Shapes the data for a Telemetry variables on the implementing object.
     *
     * @return array
     */
    public function getVars(): array;
}
