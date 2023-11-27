<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\User;
use App\Entity\UserPasskey;
use App\Security\WebAuthnPasskey;

/**
 * @extends Repository<UserPasskey>
 */
final class UserPasskeyRepository extends Repository
{
    protected string $entityClass = UserPasskey::class;

    public function findById(string $id): ?UserPasskey
    {
        $record = $this->repository->find(WebAuthnPasskey::hashIdentifier($id));
        if (!($record instanceof UserPasskey)) {
            return null;
        }

        $record->verifyFullId($id);
        return $record;
    }

    public function getCredentialIds(User $user): array
    {
        $records = $this->em->createQuery(
            <<<'DQL'
            SELECT up.full_id
            FROM App\Entity\UserPasskey up
            WHERE up.user = :user
            DQL
        )->setParameter('user', $user)
            ->getSingleColumnResult();

        return array_map(
            fn($row) => base64_decode($row),
            $records
        );
    }
}
