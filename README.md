![](https://github.com/AzuraCast/AzuraCast/raw/master/resources/azuracast.png)

# AzuraCast: A Self-Hosted Web Radio Manager

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

#### Step 1: Install Docker

Your computer or server should be running the newest version of Docker and Docker Compose. You can use the easy scripts below to install both if you're starting from scratch:

```bash
wget -qO- https://get.docker.com/ | sh

COMPOSE_VERSION=`git ls-remote https://github.com/docker/compose | grep refs/tags | grep -oP "[0-9]+\.[0-9][0-9]+\.[0-9]+$" | tail -n 1`
sudo sh -c "curl -L https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose"
sudo chmod +x /usr/local/bin/docker-compose
sudo sh -c "curl -L https://raw.githubusercontent.com/docker/compose/${COMPOSE_VERSION}/contrib/completion/bash/docker-compose > /etc/bash_completion.d/docker-compose"
```

If you're not installing as root, you may be given instructions to add your current user to the Docker group (i.e. `usermod -aG docker $user`). You should log out or reboot after doing this before continuing below.

#### Step 2: Clone the AzuraCast Repository

Make sure Git is installed and updated on your host computer or server.

Make a new directory where you'd like AzuraCast's local files to live on your computer. This directory will be mapped into Docker.

Inside that directory, run this command to clone the repository:

```bash
git clone https://github.com/AzuraCast/AzuraCast.git .
```

#### Step 3: Run the AzuraCast Docker Installer

In that new directory, you'll find a script called `docker-install.sh`. Using the commands below, make the script executable and run it:

```bash
chmod +x ./docker-*
./docker-install.sh
```

#### Updating with Docker

From inside the base directory where AzuraCast is copied, run the following commands:

```bash
./docker-update.sh
```

or

```bash
docker-compose down
docker-compose pull
docker-compose run --rm cli azuracast_update
docker-compose up -d
```

#### Docker Volume Backup and Restore

Your station database, statistics and media are stored inside Docker volumes. AzuraCast includes dedicated helper scripts to compress this data into a single portable gzipped file, which can be backed up offsite or moved to a new server.
 
The backup script is located in the drive root and can be accessed by running:

```bash
./docker-backup.sh
```

This will create a file, `backup.tar.gz` in the app root.

To restore the application's state from this compressed file, run:

```bash
./docker-restore.sh
```

Note that the restoration process will wipe any existing AzuraCast database or media that exists inside the Docker volumes.

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

### Local Development with Vagrant

To make local development and testing easier, AzuraCast also includes the necessary configuration to set up a Vagrant box on your computer.

See [the AzuraCast Wiki](https://github.com/AzuraCast/AzuraCast/wiki/Developing-Locally) for detailed instructions on the installation process.

## See AzuraCast in Action

### Demo Instance

See the AzuraCast interface in action yourself by visiting our demo site at [demo.azuracast.com](https://demo.azuracast.com/).

* Username: `demo@azuracast.com`
* Password: `demo`

The demo instance is automatically reset at the top of every hour, and always features the latest changes in the codebase.

A special thank you to our friends at [DigitalOcean](https://m.do.co/c/21612b90440f) for supporting AzuraCast's development!

### Screenshots

Take a look at samples of the AzuraCast interface on the [Screenshots](https://github.com/AzuraCast/AzuraCast/wiki/Screenshots) page on the Wiki.

## AzuraCast API

Once installed and running, AzuraCast exposes an API that allows you to monitor and interact with your stations.

Documentation about this API and its endpoints are available on the [AzuraCast API Documentation](http://azuracast.com/api/).

## License

AzuraCast is licensed under the [Apache license, version 2.0](https://github.com/AzuraCast/AzuraCast/blob/master/License.txt). This project is free and open-source software, and pull requests are always welcome.

## Questions? Comments? Feedback?

AzuraCast is a volunteer project, and we depend on your support and feedback to keep growing.

Issues for this codebase are tracked in this repository's Issues section on GitHub. Anyone can create a new issue for the project, and you are encouraged to do so.

**Please do not e-mail the developer of this project directly with questions or concerns. Thank you!**

## Support AzuraCast Development

AzuraCast will always be available free of charge, but if you find the software useful and would like to support the project's lead developer, click the link below to buy him a coffee. Your support is greatly appreciated.

<a href='https://ko-fi.com/A736ATQ' target='_blank'><img height='32' style='border:0px;height:32px;' src='https://az743702.vo.msecnd.net/cdn/kofi1.png?v=b' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>