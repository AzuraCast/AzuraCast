<?php
namespace App\Http\Factory;

use App\Http\Response;
use Http\Factory\Guzzle\StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    protected static $responseClass = Response::class;

    public static function setResponseClass(string $responseClass): void
    {
        self::$responseClass = $responseClass;
    }

    /**
     * Create a new response.
     *
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.
     *
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $responseFactory = new \Http\Factory\Guzzle\ResponseFactory();

        $response = $responseFactory->createResponse($code, $reasonPhrase);

        return new self::$responseClass($response, new StreamFactory);
    }
}