<?php

declare(strict_types=1);

namespace App\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

final class WrappedException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        ?Throwable $previous
    ) {
        parent::__construct($request, 'Wrapped HTTP Exception', 500, $previous);
    }
}
