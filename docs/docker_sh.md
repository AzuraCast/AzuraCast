---
title: Docker Utility Script
---

If you're using the Docker installation method to run AzuraCast, we have created a helpful utility script to perform common functions without having to type long command names.

[[toc]]

## Download the Utility Script

If you've recently followed the [Docker installation instructions](/install.html#using-docker-recommended), you already have the Docker Utility Script installed. The file name is `docker.sh`.

If you have an older installation, you can use the Docker Utility Script by running these commands inside your AzuraCast directory on your host computer:

```bash
curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker.sh > docker.sh
chmod a+x docker.sh
```

## Available Commands

### Run Command Line Tools

```bash
./docker.sh cli [command_name]
```

Runs any command exposed by the [command line interface](/cli.html) tools.

### Install AzuraCast

```bash
./docker.sh install
```

Pulls the latest version of all Docker images and sets up the AzuraCast database. When complete, your AzuraCast instance should be up and running.

### Update AzuraCast

```bash
./docker.sh update
```

Automatically pulls down any updated Docker images and applies any database and configuration changes since your last AzuraCast update.

### Uninstall AzuraCast

```bash
./docker.sh uninstall
```

Turns off and permanently deletes both the AzuraCast Docker containers and permanent volumes that store the AzuraCast database and station media.

::: danger
This command will fully remove any station media, statistics and metrics, and the entire database associated with your AzuraCast instance. 
:::

### Back Up Files and Settings

```bash
./docker.sh backup [/path/to/backup.tar.gz]
```

Creates a .tar.gz backup copy of the media, statistics and metrics of every station, along with a copy of the full AzuraCast database. You can later restore from this same file in the event of data loss or corruption.

### Restore Files and Settings

```bash
./docker.sh restore /path/to/backup.tar.gz
```

Extracts a .tar.gz file previously created by this same script's `backup` command, copying media, statistics and metrics for each station into AzuraCast and importing the version of the database contained in the backup.

::: warning
Restoring from a backup will remove any existing AzuraCast database or media that exists inside the Docker volumes.
:::

### Set Up LetsEncrypt

```bash
./docker.sh letsencrypt-create
```

If you want your AzuraCast installation to support HTTPS, one of the easiest ways of accomplishing this is with [Let's Encrypt](https://letsencrypt.org/), a free provider of SSL certificates.

Once you have a domain name pointed to your AzuraCast installation, you can run the command above, specify your domain name, and AzuraCast will automatically verify your domain name and update the server with the SSL certificate.

::: tip
Your LetsEncrypt certificate is valid for 3 months. You should manually run the [renewal command](#renew-a-letsencrypt-certificate) or schedule a cron job to do it for you.
:::

### Renew a LetsEncrypt Certificate

```bash
./docker.sh letsencrypt-renew
```

This command will automatically renew a previously established LetsEncrypt certificate. This should be run at least every 3 months to prevent your certificates from expiring.