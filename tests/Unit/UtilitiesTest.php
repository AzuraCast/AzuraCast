<?php

declare(strict_types=1);

namespace Unit;

use App\Utilities\Strings;
use Codeception\Test\Unit;
use UnitTester;

class UtilitiesTest extends Unit
{
    protected UnitTester $tester;

    public function testUtilities(): void
    {
        $testResult = Strings::generatePassword(10);
        self::assertEquals(10, strlen($testResult));

        $testString = 'Lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet';
        $testResult = Strings::truncateText($testString, 15);
        $expectedResult = 'Lorem ipsum...';
        self::assertEquals($testResult, $expectedResult);

        $testUrl = 'https://www.twitter.com/';
        $testResult = Strings::truncateUrl($testUrl);
        $expectedResult = 'twitter.com';
        self::assertEquals($testResult, $expectedResult);
    }
}
