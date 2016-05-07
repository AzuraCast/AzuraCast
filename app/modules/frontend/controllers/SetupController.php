<?php
namespace Modules\Frontend\Controllers;

use Entity\Settings;

class SetupController extends BaseController
{
    public function init()
    {
        if (Settings::getSetting('setup_complete', 0) != 0)
            return $this->redirectToRoute([]);

        return NULL;
    }

    /**
     * Setup Routing Controls
     */
    public function indexAction()
    {
        // Step 1: Register
        $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')->getSingleScalarResult();
        if ($num_users == 0)
            return $this->redirectFromHere(['action' => 'register']);

        // Step 2: Set up Station
        $num_stations = $this->em->createQuery('SELECT COUNT(s.id) FROM Entity\Station s')->getSingleScalarResult();
        if ($num_stations == 0)
            return $this->redirectFromHere(['action' => 'station']);

        // Step 3: System Settings
        return $this->redirectFromHere(['action' => 'settings']);
    }
    
    /**
     * Setup Step 1:
     * Create Super Administrator Account
     */
    public function registerAction()
    {
        // Check for user accounts.
        $num_users = $this->em->createQuery('SELECT COUNT(u.id) FROM Entity\User u')->getSingleScalarResult();
        if ($num_users != 0)
            return $this->redirectFromHere(['action' => 'index']);

        // Create first account form.
        $this->view->setTemplateAfter('minimal');

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
            $user->email = $data['email'];
            $user->setAuthPassword($data['password']);
            $user->roles->add($role);
            $this->em->persist($user);

            // Write to DB.
            $this->em->flush();

            $login_credentials = array(
                'username'  => $data['email'],
                'password'  => $data['auth_password'],
            );
            $login_success = $this->auth->authenticate($login_credentials);

            return $this->redirectFromHere(['action' => 'index']);
        }
    }

    /**
     * Setup Step 2:
     * Create Station and Parse Metadata
     */
    public function stationAction()
    {
        $form_config = $this->module_config['admin']->forms->station->toArray();
        unset($form_config['groups']['admin']);

        $form = new \App\Form($form_config);

        if (!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            $station = new \Entity\Station;
            $station->fromArray($data);

            // Create path for station.
            $station_base_dir = realpath(APP_INCLUDE_ROOT.'/..').'/stations';
            @mkdir($station_base_dir);

            $station_dir = $station_base_dir.'/'.$station->getShortName();
            $station->radio_base_dir = $station_dir;

            // Load configuration from adapter to pull source and admin PWs.
            $frontend_adapter = $station->getFrontendAdapter();
            $frontend_adapter->read();

            // Write an empty placeholder configuration.
            $backend_adapter = $station->getBackendAdapter();
            $backend_adapter->write();
            $backend_adapter->restart();

            // Save changes and continue to the last setup step.
            $station->save();

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

    }
}
