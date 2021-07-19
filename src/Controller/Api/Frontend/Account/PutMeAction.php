<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\Admin\UsersController;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PutMeAction extends UsersController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $user = $request->getUser();
        $this->editRecord((array)$request->getParsedBody(), $user);

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }
}
