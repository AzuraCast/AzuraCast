<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity;
use App\Security\SplitToken;
use Doctrine\ORM\Mapping as ORM;

trait HasSplitTokenFields
{
    #[ORM\Column(length: 16)]
    #[ORM\Id]
    protected string $id;

    #[ORM\Column(length: 128)]
    #[Entity\Attributes\AuditIgnore]
    protected string $verifier;

    protected function setFromToken(SplitToken $token): void
    {
        $this->id = $token->identifier;
        $this->verifier = $token->hashVerifier();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function verify(SplitToken $userSuppliedToken): bool
    {
        return $userSuppliedToken->verify($this->verifier);
    }
}
