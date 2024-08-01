<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\User;

/**
 * @extends Repository<User>
 */
final class UserRepository extends Repository
{
    protected string $entityClass = User::class;

    public function findByEmail(string $email): ?User
    {
        return $this->repository->findOneby(['email' => $email]);
    }

    public function authenticate(string $username, string $password): ?User
    {
        $user = $this->findByEmail($username);

        if ($user instanceof User && $user->verifyPassword($password)) {
            return $user;
        }

        // Verify a password (and do nothing with it) to avoid timing attacks on authentication.
        password_verify(
            $password,
            '$argon2id$v=19$m=65536,t=4,p=1$WHptOW0xM1UweHp0ZXpmNg$qC5anR37sV/G8k7l09eLKLHukkUD7e5csUdbmjGYsgs'
        );

        return null;
    }

    public function getOrCreate(string $email): User
    {
        $user = $this->findByEmail($email);
        if (!($user instanceof User)) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($email);
        }

        return $user;
    }
}
