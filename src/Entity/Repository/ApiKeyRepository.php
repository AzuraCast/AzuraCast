<?php
namespace App\Entity\Repository;

use App\Entity;
use Azura\Doctrine\Repository;

class ApiKeyRepository extends Repository
{
    /**
     * Given an API key string in the format `identifier:verifier`, find and authenticate an API key.
     *
     * @param string $key_string
     *
     * @return Entity\User|null
     */
    public function authenticate($key_string): ?Entity\User
    {
        [$key_identifier, $key_verifier] = explode(':', $key_string);

        $api_key = $this->repository->find($key_identifier);

        if ($api_key instanceof Entity\ApiKey) {
            return ($api_key->verify($key_verifier))
                ? $api_key->getUser()
                : null;
        }

        return null;
    }
}
