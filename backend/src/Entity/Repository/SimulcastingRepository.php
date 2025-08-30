<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Simulcasting;
use App\Entity\Station;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Simulcasting>
 */
class SimulcastingRepository extends EntityRepository
{
    /**
     * @return Simulcasting[]
     */
    public function findByStation(Station $station): array
    {
        return $this->findBy(['station' => $station], ['name' => 'ASC']);
    }

    /**
     * @return Simulcasting[]
     */
    public function findActiveByStation(Station $station): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.station = :station')
            ->andWhere('s.status IN (:activeStatuses)')
            ->setParameter('station', $station)
            ->setParameter('activeStatuses', ['running', 'starting', 'stopping'])
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Simulcasting[]
     */
    public function findWithErrors(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', 'error')
            ->orderBy('s.station', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function clearErrors(Station $station): void
    {
        $this->createQueryBuilder('s')
            ->update(Simulcasting::class, 's')
            ->set('s.status', ':status')
            ->set('s.error_message', ':errorMessage')
            ->where('s.station = :station')
            ->andWhere('s.status = :errorStatus')
            ->setParameter('status', 'stopped')
            ->setParameter('errorMessage', null)
            ->setParameter('station', $station)
            ->setParameter('errorStatus', 'error')
            ->getQuery()
            ->execute();
    }
}

