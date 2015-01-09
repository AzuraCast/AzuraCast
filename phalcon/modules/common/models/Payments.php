<?php

namespace Baseapp\Models;

use Baseapp\Library\Auth;

/**
 * Payment Model
 *
 * @package     base-app
 * @category    Model
 * @version     2.0
 */
class Payments extends \Phalcon\Mvc\Model
{

    /**
     * Payment initialize relatins
     *
     * @package     base-app
     * @version     2.0
     */
    public function initialize()
    {
        // Relation with user, be able to get payment's user
        // eg. $payment->getUser()->username
        $this->belongsTo('user_id', __NAMESPACE__ . '\Users', 'id', array(
            'alias' => 'User'
        ));
    }

    /**
     * Add new payment method
     *
     * @package     base-app
     * @version     2.0
     *
     * @param array $checkout data
     * @return object payment or errors
     */
    public function add($checkout)
    {
        $validation = new \Baseapp\Extension\Validation();

        $validation->add('firstname', new \Phalcon\Validation\Validator\PresenceOf());
        $validation->add('lastname', new \Phalcon\Validation\Validator\PresenceOf());
        $validation->add('email', new \Phalcon\Validation\Validator\PresenceOf());
        $validation->add('email', new \Phalcon\Validation\Validator\Email());

        $messages = $validation->validate($_POST);

        if (count($messages)) {
            return $validation->getMessages();
        } else {
            $this->user_id = $this->getDI()->getShared('auth')->get_user()->id;
            $this->firstname = $this->getDI()->getShared('request')->getPost('firstname', 'string');
            $this->lastname = $this->getDI()->getShared('request')->getPost('lastname', 'string');
            $this->email = $this->getDI()->getShared('request')->getPost('email');

            $this->quantity = $checkout['quantity'];
            $this->amount = $checkout['price'];
            $this->total = $checkout['price'] * $checkout['quantity'];

            $date = date("Y-m-d H:i:s");
            $this->control = md5($this->getDI()->getShared('request')->getPost('email') . $date);
            $this->state = 'REQUEST';
            $this->date = $date;
            $this->note = $this->getDI()->getShared('request')->getPost('note', 'string');

            $this->ip = $this->getDI()->getShared('request')->getClientAddress();
            $this->user_agent = $this->getDI()->getShared('request')->getUserAgent();

            if ($this->create() === true) {
                return $this;
            } else {
                \Baseapp\Bootstrap::log($this->getMessages());
                return $this->getMessages();
            }
        }
    }

}
