<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Entity\Api\Admin\Vue\StationsFormProps;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\StereoTool;
use App\Utilities\Time;
use Symfony\Component\Intl\Countries;

final readonly class StationFormComponent implements VueComponentInterface
{
    public function __construct(
        private Adapters $adapters
    ) {
    }

    public function getProps(ServerRequest $request): StationsFormProps
    {
        $installedFrontends = $this->adapters->listFrontendAdapters(true);

        return new StationsFormProps(
            timezones: Time::getTimezones(),
            countries: Countries::getNames(),
            isRsasInstalled: isset($installedFrontends[FrontendAdapters::Rsas->value]),
            isShoutcastInstalled: isset($installedFrontends[FrontendAdapters::Shoutcast->value]),
            isStereoToolInstalled: StereoTool::isInstalled()
        );
    }
}
