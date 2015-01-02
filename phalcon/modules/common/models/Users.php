<?php

namespace Baseapp\Models;

use Baseapp\Library\Email;

/**
 * User Model
 *
 * @package     base-app
 * @category    Model
 * @version     2.0
 */
class Users extends \Phalcon\Mvc\Model
{

    public $request;

    /**
     * User initialize
     *
     * @package     base-app
     * @version     2.0
     */
    public function initialize()
    {
        $this->hasMany('id', __NAMESPACE__ . '\Tokens', 'user_id', array(
            'alias' => 'Tokens',
            'foreignKey' => array(
                'action' => \Phalcon\Mvc\Model\Relation::ACTION_CASCADE
            )
        ));
        $this->hasMany('id', __NAMESPACE__ . '\RolesUsers', 'user_id', array(
            'alias' => 'Roles',
            'foreignKey' => array(
                'action' => \Phalcon\Mvc\Model\Relation::ACTION_CASCADE
            )
        ));

        $this->request = $this->getDI()->getShared('request');
    }

    /**
     * Activation User method
     *
     * @package     base-app
     * @version     2.0
     */
    public function activation()
    {
        if ($this->getRole()) {
            // This user has login role, activation has already been completed
            return NULL;
        } else {
            // Add login role
            $role = new RolesUsers();
            $role->user_id = $this->id;
            $role->role_id = Roles::findFirst(array('name="login"'))->id;

            if ($role->create() === true) {
                return TRUE;
            } else {
                \Baseapp\Bootstrap::log($this->getMessages());
                return $this->getMessages();
            }
        }
    }

    /**
     * Get user's role relation
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $role role to get one RolesUsers
     */
    public function getRole($role = 'login')
    {
        $role = Roles::findFirst(array('name=:role:', 'bind' => array(':role' => $role)));
        // Return null if role does not exist
        if (!$role) {
            return null;
        }
        // Return the role if user has the role otherwise false
        return $this->getRoles(array('role_id=:role:', 'bind' => array(':role' => $role->id)))->getFirst();
    }

    /**
     * Sign up User method
     *
     * @version     2.0
     */
    public function signup()
    {
        $validation = new \Baseapp\Extension\Validation();

        $validation->add('username', new \Phalcon\Validation\Validator\PresenceOf());
        $validation->add('username', new \Baseapp\Extension\Uniqueness(array(
            'model' => '\Baseapp\Models\Users',
        )));
        $validation->add('username', new \Phalcon\Validation\Validator\StringLength(array(
            'min' => 4,
            'max' => 24,
        )));
        $validation->add('password', new \Phalcon\Validation\Validator\PresenceOf());
        $validation->add('repeatPassword', new \Phalcon\Validation\Validator\Confirmation(array(
            'with' => 'password',
        )));
        $validation->add('email', new \Phalcon\Validation\Validator\PresenceOf());
        $validation->add('email', new \Phalcon\Validation\Validator\Email());
        $validation->add('email', new \Baseapp\Extension\Uniqueness(array(
            'model' => '\Baseapp\Models\Users',
        )));
        $validation->add('repeatEmail', new \Phalcon\Validation\Validator\Confirmation(array(
            'with' => 'email',
        )));

        $validation->setLabels(array('username' => __('Username'), 'password' => __('Password'), 'repeatPassword' => __('Repeat password'), 'email' => __('Email'), 'repeatEmail' => __('Repeat email')));
        $messages = $validation->validate($_POST);

        if (count($messages)) {
            return $validation->getMessages();
        } else {
            $this->username = $this->request->getPost('username');
            $this->password = $this->getDI()->getShared('auth')->hash($this->request->getPost('password'));
            $this->email = $this->request->getPost('email');
            $this->logins = 0;

            if ($this->create() === true) {
                $hash = md5($this->id . $this->email . $this->password . $this->getDI()->getShared('config')->auth->hash_key);

                $email = new Email();
                $email->prepare(__('Activation'), $this->request->getPost('email'), 'activation', array('username' => $this->request->getPost('username'), 'hash' => $hash));

                if ($email->Send() === true) {
                    unset($_POST);
                    return $this;
                } else {
                    \Baseapp\Bootstrap::log($email->ErrorInfo);
                    return false;
                }
            } else {
                \Baseapp\Bootstrap::log($this->getMessages());
                return false;
            }
        }
    }

}
