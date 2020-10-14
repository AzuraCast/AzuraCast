<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use InvalidArgumentException;

class ApiKeyRepository extends Repository
{
    /**
     * Given an API key string in the format `identifier:verifier`, find and authenticate an API key.
     *
     * @param string $key_string
     */
    public function authenticate(string $key_string): ?Entity\User
    {
        [$key_identifier, $key_verifier] = explode(':', $key_string);

        if (empty($key_identifier) || empty($key_verifier)) {
            throw new InvalidArgumentException('API key is not in a valid format.');
        }

        $api_key = $this->repository->find($key_identifier);

        if ($api_key instanceof Entity\ApiKey) {
            return ($api_key->verify($key_verifier))
                ? $api_key->getUser()
                : null;
        }

        return null;
    }
}
