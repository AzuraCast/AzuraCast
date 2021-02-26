<?php

namespace App\Entity\Traits;

use App\Annotations\AuditLog;
use App\Security\SplitToken;
use Doctrine\ORM\Mapping as ORM;

trait HasSplitTokenFields
{
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

    protected function setFromToken(SplitToken $token): void
    {
        $this->id = $token->identifier;
        $this->verifier = $token->hashVerifier();
    }

    public function getId(): string
    {
        return $this->id;
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
}
