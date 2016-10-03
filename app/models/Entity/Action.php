<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="action")
 * @Entity(repositoryClass="ActionRepository")
 */
class Action extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->is_global = false;
        $this->has_role = new ArrayCollection;
    }
    
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;

    /** @Column(name="is_global", type="boolean") */
    protected $is_global;
    
    /** @OneToMany(targetEntity="Entity\RoleHasAction", mappedBy="action") */
    protected $has_role;
}

use App\Doctrine\Repository;

class ActionRepository extends Repository
{

}