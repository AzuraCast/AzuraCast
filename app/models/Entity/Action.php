<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="action")
 * @Entity(repositoryClass="Repository\ActionRepository")
 */
class Action extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->roles = new ArrayCollection();
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