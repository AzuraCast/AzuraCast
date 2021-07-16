<?php

declare(strict_types=1);

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
    public function __construct(
        protected UpdateGeoLiteTask $syncTask,
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        Config $config,
    ) {
        $formConfig = $config->get('forms/install_geolite');

        parent::__construct($settingsRepo, $environment, $em, $serializer, $validator, $formConfig);
    }
}
