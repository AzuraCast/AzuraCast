<?php

namespace Unit;

use App\Utilities;
use Codeception\Test\Unit;
use UnitTester;

class ExportsTest extends Unit
{
    protected UnitTester $tester;

    public function testExports()
    {
        $raw_data = [
            [
                'test_field_a' => 'Test Field A',
                'test_field_b' => 'Test Field B',
            ],
        ];

        $csv = Utilities\Csv::arrayToCsv($raw_data, false);
        $this->assertStringContainsString('"test_field_a","test_field_b"', $csv);

        $raw_data = '<test><subtest>Contents</subtest></test>';
        $xml_array = Utilities\Xml::xmlToArray($raw_data);

        $this->assertArrayHasKey('test', $xml_array);

        $xml = Utilities\Xml::arrayToXml($xml_array);

        $this->assertStringContainsString($raw_data, $xml);
    }
}
