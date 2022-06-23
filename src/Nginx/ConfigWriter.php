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
                ['writeHlsSection', 25],
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
            location ~ ^({$listenBaseUrl}|/radio/{$port})(/?)(.*)\$ {
                include proxy_params;
                
                proxy_intercept_errors    on;
                proxy_next_upstream       error timeout invalid_header;
                proxy_redirect            off;
                proxy_connect_timeout     60;
                
                proxy_set_header Host localhost:{$port};
                proxy_pass http://127.0.0.1:{$port}/\$3?\$args;
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
            location ~ ^({$webDjBaseUrl}|/radio/{$autoDjPort})(/?)(.*)\$ {
                include proxy_params;

                proxy_pass http://127.0.0.1:{$autoDjPort}/$3;
            }
            NGINX
        );
    }

    public function writeHlsSection(WriteNginxConfiguration $event): void
    {
        $station = $event->getStation();

        if (!$station->getEnableHls()) {
            return;
        }

        $hlsBaseUrl = CustomUrls::getHlsUrl($station);
        $hlsFolder = $station->getRadioHlsDir();

        $event->appendBlock(
            <<<NGINX
            # Reverse proxy the frontend broadcast.
            location {$hlsBaseUrl} {
                types {
                    application/vnd.apple.mpegurl m3u8;
                    video/mp2t ts;
                }
                
                add_header 'Access-Control-Allow-Origin' '*';
                add_header 'Cache-Control' 'no-cache';
                
                alias {$hlsFolder};
                try_files \$uri =404;
            }
            NGINX
        );
    }
}
