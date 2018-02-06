<?php
namespace Entity;

/**
 * @Table(name="api_keys")
 * @Entity(repositoryClass="Entity\Repository\ApiKeyRepository")
 */
class ApiKey
{
    /**
     * @Column(name="id", type="string", length=16)
     * @Id
     * @var string
     */
    protected $id;

    /**
     * @Column(name="verifier", type="string", length=128, nullable=false)
     * @var string
     */
    protected $verifier;

    /**
     * @Column(name="user_id", type="integer")
     * @var int
     */
    protected $user_id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="api_keys", fetch="EAGER")
     * @JoinColumns({
     *   @JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")
     * })
     * @var User
     */
    protected $user;

    /**
     * @Column(name="comment", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $comment;

    public function __construct(User $user)
    {
        $this->user = $user;
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
        $this->verifier = hash('sha512', $verifier);

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
        $verifier_to_compare = hash('sha512', $verifier);
        return hash_equals($this->verifier, $verifier_to_compare);
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
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
        $this->comment = $comment;
    }
}