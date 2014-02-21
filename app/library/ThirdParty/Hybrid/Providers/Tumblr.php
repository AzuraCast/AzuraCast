<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
* Hybrid_Providers_Tumblr 
*/
class Hybrid_Providers_Tumblr extends Hybrid_Provider_Model_OAuth1
{
   	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// provider api end-points
		$this->api->api_base_url      = "http://api.tumblr.com/v2/";
		$this->api->authorize_url     = "http://www.tumblr.com/oauth/authorize";
		$this->api->request_token_url = "http://www.tumblr.com/oauth/request_token";
		$this->api->access_token_url  = "http://www.tumblr.com/oauth/access_token";

		$this->api->curl_auth_header  = false;
	}


   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		try{  
			$profile = $this->api->get( 'user/info' );

			foreach ( $profile->response->user->blogs as $blog ){
				if( $blog->primary ){
					$bloghostname = explode( '://', $blog->url );
					$bloghostname = substr( $bloghostname[1], 0, -1);

					// store the user primary blog base hostname
					$this->token( "primary_blog" , $bloghostname );

					$this->user->profile->identifier 	= $blog->url;
					$this->user->profile->displayName	= $profile->response->user->name;
					$this->user->profile->profileURL	= $blog->url;
					$this->user->profile->webSiteURL	= $blog->url;
					$this->user->profile->description	= strip_tags( $blog->description );

					$avatar = $this->api->get( 'blog/'. $this->token( "primary_blog" ) .'/avatar' );

					$this->user->profile->photoURL 		= $avatar->response->avatar_url;

					break; 
				}
			} 
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
		}	
	
		return $this->user->profile;
 	}

   	/**
	* post to tumblr
	*/ 
	function setUserStatus( $status )
	{
		$parameters = array( 'type' => "text", 'body' => $status ); 
		$response  = $this->api->post( "blog/" . $this->token( "primary_blog" ) . '/post', $parameters );  

		if ( $response->meta->status != 201 ){
			throw new Exception( "Update user status failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $response->meta->status ) );
		} 
	}
}
