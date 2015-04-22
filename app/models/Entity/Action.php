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

    public function getUsers()
    {
        return self::getUsersWithAction($this->name);
    }

    public static function getUsersWithAction($action_name)
    {
        $em = self::getEntityManager();

        return $em->createQuery('SELECT u FROM Entity\Role r LEFT JOIN r.actions a LEFT JOIN r.users u WHERE (a.name = :action OR a.name = :admin_action)')
            ->setParameter('action', $action_name)
            ->setParameter('admin_action', 'administer all')
            ->getArrayResult();
    }
}