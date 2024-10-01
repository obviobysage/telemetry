<?php

namespace ObvioBySage\Telemetry;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ObvioBySage\Telemetry\Contracts\TelemetryData;
use ObvioBySage\Telemetry\Contracts\TelemetryIndexResolver;
use ObvioBySage\Telemetry\Transports\Transport;
use Symfony\Component\HttpFoundation\Response;

class Telemetry
{
    const KEY_DATA = 'data';
    const KEY_ENV = 'env';
    const KEY_EVENT = 'event';
    const KEY_EVENT_TS = 'event_ts';
    const KEY_HOST = 'host';
    const KEY_IP = 'ip';
    const KEY_METADATA = '@metadata';
    const KEY_NAME = 'name';
    const KEY_REQUEST = 'request';
    const KEY_RESPONSE = 'response';
    const KEY_THREAD_ID = 'thread_id';
    const KEY_THREAD_IOC = 'TelemetryThreadId';
    const KEY_USER = 'user';

    /**
     * The identifier of the event, to be able to aggregate and report based on
     * event type.
     *
     * @var string
     */
    protected $event = null;

    /**
     * The "first-class" data that goes into the Telemetric.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Whether or not to include request input in Telemetry payload.
     *
     * @var bool|array|Request
     */
    protected $requestData = null;

    /**
     * Any response data that should be included in the Telemetric payload.
     *
     * @var array
     */
    protected $responseData = [];

    /**
     * Flag to indicate whether including the authenticated user in the payload.
     *
     * @var bool
     */
    protected $withUser = null;

    /**
     * By default the input of a request is not included in the payload indexed
     * in Telemetry. This method allows the indication that we DO want the input
     * data included.
     *
     * @param  bool  $withRequestData
     * @return Telemetry
     */
    public function withRequestData(array|bool|Request $withRequestData = true)
    {
        $this->requestData = $withRequestData;

        return $this;
    }

    /**
     * Data that should be included in the response attribute of the document.
     * This is primarily used in a "request identification" event. But not only
     * limited to...
     *
     * @param  Symfony\Component\HttpFoundation\Response  $respone
     * @return Telemetry
     */
    public function withResponseData(Response $response = null)
    {
        $this->responseData = [
            'status'  => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'content' => $response->getContent(),
        ];

        return $this;
    }

    /**
     * Toggles the flag to indicate if the payload should include the currently
     * authenticated User.
     *
     * @param  bool  $flag
     * @return Telemetry
     */
    public function withUserData($user = null): self
    {
        if ($user === false) {
            $this->withUser = false;

            return $this;
        }

        $this->withUser = $user;

        return $this;
    }

    /**
     * The event name for this Telemetry, this is what identifies the type of
     * information being indexed.
     *
     * @param  string  $eventName
     * @return Telemetry
     */
    public function event(string $eventName = null)
    {
        $this->event = $eventName;

        return $this;
    }

    /**
     * When given some first-class data to put into the Telemetry payload, ensure
     * it can be used, then massage it into the payload.
     *
     * @param  array|Arrayable  $data
     * @return Telemetry
     *
     * @throws Exception
     */
    public function data(array|Arrayable $data = null)
    {
        // If the $data is Arrayable, we'll toArray() it.
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        if (is_array($data) === false) {
            throw new Exception(
                'Telemetry data must be an array or implement Arrayable'
            );
        }

        $this->data = $data;

        return $this;
    }

    /**
     * The application may have some logic resolving the index to put the payload
     * into.
     *
     * @param  string $eventName
     * @param  array $payload
     * @return string
     */
    protected function getIndex(string $eventName, array $payload = []): string
    {
        $resolver = null;
        $isValidResolver = false;

        // Try to get a resolver out of the container, in case the application
        // has defined one for some localized logic.
        try {
            $resolver = app(TelemetryIndexResolver::class);
        } catch (BindingResolutionException $e) {
            // We're not trying to do anything if there is no binding found in
            // the container. We handle this situation below with some config
            // lookup/defaulting.
        }

        // But is it ACTUALLY a resolver?
        $isValidResolver = $resolver instanceof TelemetryIndexResolver;

        // If there is a resolver - and it's valid - use it to get the index. If
        // not, get it out of config (which has the option to override with any
        // environment config).
        return empty($resolver) === false && $isValidResolver === true ?
            $resolver->getIndex(eventName: $eventName, payload: $payload) :
            config('telemetry.index');
    }

