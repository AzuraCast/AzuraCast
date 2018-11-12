<?php
namespace App\Http;

use App\Entity\Repository\SettingsRepository;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class Router extends \Azura\Http\Router
{
    /**
     * Dynamically calculate the base URL the first time it's called, if it is at all in the request.
     *
     * @return UriInterface
     */
    public function getBaseUrl(): UriInterface
    {
        static $base_url;

        if (!$base_url)
        {
            $base_url = new Uri('');

            /** @var SettingsRepository $settings_repo */
            $settings_repo = $this->container[SettingsRepository::class];

            $settings_base_url = $settings_repo->getSetting('base_url', '');

            if (!empty($settings_base_url)) {
                $base_url = new Uri('http://'.$settings_base_url);
            }

            $use_https = (bool)$settings_repo->getSetting('always_use_ssl', 0);

            if ($this->current_request instanceof Request) {
                $current_uri = $this->current_request->getUri();

                if ('https' === $current_uri->getScheme()) {
                    $use_https = true;
                }

                $prefer_browser_url = (bool)$settings_repo->getSetting('prefer_browser_url', 0);
                if ($prefer_browser_url || $base_url->getHost() === '') {
                    $ignored_hosts = ['nginx', 'localhost'];
                    if (!in_array($current_uri->getHost(), $ignored_hosts)) {
                        $base_url = (new Uri())
                            ->withScheme($current_uri->getScheme())
                            ->withHost($current_uri->getHost())
                            ->withPort($current_uri->getPort());
                    }
                }
            }

            $base_url = $base_url->withScheme($use_https ? 'https' : 'http');
        }

        return $base_url;
    }
}
