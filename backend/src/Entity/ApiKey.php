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
use Symfony\Component\Serializer\Attribute as Serializer;

#[
    OA\Schema(type: 'object'),
    Attributes\Auditable,
    ORM\Table(name: 'api_keys'),
    ORM\Entity(readOnly: true)
]
final readonly class ApiKey implements Stringable, IdentifiableEntityInterface, SplitTokenEntityInterface
{
    use Traits\HasSplitTokenFields;
    use Traits\TruncateStrings;

    #[OA\Property]
    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER', inversedBy: 'api_keys')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Serializer\Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])]
    #[DeepNormalize(true)]
    #[Serializer\MaxDepth(1)]
    public User $user;

    #[OA\Property]
    #[ORM\Column(length: 255, nullable: false)]
    #[Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])]
    public string $comment;

    public function __construct(
        User $user,
        SplitToken $token,
        string $comment = ''
    ) {
        $this->id = $token->identifier;
        $this->verifier = $token->hashVerifier();
        
        $this->user = $user;
        $this->comment = $this->truncateString($comment);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function __toString(): string
    {
        return $this->comment;
    }
}
