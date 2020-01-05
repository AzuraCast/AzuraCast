<?php
namespace App;

class Settings extends \Azura\Settings
{
    public const DOCKER_REVISION = 'docker_revision';

    /**
     * @return string The parent directory the application is within, i.e. `/var/azuracast`.
     */
    public function getParentDirectory(): string
    {
        return dirname($this->getBaseDirectory());
    }

    /**
     * @return string The default directory where station data is stored.
     */
    public function getStationDirectory(): string
    {
        return $this->getParentDirectory() . '/stations';
    }

    public function isDockerRevisionNewerThan(int $version): bool
    {
        if (!$this->isDocker()) {
            return false;
        }

        $compareVersion = (int)$this->get(self::DOCKER_REVISION, 0);
        return ($compareVersion >= $version);
    }
}
