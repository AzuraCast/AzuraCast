<?php

namespace App\Form;

use App\Config;
use App\Entity;
use App\Environment;
use App\Sync\Task\UpdateGeoLiteTask;
use Doctrine\ORM\EntityManagerInterface;

class GeoLiteSettingsForm extends AbstractSettingsForm
{
    protected UpdateGeoLiteTask $syncTask;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        Config $config,
        UpdateGeoLiteTask $syncTask
    ) {
        $formConfig = $config->get('forms/install_geolite');

        parent::__construct(
            $em,
            $settingsRepo,
            $environment,
            $formConfig
        );

        $this->syncTask = $syncTask;
    }
}
