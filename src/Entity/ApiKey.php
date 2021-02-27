<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Security\SplitToken;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Table(name="api_keys")
 * @ORM\Entity(readOnly=true)
 *
 * @AuditLog\Auditable
 */
class ApiKey implements JsonSerializable
{
    use Traits\HasSplitTokenFields;
    use Traits\TruncateStrings;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="api_keys", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")
     * })
     * @var User
     */
    protected $user;

    /**
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $comment;

    public function __construct(User $user, SplitToken $token)
    {
        $this->user = $user;
        $this->setFromToken($token);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @AuditLog\AuditIdentifier
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $this->truncateString($comment);
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
}
