<?php
namespace App\Service;

use App\Entity\SFTPUser;
use App\Settings;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;

class SFTPGo
{
    protected EntityManager $em;

    protected Client $client;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        $this->client = new Client([
            'base_uri' => 'http://localhost:10080/api/v1/',
            'timeout' => 2.0,
        ]);
    }

    public function sync(): void
    {
        $users = $this->em->createQuery(/** @lang DQL */ '
            SELECT su, s
            FROM App\Entity\SFTPUser su JOIN su.station s
        ')->execute();

        $export = [];

        foreach ($users as $user) {
            /** @var SFTPUser $user */

            $row = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'expiration_date' => 0,
                'status' => 1,
                'password' => $user->getHashedPassword(),
                'public_keys' => $user->getPublicKeysArray(),
                'home_dir' => $user->getStation()->getRadioMediaDir(),
                'uid' => 0,
                'gid' => 0,
                'permissions' => [
                    '/' => ['*'],
                ],
            ];

            $export[] = $row;
        }

        $backupContents = json_encode(['users' => $export], \JSON_THROW_ON_ERROR);

        $backupDir = '/var/azuracast/sftpgo/backups';
        $backupFileName = time() . '.json';

        $backupPath = $backupDir . '/' . $backupFileName;
        file_put_contents($backupPath, $backupContents);

        $this->client->get('loaddata', [
            'query' => [
                'input_file' => $backupPath,
                'scan_quota' => 2,
            ],
        ]);
    }

    public static function isSupported(): bool
    {
        $settings = Settings::getInstance();
        return $settings->isDockerRevisionNewerThan(7);
    }
}