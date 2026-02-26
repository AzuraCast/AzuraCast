<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity\Interfaces\EntityGroupsInterface;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute as Serializer;

#[OA\Schema(
    required: ['id'],
    properties: [
        // Defined here to enforce nullable false
        new OA\Property(
            property: 'id',
            type: 'integer',
            readOnly: true
        ),
    ],
    type: 'object',
)]
trait HasAutoIncrementId
{
    #[
        ORM\Column(name: 'id', nullable: false),
        ORM\Id,
        ORM\GeneratedValue,
        Serializer\Groups([EntityGroupsInterface::GROUP_ID, EntityGroupsInterface::GROUP_ALL])
    ]
    public protected(set) int $id;
}
