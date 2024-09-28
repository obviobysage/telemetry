<?php

namespace ObvioBySage\Telemetry\Transports;

use ObvioBySage\Telemetry\Contracts\TelemetryTransport;
use ObvioBySage\Telemetry\Exceptions\TransportException;
use ObvioBySage\Telemetry\Transports\RedisTransport;

class Transport
{
    const DRIVER_REDIS = 'redis';

    /**
     * @var TelemetryTransport
     */
    protected $transport = null;

    public function __construct(string|null $transportConnection)
    {
        $this->resolveTransport($transportConnection);
    }

    /**
     * Resolves the transport to move a payload into storage. Configuration
     * settings drive the result here.
     *
     * @param  string $transportConnection
     * @return void
     */
    protected function resolveTransport(string|null $transportConnection)
    {
        $connectionDetails = config('telemetry.connections.' . $transportConnection);

        $driver = $connectionDetails['driver'] ?? null;
        $transport = $connectionDetails['transport'] ?? null;

        // Unset these attributes so they don't get splat'ed.
        unset($connectionDetails['driver']);
        unset($connectionDetails['transport']);

        switch ($driver) {
            case self::DRIVER_REDIS:
                $this->transport = app()->makeWith(
                    RedisTransport::class,
                    [...$connectionDetails]
                );

                break;
            default:
                // We don't natively know about this driver... Try to create the
                // object we've been told about in the configuration. If we don't
                // have a $transport value from configuration, we'll end up with
                // an exception below.
                if (empty($transport) === false) {
                    $this->transport = new $transport(...$connectionDetails);
                }

                break;
        }

        // Whatever we've resolved, needs to be a TelemetryTransport, because
        // reasons...
        if (! ($this->transport instanceof TelemetryTransport)) {
            throw new TransportException(
                'Telemtry driver "' . $driver . '" is not a valid TelemetryTransport',
            );
        }

        $this->transport->validateConnection();
    }

    /**
     * Publishes a payload into storage, usually a queeu of sorts, for the
     * Telemetry index to consume from.
     *
     * @param  array $payload
     * @return mixed
     */
    public function publish(array $payload = []): mixed
    {
        if (empty($payload) === true) {
            return null;
        }

        return $this->transport->publish($payload);
    }
}
