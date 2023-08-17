<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

interface SingleActionInterface
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array<string, string> $params
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface;
}
