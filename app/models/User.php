<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Table(name="users")
 * @Entity(repositoryClass="Entity\Repository\UserRepository")
 * @HasLifecycleCallbacks
 */
class User
{
    /**
     * @Column(name="uid", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="email", type="string", length=100, nullable=true)
     * @var string|null
     */
    protected $email;

    /**
     * @Column(name="auth_password", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $auth_password;

    /**
     * @Column(name="name", type="string", length=100, nullable=true)
     * @var string|null
     */
    protected $name;

    /**
     * @Column(name="timezone", type="string", length=100, nullable=true)
     * @var string|null
     */
    protected $timezone;

    /**
     * @Column(name="locale", type="string", length=25, nullable=true)
     * @var string|null
     */
    protected $locale;

    /**
     * @Column(name="theme", type="string", length=25, nullable=true)
     * @var string|null
     */
    protected $theme;

    /**
     * @Column(name="created_at", type="integer")
     * @var int
     */
    protected $created_at;

    /**
     * @Column(name="updated_at", type="integer")
     * @var int
     */
    protected $updated_at;

    /**
     * @ManyToMany(targetEntity="Role", inversedBy="users")
     * @JoinTable(name="user_has_role",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @var Collection
     */
    protected $roles;

    public function __construct()
    {
        $this->created_at = time();
        $this->updated_at = time();

        $this->roles = new ArrayCollection;
    }

    /**
     * @PrePersist
     */
    public function preSave()
    {
        $this->updated_at = time();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param null|string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getAuthPassword(): string
    {
        return '';
    }

    /**
     * @param string $password
     */
    public function setAuthPassword(string $password)
    {
        if (trim($password)) {
            $this->auth_password = password_hash($password, \PASSWORD_DEFAULT);
        }
    }

    /**
     * @param $password
     * @return bool
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->auth_password);
    }

    public function generateRandomPassword()
    {
        $this->setAuthPassword(bin2hex(random_bytes(20)));
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param null|string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @return null|string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param null|string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return null|string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * @param null|string $theme
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    /**
     * @return int
     */
    public function getCreatedAt(): int
    {
        return $this->created_at;
    }

    /**
     * @return int
     */
    public function getUpdatedAt(): int
    {
        return $this->updated_at;
    }

    /**
     * @return Collection
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function getAvatar($size = 50)
    {
        return \App\Service\Gravatar::get($this->email, $size, 'identicon');
    }
}