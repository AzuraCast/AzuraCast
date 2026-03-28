<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity\Attributes\AuditIgnore;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Security\SplitToken;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute as Serializer;

#[OA\Schema(
    type: 'object'
)]
trait HasSplitTokenFields
{
    #[OA\Property(
        readOnly: true
    )]
    #[ORM\Column(length: 16)]
    #[ORM\Id]
    #[Serializer\Groups([
        EntityGroupsInterface::GROUP_ID,
        EntityGroupsInterface::GROUP_ALL,
    ])]
    public readonly string $id;

    #[OA\Property(
        readOnly: true
    )]
    #[ORM\Column(length: 128)]
    #[AuditIgnore]
    protected readonly string $verifier;

    public function verify(SplitToken $userSuppliedToken): bool
    {
        return $userSuppliedToken->verify($this->verifier);
    }
}
