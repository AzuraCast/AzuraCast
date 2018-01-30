<?php
namespace Controller\Frontend;

use App\Http\Request;
use App\Http\Response;
use Slim\Container;

class UtilController
{
    /** @var Container */
    protected $di;

    /**
     * @param Container $di
     */
    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    public function testAction(Request $request, Response $response): Response
    {
        $body_contents = $request->getBody()->getContents();
        file_put_contents(__DIR__.'/test.txt', $body_contents);

        return $response->withJson('Test successful!');
    }
}