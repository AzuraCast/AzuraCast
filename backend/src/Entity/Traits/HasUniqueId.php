<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Doctrine\Generator\UuidV6Generator;
use App\Entity\Interfaces\EntityGroupsInterface;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute as Serializer;

#[
    OA\Schema(
        required: ['id'],
        properties: [
            // Defined here to enforce nullable false
            new OA\Property(
                property: 'id',
                type: 'string',
                readOnly: true
            ),
        ],
        type: 'object'
    )
]
trait HasUniqueId
{
    #[
        ORM\Column(name: 'id', type: 'guid', unique: true, nullable: false),
        ORM\Id, ORM\GeneratedValue(strategy: 'CUSTOM'), ORM\CustomIdGenerator(UuidV6Generator::class),
        Serializer\Groups([EntityGroupsInterface::GROUP_ID, EntityGroupsInterface::GROUP_ALL])
    ]
    public protected(set) string $id;
}
