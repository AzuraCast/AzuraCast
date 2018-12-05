<?php
use Slim\App;
use Interop\Container\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity;

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

    protected function _inject(\Azura\Tests\Module $tests_module)
    {
        $this->di = $tests_module->container;
        $this->em = $tests_module->em;
    }

    public function _before(FunctionalTester $I)
    {}

    public function _after(FunctionalTester $I)
    {
        /** @var \App\Auth $auth */
        $auth = $this->di[\App\Auth::class];
        $auth->logout();

        if ($this->test_station instanceof Entity\Station)
        {
            /** @var Entity\Repository\StationRepository $station_repo */
            $station_repo = $this->em->getRepository(Entity\Station::class);

            $this->test_station = $station_repo->destroy($this->test_station);
        }
    }

    protected $login_username = 'azuracast@azuracast.com';
    protected $login_password = 'AzuraCastFunctionalTests!';
    protected $login_cookie = null;

    /** @var Entity\Station|null */
    protected $test_station = null;

    protected function setupIncomplete(FunctionalTester $I)
    {
        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);
        $settings_repo->setSetting('setup_complete', 0);

        $this->_cleanTables();
    }

    protected function setupComplete(FunctionalTester $I)
    {
        $this->_cleanTables();

        /* Walk through the steps of completing setup automatically. */

        // Create administrator account.
        $role = new Entity\Role;
        $role->setName('Super Administrator');

        $this->em->persist($role);
        $this->em->flush();

        $rha = new Entity\RolePermission($role);
        $rha->setActionName('administer all');
        $this->em->persist($rha);

        // Create user account.
        $user = new Entity\User;
        $user->setName('AzuraCast Test User');
        $user->setEmail($this->login_username);
        $user->setAuthPassword($this->login_password);

        $user->getRoles()->add($role);

        $this->em->persist($user);
        $this->em->flush();

        $this->di[\App\Acl::class]->reload();

        // Create station.
        $station_info = [
            'id'            => 25,
            'name'          => 'Functional Test Radio',
            'description'   => 'Test radio station.',
            'frontend_type' => \App\Radio\Adapters::DEFAULT_FRONTEND,
            'backend_type'  => \App\Radio\Adapters::DEFAULT_BACKEND,
        ];

        /** @var Entity\Repository\StationRepository $station_repo */
        $station_repo = $this->em->getRepository(Entity\Station::class);

        $this->test_station = $station_repo->create($station_info);

        // Set settings.

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);
        $settings_repo->setSetting('setup_complete', time());
        $settings_repo->setSetting('base_url', 'localhost');
    }

    protected function _cleanTables()
    {
        $clean_tables = [
            Entity\User::class,
            Entity\Role::class,
            Entity\Station::class,
        ];
        foreach($clean_tables as $clean_table)
            $this->em->createQuery('DELETE FROM '.$clean_table.' t')->execute();

        /** @var \App\Auth $auth */
        $auth = $this->di[\App\Auth::class];
        $auth->logout();
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
}
