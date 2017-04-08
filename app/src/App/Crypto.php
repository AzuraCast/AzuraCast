<?php
namespace App;

use Defuse\Crypto\Crypto as DefuseCrypto;
use Defuse\Crypto\Key;

/**
 * General cryptography helpers for message handling.
 *
 * @package App
 */
class Crypto
{
    /**
     * @var \Defuse\Crypto\Key
     */
    protected $_key;

    public function __construct(Config $config)
    {
        $crypto_key = $config->apis->crypto_key;

        if (empty($crypto_key)) {
            $random_key = DefuseCrypto::createNewRandomKey();
            $random_key_str = $random_key->saveToAsciiSafeString();

            throw new Exception('No crypto key exists! Specify one in "apis.conf.php". Here\'s a random one for development: ' . $random_key_str);
        }

        $this->_key = Key::LoadFromAsciiSafeString($crypto_key);
    }

    public function encrypt($message)
    {
        return DefuseCrypto::encrypt($message, $this->_key);
    }

    public function decrypt($message)
    {
        return DefuseCrypto::decrypt($message, $this->_key);
    }
}