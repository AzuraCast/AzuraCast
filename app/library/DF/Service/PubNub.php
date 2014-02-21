<?php

namespace DF\Service;

class PubNub {
    private $ORIGIN        = 'pubsub.pubnub.com';
    private $LIMIT         = 1800;
    private $PUBLISH_KEY   = 'demo';
    private $SUBSCRIBE_KEY = 'demo';
    private $SECRET_KEY    = false;
    private $SSL           = false;

    public function __construct()
    {
        $config = \Zend_Registry::get('config');
        $settings = $config->services->pubnub->toArray();

        $this->PUBLISH_KEY = $settings['pub_key'];
        $this->SUBSCRIBE_KEY = $settings['sub_key'];
        $this->SECRET_KEY = $settings['secret_key'];
        $this->SSL = $settings['ssl'];
        $this->ORIGIN = (($this->SSL) ? 'https://' : 'http://').$this->ORIGIN;
    }

    public function publish($channel, $message)
    {
        $message = json_encode($message);

        ## Generate String to Sign
        $string_to_sign = implode( '/', array(
            $this->PUBLISH_KEY,
            $this->SUBSCRIBE_KEY,
            $this->SECRET_KEY,
            $channel,
            $message
        ) );

        ## Sign Message
        $signature = $this->SECRET_KEY ? md5($string_to_sign) : '0';
        
        ## Fail if message too long.
        if (strlen($message) > $this->LIMIT) {
            echo('Message TOO LONG (' . $this->LIMIT . ' LIMIT)');
            return array( 0, 'Message Too Long.' );
        }

        ## Send Message
        return $this->_request(array(
            'publish',
            $this->PUBLISH_KEY,
            $this->SUBSCRIBE_KEY,
            $signature,
            $channel,
            '0',
            $message
        ));
    }

    public function history($channel, $limit = 10)
    {
        ## Get History
        return $this->_request(array(
            'history',
            $this->SUBSCRIBE_KEY,
            $channel,
            '0',
            $limit
        ));
    }

    public function time() {
        ## Get History
        $response = $this->_request(array(
            'time',
            '0'
        ));

        return $response[0];
    }

    private function _request($request)
    {
        $request = array_map(array(__CLASS__, '_encode'), $request);
        array_unshift( $request, $this->ORIGIN );

        $ctx = stream_context_create(array(
            'http' => array( 'timeout' => 200 ) 
        ));

        return json_decode( @file_get_contents(
            implode( '/', $request ), 0, $ctx
        ), true );
    }

    protected static function _encode($part)
    {
        return implode('', array_map(
            array(__CLASS__, '_encode_char'), str_split($part)
        ));
    }

    protected static function _encode_char($char)
    {
        if (strpos( ' ~`!@#$%^&*()+=[]\\{}|;\':",./<>?', $char ) === false)
            return $char;
        return rawurlencode($char);
    }
}