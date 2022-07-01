<?php

declare(strict_types=1);

namespace App\Http\Factory;

use App\Http\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Factory\DecoratedResponseFactory;

final class ResponseFactory extends DecoratedResponseFactory
{
    public function __construct()
    {
        $httpFactory = new HttpFactory();
        parent::__construct($httpFactory, $httpFactory);
    }

    /**
     * Create a new response.
     *
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($code, $reasonPhrase);
        return new Response($response, $this->streamFactory);
    }
}
