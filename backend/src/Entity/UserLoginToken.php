<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\LoginTokenTypes;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Interfaces\SplitTokenEntityInterface;
use App\Security\SplitToken;
use Azura\Normalizer\Attributes\DeepNormalize;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute as Serializer;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'user_login_tokens'),
    OA\Schema(type: 'object'),
    Attributes\Auditable,
]
final readonly class UserLoginToken implements SplitTokenEntityInterface, IdentifiableEntityInterface
{
    use Traits\HasSplitTokenFields;
    use Traits\TruncateStrings;

    #[OA\Property]
    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'login_tokens')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[DeepNormalize(true)]
    #[Serializer\MaxDepth(1)]
    public User $user;

    #[
        ORM\Column(type: 'string', length: 50, enumType: LoginTokenTypes::class),
        OA\Property(
            description: 'The type of login token represented.',
            example: 'reset_password'
        )
    ]
    public LoginTokenTypes $type;

    #[OA\Property(example: "SSO Login")]
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $comment;

    #[OA\Property(example: 1640998800)]
    #[ORM\Column]
    public int $created_at;

    #[OA\Property(example: 1640998800)]
    #[ORM\Column]
    public int $expires_at;

    public function __construct(
        User $user,
        SplitToken $token,
        LoginTokenTypes $type,
        ?string $comment,
        int $expiresMinutes,
    )
    {
        $this->id = $token->identifier;
        $this->verifier = $token->hashVerifier();
        $this->user = $user;
        $this->type = $type;
        $this->comment = $this->truncateNullableString($comment);
        $this->created_at = time();
        $this->expires_at = $this->created_at + ($expiresMinutes * 60);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
