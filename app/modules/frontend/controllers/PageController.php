<?php
namespace Modules\Frontend\Controllers;

use \Entity\Block;

class PageController extends BaseController
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