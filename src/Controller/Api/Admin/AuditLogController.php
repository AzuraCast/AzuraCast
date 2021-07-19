<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

use const JSON_PRETTY_PRINT;

class AuditLogController
{
    public function __construct(
        protected EntityManagerInterface $em
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $tz = new DateTimeZone('UTC');

        $params = $request->getParams();
        if (!empty($params['start'])) {
            $start = CarbonImmutable::parse($params['start'] . ' 00:00:00', $tz);
            $end = CarbonImmutable::parse(($params['end'] ?? $params['start']) . ' 23:59:59', $tz);
        } else {
            $start = CarbonImmutable::parse('-2 weeks', $tz);
            $end = CarbonImmutable::now($tz);
        }

        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from(Entity\AuditLog::class, 'a')
            ->andWhere('a.timestamp >= :start AND a.timestamp <= :end')
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        $search_phrase = trim($params['searchPhrase'] ?? '');
        if (!empty($search_phrase)) {
            $qb->andWhere('(a.user LIKE :query OR a.identifier LIKE :query OR a.target LIKE :query)')
                ->setParameter('query', '%' . $search_phrase . '%');
        }

        $qb->orderBy('a.timestamp', 'DESC');

        $paginator = Paginator::fromQueryBuilder($qb, $request);

        $paginator->setPostprocessor(
            function (Entity\AuditLog $row) {
                $operations = [
                    Entity\AuditLog::OPER_UPDATE => 'update',
                    Entity\AuditLog::OPER_DELETE => 'delete',
                    Entity\AuditLog::OPER_INSERT => 'insert',
                ];

                $changesRaw = $row->getChanges();
                $changes = [];

                foreach ($changesRaw as $fieldName => [$fieldPrevious, $fieldNew]) {
                    $changes[] = [
                        'field' => $fieldName,
                        'from' => json_encode($fieldPrevious, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
                        'to' => json_encode($fieldNew, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
                    ];
                }

                return [
                    'id' => $row->getId(),
                    'timestamp' => $row->getTimestamp(),
                    'operation' => $row->getOperation(),
                    'operation_text' => $operations[$row->getOperation()],
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
