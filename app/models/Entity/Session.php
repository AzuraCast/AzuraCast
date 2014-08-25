<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="sessions", indexes={
 *   @index(name="cleanup_idx", columns={"expires"})
 * })
 * @Entity
 */
class Session extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->last_modified = time();
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * @Column(name="id", type="string", length=128)
     * @Id
     */
    protected $id;

    /** @Column(name="user_agent", type="string", length=255) */
    protected $user_agent;

    /** @Column(name="expires", type="integer") */
    protected $expires;

    public function setLifetime($lifetime)
    {
        $this->expires = time()+$lifetime;
    }

    /** @Column(name="last_modified", type="integer") */
    protected $last_modified;

    /** @Column(name="data", type="text", nullable=true) */
    protected $data;

    /*
     * Validate session.
     * @return boolean
     */
    public function isValid()
    {
        if ($this->expires < time())
            return false;

        $current_ua = $_SERVER['HTTP_USER_AGENT'];
        if (strcmp($this->user_agent, $current_ua) !== 0)
            return false;

        return true;
    }
}