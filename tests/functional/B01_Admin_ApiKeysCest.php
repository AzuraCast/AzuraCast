<?php
class B01_Admin_ApiKeysCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     * @after cleanup
     */
    public function manageApiKeys(FunctionalTester $I)
    {
        $I->wantTo('Administer API keys.');

        $I->amOnPage('/admin/api');
        $I->see('API Keys');

        $I->click('.btn-float'); // Plus sign

        $I->submitForm('.form', [
            'app_form' => [
                'owner' => 'API Key Test',
            ],
        ]);

        $I->seeCurrentUrlEquals('/admin/api');
        $I->see('API Key Test');
    }
}
