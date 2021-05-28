<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object")
 */
trait HasUniqueId
{
    /** @OA\Property() */
    #[ORM\Column(type: 'guid', unique: true)]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'UUID')]
    protected string $id;

    public function getId(): ?string
    {
        return $this->id ?? null;
    }
}
