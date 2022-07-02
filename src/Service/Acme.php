<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Repository\SettingsRepository;
use App\Entity\Repository\StationRepository;
use App\Environment;
use App\Message\AbstractMessage;
use App\Message\GenerateAcmeCertificate;
use App\Nginx\Nginx;
use App\Radio\Adapters;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use RuntimeException;
use skoerfgen\ACMECert\ACMECert;
use Symfony\Component\Filesystem\Filesystem;

final class Acme
{
    public const LETSENCRYPT_PROD = 'https://acme-v02.api.letsencrypt.org/directory';
    public const LETSENCRYPT_DEV = 'https://acme-staging-v02.api.letsencrypt.org/directory';
    public const THRESHOLD_DAYS = 14;

    public function __construct(
        private readonly SettingsRepository $settingsRepo,
        private readonly StationRepository $stationRepo,
        private readonly Environment $environment,
        private readonly Logger $logger,
        private readonly Nginx $nginx,
        private readonly Adapters $adapters,
    ) {
    }

    public function __invoke(AbstractMessage $message): void
    {
        if ($message instanceof GenerateAcmeCertificate) {
            $outputPath = $message->outputPath;

            if (null !== $outputPath) {
                $logHandler = new StreamHandler($outputPath, LogLevel::DEBUG, true);
                $this->logger->pushHandler($logHandler);
            }

            try {
                $this->getCertificate();
            } catch (Exception $e) {
                $this->logger->error(
                    sprintf('ACME Error: %s', $e->getMessage()),
                    [
                        'exception' => $e,
                    ]
                );
            }

            if (null !== $outputPath) {
                $this->logger->popHandler();
            }
        }
    }

    public function getCertificate(bool $force = false): void
    {
        // Check folder permissions.
        $acmeDir = self::getAcmeDirectory();
        $fs = new Filesystem();

        // Build ACME Cert class.
        $directoryUrl = $this->environment->isProduction() ? self::LETSENCRYPT_PROD : self::LETSENCRYPT_DEV;

        $this->logger->debug(
            sprintf('ACME: Using directory URL: %s', $directoryUrl)
        );

        $acme = new ACMECert($directoryUrl);

        // Build LetsEncrypt settings.
        $settings = $this->settingsRepo->readSettings();

        $acmeEmail = $settings->getAcmeEmail();
        $acmeDomain = $settings->getAcmeDomains();

        if (empty($acmeEmail)) {
            $acmeEmail = getenv('LETSENCRYPT_EMAIL');
            if (!empty($acmeEmail)) {
                $settings->setAcmeEmail($acmeEmail);
                $this->settingsRepo->writeSettings($settings);
            }
        }

        if (empty($acmeDomain)) {
            $acmeDomain = getenv('LETSENCRYPT_HOST');
            if (empty($acmeDomain)) {
                throw new RuntimeException('Skipping LetsEncrypt; no domain(s) set.');
            } else {
                $settings->setAcmeDomains($acmeDomain);
                $this->settingsRepo->writeSettings($settings);
            }
        }

        // Account certificate registration.
        if (file_exists($acmeDir . '/account_key.pem')) {
            $acme->loadAccountKey('file://' . $acmeDir . '/account_key.pem');
        } else {
            $accountKey = $acme->generateECKey();
            $fs->dumpFile($acmeDir . '/account_key.pem', $accountKey);
            $acme->loadAccountKey($accountKey);

            if (!empty($acmeEmail)) {
                $acme->register(true, $acmeEmail);
            } else {
                $acme->register(true);
            }
        }

        $domains = array_map(
            'trim',
            explode(',', $acmeDomain)
        );

        // Renewal check.
        if (
            !$force
            && file_exists($acmeDir . '/acme.crt')
            && $domains === $acme->getSAN('file://' . $acmeDir . '/acme.crt')
            && $acme->getRemainingDays('file://' . $acmeDir . '/acme.crt') > self::THRESHOLD_DAYS
        ) {
            throw new RuntimeException('Certificate does not need renewal.');
        }

        $fs->mkdir($acmeDir . '/challenges');

        $domainConfig = [];
        foreach ($domains as $domain) {
            $domainConfig[$domain] = ['challenge' => 'http-01'];
        }

        $handler = function ($opts) use ($acmeDir, $fs) {
            $fs->dumpFile(
                $acmeDir . '/challenges/' . basename($opts['key']),
                $opts['value']
            );

            return function ($opts) use ($acmeDir, $fs) {
                $fs->remove($acmeDir . '/challenges/' . $opts['key']);
            };
        };

        if (!file_exists($acmeDir . '/acme.key')) {
            $acmeKey = $acme->generateECKey();
            $fs->dumpFile($acmeDir . '/acme.key', $acmeKey);
        }

        $fullchain = $acme->getCertificateChain(
            'file://' . $acmeDir . '/acme.key',
            $domainConfig,
            $handler
        );
        $fs->dumpFile($acmeDir . '/acme.crt', $fullchain);

        // Symlink to the shared SSL cert.
        $fs->remove([
            $acmeDir . '/ssl.crt',
            $acmeDir . '/ssl.key',
        ]);

        $fs->symlink($acmeDir . '/acme.crt', $acmeDir . '/ssl.crt');
        $fs->symlink($acmeDir . '/acme.key', $acmeDir . '/ssl.key');

        $this->reloadServices();

        $this->logger->notice('ACME certificate process successful.');
    }

    private function reloadServices(): void
    {
        try {
            $this->nginx->reload();

            foreach ($this->stationRepo->iterateEnabledStations() as $station) {
                $frontendType = $station->getFrontendTypeEnum();
                if (!$station->getHasStarted() || !$frontendType->supportsReload()) {
                    continue;
                }

                $frontend = $this->adapters->getFrontendAdapter($station);
                if (null !== $frontend && $frontend->isRunning($station)) {
                    $frontend->reload($station);
                }
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('ACME: Could not reload all adapters: %s', $e->getMessage()),
                [
                    'exception' => $e,
                ]
            );
        }
    }

    public static function getAcmeDirectory(): string
    {
        return Environment::getInstance()->getParentDirectory() . '/acme';
    }

    public static function getCertificatePaths(): array
    {
        $acmeDir = self::getAcmeDirectory();
        return [
            $acmeDir . '/ssl.crt',
            $acmeDir . '/ssl.key',
        ];
    }
}
