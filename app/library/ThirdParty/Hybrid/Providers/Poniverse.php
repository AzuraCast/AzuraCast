<?php
class Hybrid_Providers_Poniverse extends Hybrid_Provider_Model_OAuth2
{
    function initialize()
    {
        parent::initialize();

        // Provider api end-points
        $this->api->authorize_url  = 'https://poniverse.net/oauth/authorize';
        $this->api->token_url      = 'https://poniverse.net/oauth/access_token';
        $this->api->token_info_url = 'http://api.poniverse.net/v1/users/me';
    }

    /**
     * begin login step
     */
    function loginBegin()
    {
        $parameters = array("scope" => $this->scope, "access_type" => "offline");
        $optionals  = array("scope", "access_type", "redirect_uri", "approval_prompt", "hd");

        foreach ($optionals as $parameter){
            if( isset( $this->config[$parameter] ) && ! empty( $this->config[$parameter] ) ){
                $parameters[$parameter] = $this->config[$parameter];
            }
        }

        Hybrid_Auth::redirect( $this->api->authorizeUrl( array() ) );
    }

    /**
     * load the user profile from the IDp api client
     */
    function getUserProfile()
    {
        // refresh tokens if needed
        $this->refreshToken();

        // ask google api for user infos
        $response = $this->api->api('http://api.poniverse.net/v1/users/me');

        if ( ! isset( $response->id ) || isset( $response->error ) ){
            throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
        }

        $this->user->profile->identifier    = $response->id;
        $this->user->profile->displayName   = $response->display_name;
        $this->user->profile->email         = $response->email;

        return $this->user->profile;
    }
}
