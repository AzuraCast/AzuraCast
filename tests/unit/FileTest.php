<?php
class FileTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testFile()
    {
        $temp_path = sys_get_temp_dir();

        $file = new \App\File('test_file.wav', $temp_path);
        $file->setName('test_file.mp3');

        $expected_path = $temp_path.'/test_file.mp3';
        $this->assertEquals($expected_path, $file->getPath());

        $expected_extension = 'mp3';
        $this->assertEquals($expected_extension, $file->getExtension());

        $file->addSuffix('.test');

        $expected_name = 'test_file.test.mp3';
        $this->assertEquals($expected_name, $file->getName());
    }
}