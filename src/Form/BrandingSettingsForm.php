<?php

namespace App\Form;

use App\Config;
use App\Entity;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;

class BrandingSettingsForm extends AbstractSettingsForm
{
    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Settings $settings,
        Config $config
    ) {
        $formConfig = $config->get('forms/branding', [
            'settings' => $settings,
        ]);

        parent::__construct(
            $em,
            $settingsRepo,
            $settings,
            $formConfig
        );
    }
}
