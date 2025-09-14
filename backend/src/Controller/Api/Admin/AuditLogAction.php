<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\AcceptsDateRange;
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
        summary: 'List all Audit Log actions that have taken place on the installation.',
        tags: [OpenApi::TAG_ADMIN],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: AuditLog::class
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
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
        return $paginator->write($response);
    }
}
