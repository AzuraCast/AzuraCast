<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Enums\AnalyticsLevel;
use App\Entity\Settings;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

final class SettingsFixture extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($manager->getRepository(Settings::class)->findAll() as $row) {
            $manager->remove($row);
        }

        $settings = new Settings();
        $settings->setBaseUrl((string)(getenv('INIT_BASE_URL') ?: 'http://docker.local'));
        $settings->setInstanceName((string)(getenv('INIT_INSTANCE_NAME') ?: 'local test'));
        $settings->setGeoliteLicenseKey((string)(getenv('INIT_GEOLITE_LICENSE_KEY') ?: ''));

        $settings->setSetupCompleteTime(time());
        $settings->setPreferBrowserUrl(true);
        $settings->setUseRadioProxy(true);
        $settings->setCheckForUpdates(true);
        $settings->setExternalIp('127.0.0.1');
        $settings->setEnableAdvancedFeatures(true);
        $settings->setEnableStaticNowPlaying(true);

        if (!empty(getenv('INIT_DEMO_API_KEY') ?: '')) {
            $settings->setAnalytics(AnalyticsLevel::NoIp);
            $settings->setCheckForUpdates(false);

            $settings->setPublicCustomJs(
                <<<'JS'
                (() => {
                    document.addEventListener('vue-ready', () => {
                        var form = document.getElementById('login-form');
                        if (form) {
                            document.querySelector('input[name="username"]').value = 'demo@azuracast.com';
                            document.querySelector('input[name="password"]').value = 'demo';
                        }
                    });
                })();
            JS
            );
        }

        $manager->persist($settings);
        $manager->flush();
    }
}
