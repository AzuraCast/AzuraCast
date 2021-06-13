<?php

class Api_Stations_WebhooksCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageWebhooks(FunctionalTester $I): void
    {
        $I->wantTo('Manage station webhooks via API.');

        $station = $this->getTestStation();

        $this->testCrudApi(
            $I,
            '/api/station/' . $station->getId() . '/webhooks',
            [
                'type' => \App\Webhook\Connector\Generic::NAME,
                'name' => 'Test Webhook',
            ],
            [
                'name' => 'Modified Webhook',
            ]
        );
    }
}
