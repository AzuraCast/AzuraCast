<?php
namespace Controller\Frontend;

use Doctrine\ORM\EntityManager;
use App\Http\Request;
use App\Http\Response;

class UtilController extends \AzuraCast\Legacy\Controller
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