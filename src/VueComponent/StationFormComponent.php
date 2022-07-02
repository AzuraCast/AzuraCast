<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Entity\Repository\SettingsRepository;
use App\Enums\GlobalPermissions;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\StereoTool;
use DateTime;
use DateTimeZone;
use Symfony\Component\Intl\Countries;

final class StationFormComponent implements VueComponentInterface
{
    public function __construct(
        private readonly Adapters $adapters,
        private readonly SettingsRepository $settingsRepo
    ) {
    }

    public function getProps(ServerRequest $request): array
    {
        $installedFrontends = $this->adapters->listFrontendAdapters(true);

        $settings = $this->settingsRepo->readSettings();

        return [
            'showAdminTab' => $request->getAcl()->isAllowed(GlobalPermissions::Stations),
            'showAdvanced' => $settings->getEnableAdvancedFeatures(),
            'timezones' => $this->getTimezones(),
            'isShoutcastInstalled' => isset($installedFrontends[FrontendAdapters::Shoutcast->value]),
            'isStereoToolInstalled' => StereoTool::isInstalled(),
            'countries' => Countries::getNames(),
            'storageLocationApiUrl' => (string)$request->getRouter()->named('api:admin:stations:storage-locations'),
        ];
    }

    private function getTimezones(): array
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
            $tzRegion = substr($tzIdentifier, 0, strpos($tzIdentifier, '/') ?: 0) ?: $tzIdentifier;
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
