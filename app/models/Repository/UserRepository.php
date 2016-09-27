<?php
namespace Repository;

use App\Doctrine\Repository;
use Entity\User as Record;

class UserRepository extends Repository
{
    /**
     * @param $username
     * @param $password
     * @return bool|null|object
     */
    public function authenticate($username, $password)
    {
        $login_info = $this->findOneBy(['email' => $username]);

        if (!($login_info instanceof Record))
            return FALSE;

        if (password_verify($password, $login_info->auth_password))
        {
            if (password_needs_rehash($login_info->auth_password, \PASSWORD_DEFAULT))
            {
                $login_info->setAuthPassword($password);

                $this->_em->persist($login_info);
                $this->_em->flush();
            }

            return $login_info;
        }

        return FALSE;
    }

    /**
     * Creates or returns an existing user with the specified e-mail address.
     *
     * @param $email
     * @return \Entity\User
     */
    public function getOrCreate($email)
    {
        $user = $this->findOneBy(['email' => $email]);

        if (!($user instanceof Record))
        {
            $user = new Record;
            $user->email = $email;
            $user->name = $email;
        }

        return $user;
    }
}