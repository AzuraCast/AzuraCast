<?php

namespace App\Controller\Frontend\Account;

use App\Entity;
use App\Exception\RateLimitExceededException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\RateLimit;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class ForgotPasswordAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\UserRepository $userRepo,
        Entity\Repository\UserLoginTokenRepository $loginTokenRepo,
        RateLimit $rateLimit,
        MailerInterface $mailer
    ): ResponseInterface {
        $flash = $request->getFlash();
        $view = $request->getView();

        $settings = $settingsRepo->readSettings();
        if (!$settings->getMailEnabled()) {
            return $view->renderToResponse($response, 'frontend/account/forgot_disabled');
        }

        if ($request->isPost()) {
            try {
                $rateLimit->checkRequestRateLimit($request, 'forgot', 30, 3);
            } catch (RateLimitExceededException $e) {
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
                $email = new Email();
                $email->from(new Address($settings->getMailSenderEmail(), $settings->getMailSenderName()));
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

                $mailer->send($email);
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

            return $response->withRedirect($request->getRouter()->named('home'));
        }

        return $view->renderToResponse($response, 'frontend/account/forgot');
    }
}
