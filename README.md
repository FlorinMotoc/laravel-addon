# laravel-addon

---

## Installation

Install the latest version with

```bash
composer require florinmotoc/laravel-addon
```

---

## testing addon
#### your phpunit laravel tests should extend `FlorinMotoc\LaravelAddon\Tests\TestCase`
- set `FM_LARAVEL_ADDON_TESTS_USE_DATABASE_MOCKING=false` in your `.env` file if you want to disable database mocking
    - how database mocking works:
        - first time it will create `database/init.sqlite` - via `artisan migrate && artisan db:seed`
        - following tests will copy `init.sqlite` into `a.sqlite` for each run
            - you can use env variable `PHPUNITDB=` to change sqlite file. ex: if you run tests in parallel and each test should use its own database file
- set `FM_LARAVEL_ADDON_TESTS_USE_GUZZLE_HTTP_MOCKING=false` in your `.env` file if you want to disable guzzle http mocking
- set `FM_LARAVEL_ADDON_TESTS_USE_LARAVEL_HTTP_MOCKING=false` in your `.env` file if you want to disable laravel http mocking
- set `FM_LARAVEL_ADDON_TESTS_HTTP_MOCK_BEAUTIFY_JSON=false` in your `.env` file if you want to disable json beautifier in mock files
- set `FM_LARAVEL_ADDON_TESTS_HTTP_MOCK_EXCEPTION_FOR_NEW_MOCKS=false` in your `.env` file if you want to not throw exceptions for new mock files
    - it's recommended to be left true for automated test runs (ex Jenkins) because you want to know when a http call is not mocked (test will fail)
- set `FM_LARAVEL_ADDON_TESTS_HTTP_MOCK_IGNORED_HOSTS=domain.tld,domain2.tld` in your `.env` file if you want to not generate http mock files for some domains.
- set `FM_LARAVEL_ADDON_TESTS_HTTP_MOCK_REGENERATE=true` in your `.env` file if you want to ignore disk files and regenerate
- set `FM_LARAVEL_ADDON_TESTS_HTTP_SKIP_MOCK_GENERATION=true` in your `.env` file if you want to ignore disk files and make http calls - without mock file generation

---

## statsd addon

```dotenv
FM_LARAVEL_ADDON_STATSD_JOB_TIME_ENABLED=true
FM_LARAVEL_ADDON_STATSD_CLIENT=FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG
# example of .env variables
FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_HOST=localhost
FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_PORT=8125
FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_LOCAL_HOSTNAME=optional-hostname
FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_TAGS_CUSTOM_SOME_KEY=some_value
```

- set `FM_LARAVEL_ADDON_STATSD_JOB_TIME_ENABLED=true` in your `.env` file if you want to send laravel queue job times to statsd
- set `FM_LARAVEL_ADDON_STATSD_CLIENT` to any of:
  - `FM_LARAVEL_ADDON_STATSD_CLIENT_NULL` - nothing will be stored
  - `FM_LARAVEL_ADDON_STATSD_CLIENT_ARRAY` - all will be stored into ArrayStatsdClient::$data
  - `FM_LARAVEL_ADDON_STATSD_CLIENT_DATADOG` - default datadog statsd client - udp localhost on port 8125
  - `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG` - datadog statsd client with possibility to change host,port,hostname; add custom tags
- if `FM_LARAVEL_ADDON_STATSD_CLIENT` is `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG`, then these are available:
    - `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_HOST` - your statsd host
    - `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_PORT` - your statsd port
    - `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_LOCAL_HOSTNAME=changeable-hostname`
        - if you want to send a custom hostname (not system hostname), you can set this .env variable
    - `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_TAGS_CUSTOM_`
        - this is a prefix.
        - anything after prefix's `_` will be sent to statsd with a prefix `c_`
        - ex: `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_TAGS_CUSTOM_ABC=ABC` will send to statsd `c_abc=ABC`
        - ex: `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_TAGS_CUSTOM_ABC=abc` will send to statsd `c_abc=abc`
        - ex: `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_TAGS_CUSTOM_D=smth` will send to statsd `c_d=smth`
        - ex: `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_TAGS_CUSTOM_ENV=prod` will send to statsd `c_env=prod`
        - ex: `FM_LARAVEL_ADDON_STATSD_CLIENT_CUSTOM_DATADOG_TAGS_CUSTOM_ENV=devel` will send to statsd `c_env=devel`

---

## logs addon
### This will log in json format to laravel.log with extra information, and when used via CLI it will log in human-readable format in console output, and also in json format in laravel.log.

```dotenv
LOG_CHANNEL=fm_stack
FM_LARAVEL_ADDON_LOGS_USE_EXTRA_PID=true
FM_LARAVEL_ADDON_LOGS_USE_EXTRA_JOB_INFO=true
FM_LARAVEL_ADDON_LOGS_USE_EXTRA_INTROSPECTION=true

# change this to one of \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_* values for more logs - 256 is very verbose!
FM_LARAVEL_ADDON_LOGS_CONSOLE_VERBOSITY=256
```

- set `LOG_CHANNEL=fm_stack` in your `.env` file to activate `LaravelMonologTap`
    - also need to change laravel's `config/logging.php` file with contents below!
- set `FM_LARAVEL_ADDON_LOGS_USE_EXTRA_PID=true` in your `.env` file if you want to add the PID to the monolog extra array.
- set `FM_LARAVEL_ADDON_LOGS_USE_EXTRA_JOB_INFO=true` in your `.env` file if you want to add the laravel queue jobs id to the monolog extra array.
- set `FM_LARAVEL_ADDON_LOGS_USE_EXTRA_INTROSPECTION=true` in your `.env` file if you want to add the `\Monolog\Processor\IntrospectionProcessor` to the monolog extra array. (this will add file,class,function,line)
- optionally set `FM_LARAVEL_ADDON_LOGS_CONSOLE_VERBOSITY=` in your `.env` file to control verbosity
    - change this to one of `\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_*` values for more logs - 256 is very verbose!
        - VERBOSITY_QUIET = 16;
        - VERBOSITY_NORMAL = 32;
        - VERBOSITY_VERBOSE = 64;
        - VERBOSITY_VERY_VERBOSE = 128;
        - VERBOSITY_DEBUG = 256;

```php
<?php
// laravel's config/logging.php file:

return [
    'default' => env('LOG_CHANNEL', 'fm_stack'),
    'channels' => [
        'fm_stack' => [
            'driver' => 'stack',
            'channels' => ['fm_console', 'fm_file'],
            'ignore_exceptions' => false,
        ],

        'fm_console' => [
            'driver' => 'monolog',
            'handler' => \FlorinMotoc\LaravelAddon\Logs\LaravelMonologTap\Handler\ConsoleHandler::class,
            'with' => [
                'verbosity' => env('FM_LARAVEL_ADDON_LOGS_CONSOLE_VERBOSITY'), // \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_DEBUG
            ]
        ],

        'fm_file' => [
            'driver' => 'monolog',
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'handler' => Monolog\Handler\StreamHandler::class,
            'with' => [
                'stream' => storage_path('logs/laravel.log'),
                'level' => 'debug',
            ],
            'tap' => [
                \FlorinMotoc\LaravelAddon\Logs\LaravelMonologTap\LaravelMonologTap::class
            ],
        ],
    ]
]
```

---
