<?php
namespace Modules\Url\Controllers;

use \Entity\ShortUrl;

class IndexController extends \DF\Phalcon\Controller
{
    public function indexAction()
    {
        $this->doNotRender();

        $origin = $this->_getParam('origin');
        $destination = ShortUrl::parse($origin);

        $this->redirect($destination);
    }
}