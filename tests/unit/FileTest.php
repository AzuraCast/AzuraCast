<?php
class FileTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testFile()
    {
        $file = new \App\File('test_file.wav', APP_INCLUDE_TEMP);
        $file->setName('test_file.mp3');

        $expected_path = APP_INCLUDE_TEMP.'/test_file.mp3';
        $this->assertEquals($expected_path, $file->getPath());

        $expected_extension = 'mp3';
        $this->assertEquals($expected_extension, $file->getExtension());

        $file->addSuffix('.test');

        $expected_name = 'test_file.test.mp3';
        $this->assertEquals($expected_name, $file->getName());
    }
}