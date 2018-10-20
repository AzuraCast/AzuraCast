---
title: Command-Line Interface
---

AzuraCast features a powerful Command-Line Interface (CLI) tool that allows you to perform maintenance and troubleshooting tasks "under the hood".

In order to use the AzuraCast CLI, you must have shell terminal access to the server running AzuraCast (to the host machine, if you're running Docker).

[[toc]]

## Invoking the CLI

**Using the Docker Utility Script:**

If you're using the Docker installation method, it's recommended to download the [Docker Utility Script](/docker_sh.html), which will allow you to use this shorter syntax:

```bash
./docker.sh cli [command]
```

**From a Docker Installation:**

```bash
docker-compose run --rm cli azuracast_cli [command]
```

**Traditional Installation:**

```bash
php /var/azuracast/www/util/cli.php [command]
```

## Command Reference

### List All Commands

To see all commands available, run the appropriate CLI command for your installation type with no command specified.

### Reset Administrator Password

```bash
(cli_command) azuracast:account:reset-password your@email.com
```

Generate a temporary password for the user account with the specified e-mail address. This can help you recover an account you have lost access to. This method is used for security purposes instead of a typical "Forgot Password?" prompt on the login screen.

### Manually Reprocess All Media

```bash
(cli_command) azuracast:media:reprocess
```

Iterates through all stations' media directories and manually reloads the metadata information stored inside AzuraCast with the latest data on the files themselves. This is useful for troubleshooting songs that are stuck in "Processing" status, or if you have recently uploaded multiple songs via SFTP.

### Manually Run AzuraCast Setup

```bash
(cli_command) azuracast:setup [--update]
```

Runs any necessary database updates to bring your AzuraCast installation to the latest version. This is normally run automatically as part of the installation and update processes, but can be run manually for troubleshooting or local development.

::: warning
Running this command will disconnect all current active listeners to your radio stations.
:::

### Restart All Radio Stations

```bash
(cli_command) azuracast:radio:restart
```

Shuts down both the frontends (Icecast, SHOUTcast, etc) and backends (Liquidsoap) of all radio stations, rewrites their configuration files, then relaunches them. This is identical to the "Restart Broadcasting" command inside the web interface.

::: warning
Running this command will disconnect all current active listeners to your radio stations.
:::

### Clear All Caches

```bash
(cli_command) cache:clear
```

Clears all caches used internally by AzuraCast. This can be used as a troubleshooting step if you are encountering issues with out-of-date information appearing on dashboard pages. Note that some pages may take slightly longer to load after all caches are cleared.

### Run Synchronization Tasks

```bash
(cli_command) sync:run [nowplaying|short|medium|long]
```

Manually invoke the synchronized tasks ("cron jobs") that normally run automatically behind the scenes to keep AzuraCast updated.

- **nowplaying** corresponds to the every-15-second check of all stations' currently playing song and listener metrics
- **short** corresponds to the every-minute sync task
- **medium** corresponds to the every-5-minutes sync task
- **long** corresponds to the every-hour sync task

These tasks can also be invoked directly from the web interface via the Administration homepage.

## Other Commands

The AzuraCast CLI interface also exposes a number of other advanced commands. These commands are intended for developers to use when building the application, and often should not be run by station owners on production installations.

For more information about the additional command line tools available, see their respective documentation pages below:
- [Doctrine Command Line Reference](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/tools.html#command-overview)
- [Doctrine Migrations](https://www.doctrine-project.org/projects/doctrine-migrations/en/latest/reference/introduction.html#introduction)