<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Container\EnvironmentAwareTrait;
use App\Environment;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:config:migrate',
    description: 'Migrate existing configuration to new INI format if any exists.',
)]
final class MigrateConfigCommand extends CommandAbstract
{
    use EnvironmentAwareTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $envSettings = [];

        $iniPath = $this->environment->getParentDirectory() . '/env.ini';
        if (is_file($iniPath)) {
            $envSettings = (array)parse_ini_file($iniPath);
        }

        // Migrate from existing legacy config files.
        $legacyIniPath1 = $this->environment->getBaseDirectory() . '/env.ini';
        if (is_file($legacyIniPath1)) {
            $iniSettings = parse_ini_file($legacyIniPath1);
            $envSettings = array_merge($envSettings, (array)$iniSettings);
        }

        $legacyIniPath2 = $this->environment->getBaseDirectory() . '/app/env.ini';
        if (is_file($legacyIniPath2)) {
            $iniSettings = parse_ini_file($legacyIniPath2);
            $envSettings = array_merge($envSettings, (array)$iniSettings);
        }

        $legacyAppEnvFile = $this->environment->getBaseDirectory() . '/app/.env';
        if (is_file($legacyAppEnvFile)) {
            $envSettings[Environment::APP_ENV] ??= file_get_contents($legacyAppEnvFile);
        }

        $legacyDbConfFile = $this->environment->getBaseDirectory() . '/app/config/db.conf.php';
        if (is_file($legacyDbConfFile)) {
            $dbConf = include($legacyDbConfFile);

            $envSettings[Environment::DB_PASSWORD] ??= $dbConf['password'];
            if (isset($dbConf['user']) && 'root' === $dbConf['user']) {
                $envSettings[Environment::DB_USER] = 'root';
            }
        }

        // Migrate from older environment variable names to new ones.
        $settingsToMigrate = [
            'application_env' => Environment::APP_ENV,
            'db_host' => Environment::DB_HOST,
            'db_port' => Environment::DB_PORT,
            'db_name' => Environment::DB_NAME,
            'db_username' => Environment::DB_USER,
            'db_password' => Environment::DB_PASSWORD,
        ];

        foreach ($settingsToMigrate as $oldSetting => $newSetting) {
            if (!empty($envSettings[$oldSetting])) {
                $envSettings[$newSetting] ??= $envSettings[$oldSetting];
                unset($envSettings[$oldSetting]);
            }
        }

        // Set sensible defaults for variables that may not be set.
        $envSettings[Environment::DB_HOST] ??= 'localhost';
        if ('azuracast' === $envSettings[Environment::DB_HOST]) {
            $envSettings[Environment::DB_HOST] = 'localhost';
        }

        $envSettings[Environment::DB_PORT] ??= '3306';
        $envSettings[Environment::DB_NAME] ??= 'azuracast';
        $envSettings[Environment::DB_USER] ??= 'azuracast';

        $iniData = [
            ';',
            '; Boostcast Environment Settings',
            ';',
            '; This file is automatically generated by BoostCast.',
            ';',
            '[configuration]',
        ];
        foreach ($envSettings as $settingKey => $settingVal) {
            $iniData[] = $settingKey . '="' . $settingVal . '"';
        }

        file_put_contents($iniPath, implode("\n", $iniData));

        // Remove legacy files.
        @unlink($legacyIniPath1);
        @unlink($legacyIniPath2);
        @unlink($legacyAppEnvFile);
        @unlink($legacyDbConfFile);

        $io->writeln(__('Configuration successfully written.'));
        return 0;
    }
}
