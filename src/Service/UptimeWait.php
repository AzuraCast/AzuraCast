<?php
namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InfluxDB;
use Redis;

class UptimeWait
{
    protected Connection $db;

    protected Redis $redis;

    protected InfluxDB\Client $influx;

    public function __construct(EntityManagerInterface $em, Redis $redis, InfluxDB\Database $influx)
    {
        $this->db = $em->getConnection();
        $this->redis = $redis;
        $this->influx = $influx->getClient();
    }

    public function waitForAll(): void
    {
        $this->waitForRedis();
        $this->waitForInflux();
        $this->waitForDatabase();
    }

    public function waitForDatabase(): void
    {
        $this->attempt(function () {
            $this->db->connect();
        });
    }

    public function waitForInflux(): void
    {
        $this->attempt(function () {
            $this->influx->listDatabases();
        });
    }

    public function waitForRedis(): void
    {
        $this->attempt(function () {
            $this->redis->ping();
        });
    }

    protected function attempt(callable $run)
    {
        $attempt = 0;
        $maxAttempts = 10;
        $baseWaitTime = 100;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $waitTime = ($attempt ** 2) * $baseWaitTime;
            usleep($waitTime * 1000);

            $attempt++;

            try {
                return $run();
            } catch (Exception $e) {
                $lastException = $e;
            }
        }

        throw $lastException;
    }
}