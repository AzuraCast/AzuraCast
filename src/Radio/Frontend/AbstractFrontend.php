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
        protected Client $httpClient,
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
     * @param UriInterface|null $baseUrl
     */
    public function getStreamUrl(Station $station, UriInterface $baseUrl = null): UriInterface
    {
        $defaultMount = $this->stationMountRepo->getDefaultMount($station);

        return $this->getUrlForMount($station, $defaultMount, $baseUrl);
    }

    public function getUrlForMount(
        Station $station,
        ?StationMount $mount = null,
        ?UriInterface $baseUrl = null
    ): UriInterface {
        if ($mount === null) {
            return $this->getPublicUrl($station, $baseUrl);
        }

        $customListenUri = $mount->getCustomListenUrlAsUri();
        if (null !== $customListenUri) {
            return $customListenUri;
        }

        $publicUrl = $this->getPublicUrl($station, $baseUrl);
        return $publicUrl->withPath($publicUrl->getPath() . $mount->getName());
    }

    public function getPublicUrl(Station $station, ?UriInterface $baseUrl = null): UriInterface
    {
        $radioPort = $station->getFrontendConfig()->getPort();
        $baseUrl ??= $this->router->getBaseUrl();

        $useRadioProxy = $this->readSettings()->getUseRadioProxy();

        if (
            $useRadioProxy
            || 'https' === $baseUrl->getScheme()
            || (!$this->environment->isProduction() && !$this->environment->isDocker())
        ) {
            // Web proxy support.
            return $baseUrl
                ->withPath($baseUrl->getPath() . CustomUrls::getListenUrl($station));
        }

        // Remove port number and other decorations.
        return $baseUrl
            ->withPort($radioPort)
            ->withPath('');
    }

    /**
     * @param Station $station
     * @param UriInterface|null $baseUrl
     *
     * @return UriInterface[]
     */
    public function getStreamUrls(Station $station, UriInterface $baseUrl = null): array
    {
        $urls = [];
        foreach ($station->getMounts() as $mount) {
            $urls[] = $this->getUrlForMount($station, $mount, $baseUrl);
        }

        return $urls;
    }

    abstract public function getAdminUrl(Station $station, UriInterface $baseUrl = null): UriInterface;

    public function getNowPlaying(Station $station, bool $includeClients = true): Result
    {
        return Result::blank();
    }

    /**
     * @param string $customConfigRaw
     *
     * @return mixed[]|false
     */
    protected function processCustomConfig(string $customConfigRaw): array|false
    {
        try {
            if (str_starts_with($customConfigRaw, '{')) {
                return json_decode($customConfigRaw, true, 512, JSON_THROW_ON_ERROR);
            }

            if (str_starts_with($customConfigRaw, '<')) {
                $xmlConfig = Reader::fromString('<custom_config>' . $customConfigRaw . '</custom_config>');
                return (false !== $xmlConfig)
                    ? (array)$xmlConfig
                    : false;
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Could not parse custom configuration.',
                [
                    'config' => $customConfigRaw,
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
