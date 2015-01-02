<?php

namespace Baseapp\Library\Payment;

/**
 * PayPal Payment adapter
 *
 * @package     base-app
 * @category    Library
 * @version     2.0
 */
class Paypal extends \Baseapp\Library\Payment
{

    protected $_response = array();
    protected $_required = array(
        'METHOD' => null,
        'VERSION' => null,
        'USER' => null,
        'PWD' => null,
        'SIGNATURE' => null,
    );
    protected $_fields = array(
        'RETURNURL' => null,
        'CANCELURL' => null,
        'PAYMENTACTION' => null,
    );
    private $expressCheckout = array(
        'SetExpressCheckout' => array('AMT'),
        'GetExpressCheckoutDetails' => array('TOKEN'),
        'DoExpressCheckoutPayment' => array('PAYERID', 'TOKEN', 'AMT'),
    );

    /**
     * Returns the NVP API URL for the current environment.
     *
     * @package     base-app
     * @version     2.0
     *
     * @return string
     */
    protected function apiURL()
    {
        if ($this->_config->paypal->env === 'live') {
            // Live environment does not use a sub-domain
            $env = '';
        } else {
            // Use the environment sub-domain
            $env = $this->_config->paypal->env . '.';
        }

        return 'https://api-3t.' . $env . 'paypal.com/nvp';
    }

    /**
     * Make an SetExpressCheckout/GetExpressCheckoutDetails/DoExpressCheckoutPayment call
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $method
     * @param array $params NVP parameters
     * @return array
     */
    public function call($method, array $params = null)
    {
        $this->_required['METHOD'] = $method;
        $this->_required['VERSION'] = $this->_config->paypal->apiVersion;
        $this->_required['USER'] = $this->_config->paypal->username;
        $this->_required['PWD'] = $this->_config->paypal->password;
        $this->_required['SIGNATURE'] = $this->_config->paypal->signature;
        $this->_fields['RETURNURL'] = $this->returnURL();
        $this->_fields['CANCELURL'] = $this->cancelURL();
        $this->_fields['PAYMENTACTION'] = 'Sale';

        $fields = array_merge($this->_required, $this->_fields, $params);

        foreach ($this->expressCheckout[$method] as $key) {
            if (empty($fields[$key])) {
                throw new \Phalcon\Exception(strtr('You must provide a :param parameter for :method', array(':param' => $key, ':method' => $method)));
            }
        }

        return $this->post($fields);
    }

    /**
     * Get cancel url
     *
     * @package     base-app
     * @version     2.0
     *
     * @return string
     */
    protected function cancelURL()
    {
        return $this->siteURL($this->_config->paypal->cancelURL);
    }

    /**
     * Check the token
     *
     * @package     base-app
     * @version     2.0
     *
     * @return mixed token
     */
    public function check()
    {
        if (!$this->_response) {
            $this->_response = array_change_key_case($_GET, CASE_UPPER);
        }

        return (isset($this->_response['TOKEN'])) ? $this->_response['TOKEN'] : FALSE;
    }

    /**
     * Get ExpressCheckout details
     *
     * @package     base-app
     * @version     2.0
     *
     * @param array $params
     * @param string $field
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
     * Makes a POST request to PayPal NVP for the given method and parameters.
     *
     * @see https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_NVPAPIOverview
     *
     * @package     base-app
     * @version     2.0
     *
     * @param array POST parameters
     * @return array
     */
    protected function post(array $params)
    {
        // Create a new curl instance
        $curl = curl_init();

        // Set curl options
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiURL(),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params, null, '&'),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
        ));

        if (($response = curl_exec($curl)) === false) {
            // Get the error code and message
            $code = curl_errno($curl);
            $error = curl_error($curl);

            // Close curl
            curl_close($curl);

            throw new \Phalcon\Exception(strtr('PayPal API request for :method failed: :error (:code)', array(':method' => $params['METHOD'], ':error' => $error, ':code' => $code)));
        }

        // Close curl
        curl_close($curl);

        // Parse the response
        parse_str($response, $this->_response);

        if (!isset($this->_response['ACK']) OR strpos($this->_response['ACK'], 'Success') === false) {
            throw new \Phalcon\Exception(strtr('PayPal API request for :method failed: :error (:code)', array(':method' => $params['METHOD'], ':error' => $this->_response['L_LONGMESSAGE0'], ':code' => $this->_response['L_ERRORCODE0'])));
        }

        return $this->_response;
    }

    /**
     * Make an SetExpressCheckout call
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $method
     * @param array $params NVP parameters
     * @return array
     */
    public function process(array $params)
    {
        return $this->call('SetExpressCheckout', $params);
    }

    /**
     * Returns the redirect URL for the current environment.
     *
     * @see  https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_Appx_websitestandard_htmlvariables#id08A6HF00TZS
     *
     * @package     base-app
     * @version     2.0
     *
     * @param   string   PayPal command
     * @param   array    GET parameters
     * @return  string
     */
    public function redirectURL($command, array $params)
    {
        if ($this->_config->paypal->env === 'live') {
            // Live environment does not use a sub-domain
            $env = '';
        } else {
            // Use the environment sub-domain
            $env = $this->_config->paypal->env . '.';
        }

        // Add the command to the parameters
        $params = array('cmd' => '_' . $command) + $params;

        return 'https://www.' . $env . 'paypal.com/webscr?' . http_build_query($params, null, '&');
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
        return $this->siteURL($this->_config->paypal->returnURL);
    }

}
