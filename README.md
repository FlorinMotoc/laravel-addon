# laravel-addon

## Installation

Install the latest version with

```bash
composer require florinmotoc/laravel-addon
```

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
