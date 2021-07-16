<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class RelaysController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em
    ): ResponseInterface {
        $relays = $em->createQueryBuilder()
            ->select('e')
            ->from(Entity\Relay::class, 'e')
            ->getQuery()->getArrayResult();

        return $request->getView()->renderToResponse(
            $response,
            'admin/relays/index',
            [
                'relays' => $relays,
            ]
        );
    }
}
