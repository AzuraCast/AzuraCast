<?php

declare(strict_types=1);

namespace App\Radio\Frontend;

use App\Entity\Api\LogType;
use App\Entity\Station;
use App\Entity\StationMount;
use App\Environment;
use App\Service\Acme;
use App\Utilities\File;
use GuzzleHttp\Promise\Utils;
use InvalidArgumentException;
use NowPlaying\Result\Result;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Process\Process;

final class Shoutcast extends AbstractFrontend
{
    /**
     * @inheritDoc
     */
    public function getBinary(): ?string
    {
        try {
            $binaryPath = self::getDirectory() . '/sc_serv';
            return file_exists($binaryPath)
                ? $binaryPath
                : null;
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public static function getDirectory(): string
    {
        $parentDir = Environment::getInstance()->getParentDirectory();
        return File::getFirstExistingDirectory([
            $parentDir . '/servers/shoutcast2',
            $parentDir . '/storage/shoutcast2',
        ]);
    }

    public function getVersion(): ?string
    {
        $binary = $this->getBinary();
        if (!$binary) {
            return null;
        }

        $process = new Process([$binary, '--version']);
        $process->setWorkingDirectory(dirname($binary));
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        return preg_match('/^Shoutcast .* v(\S+) .*$/i', $process->getOutput(), $matches)
            ? $matches[1]
            : null;
    }

    public function getNowPlaying(Station $station, bool $includeClients = true): Result
    {
        $feConfig = $station->frontend_config;
        $radioPort = $feConfig->port;

        $baseUrl = $this->environment->getLocalUri()
            ->withPort($radioPort);

        $npAdapter = $this->adapterFactory->getShoutcast2Adapter($baseUrl);
        $npAdapter->setAdminPassword($feConfig->admin_pw);

        $mountPromises = [];
        $defaultMountId = null;

        $sid = 0;
        foreach ($station->mounts as $mount) {
            $sid++;

            if ($mount->is_default) {
                $defaultMountId = $sid;
            }

            $mountPromises[$sid] = $npAdapter->getNowPlayingAsync(
                (string)$sid,
                $includeClients
            )->then(
                function (Result $result) use ($mount) {
                    if (!empty($result->clients)) {
                        foreach ($result->clients as $client) {
                            $client->mount = 'local_' . $mount->id;
                        }
                    }

                    $mount->listeners_total = $result->listeners->total;
                    $mount->listeners_unique = $result->listeners->unique ?? 0;
                    $this->em->persist($mount);

                    return $result;
                }
            );
        }

        $mountPromiseResults = Utils::settle($mountPromises)->wait();

        $this->em->flush();

        $defaultResult = Result::blank();
        $otherResults = [];
        foreach ($mountPromiseResults as $mountId => $result) {
            if ($mountId === $defaultMountId) {
                $defaultResult = $result['value'] ?? Result::blank();
            } else {
                $otherResults[] = $result['value'] ?? Result::blank();
            }
        }

        foreach ($otherResults as $otherResult) {
            $defaultResult = $defaultResult->merge($otherResult);
        }

        return $defaultResult;
    }

    public function getConfigurationPath(Station $station): string
    {
        return $station->getRadioConfigDir() . '/sc_serv.conf';
    }

    public function getCurrentConfiguration(Station $station): string
    {
        $configPath = $station->getRadioConfigDir();
        $frontendConfig = $station->frontend_config;

        [$certPath, $certKey] = Acme::getCertificatePaths();

        $urlHost = $this->getPublicUrl($station)->getHost();

        $config = [
            'password' => $frontendConfig->source_pw,
            'adminpassword' => $frontendConfig->admin_pw,
            'logfile' => $configPath . '/sc_serv.log',
            'w3clog' => $configPath . '/sc_w3c.log',
            'banfile' => $this->writeIpBansFile($station),
            'agentfile' => $this->writeUserAgentBansFile($station, 'sc_serv.agent'),
            'ripfile' => $configPath . '/sc_serv.rip',
            'maxuser' => $frontendConfig->max_listeners ?? 250,
            'portbase' => $frontendConfig->port,
            'requirestreamconfigs' => 1,
            'savebanlistonexit' => '0',
            'saveagentlistonexit' => '0',
            'licenceid' => $frontendConfig->sc_license_id,
            'userid' => $frontendConfig->sc_user_id,
            'sslCertificateFile' => $certPath,
            'sslCertificateKeyFile' => $certKey,
            'destdns' => $urlHost,
            'destip' => $urlHost,
            'publicdns' => $urlHost,
            'publicip' => $urlHost,
        ];

        if ($station->max_bitrate !== 0) {
            $maxBitrateInBps = $station->max_bitrate * 1024 + 2500;
            $config['maxbitrate'] = $maxBitrateInBps;
        }

        $customConf = $this->processCustomConfig($frontendConfig->custom_config);
        if (false !== $customConf) {
            $config = array_merge($config, $customConf);
        }

        $i = 0;

        /** @var StationMount $mountRow */
        foreach ($station->mounts as $mountRow) {
            $i++;
            $config['streamid_' . $i] = $i;
            $config['streampath_' . $i] = $mountRow->name;

            if (!empty($mountRow->intro_path)) {
                $introPath = $mountRow->intro_path;
                $config['streamintrofile_' . $i] = $station->getRadioConfigDir() . '/' . $introPath;
            }

            if ($mountRow->relay_url) {
                $config['streamrelayurl_' . $i] = $mountRow->relay_url;
            }

            if ($mountRow->authhash) {
                $config['streamauthhash_' . $i] = $mountRow->authhash;
            }

            if ($mountRow->max_listener_duration) {
                $config['streamlistenertime_' . $i] = $mountRow->max_listener_duration;
            }
        }

        $configFileOutput = '';
        foreach ($config as $configKey => $configValue) {
            $configFileOutput .= $configKey . '=' . str_replace("\n", '', (string)$configValue) . "\n";
        }

        return $configFileOutput;
    }

    public function getCommand(Station $station): ?string
    {
        $binary = $this->getBinary();
        if ($binary === null) {
            return null;
        }

        return sprintf(
            '%s %s',
            escapeshellcmd($binary),
            escapeshellarg($this->getConfigurationPath($station))
        );
    }

    public function getAdminUrl(Station $station, ?UriInterface $baseUrl = null): UriInterface
    {
        $publicUrl = $this->getPublicUrl($station, $baseUrl);
        return $publicUrl
            ->withPath($publicUrl->getPath() . '/admin.cgi');
    }

    protected function writeIpBansFile(
        Station $station,
        string $fileName = 'sc_serv.ban',
        string $ipsSeparator = ";255;\n"
    ): string {
        $ips = $this->getBannedIps($station);

        $configDir = $station->getRadioConfigDir();
        $bansFile = $configDir . '/' . $fileName;

        $bannedIpsString = implode($ipsSeparator, $ips);
        if (!empty($bannedIpsString)) {
            $bannedIpsString .= $ipsSeparator;
        }

        file_put_contents($bansFile, $bannedIpsString);

        return $bansFile;
    }

    public function getLogTypes(Station $station): array
    {
        $stationConfigDir = $station->getRadioConfigDir();

        return [
            new LogType(
                'shoutcast_log',
                __('Shoutcast Log'),
                $stationConfigDir . '/shoutcast.log',
                true
            ),
            new LogType(
                'shoutcast_conf',
                __('Shoutcast Configuration'),
                $stationConfigDir . '/sc_serv.conf',
                false
            ),
        ];
    }
}
