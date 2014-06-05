<?php
use \Entity\ShortUrl;

class Url_IndexController extends \DF\Controller\Action
{
    public function indexAction()
    {
        $this->doNotRender();

        $origin = $this->_getParam('origin');
        $destination = ShortUrl::parse($origin);

        $this->redirect($destination);
    }
}