    /**
     * Massages the payload, based on what data is available.
     *
     * @return array
     */
    protected function getPayload(): array
    {
        // Most importantly, we want the event type in the payload, more times
        // than not, this is what is aggregated on.
        $payload = [
            self::KEY_ENV      => config('app.env'),
            self::KEY_EVENT    => $this->event,
            self::KEY_EVENT_TS => now()->timestamp,
            self::KEY_HOST     => [
                self::KEY_NAME => gethostname(),
                self::KEY_IP   => gethostbyname(gethostname()),
            ],
        ];

        // If the calling side has asked to include the request details into the
        // Telemetric being indexed, add it in.
        $this->getRequestData($payload);

        // If the calling side has asked to include the response details into the
        // Telemetric being indexed...
        if (empty($this->responseData) === false) {
            $payload[self::KEY_RESPONSE] = $this->responseData;
        }

        // If we have a user associated with this Telemetry, get the payload to
        // include in the indexed document.
        $this->getUser($payload);

        // Attempt to get a threadId out of the container, if it's present, we'll
        // include it in the payload to link documents together to a single
        // "thought".
        if (app()->bound(self::KEY_THREAD_IOC) === true) {
            $payload[self::KEY_THREAD_ID] = app(self::KEY_THREAD_IOC);
        }

        // If there are any first-class citizens of data for this Telemetric,
        // merge the payload we've already put together with those attributes
        // that have been supplied.
        if (empty($this->data) === false) {
            $payload[self::KEY_DATA] = $this->data;
        }

        // We need to run the built payload through getTelemetryData to expand
        // any items accordingly. This needs to be done before obfuscation.
        $payload = $this->getTelemetryData($payload);

        // We may need to obfuscate some of the data going into Telemetry.
        $payload = $this->getObfuscatedData($payload);

        // If the Telemetry backend requires an index (Elasticsearch, for instance),
        // now is the time to get the value for it.
        if (empty($index = $this->getIndex($this->event, $payload)) === false) {
            $payload[self::KEY_METADATA] = [
                'index' => $index,
            ];
        }

        return $payload;
    }

    protected function getRequestData(array &$payload): void
    {
        // If "global" configuration is telling us no and our "local" requestData
        // is empty, there isn't any request data to include in this Telemetry.
        if (empty(config('telemetry.payloads.request.included')) === true &&
            empty($this->requestData) === true
        ) {
            return;
        }

        // If the requestData is an array and not empty, we've been told what to
        // include as the data, so just return that.
        if (is_array($this->requestData) === true &&
            empty($this->requestData) === false
        ) {
            $payload[self::KEY_REQUEST] = $this->requestData;

            return;
        }

        // Otherwise, if we've been given an instance of a Request, we'll use
        // that. Lastly, utilize the request() helper.
        $request = $this->requestData instanceof Request ?
            $this->requestData :
            request();

        $requestHeaders = $request->headers->all();
        $loggingHeaders = config('telemetry.payloads.request.headers', []);
        $filteredHeaders = [];

        // Iterate the headers we WANT to log and attempt to get them from the
        // request so they can be added to the payload.
        foreach ($loggingHeaders as $header) {
            $header = trim($header);

            empty($requestHeaders[$header]) === false ?
                $filteredHeaders[$header] = implode(
                    ',',
                    ($requestHeaders[$header] ?? '')
                ) :
                null;
        }

        $data = [
            'uri'     => $request->fullUrl(),
            'method'  => $request->method(),
            'agent'   => $request->userAgent(),
            'ip'      => $request->ip(),
            'data'    => $request->all(),
            'headers' => $filteredHeaders,
        ];

        $payload[self::KEY_REQUEST] = $data;
    }

    /**
     * Gets the User data for the payload. Whether the User is being included or
     * resolving the User data from current, or supplied Auth model.
     *
     * @return array
     */
    protected function getUser(array &$payload): void
    {
        // If "global" configuration is telling us no and our "local" withUser
        // is empty, there isn't any user data to include in this Telemetry.
        if (empty(config('telemetry.payloads.user.included')) === true &&
            $this->withUser === false
        ) {
            return;
        }

        // Assuming that if $withUser is NOT null, we've been told which user
        // object to use. If it is null, we'll try to get it out of the Auth
        // facade.
        $user = is_null($this->withUser) === true ?
            Auth::user() :
            $this->withUser;

        // If we don't have a user after trying to resolve it, we're done here,
        // there's no data to represent.
        if (empty($user) === true) {
            return;
        }

        // If there is an array of user attirbutes to include in the payload,
        // interate those and try to get them from the resolved $user object.
        foreach (config('telemetry.payloads.user.attributes', []) as $attribute) {
            $attribute = trim($attribute);

            $data[$attribute] = $user->$attribute ?? null;
        }

        // We may have a callback on the user object that wants to be called to
        // add more context to the payload.
        $callback = config('telemetry.payloads.user.callback_method');

        // Get the data from the user object if we have a callback defined.
        $callbackData = empty($callback) === false &&
            is_callable([$user, $callback]) === true ?
                $user->$callback() :
                [];

        // When we find some data, merge that into what we've already (potentially)
        // put together.
        if (empty($callbackData) === false) {
            $data = array_merge(
                $data,
                $callbackData
            );
        }

        if (empty($data) === false) {
            $payload[self::KEY_USER] = $data;
        }
    }

