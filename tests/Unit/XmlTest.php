<?php

declare(strict_types=1);

namespace Unit;

use App\Xml\Reader;
use App\Xml\Writer;
use Codeception\Test\Unit;
use UnitTester;

class XmlTest extends Unit
{
    protected UnitTester $tester;

    public function testXml(): void
    {
        $arrayValue = [
            'mounts' => [
                'mount' => [
                    [
                        '@type' => 'normal',
                        'path' => '/radio.mp3',
                    ],
                    [
                        '@type' => 'special',
                        'path' => '/special.mp3',
                    ],
                ],
            ],
        ];

        $xmlString = (new Writer())->toString($arrayValue, 'icecast');

        $xmlExpected = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <icecast>
            <mounts>
                <mount type="normal">
                    <path>/radio.mp3</path>
                </mount>
                <mount type="special">
                    <path>/special.mp3</path>
                </mount>
            </mounts>
        </icecast>

        XML;

        self::assertEquals($xmlString, $xmlExpected);

        $backToArray = (new Reader())->fromString($xmlString);

        self::assertEquals($arrayValue, $backToArray);
    }
}
