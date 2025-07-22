<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Frontend_PublicCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function seePublicPage(FunctionalTester $I): void
    {
        $I->wantTo('Verify that the public page displays.');

        // Disable public pages
        $testStation = $this->getTestStation();
        $testStation->enable_public_page = false;
        $this->em->persist($testStation);
        $this->em->flush();

        $I->amOnPage('/public/' . $testStation->id);
        $I->seeResponseCodeIs(404);

        // Enable public pages
        $testStation = $this->getTestStation();
        $testStation->enable_public_page = true;
        $this->em->persist($testStation);
        $this->em->flush();

        $I->amOnPage('/public/' . $testStation->id);
        $I->seeResponseCodeIs(200);
        $I->see($testStation->name);

        $I->amOnPage('/public/' . $testStation->id . '/embed');
        $I->seeResponseCodeIs(200);

        $I->amOnPage('/public/' . $testStation->id . '/history');
        $I->seeResponseCodeIs(200);

        $I->amOnPage('/public/' . $testStation->id . '/playlist.pls');
        $I->seeResponseCodeIs(200);

        $I->amOnPage('/public/' . $testStation->id . '/playlist.m3u');
        $I->seeResponseCodeIs(200);

        // Disable WebDJ
        $testStation = $this->getTestStation();
        $testStation->enable_streamers = false;
        $this->em->persist($testStation);
        $this->em->flush();

        $I->amOnPage('/public/' . $testStation->id . '/dj');
        $I->seeResponseCodeIs(500);

        // Enable WebDJ
        $testStation = $this->getTestStation();
        $testStation->enable_streamers = true;
        $this->em->persist($testStation);
        $this->em->flush();

        $I->amOnPage('/public/' . $testStation->id . '/dj');
        $I->seeResponseCodeIs(200);
    }
}
