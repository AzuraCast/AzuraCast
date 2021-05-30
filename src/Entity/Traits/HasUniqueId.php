<?php

namespace App\Entity\Traits;

use App\Doctrine\Generator\UuidV6Generator;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object")
 */
trait HasUniqueId
{
    /** @OA\Property() */
    #[ORM\Column(type: 'guid', unique: true)]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'CUSTOM'), ORM\CustomIdGenerator(UuidV6Generator::class)]
    protected string $id;

    public function getId(): ?string
    {
        return $this->id ?? null;
    }
}
