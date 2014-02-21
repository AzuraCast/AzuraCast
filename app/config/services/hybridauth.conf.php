<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// ----------------------------------------------------------------------------------------
//	HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

return array(
	"base_url" => \DF\Url::baseUrl(TRUE),

	"providers" => array ( 
		// openid providers
		"OpenID" => array (
			"enabled" => true
		),

		/*
		"Yahoo" => array ( 
			"enabled" => true,
			"keys"    => array ( "id" => "", "secret" => "" ),
		),

		"AOL"  => array ( 
			"enabled" => true 
		),
		*/

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

		/*
		// windows live
		"Live" => array ( 
			"enabled" => true,
			"keys"    => array ( "id" => "", "secret" => "" ) 
		),

		"MySpace" => array ( 
			"enabled" => true,
			"keys"    => array ( "key" => "", "secret" => "" ) 
		),

		"LinkedIn" => array ( 
			"enabled" => true,
			"keys"    => array ( "key" => "", "secret" => "" ) 
		),

		"Foursquare" => array (
			"enabled" => true,
			"keys"    => array ( "id" => "", "secret" => "" ) 
		),
		*/
	),

	// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
	"debug_mode" => false,
	"debug_file" => "",
);