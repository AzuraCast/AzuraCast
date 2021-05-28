<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Security\SplitToken;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Stringable;

#[
    AuditLog\Auditable,
    ORM\Table(name: 'api_keys'),
    ORM\Entity(readOnly: true)
]
class ApiKey implements JsonSerializable, Stringable
{
    use Traits\HasSplitTokenFields;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER', inversedBy: 'api_keys')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(length: 255)]
    protected ?string $comment = null;

    public function __construct(User $user, SplitToken $token)
    {
        $this->user = $user;
        $this->setFromToken($token);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $this->truncateNullableString($comment);
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'comment' => $this->comment,
        ];
    }

    public function __toString(): string
    {
        return $this->comment;
    }
}
