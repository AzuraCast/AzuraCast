<?php

namespace App\Form;

use App\Acl;
use App\Config;
use App\Entity;
use App\Http\ServerRequest;
use App\Radio\Frontend\SHOUTcast;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationForm extends EntityForm
{
    protected Entity\Repository\StationRepository $station_repo;

    protected Entity\Repository\StorageLocationRepository $storageLocationRepo;

    protected Acl $acl;

    protected Settings $settings;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\StationRepository $station_repo,
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        Acl $acl,
        Config $config,
        Settings $settings
    ) {
        $this->acl = $acl;
        $this->entityClass = Entity\Station::class;
        $this->station_repo = $station_repo;
        $this->storageLocationRepo = $storageLocationRepo;
        $this->settings = $settings;

        $form_config = $config->get('forms/station');
        parent::__construct($em, $serializer, $validator, $form_config);
    }

    public function configure(array $options): void
    {
        // Hide "advanced" fields if advanced features are hidden on this installation.
        if (!$this->settings->enableAdvancedFeatures()) {
            foreach ($options['groups'] as $groupId => $group) {
                foreach ($group['elements'] as $elementKey => $element) {
                    $elementOptions = (array)$element[1];
                    $class = $elementOptions['label_class'] ?? '';

                    if (false !== strpos($class, 'advanced')) {
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
    public function process(ServerRequest $request, $record = null)
    {
        // Check for administrative permissions and hide admin fields otherwise.
        $user = $request->getUser();

        $canSeeAdministration = $this->acl->userAllowed($user, Acl::GLOBAL_STATIONS);
        if (!$canSeeAdministration) {
            foreach ($this->options['groups']['admin']['elements'] as $element_key => $element_info) {
                unset($this->fields[$element_key]);
            }
            unset($this->options['groups']['admin']);
        }

        if (!SHOUTcast::isInstalled()) {
            $frontendDesc = __(
                'Want to use SHOUTcast 2? <a href="%s" target="_blank">Install it here</a>, then reload this page.',
                $request->getRouter()->named('admin:install_shoutcast:index')
            );

            $this->getField('frontend_type')->setOption('description', $frontendDesc);
        }

        $create_mode = (null === $record);
        if (!$create_mode) {
            $recordArray = $this->normalizeRecord($record);
            $recordArray['media_storage_location_id'] = $recordArray['media_storage_location']['id'] ?? null;
            $recordArray['recordings_storage_location_id'] = $recordArray['recordings_storage_location']['id'] ?? null;

            $this->populate($recordArray);
        }

        if ($canSeeAdministration) {
            $storageLocationsDesc = __(
                '<a href="%s" target="_blank">Manage storage locations and storage quota here</a>.',
                $request->getRouter()->named('admin:storage_locations:index')
            );

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

            $this->options['groups']['admin']['elements']['recordings_storage_location_id'][1]['choices'] =
                $this->storageLocationRepo->fetchSelectByType(
                    Entity\StorageLocation::TYPE_STATION_RECORDINGS,
                    $create_mode,
                    __('Create a new storage location based on the base directory.'),
                );
        }

        if ('POST' === $request->getMethod() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();

            /** @var Entity\Station $record */
            $record = $this->denormalizeToRecord($data, $record);

            if ($canSeeAdministration) {
                if (!empty($data['media_storage_location_id'])) {
                    $record->setMediaStorageLocation(
                        $this->storageLocationRepo->findByType(
                            Entity\StorageLocation::TYPE_STATION_MEDIA,
                            $data['media_storage_location_id']
                        )
                    );
                }
                if (!empty($data['recordings_storage_location_id'])) {
                    $record->setRecordingsStorageLocation(
                        $this->storageLocationRepo->findByType(
                            Entity\StorageLocation::TYPE_STATION_RECORDINGS,
                            $data['recordings_storage_location_id']
                        )
                    );
                }
            }

            $errors = $this->validator->validate($record);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    /** @var ConstraintViolation $error */
                    $field_name = $error->getPropertyPath();

                    if (isset($this->fields[$field_name])) {
                        $this->fields[$field_name]->addError($error->getMessage());
                    } else {
                        $this->addError($error->getMessage());
                    }
                }
                return false;
            }

            return ($create_mode)
                ? $this->station_repo->create($record)
                : $this->station_repo->edit($record);
        }

        return false;
    }
}
