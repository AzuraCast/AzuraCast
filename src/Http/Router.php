<?php
namespace App\Http;

use App\Entity;
use Azura\Settings;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouteParserInterface;

class Router extends \Azura\Http\Router
{
    /** @var Entity\Repository\SettingsRepository */
    protected $settingsRepo;

    /**
     * @param Settings $settings
     * @param RouteParserInterface $route_parser
     * @param EntityManager $em
     */
    public function __construct(
        Settings $settings,
        RouteParserInterface $route_parser,
        EntityManager $em
    ) {
        /** @var Entity\Repository\SettingsRepository $settingsRepo */
        $settingsRepo = $em->getRepository(Entity\Settings::class);
        $this->settingsRepo = $settingsRepo;

        parent::__construct($settings, $route_parser);
    }

    /**
     * @inheritDoc
     */
    public function getBaseUrl(bool $use_request = true): UriInterface
    {
        $base_url = new Uri('');

        $settings_base_url = $this->settingsRepo->getSetting(Entity\Settings::BASE_URL, '');
        if (!empty($settings_base_url)) {
            $base_url = new Uri('http://'.$settings_base_url);
        }

        $use_https = (bool)$this->settingsRepo->getSetting(Entity\Settings::ALWAYS_USE_SSL, 0);

        if ($use_request && $this->current_request instanceof ServerRequestInterface) {
            $current_uri = $this->current_request->getUri();

            if ('https' === $current_uri->getScheme()) {
                $use_https = true;
            }

            $prefer_browser_url = (bool)$this->settingsRepo->getSetting(Entity\Settings::PREFER_BROWSER_URL, 0);
            if ($prefer_browser_url || $base_url->getHost() === '') {
                $ignored_hosts = ['web', 'nginx', 'localhost'];
                if (!in_array($current_uri->getHost(), $ignored_hosts, true)) {
                    $base_url = (new Uri())
                        ->withScheme($current_uri->getScheme())
                        ->withHost($current_uri->getHost())
                        ->withPort($current_uri->getPort());
                }
            }
        }

        $base_url = $base_url->withScheme($use_https ? 'https' : 'http');
        return $base_url;
    }
}
