<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use FunctionalTester;

class Api_Admin_StorageLocationsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageStorageLocations(FunctionalTester $I): void
    {
        $I->wantTo('Manage storage locations via API.');

        $this->testCrudApi(
            $I,
            '/api/admin/storage_locations',
            [
                'type' => StorageLocationTypes::StationMedia->value,
                'adapter' => StorageLocationAdapters::Local->value,
                'path' => '/tmp/test_storage_location',
            ],
            [
                'path' => '/tmp/test_storage_location_2',
            ]
        );
    }
}
