<?php
namespace App\Radio\Frontend;

use App\Utilities;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Psr\Http\Message\UriInterface;

class SHOUTcast extends AbstractFrontend
{
    public function getWatchCommand(Entity\Station $station): ?string
    {
        $fe_config = (array)$station->getFrontendConfig();

        $mount_name = $this->_getDefaultMountSid($station);

        return $this->_getStationWatcherCommand(
            $station,
            'shoutcast2',
            'http://localhost:' . $fe_config['port'] . '/stats?sid='.$mount_name
        );
    }

    public function getNowPlaying(Entity\Station $station, $payload = null, $include_clients = true): array
    {
        $fe_config = (array)$station->getFrontendConfig();
        $radio_port = $fe_config['port'];

        $base_url = 'http://' . (APP_INSIDE_DOCKER ? 'stations' : 'localhost') . ':' . $radio_port;

        $np_adapter = new \NowPlaying\Adapter\SHOUTcast2($base_url, $this->http_client);
        $np_adapter->setAdminPassword($fe_config['admin_pw']);

        $np_final = \NowPlaying\Adapter\AdapterAbstract::NOWPLAYING_EMPTY;
        $np_final['listeners']['clients'] = [];

        try {
            $sid = 0;

            foreach($station->getMounts() as $mount) {
                /** @var Entity\StationMount $mount */
                $sid++;
                $np = $np_adapter->getNowPlaying($sid);

                if ($include_clients) {
                    $np['listeners']['clients'] = $np_adapter->getClients($sid, true);
                    $np['listeners']['unique'] = count($np['listeners']['clients']);
                } else {
                    $np['listeners']['clients'] = [];
                }

                if ($mount->getIsDefault()) {
                    $np_final['current_song'] = $np['current_song'];
                    $np_final['meta'] = $np['meta'];
                }

                $np_final['listeners']['clients'] = array_merge($np_final['listeners']['clients'], $np['listeners']['clients']);

                $np_final['listeners']['current'] += $np['listeners']['current'];
                $np_final['listeners']['unique'] += $np['listeners']['unique'];
                $np_final['listeners']['total'] += $np['listeners']['total'];

                $this->logger->debug('Response for mount point', ['mount' => $mount->getName(), 'response' => $np]);
            }

            $this->logger->debug('Aggregated NowPlaying response', ['response' => $np_final]);
        } catch(\NowPlaying\Exception $e) {
            $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
        }

        return $np_final;
    }

    /**
     * @inheritdoc
     */
    public function read(Entity\Station $station): bool
    {
        $config = $this->_getConfig($station);
        $station->setFrontendConfigDefaults($this->_loadFromConfig($config));
        return true;
    }

    public function write(Entity\Station $station): bool
    {
        $config = $this->_getDefaults($station);

        $frontend_config = (array)$station->getFrontendConfig();

        if (!empty($frontend_config['port'])) {
            $config['portbase'] = $frontend_config['port'];
        }

        if (!empty($frontend_config['source_pw'])) {
            $config['password'] = $frontend_config['source_pw'];
        }

        if (!empty($frontend_config['admin_pw'])) {
            $config['adminpassword'] = $frontend_config['admin_pw'];
        }

        if (!empty($frontend_config['max_listeners'])) {
            $config['maxuser'] = $frontend_config['max_listeners'];
        }

        if (!empty($frontend_config['custom_config'])) {
            $custom_conf = $this->_processCustomConfig($frontend_config['custom_config']);
            if (!empty($custom_conf)) {
                $config = array_merge($config, $custom_conf);
            }
        }

        $i = 0;
        foreach ($station->getMounts() as $mount_row) {
            /** @var Entity\StationMount $mount_row */
            $i++;
            $config['streamid_'.$i] = $i;
            $config['streampath_'.$i] = $mount_row->getName();

            if ($mount_row->getRelayUrl()) {
                $config['streamrelayurl_'.$i] = $mount_row->getRelayUrl();
            }

            if ($mount_row->getAuthhash()) {
                $config['streamauthhash_' . $i] = $mount_row->getAuthhash();
            }
        }

        // Set any unset values back to the DB config.
        $station->setFrontendConfigDefaults($this->_loadFromConfig($config));

        $this->em->persist($station);
        $this->em->flush();

        $config_path = $station->getRadioConfigDir();
        $sc_path = $config_path . '/sc_serv.conf';

        $sc_file = '';
        foreach ($config as $config_key => $config_value) {
            $sc_file .= $config_key . '=' . str_replace("\n", "", $config_value) . "\n";
        }

        file_put_contents($sc_path, $sc_file);
        return true;
    }

    /*
     * Process Management
     */

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
            ->withPath($public_url->getPath().'/admin.cgi');
    }

    /*
     * Configuration
     */

    protected function _getConfig(Entity\Station $station)
    {
        $config_dir = $station->getRadioConfigDir();
        return @parse_ini_file($config_dir . '/sc_serv.conf', false, INI_SCANNER_RAW);
    }

    protected function _loadFromConfig($config): array
    {
        return [
            'port' => $config['portbase'],
            'source_pw' => $config['password'],
            'admin_pw' => $config['adminpassword'],
            'max_listeners' => $config['maxuser'],
        ];
    }

    protected function _getDefaults(Entity\Station $station): array
    {
        $config_path = $station->getRadioConfigDir();

        $defaults = [
            'password' => Utilities::generatePassword(),
            'adminpassword' => Utilities::generatePassword(),
            'logfile' => $config_path . '/sc_serv.log',
            'w3clog' => $config_path . '/sc_w3c.log',
            'banfile' => $config_path . '/sc_serv.ban',
            'ripfile' => $config_path . '/sc_serv.rip',
            'maxuser' => 250,
            'portbase' => $this->_getRadioPort($station),
            'requirestreamconfigs' => 1,
        ];

        return $defaults;
    }

    protected function _getDefaultMountSid(Entity\Station $station): int
    {
        $default_sid = null;
        $sid = 0;
        foreach($station->getMounts() as $mount) {
            /** @var Entity\StationMount $mount */
            $sid++;

            if ($mount->getIsDefault()) {
                $default_sid = $sid;
                break;
            }
        }

        return $default_sid ?? 1;
    }

    public static function getBinary()
    {
        $new_path = dirname(APP_INCLUDE_ROOT) . '/servers/shoutcast2/sc_serv';

        // Docker versions before 3 included the SC binary across the board.
        if (APP_INSIDE_DOCKER && APP_DOCKER_REVISION < 3) {
            return $new_path;
        }

        return file_exists($new_path)
            ? $new_path
            : false;
    }
}
