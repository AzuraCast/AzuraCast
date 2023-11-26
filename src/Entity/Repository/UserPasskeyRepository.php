<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
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
}
