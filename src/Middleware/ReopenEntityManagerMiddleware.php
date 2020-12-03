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

    protected Environment $settings;

    public function __construct(DecoratedEntityManager $em, Environment $settings)
    {
        $this->em = $em;
        $this->settings = $settings;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->em->open();

        try {
            return $handler->handle($request);
        } finally {
            if (!$this->settings->isTesting()) {
                $this->em->getConnection()->close();
            }

            $this->em->clear();
        }
    }
}
