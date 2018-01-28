<?php
namespace Controller\Frontend;

use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class UtilController extends BaseController
{
    protected function permissions()
    {
        return true;
    }

    public function testAction(Request $request, Response $response): Response
    {
        $this->doNotRender();

        $body_contents = $this->request->getBody()->getContents();
        file_put_contents(__DIR__.'/test.txt', $body_contents);
    }
}