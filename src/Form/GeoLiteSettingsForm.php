<?php
namespace App\Form;

use App\Entity;
use App\Http\ServerRequest;
use App\Settings;
use App\Sync\Task\UpdateGeoLiteDatabase;
use Azura\Config;
use Doctrine\ORM\EntityManager;

class GeoLiteSettingsForm extends AbstractSettingsForm
{
    protected UpdateGeoLiteDatabase $syncTask;

    public function __construct(
        EntityManager $em,
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

    public function process(ServerRequest $request): bool
    {
        $processed = parent::process($request);
        if ($processed) {
            $this->syncTask->run(true);
        }

        return $processed;
    }
}
