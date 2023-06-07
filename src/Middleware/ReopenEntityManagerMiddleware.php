<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Container\EnvironmentAwareTrait;
use App\Doctrine\DecoratedEntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ReopenEntityManagerMiddleware implements MiddlewareInterface
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly DecoratedEntityManager $em,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->em->open();

        try {
            return $handler->handle($request);
        } finally {
            if (!$this->environment->isTesting()) {
                $this->em->getConnection()->close();
            }

            $this->em->clear();
        }
    }
}
