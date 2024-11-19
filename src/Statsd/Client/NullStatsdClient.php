<?php

namespace FlorinMotoc\LaravelAddon\Statsd\Client;

class NullStatsdClient implements StatsdClientInterface
{
    /**
     * @inheritDoc
     */
    public function increment(string $key, array $tags = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, array $tags = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function count(string $key, int $value, array $tags = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function timing(string $key, float $value, array $tags = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function microtiming(string $key, float $value, array $tags = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function startTiming(string $key)
    {
    }

    /**
     * @inheritDoc
     */
    public function endTiming(string $key, array $tags = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function histogram(string $key, float $value, array $tags = [])
    {
    }
}
