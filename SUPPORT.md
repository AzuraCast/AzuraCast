# Support for AzuraCast

Having trouble with your AzuraCast installation? These pointers may be able to help.

If you still don't find what you're looking for, check the GitHub Issues section for an existing issue relating to the 
one you're experiencing. If one does not exist, create a new one.

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
    azuracast:azuracast:::stations
```

### Force a Full Update (Traditional Installations)

Normally, the traditional installer's update script only updates the portion of the system that have been modified since
your last update. If an update was interrupted or otherwise is causing trouble, you can force the update script to process
all components, which can often fix any issues:

##### Traditional

```bash
./update.sh --full
```