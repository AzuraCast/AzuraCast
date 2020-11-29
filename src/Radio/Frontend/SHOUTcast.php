<?php

namespace App\Radio\Frontend;

use App\Entity;
use App\Logger;
use App\Settings;
use App\Utilities;
use Exception;
use NowPlaying\Adapter\AdapterFactory;
use NowPlaying\Result\Result;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Process\Process;

class SHOUTcast extends AbstractFrontend
{
    public static function getVersion(): ?string
    {
        $binary_path = self::getBinary();
        if (!$binary_path) {
            return null;
        }

        $process = new Process([$binary_path, '--version']);
        $process->setWorkingDirectory(dirname($binary_path));
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        return trim($process->getOutput());
    }

    /**
     * @inheritDoc
     */
    public static function getBinary()
    {
        $new_path = '/var/azuracast/servers/shoutcast2/sc_serv';

        // Docker versions before 3 included the SC binary across the board.
        $settings = Settings::getInstance();
        if ($settings->isDocker() && $settings[Settings::DOCKER_REVISION] < 3) {
            return $new_path;
        }

        return file_exists($new_path)
            ? $new_path
            : false;
    }

    public function getNowPlaying(Entity\Station $station, bool $includeClients = true): Result
    {
        $feConfig = $station->getFrontendConfig();
        $radioPort = $feConfig->getPort();
        $baseUrl = 'http://' . (Settings::getInstance()->isDocker() ? 'stations' : 'localhost') . ':' . $radioPort;

        $npAdapter = $this->adapterFactory->getAdapter(
            AdapterFactory::ADAPTER_SHOUTCAST2,
            $baseUrl
        );
        $npAdapter->setAdminPassword($feConfig->getAdminPassword());

        $defaultResult = Result::blank();
        $otherResults = [];

        try {
            $sid = 0;
            foreach ($station->getMounts() as $mount) {
                /** @var Entity\StationMount $mount */
                $sid++;

                $result = $npAdapter->getNowPlaying((string)$sid, $includeClients);

                $mount->setListenersTotal($result->listeners->total);
                $mount->setListenersUnique($result->listeners->unique);
                $this->em->persist($mount);

                if ($mount->getIsDefault()) {
                    $defaultResult = $result;
                } else {
                    $otherResults[] = $result;
                }
            }

            $this->em->flush();

            foreach ($otherResults as $otherResult) {
                $defaultResult = $defaultResult->merge($otherResult);
            }
        } catch (Exception $e) {
            Logger::getInstance()->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
        }

        return $defaultResult;
    }

    /*
     * Process Management
     */

    /**
     * @inheritdoc
     */
    public function read(Entity\Station $station): bool
    {
        $config = $this->getConfig($station);
        $station->setFrontendConfigDefaults($this->loadFromConfig($config));
        return true;
    }

    /**
     * @return string[]|bool Returns either all lines of the config file or false on failure
     */
    protected function getConfig(Entity\Station $station)
    {
        $config_dir = $station->getRadioConfigDir();
        return @parse_ini_file($config_dir . '/sc_serv.conf', false, INI_SCANNER_RAW);
    }

    /*
     * Configuration
     */

    /**
     * @return mixed[]
     */
    protected function loadFromConfig($config): array
    {
        return [
            Entity\StationFrontendConfiguration::PORT => $config['portbase'],
            Entity\StationFrontendConfiguration::SOURCE_PASSWORD => $config['password'],
            Entity\StationFrontendConfiguration::ADMIN_PASSWORD => $config['adminpassword'],
            Entity\StationFrontendConfiguration::MAX_LISTENERS => $config['maxuser'],
        ];
    }

    public function write(Entity\Station $station): bool
    {
        $config = $this->getDefaults($station);

        $frontend_config = $station->getFrontendConfig();

        $port = $frontend_config->getPort();
        if (null !== $port) {
            $config['portbase'] = $port;
        }

        $sourcePw = $frontend_config->getSourcePassword();
        if (!empty($sourcePw)) {
            $config['password'] = $sourcePw;
        }

        $adminPw = $frontend_config->getAdminPassword();
        if (!empty($adminPw)) {
            $config['adminpassword'] = $adminPw;
        }

        $maxListeners = $frontend_config->getMaxListeners();
        if (null !== $maxListeners) {
            $config['maxuser'] = $maxListeners;
        }

        $customConfig = $frontend_config->getCustomConfiguration();
        if (!empty($customConfig)) {
            $custom_conf = $this->processCustomConfig($customConfig);
            if (!empty($custom_conf)) {
                $config = array_merge($config, $custom_conf);
            }
        }

        $i = 0;
        foreach ($station->getMounts() as $mount_row) {
            /** @var Entity\StationMount $mount_row */
            $i++;
            $config['streamid_' . $i] = $i;
            $config['streampath_' . $i] = $mount_row->getName();

            if ($mount_row->getRelayUrl()) {
                $config['streamrelayurl_' . $i] = $mount_row->getRelayUrl();
            }

            if ($mount_row->getAuthhash()) {
                $config['streamauthhash_' . $i] = $mount_row->getAuthhash();
            }
        }

        // Set any unset values back to the DB config.
        $station->setFrontendConfigDefaults($this->loadFromConfig($config));

        $this->em->persist($station);
        $this->em->flush();

        $config_path = $station->getRadioConfigDir();
        $sc_path = $config_path . '/sc_serv.conf';

        $sc_file = '';
        foreach ($config as $config_key => $config_value) {
            $sc_file .= $config_key . '=' . str_replace("\n", '', $config_value) . "\n";
        }

        file_put_contents($sc_path, $sc_file);
        return true;
    }

    /**
     * @return mixed[]
     */
    protected function getDefaults(Entity\Station $station): array
    {
        $config_path = $station->getRadioConfigDir();

        return [
            'password' => Utilities::generatePassword(),
            'adminpassword' => Utilities::generatePassword(),
            'logfile' => $config_path . '/sc_serv.log',
            'w3clog' => $config_path . '/sc_w3c.log',
            'banfile' => $this->writeIpBansFile($station),
            'ripfile' => $config_path . '/sc_serv.rip',
            'maxuser' => 250,
            'portbase' => $this->getRadioPort($station),
            'requirestreamconfigs' => 1,
        ];
    }

    public function getCommand(Entity\Station $station): ?string
    {
        if ($binary = self::getBinary()) {
            $config_path = $station->getRadioConfigDir();
            $sc_config = $config_path . '/sc_serv.conf';

            return $binary . ' ' . $sc_config;
        }

        return '/bin/false';
    }

    public function getAdminUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface
    {
        $public_url = $this->getPublicUrl($station, $base_url);
        return $public_url
            ->withPath($public_url->getPath() . '/admin.cgi');
    }

    protected function getDefaultMountSid(Entity\Station $station): int
    {
        $default_sid = null;
        $sid = 0;
        foreach ($station->getMounts() as $mount) {
            /** @var Entity\StationMount $mount */
            $sid++;

            if ($mount->getIsDefault()) {
                $default_sid = $sid;
                break;
            }
        }

        return $default_sid ?? 1;
    }
}
