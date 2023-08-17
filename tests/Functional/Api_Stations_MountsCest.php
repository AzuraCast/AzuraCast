<?php

declare(strict_types=1);

namespace Functional;

use FunctionalTester;

class Api_Stations_MountsCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function manageMounts(FunctionalTester $I): void
    {
        $I->wantTo('Manage station mount points via API.');

        $station = $this->getTestStation();

        $this->testCrudApi(
            $I,
            '/api/station/' . $station->getId() . '/mounts',
            [
                'name' => '/radio.mp3',
                'enable_autodj' => true,
                'autodj_format' => 'mp3',
                'autodj_bitrate' => 128,
            ],
            [
                'name' => '/music.mp3',
                'enable_autodj' => false,
            ]
        );
    }
}
