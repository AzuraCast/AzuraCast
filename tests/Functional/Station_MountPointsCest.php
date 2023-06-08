<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Station_MountPointsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editMountPoints(FunctionalTester $I): void
    {
        $testStation = $this->getTestStation();
        $stationId = $testStation->getId();

        $I->amOnPage('/station/' . $stationId . '/mounts');

        $I->see('Mount Points');
    }
}
