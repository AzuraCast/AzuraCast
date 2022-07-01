<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

final class Settings extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($manager->getRepository(Entity\Settings::class)->findAll() as $row) {
            $manager->remove($row);
        }

        $settings = new Entity\Settings();
        $settings->setBaseUrl((string)(getenv('INIT_BASE_URL') ?: 'http://docker.local'));
        $settings->setInstanceName((string)(getenv('INIT_INSTANCE_NAME') ?: 'local test'));
        $settings->setGeoliteLicenseKey((string)(getenv('INIT_GEOLITE_LICENSE_KEY') ?: ''));

        $settings->setSetupCompleteTime(time());
        $settings->setPreferBrowserUrl(true);
        $settings->setUseRadioProxy(true);
        $settings->setCheckForUpdates(true);
        $settings->setExternalIp('127.0.0.1');
        $settings->setEnableAdvancedFeatures(true);

        if (!empty(getenv('INIT_DEMO_API_KEY') ?: '')) {
            $settings->setAnalytics(Entity\Enums\AnalyticsLevel::NoIp->value);
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
