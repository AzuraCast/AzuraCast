<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\SplitTokenEntityInterface;
use App\Security\SplitToken;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'user_login_tokens')
]
final readonly class UserLoginToken implements SplitTokenEntityInterface
{
    use Traits\HasSplitTokenFields;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'login_tokens')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public User $user;

    #[ORM\Column]
    public int $created_at;

    public function __construct(User $user, SplitToken $token)
    {
        $this->id = $token->identifier;
        $this->verifier = $token->hashVerifier();

        $this->user = $user;
        $this->created_at = time();
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
