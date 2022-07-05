<?php

declare(strict_types=1);

namespace App\Doctrine\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

final class SetExplicitChangeTracking implements EventSubscriber
{
    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $classMetadata = $args->getClassMetadata();
        $classMetadata->setChangeTrackingPolicy(
            ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT
        );
    }
}
