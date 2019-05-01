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

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Session */
    protected $session;

    /**
     * @param Session $session
     * @param Entity\Repository\ApiKeyRepository $api_repo
     * @param Entity\Repository\SettingsRepository $settings_repo
     *
     * @see \App\Provider\MiddlewareProvider
     */
    public function __construct(
        Session $session,
        Entity\Repository\ApiKeyRepository $api_repo,
        Entity\Repository\SettingsRepository $settings_repo
    ) {
        $this->session = $session;
        $this->api_repo = $api_repo;
        $this->settings_repo = $settings_repo;
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
        $request = $request->withAttribute(Request::ATTRIBUTE_IS_API_CALL, true);

        // Attempt API key auth if a key exists.
        $api_key = $this->getApiKey($request);
        $api_user = (!empty($api_key)) ? $this->api_repo->authenticate($api_key) : null;

        // Override the request's "user" variable if API authentication is supplied and valid.
        if ($api_user instanceof Entity\User) {
            $request = $request->withAttribute(Request::ATTRIBUTE_USER, $api_user);
        }

        // Check for a user-set CORS header override.
        $acao_header = trim($this->settings_repo->getSetting(Entity\Settings::API_ACCESS_CONTROL));
        if (!empty($acao_header)) {
            if ('*' === $acao_header) {
                $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            } else {
                // Return the proper ACAO header matching the origin (if one exists).
                $origin = $request->getHeaderLine('Origin');
                if (!empty($origin)) {
                    $origins = array_map('trim', explode(',', $acao_header));

                    $base_url = $this->settings_repo->getSetting(Entity\Settings::BASE_URL);
                    $origins[] = 'http://'.$base_url;
                    $origins[] = 'https://'.$base_url;

                    if (in_array($origin, $origins, true)) {
                        $response
                            ->withHeader('Access-Control-Allow-Origin', $origin)
                            ->withHeader('Vary', 'Origin');
                    }
                }
            }
        } else if ($api_user instanceof Entity\User || $request->isGet() || $request->isOptions()) {
            // Default behavior:
            // Only set global CORS for GET requests and API-authenticated requests;
            // Session-authenticated, non-GET requests should only be made in a same-host situation.
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        // Set default cache control for API pages.
        $prefer_browser_url = (bool)$this->settings_repo->getSetting(Entity\Settings::PREFER_BROWSER_URL, 0);

        if ($prefer_browser_url || $request->getAttribute(Request::ATTRIBUTE_USER) instanceof Entity\User) {
            $response = $response->withNoCache();
        } else {
            $response = $response->withCacheLifetime(15);
        }

        return $next($request, $response);
    }

    protected function getApiKey(Request $request): ?string
    {
        // Check authorization header
        $auth_headers = $request->getHeader('Authorization');
        $auth_header = $auth_headers[0] ?? '';

        if (preg_match("/Bearer\s+(.*)$/i", $auth_header, $matches)) {
            return $matches[1];
        }

        // Check API key header
        $api_key_headers = $request->getHeader('X-API-Key');
        if (!empty($api_key_headers[0])) {
            return $api_key_headers[0];
        }

        // Check cookies
        $cookieParams = $request->getCookieParams();
        return $cookieParams['token'] ?? null;
    }
}
