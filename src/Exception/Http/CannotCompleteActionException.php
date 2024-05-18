<?php

declare(strict_types=1);

namespace App\Exception\Http;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

final class CannotCompleteActionException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Cannot complete action.',
        int $code = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }

    public static function submitRequest(
        ServerRequestInterface $request,
        string $reason
    ): self {
        return new self(
            $request,
            sprintf(
                __('Cannot submit request: %s'),
                $reason
            )
        );
    }
}
