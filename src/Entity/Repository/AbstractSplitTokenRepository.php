<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\ApiKey;
use App\Entity\User;
use App\Entity\UserLoginToken;
use App\Security\SplitToken;

/**
 * @template TEntity of ApiKey|UserLoginToken
 * @extends Repository<TEntity>
 */
abstract class AbstractSplitTokenRepository extends Repository
{
    /**
     * Given an API key string in the format `identifier:verifier`, find and authenticate an API key.
     *
     * @param string $key
     */
    public function authenticate(string $key): ?User
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
