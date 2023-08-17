<?php

declare(strict_types=1);

namespace Functional;

use App\Radio\Enums\FrontendAdapters;
use FunctionalTester;

class Api_Admin_StationsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageStations(FunctionalTester $I): void
    {
        $I->wantTo('Manage stations via API.');

        $this->testCrudApi(
            $I,
            '/api/admin/stations',
            [
                'name' => 'Test Station',
                'short_name' => 'test_station',
            ],
            [
                'name' => 'Modified Station',
                'frontend_type' => FrontendAdapters::Shoutcast->value,
            ]
        );
    }
}
