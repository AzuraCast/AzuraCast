<?php
namespace App\Entity\Traits;

/**
 * @HasLifecycleCallbacks
 */
trait UniqueId
{

    /**
     * @Column(name="unique_id", type="string", length=25, nullable=true)
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
     * @PrePersist
     */
    public function generateUniqueId()
    {
        $this->unique_id = bin2hex(random_bytes(12));
    }
}
