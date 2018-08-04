<?php
namespace App\Controller\Frontend;

use App\Http\Request;
use App\Http\Response;
use App\Entity;

class IndexController
{
    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /**
     * IndexController constructor.
     * @param Entity\Repository\SettingsRepository $settings_repo
     */
    public function __construct(Entity\Repository\SettingsRepository $settings_repo)
    {
        $this->settings_repo = $settings_repo;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        // Redirect to complete setup, if it hasn't been completed yet.
        if ($this->settings_repo->getSetting('setup_complete', 0) === 0) {
            return $response->redirectToRoute('setup:index');
        }

        // Redirect to login screen if the user isn't logged in.
        $user = $request->getAttribute(Request::ATTRIBUTE_USER);

        if (!($user instanceof Entity\User)) {
            // Redirect to a custom homepage URL if specified in settings.
            $homepage_redirect = trim($this->settings_repo->getSetting('homepage_redirect_url'));

            if (!empty($homepage_redirect)) {
                return $response->withRedirect($homepage_redirect, 302);
            }

            return $response->redirectToRoute('account:login');
        }

        // Redirect to dashboard if no other custom redirection rules exist.
        return $response->redirectToRoute('dashboard');
    }
}
