<?php

declare(strict_types=1);

namespace App\Entity;

use App\Auth;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\OpenApi;
use App\Utilities\Strings;
use App\Validator\Constraints\UniqueEntity;
use Azura\Normalizer\Attributes\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use OTPHP\Factory;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'users'),
    ORM\HasLifecycleCallbacks,
    ORM\UniqueConstraint(name: 'email_idx', columns: ['email']),
    Attributes\Auditable,
    UniqueEntity(fields: ['email'])
]
final class User implements Stringable, IdentifiableEntityInterface
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
    public string $email;

    #[
        ORM\Column(length: 255, nullable: false),
        Attributes\AuditIgnore
    ]
    public string $auth_password = '';

    #[
        OA\Property(example: ""),
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $new_password = null;

    #[
        OA\Property(example: "Demo Account"),
        ORM\Column(length: 100, nullable: true),
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $name = null {
        set => $this->truncateNullableString($value, 100);
    }

    #[
        OA\Property(example: "en_US"),
        ORM\Column(length: 25, nullable: true),
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $locale = null {
        set => $this->truncateNullableString($value, 25);
    }

    #[
        OA\Property(example: true),
        ORM\Column(nullable: true),
        Attributes\AuditIgnore,
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?bool $show_24_hour_time = null;

    #[
        OA\Property(example: "A1B2C3D4"),
        ORM\Column(length: 255, nullable: true),
        Attributes\AuditIgnore,
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $two_factor_secret = null {
        set => $this->truncateNullableString($value);
    }

    #[
        OA\Property(example: OpenApi::SAMPLE_TIMESTAMP),
        ORM\Column,
        Attributes\AuditIgnore,
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public readonly int $created_at;

    #[
        OA\Property(example: OpenApi::SAMPLE_TIMESTAMP),
        ORM\Column,
        Attributes\AuditIgnore,
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])
    ]
    public int $updated_at;

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
    public private(set) Collection $roles;

    /** @var Collection<int, ApiKey> */
    #[
        ORM\OneToMany(targetEntity: ApiKey::class, mappedBy: 'user'),
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL]),
        DeepNormalize(true)
    ]
    public private(set) Collection $api_keys;

    /** @var Collection<int, UserPasskey> */
    #[
        ORM\OneToMany(targetEntity: UserPasskey::class, mappedBy: 'user'),
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL]),
        DeepNormalize(true)
    ]
    public private(set) Collection $passkeys;

    /** @var Collection<int, UserLoginToken> */
    #[
        ORM\OneToMany(targetEntity: UserLoginToken::class, mappedBy: 'user'),
        Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL]),
        DeepNormalize(true)
    ]
    public private(set) Collection $login_tokens;

    public function __construct()
    {
        $this->created_at = time();
        $this->updated_at = time();

        $this->roles = new ArrayCollection();
        $this->api_keys = new ArrayCollection();
        $this->passkeys = new ArrayCollection();
        $this->login_tokens = new ArrayCollection();
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

    public function verifyPassword(string $password): bool
    {
        if (password_verify($password, $this->auth_password)) {
            if (password_needs_rehash($this->auth_password, PASSWORD_ARGON2ID)) {
                $this->setNewPassword($password);
            }
            return true;
        }

        return false;
    }

    public function setNewPassword(?string $password): void
    {
        if (null !== $password && trim($password)) {
            $this->auth_password = password_hash($password, PASSWORD_ARGON2ID);
        }
    }

    public function generateRandomPassword(): void
    {
        $this->setNewPassword(Strings::generatePassword());
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

    public function __clone(): void
    {
        $this->roles = new ArrayCollection();
        $this->api_keys = new ArrayCollection();
        $this->passkeys = new ArrayCollection();
        $this->login_tokens = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name . ' (' . $this->email . ')';
    }
}
