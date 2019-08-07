<?php
namespace App\Controller\Frontend;

use App\Entity;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexController
{
    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(Entity\Settings::class);

        $this->settings_repo = $settings_repo;
    }

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Redirect to complete setup, if it hasn't been completed yet.
        if ($this->settings_repo->getSetting(Entity\Settings::SETUP_COMPLETE, 0) === 0) {
            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('setup:index'));
        }

        // Redirect to login screen if the user isn't logged in.
        $user = $request->getAttribute(RequestHelper::ATTR_USER);

        if (!($user instanceof Entity\User)) {
            // Redirect to a custom homepage URL if specified in settings.
            $homepage_redirect = trim($this->settings_repo->getSetting(Entity\Settings::HOMEPAGE_REDIRECT_URL));

            if (!empty($homepage_redirect)) {
                return ResponseHelper::withRedirect($response, $homepage_redirect, 302);
            }

            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('account:login'));
        }

        // Redirect to dashboard if no other custom redirection rules exist.
        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('dashboard'));
    }
}
