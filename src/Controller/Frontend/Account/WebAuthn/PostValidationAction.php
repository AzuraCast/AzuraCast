<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account\WebAuthn;

use App\Controller\Traits\UsesWebAuthnTrait;
use App\Entity\Repository\UserPasskeyRepository;
use App\Entity\UserPasskey;
use App\Http\Response;
use App\Http\ServerRequest;
use InvalidArgumentException;
use Mezzio\Session\SessionCookiePersistenceInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class PostValidationAction
{
    use UsesWebAuthnTrait;

    public function __construct(
        private readonly UserPasskeyRepository $passkeyRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $webAuthn = $this->getWebAuthn($request);

        $parsedBody = $request->getParsedBody();
        $validateData = json_decode($parsedBody['validateData'] ?? '', true, 512, JSON_THROW_ON_ERROR);

        $challenge = $this->getChallenge($request);

        try {
            $record = $this->passkeyRepo->findById(base64_decode($validateData['id']));
            if (!($record instanceof UserPasskey)) {
                throw new InvalidArgumentException('This passkey does not correspond to a valid user.');
            }

            // Validate the passkey. Exception thrown if invalid.
            $webAuthn->processGet(
                base64_decode($validateData['clientDataJSON'] ?? ''),
                base64_decode($validateData['authenticatorData'] ?? ''),
                base64_decode($validateData['signature'] ?? ''),
                $record->getPasskey()->getPublicKeyPem(),
                $challenge
            );
        } catch (Throwable $e) {
            $flash = $request->getFlash();
            $flash->error(
                '<b>' . __('Login unsuccessful') . '</b><br>' . $e->getMessage(),
            );

            return $response->withRedirect($request->getRouter()->named('dashboard'));
        }

        $user = $record->getUser();

        $auth = $request->getAuth();
        $auth->setUser($user, true);

        $session = $request->getSession();
        if ($session instanceof SessionCookiePersistenceInterface) {
            $session->persistSessionFor(86400 * 14);
        }

        $acl = $request->getAcl();
        $acl->reload();

        $flash = $request->getFlash();
        $flash->success(
            '<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(),
        );

        $referrer = $session->get('login_referrer');
        return $response->withRedirect(
            (!empty($referrer)) ? $referrer : $request->getRouter()->named('dashboard')
        );
    }
}
