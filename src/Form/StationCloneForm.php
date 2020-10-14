<?php

namespace App\Form;

use App\Acl;
use App\Config;
use App\Entity;
use App\Http\ServerRequest;
use App\Radio\Configuration;
use App\Settings;
use App\Sync\Task\Media;
use DeepCopy;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationCloneForm extends StationForm
{
    protected Configuration $configuration;

    protected Media $media_sync;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\StationRepository $station_repo,
        Acl $acl,
        Configuration $configuration,
        Media $media_sync,
        Config $config,
        Settings $settings
    ) {
        parent::__construct($em, $serializer, $validator, $station_repo, $acl, $config, $settings);

        $form_config = $config->get('forms/station_clone');
        $this->configure($form_config);

        $this->configuration = $configuration;
        $this->media_sync = $media_sync;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequest $request, $record = null)
    {
        if (!$record instanceof Entity\Station) {
            throw new InvalidArgumentException('Record must be a station.');
        }

        $this->populate([
            'name' => $record->getName() . ' - Copy',
            'description' => $record->getDescription(),
        ]);

        if ('POST' === $request->getMethod() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();

            $copier = new DeepCopy\DeepCopy();
            $copier->addFilter(
                new DeepCopy\Filter\Doctrine\DoctrineProxyFilter(),
                new DeepCopy\Matcher\Doctrine\DoctrineProxyMatcher()
            );
            $copier->addFilter(
                new DeepCopy\Filter\KeepFilter(),
                new DeepCopy\Matcher\PropertyMatcher(Entity\StationMedia::class, 'song')
            );
            $copier->addFilter(
                new DeepCopy\Filter\KeepFilter(),
                new DeepCopy\Matcher\PropertyMatcher(Entity\RolePermission::class, 'role')
            );
            $copier->addFilter(
                new DeepCopy\Filter\KeepFilter(),
                new DeepCopy\Matcher\PropertyMatcher(Entity\StationMediaCustomField::class, 'field')
            );
            $copier->addFilter(
                new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
                new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'history')
            );
            $copier->addFilter(
                new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
                new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'sftp_users')
            );
            $copier->addFilter(
                new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
                new DeepCopy\Matcher\PropertyMatcher(Entity\StationPlaylist::class, 'media_items')
            );

            // Unset some properties across all copied record types.
            $global_unsets = [
                'id',
                'station_id',
                'media_id',
                'playlist_id',
                'field_id',
            ];
            foreach ($global_unsets as $prop) {
                $copier->addFilter(
                    new DeepCopy\Filter\SetNullFilter(),
                    new DeepCopy\Matcher\PropertyNameMatcher($prop)
                );
            }

            // Unset some values only on Station entities.
            $unset_values = [
                'short_name',
                'radio_base_dir',
                'adapter_api_key',
                'nowplaying',
                'nowplaying_timestamp',
                'current_streamer_id',
                'current_streamer',
                'storage_used',
            ];

            foreach ($unset_values as $prop) {
                $copier->addFilter(
                    new DeepCopy\Filter\SetNullFilter(),
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, $prop)
                );
            }

            if (!$data['clone_playlists']) {
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'playlists')
                );
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
                    new DeepCopy\Matcher\PropertyMatcher(Entity\StationMedia::class, 'playlists')
                );
            }

            if (!$data['clone_streamers']) {
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'streamers')
                );
            }

            if (!$data['clone_permissions']) {
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'permissions')
                );
            }

            if ('none' === $data['clone_media']) {
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'media')
                );
            } else {
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'playlists')
                );
            }

            // Execute the Doctrine entity copy.
            $copier->addFilter(
                new DeepCopy\Filter\Doctrine\DoctrineCollectionFilter(),
                new DeepCopy\Matcher\PropertyTypeMatcher(Collection::class)
            );

            /** @var Entity\Station $new_record */
            $new_record = $copier->copy($record);

            $new_record->setName($data['name']);
            $new_record->setDescription($data['description']);
            $new_record->clearPorts();

            $new_record->setIsStreamerLive(false);
            $new_record->setNeedsRestart(false);
            $new_record->setHasStarted(false);

            if ('share' === $data['clone_media']) {
                $new_record->setRadioMediaDir($record->getRadioMediaDir());
            }

            // Set new radio base directory
            $station_base_dir = Settings::getInstance()->getStationDirectory();
            $new_record->setRadioBaseDir($station_base_dir . '/' . $new_record->getShortName());

            // Persist all newly created records (and relations).
            $this->em->persist($new_record);
            foreach ($new_record->getMedia() as $subrecord) {
                /** @var Entity\StationMedia $subrecord */
                $this->em->persist($subrecord);

                foreach ($subrecord->getCustomFields() as $subrecord_custom_field) {
                    $this->em->persist($subrecord_custom_field);
                }

                foreach ($subrecord->getPlaylists() as $subrecord_playlist_items) {
                    /** @var Entity\StationPlaylistMedia $subrecord_playlist_items */
                    $this->em->persist($subrecord_playlist_items);

                    $playlist = $subrecord_playlist_items->getPlaylist();
                    $this->em->persist($playlist);
                }
            }
            foreach ($new_record->getMounts() as $subrecord) {
                $this->em->persist($subrecord);
            }
            foreach ($new_record->getPermissions() as $subrecord) {
                $this->em->persist($subrecord);
            }
            foreach ($new_record->getPlaylists() as $subrecord) {
                /** @var Entity\StationPlaylist $subrecord */
                $this->em->persist($subrecord);

                foreach ($subrecord->getScheduleItems() as $playlist_schedule_item) {
                    $this->em->persist($playlist_schedule_item);
                }
            }
            foreach ($new_record->getRemotes() as $subrecord) {
                $this->em->persist($subrecord);
            }
            foreach ($new_record->getStreamers() as $subrecord) {
                /** @var Entity\StationStreamer $subrecord */
                $this->em->persist($subrecord);

                foreach ($subrecord->getScheduleItems() as $playlist_schedule_item) {
                    $this->em->persist($playlist_schedule_item);
                }
            }
            $this->em->flush();

            // Copy album art.
            if ('none' !== $data['clone_media']) {
                $this->copy(
                    $record->getRadioAlbumArtDir(),
                    $new_record->getRadioAlbumArtDir()
                );
            }

            // Copy media.
            if ('copy' === $data['clone_media']) {
                $this->copy(
                    $record->getRadioMediaDir(),
                    $new_record->getRadioMediaDir()
                );
            }

            // Clear the EntityManager for later functions.
            $new_record_id = $new_record->getId();
            $this->em->clear();
            $new_record = $this->em->find(Entity\Station::class, $new_record_id);

            // Run normal post-creation steps.
            $this->media_sync->importMusic($new_record);

            $new_record = $this->em->find(Entity\Station::class, $new_record_id);

            $this->configuration->assignRadioPorts($new_record, true);
            $this->configuration->writeConfiguration($new_record);

            $this->em->flush();
            return $new_record;
        }

        return false;
    }

    protected function copy($src, $dest): void
    {
        foreach (scandir($src) as $file) {
            if (!is_readable($src . '/' . $file)) {
                continue;
            }

            if (is_dir($src . '/' . $file) && ($file !== '.') && ($file !== '..')) {
                if (!mkdir($concurrentDirectory = $dest . '/' . $file) && !is_dir($concurrentDirectory)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
                $this->copy($src . '/' . $file, $dest . '/' . $file);
            } else {
                copy($src . '/' . $file, $dest . '/' . $file);
            }
        }
    }
}
