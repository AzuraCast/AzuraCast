<?php
namespace App\Controller\Api\Admin;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Doctrine\Paginator;
use Cake\Chronos\Chronos;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use const JSON_PRETTY_PRINT;

class AuditLogController
{
    protected EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $tz = new DateTimeZone('UTC');

        $params = $request->getParams();
        if (!empty($params['start'])) {
            $start = Chronos::parse($params['start'] . ' 00:00:00', $tz);
            $end = Chronos::parse(($params['end'] ?? $params['start']) . ' 23:59:59', $tz);
        } else {
            $start = Chronos::parse('-2 weeks', $tz);
            $end = Chronos::now($tz);
        }

        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from(Entity\AuditLog::class, 'a')
            ->andWhere('a.timestamp >= :start AND a.timestamp <= :end')
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        $search_phrase = trim($params['searchPhrase']);
        if (!empty($search_phrase)) {
            $qb->andWhere('(a.user LIKE :query OR a.identifier LIKE :query OR a.target LIKE :query)')
                ->setParameter('query', '%' . $search_phrase . '%');
        }

        $qb->orderBy('a.timestamp', 'DESC');

        $paginator = new Paginator($qb);
        $paginator->setFromRequest($request);

        $paginator->setPostprocessor(function (Entity\AuditLog $row) {
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
                    'from' => json_encode($fieldPrevious, JSON_PRETTY_PRINT),
                    'to' => json_encode($fieldNew, JSON_PRETTY_PRINT),
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
        });

        return $paginator->write($response);
    }
}
