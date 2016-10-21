<?php
use Slim\App;
use Interop\Container\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;

abstract class CestAbstract
{
    /**
     * @var ContainerInterface
     */
    protected $di;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    protected function _inject(\App\Tests\Module $tests_module)
    {
        $this->di = $tests_module->container;
        $this->em = $tests_module->em;
    }

    public function _before(FunctionalTester $I)
    {}

    public function _after(FunctionalTester $I)
    {}

    protected $login_username = 'azuracast@azuracast.com';
    protected $login_password = 'AzuraCastFunctionalTests!';
    protected $login_cookie = null;

    protected $test_station = null;

    protected function setupIncomplete(FunctionalTester $I)
    {
        $settings_repo = $this->em->getRepository(\Entity\Settings::class);
        $settings_repo->setSetting('setup_complete', 0);

        $this->_cleanTables();
    }

    protected function setupComplete(FunctionalTester $I)
    {
        $this->_cleanTables();

        /* Walk through the steps of completing setup automatically. */

        // Create administrator account.
        $role = new \Entity\Role;
        $role->name = 'Super Administrator';

        $this->em->persist($role);
        $this->em->flush();

        $rha = new \Entity\RolePermission;
        $rha->fromArray($this->em, [
            'role' => $role,
            'action_name' => 'administer all',
        ]);
        $this->em->persist($rha);

        // Create user account.
        $user = new \Entity\User;
        $user->email = $this->login_username;
        $user->setAuthPassword($this->login_password);
        $user->roles->add($role);

        $this->em->persist($user);
        $this->em->flush();

        $this->di['acl']->reload();

        // Create station.
        $frontends = \Entity\Station::getFrontendAdapters();
        $backends = \Entity\Station::getBackendAdapters();

        $station_info = [
            'id'            => 25,
            'name'          => 'Functional Test Radio',
            'description'   => 'Test radio station.',
            'frontend_type' => $frontends['default'],
            'backend_type'  => $backends['default'],
        ];

        $station_repo = $this->em->getRepository(\Entity\Station::class);
        $this->test_station = $station_repo->create($station_info, $this->di);

        // Set settings.
        $settings_repo = $this->em->getRepository(\Entity\Settings::class);
        $settings_repo->setSetting('setup_complete', time());
        $settings_repo->setSetting('base_url', 'localhost');
    }

    protected function _cleanTables()
    {
        $clean_tables = ['Entity\User', 'Entity\Role', 'Entity\Station'];
        foreach($clean_tables as $clean_table)
            $this->em->createQuery('DELETE FROM '.$clean_table.' t')->execute();
    }

    protected function login(FunctionalTester $I)
    {
        $I->wantTo('Log in to the application.');

        $I->amOnPage('/');
        $I->seeInCurrentUrl('/login');

        $I->submitForm('#login-form', [
            'username' => $this->login_username,
            'password' => $this->login_password,
        ]);

        $I->seeInSource('Logged in');
    }

    protected function logout(FunctionalTester $I)
    {
        if (!empty($this->login_cookie))
        {
            $I->wantTo('Log out of the application.');

            $I->amOnPage('/logout');
            $I->seeInCurrentUrl('/login');

            $this->login_cookie = null;
        }
    }

    protected function cleanup(FunctionalTester $I)
    {
        $auth = $this->di['auth'];
        $auth->logout();

        if ($this->test_station instanceof \Entity\Station)
        {
            $station_repo = $this->em->getRepository(\Entity\Station::class);
            $this->test_station = $station_repo->destroy($this->test_station, $this->di);
        }
    }
}