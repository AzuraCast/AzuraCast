<?php
namespace App\Form;

use App\Entity;
use App\Settings;
use App\Config;
use Doctrine\ORM\EntityManager;

class BrandingSettingsForm extends AbstractSettingsForm
{
    public function __construct(
        EntityManager $em,
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
