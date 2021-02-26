<?php

namespace App\Controller\Frontend\Account;

use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Exception\RateLimitExceededException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\ForgotPasswordMessage;
use App\RateLimit;
use App\Service\Mail;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

class ForgotPasswordAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        UserRepository $userRepo,
        MessageBus $messageBus,
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
            } catch (RateLimitExceededException $e) {
                $flash->addMessage(
                    sprintf(
                        '<b>%s</b><br>%s',
                        __('Too many forgot password attempts'),
                        __(
                            'You have attempted to reset your password too many times. Please wait 30 seconds and try again.'
                        )
                    ),
                    Flash::ERROR
                );

                return $response->withRedirect($request->getUri()->getPath());
            }

            $email = $request->getParsedBodyParam('email', '');
            $user = $userRepo->findByEmail($email);

            if ($user instanceof User) {
                $message = new ForgotPasswordMessage();
                $message->userId = $user->getId();
                $message->locale = $request->getLocale()->getLocale();

                $messageBus->dispatch($message);
            }

            $flash->addMessage(
                sprintf(
                    '<b>%s</b><br>%s',
                    __('Account recovery e-mail sent.'),
                    __(
                        'If the e-mail address you provided is in the system, check your inbox for a password reset message.'
                    )
                ),
                Flash::SUCCESS
            );

            return $response->withRedirect($request->getRouter()->named('home'));
        }

        return $view->renderToResponse($response, 'frontend/account/forgot');
    }
}
