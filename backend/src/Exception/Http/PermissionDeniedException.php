<?php

declare(strict_types=1);

namespace App\Exception\Http;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

final class PermissionDeniedException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Permission denied.',
        int $code = 403,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }

    public static function create(ServerRequestInterface $request): self
    {
        return new self(
            $request,
            __('You do not have permission to access this portion of the site.')
        );
    }
}
