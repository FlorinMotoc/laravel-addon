<?php

namespace FlorinMotoc\LaravelAddon\Statsd\Client;

class ArrayStatsdClient implements StatsdClientInterface
{
    public array $data;

    /**
     * holds all the timings that have not yet been completed
     *
     * @var array
     */
    protected $timings = [];

    /**
     * @inheritDoc
     */
    public function increment(string $key, array $tags = [])
    {
        $this->data['increment'][] = ['key' => $key, 'tags' => $tags];
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, array $tags = [])
    {
        $this->data['decrement'][] = ['key' => $key, 'tags' => $tags];
    }

    /**
     * @inheritDoc
     */
    public function count(string $key, int $value, array $tags = [])
    {
        $this->data['count'][] = ['key' => $key, 'value' => $value, 'tags' => $tags];
    }

    /**
     * @inheritDoc
     */
    public function timing(string $key, float $value, array $tags = [])
    {
        $this->data['timing'][] = ['key' => $key, 'value' => $value, 'tags' => $tags];
    }

    /**
     * @inheritDoc
     */
    public function microtiming(string $key, float $value, array $tags = [])
    {
        $this->data['microtiming'][] = ['key' => $key, 'value' => $value, 'tags' => $tags];
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
        $this->data['histogram'][] = ['key' => $key, 'value' => $value, 'tags' => $tags];
    }
}
