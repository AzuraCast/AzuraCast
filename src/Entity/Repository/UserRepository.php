<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

/**
 * @extends Repository<Entity\User>
 */
class UserRepository extends Repository
{
    public function find(int $id): ?Entity\User
    {
        return $this->repository->find($id);
    }

    public function findByEmail(string $email): ?Entity\User
    {
        return $this->repository->findOneby(['email' => $email]);
    }

    public function authenticate(string $username, string $password): ?Entity\User
    {
        $user = $this->findByEmail($username);

        if ($user instanceof Entity\User && $user->verifyPassword($password)) {
            return $user;
        }

        // Verify a password (and do nothing with it) to avoid timing attacks on authentication.
        password_verify(
            $password,
            '$argon2id$v=19$m=65536,t=4,p=1$WHptOW0xM1UweHp0ZXpmNg$qC5anR37sV/G8k7l09eLKLHukkUD7e5csUdbmjGYsgs'
        );

        return null;
    }

    public function getOrCreate(string $email): Entity\User
    {
        $user = $this->findByEmail($email);
        if (!($user instanceof Entity\User)) {
            $user = new Entity\User();
            $user->setEmail($email);
            $user->setName($email);
        }

        return $user;
    }
}
