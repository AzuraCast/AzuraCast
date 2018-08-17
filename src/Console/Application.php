<?php
namespace App\Console;

use Slim\Container;

class Application extends \Symfony\Component\Console\Application
{
    /** @var Container */
    protected $di;

    /**
     * @param Container $di
     */
    public function setContainer(Container $di)
    {
        $this->di = $di;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->di;
    }

    /**
     * @param $service_name
     * @return mixed
     * @throws \App\Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getService($service_name)
    {
        if ($this->di->has($service_name)) {
            return $this->di->get($service_name);
        } else {
            throw new \App\Exception(sprintf('Service "%s" not found.', $service_name));
        }
    }

    /**
     * Register commands associated with this application.
     */
    public function registerAppCommands()
    {
        $this->addCommands([
            // Liquidsoap Internal CLI Commands
            new Command\NextSong,
            new Command\DjAuth,
            new Command\DjOn,
            new Command\DjOff,

            // Locales
            new Command\LocaleGenerate,
            new Command\LocaleImport,

            // Setup
            new Command\MigrateConfig,
            new Command\SetupInflux,
            new Command\SetupFixtures,
            new Command\Setup,

            // Maintenance
            new Command\ClearCache,
            new Command\RestartRadio,
            new Command\Sync,
            new Command\ReprocessMedia,

            new Command\GenerateApiDocs,
            new Command\UptimeWait,

            // User-side tools
            new Command\ResetPassword,
            new Command\ListSettings,
            new Command\SetSetting,
        ]);
    }
}
