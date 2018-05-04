<?php
namespace Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Entity;

class Settings extends AbstractFixture
{
    public function load(ObjectManager $em)
    {
        $settings = [
            'base_url' => getenv('INIT_BASE_URL') ?? 'docker.local',
            'gmaps_api_key' => getenv('INIT_GMAPS_API_KEY') ?? '',
            'instance_name' => getenv('INIT_INSTANCE_NAME') ?? 'local test',
            'setup_complete' => time(),
            'use_radio_proxy' => 1,
        ];

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(Entity\Settings::class);

        foreach($settings as $setting_key => $setting_value) {
            $settings_repo->setSetting($setting_key, $setting_value);
        }
    }
}