<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Mail;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[
    OA\Post(
        path: '/admin/send-test-message',
        operationId: 'adminSendTestEmail',
        summary: 'Send a test e-mail to confirm mail delivery settings.',
        tags: [OpenApi::TAG_ADMIN],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
final readonly class SendTestMessageAction implements SingleActionInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private Mail $mail,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $emailAddress = Types::string($request->getParam('email'));

        $errors = $this->validator->validate(
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
            $email = $this->mail->createMessage();
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

            $this->mail->send($email);
        } catch (TransportException $e) {
            return $response->withStatus(400)->withJson(Error::fromException($e));
        }

        return $response->withJson(
            new Status(
                true,
                __('Test message sent successfully.')
            )
        );
    }
}
