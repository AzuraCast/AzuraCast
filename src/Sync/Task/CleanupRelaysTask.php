<?php

declare(strict_types=1);

namespace App\Sync\Task;

class CleanupRelaysTask extends AbstractTask
{
    public function run(bool $force = false): void
    {
        // Relays should update every 15 seconds, so be fairly aggressive with this.
        $threshold = time() - 90;

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\Relay r WHERE r.updated_at < :threshold
            DQL
        )->setParameter('threshold', $threshold)
            ->execute();
    }
}
