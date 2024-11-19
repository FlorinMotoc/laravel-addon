<?php

namespace FlorinMotoc\LaravelAddon\Statsd\Client;

interface StatsdClientInterface
{
    /**
     * Increment the given key
     *
     * @param string $key
     * @param array $tags Key Value array of Tag => Value, or single tag as string
     */
    public function increment(string $key, array $tags = []);

    /**
     * Decrement the given key
     *
     * @param string $key
     * @param array $tags Key Value array of Tag => Value, or single tag as string
     */
    public function decrement(string $key, array $tags = []);

    /**
     * Send a count
     *
     * @param string $key
     * @param int $value
     * @param array $tags Key Value array of Tag => Value, or single tag as string
     */
    public function count(string $key, int $value, array $tags = []);

    /**
     * Send a timing (in ms).
     *
     * @param string $key
     * @param float $value the timing in ms
     * @param array $tags Key Value array of Tag => Value, or single tag as string
     */
    public function timing(string $key, float $value, array $tags = []);

    /**
     * A convenient alias for the timing function when used with micro-timing (ex microtime(true))
     *
     * @param string $key
     * @param float $value the timing in seconds
     * @param array $tags Key Value array of Tag => Value, or single tag as string
     */
    public function microtiming(string $key, float $value, array $tags = []);

    /**
     * starts the timing for a key
     *
     * @param string $key
     */
    public function startTiming(string $key);

    /**
     * ends the timing for a key and sends it to statsd
     *
     * @param string $key
     * @param array $tags
     *
     * @return float|null
     */
    public function endTiming(string $key, array $tags = []);

    /**
     * Histogram
     *
     * @param string $key
     * @param float $value The value
     * @param array $tags Key Value array of Tag => Value, or single tag as string
     */
    public function histogram(string $key, float $value, array $tags = []);
}
