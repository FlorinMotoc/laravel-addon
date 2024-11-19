<?php

namespace FlorinMotoc\LaravelAddon\Logs\LaravelMonologTap;

use Illuminate\Log\Logger;

class LaravelMonologTap
{
    public function __invoke(Logger $logger)
    {
        if (env('FM_LARAVEL_ADDON_LOGS_USE_EXTRA_INTROSPECTION')) {
            $introspection = new \Monolog\Processor\IntrospectionProcessor(
                \Monolog\Logger::DEBUG, // whatever level you want this processor to handle
                [
                    'Monolog\\',
                    'Illuminate\\',
                ]
            );
        }

        foreach ($logger->getHandlers() as $handler) {
            if (env('FM_LARAVEL_ADDON_LOGS_USE_EXTRA_INTROSPECTION')) {
                $handler->pushProcessor($introspection);
            }
            if (env('FM_LARAVEL_ADDON_LOGS_USE_EXTRA_PID')) {
                $handler->pushProcessor([$this, 'processLogRecordAddPid']);
            }

            if (env('FM_LARAVEL_ADDON_LOGS_USE_EXTRA_JOB_INFO')) {
                $handler->pushProcessor([$this, 'processLogRecordAddLaravelJobId']);
            }
        }
    }

    public function processLogRecordAddPid(\Monolog\LogRecord|array $record): \Monolog\LogRecord|array
    {
        $record['extra']['pid'] = getmypid();

        return $record;
    }

    public function processLogRecordAddLaravelJobId(\Monolog\LogRecord|array $record): \Monolog\LogRecord|array
    {
        $jobId = $GLOBALS['fm_laravel_addon_logs_queue_job_data']['jobId'] ?? null;
        if (is_string($jobId)) {
            $record['extra']['jobId'] = $jobId;
        }

        return $record;
    }
}
