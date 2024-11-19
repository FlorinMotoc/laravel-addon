<?php

namespace FlorinMotoc\LaravelAddon;

use FlorinMotoc\LaravelAddon\Statsd\Client\ArrayStatsdClient;
use FlorinMotoc\LaravelAddon\Statsd\Client\CustomDatadogStatsdClient;
use FlorinMotoc\LaravelAddon\Statsd\Client\DatadogStatsdClient;
use FlorinMotoc\LaravelAddon\Statsd\Client\NullStatsdClient;
use FlorinMotoc\LaravelAddon\Statsd\Client\StatsdClientInterface;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class LaravelAddonServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->statsdAddonRegister();
    }

    public function boot()
    {
        if (env('FM_LARAVEL_ADDON_STATSD_JOB_TIME_ENABLED')) {
            $this->statsdAddonSendQueueJobsProcessingTime();
        }
        if (env('FM_LARAVEL_ADDON_LOGS_USE_EXTRA_JOB_INFO')) {
            $this->logsAddonAddJobId();
        }
    }

    protected function statsdAddonRegister()
    {
        $mapping = [
            'FM_LARAVEL_ADDON_STATSD_CLIENT_DATADOG' => DatadogStatsdClient::class,
            'FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG' => CustomDatadogStatsdClient::class,
            'FM_LARAVEL_ADDON_STATSD_CLIENT_ARRAY' => ArrayStatsdClient::class,
            'FM_LARAVEL_ADDON_STATSD_CLIENT_NULL' => NullStatsdClient::class,
        ];

        $envClient = env('FM_LARAVEL_ADDON_STATSD_CLIENT');
        if (array_key_exists($envClient, $mapping)) {
            $this->app->singleton(StatsdClientInterface::class, $mapping[$envClient]);
        } else {
            $this->app->singleton(StatsdClientInterface::class, NullStatsdClient::class);
        }

        foreach ($mapping as $class) {
            $this->app->singleton($class);
        }
    }

    protected function statsdAddonSendQueueJobsProcessingTime(): void
    {
        Queue::before(function (JobProcessing $event) {
            try {
                $event->job->_logJobTime_StartAt = microtime(1); // maybe a better idea for the variable transfer?
            } catch (\Throwable $e) {
                Log::error(sprintf("LaravelAddonServiceProvider statsdAddonSendQueueJobsProcessingTime logJobTime error @ before: %s @ %s @ %s"
                    , $e->getMessage(), $e->getFile(), $e->getLine()));
            }
        });

        Queue::after(function (JobProcessed $event) {
            try {
                // maybe a better idea for the variable transfer? :: $event->job->_logJobTime_StartAt
                /** @var StatsdClientInterface $statsdClient */
                $statsdClient = $this->app->get(StatsdClientInterface::class);
                $statsdClient->microtiming(
                    'dogstatsd.time.queue.job',
                    (microtime(1) - $event->job->_logJobTime_StartAt),
                    ['class' => $event->job->payload()['displayName'] ?? 'null']
                );
            } catch (\Throwable $e) {
                Log::error(sprintf("LaravelAddonServiceProvider statsdAddonSendQueueJobsProcessingTime logJobTime error @ after: %s @ %s @ %s"
                    , $e->getMessage(), $e->getFile(), $e->getLine()));
            }
        });
    }

    protected function logsAddonAddJobId()
    {
        Queue::before(function (JobProcessing $event) {
            try {
                $GLOBALS['fm_laravel_addon_logs_queue_job_data']['jobId'] = $event->job->getJobId();
            } catch (\Throwable $e) {
                Log::error(sprintf("LaravelAddonServiceProvider logsAddonAddJobId error @ before: %s @ %s @ %s"
                    , $e->getMessage(), $e->getFile(), $e->getLine()));
            }
        });

        Queue::after(function (JobProcessed $event) {
            try {
                unset($GLOBALS['fm_laravel_addon_logs_queue_job_data']);
            } catch (\Throwable $e) {
                Log::error(sprintf("LaravelAddonServiceProvider logsAddonAddJobId error @ after: %s @ %s @ %s"
                    , $e->getMessage(), $e->getFile(), $e->getLine()));
            }
        });
    }

}
