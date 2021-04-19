<?php

namespace App\Form;

use App\Config;
use App\Entity;
use App\Environment;
use App\Sync\Task\UpdateGeoLiteTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GeoLiteSettingsForm extends AbstractSettingsForm
{
    protected UpdateGeoLiteTask $syncTask;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        Config $config,
        UpdateGeoLiteTask $syncTask
    ) {
        $formConfig = $config->get('forms/install_geolite');

        parent::__construct($em, $serializer, $validator, $settingsRepo, $environment, $formConfig);

        $this->syncTask = $syncTask;
    }
}
