<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Profile;

use App\Container\EntityManagerAwareTrait;
use App\Enums\SupportedThemes;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ThemeAction
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $user = $request->getUser();

        $currentTheme = $user->getTheme();
        $newTheme = match ($currentTheme) {
            SupportedThemes::Dark => SupportedThemes::Light,
            default => SupportedThemes::Dark
        };
        $user->setTheme($newTheme);

        $this->em->persist($user);
        $this->em->flush();

        $referrer = $request->getHeaderLine('Referer');
        return $response->withRedirect(
            $referrer ?: $request->getRouter()->named('dashboard')
        );
    }
}
