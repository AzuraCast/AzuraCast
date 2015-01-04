<?php
class UtilController extends \DF\Controller\Action
{
    public function testAction()
    {
        $this->doNotRender();

        echo \PVL\Service\PvlNode::push('test', array('test' => true));

    }
}