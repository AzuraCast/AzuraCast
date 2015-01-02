<?php
namespace DF\Test;

/**
 * @backupGlobals disabled
 */
class TestCase extends \Zend_Test_PHPUnit_ControllerTestCase
{
    public function setUp()
    {
        $this->bootstrap = \Zend_Registry::get('application');
        parent::setUp();
    }
    
    public function tearDown()
    {   
        $this->resetRequest();
        $this->resetResponse();
        
        parent::tearDown();
    }
}