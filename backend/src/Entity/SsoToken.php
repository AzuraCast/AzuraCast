<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Interfaces\SplitTokenEntityInterface;
use App\Security\SplitToken;
use Azura\Normalizer\Attributes\DeepNormalize;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups;

#[
    OA\Schema(type: 'object'),
    Attributes\Auditable,
    ORM\Table(name: 'sso_tokens'),
    ORM\Entity(readOnly: true)
]
final readonly class SsoToken implements Stringable, IdentifiableEntityInterface, SplitTokenEntityInterface
{
    use Traits\HasSplitTokenFields;
    use Traits\TruncateStrings;

    #[OA\Property]
    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER', inversedBy: 'sso_tokens')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])]
    #[DeepNormalize(true)]
    #[Serializer\MaxDepth(1)]
    public User $user;

    #[OA\Property(example: "WHMCS SSO Login")]
    #[ORM\Column(length: 255, nullable: false)]
    #[Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])]
    public string $comment;

    #[OA\Property(example: 1640995200)]
    #[ORM\Column]
    #[Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])]
    public int $created_at;

    #[OA\Property(example: 1640998800)]
    #[ORM\Column]
    #[Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])]
    public int $expires_at;

    #[OA\Property(example: false)]
    #[ORM\Column]
    #[Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])]
    public bool $used;

    #[OA\Property(example: "192.168.1.1")]
    #[ORM\Column(length: 45, nullable: true)]
    #[Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])]
    public ?string $ip_address;

    public function __construct(
        User $user,
        SplitToken $token,
        string $comment = '',
        int $expiresIn = 300, // 5 minutes default
        ?string $ipAddress = null
    ) {
        $this->id = $token->identifier;
        $this->verifier = $token->hashVerifier();
        
        $this->user = $user;
        $this->comment = $this->truncateString($comment);
        $this->created_at = time();
        $this->expires_at = $this->created_at + $expiresIn;
        $this->used = false;
        $this->ip_address = $ipAddress;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isExpired(): bool
    {
        return time() > $this->expires_at;
    }

    public function isValid(): bool
    {
        // @phpstan-ignore-next-line
        return !$this->used && !$this->isExpired();
    }

    public function markAsUsed(): self
    {
        // Since this is a readonly class, we can't modify the used field
        // This would typically be handled by the repository or service
        return $this;
    }

    public function __toString(): string
    {
        return $this->comment;
    }
}
