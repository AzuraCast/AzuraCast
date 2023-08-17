<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\Admin\UsersController;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class PutMeAction extends UsersController implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $user = $request->getUser();
        $user = $this->em->refetch($user);

        $this->editRecord(
            (array)$request->getParsedBody(),
            $user,
            [
                AbstractNormalizer::GROUPS => [
                    EntityGroupsInterface::GROUP_ID,
                    EntityGroupsInterface::GROUP_GENERAL,
                ],
            ]
        );

        return $response->withJson(Status::updated());
    }
}
