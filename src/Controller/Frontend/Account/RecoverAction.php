<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class RecoverAction
{
    public function __construct(
        private readonly Entity\Repository\UserLoginTokenRepository $loginTokenRepo,
        private readonly EntityManagerInterface $em
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $token
    ): ResponseInterface {
        $user = $this->loginTokenRepo->authenticate($token);
        $flash = $request->getFlash();

        if (!$user instanceof Entity\User) {
            $flash->addMessage(
                sprintf(
                    '<b>%s</b>',
                    __('Invalid token specified.'),
                ),
                Flash::ERROR
            );

            return $response->withRedirect($request->getRouter()->named('account:login'));
        }

        $csrf = $request->getCsrf();
        $error = null;

        if ($request->isPost()) {
            try {
                $data = $request->getParams();

                $csrf->verify($data['csrf'] ?? null, 'recover');

                if (empty($data['password'])) {
                    throw new InvalidArgumentException('Password required.');
                }

                $user->setNewPassword($data['password']);
                $user->setTwoFactorSecret();

                $this->em->persist($user);
                $this->em->flush();

                $request->getAuth()->setUser($user);

                $this->loginTokenRepo->revokeForUser($user);

                $flash->addMessage(
                    sprintf(
                        '<b>%s</b><br>%s',
                        __('Logged in using account recovery token'),
                        __('Your password has been updated.')
                    ),
                    Flash::SUCCESS
                );

                return $response->withRedirect($request->getRouter()->named('dashboard'));
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_Recover',
            id: 'account-recover',
            layout: 'minimal',
            title: __('Recover Account'),
            props: [
                'csrf' => $csrf->generate('recover'),
                'error' => $error,
            ]
        );
    }
}
