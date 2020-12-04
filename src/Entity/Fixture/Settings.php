<?php

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class Settings extends AbstractFixture
{
    public function load(ObjectManager $em): void
    {
        $settings = new Entity\Settings();

        $settings->setBaseUrl(getenv('INIT_BASE_URL') ?? 'docker.local');
        $settings->setInstanceName(getenv('INIT_INSTANCE_NAME') ?? 'local test');
        $settings->setGeoliteLicenseKey(getenv('INIT_GEOLITE_LICENSE_KEY') ?? '');
        $settings->setPreferBrowserUrl(true);
        $settings->updateSetupComplete();
        $settings->setUseRadioProxy(true);
        $settings->setCheckForUpdates(true);
        $settings->setExternalIp('127.0.0.1');

        $isDemoMode = (!empty(getenv('INIT_DEMO_API_KEY') ?? ''));
        if ($isDemoMode) {
            $settings->setAnalytics(Entity\Analytics::LEVEL_NO_IP);
            $settings->setPublicCustomJs(
                <<<'JS'
                $(function() {
                  if ($('body').hasClass('login-content')) {
                    $('input[name="username"]').val('demo@azuracast.com');
                    $('input[name="password"]').val('demo');
                  }
                });
                JS
            );
        }

        $settings = json_decode(json_encode($settings), true);

        foreach ($settings as $setting_key => $setting_value) {
            $record = new Entity\SettingsTable($setting_key);
            $record->setSettingValue($setting_value);
            $em->persist($record);
        }

        $em->flush();
    }
}
