<?php

namespace FlorinMotoc\LaravelAddon\Statsd\Client;

use DataDog\DogStatsd;

class CustomDatadogStatsdClient extends DatadogStatsdClient
{
    public function __construct()
    {
        $config = [
            'disable_telemetry' => true,
        ];

        if (!empty($this->getFromEnv('FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_HOST'))) {
            $config['host'] = $this->getFromEnv('FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_HOST');
        }

        if (!empty($this->getFromEnv('FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_PORT'))) {
            $config['port'] = $this->getFromEnv('FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_PORT');
        }

        if (!empty($this->getFromEnv('FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_LOCAL_HOSTNAME'))) {
            $config['global_tags'] = ['host' => $this->getFromEnv('FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_LOCAL_HOSTNAME')];
        } else {
            $config['global_tags'] = ['host' => gethostname()];
        }

        // custom tags
        $customTagsPrefix = 'FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_TAGS_CUSTOM_';
        foreach (array_filter($this->getAllEnv(), fn($key) => str_starts_with($key, $customTagsPrefix), ARRAY_FILTER_USE_KEY) as $key => $value) {
            $key = strtolower(str_replace($customTagsPrefix, '', $key));
            $config['global_tags']["c_$key"] = $value;
        }

        parent::__construct(new DogStatsd($config));
    }

    private function getFromEnv(string $key)
    {
        if (function_exists('env')) {
            return env($key);
        } else {
            return $_ENV[$key] ?? '';
        }
    }

    private function getAllEnv()
    {
        if (class_exists('\Dotenv\Dotenv') && function_exists('base_path')) {
            return \Dotenv\Dotenv::createArrayBacked(base_path())->load();
        } else {
            return $_ENV;
        }
    }
}
