<?php

declare(strict_types=1);

namespace App\Form;

use App\Config;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\Radio\Configuration;
use App\Sync\Task\CheckMediaTask;
use InvalidArgumentException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationCloneForm extends StationForm
{


    public function __construct(
        protected Configuration $configuration,
        protected CheckMediaTask $media_sync,
        protected ReloadableEntityManagerInterface $reloadableEm,
        Entity\Repository\StationRepository $stationRepo,
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        Adapters $adapters,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config
    ) {
        parent::__construct(
            $stationRepo,
            $storageLocationRepo,
            $settingsRepo,
            $environment,
            $adapters,
            $reloadableEm,
            $serializer,
            $validator,
            $config
        );

        $form_config = $config->get('forms/station_clone');
        $this->configure($form_config);
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequest $request, $record = null): object|bool
    {
        if (!$record instanceof Entity\Station) {
            throw new InvalidArgumentException('Record must be a station.');
        }

        $this->populate(
            [
                'name' => $record->getName() . ' - Copy',
                'description' => $record->getDescription(),
            ]
        );

        if ($this->isValid($request)) {
            return $newStation;
        }

        return false;
    }

    
}
