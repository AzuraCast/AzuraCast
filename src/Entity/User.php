<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="email_idx", columns={"email"})})
 * @ORM\Entity(repositoryClass="App\Entity\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @OA\Schema(type="object")
 */
class User
{
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="uid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @OA\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="demo@azuracast.com")
     * @var string|null
     *
     * @Assert\NotBlank
     */
    protected $email;

    /**
     * @ORM\Column(name="auth_password", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="")
     * @var string|null
     */
    protected $auth_password;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="Demo Account")
     * @var string|null
     */
    protected $name;

    /**
     * @ORM\Column(name="timezone", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="America/Chicago")
     * @var string|null
     */
    protected $timezone;

    /**
     * @ORM\Column(name="locale", type="string", length=25, nullable=true)
     *
     * @OA\Property(example="en_US")
     * @var string|null
     */
    protected $locale;

    /**
     * @ORM\Column(name="theme", type="string", length=25, nullable=true)
     *
     * @OA\Property(example="dark")
     * @var string|null
     */
    protected $theme;

    /**
     * @ORM\Column(name="created_at", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    protected $created_at;

    /**
     * @ORM\Column(name="updated_at", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    protected $updated_at;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users", fetch="EAGER")
     * @ORM\JoinTable(name="user_has_role",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     *
     * @Serializer\MaxDepth(1)
     * @OA\Property(
     *     @OA\Items()
     * )
     *
     * @var Collection
     */
    protected $roles;

    /**
     * @ORM\OneToMany(targetEntity="ApiKey", mappedBy="user")
     * @var Collection
     */
    protected $api_keys;

    public function __construct()
    {
        $this->created_at = time();
        $this->updated_at = time();

        $this->roles = new ArrayCollection;
        $this->api_keys = new ArrayCollection;
    }

    /**
     * @ORM\PrePersist
     */
    public function preSave(): void
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
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param null|string $email
     */
    public function setEmail($email): void
    {
        $this->email = $this->_truncateString($email, 100);
    }

    /**
     * @param string $password
     */
    public function setAuthPassword(string $password): void
    {
        if (trim($password)) {
            [$algo, $algo_opts] = $this->_getPasswordAlgorithm();
            $this->auth_password = password_hash($password, $algo, $algo_opts);
        }
    }

    /**
     * @param string $password
     * @return bool
     */
    public function verifyPassword($password): bool
    {
        if (password_verify($password, $this->auth_password)) {
            [$algo, $algo_opts] = $this->_getPasswordAlgorithm();

            if (password_needs_rehash($this->auth_password, $algo, $algo_opts)) {
                $this->setAuthPassword($password);
            }
            return true;
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function generateRandomPassword(): void
    {
        $this->setAuthPassword(bin2hex(random_bytes(20)));
    }

    /**
     * Get the most secure available password hashing algorithm.
     *
     * @return array [algorithm constant, algorithm options array]
     */
    protected function _getPasswordAlgorithm(): array
    {
        if (defined('PASSWORD_ARGON2I')) {
            return [\PASSWORD_ARGON2I, []];
        }

        return [\PASSWORD_BCRYPT, []];
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     */
    public function setName($name): void
    {
        $this->name = $this->_truncateString($name, 100);
    }

    /**
     * @return null|string
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * @param null|string $timezone
     */
    public function setTimezone($timezone): void
    {
        $this->timezone = $timezone;
    }

    /**
     * @return null|string
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param null|string $locale
     */
    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return null|string
     */
    public function getTheme(): ?string
    {
        return $this->theme;
    }

    /**
     * @param null|string $theme
     */
    public function setTheme($theme): void
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

    /**
     * @return Collection
     */
    public function getApiKeys(): Collection
    {
        return $this->api_keys;
    }

    /**
     * @param int $size
     * @return string
     */
    public function getAvatar($size = 50): string
    {
        return \App\Service\Gravatar::get($this->email, $size, 'identicon');
    }
}
