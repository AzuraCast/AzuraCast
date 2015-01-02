<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="role")
 * @Entity
 */
class Role extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->users = new ArrayCollection;
        $this->actions = new ArrayCollection;
    }
    
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="name", type="string", length=100) */
    protected $name;

    /** @ManyToMany(targetEntity="User", mappedBy="roles")*/
    protected $users;
    
    /**
     * @ManyToMany(targetEntity="Action", inversedBy="roles")
     * @JoinTable(name="role_has_action",
     *      joinColumns={@JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="action_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $actions;
    
    public static function fetchSelect($add_blank = FALSE)
    {
        $roles_raw = self::getRepository()->findAll();
        $roles = array();
        
        if ($add_blank)
            $roles[0] = 'N/A';
        
        foreach((array)$roles_raw as $role)
        {
            $roles[$role['id']] = $role['name'];
        }
        return $roles;
    }
}