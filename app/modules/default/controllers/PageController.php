<?php
use \Entity\Block;

class PageController extends \DF\Controller\Action
{
	public function indexAction()
    {
    	$page_id = $this->_getParam('id');
    	$block = Block::find($page_id);

    	if (!$block instanceof Block)
    		throw new \DF\Exception('Invalid block ID.');

    	$this->view->block = $block;;
    }
}