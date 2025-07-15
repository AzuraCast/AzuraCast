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
    ORM\Table(name: 'api_keys'),
    ORM\Entity(readOnly: true)
]
final class ApiKey implements Stringable, IdentifiableEntityInterface, SplitTokenEntityInterface
{
    use Traits\HasSplitTokenFields;
    use Traits\TruncateStrings;

    #[OA\Property]
    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER', inversedBy: 'api_keys')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([EntityGroupsInterface::GROUP_ADMIN, EntityGroupsInterface::GROUP_ALL])]
    #[DeepNormalize(true)]
    #[Serializer\MaxDepth(1)]
    public readonly User $user;

    #[OA\Property]
    #[ORM\Column(length: 255, nullable: false)]
    #[Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])]
    public string $comment = '' {
        set => $this->truncateString($value);
    }

    public function __construct(User $user, SplitToken $token)
    {
        $this->user = $user;
        $this->setFromToken($token);
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
