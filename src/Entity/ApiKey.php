<?php
namespace Entity;

/**
 * @Table(name="api_keys")
 * @Entity
 * @HasLifecycleCallbacks
 */
class ApiKey extends \App\Doctrine\Entity
{
    /**
     * @Column(name="id", type="string", length=50)
     * @Id
     */
    protected $id;

    /** @Column(name="owner", type="string", length=150, nullable=true) */
    protected $owner;

    /** @Column(name="calls_made", type="integer") */
    protected $calls_made;

    /** @Column(name="created", type="integer") */
    protected $created;

    public function __construct()
    {
        $this->calls_made = 0;
        $this->created = time();
    }

    /** @PrePersist */
    public function preSave()
    {
        if (!$this->id) {
            $this->id = sha1(mt_rand(0, microtime(true)));
        }
    }
}