<?php
namespace App\Middleware\Module;

use App\Entity;
use Azura\Session;
use App\Http\Request;
use App\Http\Response;

/**
 * Handle API calls and wrap exceptions in JSON formatting.
 */
class Api
{
    /** @var Entity\Repository\ApiKeyRepository */
    protected $api_repo;

    /** @var Session */
    protected $session;

    public function __construct(Session $session, Entity\Repository\ApiKeyRepository $api_repo)
    {
        $this->session = $session;
        $this->api_repo = $api_repo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        // Prevent unnecessary session creation on API pages from flooding the session databases
        if (!$this->session->exists()) {
            $this->session->disable();
        }

        // Set "is API call" attribute on the request so error handling responds correctly.
        $request = $request->withAttribute('is_api_call', true);

        // Attempt API key auth if a key exists.
        $api_key = $this->getApiKey($request);
        $api_user = (!empty($api_key)) ? $this->api_repo->authenticate($api_key) : null;

        // Override the request's "user" variable if API authentication is supplied and valid.
        if ($api_user instanceof Entity\User) {
            $request = $request->withAttribute(Request::ATTRIBUTE_USER, $api_user);
        }

        // Only set global CORS for GET requests and API-authenticated requests;
        // Session-authenticated, non-GET requests should only be made in a same-host situation.
        if ($api_user instanceof Entity\User || $request->isGet()) {
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        // Set default cache control for API pages.
        $response = $response->withCacheLifetime(30);

        // Custom error handling for API responses.
        return $next($request, $response);
    }

    protected function getApiKey(Request $request): ?string
    {
        // Check authorization header
        $auth_headers = $request->getHeader('Authorization');
        $auth_header = $auth_headers[0] ?? "";

        if (preg_match("/Bearer\s+(.*)$/i", $auth_header, $matches)) {
            return $matches[1];
        }

        // Check API key header
        $api_key_headers = $request->getHeader('X-API-Key');
        if ($api_key_headers[0]) {
            return $api_key_headers[0];
        }

        // Check cookies
        $cookieParams = $request->getCookieParams();
        if (isset($cookieParams['token'])) {
            return $cookieParams['token'];
        };

        return null;
    }
}
