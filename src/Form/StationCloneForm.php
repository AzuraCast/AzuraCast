<?php
namespace App\Form;

use App\Acl;
use App\Entity;
use App\Http\Request;
use App\Radio\Configuration;
use App\Radio\Frontend\SHOUTcast;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationCloneForm extends StationForm
{
    /** @var Configuration */
    protected $configuration;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param Acl $acl
     * @param Configuration $configuration
     * @param array $form_config
     *
     * @see \App\Provider\FormProvider
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Acl $acl,
        Configuration $configuration,
        array $form_config
    ) {
        parent::__construct($em, $serializer, $validator, $acl, $form_config);

        $this->configuration = $configuration;
    }

    /**
     * @inheritdoc
     */
    public function process(Request $request, $record = null)
    {
        if (!$record instanceof Entity\Station) {
            throw new \InvalidArgumentException('Record must be a station.');
        }

        $this->populate([
            'name' => $record->getName().' - Copy',
            'description' => $record->getDescription(),
        ]);

        if ($request->isPost() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();

            $new_record_data = $this->_normalizeRecord($record);
            $new_record_data['name'] = $data['name'];
            $new_record_data['description'] = $data['description'];

            $unset_values = [
                'short_name',
                'radio_base_dir',
                'nowplaying',
                'nowplaying_timestamp',
                'is_streamer_live',
                'needs_restart',
                'has_started',
            ];

            foreach($unset_values as $unset_value) {
                unset($new_record_data[$unset_value]);
            }

            // Unset ports.
            unset(
                $new_record_data['frontend_config']['port'],
                $new_record_data['backend_config']['dj_port'],
                $new_record_data['backend_config']['telnet_port']
            );

            if ('share' === $data['clone_media']) {
                $new_record_data['radio_media_dir'] = $record->getRadioMediaDir();
            } else {
                unset($new_record_data['radio_media_dir'], $new_record_data['storage_used']);
            }

            $new_record = $this->_denormalizeToRecord($new_record_data);
            $this->station_repo->create($new_record);

            $this->configuration->assignRadioPorts($new_record, true);
            $this->configuration->writeConfiguration($new_record);

            // Copy associated records if applicable.
            if ('copy' === $data['clone_media']) {
                copy($record->getRadioMediaDir(), $new_record->getRadioMediaDir());
            }

            if (1 == $data['clone_playlists']) {
                foreach ($record->getPlaylists() as $source_record) {
                    $dest_record_data = $this->_normalizeRecord($source_record);
                    unset($dest_record_data['id'], $dest_record_data['station_id']);

                    $dest_record = $this->serializer->denormalize($data, Entity\StationPlaylist::class, null, [
                        ObjectNormalizer::OBJECT_TO_POPULATE => new Entity\StationPlaylist($new_record),
                    ]);
                    $this->em->persist($dest_record);
                }
            }

            if (1 == $data['clone_streamers']) {
                foreach ($record->getStreamers() as $source_record) {
                    $dest_record_data = $this->_normalizeRecord($source_record);
                    unset($dest_record_data['id'], $dest_record_data['station_id']);

                    $dest_record = $this->serializer->denormalize($data, Entity\StationStreamer::class, null, [
                        ObjectNormalizer::OBJECT_TO_POPULATE => new Entity\StationStreamer($new_record),
                    ]);
                    $this->em->persist($dest_record);
                }
            }

            if (1 == $data['clone_permissions']) {
                foreach ($record->getPermissions() as $source_record) {
                    $dest_record_data = $this->_normalizeRecord($source_record);
                    unset($dest_record_data['id'], $dest_record_data['station_id']);

                    $dest_record = $this->serializer->denormalize($data, Entity\RolePermission::class, null, [
                        ObjectNormalizer::OBJECT_TO_POPULATE => new Entity\RolePermission($source_record->getRole(), $new_record),
                    ]);
                    $this->em->persist($dest_record);
                }
            }

            $this->em->flush();
            return $new_record;
        }

        return false;
    }
}
