<?php
namespace App\Form;

use App\Entity;
use App\Http\ServerRequest;
use App\Service\GeoLite;
use App\Settings;
use App\Sync\Task\UpdateGeoLiteDatabase;
use Azura\Config;
use AzuraForms\Field\Markup;
use Doctrine\ORM\EntityManager;

class GeoLiteSettingsForm extends AbstractSettingsForm
{
    protected GeoLite $geoLite;

    protected UpdateGeoLiteDatabase $syncTask;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Settings $settings,
        Config $config,
        GeoLite $geoLite,
        UpdateGeoLiteDatabase $syncTask
    ) {
        $formConfig = $config->get('forms/install_geolite');

        parent::__construct(
            $em,
            $settingsRepo,
            $settings,
            $formConfig
        );

        $this->geoLite = $geoLite;
        $this->syncTask = $syncTask;
    }

    public function process(ServerRequest $request): bool
    {
        $version = $this->geoLite->getVersion();
        if (null !== $version) {
            /** @var Markup $currentVersionField */
            $currentVersionField = $this->getField('current_version');
            $currentVersionField->setAttribute(
                'markup',
                '<p class="text-success">' . __('GeoLite version "%s" is currently installed.', $version) . '</p>'
            );
        }

        $processed = parent::process($request);
        if ($processed) {
            $this->syncTask->run(true);
        }

        return $processed;
    }
}
