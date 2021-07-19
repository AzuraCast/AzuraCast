<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class RecoverAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $token,
        Entity\Repository\UserLoginTokenRepository $loginTokenRepo,
        EntityManagerInterface $em
    ): ResponseInterface {
        $flash = $request->getFlash();

        $user = $loginTokenRepo->authenticate($token);

        if (!$user instanceof Entity\User) {
            $flash->addMessage(
                sprintf(
                    '<b>%s</b>',
                    __('Invalid token specified.'),
                ),
                Flash::ERROR
            );

            return $response->withRedirect((string)$request->getRouter()->named('account:login'));
        }

        if ($request->isPost()) {
            $newPassword = $request->getParsedBodyParam('password');

            $user->setNewPassword($newPassword);
            $em->persist($user);
            $em->flush();

            $request->getAuth()->setUser($user);

            $loginTokenRepo->revokeForUser($user);

            $flash->addMessage(
                sprintf(
                    '<b>%s</b><br>%s',
                    __('Logged in using account recovery token'),
                    __('Your password has been updated.')
                ),
                Flash::SUCCESS
            );

            return $response->withRedirect((string)$request->getRouter()->named('dashboard'));
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/recover');
    }
}
