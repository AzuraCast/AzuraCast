<?php
class Api_IndexController extends \PVL\Controller\Action\Api
{
    public function indexAction()
    {
    	$this->returnSuccess('The PVL! API is online and functioning. For more information, visit http://docs.ponyvillelive.apiary.io/');
    }

    public function statusAction()
    {
    	$this->returnSuccess(array(
    		'online' => 'true',
    		'timestamp' => time(),
    	));
    }
}