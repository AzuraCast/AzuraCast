<?php
namespace App\Tests;

use App\Http\Factory\ServerRequestFactory;
use Codeception\Lib\Connector\Shared\PhpSuperGlobalsConverter;
use Slim\App;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;

class Connector extends AbstractBrowser
{
    use PhpSuperGlobalsConverter;

    /**
     * @var App
     */
    protected $app;

    /**
     * @param App $app
     */
    public function setApp(App $app): void
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
        if (strtoupper($request->getMethod()) === 'GET') {
            $_GET = $_REQUEST;
            $_POST = [];
        } else {
            $_GET = [];
            $_POST = $_REQUEST;
        }

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
        $_SERVER['REQUEST_URI'] = $uri;

        $serverRequestCreator = new ServerRequestFactory;
        $request = $serverRequestCreator->createServerRequestFromGlobals();

        $slimResponse = $this->app->handle($request);

        return new BrowserKitResponse(
            (string)$slimResponse->getBody(),
            $slimResponse->getStatusCode(),
            $slimResponse->getHeaders()
        );
    }
}
