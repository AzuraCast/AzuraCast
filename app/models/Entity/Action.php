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

use App\Doctrine\Repository;

class ActionRepository extends Repository
{
    public function getUsersWithAction($action_name)
    {
        return $this->_em->createQuery('SELECT u FROM Entity\User u LEFT JOIN u.roles r LEFT JOIN r.actions a WHERE (a.name = :action OR a.name = :admin_action)')
            ->setParameter('action', $action_name)
            ->setParameter('admin_action', 'administer all')
            ->getArrayResult();
    }
}