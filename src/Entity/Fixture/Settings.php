<?php
namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class Settings extends AbstractFixture
{
    public function load(ObjectManager $em)
    {
        $settings = [
            Entity\Settings::BASE_URL => getenv('INIT_BASE_URL') ?? 'docker.local',
            Entity\Settings::INSTANCE_NAME => getenv('INIT_INSTANCE_NAME') ?? 'local test',
            Entity\Settings::PREFER_BROWSER_URL => 1,
            Entity\Settings::SETUP_COMPLETE => time(),
            Entity\Settings::USE_RADIO_PROXY => 1,
            Entity\Settings::SEND_ERROR_REPORTS => 0,
            Entity\Settings::CENTRAL_UPDATES => Entity\Settings::UPDATES_NONE,
            Entity\Settings::EXTERNAL_IP => '127.0.0.1',
        ];

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(Entity\Settings::class);

        foreach ($settings as $setting_key => $setting_value) {
            $settings_repo->setSetting($setting_key, $setting_value);
        }
    }
}
