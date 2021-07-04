<?php

use App\Console\Application;
use App\Console\Command;

return function (Application $console) {
    // Liquidsoap Internal CLI commands
    $console->command(
        'azuracast:internal:auth station-id [--dj-user=] [--dj-password=]',
        Command\Internal\DjAuthCommand::class
    )->setDescription('Authorize a streamer to connect as a source for the radio service.');

    $console->command(
        'azuracast:internal:djoff station-id [--dj-user=]',
        Command\Internal\DjOffCommand::class
    )->setDescription('Indicate that a DJ has finished streaming to a station.');

    $console->command(
        'azuracast:internal:djon station-id [--dj-user=]',
        Command\Internal\DjOnCommand::class
    )->setDescription('Indicate that a DJ has begun streaming to a station.');

    $console->command(
        'azuracast:internal:feedback station-id [-s|--song=] [-m|--media=] [-p|--playlist=]',
        Command\Internal\FeedbackCommand::class
    )->setDescription('Send upcoming song feedback from the AutoDJ back to AzuraCast.');

    $console->command(
        'azuracast:internal:sftp-event action username path [target-path] [ssh-cmd]',
        Command\Internal\SftpEventCommand::class
    )->setDescription('Process an event triggered via SFTP');

    $console->command(
        'azuracast:internal:sftp-auth',
        Command\Internal\SftpAuthCommand::class
    )->setDescription('Attempt SFTP authentication');

    $console->command(
        'azuracast:internal:nextsong station-id [as-autodj]',
        Command\Internal\NextSongCommand::class
    )->defaults(
        [
            'as-autodj' => true,
        ]
    )->setDescription('Return the next song to the AutoDJ.');

    $console->command(
        'azuracast:internal:ip',
        Command\Internal\GetIpCommand::class
    )->setDescription('Get the external IP address for this instance.');

    // Locales
    $console->command(
        'locale:generate',
        Command\Locale\GenerateCommand::class
    )->setDescription(__('Generate the translation locale file.'));

    $console->command(
        'locale:import',
        Command\Locale\ImportCommand::class
    )->setDescription(__('Convert translated locale files into PHP arrays.'));

    // Setup
    $console->command(
        'azuracast:setup:initialize',
        Command\InitializeCommand::class
    )->setDescription(__('Ensure key settings are initialized within AzuraCast.'));

    $console->command(
        'azuracast:config:migrate',
        Command\MigrateConfigCommand::class
    )->setDescription(__('Migrate existing configuration to new INI format if any exists.'));

    $console->command(
        'azuracast:setup:fixtures',
        Command\SetupFixturesCommand::class
    )->setDescription(__('Install fixtures for demo / local development.'));

    $console->command(
        'azuracast:setup [--update] [--load-fixtures] [--release]',
        Command\SetupCommand::class
    )->setDescription(__('Run all general AzuraCast setup steps.'));

    // Maintenance
    $console->command(
        'azuracast:radio:restart [station-name]',
        Command\RestartRadioCommand::class
    )->setDescription('Restart all radio stations, or a single one if specified.');

    $console->command(
        'sync:run [--force] [task]',
        Command\SyncCommand::class
    )->setDescription(__('Run one or more scheduled synchronization tasks.'));

    $console->command(
        'queue:process [runtime] [--worker-name=]',
        Command\MessageQueue\ProcessCommand::class
    )->setDescription(__('Process the message queue.'));

    $console->command(
        'queue:clear [queue]',
        Command\MessageQueue\ClearCommand::class
    )->setDescription(__('Clear the contents of the message queue.'));

    $console->command(
        'azuracast:media:reprocess [station-name]',
        Command\ReprocessMediaCommand::class
    )->setDescription('Manually reload all media metadata from file.');

    $console->command(
        'azuracast:api:docs',
        Command\GenerateApiDocsCommand::class
    )->setDescription('Trigger regeneration of AzuraCast API documentation.');

    $console->command(
        'azuracast:debug:optimize-tables',
        Command\Debug\OptimizeTablesCommand::class
    )->setDescription('Optimize all tables in the database.');

    // User-side tools
    $console->command(
        'azuracast:account:list',
        Command\Users\ListCommand::class
    )->setDescription('List all accounts in the system.');

    $console->command(
        'azuracast:account:reset-password email',
        Command\Users\ResetPasswordCommand::class
    )->setDescription('Reset the password of the specified account.');

    $console->command(
        'azuracast:account:set-administrator email',
        Command\Users\SetAdministratorCommand::class
    )->setDescription('Set the account specified as a global administrator.');

    $console->command(
        'azuracast:settings:list',
        Command\Settings\ListCommand::class
    )->setDescription(__('List all settings in the AzuraCast settings database.'));

    $console->command(
        'azuracast:settings:set setting-key setting-value',
        Command\Settings\SetCommand::class
    )->setDescription('Set the value of a setting in the AzuraCast settings database.');

    $console->command(
        'azuracast:backup [path] [--storage-location-id=] [--exclude-media]',
        Command\Backup\BackupCommand::class
    )->setDescription(__('Back up the AzuraCast database and statistics (and optionally media).'));

    $console->command(
        'azuracast:restore path [--restore] [--release]',
        Command\Backup\RestoreCommand::class
    )->setDescription('Restore a backup previously generated by AzuraCast.');
};
