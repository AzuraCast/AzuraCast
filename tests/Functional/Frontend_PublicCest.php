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
        $testStation->setEnablePublicPage(false);
        $this->em->persist($testStation);
        $this->em->flush();

        $I->amOnPage('/public/' . $testStation->getId());
        $I->seeResponseCodeIs(500);

        // Enable public pages
        $testStation = $this->getTestStation();
        $testStation->setEnablePublicPage(true);
        $this->em->persist($testStation);
        $this->em->flush();

        $I->amOnPage('/public/' . $testStation->getId());
        $I->seeResponseCodeIs(200);
        $I->see($testStation->getName());

        $I->amOnPage('/public/' . $testStation->getId() . '/embed');
        $I->seeResponseCodeIs(200);

        $I->amOnPage('/public/' . $testStation->getId() . '/history');
        $I->seeResponseCodeIs(200);

        $I->amOnPage('/public/' . $testStation->getId() . '/playlist.pls');
        $I->seeResponseCodeIs(200);

        $I->amOnPage('/public/' . $testStation->getId() . '/playlist.m3u');
        $I->seeResponseCodeIs(200);

        // Disable WebDJ
        $testStation = $this->getTestStation();
        $testStation->setEnableStreamers(false);
        $this->em->persist($testStation);
        $this->em->flush();

        $I->amOnPage('/public/' . $testStation->getId() . '/dj');
        $I->seeResponseCodeIs(500);

        // Enable WebDJ
        $testStation = $this->getTestStation();
        $testStation->setEnableStreamers(true);
        $this->em->persist($testStation);
        $this->em->flush();

        $I->amOnPage('/public/' . $testStation->getId() . '/dj');
        $I->seeResponseCodeIs(200);
    }
}
