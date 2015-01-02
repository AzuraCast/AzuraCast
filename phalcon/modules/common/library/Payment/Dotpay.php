<?php

namespace Baseapp\Library\Payment;

/**
 * Dotpay Payment adapter
 *
 * @package     base-app
 * @category    Library
 * @version     2.0
 */
class Dotpay extends \Baseapp\Library\Payment
{

    private $apiURL = 'https://ssl.dotpay.pl/';
    protected $_response = array();
    protected $_required = array(
        'id' => null,
        'amount' => null,
        'currency' => null,
        'description' => null,
        'lang' => null,
    );
    protected $_fields = array(
        'channel' => null,
        'ch_lock' => null,
        'online_transfer' => null,
        'URL' => null,
        'type' => null,
        'buttontext' => null,
        'URLC' => null,
        'firstname' => null,
        'lastname' => null,
        'email' => null,
        'street' => null,
        'street_n1' => null,
        'street_n2' => null,
        'addr2' => null,
        'addr3' => null,
        'city' => null,
        'postcode' => null,
        'phone' => null,
        'code' => null,
        'p_info' => null,
        'p_email' => null,
        'tax' => null,
        'control' => null,
    );

    /**
     * Get api url
     *
     * @package     base-app
     * @version     2.0
     *
     * @return string
     */
    protected function apiURL()
    {
        return $this->apiURL;
    }

    /**
     * Check the control md5 sum
     *
     * @package     base-app
     * @version     2.0
     *
     * @return mixed control
     */
    public function check()
    {
        if (!$this->_response) {
            $this->_response = $_POST;
        }

        $data = array(
            'PIN' => $this->_config->dotpay->PIN,
            'id' => isset($this->_response['id']) ? $this->_response['id'] : null,
            'control' => isset($this->_response['control']) ? $this->_response['control'] : null,
            't_id' => isset($this->_response['t_id']) ? $this->_response['t_id'] : null,
            'amount' => isset($this->_response['amount']) ? $this->_response['amount'] : null,
            'email' => isset($this->_response['email']) ? $this->_response['email'] : null,
            'service' => isset($this->_response['service']) ? $this->_response['service'] : null,
            'code' => isset($this->_response['code']) ? $this->_response['code'] : null,
            'username' => isset($this->_response['username']) ? $this->_response['username'] : null,
            'password' => isset($this->_response['password']) ? $this->_response['password'] : null,
            't_status' => isset($this->_response['t_status']) ? $this->_response['t_status'] : null,
        );

        return ($this->_response['md5'] == md5(implode(':', $data))) ? $this->_response['control'] : FALSE;
    }

    /**
     * Get the response value(s) if chontrol pass
     *
     * @package     base-app
     * @version     2.0
     *
     * @param array $params POST patameters
     * @param string $field field name
     * @return mixed
     */
    public function get($field = null)
    {
        if ($this->check()) {
            if (!$field) {
                return $this->_response;
            } elseif (isset($this->_response[$field])) {
                return $this->_response[$field];
            }
        }
        return FALSE;
    }

    /**
     * Prepare form and send it
     *
     * @package     base-app
     * @version     2.0
     *
     * @param array $fields fields to send
     * @return mixed
     */
    protected function post(array $fields)
    {
        $tag = \Phalcon\DI::getDefault()->getShared('tag');
        $form = $tag->form(array($this->apiURL(), 'name' => 'dotpay'));

        foreach ($fields as $key => $value) {
            if (!empty($value)) {
                $form .= $tag->hiddenField(array($key, 'value' => $value));
            }
        }

        $form .= '<button type="submit" name="payment" class="btn btn-default"><span class="glyphicon glyphicon-envelope"></span>' . __("Go to dotpay") . '</button>';
        $form .= $tag->endForm();
        $form .= '<script type="text/javascript">document.dotpay.submit();</script>';

        return $form;
    }

    /**
     * Process the payment
     *
     * @package     base-app
     * @version     2.0
     *
     * @param array $params patameters
     * @return mixed
     */
    public function process(array $params)
    {
        $this->_required['id'] = $this->_config->dotpay->id;
        $this->_fields['URL'] = $this->returnURL();
        $this->_fields['URLC'] = $this->statusURL();

        $fields = array_merge($this->_required, $this->_fields, $params);

        foreach (array_keys($this->_required) as $key) {
            if (empty($fields[$key])) {
                throw new \Phalcon\Exception(strtr('You must provide a :param parameter for :method', array(':param' => $key, ':method' => 'dotpay')));
            }
        }

        return $this->post($fields);
    }

    /**
     * Get return url
     *
     * @package     base-app
     * @version     2.0
     *
     * @return string
     */
    protected function returnURL()
    {
        return $this->siteURL($this->_config->dotpay->returnURL);
    }

    /**
     * Get status url
     *
     * @package     base-app
     * @version     2.0
     *
     * @return string
     */
    private function statusURL()
    {
        return $this->siteURL($this->_config->dotpay->statusURL);
    }

}
