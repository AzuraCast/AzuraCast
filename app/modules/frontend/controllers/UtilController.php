<?php
namespace Modules\Frontend\Controllers;

class UtilController extends BaseController
{
    public function testAction()
    {
        $this->doNotRender();

        echo \PVL\Service\PvlNode::push('test', array('test' => true));

    }
}