<?php

class Api_Admin_CustomFieldsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageCustomFields(FunctionalTester $I): void
    {
        $I->wantTo('Manage custom fields via API.');

        $this->testCrudApi(
            $I,
            '/api/admin/custom_fields',
            [
                'name' => 'Test Field',
            ],
            [
                'name' => 'Modified Field',
            ]
        );
    }
}
