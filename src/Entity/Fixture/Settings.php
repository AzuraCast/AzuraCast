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
            Entity\Settings::BASE_URL => getenv('INIT_BASE_URL') ?? 'docker.local',
            Entity\Settings::INSTANCE_NAME => getenv('INIT_INSTANCE_NAME') ?? 'local test',
            Entity\Settings::GEOLITE_LICENSE_KEY => getenv('INIT_GEOLITE_LICENSE_KEY') ?? '',
            Entity\Settings::PREFER_BROWSER_URL => 1,
            Entity\Settings::SETUP_COMPLETE => time(),
            Entity\Settings::USE_RADIO_PROXY => 1,
            Entity\Settings::CENTRAL_UPDATES => Entity\Settings::UPDATES_NONE,
            Entity\Settings::EXTERNAL_IP => '127.0.0.1',
        ];

        $isDemoMode = (!empty(getenv('INIT_DEMO_API_KEY') ?? ''));
        if ($isDemoMode) {
            $settings[Entity\Settings::LISTENER_ANALYTICS] = Entity\Analytics::LEVEL_NO_IP;
            $settings[Entity\Settings::CUSTOM_JS_PUBLIC] = <<<EOF
            $(function() {
              if ($('body').hasClass('login-content')) {
                $('input[name="username"]').val('demo@azuracast.com');
                $('input[name="password"]').val('demo');
              }
            });
            EOF;
        }

        foreach ($settings as $setting_key => $setting_value) {
            $record = new Entity\Settings($setting_key);
            $record->setSettingValue($setting_value);
            $em->persist($record);
        }

        $em->flush();
    }
}
