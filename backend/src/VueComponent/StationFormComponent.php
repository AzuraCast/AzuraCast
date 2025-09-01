<?php

declare(strict_types=1);

namespace App\VueComponent;

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

    public function getProps(ServerRequest $request): array
    {
        $installedFrontends = $this->adapters->listFrontendAdapters(true);

        return [
            'timezones' => Time::getTimezones(),
            'isShoutcastInstalled' => isset($installedFrontends[FrontendAdapters::Shoutcast->value]),
            'isRsasInstalled' => isset($installedFrontends[FrontendAdapters::Rsas->value]),
            'isStereoToolInstalled' => StereoTool::isInstalled(),
            'countries' => Countries::getNames(),
        ];
    }
}
