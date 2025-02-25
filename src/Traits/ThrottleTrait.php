<?php

namespace FlorinMotoc\LaravelAddon\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait ThrottleTrait
{
    public function shouldThrottle(array $params = [], int $seconds = 50): bool
    {
        $key = "_throttle:" . static::class . '_' . md5(serialize($params));
        if (Cache::has($key)) {
            Log::info("Already checked this in the last $seconds seconds. Please wait $seconds seconds and try again. Key: $key");

            return true;
        }

        Cache::put($key, 1, now()->addSeconds($seconds));

        return false;
    }
}
