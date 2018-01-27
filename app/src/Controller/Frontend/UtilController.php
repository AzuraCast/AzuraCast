<?php
namespace Controller\Frontend;

use Doctrine\ORM\EntityManager;

class UtilController extends BaseController
{
    protected function permissions()
    {
        return true;
    }

    public function testAction()
    {
        $this->doNotRender();

        $body_contents = $this->request->getBody()->getContents();
        file_put_contents(__DIR__.'/test.txt', $body_contents);
    }
}