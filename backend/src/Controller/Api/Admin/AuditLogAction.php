<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\AcceptsDateRange;
use App\Entity\Api\Admin\AuditLog as ApiAuditLog;
use App\Entity\AuditLog;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/auditlog',
        operationId: 'getAuditlog',
        description: 'Return a list of all available permissions.',
        tags: ['Administration: General'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: ApiAuditLog::class
                    )
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class AuditLogAction
{
    use AcceptsDateRange;
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $dateRange = $this->getDateRange($request);
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from(AuditLog::class, 'a')
            ->andWhere('a.timestamp >= :start AND a.timestamp <= :end')
            ->setParameter('start', $dateRange->start)
            ->setParameter('end', $dateRange->end);

        $searchPhrase = trim(
            Types::string($request->getQueryParam('searchPhrase'))
        );

        if (!empty($searchPhrase)) {
            $qb->andWhere('(a.user LIKE :query OR a.identifier LIKE :query OR a.target LIKE :query)')
                ->setParameter('query', '%' . $searchPhrase . '%');
        }

        $qb->orderBy('a.timestamp', 'DESC');

        $paginator = Paginator::fromQueryBuilder($qb, $request);
        $paginator->setPostprocessor([ApiAuditLog::class, 'fromRow']);

        return $paginator->write($response);
    }
}
