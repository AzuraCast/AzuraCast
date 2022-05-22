<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\Admin\UsersController;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class PutPasswordAction extends UsersController
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $user = $request->getUser();
        $body = (array)$request->getParsedBody();

        try {
            if (empty($body['current_password'])) {
                throw new InvalidArgumentException('Current password not provided (current_password).');
            }

            $currentPassword = $body['current_password'];
            if (!$user->verifyPassword($currentPassword)) {
                throw new InvalidArgumentException('Invalid current password.');
            }

            if (empty($body['new_password'])) {
                throw new InvalidArgumentException('New password not provided (new_password).');
            }

            $user = $this->em->refetch($user);

            $user->setNewPassword($body['new_password']);
            $this->em->persist($user);
            $this->em->flush();

            return $response->withJson(Entity\Api\Status::updated());
        } catch (Throwable $e) {
            return $response->withStatus(400)->withJson(Entity\Api\Error::fromException($e));
        }
    }
}
