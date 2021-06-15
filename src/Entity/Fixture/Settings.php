<?php

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class Settings extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($manager->getRepository(Entity\Settings::class)->findAll() as $row) {
            $manager->remove($row);
        }

        $settings = new Entity\Settings();
        $settings->setBaseUrl(getenv('INIT_BASE_URL') ?? 'docker.local');
        $settings->setInstanceName(getenv('INIT_INSTANCE_NAME') ?? 'local test');
        $settings->setGeoliteLicenseKey(getenv('INIT_GEOLITE_LICENSE_KEY') ?? '');

        $settings->setSetupCompleteTime(time());
        $settings->setPreferBrowserUrl(true);
        $settings->setUseRadioProxy(true);
        $settings->setCheckForUpdates(true);
        $settings->setExternalIp('127.0.0.1');
        $settings->setEnableAdvancedFeatures(true);

        $isDemoMode = (!empty(getenv('INIT_DEMO_API_KEY') ?? ''));
        if ($isDemoMode) {
            $settings->setAnalytics(Entity\Analytics::LEVEL_NO_IP);
            $settings->setCheckForUpdates(false);

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

        $manager->persist($settings);
        $manager->flush();
    }
}
