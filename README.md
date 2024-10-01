# Obv.io - Telemetry

[![Latest Version on Packagist](https://img.shields.io/packagist/v/obviobysage/telemetry.svg?style=flat-square)](https://packagist.org/packages/obviobysage/telemetry)[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/obviobysage/telemetry/ci.yml?branch=master)](https://github.com/obviobysage/telemetry/actions/workflows/ci.yml?query=branch:master)


This Laravel package is a common facility to ship Telemetry payloads from internal Obv.io applications to an Elasticsearch storage, using a Redis transport by default. Custom transports can be configured, if Redis is not the choice.

Using various configuration options, honing in the usability per application is straightforward.

## Enabling Telemetry
Telemetry is in the **disabled** state by default when you require the package, but a simple switch in your application `.env` is all that's needed to start shipping Telemetry payloads:

```bash
TELEMETRY_ENABLED=true
```

Then add some events in whatever way makes sense:

```php
use ObvioBySage\Telemetry\Facades\Telemetry;

protected someFunction()
{
    // ...Things hapen...

    Telemetry::event('event-name-keyword')
        ->data(['array' => 'of payload...'])
        ->fire();
}
```

With no configuration out of the box, Telemetry will use your `default` Redis connection found in `config/database.php` and will publish to a list named `telemetry`. It's up to your ELK stack to consume from this list for the last mile.

Changing these two settings is as simple as providing a couple more `.env` directives:

```bash
TELEMETRY_REDIS_CONNECTION="your-connection-name"
TELEMETRY_REDIS_QUEUE="not_telemetry"
```

This Telemetry package is configured to use Redis as a transport by default, but you are free to configure other transport methods if it makes more sense for your application. Any custom transport must implement the `ObvioBySage\Telemetry\Contracts\TelemetryTransport` contract. After that, it's entirely up to you.

The assumed storage being shipped to is Elasticsearch, and comes configured with the index setting of `telemetry`. This can be overriden using the `TELEMETRY_INDEX` environment variable.

For a view into everything that is configurable, it's likely easier to publish the configuration from the package and peruse the settings:

```bash
artisan vendor:publish --tag obvio-telemetry
```

This will place a configuration file in `config/telemetry.php`, and from here it's in your hands.

## Request Payload
Laravel's request is automatically included in the payload being shipped to the Telemetry storage. This can be toggled using an environment variable.

Generic headers from the request are included in the payload, but can be configured in your environment(s). The value should be a comma-separated list of headers to try to include from the request object. If a listed header is not found in the request, it's simply not included in the payload to Telemetry.

```bash
TELEMETRY_PAYLOADS_REQUEST_INCLUDED=false
TELEMETRY_PAYLOADS_REQUEST_HEADERS="accept,authorization,content-length,content-type,origin"
```

## User Payload
The user making the request is automatically included in the payload being shipped to the Telemetry storage. This can be toggled using the an environment variable. When preparing the payload to be shipped, if no user is present in the request, the top-level attribute is not included in the payload.

Generic attributes from the user are included in the payload, but can be configured in your environment(s). The value should be a comma-separated list of attributes from your user model. If a listed attribute is not found on the user model, a `null` value is inclued in the payload to Telemetry.

A callback method can be supplied in the environment variables, and if present and callable on the user model, will be called when preparing the payload. This method should return an array of data, which will be merged with whatever attributes were able to be taken from the user model previously.

```bash
TELEMETRY_PAYLOADS_USER_INCLUDED=false
TELEMETRY_PAYLOADS_USER_ATTRIBUTES="email,id,name"
TELEMETRY_PAYLOADS_USER_CALLBACK_METHOD="myUserMehod"
```

## Obfuscated Data
There may be some payload attributes that you would like to have obfuscated before they are shipped to Telemetry for storage. By creating a list in the configuration, these items will be iterated and searched for within the resolved payload. If any of those keys are found, recursively..., the values will be obfuscated. The attributes remain in the payload, but they're value is replaced with `****`, the length of the value.

In the same fashion as the request headers, or the user attributes, a comma-separated list can be provided to the environment.

```bash
TELEMETRY_OBFUSCATED_DATA_KEYS="password,password_confirmation"
```

## Notifications
When exceptions are caught, the exception message will be logged to whatever channel is configured in your application's `config('logging.default')`, but this can of course be overriden in your environment(s).

Exceptions are not re-thrown by default, it's of the opinion that the application flow shouldn't be interrupted because of a logging/connection issue. If this is coutnerintuitive for you, toggle the setting in your environment and the exception will be logged and then re-thrown.

```bash
TELEMETRY_LOGGING_CHANNEL="stack"
TELEMETRY_THROW_TRANSPORT_EXCEPTIONS=true
```

## Configuration vs. `.env`
The results are the same in either decision, it's entirely up to you and your application. The exception to that statement is if you require custom transports to ship your Telemetry payloads. There isn't an `.env` solution for this scenario.

## `TelemetryData`
Any included object in the resolved payload that implements the `ObvioBySage\Telemetry\Contracts\TelemetryData` contract will provide a shape for the payload to build.

Perhaps you have a model that has many attributes that you don't want/need to be sent to Telemetry, within the implemented `telemetryData()` method, you return an array in whatever shape you would like to represent that model.

When creating the payload, Telemetry will recursively iterate all attributes and check for:
- `TelemetryData`
- `Arrayable`
- `Carbon`
- `UploadedFile`

`TelemetryData` and `Arrayable` are self-explanatory. `Carbon` will represent the object as `toISOString()`. The `UploadedFile` will simply get the `getClientOriginalName()`.
