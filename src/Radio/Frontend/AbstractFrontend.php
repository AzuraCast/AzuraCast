<?php

declare(strict_types=1);

namespace App\Radio\Frontend;

use App\Container\SettingsAwareTrait;
use App\Entity\Repository\StationMountRepository;
use App\Entity\Station;
use App\Entity\StationMount;
use App\Http\Router;
use App\Nginx\CustomUrls;
use App\Radio\AbstractLocalAdapter;
use App\Radio\Configuration;
use App\Xml\Reader;
use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use NowPlaying\AdapterFactory;
use NowPlaying\Result\Result;
use PhpIP\IP;
use PhpIP\IPBlock;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use Supervisor\SupervisorInterface;

abstract class AbstractFrontend extends AbstractLocalAdapter
{
    use SettingsAwareTrait;

    public function __construct(
        protected AdapterFactory $adapterFactory,
        protected Client $http_client,
        protected StationMountRepository $stationMountRepo,
        SupervisorInterface $supervisor,
        EventDispatcherInterface $dispatcher,
        Router $router
    ) {
        parent::__construct($supervisor, $dispatcher, $router);
    }

    /**
     * @inheritdoc
     */
    public function getSupervisorProgramName(Station $station): string
    {
        return Configuration::getSupervisorProgramName($station, 'frontend');
    }

    /**
     * @param Station $station
     * @param UriInterface|null $base_url
     */
    public function getStreamUrl(Station $station, UriInterface $base_url = null): UriInterface
    {
        $default_mount = $this->stationMountRepo->getDefaultMount($station);

        return $this->getUrlForMount($station, $default_mount, $base_url);
    }

    public function getUrlForMount(
        Station $station,
        ?StationMount $mount = null,
        ?UriInterface $base_url = null
    ): UriInterface {
        if ($mount === null) {
            return $this->getPublicUrl($station, $base_url);
        }

        $customListenUri = $mount->getCustomListenUrlAsUri();
        if (null !== $customListenUri) {
            return $customListenUri;
        }

        $public_url = $this->getPublicUrl($station, $base_url);
        return $public_url->withPath($public_url->getPath() . $mount->getName());
    }

    public function getPublicUrl(Station $station, ?UriInterface $base_url = null): UriInterface
    {
        $radio_port = $station->getFrontendConfig()->getPort();
        $base_url ??= $this->router->getBaseUrl();

        $use_radio_proxy = $this->readSettings()->getUseRadioProxy();

        if (
            $use_radio_proxy
            || 'https' === $base_url->getScheme()
            || (!$this->environment->isProduction() && !$this->environment->isDocker())
        ) {
            // Web proxy support.
            return $base_url
                ->withPath($base_url->getPath() . CustomUrls::getListenUrl($station));
        }

        // Remove port number and other decorations.
        return $base_url
            ->withPort($radio_port)
            ->withPath('');
    }

    /**
     * @param Station $station
     * @param UriInterface|null $base_url
     *
     * @return UriInterface[]
     */
    public function getStreamUrls(Station $station, UriInterface $base_url = null): array
    {
        $urls = [];
        foreach ($station->getMounts() as $mount) {
            $urls[] = $this->getUrlForMount($station, $mount, $base_url);
        }

        return $urls;
    }

    abstract public function getAdminUrl(Station $station, UriInterface $base_url = null): UriInterface;

    public function getNowPlaying(Station $station, bool $includeClients = true): Result
    {
        return Result::blank();
    }

    /**
     * @param string $custom_config_raw
     *
     * @return mixed[]|false
     */
    protected function processCustomConfig(string $custom_config_raw): array|false
    {
        try {
            if (str_starts_with($custom_config_raw, '{')) {
                return json_decode($custom_config_raw, true, 512, JSON_THROW_ON_ERROR);
            }

            if (str_starts_with($custom_config_raw, '<')) {
                $xmlConfig = Reader::fromString('<custom_config>' . $custom_config_raw . '</custom_config>');
                return (false !== $xmlConfig)
                    ? (array)$xmlConfig
                    : false;
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Could not parse custom configuration.',
                [
                    'config' => $custom_config_raw,
                    'exception' => $e,
                ]
            );
        }

        return false;
    }

    protected function writeUserAgentBansFile(
        Station $station,
        string $fileName = 'user_agent_bans.txt',
    ): string {
        $bannedUserAgents = array_filter(
            array_map(
                'trim',
                explode("\n", $station->getFrontendConfig()->getBannedUserAgents() ?? '')
            )
        );

        $configDir = $station->getRadioConfigDir();
        $bansFile = $configDir . '/' . $fileName;

        file_put_contents($bansFile, implode("\n", $bannedUserAgents));

        return $bansFile;
    }

    protected function writeIpBansFile(
        Station $station,
        string $fileName = 'ip_bans.txt',
        string $ipsSeparator = "\n"
    ): string {
        $ips = $this->getBannedIps($station);

        $configDir = $station->getRadioConfigDir();
        $bansFile = $configDir . '/' . $fileName;

        file_put_contents($bansFile, implode($ipsSeparator, $ips));

        return $bansFile;
    }

    protected function getBannedIps(Station $station): array
    {
        return $this->getIpsAsArray($station->getFrontendConfig()->getBannedIps());
    }

    protected function getIpsAsArray(?string $ipString): array
    {
        $ips = [];

        if (!empty($ipString)) {
            foreach (array_filter(array_map('trim', explode("\n", $ipString))) as $ip) {
                try {
                    if (!str_contains($ip, '/')) {
                        $ipObj = IP::create($ip);
                        $ips[] = (string)$ipObj;
                    } else {
                        // Iterate through CIDR notation
                        foreach (IPBlock::create($ip) as $ipObj) {
                            $ips[] = (string)$ipObj;
                        }
                    }
                } catch (InvalidArgumentException) {
                }
            }
        }

        return $ips;
    }
}
