<?php

declare(strict_types=1);

namespace App\Service\Dropbox;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use LogicException;
use Psr\Http\Message\ResponseInterface;

final class OAuthProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string Key used in the access token response to identify the resource owner.
     */
    public const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'account_id';

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://www.dropbox.com/oauth2/authorize';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://api.dropbox.com/oauth2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return 'https://api.dropbox.com/2/users/get_current_account';
    }

    protected function getDefaultScopes(): array
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @link   https://www.dropbox.com/developers/core/docs
     * @throws IdentityProviderException
     * @param ResponseInterface $response
     * @param array|string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            throw new IdentityProviderException(
                $data['error'] ?: $response->getReasonPhrase(),
                $response->getStatusCode(),
                (string)$response->getBody()
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        throw new LogicException('Not implemented.');
    }
}
