<?php

namespace App\Form;

use App\Config;
use App\Entity;
use App\Settings;
use App\Sync\Task\UpdateGeoLiteDatabase;
use Doctrine\ORM\EntityManagerInterface;

class GeoLiteSettingsForm extends AbstractSettingsForm
{
    protected UpdateGeoLiteDatabase $syncTask;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Settings $settings,
        Config $config,
        UpdateGeoLiteDatabase $syncTask
    ) {
        $formConfig = $config->get('forms/install_geolite');

        parent::__construct(
            $em,
            $settingsRepo,
            $settings,
            $formConfig
        );

        $this->syncTask = $syncTask;
    }
}
