<?php

namespace App\Sync\Task;

class RelayCleanup extends AbstractTask
{
    public function run(bool $force = false): void
    {
        // Relays should update every 15 seconds, so be fairly aggressive with this.
        $threshold = time() - 90;

        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\Relay r WHERE r.updated_at < :threshold')
            ->setParameter('threshold', $threshold)
            ->execute();
    }
}
