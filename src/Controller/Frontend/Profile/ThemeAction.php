<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Profile;

use App\Enums\SupportedThemes;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class ThemeAction
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $user = $request->getUser();

        $currentTheme = $user->getThemeEnum();
        $newTheme = match ($currentTheme) {
            SupportedThemes::Dark => SupportedThemes::Light,
            default => SupportedThemes::Dark
        };
        $user->setTheme($newTheme->value);

        $this->em->persist($user);
        $this->em->flush();

        $referrer = $request->getHeaderLine('Referer');
        return $response->withRedirect(
            $referrer ?: $request->getRouter()->named('dashboard')
        );
    }
}
