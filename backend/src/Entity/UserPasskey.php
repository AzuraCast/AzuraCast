<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Traits\TruncateStrings;
use App\Security\WebAuthnPasskey;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Attribute as Serializer;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'user_passkeys')
]
final readonly class UserPasskey implements IdentifiableEntityInterface
{
    use TruncateStrings;

    #[ORM\Column(length: 64)]
    #[ORM\Id]
    #[Serializer\Groups([
        EntityGroupsInterface::GROUP_ID,
        EntityGroupsInterface::GROUP_ALL,
    ])]
    public string $id;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'passkeys')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public User $user;

    #[ORM\Column]
    #[Serializer\Groups([
        EntityGroupsInterface::GROUP_GENERAL,
        EntityGroupsInterface::GROUP_ALL,
    ])]
    public int $created_at;

    #[ORM\Column(length: 255)]
    #[Serializer\Groups([
        EntityGroupsInterface::GROUP_GENERAL,
        EntityGroupsInterface::GROUP_ALL,
    ])]
    public string $name;

    #[ORM\Column(type: 'text')]
    private string $full_id;

    #[ORM\Column(type: 'text')]
    private string $public_key_pem;

    public function __construct(User $user, string $name, WebAuthnPasskey $passkey)
    {
        $this->id = $passkey->getHashedId();
        $this->user = $user;
        $this->name = $this->truncateString($name);
        $this->full_id = base64_encode($passkey->getId());
        $this->public_key_pem = $passkey->getPublicKeyPem();
        $this->created_at = time();
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
