<?php

declare(strict_types=1);

namespace App;

use App\Enums\ReleaseChannel;
use DateTime;
use DateTimeZone;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Process\Process;

/**
 * App Core Framework Version
 */
final class Version
{
    /** @var string Version that is displayed if no Git repository information is present. */
    public const FALLBACK_VERSION = '0.17.4';

    public const UPDATE_URL = 'https://docs.azuracast.com/en/getting-started/updates';
    public const CHANGELOG_URL = 'https://github.com/AzuraCast/AzuraCast/blob/main/CHANGELOG.md';

    private string $repoDir;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Environment $environment
    ) {
        $this->repoDir = $environment->getBaseDirectory();
    }

    public function getReleaseChannelEnum(): ReleaseChannel
    {
        if ($this->environment->isDocker()) {
            return $this->environment->getReleaseChannelEnum();
        }

        $details = $this->getDetails();
        return ('stable' === $details['branch'])
            ? ReleaseChannel::Stable
            : ReleaseChannel::RollingRelease;
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
                $ttl = $this->environment->isProduction() ? 86400 : 600;

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
    private function getRawDetails(): array
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

        $details['branch'] = $this->runProcess(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], 'main');

        return $details;
    }

    /**
     * Run the specified process and return its output.
     */
    private function runProcess(array $proc, string $default = ''): string
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

            $releaseChannel = $this->getReleaseChannelEnum();
            if (ReleaseChannel::RollingRelease === $releaseChannel) {
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
}
