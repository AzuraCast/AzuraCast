<?php

namespace App\Form;

use App\Config;
use App\Entity;
use App\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BrandingSettingsForm extends AbstractSettingsForm
{
    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        Config $config
    ) {
        $formConfig = $config->get(
            'forms/branding',
            [
                'settings' => $environment,
            ]
        );

        parent::__construct($settingsRepo, $environment, $em, $serializer, $validator, $formConfig);
    }
}
