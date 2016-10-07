<?php
namespace Entity;

use \Doctrine\ORM\Mapping as ORM;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="users")
 * @Entity(repositoryClass="UserRepository")
 * @HasLifecycleCallbacks
 */
class User extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->roles = new ArrayCollection;

        $this->created_at = time();
        $this->updated_at = time();
    }

    /**
     * @PrePersist
     */
    public function preSave()
    {
        $this->updated_at = time();
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
        $this->setAuthPassword(md5('APP_EXTERNAL_'.mt_rand()));
    }

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;

    /** @Column(name="timezone", type="string", length=100, nullable=true) */
    protected $timezone;

    /** @Column(name="locale", type="string", length=25, nullable=true) */
    protected $locale;

    /** @Column(name="theme", type="string", length=25, nullable=true) */
    protected $theme;

    /** @Column(name="created_at", type="integer") */
    protected $created_at;

    /** @Column(name="updated_at", type="integer") */
    protected $updated_at;

    /**
     * @ManyToMany(targetEntity="Role", inversedBy="users")
     * @JoinTable(name="user_has_role",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $roles;
}

use App\Doctrine\Repository;

class UserRepository extends Repository
{
    /**
     * @param $username
     * @param $password
     * @return bool|null|object
     */
    public function authenticate($username, $password)
    {
        $login_info = $this->findOneBy(['email' => $username]);

        if (!($login_info instanceof User))
            return FALSE;

        if ($login_info->verifyPassword($password))
            return $login_info;
        else
            return FALSE;
    }

    /**
     * Creates or returns an existing user with the specified e-mail address.
     *
     * @param $email
     * @return User
     */
    public function getOrCreate($email)
    {
        $user = $this->findOneBy(['email' => $email]);

        if (!($user instanceof User))
        {
            $user = new User;
            $user->email = $email;
            $user->name = $email;
        }

        return $user;
    }
}