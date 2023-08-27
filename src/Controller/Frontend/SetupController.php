<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Container\EntityManagerAwareTrait;
use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Repository\RolePermissionRepository;
use App\Entity\User;
use App\Exception\NotLoggedInException;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\SettingsComponent;
use App\VueComponent\StationFormComponent;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class SetupController
{
    use EntityManagerAwareTrait;
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly RolePermissionRepository $permissionRepo,
        private readonly ValidatorInterface $validator,
        private readonly StationFormComponent $stationFormComponent,
        private readonly SettingsComponent $settingsComponent
    ) {
    }

    /**
     * Setup Routing Controls
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function indexAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $currentStep = $this->getSetupStep($request);
        return $response->withRedirect($request->getRouter()->named('setup:' . $currentStep));
    }

    /**
     * Setup Step 1:
     * Create Super Administrator Account
     */
    public function registerAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        // Verify current step.
        $currentStep = $this->getSetupStep($request);
        if ($currentStep !== 'register' && $this->environment->isProduction()) {
            return $response->withRedirect($request->getRouter()->named('setup:' . $currentStep));
        }

        $csrf = $request->getCsrf();

        $error = null;

        if ($request->isPost()) {
            try {
                $data = $request->getParams();

                $csrf->verify($data['csrf'] ?? null, 'register');

                if (empty($data['username']) || empty($data['password'])) {
                    throw new InvalidArgumentException('Username and password required.');
                }

                $role = $this->permissionRepo->ensureSuperAdministratorRole();

                // Create user account.
                $user = new User();
                $user->setEmail($data['username']);
                $user->setNewPassword($data['password']);
                $user->getRoles()->add($role);

                $errors = $this->validator->validate($user);
                if (count($errors) > 0) {
                    throw ValidationException::fromValidationErrors($errors);
                }

                $this->em->persist($user);
                $this->em->flush();

                // Log in the newly created user.
                $auth = $request->getAuth();
                $auth->authenticate($data['username'], $data['password']);

                $acl = $request->getAcl();
                $acl->reload();

                return $response->withRedirect($request->getRouter()->named('setup:index'));
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Setup/Register',
            id: 'setup-register',
            layout: 'minimal',
            title: __('Set Up AzuraCast'),
            props: [
                'csrf' => $csrf->generate('register'),
                'error' => $error,
            ]
        );
    }

    /**
     * Setup Step 2:
     * Create Station and Parse Metadata
     */
    public function stationAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        // Verify current step.
        $currentStep = $this->getSetupStep($request);
        if ($currentStep !== 'station' && $this->environment->isProduction()) {
            return $response->withRedirect($request->getRouter()->named('setup:' . $currentStep));
        }

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Setup/Station',
            id: 'setup-station',
            title: __('Create a New Radio Station'),
            props: array_merge(
                $this->stationFormComponent->getProps($request),
                [
                    'createUrl' => $router->named('api:admin:stations'),
                    'continueUrl' => $router->named('setup:settings'),
                ]
            )
        );
    }

    /**
     * Setup Step 3:
     * Set site settings.
     */
    public function settingsAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        // Verify current step.
        $currentStep = $this->getSetupStep($request);
        if ($currentStep !== 'settings' && $this->environment->isProduction()) {
            return $response->withRedirect($router->named('setup:' . $currentStep));
        }

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Setup/Settings',
            id: 'setup-settings',
            title: __('System Settings'),
            props: [
                ...$this->settingsComponent->getProps($request),
                'continueUrl' => $router->named('dashboard'),
            ],
        );
    }

    /**
     * Placeholder function for "setup complete" redirection.
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function completeAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $request->getFlash()->error('<b>' . __('Setup has already been completed!') . '</b>');

        return $response->withRedirect($request->getRouter()->named('dashboard'));
    }

    /**
     * Determine which step of setup is currently active.
     *
     * @param ServerRequest $request
     */
    private function getSetupStep(ServerRequest $request): string
    {
        $settings = $this->readSettings();
        if ($settings->isSetupComplete()) {
            return 'complete';
        }

        // Step 1: Register
        $numUsers = (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(u.id) FROM App\Entity\User u
            DQL
        )->getSingleScalarResult();

        if (0 === $numUsers) {
            return 'register';
        }

        // If past "register" step, require login.
        $auth = $request->getAuth();
        if (!$auth->isLoggedIn()) {
            throw new NotLoggedInException();
        }

        // Step 2: Set up Station
        $numStations = (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(s.id) FROM App\Entity\Station s
            DQL
        )->getSingleScalarResult();

        if (0 === $numStations) {
            return 'station';
        }

        // Step 3: System Settings
        return 'settings';
    }
}
