<?php
/**
 * Configuration for PVL Third-Party APIs.
 */

return array(

	// Mandrill SMTP service.
	'smtp' => array(
		'server'		=> 'smtp.mandrillapp.com',
    	'port'			=> '587',
    	'auth'			=> 'login',
    	'username'		=> 'loobalightdark@gmail.com',
    	'password'		=> 'd05MxdhMxGxq7i8HRh8_mg',
	),

	// YouTube v3 API key.
	'youtube_v3' => 'AIzaSyC1vhf1rFShf9mzbUEL2LpfaD0E0BNOURk',

	// Twitter API settings.
	'twitter' => array(
		'consumer_key'  => 'dekLAskiLF8nrTZI3zmmg',
	    'consumer_secret' => 'J6OaNpKHlDmrQLEmvxfdlRWO4E7WbyNnBTdpz1njLcw',
	    'user_token'    => '974916638-1jK4vgMYvv9pAc2gQfAYGcnDY58xTij5M42P93VU',
	    'user_secret'   => 'TTDLFrhcULlU3a9uYxIbdW5DZxx4TsCfOlf9sWuVlY4',

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
					"id" => "722497886984.apps.googleusercontent.com", 
					"secret" => "d8SDY90qkWddYFSRamKe40zq",
				), 
			),

			"Facebook" => array ( 
				"enabled" => true,
				"keys"    => array(
					"id" => "507310642670190", 
					"secret" => "c4481c738c8e3a86eb9fa9838f4bc8b8"
				), 
				"scope"   => "email, user_about_me", // optional
			),

			"Twitter" => array ( 
				"enabled" => true,
				"keys"    => array(
					"key" => "dekLAskiLF8nrTZI3zmmg", 
					"secret" => "J6OaNpKHlDmrQLEmvxfdlRWO4E7WbyNnBTdpz1njLcw",
				),
			),

			"Tumblr" => array ( 
				"enabled" => true,
				"keys"    => array(
					"key" => "Hp1W4lpJ0dhHA7pOGih0yow02ZXAFHdiIR5bzFS67C0xlERPAZ", 
					"secret" => "Nr3gbtyd5N0maCC1rx3GJ6K7I7wAOxYM7nfYbLnhS2bYIqbtOg"
				),
			),
		),
	),

	// CentovaCast API settings.
	'centovacast' => array(
		// IP or hostname of the CentovaCast server.
		'host'		=> '198.27.112.218',

		'db_user'	=> 'centova',
		'db_pass'	=> 'PVLRadio2013!',
		'db_name'	=> 'centova',

		// Time zone to use when submitting requests.
		'timezone'	=> 'US/Eastern',
	),

	// ReCAPTCHA Service keys.
	'recaptcha' => array(
		'public_key' => '6LfE7eASAAAAADg6R11mHJaFdiGKj_KNB55kB-A4',
		'private_key' => '6LfE7eASAAAAAIH3Wn8LUhEUihib4uO2qDxg64n7',
	),

);