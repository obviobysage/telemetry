<?php

namespace ObvioBySage\Telemetry\Tests\Support;

use Illuminate\Contracts\Support\Arrayable;

class ArrayableData implements Arrayable
{
    public function __construct(
        protected string $key,
        protected string $value
    ) {}

    public function toArray()
    {
        return [$this->key => $this->value];
    }
}
