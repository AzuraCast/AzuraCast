<?php

declare(strict_types=1);

namespace App\Exception\Http;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

final class RateLimitExceededException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'You have exceeded the rate limit for this application.',
        int $code = 429,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }
}
