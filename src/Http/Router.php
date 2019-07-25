<?php
namespace App\Http;

use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class Router extends \Azura\Http\Router
{
    /**
     * @inheritDoc
     */
    public function getBaseUrl(bool $use_request = true): UriInterface
    {
        $base_url = new Uri('');

        /** @var SettingsRepository $settings_repo */
        $settings_repo = $this->container[SettingsRepository::class];

        $settings_base_url = $settings_repo->getSetting(Settings::BASE_URL, '');
        if (!empty($settings_base_url)) {
            $base_url = new Uri('http://'.$settings_base_url);
        }

        $use_https = (bool)$settings_repo->getSetting(Settings::ALWAYS_USE_SSL, 0);

        if ($use_request && $this->current_request instanceof Request) {
            $current_uri = $this->current_request->getUri();

            if ('https' === $current_uri->getScheme()) {
                $use_https = true;
            }

            $prefer_browser_url = (bool)$settings_repo->getSetting(Settings::PREFER_BROWSER_URL, 0);
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
