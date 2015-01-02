<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_managers")
 * @Entity
 */
class StationManager extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->timestamp = time();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="email", type="string", length=100) */
    protected $email;

    public function setEmail($new_email)
    {
        $this->email = trim(strtolower($new_email));
    }

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="managers")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;
}