    /**
     * Iterate recursively through the payload build by getPayload() to see if
     * any keys' value happens to implement the TelemetricData interface. This is
     * just a little "sugar" on formulating payloads.
     *
     * @param  array  $payload
     * @return array
     */
    protected function getTelemetryData(array $payload): array
    {
        // We're gonna iterate the $payload we've been given to see if there is
        // any TelemetricData objects to massage or arrays to recurse. If the
        // value is neither one of these, there are no alterations to make, so
        // there's nothing to do.
        foreach ($payload as $key => $data) {
            // Scalar/regular types like ints and strings can stay as is, so we
            // just keep on going.
            $isScalar = !is_object($data) && !is_array($data);
            if ($isScalar) {
                continue;
            }

            // If the current $data we're iterating is an array, hello recursion!
            if (is_array($data) === true) {
                $payload[$key] = $this->getTelemetryData($data);

                continue;
            }

            // If the current $data is an instance of TelemetryData, theoretically
            // there is a method for us to call. Otherwise, nothing needs to be
            // done so we'll reset it to what it already is.
            if ($data instanceof TelemetryData) {
                $payload[$key] = $this->getTelemetryData($data->telemetryData());

                continue;
            }

            // If we've got an object that implements Arrayable, recurse into it
            // using it's implemented toArray().
            if ($data instanceof Arrayable) {
                $payload[$key] = $this->getTelemetryData($data->toArray());

                continue;
            }

            // For Carbon instances, we only want the datetime string.
            if ($data instanceof Carbon) {
                $payload[$key] = $data->toISOString();

                continue;
            }

            // If we've got an UploadedFile, we only want to record the filename.
            if ($data instanceof UploadedFile) {
                $payload[$key] = $data->getClientOriginalName();

                continue;
            }

            // If we made it down here and the $data happens to be an object, we've
            // entered a scenario that is a problem. We can't process the object's
            // data, so Exception outta here.
            throw new Exception(
                'TelemetryData Exception: ' . get_class($data) .
                ' is an object that is not handled explicitly, does not implement' .
                ' Arrayable or TelemetryData'
            );
        }

        return $payload;
    }

    /**
     * Obfuscates any occurances of key names from configuration, inside the
     * payload that has been built. Recursive, looking for any key names that
     * match.
     *
     * @param  array $payload
     * @return array
     */
    protected function getObfuscatedData(array $payload): array
    {
        $obfuscations = config('telemetry.payloads.obfuscated_data_keys', []);

        foreach ($payload as $key => $data) {
            if (is_array($data) === true) {
                $payload[$key] = $this->getObfuscatedData($data);

                continue;
            }

            if (in_array($key, $obfuscations) === true) {
                $payload[$key] = str_repeat('*', strlen($payload[$key]));
            }
        }

        return $payload;
    }

    /**
     * Checks for validity of the Telemetry and fires it off to be indexed.
     *
     * @return void|bool
     */
    public function fire(string $connection = null)
    {
        // If we don't have Telemetry enabled, we can't fire off this metric to
        // anywhere, so we're done.
        if (config('telemetry.enabled') !== true) {
            return false;
        }

        if (empty($this->event) === true) {
            return $this->notifyException(
                new Exception('Telemetry requires an event name to index')
            );
        }

        try {
            // We may have been given a transport connection to publish this
            // particular payload through. If not, well use the default specified
            // in the Telemetry configuration.
            $transportConnection = $connection ??
                config('telemetry.default_transport');

            return $this->getTransportByConnection($transportConnection)
                ->publish($this->getPayload());
        } catch (Exception $e) {
            // If there is any exception thrown from trying to publish into
            // Telemetry, we'll bark about it, but we may not want to cause a
            // problem for the appication.
            $this->notifyException($e);
        }
    }

    /**
     * Removing smell for testing...
     *
     * @param  string|null $transportConnection
     * @return Transport
     */
    protected function getTransportByConnection(string|null $transportConnection)
    {
        return new Transport($transportConnection);
    }

    /**
     * Sends the exception through the logging channel. Re-throws the exception
     * if configuration allows it.
     *
     * @param  Exception $e
     * @throws Exception
     * @return void
     */
    protected function notifyException(Exception $e)
    {
        // Log into whichever channel is specified for Telemetry. Uses the
        // application's default channel if not overridden.
        Log::channel(config('telemetry.notifications.logging_channel'))
            ->warning('Telemetry Error: ' . $e->getMessage());

        // If Telemetry config specifies to throw exceptions, throw the exception.
        if (config('telemetry.notifications.throw_transport_exceptions') === true) {
            throw $e;
        }
    }
}
