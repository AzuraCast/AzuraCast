<?php
namespace Entity;

use \Doctrine\ORM\Mapping as ORM;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="users")
 * @Entity(repositoryClass="Repository\UserRepository")
 */
class User extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->roles = new ArrayCollection;
        $this->stations = new ArrayCollection;

        $this->time_created = time();
        $this->time_updated = time();
    }

    /**
     * @Column(name="uid", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="email", type="string", length=100, nullable=true) */
    protected $email;

    public function getAvatar($size = 50)
    {
        return \App\Service\Gravatar::get($this->email, $size, 'identicon');
    }

    /** @Column(name="auth_password", type="string", length=255, nullable=true) */
    protected $auth_password;

    public function verifyPassword($password)
    {
        return password_verify($password, $this->auth_password);
    }

    public function getAuthPassword()
    {
        return '';
    }

    public function setAuthPassword($password)
    {
        if (trim($password))
            $this->auth_password = password_hash($password, \PASSWORD_DEFAULT);

        return $this;
    }

    public function generateRandomPassword()
    {
        $this->setAuthPassword(md5('APP_EXTERNAL_'.mt_rand(0, 10000)));
    }

    /** @Column(name="auth_last_login_time", type="integer", nullable=true) */
    protected $auth_last_login_time;

    /** @Column(name="auth_recovery_code", type="string", length=50, nullable=true) */
    protected $auth_recovery_code;

    public function generateAuthRecoveryCode()
    {
        $this->auth_recovery_code = sha1(mt_rand());
        return $this->auth_recovery_code;
    }

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;
    
    /** @Column(name="gender", type="string", length=1, nullable=true) */
    protected $gender;

    /** @Column(name="customization", type="json", nullable=true) */
    protected $customization;

    /**
     * @ManyToMany(targetEntity="Role", inversedBy="users")
     * @JoinTable(name="user_has_role",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $roles;

    /**
     * @ManyToMany(targetEntity="Station", inversedBy="users")
     * @JoinTable(name="user_manages_station",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $stations;
}
