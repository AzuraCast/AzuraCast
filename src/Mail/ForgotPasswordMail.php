<?php

namespace App\Mail;

use App\Entity;
use App\Locale;
use App\Message;
use App\Service\Mail;
use App\View;
use DI\FactoryInterface;
use Psr\Log\LoggerInterface;

class ForgotPasswordMail
{
    protected Entity\Repository\UserRepository $userRepo;

    protected Entity\Repository\UserLoginTokenRepository $loginTokenRepo;

    protected Mail $mail;

    protected FactoryInterface $factory;

    protected LoggerInterface $logger;

    public function __construct(
        Entity\Repository\UserRepository $userRepo,
        Entity\Repository\UserLoginTokenRepository $loginTokenRepo,
        Mail $mail,
        FactoryInterface $factory,
        LoggerInterface $logger
    ) {
        $this->userRepo = $userRepo;
        $this->loginTokenRepo = $loginTokenRepo;
        $this->mail = $mail;
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof Message\ForgotPasswordMessage) {
            $this->handleMessage($message);
        }
    }

    protected function handleMessage(Message\ForgotPasswordMessage $message): void
    {
        if (!$this->mail->isEnabled()) {
            $this->logger->error('Cannot send "Forgot Password" message; mail is not configured or enabled.');
            return;
        }

        $user = $this->userRepo->find($message->userId);
        if (!$user instanceof Entity\User) {
            $this->logger->error(
                sprintf(
                    'Cannot send "Forgot Password" message: User ID %d not found.',
                    $message->userId
                )
            );
        }

        $locale = $this->factory->make(Locale::class);
        $locale->setLocale($message->locale);
        $locale->register();

        $message = $this->mail->createMessage();

        $message->setSubject(__('Account Recovery Link'));
        $message->setTo($user->getEmail());

        $view = $this->factory->make(View::class);

        $loginToken = $this->loginTokenRepo->createToken($user);
        $message->setBody($view->render('mail/forgot', [
            'token' => (string)$loginToken,
        ]));

        $this->mail->sendMessage($message);

        // Reset locale back to default.
        $locale = $this->factory->make(Locale::class);
        $locale->register();
    }
}
