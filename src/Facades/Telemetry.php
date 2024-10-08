<?php

namespace ObvioBySage\Telemetry\Facades;

use Illuminate\Support\Facades\Facade;

class Telemetry extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'telemetry';
    }
}
