<?php

namespace FlorinMotoc\LaravelAddon\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GuzzleTestHandler
 * @package FlorinMotoc\LaravelAddon\Tests
 * @codeCoverageIgnore
 */
class GuzzleTestHandler
{
    /** @var string */
    protected $basePath;

    /** @var callable[] */
    protected static $customRequestHandlers = [];

    /** @var callable[] */
    protected static $customResponseHandlers = [];

    /**
     * @param callable $customRequestHandler
     * Example of usage:
     *      GuzzleTestHandler::addCustomRequestHandlers(function (\Psr\Http\Message\RequestInterface $request) {
     *           if ($request->getUri() != 'google.com') {
     *               return null;
     *           } else {
     *               return [$testPrefix, $testId];
     *           }
     *      });
     */
    public static function addCustomRequestHandlers(callable $customRequestHandler)
    {
        self::$customRequestHandlers[] = $customRequestHandler;
    }

    public static function removeCustomRequestHandlers()
    {
        self::$customRequestHandlers = [];
    }

    /**
     * @param callable $customResponseHandler
     * Example of usage:
     *      GuzzleTestHandler::addCustomResponseHandlers(function (\Psr\Http\Message\ResponseInterface $response) {
     *          // return null if you want to skip, or $response if you want to override $response
     *      });
     */
    public static function addCustomResponseHandlers(callable $customResponseHandler)
    {
        self::$customResponseHandlers[] = $customResponseHandler;
    }

    public static function removeCustomResponseHandlers()
    {
        self::$customResponseHandlers = [];
    }

    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return PromiseInterface
     * @throws \Throwable
     */
    public function __invoke(RequestInterface $request, $options)
    {
        $data = $this->read($request);

        if ($data instanceof ResponseInterface) {
            if ($data->getStatusCode() > 300) {
                $body = $data->getBody()->getContents();
                $data->getBody()->rewind();

                return new RejectedPromise(
                    new RequestException(
                        "RequestException with status {$data->getStatusCode()} and body: $body",
                        $request,
                        $data
                    )
                );
            }
            return new FulfilledPromise($data);
        }

        return new FulfilledPromise(new Response(200, [], $data));
    }

    /**
     * @param RequestInterface $request
     * @return string
     * @throws \Exception
     */
    public function read(RequestInterface $request)
    {
        if (env('FM_LARAVEL_ADDON_TESTS_HTTP_SKIP_MOCK_GENERATION')) {
            return $this->sendRequest($request);
        }

        list($testPrefix, $testId) = $this->handleRequest($request);

        try {
            if (env('FM_LARAVEL_ADDON_TESTS_HTTP_MOCK_REGENERATE')) {
                throw new \Exception('regenerate files');
            }
            return $this->getTestSample($testPrefix, $testId);
        } catch (\Throwable $e) {
            $this->createMockFile($request, $testPrefix, $testId);

            return $this->getTestSample($testPrefix, $testId);
        }
    }

    /**
     * @param string $testPrefix
     * @param int $testId
     * @return string
     * @throws \Exception
     * @note Files are touched so we can run cleanup by last modified date
     */
    protected function getTestSample($testPrefix, $testId)
    {
        $filePathName = "{$this->getBasePath()}/{$testPrefix}/{$testId}";

        if (file_exists("{$filePathName}.serialized")) {
            touch("{$filePathName}.serialized");
            $unserialized = file_get_contents("{$filePathName}.serialized");

            $object = \GuzzleHttp\Psr7\Message::parseResponse($unserialized);

            return $object;
        }

        throw new \Exception("mock file not found");
    }

