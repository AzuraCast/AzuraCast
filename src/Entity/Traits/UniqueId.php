<?php
namespace App\Entity\Traits;

trait UniqueId
{
    /**
     * @ORM\Column(name="unique_id", type="string", length=25, nullable=true)
     * @var string
     */
    protected $unique_id;

    /**
     * @return mixed
     */
    public function getUniqueId()
    {
        return $this->unique_id;
    }

    /**
     * Generate a new unique ID for this item.
     */
    public function generateUniqueId($force_new = false)
    {
        if (empty($this->unique_id) || $force_new) {
            $this->unique_id = bin2hex(random_bytes(12));
        }
    }
}
