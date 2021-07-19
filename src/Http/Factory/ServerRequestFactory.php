<?php

declare(strict_types=1);

namespace App\Http\Factory;

use App\Http\ServerRequest;
use GuzzleHttp\Psr7\ServerRequest as GuzzleServerRequest;
use Http\Factory\Guzzle\ServerRequestFactory as GuzzleServerRequestFactory;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface, ServerRequestCreatorInterface
{
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $serverRequest = (new GuzzleServerRequestFactory())->createServerRequest($method, $uri, $serverParams);
        return $this->decorateServerRequest($serverRequest);
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function decorateServerRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        return new ServerRequest($request);
    }

    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        return $this->decorateServerRequest(GuzzleServerRequest::fromGlobals());
    }
}
