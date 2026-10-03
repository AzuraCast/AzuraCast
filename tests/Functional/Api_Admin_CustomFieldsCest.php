<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

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

    /**
     * @before setupComplete
     * @before login
     */
    public function rejectsInvalidLinkedTags(FunctionalTester $I): void
    {
        $I->wantTo('Reject custom fields linked to invalid or duplicate media file tags.');

        $listUrl = '/api/admin/custom_fields';

        $I->haveHttpHeader('Content-Type', 'application/json');

        foreach (['bad=name', 'Ünicode', 'CUE_IN'] as $invalidTag) {
            $I->sendPOST($listUrl, ['name' => 'Invalid Field', 'auto_assign' => $invalidTag]);
            $I->seeResponseCodeIs(400);
        }

        $I->sendPOST($listUrl, ['name' => 'Likes', 'auto_assign' => 'likes']);
        $I->seeResponseCodeIs(200);

        $selfLink = $I->grabDataFromResponseByJsonPath('links.self')[0];

        $I->sendPOST($listUrl, ['name' => 'Likes Copy', 'auto_assign' => 'Likes']);
        $I->seeResponseCodeIs(400);

        $I->sendDELETE($selfLink);
        $I->seeResponseCodeIs(200);
    }
}
