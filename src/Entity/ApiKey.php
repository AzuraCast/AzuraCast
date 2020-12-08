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
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="string", length=16)
     * @ORM\Id
     * @var string
     */
    protected $id;

    /**
     * @ORM\Column(name="verifier", type="string", length=128, nullable=false)
     *
     * @AuditLog\AuditIgnore()
     *
     * @var string
     */
    protected $verifier;

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
        $this->id = $token->identifier;
        $this->verifier = $token->hashVerifier();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Verify an incoming API key against the verifier on this record.
     *
     * @param SplitToken $userSuppliedToken
     *
     */
    public function verify(SplitToken $userSuppliedToken): bool
    {
        return $userSuppliedToken->verify($this->verifier);
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
