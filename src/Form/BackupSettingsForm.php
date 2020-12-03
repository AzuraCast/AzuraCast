<?php

namespace App\Form;

use App\Config;
use App\Entity;
use App\Environment;
use Doctrine\ORM\EntityManagerInterface;

class BackupSettingsForm extends AbstractSettingsForm
{
    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        Environment $settings,
        Config $config
    ) {
        $formConfig = $config->get('forms/backup', [
            'settings' => $settings,
            'storageLocations' => $storageLocationRepo->fetchSelectByType(Entity\StorageLocation::TYPE_BACKUP, true),
        ]);

        parent::__construct(
            $em,
            $settingsRepo,
            $settings,
            $formConfig
        );
    }
}
