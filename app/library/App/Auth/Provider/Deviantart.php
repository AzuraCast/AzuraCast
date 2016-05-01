<?php
namespace App\Auth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 *
 */
class Deviantart extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://www.deviantart.com/api/v1/oauth2/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://www.deviantart.com/oauth2/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://www.deviantart.com/oauth2/token';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user/whoami');

        $data = new Data\Collection($response);

        if (! $data->exists('userid')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('userid');

        $name_parts = explode(' ', $data->filter('profile')->get('real_name'));
        $userProfile->firstName   = $name_parts[0];
        $userProfile->lastName    = implode(' ', array_splice($name_parts, 1));

        $userProfile->displayName = $data->get('username');
        $userProfile->photoURL    = $data->get('usericon');
        $userProfile->profileURL  = 'http://'.$data->get('username').'.deviantart.com/';
        $userProfile->description = $data->filter('profile')->get('tagline');
        $userProfile->gender      = $data->filter('details')->get('sex');
        $userProfile->country     = $data->filter('geo')->get('country');

        return $userProfile;
    }
}
