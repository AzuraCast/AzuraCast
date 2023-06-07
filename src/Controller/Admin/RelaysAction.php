<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Relay;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class RelaysAction
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $relays = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Relay::class, 'e')
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
