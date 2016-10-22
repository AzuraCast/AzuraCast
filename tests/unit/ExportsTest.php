<?php
class ExportsTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testExports()
    {
        $raw_data = [
            [
                'test_field_a' => 'Test Field A',
                'test_field_b' => 'Test Field B',
            ]
        ];

        $csv = \App\Export::csv($raw_data, false);
        $this->assertContains('"test_field_a","test_field_b"', $csv);

        $raw_data = '<test><subtest>Contents</subtest></test>';
        $xml_array = \App\Export::xml_to_array($raw_data);

        $this->assertArrayHasKey('test', $xml_array);

        $xml = \App\Export::array_to_xml($xml_array);

        $this->assertContains($raw_data, $xml);
    }
}