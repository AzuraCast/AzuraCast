<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Traits\TruncateStrings;
use App\Security\WebAuthnPasskey;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'user_passkeys')
]
class UserPasskey implements IdentifiableEntityInterface
{
    use TruncateStrings;

    #[ORM\Column(length: 64)]
    #[ORM\Id]
    #[Groups([
        EntityGroupsInterface::GROUP_ID,
        EntityGroupsInterface::GROUP_ALL,
    ])]
    protected string $id;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'passkeys')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column]
    #[Groups([
        EntityGroupsInterface::GROUP_GENERAL,
        EntityGroupsInterface::GROUP_ALL,
    ])]
    protected int $created_at;

    #[ORM\Column(length: 255)]
    #[Groups([
        EntityGroupsInterface::GROUP_GENERAL,
        EntityGroupsInterface::GROUP_ALL,
    ])]
    protected string $name;

    #[ORM\Column(type: 'text')]
    protected string $full_id;

    #[ORM\Column(type: 'text')]
    protected string $public_key_pem;

    public function __construct(User $user, string $name, WebAuthnPasskey $passkey)
    {
        $this->user = $user;
        $this->name = $this->truncateString($name);
        $this->id = $passkey->getHashedId();
        $this->full_id = base64_encode($passkey->getId());
        $this->public_key_pem = $passkey->getPublicKeyPem();
        $this->created_at = time();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIdRequired(): int|string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreatedAt(): int
    {
        return $this->created_at;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPasskey(): WebAuthnPasskey
    {
        return new WebAuthnPasskey(
            base64_decode($this->full_id),
            $this->public_key_pem
        );
    }

    public function verifyFullId(string $fullId): void
    {
        if (!hash_equals($this->getPasskey()->getId(), $fullId)) {
            throw new InvalidArgumentException('Full ID does not match passkey.');
        }
    }
}
