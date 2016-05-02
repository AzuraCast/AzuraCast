<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="api_keys")
 * @Entity
 * @HasLifecycleCallbacks
 */
class ApiKey extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->calls_made = 0;
        $this->created = time();
    }

    /** @PrePersist */
    public function preSave()
    {
        if (!$this->id)
            $this->id = sha1(mt_rand(0, microtime(true)));
    }

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

    /* Static Functions */

    /**
     * Authenticate a supplied API key.
     *
     * @param $key
     * @return bool
     */
    public static function authenticate($key)
    {
        if (empty($key))
            return false;

        $record = self::find($key);
        return ($record instanceof self);
    }
}