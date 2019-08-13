<?php
namespace App\Entity;

use App\Annotations\AuditLog;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="api_keys")
 * @ORM\Entity(repositoryClass="App\Entity\Repository\ApiKeyRepository")
 *
 * @AuditLog\Auditable
 */
class ApiKey implements \JsonSerializable
{
    public const SEPARATOR = ':';

    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="string", length=16)
     * @ORM\Id
     * @var string
     */
    protected $id;

    /**
     * @ORM\Column(name="verifier", type="string", length=128, nullable=false)
     * @var string
     */
    protected $verifier;

    /**
     * @ORM\Column(name="user_id", type="integer")
     * @var int
     */
    protected $user_id;

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

    /**
     * @param User $user
     * @param string|null $key An existing API key to import (if one exists).
     */
    public function __construct(User $user, $key = null)
    {
        $this->user = $user;

        if (null !== $key) {
            [$identifier, $verifier] = explode(self::SEPARATOR, $key);

            $this->id = $identifier;
            $this->verifier = $this->hashVerifier($verifier);
        }
    }

    /**
     * Generate a unique identifier and return both the identifier and verifier.
     *
     * @return array [identifier, verifier]
     * @throws \Exception
     */
    public function generate(): array
    {
        $random_str = hash('sha256', random_bytes(32));

        $identifier = substr($random_str, 0, 16);
        $verifier = substr($random_str, 16, 32);

        $this->id = $identifier;
        $this->verifier = $this->hashVerifier($verifier);

        return [$identifier, $verifier];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Verify an incoming API key against the verifier on this record.
     *
     * @return bool
     */
    public function verify($verifier): bool
    {
        return hash_equals($this->verifier, $this->hashVerifier($verifier));
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @AuditLog\AuditIdentifier
     *
     * @return string
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment(?string $comment): void
    {
        $this->comment = $this->_truncateString($comment);
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'comment' => $this->comment,
        ];
    }

    /**
     * @param string $original
     * @return string The hashed verifier.
     */
    protected function hashVerifier(string $original): string
    {
        return hash('sha512', $original);
    }
}
