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
    #[ORM\Column(nullable: false)]
    #[ORM\Id, ORM\GeneratedValue]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
