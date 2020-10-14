<?php

namespace App\Controller\Admin;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class RelaysController
{
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $relays = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Entity\Relay::class, 'e')
            ->getQuery()->getArrayResult();

        return $request->getView()->renderToResponse($response, 'admin/relays/index', [
            'relays' => $relays,
        ]);
    }
}
