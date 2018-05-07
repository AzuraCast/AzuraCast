![](https://github.com/AzuraCast/AzuraCast/raw/master/resources/azuracast.png)

# AzuraCast: A Self-Hosted Web Radio Manager

[![Build Status](https://travis-ci.org/AzuraCast/AzuraCast.svg?branch=master)](https://travis-ci.org/AzuraCast/AzuraCast)
[![Apache 2.0 License](https://img.shields.io/github/license/azuracast/azuracast.svg)]()
[![Docker Pulls](https://img.shields.io/docker/pulls/azuracast/azuracast_web.svg)](https://hub.docker.com/r/azuracast/azuracast_web/)
[![Twitter Follow](https://img.shields.io/twitter/follow/azuracast.svg?style=social&label=Follow)](https://twitter.com/azuracast)

**AzuraCast** is a self-hosted, all-in-one web radio management kit. Using its easy installer and powerful but intuitive web interface, you can start up a fully working web radio station in a few quick minutes. 

AzuraCast works for web radio stations of all types and sizes, and is built to run on even the most affordable VPS web hosts. The project is named after Azura Peavielle, the mascot of [its predecessor project](https://github.com/SlvrEagle23/Ponyville-Live). AzuraCast also has its own project mascot, [Azura Ruisselante](https://github.com/AzuraCast/AzuraCast/wiki/Meet-Azura-Ruisselante) created by the talented artist [Tyson Tan](https://tysontan.deviantart.com/).

**AzuraCast is currently in beta.** Many web radio stations already run AzuraCast, but keeping your server up-to-date with the latest code from the GitHub repository is strongly recommended for security, bug fixes and new feature releases. It's unlikely, but updates may result in unexpected issues or data loss, so always make sure to keep your station's media files backed up in a second location.

To install AzuraCast, you should have a basic understanding of the Linux shell terminal. Once installed, every aspect of your radio station can be managed via AzuraCast's simple to use web interface.

Want to see AzuraCast for yourself? Check out [screenshots](https://github.com/AzuraCast/AzuraCast/wiki/Screenshots) or visit
our demo site at [demo.azuracast.com](https://demo.azuracast.com/):

* Username: `demo@azuracast.com`
* Password: `demo`

## Features

With AzuraCast, you can:

* **Manage your Media:** Upload songs from the web, organize music into folders, and preview songs in your browser.
* **Create Playlists:** Set up standard playlists that play all the time, scheduled playlists for time periods, or special playlists that play once per x songs, or once per x minutes.
* **Set Up Live DJs:** Enable or disable live broadcasting from streamers/DJs, and create individual accounts for each streamer to use.
* **Take Listener Requests:** Let your listeners request specific songs from your playlists, both via an API and a simple public-facing listener page.
* **Track Analytics and Reports:** Keep track of every aspect of your station's listeners over time. View reports of each song's impact on your listener count.
* **Let Station Autopilot Do the Work:** AzuraCast can automatically assign songs to a playlist based on the song's impact on listener numbers. 
* **Delegate Management:** Create and remove separate administrator accounts for each station manager.
* **Build Your Own Radio Player:** AzuraCast's powerful, well-documented API lets you control your station from software built in any programming language.
* **Integrate with TuneIn, Discord and More:** The new web hook system lets you broadcast changes in your station to third party services.
* ...and more.

### What's Included

Whether you're using the traditional installer or Docker containers, AzuraCast will automatically retrieve and install these components for you:

#### Radio Software

* **[Liquidsoap](http://savonet.sourceforge.net/)** as the always-playing "AutoDJ"
* **[Icecast 2.4](http://icecast.org/)** as a radio broadcasting frontend (Icecast-KH installed on supported platforms)
* **[SHOUTcast 2 DNAS](http://wiki.shoutcast.com/wiki/SHOUTcast_DNAS_Server_2)** as an alternative radio frontend (x86/x64 only)

#### Supporting Software

* **[NGINX](https://www.nginx.com)** for serving web pages and the radio proxy
* **[MariaDB](https://mariadb.org/)** as the primary database
* **[PHP 7.2](https://secure.php.net/)** powering the web application
* **[InfluxDB](https://www.influxdata.com/)** for time-series based statistics
* **[Redis](https://redis.io/)** for sessions, database and general caching

### AzuraCast Runs Everywhere

Thanks to the power and portability of Docker, you can run a full installation of AzuraCast on virtually any modern server operating system, or even your home Windows or MacOS computer. Using docker, we preassemble "containers" that have all of the necessary software installed and set up already, so with a single file you're up and running. Updates are extremely easy, fast and reliable, too. We _highly recommend_ using the Docker installation whenever possible.

If you want a more "bare-metal" experience and greater customization, you can also use our "Traditional" installer, which requires Ubuntu 16.04 LTS. This installer loads and configures the necessary software directly onto your server; this may interfere with other software, so you should always start with a clean server when possible.   

## Installing AzuraCast

### Docker Installation (Recommended)

We strongly recommend installing and using AzuraCast via Docker. All of the necessary software packages are built by our automated tools, so installation is as easy as just pulling down the pre-compiled images. There's no need to worry about compatibility with your host operating system, so any host (including Windows and MacOS) will work great out of the box.

You can use the AzuraCast Docker installer to check for (and install, if necessary) the latest version of Docker and Docker Compose, then pull the necessary files and get your instance running.

```bash
curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker.sh > docker.sh
chmod a+x docker.sh
./docker.sh install
```

#### Setting up HTTPS with LetsEncrypt: `./docker.sh letsencrypt-create`

AzuraCast now supports full encryption with LetsEncrypt. LetsEncrypt offers free SSL certificates with easy validation and renewal.

First, make sure your AzuraCast instance is set up and serving from the domain you want to use. 

If you have the Docker utility script from the steps above, you can simply run `./docker.sh letsencrypt-create` to set up your LetsEncrypt SSL certificate.

Otherwise, you can use the following manual commands:

```bash
docker-compose run --rm letsencrypt certonly --webroot -w /var/www/letsencrypt
docker-compose run --rm nginx letsencrypt_connect YOURDOMAIN.example.com
docker-compose kill -s SIGHUP nginx
``` 

Your LetsEncrypt certificate is valid for 3 months. To renew the certificates, run `./docker.sh letsencrypt-renew` or manually run this command:

```
docker-compose run --rm letsencrypt renew --webroot -w /var/www/letsencrypt
```

#### Updating: `./docker.sh update`

If you have the `docker.sh` script from the installation steps above, you can run `./docker.sh update` to automatically update your installation.

To manually update, from inside the base directory where AzuraCast is copied, run the following commands:

```bash
docker-compose down
docker-compose pull
docker-compose run --rm cli azuracast_update
docker-compose up -d
```

#### Backup and Restore: `./docker.sh backup` and `./docker.sh restore`

AzuraCast has utility scripts to allow for easy backup and restoration of Docker volumes.

You can use [docker-backup.sh](https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-backup.sh) to back up existing volumes. You can specify a custom path as the script's argument. By default, the script will create a file, `backup.tar.gz` in the app root.

To restore the application's state from this compressed file use [docker-restore.sh](https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-restore.sh) and provide it with the path of the existing backup file.

Note that the restoration process will replace any existing AzuraCast database or media that exists inside the Docker volumes.

### Traditional Installation (Ubuntu 16.04 LTS Only)

**Note:** Some web hosts offer custom versions of Ubuntu that include different software repositories. These may cause compatibility issues with AzuraCast. Many VPS providers are known to work out of the box with AzuraCast (OVH, DigitalOcean, Vultr, etc), and are thus highly recommended if you plan to use the traditional installer.

AzuraCast is optimized for speed and performance, and can run on very inexpensive hardware, from the Raspberry Pi 3 to the lowest-level VPSes offered by most providers.

Since AzuraCast installs its own radio tools, databases and web servers, you should always install AzuraCast on a "clean" server instance with no other web or radio software installed previously.

Execute these commands **as the root user** to set up your AzuraCast server:

```bash
apt-get update
apt-get install -q -y git

mkdir -p /var/azuracast/www
cd /var/azuracast/www
git clone https://github.com/AzuraCast/AzuraCast.git .

chmod a+x install.sh
./install.sh
```

If you cannot directly log in as the root account on your server, try running `sudo su` before running the commands above.

The installation process will take between 5 and 15 minutes, depending on your Internet connection. If you encounter an error, let us know in the [Issues section](https://github.com/AzuraCast/AzuraCast/issues).

Once the terminal-based installation is complete, you can visit your server's public IP address (`http://ip.of.your.server/`) to finish the web-based setup.

#### Updating

AzuraCast also includes a handy updater script that pulls down the latest copy of the codebase from Git, flushes the site caches and makes any necessary database updates. Run these commands as any user with `sudo` permissions:

```bash
cd /var/azuracast/www

sudo chmod a+x update.sh
sudo ./update.sh
```

## AzuraCast API

Once installed and running, AzuraCast exposes an API that allows you to monitor and interact with your stations.

Documentation about this API and its endpoints are available on the [AzuraCast API Documentation](http://azuracast.com/api/).

## License

AzuraCast is licensed under the [Apache license, version 2.0](https://github.com/AzuraCast/AzuraCast/blob/master/License.txt). This project is free and open-source software, and pull requests are always welcome.

## Questions? Comments? Feedback?

AzuraCast is a volunteer project, and we depend on your support and feedback to keep growing. Issues for this codebase are tracked in this repository's Issues section on GitHub. Anyone can create a new issue for the project, and you are encouraged to do so.

## Friends of AzuraCast

We would like to thank the following organizations for their support of AzuraCast's ongoing development:

- [DigitalOcean](https://m.do.co/c/21612b90440f) for generously providing the server resources we use for our demonstration instance, our staging and testing environments, and more
- [JetBrains](https://www.jetbrains.com/) for making our development faster, easier and more productive with tools like PhpStorm
- [CrowdIn](https://crowdin.com/) for giving us a simple and powerful tool to help translate our application for users around the world
- The creators and maintainers of the many free and open-source tools that AzuraCast is built on, who have done so much to help move FOSS forward

## Support AzuraCast Development

AzuraCast will always be available free of charge, but if you find the software useful and would like to support the project's lead developer, visit either of the links below. Your support is greatly appreciated.

<a href="https://ko-fi.com/A736ATQ" target="_blank" title="Buy me a coffee!"><img height='32' style='border:0px;height:32px;' src='https://az743702.vo.msecnd.net/cdn/kofi1.png?v=b' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>

<a href="https://www.patreon.com/bePatron?u=232463" target="_blank" title="Become a Patron"><img src="https://c5.patreon.com/external/logo/become_a_patron_button.png"></a>