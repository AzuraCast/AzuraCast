<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Entity;
use App\Environment;
use App\Exception\NotLoggedInException;
use App\Form\SettingsForm;
use App\Form\StationForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use DI\FactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class SetupController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Environment $environment
    ) {
    }

    /**
     * Setup Routing Controls
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $current_step = $this->getSetupStep($request);
        return $response->withRedirect((string)$request->getRouter()->named('setup:' . $current_step));
    }

    /**
     * Determine which step of setup is currently active.
     *
     * @param ServerRequest $request
     */
    protected function getSetupStep(ServerRequest $request): string
    {
        $settings = $this->settingsRepo->readSettings();
        if ($settings->isSetupComplete()) {
            return 'complete';
        }

        // Step 1: Register
        $num_users = (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(u.id) FROM App\Entity\User u
            DQL
        )->getSingleScalarResult();

        if (0 === $num_users) {
            return 'register';
        }

        // If past "register" step, require login.
        $auth = $request->getAuth();
        if (!$auth->isLoggedIn()) {
            throw new NotLoggedInException();
        }

        // Step 2: Set up Station
        $num_stations = (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(s.id) FROM App\Entity\Station s
            DQL
        )->getSingleScalarResult();

        if (0 === $num_stations) {
            return 'station';
        }

        // Step 3: System Settings
        return 'settings';
    }

    /**
     * Placeholder function for "setup complete" redirection.
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function completeAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $request->getFlash()->addMessage('<b>' . __('Setup has already been completed!') . '</b>', Flash::ERROR);

        return $response->withRedirect((string)$request->getRouter()->named('dashboard'));
    }

    /**
     * Setup Step 1:
     * Create Super Administrator Account
     */
    public function registerAction(ServerRequest $request, Response $response): ResponseInterface
    {
        // Verify current step.
        $current_step = $this->getSetupStep($request);
        if ($current_step !== 'register' && $this->environment->isProduction()) {
            return $response->withRedirect((string)$request->getRouter()->named('setup:' . $current_step));
        }

        // Create first account form.
        $data = $request->getParams();

        if (!empty($data['username']) && !empty($data['password'])) {
            // Create actions and roles supporting Super Admninistrator.
            $role = new Entity\Role();
            $role->setName(__('Super Administrator'));

            $this->em->persist($role);
            $this->em->flush();

            $rha = new Entity\RolePermission($role);
            $rha->setActionName('administer all');

            $this->em->persist($rha);

            // Create user account.
            $user = new Entity\User();
            $user->setEmail($data['username']);
            $user->setNewPassword($data['password']);
            $user->getRoles()->add($role);
            $this->em->persist($user);

            // Write to DB.
            $this->em->flush();

            // Log in the newly created user.
            $auth = $request->getAuth();
            $auth->authenticate($data['username'], $data['password']);

            $acl = $request->getAcl();
            $acl->reload();

            return $response->withRedirect((string)$request->getRouter()->named('setup:index'));
        }

        return $request->getView()
            ->renderToResponse($response, 'frontend/setup/register');
    }

    /**
     * Setup Step 2:
     * Create Station and Parse Metadata
     */
    public function stationAction(
        ServerRequest $request,
        Response $response,
        FactoryInterface $factory
    ): ResponseInterface {
        $stationForm = $factory->make(StationForm::class);

        // Verify current step.
        $current_step = $this->getSetupStep($request);
        if ($current_step !== 'station' && $this->environment->isProduction()) {
            return $response->withRedirect((string)$request->getRouter()->named('setup:' . $current_step));
        }

        if (false !== $stationForm->process($request)) {
            return $response->withRedirect((string)$request->getRouter()->named('setup:settings'));
        }

        return $request->getView()->renderToResponse(
            $response,
            'frontend/setup/station',
            [
                'form' => $stationForm,
            ]
        );
    }

    /**
     * Setup Step 3:
     * Set site settings.
     */
    public function settingsAction(
        ServerRequest $request,
        Response $response,
        FactoryInterface $factory
    ): ResponseInterface {
        $settingsForm = $factory->make(SettingsForm::class);

        // Verify current step.
        $current_step = $this->getSetupStep($request);
        if ($current_step !== 'settings' && $this->environment->isProduction()) {
            return $response->withRedirect((string)$request->getRouter()->named('setup:' . $current_step));
        }

        if ($settingsForm->process($request)) {
            $settings = $this->settingsRepo->readSettings();
            $settings->updateSetupComplete();
            $this->settingsRepo->writeSettings($settings);

            // Notify the user and redirect to homepage.
            $request->getFlash()->addMessage(
                sprintf(
                    '<b>%s</b><br>%s',
                    __('Setup is now complete!'),
                    __('Continue setting up your station in the main AzuraCast app.')
                ),
                Flash::SUCCESS
            );

            return $response->withRedirect((string)$request->getRouter()->named('dashboard'));
        }

        return $request->getView()->renderToResponse(
            $response,
            'frontend/setup/settings',
            [
                'form' => $settingsForm,
            ]
        );
    }
}
