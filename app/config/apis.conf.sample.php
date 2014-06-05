<?php
/**
 * Configuration for PVL Third-Party APIs.
 */

return array(

    // PVL deployment API sent by this application. Contact PVL lead developer for info.
    'pvl_api_key'       => '',

    // PVL deployment API keys accepted by this application.
    'pvl_api_keys'      => array(),

    // Mandrill SMTP service.
    'smtp' => array(
        'server'        => 'smtp.mandrillapp.com',
        'port'          => '587',
        'auth'          => 'login',
        'username'      => '',
        'password'      => '',
    ),

    // YouTube v3 API key.
    'youtube_v3' => '',

    // Twitter API settings.
    'twitter' => array(
        'consumer_key'  => '',
        'consumer_secret' => '',
        'user_token'    => '',
        'user_secret'   => '',

        'curl_ssl_verifyhost' => 0,
        'curl_ssl_verifypeer' => false,
    ),

    // Hybrid/oAuth API settings.
    'hybrid_auth' => array(
        'base_url' => \DF\Url::baseUrl(TRUE),

        // Enable debug mode (specify "debug_file" below).
        'debug_mode' => false,
        'debug_file' => '',

        'providers' => array ( 
            "OpenID" => array (
                "enabled" => true
            ),

            "Google" => array ( 
                "enabled" => true,
                "keys"    => array(
                    "id" => "", 
                    "secret" => "",
                ), 
            ),

            "Facebook" => array ( 
                "enabled" => true,
                "keys"    => array(
                    "id" => "", 
                    "secret" => ""
                ), 
                "scope"   => "email, user_about_me", // optional
            ),

            "Twitter" => array ( 
                "enabled" => true,
                "keys"    => array(
                    "key" => "", 
                    "secret" => "",
                ),
            ),

            "Tumblr" => array ( 
                "enabled" => true,
                "keys"    => array(
                    "key" => "", 
                    "secret" => ""
                ),
            ),
        ),
    ),

    // CentovaCast API settings.
    'centovacast' => array(
        // IP or hostname of the CentovaCast server.
        'host'      => '198.27.112.218',

        'db_user'   => 'centova',
        'db_pass'   => '',
        'db_name'   => 'centova',

        // Time zone to use when submitting requests.
        'timezone'  => 'US/Eastern',
    ),

    // ReCAPTCHA Service keys.
    'recaptcha' => array(
        'public_key' => '',
        'private_key' => '',
    ),

);