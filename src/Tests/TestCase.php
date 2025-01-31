<?php

namespace FlorinMotoc\LaravelAddon\Tests;

use FlorinMotoc\LaravelAddon\Http\Client\BaseClient;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TestCase
 * @package FlorinMotoc\LaravelAddon\Tests
 * @codeCoverageIgnore
 */
abstract class TestCase extends LaravelTestCase
{
    const HTTP_MOCKS_DIR_NAME = "http.mocks";

    /**
     * @return Application|\Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $dbName = $_SERVER['PHPUNITDB'] ?? 'a';

        putenv('APP_ENV=testing');
        putenv('DB_DATABASE=' . $this->getDatabaseLocation($dbName));
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
        $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $this->getDatabaseLocation($dbName);

        $app = require "{$this->getRootPath()}bootstrap/app.php";
        $app->make(Kernel::class)->bootstrap();
        $this->app = $app; // needed for things in following methods (like this->artisan())

        if (env('FM_LARAVEL_ADDON_TESTS_USE_DATABASE_MOCKING', true)) {
            $this->mockDatabase($dbName);
        }
        if (env('FM_LARAVEL_ADDON_TESTS_USE_GUZZLE_HTTP_MOCKING', true)) {
            $this->mockGuzzleHttpClient();
        }
        if (env('FM_LARAVEL_ADDON_TESTS_USE_LARAVEL_HTTP_MOCKING', true)) {
            $this->mockLaravelHttpClient();
        }

        return $app;
    }

    private function getRootPath(): string
    {
        $ds = DIRECTORY_SEPARATOR;

        // return to pre-vendor path
        return str_replace("vendor{$ds}florinmotoc{$ds}laravel-addon{$ds}src{$ds}Tests", '', __DIR__);
    }

    private function getDatabaseLocation(string $name): string
    {
        return "{$this->getRootPath()}database/$name.sqlite";
    }

    /**
     * @param string $dbName
     * @throws \Throwable
     */
    private function mockDatabase(string $dbName): void
    {
        if (!file_exists($this->getDatabaseLocation($dbName)) || !file_exists($this->getDatabaseLocation('init'))) {
            @unlink($this->getDatabaseLocation($dbName));
            exec('touch ' . $this->getDatabaseLocation($dbName));
            try {
                $this->migrateAndSeed();
            } catch (\Throwable $e) {
                @unlink($this->getDatabaseLocation($dbName));
                throw $e;
            }
            copy($this->getDatabaseLocation($dbName), $this->getDatabaseLocation('init')); // copy dbName to init
        }

        copy($this->getDatabaseLocation('init'), $this->getDatabaseLocation($dbName)); // copy init to dbName
    }

    private function migrateAndSeed()
    {
        $start = microtime(1);
        $this->artisan('migrate', ['--force' => true]);
        echo (microtime(1) - $start) . ' (migrate)' . PHP_EOL;

        $start = microtime(1);
        $this->artisan('db:seed');
        echo (microtime(1) - $start) . ' (seed)' . PHP_EOL;
    }

    private function mockGuzzleHttpClient()
    {
        $handler = new GuzzleTestHandler();
        $handler->setBasePath($this->getGuzzleTestHandlerBasePath());

        $client = $this->app->make(BaseClient::class, ['config' => ['handler' => $handler]]);

        $this->app->instance(BaseClient::class, $client);
    }

    public function mockLaravelHttpClient()
    {
        Http::fake(function (Request $request) {
            $ignoreHosts = explode(',', env('FM_LARAVEL_ADDON_TESTS_HTTP_MOCK_IGNORED_HOSTS'));
            if (!in_array($request->toPsrRequest()->getUri()->getHost(), $ignoreHosts)) {
                return $this->getMockedHttpClientResponse($request->toPsrRequest());
            }
        });
    }

    public function getMockedHttpClientResponse(RequestInterface $request)
    {
        $handler = new GuzzleTestHandler();
        $handler->setBasePath($this->getGuzzleTestHandlerBasePath());

        /** @var ResponseInterface $data */
        $data = $handler->read($request);

        return Http::response($data->getBody()->getContents(), $data->getStatusCode(), $data->getHeaders());
    }
    private function getGuzzleTestHandlerBasePath(): string
    {
        $path = preg_replace("/(.*vendor\/florinmotoc.*tests)(.*)/", "$1/" . self::HTTP_MOCKS_DIR_NAME, $_SERVER['PWD'] ?? getcwd(), -1, $count);

        if (!$count) {
            // if pwd is outside vendor, check cmd arguments to be sure we are running tests out of vendor
            $argv = $_SERVER['argv'];
            unset($argv[0]);
            foreach ($argv as $value) {
                $match = preg_match("/vendor\/florinmotoc\/.*\/phpunit.xml/", $value, $matches);
                if ($match === 1) {
                    return $this->app->basePath(str_replace('phpunit.xml', 'tests/' . self::HTTP_MOCKS_DIR_NAME, $matches[0]));
                }
            }

            // if outside vendor, overwrite $path
            return $this->app->basePath('tests/' . self::HTTP_MOCKS_DIR_NAME);
        }

        return $path;
    }
}
