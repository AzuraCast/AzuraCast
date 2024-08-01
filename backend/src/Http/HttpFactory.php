<?php

declare(strict_types=1);

namespace App\Http;

use GuzzleHttp\Psr7\HttpFactory as GuzzleHttpFactory;
use GuzzleHttp\Psr7\ServerRequest as GuzzleServerRequest;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class HttpFactory implements
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface,
    ServerRequestCreatorInterface
{
    private readonly GuzzleHttpFactory $httpFactory;

    public function __construct()
    {
        $this->httpFactory = new GuzzleHttpFactory();
    }

    public function createUploadedFile(...$args): UploadedFileInterface
    {
        return $this->httpFactory->createUploadedFile(...$args);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return $this->httpFactory->createStream($content);
    }

    public function createStreamFromFile(...$args): StreamInterface
    {
        return $this->httpFactory->createStreamFromFile(...$args);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return $this->httpFactory->createStreamFromResource($resource);
    }

    public function createServerRequest(...$args): ServerRequestInterface
    {
        $serverRequest = $this->httpFactory->createServerRequest(...$args);
        return $this->decorateServerRequest($serverRequest);
    }

    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        return $this->decorateServerRequest(GuzzleServerRequest::fromGlobals());
    }

    private function decorateServerRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        return new ServerRequest($request);
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = $this->httpFactory->createResponse($code, $reasonPhrase);
        return new Response($response, $this->httpFactory);
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        return $this->httpFactory->createRequest($method, $uri);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return $this->httpFactory->createUri($uri);
    }
}
