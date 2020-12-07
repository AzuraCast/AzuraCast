<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use App\Security\SplitToken;

class ApiKeyRepository extends Repository
{
    /**
     * Given an API key string in the format `identifier:verifier`, find and authenticate an API key.
     *
     * @param string $key
     */
    public function authenticate(string $key): ?Entity\User
    {
        $userSuppliedToken = SplitToken::fromKeyString($key);

        $api_key = $this->repository->find($userSuppliedToken->identifier);

        if ($api_key instanceof Entity\ApiKey) {
            return ($api_key->verify($userSuppliedToken))
                ? $api_key->getUser()
                : null;
        }

        return null;
    }
}
