<?php
namespace Controller\Frontend;

use Entity;
use Slim\Http\Request;
use Slim\Http\Response;

class SetupController extends BaseController
{
    public function init()
    {
        return null;
    }

    /**
     * Setup Routing Controls
     */
    public function indexAction(Request $request, Response $response): Response
    {
        $current_step = $this->_getSetupStep();

        return $this->redirectFromHere(['action' => $current_step]);
    }

    /**
     * Placeholder function for "setup complete" redirection.
     */
    public function completeAction(Request $request, Response $response): Response
    {
        $this->alert('<b>' . _('Setup has already been completed!') . '</b>', 'red');

        return $this->redirectHome();
    }

    /**
     * Setup Step 1:
     * Create Super Administrator Account
     */
    public function registerAction(Request $request, Response $response): Response
    {
        // Verify current step.
        $current_step = $this->_getSetupStep();
        if ($current_step != 'register') {
            return $this->redirectFromHere(['action' => $current_step]);
        }

        // Create first account form.
        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $data = $_POST;

            // Create actions and roles supporting Super Admninistrator.
            $role = new Entity\Role;
            $role->setName(_('Super Administrator'));

            $this->em->persist($role);
            $this->em->flush();

            $rha = new Entity\RolePermission($role);
            $rha->setActionName('administer all');

            $this->em->persist($rha);

            // Create user account.
            $user = new Entity\User;
            $user->setEmail($data['username']);
            $user->setAuthPassword($data['password']);
            $user->getRoles()->add($role);
            $this->em->persist($user);

            // Write to DB.
            $this->em->flush();

            // Log in the newly created user.

            /** @var \App\Auth $auth */
            $auth = $this->di[\App\Auth::class];
            $auth->authenticate($data['username'], $data['password']);

            $this->acl->reload();

            return $this->redirectFromHere(['action' => 'index']);
        }
    }

    /**
     * Setup Step 2:
     * Create Station and Parse Metadata
     */
    public function stationAction(Request $request, Response $response): Response
    {
        // Verify current step.
        $current_step = $this->_getSetupStep();
        if ($current_step != 'station') {
            return $this->redirectFromHere(['action' => $current_step]);
        }

        // Set up station form.
        $form_config = $this->config->forms->station->toArray();
        unset($form_config['groups']['admin']);
        unset($form_config['groups']['profile']['legend']);

        $form = new \App\Form($form_config);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            /** @var Entity\Repository\StationRepository $station_repo */
            $station_repo = $this->em->getRepository(Entity\Station::class);
            $station_repo->create($data, $this->di);

            return $this->redirectFromHere(['action' => 'settings']);
        }

        $this->view->form = $form;
    }

    /**
     * Setup Step 3:
     * Set site settings.
     */
    public function settingsAction(Request $request, Response $response): Response
    {
        // Verify current step.
        $current_step = $this->_getSetupStep();

        if ($current_step != 'settings') {
            return $this->redirectFromHere(['action' => $current_step]);
        }

        $form = new \App\Form($this->config->forms->settings->form);

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $existing_settings = $settings_repo->fetchArray(false);
        $form->setDefaults($existing_settings);

        if ($this->request->getMethod() == 'POST' && $form->isValid($_POST)) {
            $data = $form->getValues();

            // Mark setup as complete along with other settings changes.
            $data['setup_complete'] = time();

            $settings_repo->setSettings($data);

            // Notify the user and redirect to homepage.
            $this->alert('<b>' . _('Setup is now complete!') . '</b><br>' . _('Continue setting up your station in the main AzuraCast app.'),
                'green');

            return $this->redirectHome();
        }

        $this->view->form = $form;
    }

    /**
     * Determine which step of setup is currently active.
     *
     * @return string
     * @throws \App\Exception\NotLoggedIn
     */
    protected function _getSetupStep()
    {
        if ($this->em->getRepository('Entity\Settings')->getSetting('setup_complete', 0) != 0) {
            return 'complete';
        }

        // Step 1: Register
        $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')->getSingleScalarResult();
        if ($num_users == 0) {
            return 'register';
        }

        // If past "register" step, require login.

        /** @var \App\Auth $auth */
        $auth = $this->di[\App\Auth::class];
        if (!$auth->isLoggedIn()) {
            throw new \App\Exception\NotLoggedIn;
        }

        // Step 2: Set up Station
        $num_stations = $this->em->createQuery('SELECT COUNT(s.id) FROM Entity\Station s')->getSingleScalarResult();
        if ($num_stations == 0) {
            return 'station';
        }

        // Step 3: System Settings
        return 'settings';
    }
}
