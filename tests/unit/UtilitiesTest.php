<?php
class UtilitiesTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testUtilities()
    {
        $test_arr = ['test' => true, 'sub_test' => ['sub_test_1' => 42]];
        $test_result = \App\Utilities::print_r($test_arr, true);
        $expected_result = print_r($test_arr, true);
        $this->assertContains($expected_result, $test_result);

        $money_test = -14.00;
        $test_result = \App\Utilities::money_format($money_test);
        $expected_result = '-$14.00';
        $this->assertEquals($expected_result, $test_result);

        $test_result = \App\Utilities::generatePassword(10);
        $this->assertTrue(strlen($test_result) == 10);

        $test_result = \App\Utilities::timeToText(8640000);
        $expected_result = '100 days';
        $this->assertEquals($expected_result, $test_result);

        $time = time();
        $test_result = \App\Utilities::gstrtotime('+5 minutes', $time);
        $expected_result = $time+(60*5);
        $this->assertEquals($test_result, $expected_result);

        $test_string = 'Lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet';
        $test_result = \App\Utilities::truncate_text($test_string, 15);
        $expected_result = 'Lorem ipsum...';
        $this->assertEquals($test_result, $expected_result);

        $test_url = 'https://www.twitter.com/';
        $test_result = \App\Utilities::truncate_url($test_url);
        $expected_result = 'twitter.com';
        $this->assertEquals($test_result, $expected_result);

        $test_array = ['one', 'two', 'three'];
        $test_result = \App\Utilities::join_compound($test_array);
        $expected_result = 'one, two and three';
        $this->assertEquals($test_result, $expected_result);
    }
}