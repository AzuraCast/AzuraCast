<?php
/**
 * Encryption Handler
 */

namespace DF;

define("APP_ENCRYPTION_KEY", "1ad1afc09c07f162fe993a88b5c9fbb4");
define("APP_ENCRYPTION_CIPHER", MCRYPT_RIJNDAEL_128);

class Encryption
{
    public static function encrypt($string)
    {
        // Create initialization vector.
        $iv_size = mcrypt_get_iv_size(APP_ENCRYPTION_CIPHER, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        
        // Trim encryption key to size supported by this cipher.
        $key = substr(APP_ENCRYPTION_KEY, 0, mcrypt_get_key_size(APP_ENCRYPTION_CIPHER, MCRYPT_MODE_ECB));
        
        $encrypted_string = mcrypt_encrypt(APP_ENCRYPTION_CIPHER, $key, trim($string), MCRYPT_MODE_ECB, $iv);
        
        // Package the encrypted string for easy storage.
        return base64_encode($encrypted_string).'|'.base64_encode($iv);
    }
    
    public static function decrypt($string)
    {
        // Unpackage the encoded, encrypted string.
        list($encoded_string, $encoded_iv) = explode('|', $string);
        
        $encrypted_string = base64_decode($encoded_string);
        $iv = base64_decode($encoded_iv);
                
        // Trim encryption key to size supported by this cipher.
        $key = substr(APP_ENCRYPTION_KEY, 0, mcrypt_get_key_size(APP_ENCRYPTION_CIPHER, MCRYPT_MODE_ECB));
        
        return trim(mcrypt_decrypt(APP_ENCRYPTION_CIPHER, $key, $encrypted_string, MCRYPT_MODE_ECB, $iv));
    }
    
    public static function digest($string)
    {
        return sha1($string.APP_ENCRYPTION_KEY);
    }
}