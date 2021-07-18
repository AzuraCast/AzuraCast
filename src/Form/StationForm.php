<?php

declare(strict_types=1);

namespace App\Form;

use App\Acl;
use App\Config;
use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends EntityForm<Entity\Station>
 */
class StationForm extends EntityForm
{
    public function __construct(
        protected Entity\Repository\StationRepository $stationRepo,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Environment $environment,
        protected Adapters $adapters,
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config
    ) {
        $this->entityClass = Entity\Station::class;

        $form_config = $config->get(
            'forms/station',
            [
                'adapters' => $adapters,
            ]
        );
        parent::__construct($em, $serializer, $validator, $form_config);
    }

    public function configure(array $options): void
    {
        // Hide "advanced" fields if advanced features are hidden on this installation.
        $settings = $this->settingsRepo->readSettings();
        if (!$settings->getEnableAdvancedFeatures()) {
            foreach ($options['groups'] as $groupId => $group) {
                foreach ($group['elements'] as $elementKey => $element) {
                    $elementOptions = (array)$element[1];
                    $class = $elementOptions['label_class'] ?? '';

                    if (str_contains($class, 'advanced')) {
                        unset($options['groups'][$groupId]['elements'][$elementKey]);
                    }
                }
            }
        }

        parent::configure($options);
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequest $request, $record = null): object|bool
    {
        // Check for administrative permissions and hide admin fields otherwise.
        $canSeeAdministration = $request->getAcl()->isAllowed(Acl::GLOBAL_STATIONS);
        if (!$canSeeAdministration) {
            foreach ($this->options['groups']['admin']['elements'] as $element_key => $element_info) {
                unset($this->fields[$element_key]);
            }
            unset($this->options['groups']['admin']);
        }

        $installedFrontends = $this->adapters->listFrontendAdapters(true);
        if (!isset($installedFrontends[Adapters::FRONTEND_SHOUTCAST])) {
            $frontendDesc = __(
                'Want to use SHOUTcast 2? <a href="%s" target="_blank">Install it here</a>, then reload this page.',
                (string)$request->getRouter()->named('admin:install_shoutcast:index')
            );

            $this->getField('frontend_type')->setOption('description', $frontendDesc);
        }

        $create_mode = (null === $record);
        if (!$create_mode) {
            $recordArray = $this->normalizeRecord($record);
            $recordArray['media_storage_location_id'] = $recordArray['media_storage_location']['id'] ?? null;
            $recordArray['recordings_storage_location_id'] = $recordArray['recordings_storage_location']['id'] ?? null;
            $recordArray['podcasts_storage_location_id'] = $recordArray['podcasts_storage_location']['id'] ?? null;

            $this->populate($recordArray);
        }

        if ($canSeeAdministration) {
            $storageLocationsDesc = __(
                '<a href="%s" target="_blank">Manage storage locations and storage quota here</a>.',
                (string)$request->getRouter()->named('admin:storage_locations:index')
            );

            if ($this->hasField('media_storage_location_id')) {
                $mediaStorageField = $this->getField('media_storage_location_id');
                $mediaStorageField->setOption('description', $storageLocationsDesc);
                $mediaStorageField->setOption(
                    'choices',
                    $this->storageLocationRepo->fetchSelectByType(
                        Entity\StorageLocation::TYPE_STATION_MEDIA,
                        $create_mode,
                        __('Create a new storage location based on the base directory.'),
                    )
                );
            }

            if ($this->hasField('recordings_storage_location_id')) {
                $recordingsStorageField = $this->getField('recordings_storage_location_id');
                $recordingsStorageField->setOption('description', $storageLocationsDesc);
                $recordingsStorageField->setOption(
                    'choices',
                    $this->storageLocationRepo->fetchSelectByType(
                        Entity\StorageLocation::TYPE_STATION_RECORDINGS,
                        $create_mode,
                        __('Create a new storage location based on the base directory.'),
                    )
                );
            }

            if ($this->hasField('podcasts_storage_location_id')) {
                $recordingsStorageField = $this->getField('podcasts_storage_location_id');
                $recordingsStorageField->setOption('description', $storageLocationsDesc);
                $recordingsStorageField->setOption(
                    'choices',
                    $this->storageLocationRepo->fetchSelectByType(
                        Entity\StorageLocation::TYPE_STATION_PODCASTS,
                        $create_mode,
                        __('Create a new storage location based on the base directory.'),
                    )
                );
            }
        }

        if ($this->isValid($request)) {
            $data = $this->getValues();

            /** @var Entity\Station $record */
            $record = $this->denormalizeToRecord($data, $record);

            if ($canSeeAdministration) {
                if (!empty($data['media_storage_location_id'])) {
                    $sl = $this->storageLocationRepo->findByType(
                        Entity\StorageLocation::TYPE_STATION_MEDIA,
                        (int)$data['media_storage_location_id']
                    );

                    if (null === $sl) {
                        $this->addError('Media storage location not found.');
                    } else {
                        $record->setMediaStorageLocation($sl);
                    }
                }
                if (!empty($data['recordings_storage_location_id'])) {
                    $sl = $this->storageLocationRepo->findByType(
                        Entity\StorageLocation::TYPE_STATION_RECORDINGS,
                        (int)$data['recordings_storage_location_id']
                    );

                    if (null === $sl) {
                        $this->addError('Recordings storage location not found.');
                    } else {
                        $record->setRecordingsStorageLocation($sl);
                    }
                }
                if (!empty($data['podcasts_storage_location_id'])) {
                    $sl = $this->storageLocationRepo->findByType(
                        Entity\StorageLocation::TYPE_STATION_PODCASTS,
                        (int)$data['podcasts_storage_location_id']
                    );

                    if (null === $sl) {
                        $this->addError('Podcasts storage location not found.');
                    } else {
                        $record->setPodcastsStorageLocation($sl);
                    }
                }
            }

            $errors = $this->validator->validate($record);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    /** @var ConstraintViolation $error */
                    $field_name = $error->getPropertyPath();

                    if (isset($this->fields[$field_name])) {
                        $this->fields[$field_name]->addError((string)$error->getMessage());
                    } else {
                        $this->addError((string)$error->getMessage());
                    }
                }
                return false;
            }

            return ($create_mode)
                ? $this->stationRepo->create($record)
                : $this->stationRepo->edit($record);
        }

        return false;
    }
}
