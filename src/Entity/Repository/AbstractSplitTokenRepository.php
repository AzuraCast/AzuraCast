<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use App\Security\SplitToken;

abstract class AbstractSplitTokenRepository extends Repository
{
    /**
     * Given an API key string in the format `identifier:verifier`, find and authenticate an API key.
     *
     * @param string $key
     */
    public function authenticate(string $key): ?Entity\User
    {
        $userSuppliedToken = SplitToken::fromKeyString($key);

        $tokenEntity = $this->repository->find($userSuppliedToken->identifier);

        if ($tokenEntity instanceof $this->entityClass) {
            return ($tokenEntity->verify($userSuppliedToken))
                ? $tokenEntity->getUser()
                : null;
        }

        return null;
    }
}
