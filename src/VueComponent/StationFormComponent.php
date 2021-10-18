<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Acl;
use App\Entity;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use DateTime;
use DateTimeZone;
use Symfony\Component\Intl\Countries;

class StationFormComponent implements VueComponentInterface
{
    public function __construct(
        protected Adapters $adapters,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function getProps(ServerRequest $request): array
    {
        $installedFrontends = $this->adapters->listFrontendAdapters(true);

        $newStorageLocationMessage = __('Create a new storage location based on the base directory.');

        return [
            'showAdminTab'               => $request->getAcl()->isAllowed(Acl::GLOBAL_STATIONS),
            'timezones'                  => $this->getTimezones(),
            'isShoutcastInstalled'       => isset($installedFrontends[Adapters::FRONTEND_SHOUTCAST]),
            'countries'                  => Countries::getNames(),
            'mediaStorageLocations'      => $this->storageLocationRepo->fetchSelectByType(
                Entity\StorageLocation::TYPE_STATION_MEDIA,
                true,
                $newStorageLocationMessage
            ),
            'recordingsStorageLocations' => $this->storageLocationRepo->fetchSelectByType(
                Entity\StorageLocation::TYPE_STATION_RECORDINGS,
                true,
                $newStorageLocationMessage
            ),
            'podcastsStorageLocations'   => $this->storageLocationRepo->fetchSelectByType(
                Entity\StorageLocation::TYPE_STATION_PODCASTS,
                true,
                $newStorageLocationMessage
            ),
        ];
    }

    protected function getTimezones(): array
    {
        $tzSelect = [
            'UTC' => [
                'UTC' => 'UTC',
            ],
        ];

        foreach (
            DateTimeZone::listIdentifiers(
                (DateTimeZone::ALL ^ DateTimeZone::ANTARCTICA ^ DateTimeZone::UTC)
            ) as $tzIdentifier
        ) {
            $tz = new DateTimeZone($tzIdentifier);
            $tzRegion = substr($tzIdentifier, 0, strpos($tzIdentifier, '/')) ?: $tzIdentifier;
            $tzSubregion = str_replace([$tzRegion . '/', '_'], ['', ' '], $tzIdentifier) ?: $tzRegion;

            $offset = $tz->getOffset(new DateTime());

            $offsetPrefix = $offset < 0 ? '-' : '+';
            $offsetFormatted = gmdate(($offset % 60 === 0) ? 'G' : 'G:i', abs($offset));

            $prettyOffset = ($offset === 0) ? 'UTC' : 'UTC' . $offsetPrefix . $offsetFormatted;
            if ($tzSubregion !== $tzRegion) {
                $tzSubregion .= ' (' . $prettyOffset . ')';
            }

            $tzSelect[$tzRegion][$tzIdentifier] = $tzSubregion;
        }

        return $tzSelect;
    }
}
