<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object")
 */
trait HasAutoIncrementId
{
    /** @OA\Property() */
    #[ORM\Column, ORM\Id, ORM\GeneratedValue]
    protected int $id;

    public function getId(): ?int
    {
        return $this->id ?? null;
    }
}
