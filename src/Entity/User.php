<?php

declare(strict_types=1);

namespace App\Entity;

use App\Auth;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Normalizer\Attributes\DeepNormalize;
use App\OpenApi;
use App\Utilities\Strings;
use App\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use OTPHP\Factory;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

use const PASSWORD_BCRYPT;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'users'),
    ORM\HasLifecycleCallbacks,
    ORM\UniqueConstraint(name: 'email_idx', columns: ['email']),
    Attributes\Auditable,
    UniqueEntity(fields: ['email'])
]
class User implements Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        OA\Property(example: "demo@azuracast.com"),
        ORM\Column(length: 100, nullable: false),
        Assert\NotBlank,
        Assert\Email,
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected string $email;

    #[
        ORM\Column(length: 255, nullable: false),
        Attributes\AuditIgnore
    ]
    protected string $auth_password = '';

    #[
        OA\Property(example: ""),
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $new_password = null;

    #[
        OA\Property(example: "Demo Account"),
        ORM\Column(length: 100, nullable: true),
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $name = null;

    #[
        OA\Property(example: "en_US"),
        ORM\Column(length: 25, nullable: true),
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $locale = null;

    #[
        OA\Property(example: true),
        ORM\Column(nullable: true),
        Attributes\AuditIgnore,
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?bool $show_24_hour_time = null;

    #[
        OA\Property(example: "A1B2C3D4"),
        ORM\Column(length: 255, nullable: true),
        Attributes\AuditIgnore,
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $two_factor_secret = null;

    #[
        OA\Property(example: OpenApi::SAMPLE_TIMESTAMP),
        ORM\Column,
        Attributes\AuditIgnore,
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    protected int $created_at;

    #[
        OA\Property(example: OpenApi::SAMPLE_TIMESTAMP),
        ORM\Column,
        Attributes\AuditIgnore,
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    protected int $updated_at;

    /** @var Collection<int, Role> */
    #[
        OA\Property(type: "array", items: new OA\Items()),
        ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users', fetch: 'EAGER'),
        ORM\JoinTable(name: 'user_has_role'),
        ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE'),
        ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id', onDelete: 'CASCADE'),
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL]),
        DeepNormalize(true),
        Serializer\MaxDepth(1)
    ]
    protected Collection $roles;

    /** @var Collection<int, ApiKey> */
    #[
        ORM\OneToMany(mappedBy: 'user', targetEntity: ApiKey::class),
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL]),
        DeepNormalize(true)
    ]
    protected Collection $api_keys;

    public function __construct()
    {
        $this->created_at = time();
        $this->updated_at = time();

        $this->roles = new ArrayCollection();
        $this->api_keys = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updated_at = time();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDisplayName(): string
    {
        return $this->name ?? $this->email;
    }

    public function setName(?string $name = null): void
    {
        $this->name = $this->truncateNullableString($name, 100);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $this->truncateString($email, 100);
    }

    public function verifyPassword(string $password): bool
    {
        if (password_verify($password, $this->auth_password)) {
            [$algo, $algoOpts] = $this->getPasswordAlgorithm();

            if (password_needs_rehash($this->auth_password, $algo, $algoOpts)) {
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

    public function setNewPassword(?string $password): void
    {
        if (null !== $password && trim($password)) {
            [$algo, $algoOpts] = $this->getPasswordAlgorithm();
            $this->auth_password = password_hash($password, $algo, $algoOpts);
        }
    }

    public function generateRandomPassword(): void
    {
        $this->setNewPassword(Strings::generatePassword());
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale = null): void
    {
        $this->locale = $locale;
    }

    public function getShow24HourTime(): ?bool
    {
        return $this->show_24_hour_time;
    }

    public function setShow24HourTime(?bool $show24HourTime): void
    {
        $this->show_24_hour_time = $show24HourTime;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->two_factor_secret;
    }

    public function setTwoFactorSecret(?string $twoFactorSecret = null): void
    {
        $this->two_factor_secret = $twoFactorSecret;
    }

    public function verifyTwoFactor(string $otp): bool
    {
        if (empty($this->two_factor_secret)) {
            return true;
        }
        if (empty($otp)) {
            return false;
        }

        return Factory::loadFromProvisioningUri($this->two_factor_secret)->verify($otp, null, Auth::TOTP_WINDOW);
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
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * @return Collection<int, ApiKey>
     */
    public function getApiKeys(): Collection
    {
        return $this->api_keys;
    }

    public function __toString(): string
    {
        return $this->getName() . ' (' . $this->getEmail() . ')';
    }
}
