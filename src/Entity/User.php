<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Auth;
use App\Normalizer\Annotation\DeepNormalize;
use App\Service\Gravatar;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use OTPHP\Factory;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

use const PASSWORD_BCRYPT;

/**
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="email_idx", columns={"email"})})
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 *
 * @AuditLog\Auditable
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
     * @AuditLog\AuditIgnore()
     * @var string|null
     */
    protected $auth_password;

    /**
     * @OA\Property(example="")
     * @var string|null
     */
    protected $new_password;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="Demo Account")
     * @var string|null
     */
    protected $name;

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
     * @AuditLog\AuditIgnore()
     *
     * @OA\Property(example="dark")
     * @var string|null
     */
    protected $theme;

    /**
     * @ORM\Column(name="two_factor_secret", type="string", length=255, nullable=true)
     *
     * @AuditLog\AuditIgnore()
     *
     * @OA\Property(example="A1B2C3D4")
     * @var string|null
     */
    protected $two_factor_secret;

    /**
     * @ORM\Column(name="created_at", type="integer")
     *
     * @AuditLog\AuditIgnore()
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    protected $created_at;

    /**
     * @ORM\Column(name="updated_at", type="integer")
     *
     * @AuditLog\AuditIgnore()
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
     * @DeepNormalize(true)
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
     * @DeepNormalize(true)
     * @var Collection
     */
    protected $api_keys;

    public function __construct()
    {
        $this->created_at = time();
        $this->updated_at = time();

        $this->roles = new ArrayCollection();
        $this->api_keys = new ArrayCollection();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updated_at = time();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @AuditLog\AuditIdentifier()
     */
    public function getIdentifier(): string
    {
        return $this->getName() . ' (' . $this->getEmail() . ')';
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name = null): void
    {
        $this->name = $this->truncateString($name, 100);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email = null): void
    {
        $this->email = $this->truncateString($email, 100);
    }

    public function verifyPassword(string $password): bool
    {
        if (password_verify($password, $this->auth_password)) {
            [$algo, $algo_opts] = $this->getPasswordAlgorithm();

            if (password_needs_rehash($this->auth_password, $algo, $algo_opts)) {
                $this->setNewPassword($password);
            }
            return true;
        }

        return false;
    }

    /**
     * Get the most secure available password hashing algorithm.
     *
     * @return mixed[] [algorithm constant string, algorithm options array]
     */
    protected function getPasswordAlgorithm(): array
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return [PASSWORD_ARGON2ID, []];
        }

        return [PASSWORD_BCRYPT, []];
    }

    public function setNewPassword(string $password): void
    {
        if (trim($password)) {
            [$algo, $algo_opts] = $this->getPasswordAlgorithm();
            $this->auth_password = password_hash($password, $algo, $algo_opts);
        }
    }

    public function generateRandomPassword(): void
    {
        $this->setNewPassword(bin2hex(random_bytes(20)));
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale = null): void
    {
        $this->locale = $locale;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme = null): void
    {
        $this->theme = $theme;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->two_factor_secret;
    }

    public function setTwoFactorSecret(?string $two_factor_secret = null): void
    {
        $this->two_factor_secret = $two_factor_secret;
    }

    public function verifyTwoFactor(string $otp): bool
    {
        if (null === $this->two_factor_secret) {
            return true;
        }

        $totp = Factory::loadFromProvisioningUri($this->two_factor_secret);
        return $totp->verify($otp, null, Auth::TOTP_WINDOW);
    }

    public function getCreatedAt(): int
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): int
    {
        return $this->updated_at;
    }

    /**
     * @return Collection|Role[]
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * @return Collection|ApiKey[]
     */
    public function getApiKeys(): Collection
    {
        return $this->api_keys;
    }

    public function getAvatar(int $size = 50): string
    {
        return Gravatar::get($this->email, $size, 'https://www.azuracast.com/img/avatar.png');
    }
}
