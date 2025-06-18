<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Container\EnvironmentAwareTrait;
use App\Utilities\Spinner;
use App\Utilities\Types;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Psr\Log\LogLevel;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'azuracast:internal:uptime-wait',
    description: 'Wait for a service to become available, or exit if it times out.',
)]
final class UptimeWaitCommand extends CommandAbstract
{
    use EnvironmentAwareTrait;

    protected function configure(): void
    {
        $this->addOption(
            'timeout',
            't',
            InputOption::VALUE_OPTIONAL,
            'The timeout (in seconds) to wait for the service.',
            180
        );

        $this->addOption(
            'interval',
            'i',
            InputOption::VALUE_OPTIONAL,
            'The delay (in seconds) between retries.',
            1
        );

        $this->addArgument(
            'service',
            InputArgument::OPTIONAL,
            'The service to wait for (redis, db, php-fpm, nginx)',
            'db',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $spinner = new Spinner($output);
        $io = new SymfonyStyle($input, $output);

        $logLevel = $this->environment->getLogLevel();
        $debugMode = (LogLevel::DEBUG === $logLevel);

        $service = Types::string($input->getArgument('service'));
        $timeout = Types::int($input->getOption('timeout'));
        $interval = Types::int($input->getOption('interval'));

        $title = match ($service) {
            'redis' => 'cache service (Redis)',
            'db' => 'database (MariaDB)',
            'nginx' => 'web server (nginx)',
            default => throw new InvalidArgumentException('Invalid service name!')
        };

        $io->title(sprintf('Waiting for %s to become available...', $title));

        $spinner->start('Waiting...');

        $elapsed = 0;
        while ($elapsed <= $timeout) {
            try {
                $checkReturn = match ($service) {
                    'redis' => $this->checkRedis(),
                    'db' => $this->checkDatabase(),
                    'nginx' => $this->checkNginx()
                };

                if ($checkReturn) {
                    $spinner->finish('Services started up and ready!');
                    return 0;
                }
            } catch (Throwable $e) {
                if ($debugMode) {
                    $io->writeln($e->getMessage());
                }
            }

            sleep($interval);
            $elapsed += $interval;

            $spinner->advance();
        }

        $io->error('Timed out waiting for services to start.');
        return 1;
    }

    private function checkDatabase(): bool
    {
        $dbSettings = $this->environment->getDatabaseSettings();
        if (isset($dbSettings['unix_socket'])) {
            unset($dbSettings['host'], $dbSettings['port']);
        }

        $connection = new Driver()->connect($dbSettings);
        $connection->exec('SELECT 1');
        return true;
    }

    private function checkRedis(): bool
    {
        // Redis disabled; skipping Redis check...
        if (!$this->environment->enableRedis()) {
            return true;
        }

        $settings = $this->environment->getRedisSettings();

        $redis = new Redis();
        if (isset($settings['socket'])) {
            $redis->connect($settings['socket']);
        } else {
            $redis->connect($settings['host'], $settings['port'], 15);
        }

        $redis->ping();
        return true;
    }

    private function checkNginx(): bool
    {
        $guzzle = new Client();
        $response = $guzzle->get(
            'http://127.0.0.1:6010/api/status',
            [
                RequestOptions::ALLOW_REDIRECTS => false,
                RequestOptions::HTTP_ERRORS => true,
            ]
        );

        return 200 === $response->getStatusCode();
    }
}
