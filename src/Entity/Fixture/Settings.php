<?php

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class Settings extends AbstractFixture
{
    public function load(ObjectManager $em): void
    {
        $settings = [
            'baseUrl' => getenv('INIT_BASE_URL') ?? 'docker.local',
            'instanceName' => getenv('INIT_INSTANCE_NAME') ?? 'local test',
            'geoliteLicenseKey' => getenv('INIT_GEOLITE_LICENSE_KEY') ?? '',
            'setupCompleteTime' => time(),
            'preferBrowserUrl' => true,
            'useRadioProxy' => true,
            'checkForUpdates' => true,
            'externalIp' => '127.0.0.1',
            'enableAdvancedFeatures' => true,
        ];

        $isDemoMode = (!empty(getenv('INIT_DEMO_API_KEY') ?? ''));
        if ($isDemoMode) {
            $settings['analytics'] = Entity\Analytics::LEVEL_NO_IP;
            $settings['checkForUpdates'] = false;
            $settings['publicCustomJs'] = <<<'JS'
                $(function() {
                  if ($('body').hasClass('login-content')) {
                    $('input[name="username"]').val('demo@azuracast.com');
                    $('input[name="password"]').val('demo');
                  }
                });
            JS;
        }

        foreach ($settings as $setting_key => $setting_value) {
            $record = new Entity\SettingsTable($setting_key);
            $record->setSettingValue($setting_value);
            $em->persist($record);
        }

        $em->flush();
    }
}
