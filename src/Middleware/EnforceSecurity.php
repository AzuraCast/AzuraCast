<?php
namespace App\Middleware;

use App\Assets;
use App\Entity;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Remove trailing slash from all URLs when routing.
 */
class EnforceSecurity
{
    /** @var EntityManager */
    protected $em;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Assets */
    protected $assets;

    public function __construct(EntityManager $em, Assets $assets)
    {
        $this->em = $em;
        $this->settings_repo = $this->em->getRepository(Entity\Settings::class);

        $this->assets = $assets;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $always_use_ssl = (bool)$this->settings_repo->getSetting('always_use_ssl', 0);
        $internal_api_url = mb_stripos($request->getUri()->getPath(), '/api/internal') === 0;

        $uri = $request->getUri();
        $uri_is_https = ($uri->getScheme() === 'https');

        // Assemble Content Security Policy (CSP)
        $csp = [];

        if ($uri_is_https) {

            $csp[] = 'upgrade-insecure-requests';

        } elseif ($always_use_ssl && !$internal_api_url) {

            // Enforce secure cookies.
            ini_set('session.cookie_secure', 1);

            // Redirect if URL is not currently secure.
            if (!$uri_is_https) {
                if (!$uri->getPort()) {
                    $uri = $uri->withPort(443);
                }
                return $response->withRedirect((string)$uri->withScheme('https'), 302);
            }

            // Set HSTS header.
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=3600');

            $csp[] = 'upgrade-insecure-requests';
        }

        // Set frame-deny header before next middleware, so it can be overwritten.
        $response = $response->withHeader('X-Frame-Options', 'DENY');

        /** @var Response $response */
        $response = $next($request, $response);

        // CSP JavaScript policy
        // Note: unsafe-eval included for Vue template compiling
        $csp_script_src = (array)$this->assets->getCspDomains();
        $csp_script_src[] = "'self'";
        $csp_script_src[] = "'unsafe-eval'";
        $csp_script_src[] = "'nonce-".$this->assets->getCspNonce()."'";

        $csp[] = "script-src ".implode(' ', $csp_script_src);

        return $response->withHeader('Content-Security-Policy', implode('; ', $csp));
    }
}
