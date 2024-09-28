<?php

namespace ObvioBySage\Telemetry\Tests\Support;

use Illuminate\Foundation\Auth\User as Authenticatable;
use ObvioBySage\Telemetry\Contracts\TelemetryData;

class AuthUser extends Authenticatable implements TelemetryData
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    public function __construct()
    {
        $this->id = 4;
        $this->email = 'someone@somewhere.com';
        $this->name = 'Todd Blow';
    }

    public function callbackData()
    {
        $this->extraKey = 'extra';
        $this->extraValue = 'data';

        return [$this->extraKey => $this->extraValue];
    }

    public function telemetryData(): array
    {
        return [
            'theId'    => $this->id,
            'theEmail' => $this->email,
            'theName'  => $this->name,
        ];
    }
}
