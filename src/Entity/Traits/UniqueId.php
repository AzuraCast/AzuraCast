<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object")
 */
trait UniqueId
{
    /**
     * @OA\Property(
     *     description="A unique identifier associated with this record."
     *     example="69b536afc7ebbf16457b8645"
     * )
     */
    #[ORM\Column(length: 25)]
    protected ?string $unique_id;

    public function getUniqueId(): string
    {
        if (!isset($this->unique_id)) {
            throw new \RuntimeException('Unique ID has not been generated yet.');
        }

        return $this->unique_id;
    }

    /**
     * Generate a new unique ID for this item.
     *
     * @param bool $force_new
     *
     * @throws Exception
     */
    public function generateUniqueId($force_new = false): void
    {
        if (!isset($this->unique_id) || $force_new) {
            $this->unique_id = bin2hex(random_bytes(12));
        }
    }
}
