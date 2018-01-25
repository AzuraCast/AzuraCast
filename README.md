![](https://github.com/AzuraCast/AzuraCast/raw/master/resources/azuracast.png)

# AzuraCast: A Self-Hosted Web Radio Manager

[![Build Status](https://travis-ci.org/AzuraCast/AzuraCast.svg?branch=master)](https://travis-ci.org/AzuraCast/AzuraCast)
[![Backers on Open Collective](https://opencollective.com/azuracast/backers/badge.svg)](#backers) 
[![Sponsors on Open Collective](https://opencollective.com/azuracast/sponsors/badge.svg)](#sponsors)

**AzuraCast** is a self-hosted, all-in-one web radio management kit. Using its easy installer tools and web interface, you can start up a fully working web radio station in a few quick minutes. 

AzuraCast works for web radio stations of all types and sizes, and is built to run on even the most affordable VPS web hosts. The project is named after Azura Peavielle, the mascot of [its predecessor project](https://github.com/SlvrEagle23/Ponyville-Live).

**AzuraCast is currently in beta.** Many web radio stations already run AzuraCast, but keeping your server up-to-date with the latest code from the GitHub repository is strongly recommended for security, bug fixes and new feature releases. It's unlikely, but updates may result in unexpected issues or data loss, so always make sure to keep your station's media files backed up in a second location.

To install AzuraCast, you should have a basic understanding of the Linux shell terminal. Once installed, every aspect of your radio station can be managed via AzuraCast's simple to use web interface.

## Features

With AzuraCast, you can:

* **Manage your Media:** Upload songs from the web, organize music into folders, and preview songs in your browser.
* **Create Playlists:** Set up standard playlists that play all the time, scheduled playlists for time periods, or special playlists that play once per x songs, or once per x minutes.
* **Set Up Live DJs:** Enable or disable live broadcasting from streamers/DJs, and create individual accounts for each streamer to use.
* **Take Listener Requests:** Let your listeners request specific songs from your playlists, both via an API and a simple public-facing listener page.
* **Analytics and Reports:** Keep track of every aspect of your station's listeners over time. View reports of each song's performance and
* **Station Autopilot:** AzuraCast can automatically assign songs to a playlist based on the song's impact on listener numbers. 
* **Delegate Management:** Create and remove separate administrator accounts for each station manager.
* ...and more.

### Supported Web Radio Software

AzuraCast uses [LiquidSoap](http://liquidsoap.fm/) as an "AutoDJ" to shuffle songs and playlists and provide an always-online radio stream. You can connect to LiquidSoap and broadcast your own live events as a DJ as well.

To broadcast your radio station to the public, AzuraCast supports both of the gold standards in web radio, [IceCast](http://icecast.org/) (v2.4) and [ShoutCast](http://wiki.shoutcast.com/wiki/SHOUTcast_Broadcaster) (v2). You can switch which of these your station uses anytime you want.

You can also use AzuraCast as a tool for relaying or collecting listener statistics and other data about stations that AzuraCast doesn't manage.

### Supported Operating Systems

There are two ways to install AzuraCast:

* **Using Docker and Docker Compose (Recommended)**: This method contains all of the AzuraCast dependencies in prebuilt
    container images. Updating and installing is faster, and there are far fewer issues with software compatibility. This method
    works on any computer that supports the latest version of the Docker Engine and Docker Compose; both can be installed as
    part of the AzuraCast installer script.
    
* **Traditional Installation (Ubuntu 16.04 LTS Only)**: From a clean image of Ubuntu, you can install AzuraCast directly onto
    your server using the included installer scripts, which use Ansible to manage dependencies. Installation and updating are
    slower using this method, but you have more control over the software once installed. If you have other software installed
    on your server, it may conflict with AzuraCast, so always start from a clean installation using this method.

We are always looking to expand our compatibility with host operating systems, and we welcome any assistance in building new deployment scripts for other environments.

### What's Included with AzuraCast

Whether you're using the traditional installer or Docker containers, AzuraCast depends on the same stack of software to operate:

* **[NGINX](https://www.nginx.com)** for serving web pages and the radio proxy
* **[MariaDB](https://mariadb.org/)** as the primary database
* **[PHP 7.2](https://secure.php.net/)** powering the web application
* **[InfluxDB](https://www.influxdata.com/)** for time-series based statistics
* **[Redis](https://redis.io/)** for sessions, database and general caching
* **[LiquidSoap](http://savonet.sourceforge.net/)** as the always-playing "AutoDJ"
* **[IceCast 2](http://icecast.org/)** as a radio broadcasting frontend (Icecast-KH installed on supported platforms)
* **[ShoutCast 2 DNAS](http://wiki.shoutcast.com/wiki/SHOUTcast_DNAS_Server_2)** as an alternative radio frontend (x86/x64 only)

All of these components are automatically downloaded and installed using either of the installation methods below.

## Installing AzuraCast

### Docker Installation (Recommended)

We strongly recommend installing and using AzuraCast via Docker. All of the necessary software packages are built by our automated tools, so installation is as easy as just pulling down the pre-compiled images. There's no need to worry about compatibility with your host operating system, so any host (including Windows and MacOS) will work great out of the box.

#### Step 1: Install Docker and Docker Compose

Your computer or server should be running the newest version of Docker and Docker Compose. You can use the easy scripts below to install both if you're starting from scratch:

```bash
wget -qO- https://get.docker.com/ | sh

COMPOSE_VERSION=`git ls-remote https://github.com/docker/compose | grep refs/tags | grep -oP "[0-9]+\.[0-9][0-9]+\.[0-9]+$" | tail -n 1`
sudo sh -c "curl -L https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose"
sudo chmod +x /usr/local/bin/docker-compose
sudo sh -c "curl -L https://raw.githubusercontent.com/docker/compose/${COMPOSE_VERSION}/contrib/completion/bash/docker-compose > /etc/bash_completion.d/docker-compose"
```

If you're not installing as root, you may be given instructions to add your current user to the Docker group (i.e. `usermod -aG docker $user`). You should log out or reboot after doing this before continuing below.

#### Step 2: Pull the AzuraCast Docker Compose File

Choose where on the host computer you would like AzuraCast's configuration file to exist on your server.

Inside that directory, run this command to pull the Docker Compose configuration file.

```bash
curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-compose.yml > docker-compose.yml
```

#### Step 3: Run the AzuraCast Docker Installer

From the directory that contains your YML configuration file, run these commands:

```bash
docker-compose pull
docker-compose run --rm cli azuracast_install
docker-compose up -d
```

#### Setting up HTTPS with LetsEncrypt

AzuraCast now supports full encryption with LetsEncrypt. LetsEncrypt offers free SSL certificates with easy validation and renewal.

First, make sure your AzuraCast instance is set up and serving from the domain you want to use. Then, run the following command to generate a new LetsEncrypt certificate:

```bash
docker-compose run --rm letsencrypt certonly --webroot -w /var/www/letsencrypt
```

You will be prompted to specify your e-mail address and domain name. Validation will happen automatically. Once complete, run this command to tell nginx to use your new LetsEncrypt certificate:

```bash
docker-compose run --rm nginx letsencrypt_connect YOURDOMAIN.example.com
``` 

Reload nginx using the command below:

```bash
docker-compose kill -s SIGHUP nginx
```

Your LetsEncrypt certificate is valid for 3 months. To renew the certificates, run this command:

```
docker-compose run --rm letsencrypt renew --webroot -w /var/www/letsencrypt
```

#### Updating with Docker

From inside the base directory where AzuraCast is copied, run the following commands:

```bash
docker-compose down
docker-compose pull
docker-compose run --rm cli azuracast_update
docker-compose up -d
```

#### Docker Volume Backup and Restore

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

## See AzuraCast in Action

### Demo Instance

See the AzuraCast interface in action yourself by visiting our demo site at [demo.azuracast.com](https://demo.azuracast.com/).

* Username: `demo@azuracast.com`
* Password: `demo`

The demo instance is automatically reset at the top of every hour, and always features the latest changes in the codebase.

### Screenshots

Take a look at samples of the AzuraCast interface on the [Screenshots](https://github.com/AzuraCast/AzuraCast/wiki/Screenshots) page on the Wiki.

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

AzuraCast will always be available free of charge, but if you find the software useful and would like to support the project's lead developer, click the link below to buy him a coffee. Your support is greatly appreciated.

<a href='https://ko-fi.com/A736ATQ' target='_blank'><img height='32' style='border:0px;height:32px;' src='https://az743702.vo.msecnd.net/cdn/kofi1.png?v=b' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>

#### Contributors

This project exists thanks to all the people who contribute. [[Contribute](CONTRIBUTING.md)].
<a href="graphs/contributors"><img src="https://opencollective.com/azuracast/contributors.svg?width=890" /></a>


#### Backers

Thank you to all our backers! 🙏 [[Become a backer](https://opencollective.com/azuracast#backer)]

<a href="https://opencollective.com/azuracast#backers" target="_blank"><img src="https://opencollective.com/azuracast/backers.svg?width=890"></a>


#### Sponsors

Support this project by becoming a sponsor. Your logo will show up here with a link to your website. [[Become a sponsor](https://opencollective.com/azuracast#sponsor)]

<a href="https://opencollective.com/azuracast/sponsor/0/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/0/avatar.svg"></a>
<a href="https://opencollective.com/azuracast/sponsor/1/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/1/avatar.svg"></a>
<a href="https://opencollective.com/azuracast/sponsor/2/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/2/avatar.svg"></a>
<a href="https://opencollective.com/azuracast/sponsor/3/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/3/avatar.svg"></a>
<a href="https://opencollective.com/azuracast/sponsor/4/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/4/avatar.svg"></a>
<a href="https://opencollective.com/azuracast/sponsor/5/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/5/avatar.svg"></a>
<a href="https://opencollective.com/azuracast/sponsor/6/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/6/avatar.svg"></a>
<a href="https://opencollective.com/azuracast/sponsor/7/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/7/avatar.svg"></a>
<a href="https://opencollective.com/azuracast/sponsor/8/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/8/avatar.svg"></a>
<a href="https://opencollective.com/azuracast/sponsor/9/website" target="_blank"><img src="https://opencollective.com/azuracast/sponsor/9/avatar.svg"></a>

