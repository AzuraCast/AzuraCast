<?php

declare(strict_types=1);

namespace Functional;

use App\Acl;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\ApiKey;
use App\Entity\Repository\SettingsRepository;
use App\Entity\Repository\StationRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\Settings;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\User;
use App\Enums\GlobalPermissions;
use App\Environment;
use App\Media\MediaProcessor;
use App\Security\SplitToken;
use App\Tests\Module;
use FunctionalTester;
use Psr\Container\ContainerInterface;
use RuntimeException;

abstract class CestAbstract
{
    protected ContainerInterface $di;

    protected Environment $environment;

    protected SettingsRepository $settingsRepo;

    protected StationRepository $stationRepo;

    protected ReloadableEntityManagerInterface $em;

    protected string $login_username = 'azuracast@azuracast.com';
    protected string $login_password = 'AzuraCastFunctionalTests!';

    protected ?string $login_api_key = null;

    private ?Station $test_station = null;

    protected function _inject(Module $testsModule): void
    {
        $this->di = $testsModule->container;
        $this->em = $testsModule->em;

        $this->settingsRepo = $this->di->get(SettingsRepository::class);
        $this->stationRepo = $this->di->get(StationRepository::class);
        $this->environment = $this->di->get(Environment::class);
    }

    public function _after(FunctionalTester $I): void
    {
        $this->em->clear();

        if (null !== $this->test_station) {
            $I->sendDelete('/api/admin/station/' . $this->test_station->getId());

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

        $this->setupCompleteUser($I);
        $this->setupCompleteStations($I);
        $this->setupCompleteSettings($I);
    }

    protected function setupCompleteUser(FunctionalTester $I): void
    {
        // Create administrator account.
        $role = new Role();
        $role->setName('Super Administrator');
        $this->em->persist($role);

        $rha = new RolePermission($role);
        $rha->setActionName(GlobalPermissions::All);
        $this->em->persist($rha);

        // Create user account.
        $user = new User();
        $user->setName('AzuraCast Test User');
        $user->setEmail($this->login_username);
        $user->setNewPassword($this->login_password);
        $user->getRoles()->add($role);
        $user->setLocale('en_US.UTF-8');

        $this->em->persist($user);

        // Create API key
        $key = SplitToken::generate();

        $apiKey = new ApiKey($user, $key);
        $apiKey->setComment('Test Suite');

        $this->em->persist($apiKey);
        $this->em->flush();

        $this->login_api_key = (string)$key;
        $I->amBearerAuthenticated($this->login_api_key);

        $this->di->get(Acl::class)->reload();
    }

    protected function setupCompleteStations(FunctionalTester $I): void
    {
        $I->sendPost(
            '/api/admin/stations',
            [
                'name' => 'Functional Test Radio',
                'description' => 'Test radio station.',
            ]
        );

        $stationId = $I->grabDataFromResponseByJsonPath('id');
        $this->test_station = $this->em->find(Station::class, $stationId[0]);
    }

    protected function setupCompleteSettings(FunctionalTester $I): void
    {
        $I->sendPut(
            '/api/admin/settings/' . Settings::GROUP_GENERAL,
            [
                'base_url' => 'http://localhost',
            ]
        );
    }

    protected function getTestStation(): Station
    {
        if ($this->test_station instanceof Station) {
            $testStation = $this->em->refetch($this->test_station);
            if ($testStation instanceof Station) {
                return $testStation;
            }

            $this->test_station = null;
        }

        throw new RuntimeException('Test station is not established.');
    }

    protected function uploadTestSong(): StationMedia
    {
        $testStation = $this->getTestStation();

        $songSrc = '/var/azuracast/www/resources/error.mp3';

        $storageLocation = $testStation->getMediaStorageLocation();

        $storageLocationRepo = $this->di->get(StorageLocationRepository::class);
        $storageFs = $storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $storageFs->upload($songSrc, 'test.mp3');

        /** @var MediaProcessor $mediaProcessor */
        $mediaProcessor = $this->di->get(MediaProcessor::class);

        return $mediaProcessor->process($storageLocation, 'test.mp3');
    }

    protected function _cleanTables(): void
    {
        $cleanTables = [
            User::class,
            Role::class,
            Station::class,
            Settings::class,
        ];

        foreach ($cleanTables as $cleanTable) {
            $this->em->createQuery('DELETE FROM ' . $cleanTable . ' t')->execute();
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
