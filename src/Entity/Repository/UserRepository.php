<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class UserRepository extends Repository
{
    /**
     * @param string $username
     * @param string $password
     *
     * @return bool|null|object
     */
    public function authenticate($username, $password)
    {
        $login_info = $this->repository->findOneBy(['email' => $username]);

        if (!($login_info instanceof Entity\User)) {
            return false;
        }

        if ($login_info->verifyPassword($password)) {
            return $login_info;
        }
        return false;
    }

    /**
     * Creates or returns an existing user with the specified e-mail address.
     *
     * @param string $email
     */
    public function getOrCreate($email): Entity\User
    {
        $user = $this->repository->findOneBy(['email' => $email]);

        if (!($user instanceof Entity\User)) {
            $user = new Entity\User();
            $user->setEmail($email);
            $user->setName($email);
        }

        return $user;
    }
}
