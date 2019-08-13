<?php
namespace App\Middleware\Module;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handle API calls and wrap exceptions in JSON formatting.
 */
class Api
{
    /** @var Entity\Repository\ApiKeyRepository */
    protected $api_repo;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->api_repo = $em->getRepository(Entity\ApiKey::class);
        $this->settings_repo = $em->getRepository(Entity\Settings::class);
    }

    /**
     * @param ServerRequest $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Prevent unnecessary session creation on API pages from flooding the session databases
        $session = $request->getSession();

        if (!$session->exists()) {
            $session->disable();
        }

        // Set "is API call" attribute on the request so error handling responds correctly.
        $request = $request->withAttribute(ServerRequest::ATTR_IS_API_CALL, true);

        // Attempt API key auth if a key exists.
        $api_key = $this->getApiKey($request);
        $api_user = (!empty($api_key)) ? $this->api_repo->authenticate($api_key) : null;

        // Override the request's "user" variable if API authentication is supplied and valid.
        if ($api_user instanceof Entity\User) {
            $request = $request->withAttribute(ServerRequest::ATTR_USER, $api_user);

            Entity\AuditLog::setCurrentUser($api_user);
        }

        // Set default cache control for API pages.
        $prefer_browser_url = (bool)$this->settings_repo->getSetting(Entity\Settings::PREFER_BROWSER_URL, 0);

        $response = $handler->handle($request);

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
        } else if ($api_user instanceof Entity\User || in_array($request->getMethod(), ['GET', 'OPTIONS'])) {
            // Default behavior:
            // Only set global CORS for GET requests and API-authenticated requests;
            // Session-authenticated, non-GET requests should only be made in a same-host situation.
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        if ($response instanceof Response) {
            if ($prefer_browser_url || $request->getAttribute(ServerRequest::ATTR_USER) instanceof Entity\User) {
                $response = $response->withNoCache();
            } else {
                $response = $response->withCacheLifetime(15);
            }
        }

        return $response;
    }

    /**
     * @param ServerRequest $request
     * @return string|null
     */
    protected function getApiKey(ServerRequest $request): ?string
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
