<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class MessengerMessageRepository extends Repository
{
    public function clearQueue(?string $queueName = null): void
    {
        $qb = $this->em->createQueryBuilder()
            ->delete(Entity\MessengerMessage::class, 'mm');

        if (!empty($queueName)) {
            $qb->where('mm.queueName = :queueName')
                ->setParameter('queueName', $queueName);
        }

        $qb->getQuery()->execute();
    }
}
