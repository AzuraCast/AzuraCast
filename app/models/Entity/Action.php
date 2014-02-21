<?php
namespace Entity;

/**
 * @Table(name="action")
 * @Entity
 */
class Action extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;
    
    /** @ManyToMany(targetEntity="Entity\Role", mappedBy="actions") */
    protected $roles;
}