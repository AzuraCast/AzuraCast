<?php

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Psr\Container\ContainerInterface;

abstract class CestAbstract
{
    protected ContainerInterface $di;

    protected App\Environment $environment;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Entity\Repository\StationRepository $stationRepo;

    protected ReloadableEntityManagerInterface $em;

    protected string $login_username = 'azuracast@azuracast.com';
    protected string $login_password = 'AzuraCastFunctionalTests!';
    private ?Entity\Station $test_station = null;

    protected function _inject(App\Tests\Module $tests_module): void
    {
        $this->di = $tests_module->container;
        $this->em = $tests_module->em;

        $this->settingsRepo = $this->di->get(Entity\Repository\SettingsRepository::class);
        $this->stationRepo = $this->di->get(Entity\Repository\StationRepository::class);
        $this->environment = $this->di->get(App\Environment::class);
    }

    public function _after(FunctionalTester $I): void
    {
        $this->em->clear();

        if (null !== $this->test_station) {
            $testStation = $this->getTestStation();

            $this->stationRepo->destroy($testStation);
            $this->test_station = null;

            $this->em->clear();
        }
    }

    protected function setupIncomplete(FunctionalTester $I): void
    {
        $I->wantTo('Start with an incomplete setup.');

        $this->_cleanTables();

        $settings = $this->settingsRepo->readSettings();
        $settings->setSetupCompleteTime(0);

        $this->settingsRepo->writeSettings($settings);
    }

    protected function setupComplete(FunctionalTester $I): void
    {
        $this->_cleanTables();

        /* Walk through the steps of completing setup automatically. */

        // Create administrator account.
        $role = new Entity\Role;
        $role->setName('Super Administrator');
        $this->em->persist($role);

        $rha = new Entity\RolePermission($role);
        $rha->setActionName(App\Acl::GLOBAL_ALL);
        $this->em->persist($rha);

        // Create user account.
        $user = new Entity\User;
        $user->setName('AzuraCast Test User');
        $user->setEmail($this->login_username);
        $user->setNewPassword($this->login_password);
        $user->getRoles()->add($role);
        $user->setLocale('en_US.UTF-8');

        $this->em->persist($user);
        $this->em->flush();

        $this->di->get(App\Acl::class)->reload();

        $test_station = new Entity\Station();
        $test_station->setName('Functional Test Radio');
        $test_station->setDescription('Test radio station.');
        $test_station->setFrontendType(App\Radio\Adapters::DEFAULT_FRONTEND);
        $test_station->setBackendType(App\Radio\Adapters::DEFAULT_BACKEND);

        $this->test_station = $this->stationRepo->create($test_station);

        // Set settings.
        $settings = $this->settingsRepo->readSettings();
        $settings->updateSetupComplete();
        $settings->setBaseUrl('http://localhost');
        $this->settingsRepo->writeSettings($settings);
    }

    protected function getTestStation(): Entity\Station
    {
        if ($this->test_station instanceof Entity\Station) {
            $testStation = $this->em->refetch($this->test_station);
            if ($testStation instanceof Entity\Station) {
                return $testStation;
            }

            $this->test_station = null;
        }

        throw new RuntimeException('Test station is not established.');
    }

    protected function uploadTestSong(): Entity\StationMedia
    {
        $testStation = $this->getTestStation();

        $songSrc = '/var/azuracast/www/resources/error.mp3';

        $storageLocation = $testStation->getMediaStorageLocation();

        $storageFs = $storageLocation->getFilesystem();
        $storageFs->upload($songSrc, 'test.mp3');

        /** @var Entity\Repository\StationMediaRepository $mediaRepo */
        $mediaRepo = $this->di->get(Entity\Repository\StationMediaRepository::class);

        return $mediaRepo->getOrCreate($storageLocation, 'test.mp3');
    }

    protected function _cleanTables(): void
    {
        $clean_tables = [
            Entity\User::class,
            Entity\Role::class,
            Entity\Station::class,
            Entity\Settings::class,
        ];

        foreach ($clean_tables as $clean_table) {
            $this->em->createQuery('DELETE FROM ' . $clean_table . ' t')->execute();
        }

        $this->em->clear();
    }

    protected function login(FunctionalTester $I): void
    {
        $this->setupComplete($I);

        $I->amOnPage('/');
        $I->seeInCurrentUrl('/login');

        $I->submitForm(
            '#login-form',
            [
                'username' => $this->login_username,
                'password' => $this->login_password,
            ]
        );

        $I->seeInSource('Logged In');
    }

    protected function testCrudApi(
        FunctionalTester $I,
        string $listUrl,
        array $createJson = [],
        array $editJson = []
    ): void {
        // Create new record
        $I->sendPOST($listUrl, $createJson);

        $I->seeResponseCodeIs(200);

        $newRecord = $I->grabDataFromResponseByJsonPath('links.self');
        $newRecordSelfLink = $newRecord[0];

        // Get single record.
        $I->sendGET($newRecordSelfLink);

        $I->seeResponseContainsJson($createJson);

        // Modify record.
        $I->sendPUT($newRecordSelfLink, $editJson);

        // List all records.
        $I->sendGET($newRecordSelfLink);

        $I->seeResponseContainsJson($editJson);

        // Delete Record
        $I->sendDELETE($newRecordSelfLink);

        $I->sendGET($newRecordSelfLink);
        $I->seeResponseCodeIs(404);
    }
}
