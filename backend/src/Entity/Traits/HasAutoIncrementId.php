<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity\Interfaces\EntityGroupsInterface;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\Groups;

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
        ORM\Column(nullable: false),
        ORM\Id,
        ORM\GeneratedValue,
        Groups([EntityGroupsInterface::GROUP_ID, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdRequired(): int
    {
        if (null === $this->id) {
            throw new RuntimeException('An ID was not generated for this object.');
        }

        return $this->id;
    }
}
