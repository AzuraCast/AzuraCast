<?php
namespace App;

use Symfony\Component\Process\Process;

/**
 * App Core Framework Version
 */
class Version
{
    /** @var Cache */
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return string The current tagged version.
     */
    public function getVersion()
    {
        $details = $this->getDetails();
        return $details['tag'] ?? 'N/A';
    }

    /**
     * @return string A textual representation of the current installed version.
     */
    public function getVersionText()
    {
        $details = $this->getDetails();
        return 'v'.$details['tag'].', #'.$details['commit_short'].' ('.$details['commit_date'].')';
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
            $details = $this->cache->getOrSet('app_version_details', function() {
                return $this->_getRawDetails();
            }, 86400);
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
        $details = [];

        // Get the long form of the latest commit's hash.
        $latest_commit_hash = $this->_runProcess(['git', 'log', '--pretty=%H', '-n1', 'HEAD']);

        $details['commit'] = $latest_commit_hash;
        $details['commit_short'] = substr($latest_commit_hash, 0, 7);

        // Get the last commit's timestamp.
        $latest_commit_date = $this->_runProcess(['git', 'log', '-n1', '--pretty=%ci', 'HEAD']);

        if (!empty($latest_commit_date)) {
            $commit_date = new \DateTime($latest_commit_date);
            $commit_date->setTimezone(new \DateTimeZone('UTC'));

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
            $details['tag'] = 'N/A';
        }

        return $details;
    }

    /**
     * Run the specified process and return its output.
     *
     * @param $proc
     * @param string $default
     * @return string
     */
    protected function _runProcess($proc, $default = ''): string
    {
        $process = new Process($proc);
        $process->run();

        if (!$process->isSuccessful()) {
            return $default;
        }

        return trim($process->getOutput());
    }



}
