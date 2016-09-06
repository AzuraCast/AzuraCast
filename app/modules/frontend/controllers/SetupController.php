<?php
namespace Modules\Frontend\Controllers;

use Entity\Settings;
use Entity\Station;

class SetupController extends BaseController
{
    public function init()
    {
        return NULL;
    }

    /**
     * Setup Routing Controls
     */
    public function indexAction()
    {
        $current_step = $this->_getSetupStep();
        return $this->redirectFromHere(['action' => $current_step]);
    }

    /**
     * Placeholder function for "setup complete" redirection.
     */
    public function completeAction()
    {
        $this->alert('<b>Setup has already been completed!</b>', 'red');

        return $this->redirectHome();
    }
    
    /**
     * Setup Step 1:
     * Create Super Administrator Account
     */
    public function registerAction()
    {
        // Verify current step.
        $current_step = $this->_getSetupStep();
        if ($current_step != 'register')
            return $this->redirectFromHere(['action' => $current_step]);

        // Create first account form.
        $this->view->setLayout('minimal');

        $form = new \App\Form($this->current_module_config->forms->register);
        
        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            // Create actions and roles supporting Super Admninistrator.
            $action = new \Entity\Action;
            $action->name = 'administer all';
            $this->em->persist($action);

            $role = new \Entity\Role;
            $role->name = 'Super Administrator';
            $role->actions->add($action);
            $this->em->persist($role);

            // Create user account.
            $user = new \Entity\User;
            $user->email = $data['username'];
            $user->setAuthPassword($data['password']);
            $user->roles->add($role);
            $this->em->persist($user);

            // Write to DB.
            $this->em->flush();

            // Log in the newly created user.
            $this->auth->authenticate($data);

            return $this->redirectFromHere(['action' => 'index']);
        }
    }

    /**
     * Setup Step 2:
     * Create Station and Parse Metadata
     */
    public function stationAction()
    {
        // Verify current step.
        $current_step = $this->_getSetupStep();
        if ($current_step != 'station')
            return $this->redirectFromHere(['action' => $current_step]);

        // Set up station form.
        $form_config = $this->module_config['admin']->forms->station->toArray();
        unset($form_config['groups']['admin']);
        unset($form_config['groups']['profile']['legend']);

        $form = new \App\Form($form_config);

        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            Station::create($data);

            return $this->redirectFromHere(['action' => 'settings']);
        }

        $this->view->form = $form;
    }

    /**
     * Setup Step 3:
     * Set site settings.
     */
    public function settingsAction()
    {
        // Verify current step.
        $current_step = $this->_getSetupStep();

        if ($current_step != 'settings')
            return $this->redirectFromHere(['action' => $current_step]);

        $form = new \App\Form($this->module_config['admin']->forms->settings->form);

        $existing_settings = Settings::fetchArray(FALSE);
        $form->setDefaults($existing_settings);

        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            // Mark setup as complete along with other settings changes.
            $data['setup_complete'] = time();

            Settings::setSettings($data);

            // Notify the user and redirect to homepage.
            $this->alert('<b>Setup is now complete!</b><br>Continue setting up your station in the main AzuraCast app.', 'green');
            return $this->redirectHome();
        }

        $this->renderForm($form, 'edit', 'Site Settings');
    }

    /**
     * Determine which step of setup is currently active.
     * 
     * @return string
     * @throws \App\Exception\NotLoggedIn
     */
    protected function _getSetupStep()
    {
        if (Settings::getSetting('setup_complete', 0) != 0)
            return 'complete';

        // Step 1: Register
        $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')->getSingleScalarResult();
        if ($num_users == 0)
            return 'register';

        // If past "register" step, require login.
        if (!$this->auth->isLoggedIn())
            throw new \App\Exception\NotLoggedIn;

        // Step 2: Set up Station
        $num_stations = $this->em->createQuery('SELECT COUNT(s.id) FROM Entity\Station s')->getSingleScalarResult();
        if ($num_stations == 0)
            return 'station';

        // Step 3: System Settings
        return 'settings';
    }
}
