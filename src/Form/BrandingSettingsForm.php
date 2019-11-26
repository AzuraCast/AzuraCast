<?php
namespace App\Form;

use App\Entity;
use App\Settings;
use Azura\Config;
use Doctrine\ORM\EntityManager;

class BrandingSettingsForm extends AbstractSettingsForm
{
    /**
     * @param EntityManager $em
     * @param Entity\Repository\SettingsRepository $settingsRepo
     * @param Settings $settings
     * @param Config $config
     */
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
