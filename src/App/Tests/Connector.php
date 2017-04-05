<?php
/**
 * Based on Herloct's Slim 3.0 Connector
 * https://github.com/herloct/codeception-slim-module
 */

namespace App\Tests;

use Codeception\Lib\Connector\Shared\PhpSuperGlobalsConverter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\App;
use Slim\Http\Cookies;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\RequestBody;
use Slim\Http\Stream;
use Slim\Http\UploadedFile;
use Slim\Http\Uri;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;

class Connector extends Client
{
    use PhpSuperGlobalsConverter;

    /**
     * @var App
     */
    protected $app;

    /**
     * @param App $app
     */
    public function setApp(App $app)
    {
        $this->app = $app;
    }

    /**
     * Makes a request.
     *
     * @param BrowserKitRequest $request An origin request instance
     *
     * @return BrowserKitResponse An origin response instance
     */
    public function doRequest($request)
    {
        $_COOKIE = $request->getCookies();
        $_SERVER = $request->getServer();
        $_FILES = $this->remapFiles($request->getFiles());

        $uri = str_replace('http://localhost', '', $request->getUri());

        $_REQUEST = $this->remapRequestParameters($request->getParameters());
        if (strtoupper($request->getMethod()) == 'GET') {
            $_GET = $_REQUEST;
            $_POST = [];
        } else {
            $_GET = [];
            $_POST = $_REQUEST;
        }

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
        $_SERVER['REQUEST_URI'] = $uri;

        $slimRequest = $this->convertRequest($request);

        $container = $this->app->getContainer();

        /* @var $slimResponse ResponseInterface */
        $slimResponse = $container->get('response');

        // reset body stream
        $slimResponse = $slimResponse->withBody(new Stream(fopen('php://temp', 'w+')));

        $slimResponse = $this->app->process($slimRequest, $slimResponse);

        return new BrowserKitResponse(
            (string)$slimResponse->getBody(),
            $slimResponse->getStatusCode(),
            $slimResponse->getHeaders()
        );
    }

    /**
     * Convert to PSR-7's ServerRequestInterface.
     *
     * @param BrowserKitRequest $request
     * @return ServerRequestInterface
     */
    protected function convertRequest(BrowserKitRequest $request)
    {
        $environment = Environment::mock($request->getServer());
        $uri = Uri::createFromString($request->getUri());
        $headers = Headers::createFromEnvironment($environment);
        $cookies = Cookies::parseHeader($headers->get('Cookie', []));

        $container = $this->app->getContainer();

        /* @var $slimRequest ServerRequestInterface */
        $slimRequest = $container->get('request');

        $slimRequest = $slimRequest->withMethod($request->getMethod())
            ->withUri($uri)
            ->withUploadedFiles($this->convertFiles($request->getFiles()))
            ->withCookieParams($cookies);

        foreach ($headers->keys() as $key) {
            $slimRequest = $slimRequest->withHeader($key, $headers->get($key));
        }

        if ($request->getContent() !== null) {
            $body = new RequestBody();
            $body->write($request->getContent());
            $slimRequest = $slimRequest
                ->withBody($body);
        }

        $parsed = [];
        if ($request->getMethod() !== 'GET') {
            $parsed = $request->getParameters();
        }

        // make sure we do not overwrite a request with a parsed body
        if (!$slimRequest->getParsedBody()) {
            $slimRequest = $slimRequest
                ->withParsedBody($parsed);
        }

        return $slimRequest;
    }

    /**
     * Convert to PSR-7's UploadedFileInterface.
     *
     * @param array $files
     * @return array
     */
    protected function convertFiles(array $files)
    {
        $fileObjects = [];
        foreach ($files as $fieldName => $file) {
            if ($file instanceof UploadedFileInterface) {
                $fileObjects[$fieldName] = $file;
            } elseif (!isset($file['tmp_name']) && !isset($file['name'])) {
                $fileObjects[$fieldName] = $this->convertFiles($file);
            } else {
                $fileObjects[$fieldName] = new UploadedFile(
                    $file['tmp_name'],
                    $file['name'],
                    $file['type'],
                    $file['size'],
                    $file['error']
                );
            }
        }

        return $fileObjects;
    }
}
