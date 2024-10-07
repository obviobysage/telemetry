<?php

namespace ObvioBySage\Telemetry\Tests;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Stringable;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Tests\Support\ArrayableData;
use ObvioBySage\Telemetry\Tests\Support\AuthUser;
use ObvioBySage\Telemetry\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GetTelemetryDataTest extends TestCase
{
    #[Test]
    public function it_correctly_expands_payload()
    {
        $intData = 5;
        $stringData = 'data';
        $arrayable = new ArrayableData('arrayableKey', 'arrayableValue');
        $now = Carbon::now();
        $uploadedData = UploadedFile::fake()->create('uploadedFile.txt');
        $user = new AuthUser;
        $stringableData = new Stringable('derp');

        $payload = [
            'intValue' => $intData,
            'some'     => $stringData,
            'string'   => $stringableData,
            'lower'    => [
                'arrayable' => $arrayable,
            ],
            'datetime' => $now,
            'uploaded' => $uploadedData,
            'authUser' => $user,
        ];

        $obj = new Telemetry;

        $result = $this->callMethod($obj, 'getTelemetryData', [$payload]);

        $this->assertEquals(
            ['intValue', 'some', 'string', 'lower', 'datetime', 'uploaded', 'authUser'],
            array_keys($result)
        );
        $this->assertEquals($intData, $result['intValue']);
        $this->assertEquals($stringableData->toString, $result['string']);
        $this->assertEquals($arrayable->toArray(), $result['lower']['arrayable']);
        $this->assertEquals($now->toISOString(), $result['datetime']);
        $this->assertEquals(
            $uploadedData->getClientOriginalName(),
            $result['uploaded']
        );
        $this->assertEquals($user->telemetryData(), $result['authUser']);
    }
}
