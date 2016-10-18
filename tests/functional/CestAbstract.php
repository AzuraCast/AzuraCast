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
    {
        if (!empty($this->login_cookie))
        {
            $I->setCookie('PHPSESSID', $this->login_cookie);
        }
    }

    public function _after(FunctionalTester $I)
    {}

    protected $login_username = 'azuracast@azuracast.com';
    protected $login_password = 'AzuraCastFunctionalTests!';
    protected $login_cookie = null;

    protected function setupIncomplete(FunctionalTester $I)
    {
        $settings_repo = $this->em->getRepository(\Entity\Settings::class);
        $settings_repo->setSetting('setup_complete', 0);

        $this->_cleanTables();
    }

    protected function setupComplete(FunctionalTester $I)
    {
        $settings_repo = $this->em->getRepository(\Entity\Settings::class);
        $settings_repo->setSetting('setup_complete', time());

        $this->_cleanTables();


    }

    protected function _cleanTables()
    {
        $clean_tables = ['Entity\User', 'Entity\Role', 'Entity\Station'];
        foreach($clean_tables as $clean_table)
            $this->em->createQuery('DELETE FROM '.$clean_table.' t')->execute();
    }

    protected function login(FunctionalTester $I)
    {
        if (empty($this->login_cookie))
        {
            $I->wantTo('Log in to the application.');

            $I->amOnPage('/');
            $I->seeInCurrentUrl('/login');

            $I->submitForm('#login-form', [
                'username' => $this->login_username,
                'password' => $this->login_password,
            ]);

            $I->seeInSource('Logged in');

            $this->login_cookie = $I->grabCookie('PHPSESSID');
        }
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