<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Entity;
use App\Exception\RateLimitExceededException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\RateLimit;
use App\Service\Mail;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class ForgotPasswordAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\UserRepository $userRepo,
        Entity\Repository\UserLoginTokenRepository $loginTokenRepo,
        RateLimit $rateLimit,
        Mail $mail
    ): ResponseInterface {
        $flash = $request->getFlash();
        $view = $request->getView();

        if (!$mail->isEnabled()) {
            return $view->renderToResponse($response, 'frontend/account/forgot_disabled');
        }

        if ($request->isPost()) {
            try {
                $rateLimit->checkRequestRateLimit($request, 'forgot', 30, 3);
            } catch (RateLimitExceededException) {
                $flash->addMessage(
                    sprintf(
                        '<b>%s</b><br>%s',
                        __('Too many forgot password attempts'),
                        __(
                            'You have attempted to reset your password too many times. Please wait '
                            . '30 seconds and try again.'
                        )
                    ),
                    Flash::ERROR
                );

                return $response->withRedirect($request->getUri()->getPath());
            }

            $email = $request->getParsedBodyParam('email', '');
            $user = $userRepo->findByEmail($email);

            if ($user instanceof Entity\User) {
                $email = $mail->createMessage();
                $email->to($user->getEmail());

                $email->subject(__('Account Recovery Link'));

                $loginToken = $loginTokenRepo->createToken($user);
                $email->text(
                    $view->render(
                        'mail/forgot',
                        [
                            'token' => (string)$loginToken,
                        ]
                    )
                );

                $mail->send($email);
            }

            $flash->addMessage(
                sprintf(
                    '<b>%s</b><br>%s',
                    __('Account recovery e-mail sent.'),
                    __(
                        'If the e-mail address you provided is in the system, check your inbox '
                        . 'for a password reset message.'
                    )
                ),
                Flash::SUCCESS
            );

            return $response->withRedirect((string)$request->getRouter()->named('account:login'));
        }

        return $view->renderToResponse($response, 'frontend/account/forgot');
    }
}
