<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Doctrine\DecoratedEntityManager;
use App\Environment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ReopenEntityManagerMiddleware implements MiddlewareInterface
{
    protected DecoratedEntityManager $em;

    protected Environment $environment;

    public function __construct(DecoratedEntityManager $em, Environment $environment)
    {
        $this->em = $em;
        $this->environment = $environment;
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
