<?php

declare(strict_types=1);

namespace App\Exception\Http;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

final class InvalidRequestAttribute extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Invalid request attribute.',
        int $code = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }
}
