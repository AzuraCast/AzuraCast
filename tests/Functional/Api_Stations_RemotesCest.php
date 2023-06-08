<?php

declare(strict_types=1);

namespace Functional;

use App\Radio\Enums\RemoteAdapters;
use FunctionalTester;

class Api_Stations_RemotesCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageRemotes(FunctionalTester $I): void
    {
        $I->wantTo('Manage station remote relays via API.');

        $station = $this->getTestStation();

        $this->testCrudApi(
            $I,
            '/api/station/' . $station->getId() . '/remotes',
            [
                'type' => RemoteAdapters::Icecast->value,
                'display_name' => 'Test Remote Relay',
            ],
            [
                'display_name' => 'Modified Remote Relay',
            ]
        );
    }
}
