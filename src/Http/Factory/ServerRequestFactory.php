<?php

declare(strict_types=1);

namespace App\Http\Factory;

use App\Http\ServerRequest;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest as GuzzleServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class ServerRequestFactory implements ServerRequestFactoryInterface, ServerRequestCreatorInterface
{
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $serverRequest = (new HttpFactory())->createServerRequest($method, $uri, $serverParams);
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
