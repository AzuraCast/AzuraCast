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

    public const RELEASE_CHANNEL_ROLLING = 'rolling';
    public const RELEASE_CHANNEL_STABLE = 'stable';

    protected CacheInterface $cache;

    protected string $repoDir;

    protected Settings $appSettings;

    public function __construct(CacheInterface $cache, Settings $appSettings)
    {
        $this->cache = $cache;
        $this->appSettings = $appSettings;

        $this->repoDir = $appSettings[Settings::BASE_DIR];
    }

    public function getReleaseChannel(): string
    {
        if ($this->appSettings->isDocker()) {
            $channel = $_ENV['AZURACAST_VERSION'] ?? 'latest';

            return ('stable' === $channel)
                ? self::RELEASE_CHANNEL_STABLE
                : self::RELEASE_CHANNEL_ROLLING;
        }

        $details = $this->getDetails();

        return ('stable' === $details['branch'])
            ? self::RELEASE_CHANNEL_STABLE
            : self::RELEASE_CHANNEL_ROLLING;
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
     * @return mixed[]
     */
    public function getDetails(): array
    {
        static $details;

        if (!$details) {
            $details = $this->cache->get('app_version_details');

            if (empty($details)) {
                $details = $this->getRawDetails();
                $ttl = $this->appSettings->isProduction() ? 86400 : 600;

                $this->cache->set('app_version_details', $details, $ttl);
            }
        }

        return $details;
    }

    /**
     * Generate new repository details from the underlying Git repository.
     *
     * @return mixed[]
     */
    protected function getRawDetails(): array
    {
        if (!is_dir($this->repoDir . '/.git')) {
            return [];
        }

        $details = [];

        // Get the long form of the latest commit's hash.
        $latest_commit_hash = $this->runProcess(['git', 'log', '--pretty=%H', '-n1', 'HEAD']);

        $details['commit'] = $latest_commit_hash;
        $details['commit_short'] = substr($latest_commit_hash, 0, 7);

        // Get the last commit's timestamp.
        $latest_commit_date = $this->runProcess(['git', 'log', '-n1', '--pretty=%ci', 'HEAD']);

        if (!empty($latest_commit_date)) {
            $commit_date = new DateTime($latest_commit_date);
            $commit_date->setTimezone(new DateTimeZone('UTC'));

            $details['commit_timestamp'] = $commit_date->getTimestamp();
            $details['commit_date'] = $commit_date->format('Y-m-d G:i');
        } else {
            $details['commit_timestamp'] = 0;
            $details['commit_date'] = 'N/A';
        }

        $last_tagged_commit = $this->runProcess(['git', 'rev-list', '--tags', '--max-count=1']);
        if (!empty($last_tagged_commit)) {
            $details['tag'] = $this->runProcess(['git', 'describe', '--tags', $last_tagged_commit], 'N/A');
        } else {
            $details['tag'] = self::FALLBACK_VERSION;
        }

        $details['branch'] = $this->runProcess(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], 'master');

        return $details;
    }

    /**
     * Run the specified process and return its output.
     *
     * @param array $proc
     * @param string $default
     */
    protected function runProcess($proc, $default = ''): string
    {
        $process = new Process($proc);
        $process->setWorkingDirectory($this->repoDir);
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
            $commitText = sprintf(
                '#<a href="%s" target="_blank">%s</a> (%s)',
                $commitLink,
                $details['commit_short'],
                $details['commit_date']
            );

            $releaseChannel = $this->getReleaseChannel();
            if (self::RELEASE_CHANNEL_ROLLING === $releaseChannel) {
                return 'Rolling Release ' . $commitText;
            }
            return 'v' . $details['tag'] . ' Stable';
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
     */
    public function isInstallationModified(): bool
    {
        // We can't detect if release builds are changed, so always return true.
        if (!is_dir($this->repoDir . '/.git')) {
            return true;
        }

        $changed_files = $this->runProcess(['git', 'status', '-s']);
        return !empty($changed_files);
    }
}
