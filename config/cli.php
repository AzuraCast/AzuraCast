<?php

declare(strict_types=1);

use App\Console\Command;

return function (App\Event\BuildConsoleCommands $event) {
    $event->addAliases([
        'azuracast:acme:get-certificate' => Command\Acme\GetCertificateCommand::class,
        'azuracast:backup' => Command\Backup\BackupCommand::class,
        'azuracast:restore' => Command\Backup\RestoreCommand::class,
        'azuracast:debug:optimize-tables' => Command\Debug\OptimizeTablesCommand::class,
        'azuracast:internal:on-ssl-renewal' => Command\Internal\OnSslRenewal::class,
        'azuracast:internal:ip' => Command\Internal\GetIpCommand::class,
        'azuracast:locale:generate' => Command\Locale\GenerateCommand::class,
        'azuracast:locale:import' => Command\Locale\ImportCommand::class,
        'azuracast:queue:process' => Command\MessageQueue\ProcessCommand::class,
        'azuracast:queue:clear' => Command\MessageQueue\ClearCommand::class,
        'azuracast:settings:list' => Command\Settings\ListCommand::class,
        'azuracast:settings:set' => Command\Settings\SetCommand::class,
        'azuracast:station-queues:clear' => Command\ClearQueuesCommand::class,
        'azuracast:account:list' => Command\Users\ListCommand::class,
        'azuracast:account:login-token' => Command\Users\LoginTokenCommand::class,
        'azuracast:account:reset-password' => Command\Users\ResetPasswordCommand::class,
        'azuracast:account:set-administrator' => Command\Users\SetAdministratorCommand::class,
        'azuracast:cache:clear' => Command\ClearCacheCommand::class,
        'azuracast:config:migrate' => Command\MigrateConfigCommand::class,
        'azuracast:setup:migrate' => Command\MigrateDbCommand::class,
        'azuracast:setup:fixtures' => Command\SetupFixturesCommand::class,
        'azuracast:setup' => Command\SetupCommand::class,
        'azuracast:radio:restart' => Command\RestartRadioCommand::class,
        'azuracast:sync:nowplaying' => Command\Sync\NowPlayingCommand::class,
        'azuracast:sync:nowplaying:station' => Command\Sync\NowPlayingPerStationCommand::class,
        'azuracast:sync:run' => Command\Sync\RunnerCommand::class,
        'azuracast:sync:task' => Command\Sync\SingleTaskCommand::class,
        'azuracast:media:reprocess' => Command\ReprocessMediaCommand::class,
        'azuracast:api:docs' => Command\GenerateApiDocsCommand::class,
        'locale:generate' => Command\Locale\GenerateCommand::class,
        'locale:import' => Command\Locale\ImportCommand::class,
        'queue:process' => Command\MessageQueue\ProcessCommand::class,
        'queue:clear' => Command\MessageQueue\ClearCommand::class,
        'cache:clear' => Command\ClearCacheCommand::class,
        'acme:cert' => Command\Acme\GetCertificateCommand::class,
    ]);
};
