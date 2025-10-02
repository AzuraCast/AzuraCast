<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Interfaces\SplitTokenEntityInterface;
use App\Security\SplitToken;

/**
 * @template TEntity of SplitTokenEntityInterface
 * @extends Repository<TEntity>
 */
abstract class AbstractSplitTokenRepository extends Repository
{
    /**
     * Given an API key string in the format `identifier:verifier`, find and authenticate an API key.
     *
     * @param string $key
     * @return TEntity|null
     */
    public function authenticate(string $key): ?SplitTokenEntityInterface
    {
        $userSuppliedToken = SplitToken::fromKeyString($key);

        $tokenEntity = $this->repository->find($userSuppliedToken->identifier);

        if ($tokenEntity instanceof SplitTokenEntityInterface) {
            return ($tokenEntity->verify($userSuppliedToken))
                ? $tokenEntity
                : null;
        }

        return null;
    }
}
