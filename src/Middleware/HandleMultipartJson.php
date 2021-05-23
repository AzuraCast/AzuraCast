<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * When sending data using multipart forms (that also include uploaded files, for example),
 * it isn't possible to encode JSON values (i.e. booleans) in the other submitted values.
 *
 * This allows an alternative body format, where the entirety of the JSON-parseable body is
 * set in any multipart parameter, parsed, and then assigned to the "parsedBody"
 * attribute of the PSR-7 request. This implementation is transparent to any controllers
 * using this code.
 */
class HandleMultipartJson implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = array_filter(
            $request->getParsedBody(),
            static function ($value) {
                return $value && 'null' !== $value;
            }
        );

        if (1 === count($parsedBody)) {
            $bodyField = current($parsedBody);
            if (is_string($bodyField)) {
                $parsedBody = json_decode($bodyField, true, 512, \JSON_THROW_ON_ERROR);

                $request = $request->withParsedBody($parsedBody);
            }
        }

        return $handler->handle($request);
    }
}
