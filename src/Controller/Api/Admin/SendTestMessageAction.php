<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Mail;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SendTestMessageAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        ValidatorInterface $validator,
        Mail $mail
    ): ResponseInterface {
        $emailAddress = $request->getParam('email', '');

        $errors = $validator->validate(
            $emailAddress,
            [
                new Required(),
                new Email(),
            ]
        );
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        try {
            $email = $mail->createMessage();
            $email->to($emailAddress);
            $email->subject(
                __('Test Message')
            );
            $email->text(
                __(
                    'This is a test message from AzuraCast. If you are receiving this message, it means your '
                    . 'e-mail settings are configured correctly.'
                )
            );

            $mail->send($email);
        } catch (TransportException $e) {
            return $response->withStatus(400)->withJson(Entity\Api\Error::fromException($e));
        }

        return $response->withJson(
            new Entity\Api\Status(
                true,
                __('Test message sent successfully.')
            )
        );
    }
}
