<?php

declare(strict_types=1);

namespace App\Form;

use App\Config;
use App\Entity;
use App\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BackupSettingsForm extends AbstractSettingsForm
{
    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        Config $config
    ) {
        $formConfig = $config->get(
            'forms/backup',
            [
                'settings' => $environment,
                'storageLocations' => $storageLocationRepo->fetchSelectByType(
                    Entity\StorageLocation::TYPE_BACKUP,
                    true,
                    __('Select...')
                ),
            ]
        );

        parent::__construct($settingsRepo, $environment, $em, $serializer, $validator, $formConfig);
    }
}
