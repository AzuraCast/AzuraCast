<?php

use App\Utilities\Strings;

class UtilitiesTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    public function testUtilities(): void
    {
        $test_result = Strings::generatePassword(10);
        self::assertEquals(10, strlen($test_result));

        $test_string = 'Lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet';
        $test_result = Strings::truncateText($test_string, 15);
        $expected_result = 'Lorem ipsum...';
        self::assertEquals($test_result, $expected_result);

        $test_url = 'https://www.twitter.com/';
        $test_result = Strings::truncateUrl($test_url);
        $expected_result = 'twitter.com';
        self::assertEquals($test_result, $expected_result);
    }
}
