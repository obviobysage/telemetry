<?php

namespace ObvioBySage\Telemetry\Tests\Support;

use ObvioBySage\Telemetry\Contracts\TelemetryVars;

class TelemetryVarsData implements TelemetryVars
{
    public function __construct(
        protected string $key = 'varsDataKey',
        protected string $value = 'varsDataValue',
    ) {}

    public function getVars(): array
    {
        return [$this->key => $this->value];
    }
}
