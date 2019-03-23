<?php
namespace App\Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity;

class Settings extends AbstractFixture
{
    public function load(ObjectManager $em)
    {
        $settings = [
            Entity\Settings::BASE_URL   => getenv('INIT_BASE_URL') ?? 'docker.local',
            Entity\Settings::INSTANCE_NAME => getenv('INIT_INSTANCE_NAME') ?? 'local test',
            Entity\Settings::SETUP_COMPLETE => time(),
            Entity\Settings::USE_RADIO_PROXY => 1,
            Entity\Settings::SEND_ERROR_REPORTS => 1,
        ];

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(Entity\Settings::class);

        foreach($settings as $setting_key => $setting_value) {
            $settings_repo->setSetting($setting_key, $setting_value);
        }
    }
}
