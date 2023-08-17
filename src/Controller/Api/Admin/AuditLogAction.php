<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\AcceptsDateRange;
use App\Entity\AuditLog;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Psr\Http\Message\ResponseInterface;

use const JSON_PRETTY_PRINT;

final class AuditLogAction
{
    use AcceptsDateRange;
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $dateRange = $this->getDateRange($request);
        $start = $dateRange->getStart();
        $end = $dateRange->getEnd();

        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from(AuditLog::class, 'a')
            ->andWhere('a.timestamp >= :start AND a.timestamp <= :end')
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        $searchPhrase = trim($request->getQueryParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $qb->andWhere('(a.user LIKE :query OR a.identifier LIKE :query OR a.target LIKE :query)')
                ->setParameter('query', '%' . $searchPhrase . '%');
        }

        $qb->orderBy('a.timestamp', 'DESC');

        $paginator = Paginator::fromQueryBuilder($qb, $request);

        $paginator->setPostprocessor(
            function (AuditLog $row) {
                $changesRaw = $row->getChanges();
                $changes = [];

                foreach ($changesRaw as $fieldName => [$fieldPrevious, $fieldNew]) {
                    $changes[] = [
                        'field' => $fieldName,
                        'from' => json_encode(
                            $fieldPrevious,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                        ),
                        'to' => json_encode(
                            $fieldNew,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                        ),
                    ];
                }

                $operation = $row->getOperation();

                return [
                    'id' => $row->getId(),
                    'timestamp' => $row->getTimestamp(),
                    'operation' => $operation->value,
                    'operation_text' => $operation->getName(),
                    'class' => $row->getClass(),
                    'identifier' => $row->getIdentifier(),
                    'target_class' => $row->getTargetClass(),
                    'target' => $row->getTarget(),
                    'user' => $row->getUser(),
                    'changes' => $changes,
                ];
            }
        );

        return $paginator->write($response);
    }
}
