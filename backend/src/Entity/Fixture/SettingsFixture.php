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

        $settings->base_url = (string)(getenv('INIT_BASE_URL') ?: 'http://docker.local');
        $settings->instance_name = (string)(getenv('INIT_INSTANCE_NAME') ?: 'local test');
        $settings->geolite_license_key = (string)(getenv('INIT_GEOLITE_LICENSE_KEY') ?: '');
        $settings->last_fm_api_key = (string)(getenv('INIT_LASTFM_API_KEY') ?: '');

        $settings->setup_complete_time = time();
        $settings->prefer_browser_url = true;
        $settings->use_radio_proxy = true;
        $settings->check_for_updates = true;
        $settings->external_ip = '127.0.0.1';
        $settings->enable_static_nowplaying = true;

        if (!empty(getenv('INIT_DEMO_API_KEY') ?: '')) {
            $settings->analytics = AnalyticsLevel::NoIp;
            $settings->check_for_updates = false;

            $settings->public_custom_js = <<<'JS'
                (() => {
                    document.addEventListener('vue-ready', () => {
                        var form = document.getElementById('login-form');
                        if (form) {
                            document.querySelector('input[name="username"]').value = 'demo@azuracast.com';
                            document.querySelector('input[name="password"]').value = 'demo';
                        }
                    });
                })();
            JS;
        }

        $manager->persist($settings);
        $manager->flush();
    }
}
