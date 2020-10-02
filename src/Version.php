<?php
namespace App;

use DateTime;
use DateTimeZone;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Process\Process;

/**
 * App Core Framework Version
 */
class Version
{
    /** @var string Version that is displayed if no Git repository information is present. */
    public const FALLBACK_VERSION = '0.10.4';

    protected CacheInterface $cache;

    protected string $repo_dir;

    protected Settings $app_settings;

    public function __construct(CacheInterface $cache, Settings $app_settings)
    {
        $this->cache = $cache;
        $this->app_settings = $app_settings;

        $this->repo_dir = $app_settings[Settings::BASE_DIR];
    }

    /**
     * @return string The current tagged version.
     */
    public function getVersion(): string
    {
        $details = $this->getDetails();
        return $details['tag'] ?? self::FALLBACK_VERSION;
    }

    /**
     * Load cache or generate new repository details from the underlying Git repository.
     *
     * @return array
     */
    public function getDetails(): array
    {
        static $details;

        if (!$details) {
            $details = $this->cache->get('app_version_details');

            if (empty($details)) {
                $details = $this->_getRawDetails();
                $ttl = $this->app_settings->isProduction() ? 86400 : 600;

                $this->cache->set('app_version_details', $details, $ttl);
            }
        }

        return $details;
    }

    /**
     * Generate new repository details from the underlying Git repository.
     *
     * @return array
     */
    protected function _getRawDetails(): array
    {
        if (!is_dir($this->repo_dir . '/.git')) {
            return [];
        }

        $details = [];

        // Get the long form of the latest commit's hash.
        $latest_commit_hash = $this->_runProcess(['git', 'log', '--pretty=%H', '-n1', 'HEAD']);

        $details['commit'] = $latest_commit_hash;
        $details['commit_short'] = substr($latest_commit_hash, 0, 7);

        // Get the last commit's timestamp.
        $latest_commit_date = $this->_runProcess(['git', 'log', '-n1', '--pretty=%ci', 'HEAD']);

        if (!empty($latest_commit_date)) {
            $commit_date = new DateTime($latest_commit_date);
            $commit_date->setTimezone(new DateTimeZone('UTC'));

            $details['commit_timestamp'] = $commit_date->getTimestamp();
            $details['commit_date'] = $commit_date->format('Y-m-d G:i');
        } else {
            $details['commit_timestamp'] = 0;
            $details['commit_date'] = 'N/A';
        }

        $last_tagged_commit = $this->_runProcess(['git', 'rev-list', '--tags', '--max-count=1']);
        if (!empty($last_tagged_commit)) {
            $details['tag'] = $this->_runProcess(['git', 'describe', '--tags', $last_tagged_commit], 'N/A');
        } else {
            $details['tag'] = self::FALLBACK_VERSION;
        }

        return $details;
    }

    /**
     * Run the specified process and return its output.
     *
     * @param array $proc
     * @param string $default
     *
     * @return string
     */
    protected function _runProcess($proc, $default = ''): string
    {
        $process = new Process($proc);
        $process->setWorkingDirectory($this->repo_dir);
        $process->run();

        if (!$process->isSuccessful()) {
            return $default;
        }

        return trim($process->getOutput());
    }

    /**
     * @return string A textual representation of the current installed version.
     */
    public function getVersionText(): string
    {
        $details = $this->getDetails();

        if (isset($details['tag'])) {
            $commitLink = 'https://github.com/AzuraCast/AzuraCast/commit/' . $details['commit'];
            $commitText = '#<a href="' . $commitLink . '" target="_blank">' . $details['commit_short'] . '</a> (' . $details['commit_date'] . ')';

            if (isset($_ENV['AZURACAST_VERSION'])) {
                if ('latest' === $_ENV['AZURACAST_VERSION']) {
                    return 'Rolling Release ' . $commitText;
                }

                return 'v' . $details['tag'] . ' Stable';
            }

            return 'v' . $details['tag'] . ', ' . $commitText;
        }

        return 'v' . self::FALLBACK_VERSION . ' Release Build';
    }

    /**
     * @return string|null The long-form Git hash that represents the current commit of this installation.
     */
    public function getCommitHash(): ?string
    {
        $details = $this->getDetails();
        return $details['commit'] ?? null;
    }

    /**
     * @return string|null The shortened Git hash corresponding to the current commit.
     */
    public function getCommitShort(): ?string
    {
        $details = $this->getDetails();
        return $details['commit_short'] ?? null;
    }

    /**
     * Check if the installation has been modified by the user from the release build.
     *
     * @return bool
     */
    public function isInstallationModified(): bool
    {
        // We can't detect if release builds are changed, so always return true.
        if (!is_dir($this->repo_dir . '/.git')) {
            return true;
        }

        $changed_files = $this->_runProcess(['git', 'status', '-s']);
        return !empty($changed_files);
    }
}
