<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\ServerRequest;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

use const JSON_THROW_ON_ERROR;

/**
 * When sending data using multipart forms (that also include uploaded files, for example),
 * it isn't possible to encode JSON values (i.e. booleans) in the other submitted values.
 *
 * This allows an alternative body format, where the entirety of the JSON-parseable body is
 * set in any multipart parameter, parsed, and then assigned to the "parsedBody"
 * attribute of the PSR-7 request. This implementation is transparent to any controllers
 * using this code.
 */
final class HandleMultipartJson extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        if (!empty($parsedBody)) {
            $parsedBody = array_filter(
                (array)$parsedBody,
                static function ($value) {
                    return $value && 'null' !== $value;
                }
            );

            if (1 === count($parsedBody)) {
                $bodyField = current($parsedBody);
                if (is_string($bodyField)) {
                    try {
                        $parsedBody = json_decode($bodyField, true, 512, JSON_THROW_ON_ERROR);
                        $request = $request->withParsedBody($parsedBody);
                    } catch (JsonException) {
                    }
                }
            }
        }

        return $handler->handle($request);
    }
}
