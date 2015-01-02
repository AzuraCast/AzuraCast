<?php
/**
 * Configuration for PVL Third-Party APIs.
 */

return array(

	// PVL deployment API sent by this application.
	'pvl_api_key'       => '52e067cbb156ea70b15a95b9501d0b61',

	// PVL deployment API keys accepted by this application.
	'pvl_api_keys'      => array(
		'PVLAPIAccess_20140605', // 52e067cbb156ea70b15a95b9501d0b61
	),

    // PVLNode Live update service locations.
    'pvlnode_local_url'  => 'http://localhost:4001/data',
    'pvlnode_remote_url' => 'dev.pvlive.me',
    'pvlnode_remote_path' => '/dev/live',

	// Mandrill SMTP service.
	'smtp' => array(
		'server'		=> 'smtp.mandrillapp.com',
    	'port'			=> '587',
    	'auth'			=> 'login',
    	'username'		=> 'loobalightdark@gmail.com',
    	'password'		=> 'd05MxdhMxGxq7i8HRh8_mg',
	),

    // Google Common APIs server key (get from https://console.developers.google.com/)
    'google_apis_key' => 'AIzaSyCQkwi2pVjtmV4ZXgobsbyQcZYEkOZY9c4',

	// Twitter API settings.
	'twitter' => array(
		'consumer_key'  => 'dekLAskiLF8nrTZI3zmmg',
	    'consumer_secret' => 'J6OaNpKHlDmrQLEmvxfdlRWO4E7WbyNnBTdpz1njLcw',
	    'user_token'    => '974916638-1jK4vgMYvv9pAc2gQfAYGcnDY58xTij5M42P93VU',
	    'user_secret'   => 'TTDLFrhcULlU3a9uYxIbdW5DZxx4TsCfOlf9sWuVlY4',

	    'curl_ssl_verifyhost' => 0,
	    'curl_ssl_verifypeer' => false,
	),

	// Notifico settings.
	'notifico_push_url' => 'http://n.tkte.ch/h/3254/6XP7inz2mdedPggNN8oeWpaU',

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

			"Poniverse" => array(
		                "enabled"   => true,
                		"keys"      => array(
		                    "id" => 'B0H3Z647MoD4qg047i2Io87Jq77ydcdC',
		                    "secret" => '8R1aX5G88QMUU848gDluzxcC14Hec1S7',
		                ),
		        ),
		),
	),

	// CentovaCast API settings.
	'centovacast' => array(
		// IP or hostname of the CentovaCast server.
		'host'		=> '162.243.167.103',

		'db_user'	=> 'centova',
		'db_pass'	=> 'MZpXJhfv',
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
