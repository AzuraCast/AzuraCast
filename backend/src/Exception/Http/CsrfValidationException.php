<?php

declare(strict_types=1);

namespace App\Exception\Http;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

final class CsrfValidationException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'CSRF Validation Error',
        int $code = 400,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }
}
