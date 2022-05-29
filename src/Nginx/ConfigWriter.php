<?php

declare(strict_types=1);

namespace App\Nginx;

use App\Event\Nginx\WriteNginxConfiguration;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConfigWriter implements EventSubscriberInterface
{
    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WriteNginxConfiguration::class => [
                ['writeRadioSection', 35],
                ['writeWebDjSection', 30],
            ],
        ];
    }

    public function writeRadioSection(WriteNginxConfiguration $event): void
    {
        $station = $event->getStation();

        // Only forward local radio
        if (FrontendAdapters::Remote === $station->getFrontendTypeEnum()) {
            return;
        }

        $listenBaseUrl = CustomUrls::getListenUrl($station);

        $port = $station->getFrontendConfig()->getPort();

        $event->appendBlock(
            <<<NGINX
            # Reverse proxy the frontend broadcast.
            location ~ ^{$listenBaseUrl}(/?)(.*)\$ {
                include proxy_params;
                
                proxy_intercept_errors    on;
                proxy_next_upstream       error timeout invalid_header;
                proxy_redirect            off;
                proxy_connect_timeout     60;
                
                proxy_set_header Host localhost:{$port};
                proxy_pass http://127.0.0.1:{$port}/\$2?\$args;
            }
            NGINX
        );
    }

    public function writeWebDjSection(WriteNginxConfiguration $event): void
    {
        $station = $event->getStation();

        // Only forward Liquidsoap
        if (BackendAdapters::Liquidsoap !== $station->getBackendTypeEnum()) {
            return;
        }

        $webDjBaseUrl = CustomUrls::getWebDjUrl($station);

        $autoDjPort = $station->getBackendConfig()->getDjPort();

        $event->appendBlock(
            <<<NGINX
            # Reverse proxy the WebDJ connection.
            location ~ ^{$webDjBaseUrl}(/?)(.*)\$ {
                include proxy_params;

                proxy_pass http://127.0.0.1:{$autoDjPort}/$2;
            }
            NGINX
        );
    }
}
