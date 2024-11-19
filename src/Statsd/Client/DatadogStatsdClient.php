<?php

namespace FlorinMotoc\LaravelAddon\Statsd\Client;

use DataDog\DogStatsd;

class DatadogStatsdClient implements StatsdClientInterface
{
    /**
     * holds all the timings that have not yet been completed
     *
     * @var array
     */
    protected $timings = [];

    /** @var DogStatsd */
    protected $client;

    public function __construct(DogStatsd $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, array $tags = [])
    {
        $this->client->increment($key, 1, $tags, 1);
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, array $tags = [])
    {
        $this->client->decrement($key, 1, $tags, 1);
    }

    /**
     * @inheritDoc
     */
    public function count(string $key, int $value, array $tags = [])
    {
        $this->client->send(array($key => "$value|c"), 1, $tags);
    }

    /**
     * @inheritDoc
     */
    public function timing(string $key, float $value, array $tags = [])
    {
        $this->client->timing($key, $value, 1, $tags);
    }

    /**
     * @inheritDoc
     */
    public function microtiming(string $key, float $value, array $tags = [])
    {
        $this->timing($key, $value * 1000, $tags);
    }

    /**
     * @inheritDoc
     */
    public function startTiming(string $key)
    {
        $this->timings[$key] = gettimeofday(true);
    }

    /**
     * @inheritDoc
     */
    public function endTiming(string $key, array $tags = [])
    {
        $end = gettimeofday(true);

        if (isset($this->timings[$key])) {
            $timing = $end - $this->timings[$key];
            $this->microtiming($key, $timing, $tags);
            unset($this->timings[$key]);

            return $timing;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function histogram(string $key, float $value, array $tags = [])
    {
        $this->client->histogram($key, $value, 1, $tags);
    }
}
