<?php

declare(strict_types=1);

namespace Functional;

use App\Webhook\Enums\WebhookTypes;
use FunctionalTester;

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
                'type' => WebhookTypes::Generic->value,
                'name' => 'Test Webhook',
            ],
            [
                'name' => 'Modified Webhook',
            ]
        );
    }
}
