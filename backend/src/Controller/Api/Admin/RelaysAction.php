<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Relay;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/relays/list',
        operationId: 'adminGetRelays',
        summary: 'Return a list of all currently active AzuraRelay instances.',
        tags: [OpenApi::TAG_ADMIN],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: Relay::class
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class RelaysAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $query = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Relay::class, 'e')
            ->getQuery();

        $paginator = Paginator::fromQuery($query, $request);
        return $paginator->write($response);
    }
}
