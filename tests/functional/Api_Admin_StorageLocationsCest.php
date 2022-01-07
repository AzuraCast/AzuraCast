<?php

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
                'type'    => App\Entity\Enums\StorageLocationTypes::StationMedia->value,
                'adapter' => App\Entity\Enums\StorageLocationAdapters::Local->value,
                'path'    => '/tmp/test_storage_location',
            ],
            [
                'path' => '/tmp/test_storage_location_2',
            ]
        );
    }
}
