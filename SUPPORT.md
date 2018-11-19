# Support for AzuraCast

Having trouble with your AzuraCast installation? These pointers may be able to help.

If you still don't find what you're looking for, check the GitHub Issues section for an existing issue relating to the 
one you're experiencing. If one does not exist, create a new one.

## Troubleshooting by Viewing Logs

Before submitting any GitHub issues, you should take a look at the terminal logs that AzuraCast outputs. They can often provide additional information about the error, or include very useful information that should be included in any GitHub issue you create.

Users with the appropriate permissions can also view many logs directly through AzuraCast itself. The Log Viewer feature is available under "Utilities" in each station's management page.

#### Docker

To view logs in Docker, from the directory where your `docker-compose.yml` file is located, you can run:

```bash
docker-compose logs -f
```

This command will show you a running log of all containers. You can also get detailed logs by running `docker-compose logs -f service`, where "service" is one of `web`, `stations`, `nginx`, etc.

#### Traditional

Since the Traditional installation interacts directly with your host server, its logs are in various locations across the system.

- AzuraCast: `/var/azuracast/www_tmp/azuracast.log`
- Nginx Access: `/var/azuracast/www_tmp/access.log`
- Nginx Errors: `/var/azuracast/www_tmp/error.log`
- PHP: `/var/azuracast/www_tmp/php_errors.log`
- Supervisord: `/var/azuracast/www_tmp/supervisord.log`
- Redis: `/var/log/redis/redis-server.log`
- MariaDB: `/var/log/mysql`
- InfluxDB: `/var/log/influxdb`

For each station, logs for radio software will be inside `/var/azuracast/stations/{station_short_name}/config`, with the following filenames:

- Liquidsoap: `liquidsoap.log`
- Icecast: `icecast.log`
- SHOUTcast: `sc_serv.log`

## Common Solutions

### Reset an Account Password

If you have lost the password to log into an account, but still have access to the SSH terminal for the server, you can
execute the following command to generate a new random password for an account in the system.

Replace `YOUREMAILADDRESS` with the e-mail address whose password you intend to reset.

##### Docker

```bash
# With the Docker Utility Script
./docker.sh cli azuracast:account:reset-password YOUREMAILADDRESS

# Manually using Docker Compose
docker-compose run --rm cli azuracast_cli azuracast:account:reset-password YOUREMAILADDRESS
``` 

##### Traditional

```bash
php /var/azuracast/www/util/cli.php azuracast:account:reset-password YOUREMAILADDRESS
```

### Manually Flush the System Cache

Many parts of the AzuraCast system depend on caches to speed up site performance. Sometimes, these caches can get out of
date, and they may cause errors. You can always flush all site-wide caches using one command-line script:

```bash
# With the Docker Utility Script
./docker.sh cli cache:clear

# Manually using Docker Compose
docker-compose run --rm cli azuracast_cli cache:clear
``` 

##### Traditional

```bash
php /var/azuracast/www/util/cli.php cache:clear
```

### Access Files via SFTP (Docker Installations)

By default, SFTP access isn't set up for Docker based installations. If you have a large volume of media files, you may 
prefer to upload them via SFTP instead of using the web updater. You should *not* use the host operating system's SFTP,
however, as Docker stores station media inside a Docker-specific volume.

The script below will set up a temporary SFTP server that points to your station media directory inside Docker. The server
will stay running inside the terminal window, so you can easily hit `Ctrl+C` to terminate it when you are finished.

##### Docker
```bash
docker run --rm \
    -v azuracast_station_data:/home/azuracast/stations \
    -p 2222:22 atmoz/sftp:alpine \
    azuracast:4zur4c457:1000::stations
```

As long as you leave this script running, it will create a connection that you can access with these credentials:

* **Host:** Your server's host name
* **Port:** `2222` (Set in the third line)
* **Username:** `azuracast` (The first part of the last line)
* **Password:** `4zur4c457` (The second part of the last line) 

If you intend to leave this script running for long term periods, you must change the password to something more secure.

### Force a Full Update (Traditional Installations)

Normally, the traditional installer's update script only updates the portion of the system that have been modified since
your last update. If an update was interrupted or otherwise is causing trouble, you can force the update script to process
all components, which can often fix any issues:

##### Traditional

```bash
./update.sh --full
```

### Use Non-standard Ports

You may want to serve the AzuraCast web application itself on a different port, or host your radio station on a port that 
isn't within the default range AzuraCast serves (8000-8999).

#### Docker

If you're using AzuraCast alongside existing services that use the same ports, you may notice errors when attempting to start up Docker containers.

The Docker configuration, including the ports that are exposed to the Internet, is controlled by a file, `docker-compose.yml`. While you can modify this file directly, future updates will ask you to override your changes to this file to apply new updates or bug fixes.

It is strongly recommended to instead create a file named `docker-compose.override.yml` in the same directory as your main `docker-compose.yml` file. This file can contain any changes you need to make to the Docker Compose configuration. It will automatically be parsed by Docker Compose alongside the main file, and will be kept during updates.

The ports you will most often want to change are the ports for the web service. In `docker-compose.override.yml`, you can customize these ports by modifying the `nginx` service:

```yaml
version: '2.2'

services:
  nginx:
    image: azuracast/azuracast_nginx:latest
    ports:
      - '80:80'
      - '443:443'
```

The first part of each port mapping, before the colon character (:), is the port that will be exposed to the public. You should _only_ change this number, not the number after the colon.

For example, to serve pages via port 8080, the ports entry would be: ` - '8080:80'`.

#### Traditional

To modify the port your web application runs on, modify the configuration file in `/etc/nginx/sites-available/00-azuracast`.
Note that some updates may overwrite this file.

You can specify any port in any range for your station to use, provided the port isn't already in use.

By default, AzuraCast installs and enables the ufw (uncomplicated firewall) and sets it to lock down traffic to only SSH 
and the ports used by AzuraCast. If you're using a nonstandard port, you will likely also want to enable incoming traffic
on that port using the command `ufw allow PORTNUM`, where `PORTNUM` is the new port number.
