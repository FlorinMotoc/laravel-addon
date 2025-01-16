<?php

namespace FlorinMotoc\LaravelAddon\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait CronThrottleTrait
{
    public function shouldThrottleCron(): bool
    {
        if (
            !method_exists($this, 'hasOption')
            || !method_exists($this, 'option')
            || !method_exists($this, 'arguments')
            || !method_exists($this, 'options')
        ) {
            return false;
        }

        if (!$this->hasOption('cron')) {
            return false;
        }

        if ($this->option('cron')) {
            $key = "_cron:" . static::class . '_' . md5(serialize($this->arguments()) . serialize($this->options()));
            if (Cache::has($key)) {
                Log::info("Already checked this in the last minute. Please wait 1 minute and try again. Key: $key");

                return true;
            }

            Cache::put($key, 1, now()->addSeconds(50));
        }

        return false;
    }
}
