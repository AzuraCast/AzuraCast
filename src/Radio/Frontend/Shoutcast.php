<?php

declare(strict_types=1);

namespace App\Radio\Frontend;

use App\Entity\Station;
use App\Entity\StationMount;
use App\Service\Acme;
use GuzzleHttp\Promise\Utils;
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
        $newPath = '/var/azuracast/servers/shoutcast2/sc_serv';
        return file_exists($newPath)
            ? $newPath
            : null;
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
        $feConfig = $station->getFrontendConfig();
        $radioPort = $feConfig->getPort();

        $baseUrl = $this->environment->getLocalUri()
            ->withPort($radioPort);

        $npAdapter = $this->adapterFactory->getShoutcast2Adapter($baseUrl);
        $npAdapter->setAdminPassword($feConfig->getAdminPassword());

        $mountPromises = [];
        $defaultMountId = null;

        $sid = 0;
        foreach ($station->getMounts() as $mount) {
            $sid++;

            if ($mount->getIsDefault()) {
                $defaultMountId = $sid;
            }

            $mountPromises[$sid] = $npAdapter->getNowPlayingAsync(
                (string)$sid,
                $includeClients
            )->then(
                function (Result $result) use ($mount) {
                    if (!empty($result->clients)) {
                        foreach ($result->clients as $client) {
                            $client->mount = 'local_' . $mount->getId();
                        }
                    }

                    $mount->setListenersTotal($result->listeners->total);
                    $mount->setListenersUnique($result->listeners->unique ?? 0);
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

    public function getConfigurationPath(Station $station): ?string
    {
        return $station->getRadioConfigDir() . '/sc_serv.conf';
    }

    public function getCurrentConfiguration(Station $station): ?string
    {
        $configPath = $station->getRadioConfigDir();
        $frontendConfig = $station->getFrontendConfig();

        [$certPath, $certKey] = Acme::getCertificatePaths();

        $urlHost = $this->getPublicUrl($station)->getHost();

        $config = [
            'password' => $frontendConfig->getSourcePassword(),
            'adminpassword' => $frontendConfig->getAdminPassword(),
            'logfile' => $configPath . '/sc_serv.log',
            'w3clog' => $configPath . '/sc_w3c.log',
            'banfile' => $this->writeIpBansFile($station),
            'agentfile' => $this->writeUserAgentBansFile($station, 'sc_serv.agent'),
            'ripfile' => $configPath . '/sc_serv.rip',
            'maxuser' => $frontendConfig->getMaxListeners() ?? 250,
            'portbase' => $frontendConfig->getPort(),
            'requirestreamconfigs' => 1,
            'savebanlistonexit' => '0',
            'saveagentlistonexit' => '0',
            'licenceid' => $frontendConfig->getScLicenseId(),
            'userid' => $frontendConfig->getScUserId(),
            'sslCertificateFile' => $certPath,
            'sslCertificateKeyFile' => $certKey,
            'destdns' => $urlHost,
            'destip' => $urlHost,
            'publicdns' => $urlHost,
            'publicip' => $urlHost,
        ];

        $customConfig = trim($frontendConfig->getCustomConfiguration() ?? '');
        if (!empty($customConfig)) {
            $customConf = $this->processCustomConfig($customConfig);

            if (false !== $customConf) {
                $config = array_merge($config, $customConf);
            }
        }

        $i = 0;

        /** @var StationMount $mountRow */
        foreach ($station->getMounts() as $mountRow) {
            $i++;
            $config['streamid_' . $i] = $i;
            $config['streampath_' . $i] = $mountRow->getName();

            if (!empty($mountRow->getIntroPath())) {
                $introPath = $mountRow->getIntroPath();
                $config['streamintrofile_' . $i] = $station->getRadioConfigDir() . '/' . $introPath;
            }

            if ($mountRow->getRelayUrl()) {
                $config['streamrelayurl_' . $i] = $mountRow->getRelayUrl();
            }

            if ($mountRow->getAuthhash()) {
                $config['streamauthhash_' . $i] = $mountRow->getAuthhash();
            }

            if ($mountRow->getMaxListenerDuration()) {
                $config['streamlistenertime_' . $i] = $mountRow->getMaxListenerDuration();
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
        if ($binary = $this->getBinary()) {
            return $binary . ' ' . $this->getConfigurationPath($station);
        }
        return null;
    }

    public function getAdminUrl(Station $station, UriInterface $baseUrl = null): UriInterface
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
}
