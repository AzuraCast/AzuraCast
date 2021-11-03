<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity;
use App\Security\SplitToken;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait HasSplitTokenFields
{
    #[ORM\Column(length: 16)]
    #[ORM\Id]
    #[Groups([Entity\Interfaces\EntityGroupsInterface::GROUP_ID, Entity\Interfaces\EntityGroupsInterface::GROUP_ALL])]
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

    public function getIdRequired(): string
    {
        return $this->id;
    }

    public function verify(SplitToken $userSuppliedToken): bool
    {
        return $userSuppliedToken->verify($this->verifier);
    }
}
