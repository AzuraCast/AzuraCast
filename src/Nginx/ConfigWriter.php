<?php

declare(strict_types=1);

namespace App\Nginx;

use App\Event\Nginx\WriteNginxConfiguration;
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

        $shortCode = $station->getShortName();

        $frontendConfig = $station->getFrontendConfig();
        $port = $frontendConfig->getPort();

        $event->appendBlock(
            <<<NGINX
            # Reverse proxy the frontend broadcast.
            location ~ ^/listen/{$shortCode}(/?)(.*)\$ {
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
}
