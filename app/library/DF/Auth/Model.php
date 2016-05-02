<?php
namespace DF\Auth;

use \Entity\User;

class Model extends Instance
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function authenticate($credentials = NULL)
    {
        $user_auth = User::authenticate($credentials['username'], $credentials['password']);

        if ($user_auth instanceof User)
        {
            $this->setUser($user_auth);
            return true;
        }
        else
        {
            \App\Flash::addMessage('Could not authenticate your credentials!', 'red');
            return false;
        }
    }
}