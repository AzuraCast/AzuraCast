<?php

declare(strict_types=1);

namespace Unit;

use App\Entity\Station;
use App\Event\Nginx\WriteNginxConfiguration;
use App\Nginx\ConfigWriter;
use App\Tests\Module;
use Codeception\Test\Unit;
use UnitTester;

final class NginxConfigWriterTest extends Unit
{
    protected UnitTester $tester;

    private ConfigWriter $configWriter;

    protected function _inject(Module $testsModule): void
    {
        $this->configWriter = $testsModule->container->get(ConfigWriter::class);
    }

    public function testRadioProxyDisablesChunkedTransferEncoding(): void
    {
        $station = new Station();
        $station->name = 'Test Station';

        $frontendConfig = $station->frontend_config;
        $frontendConfig->port = 8000;
        $station->frontend_config = $frontendConfig;

        $event = new WriteNginxConfiguration($station, false);

        $this->configWriter->writeRadioSection($event);

        $configuration = $event->buildConfiguration();

        self::assertStringContainsString(
            'chunked_transfer_encoding off;',
            $configuration
        );

        self::assertStringContainsString(
            'location ~ ^(/listen/test_station|/radio/8000)/(.*)$',
            $configuration
        );

        self::assertStringContainsString(
            'proxy_pass http://127.0.0.1:8000/$2?$args;',
            $configuration
        );
    }
}
