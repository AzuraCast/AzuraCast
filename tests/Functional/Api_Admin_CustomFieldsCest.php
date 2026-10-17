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

    /**
     * @before setupComplete
     * @before login
     */
    public function storesKnownLinkedTagsByCanonicalName(FunctionalTester $I): void
    {
        $I->wantTo('Store custom fields linked to known media file tags by their canonical tag name.');

        $listUrl = '/api/admin/custom_fields';

        $I->haveHttpHeader('Content-Type', 'application/json');

        $I->sendPOST($listUrl, ['name' => 'Album Artist', 'auto_assign' => 'Album-Artist']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['auto_assign' => 'album_artist']);

        $albumArtistLink = $I->grabDataFromResponseByJsonPath('links.self')[0];

        $I->sendPOST($listUrl, ['name' => 'Year', 'auto_assign' => 'year']);
        $I->seeResponseCodeIs(200);

        $yearLink = $I->grabDataFromResponseByJsonPath('links.self')[0];

        // "date" is an alias of the year tag
        $I->sendPOST($listUrl, ['name' => 'Date', 'auto_assign' => 'date']);
        $I->seeResponseCodeIs(400);

        $I->sendDELETE($albumArtistLink);
        $I->seeResponseCodeIs(200);

        $I->sendDELETE($yearLink);
        $I->seeResponseCodeIs(200);
    }
}
