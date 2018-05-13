# Support for AzuraCast

Having trouble with your AzuraCast installation? These pointers may be able to help.

If you still don't find what you're looking for, check the GitHub Issues section for an existing issue relating to the 
one you're experiencing. If one does not exist, create a new one.

## Troubleshooting by Viewing Logs

Before submitting any GitHub issues, you should take a look at the terminal logs that AzuraCast outputs. They can often provide additional information about the error, or include very useful information that should be included in any GitHub issue you create.

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
docker-compose run --rm cli azuracast_cli cache:clear
``` 

##### Traditional

```bash
php /var/azuracast/www/util/cli.php cache:clear
```

### Change Port Mappings (Docker Installations)

If you're using AzuraCast alongside existing services that use the same ports, you may notice errors when attempting to start up Docker containers.

Since the entire Docker configuration is controlled by a single file, `docker-compose.yml` in the project root, you can easily make your own copy of this file, modify any necessary ports, and use your copy of the file to run AzuraCast instead:

 - Copy `docker-compose.yml` from the AzuraCast project root to a location outside the project root. This ensures you won't lose your changes when updating AzuraCast itself.
 - Make any needed customizations to the file. AzuraCast expects certain ports to be used, but you can forward these ports to different ones on the host by changing the first part of each `ports` item. For example, you can change `80:80` to `8080:80` to use port 8080 on the host without affecting the AzuraCast container itself. _(Note: In this case you should also remove port 8080 from the `stations` container's ports)._
 - Update any items in the `volumes` section that refer to the relative path `.`, from their original setting:
   ```
   .:/var/azuracast/www
   ```
   To their new path relative to your custom `docker-compose.yml` file:
   ```
   /path/to/azuracast/on/host:/var/azuracast/www
   ```

**Important note:** If an AzuraCast update changes the services used in the `docker-compose.yml` file, you will need to also update your custom version of the file with the changes. These changes are infrequent compared to other sections of the code, however.

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
    azuracast:4zur4c457:::stations
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

The way ports map from Docker containers to your outside server is controlled entirely by the `docker-compose.yml` file.

In the Docker Compose style, ports are listed as `outside-ip:outside-port:inside-port`. If the outside IP address isn't 
specified, it listens on all IPs, and if only one port is specified, it maps the same port both inside and outside the
container.

To edit the ports the AzuraCast web application uses, open your local `docker-compose.yml` and find the `nginx` service.
You'll see the following two lines in the `ports` section:

```yaml
    ports:
      - '80:80'
      - '443:443'
```

To change the port, only modify the first number in the pair. For example, to route HTTP traffic to port 7000, this line 
should read ` - '7000:80'`. To change where HTTPS traffic routes, update the line for port 443.

When using non-standard radio station ports, you have a number of options available to you:

- If you're only broadcasting and not accepting streamers or DJs, you can rely on the web proxy feature built in to
AzuraCast, which will route your radio traffic through the main web site's port. This feature can be enabled from the
`Site Settings` page.

- You can add just the ports you want to use to the `docker-compose.yml` file. In this file, under the `stations` service,
you will find a `ports` subsection with a large number of pre-forwarded ports. You can add to this list or replace it with
your own.

- You can forward the entire range of ports you intend to use using `docker-compose.yml`. Under the `stations` service 
inside the `ports` subsection, you can replace this entire section with your own custom port range, i.e. ` - '9000-9500:9000-9500'`.
Note that due to the way Docker is configured, forwarding a port range this large will likely consume a high amount of memory,
so only use this option if necessary.

#### Traditional

To modify the port your web application runs on, modify the configuration file in `/etc/nginx/sites-available/00-azuracast`.
Note that some updates may overwrite this file.

You can specify any port in any range for your station to use, provided the port isn't already in use.

By default, AzuraCast installs and enables the ufw (uncomplicated firewall) and sets it to lock down traffic to only SSH 
and the ports used by AzuraCast. If you're using a nonstandard port, you will likely also want to enable incoming traffic
on that port using the command `ufw allow PORTNUM`, where `PORTNUM` is the new port number.