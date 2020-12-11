<?php

use App\Utilities\Strings;

class UtilitiesTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testUtilities()
    {
        $test_result = Strings::generatePassword(10);
        $this->assertTrue(strlen($test_result) == 10);

        $test_string = 'Lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet';
        $test_result = Strings::truncateText($test_string, 15);
        $expected_result = 'Lorem ipsum...';
        $this->assertEquals($test_result, $expected_result);

        $test_url = 'https://www.twitter.com/';
        $test_result = Strings::truncateUrl($test_url);
        $expected_result = 'twitter.com';
        $this->assertEquals($test_result, $expected_result);
    }
}
