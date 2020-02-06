<?php
namespace App;

use App\Http\Factory\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App as SlimApp;

class App extends SlimApp
{
    /**
     * @inheritDoc
     */
    public function run(?ServerRequestInterface $request = null): void
    {
        if (!$request) {
            $request = $this->createServerRequest();
        }

        parent::run($request);
    }

    /**
     * @return ServerRequestInterface
     */
    public function createServerRequest(): ServerRequestInterface
    {
        $requestCreator = new ServerRequestFactory;
        return $requestCreator->createServerRequestFromGlobals();
    }
}