    /**
     * @param RequestInterface $request
     * @param mixed $testPrefix
     * @param mixed $testId
     * @throws \Exception
     */
    protected function createMockFile(RequestInterface $request, $testPrefix, $testId)
    {
        $response = $this->sendRequest($request);
        $response = $this->responseToString($response);

        $filePath = "{$this->getBasePath()}/{$testPrefix}/{$testId}.serialized";
        @mkdir("{$this->getBasePath()}/{$testPrefix}", 0777, true);
        touch($filePath);
        file_put_contents($filePath, $response);

        if (class_exists(\Log::class)) {
            \Log::debug("Missing mock file. File was created {$testPrefix}/{$testId}.serialized");
        }

        if (env('FM_LARAVEL_ADDON_TESTS_HTTP_MOCK_EXCEPTION_FOR_NEW_MOCKS', true)) {
            throw new \Exception("Missing mock file. File was created {$testPrefix}/{$testId}.serialized");
        }
    }

    protected function sendRequest(RequestInterface $request): ?ResponseInterface
    {
        $guzzleMain = new Client();

        try {
            $response = $guzzleMain->send($request);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if (null === $response) {
                throw new \Exception("Response was null. Error was: {$e->getMessage()}");
            }
        }

        return $this->handleResponse($response);
    }

    /**
     * @param MessageInterface $message
     * @return string
     *
     * @see \GuzzleHttp\Psr7\Message::toString()
     */
    protected function responseToString(MessageInterface $message): string
    {
        if ($message instanceof RequestInterface) {
            $msg = trim($message->getMethod().' '
                    .$message->getRequestTarget())
                .' HTTP/'.$message->getProtocolVersion();
            if (!$message->hasHeader('host')) {
                $msg .= "\r\nHost: ".$message->getUri()->getHost();
            }
        } elseif ($message instanceof ResponseInterface) {
            $msg = 'HTTP/'.$message->getProtocolVersion().' '
                .$message->getStatusCode().' '
                .$message->getReasonPhrase();
        } else {
            throw new \InvalidArgumentException('Unknown message type');
        }

        foreach ($message->getHeaders() as $name => $values) {
            if (is_string($name) && strtolower($name) === 'set-cookie') {
                foreach ($values as $value) {
                    $msg .= "\r\n{$name}: ".$value;
                }
            } else {
                $msg .= "\r\n{$name}: ".implode(', ', $values);
            }
        }

        $body = $message->getBody()->getContents();

        if (env('FM_LARAVEL_ADDON_TESTS_HTTP_MOCK_BEAUTIFY_JSON', true)) {
            try {
                $body = \GuzzleHttp\json_encode(\GuzzleHttp\json_decode($body), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (\Throwable $e) {
                // $body is not json, is ok, let it unformatted
            }
        }

        return "{$msg}\r\n\r\n".$body;
    }


    private function handleRequest(RequestInterface $request): array
    {
        foreach (self::$customRequestHandlers as $customRequestHandler) {
            list($testPrefix, $testId) = $customRequestHandler($request);

            if ($testPrefix !== null && $testId !== null) {
                return [$testPrefix, $testId];
            }
        }

        return $this->handleGeneric($request);
    }

    private function handleResponse(?ResponseInterface $response): ?ResponseInterface
    {
        foreach (self::$customResponseHandlers as $customResponseHandler) {
            $newResponse = $customResponseHandler($response);

            if ($newResponse !== null) {
                return $newResponse;
            }
        }

        return $response;
    }

    private function handleGeneric(RequestInterface $request): array
    {
        $testPrefix = $request->getUri()->getHost();
        if (empty($testPrefix)) {
            $testPrefix = $request->getUri()->getPath();
        }

        $testId = $request->getMethod() . '.';

        $headers = $request->getHeaders();
        unset($headers['User-Agent']);

        $friendlyUri = str_replace(["{$request->getUri()->getScheme()}://", "$testPrefix/", $testPrefix], '', (string)$request->getUri());
        $friendlyUri = substr($friendlyUri, 0, 150);
        $testId .= preg_replace("/[^A-Za-z0-9 ]/", '_', $friendlyUri) . '.';
        $testId = str_replace('..', '.', $testId);

        $body = $request->getBody()->getContents();
        $request->getBody()->rewind();

        $testId .= md5(
            $request->getMethod()
            . $request->getUri()->getPath()
            . $request->getUri()->getQuery()
            . json_encode($headers)
            . $body
        );

        return [$testPrefix, $testId];
    }
}
