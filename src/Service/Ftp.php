<?php
namespace App\Service;

use App\Acl;
use App\Entity;
use App\Http\Router;
use App\Settings;
use App\Utilities;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Ftp
{
    protected AzuraCastCentral $ac_central;

    protected Acl $acl;

    protected Settings $app_settings;

    protected EntityManager $em;

    protected Entity\Repository\UserRepository $user_repo;

    protected Entity\Repository\SettingsRepository $settings_repo;

    protected LoggerInterface $logger;

    protected Router $router;

    public function __construct(
        AzuraCastCentral $ac_central,
        Acl $acl,
        Settings $app_settings,
        EntityManager $em,
        Entity\Repository\SettingsRepository $settings_repo,
        Entity\Repository\UserRepository $user_repo,
        LoggerInterface $logger,
        Router $router
    ) {
        $this->ac_central = $ac_central;
        $this->acl = $acl;
        $this->app_settings = $app_settings;
        $this->em = $em;
        $this->logger = $logger;
        $this->router = $router;

        $this->user_repo = $user_repo;
        $this->settings_repo = $settings_repo;
    }

    /**
     * Given a username and password, handle a PureFTPD authentication request.
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    public function auth(string $username, string $password): array
    {
        $error = ['auth_ok:-1', 'end'];

        if (!$this->isEnabled()) {
            return $error;
        }

        // Some FTP clients URL Encode the username, particularly the '@' of the e-mail address.
        $username = urldecode($username);

        $this->logger->info('FTP Authentication attempt.', [
            'username' => $username,
        ]);

        $user = $this->user_repo->authenticate($username, $password);

        if (!($user instanceof Entity\User)) {
            return $error;
        }

        // Create a temporary directory with symlinks to every station that user can manage.
        $ftp_dir = '/tmp/azuracast_ftp_directories/user_' . $user->getId();
        Utilities::rmdirRecursive($ftp_dir);

        if (!mkdir($ftp_dir) && !is_dir($ftp_dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $ftp_dir));
        }

        $stations = $this->em->getRepository(Entity\Station::class)->findAll();
        $has_any_stations = false;

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            if ($this->acl->userAllowed($user, Acl::STATION_MEDIA, $station->getId())) {
                $has_any_stations = true;

                $station_media_dir = $station->getRadioMediaDir();
                $symlink_path = $ftp_dir . '/' . $station->getShortName();

                symlink($station_media_dir, $symlink_path);
            }
        }

        if (!$has_any_stations) {
            return $error;
        }

        return [
            'auth_ok:1',
            'uid:1000',
            'gid:1000',
            'dir:' . $ftp_dir . '/./',
            'end',
        ];
    }

    /**
     * @return bool Whether FTP services are enabled for this installation.
     */
    public function isEnabled(): bool
    {
        if (!$this->app_settings->isDocker() || $_ENV['AZURACAST_DC_REVISION'] < 6) {
            return false;
        }

        return (bool)$this->settings_repo->getSetting(Entity\Settings::ENABLE_FTP_SERVER, true);
    }

    /**
     * @return array|null FTP connection information, if FTP is enabled.
     */
    public function getInfo(): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $base_url = $this->router->getBaseUrl(false)
            ->withScheme('ftp')
            ->withPort(null);

        $port = $_ENV['AZURACAST_FTP_PORT'] ?? 21;

        return [
            'url' => (string)$base_url,
            'ip' => $this->ac_central->getIp(),
            'port' => $port,
        ];
    }
}
