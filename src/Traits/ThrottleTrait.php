<?php

namespace FlorinMotoc\LaravelAddon\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait ThrottleTrait
{
    public function shouldThrottle(array $params = []): bool
    {
        $key = "_throttle:" . static::class . '_' . md5(serialize($params));
        if (Cache::has($key)) {
            Log::info("Already checked this in the last minute. Please wait 1 minute and try again. Key: $key");

            return true;
        }

        Cache::put($key, 1, now()->addSeconds(50));

        return false;
    }
}
