<?php

declare(strict_types=1);

namespace App\Controller\Traits;

use App\Container\SettingsAwareTrait;
use App\Http\ServerRequest;
use InvalidArgumentException;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;

trait UsesWebAuthnTrait
{
    use SettingsAwareTrait;

    protected const SESSION_CHALLENGE_KEY = 'webauthn_challenge';
    protected const WEBAUTHN_TIMEOUT = 300;

    protected ?WebAuthn $webAuthn = null;

    protected function getWebAuthn(ServerRequest $request): WebAuthn
    {
        if (null === $this->webAuthn) {
            $settings = $this->settingsRepo->readSettings();
            $router = $request->getRouter();

            $this->webAuthn = new WebAuthn(
                $settings->getInstanceName() ?? 'AzuraCast',
                $router->getBaseUrl()->getHost()
            );
        }

        return $this->webAuthn;
    }

    protected function setChallenge(
        ServerRequest $request,
        ByteBuffer $challenge
    ): void {
        $session = $request->getSession();

        $session->set(
            self::SESSION_CHALLENGE_KEY,
            $challenge->getHex()
        );
    }

    protected function getChallenge(
        ServerRequest $request
    ): ByteBuffer {
        $session = $request->getSession();
        $challengeRaw = $session->get(self::SESSION_CHALLENGE_KEY);

        if (empty($challengeRaw)) {
            throw new InvalidArgumentException('Invalid challenge provided.');
        }

        return ByteBuffer::fromHex($challengeRaw);
    }
}
