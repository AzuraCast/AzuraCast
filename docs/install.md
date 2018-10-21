---
title: Install AzuraCast
---

AzuraCast is flexible and works on a broad number of environments, from inexpensive VPSes and servers to your own home computer running Windows, MacOS or Linux. There are two ways to install AzuraCast that differ significantly.

::: warning
AzuraCast currently only supports running on x86/x64 platforms. ARM/ARMHF platforms (i.e. the Raspberry Pi) are not currently supported due to a software compatibility issue. Follow [this issue](https://github.com/AzuraCast/AzuraCast/issues/332) for updates.
:::

[[toc]]

## Using Docker (Recommended)

We strongly recommend installing and using AzuraCast via Docker. All of the necessary software packages are built by our automated tools, so installation is as easy as just pulling down the pre-compiled images. There's no need to worry about compatibility with your host operating system, so any host (including Windows and MacOS) will work great out of the box.

You can use the AzuraCast Docker utility script to check for (and install, if necessary) the latest version of Docker and Docker Compose, then pull the necessary files and get your instance running.

If you're on a Linux server, you will need `sudo` and `curl` installed, if they aren't already, before running the installer scripts.

```bash
curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker.sh > docker.sh
chmod a+x docker.sh
./docker.sh install
```

Once installation has completed, be sure to follow the [post-installation steps](#post-installation-setup). You can also [set up LetsEncrypt](/docker_sh.html#set-up-letsencrypt) or make other changes to your installation using the [Docker Utility Script](/docker_sh.html) that you've just downloaded.

### Updating

Using the Docker utility script:

```bash
./docker.sh update-self && ./docker.sh update
```

To manually update, from inside the base directory where AzuraCast is copied, run the following commands:

```bash
docker-compose down
docker-compose pull
docker-compose run --rm cli azuracast_update
docker-compose up -d
```

## Traditional Installation

The traditional installation is an advanced option available for those who want more complex customization options or are running on very limited hardware that can't handle the minor overhead of the Docker installation method.

Currently, the following operating systems are supported:

- Ubuntu 16.04 "Xenial" LTS
- Ubuntu 18.04 "Bionic" LTS

::: tip
Some web hosts offer custom versions of Ubuntu that include different software repositories. These may cause compatibility issues with AzuraCast. Many VPS providers are known to work out of the box with AzuraCast (OVH, DigitalOcean, Vultr, etc), and are thus highly recommended if you plan to use the traditional installer.
:::

AzuraCast is optimized for speed and performance, and can run on very inexpensive hardware, from the Raspberry Pi 3 to the lowest-level VPSes offered by most providers.

Since AzuraCast installs its own radio tools, databases and web servers, you should always install AzuraCast on a "clean" server instance with no other web or radio software installed previously.

Execute these commands **as a user with sudo permissions (or root)** to set up your AzuraCast server:

```bash
sudo apt-get update
sudo apt-get install -q -y git

sudo mkdir -p /var/azuracast/www
cd /var/azuracast/www
sudo git clone https://github.com/AzuraCast/AzuraCast.git .

sudo chmod a+x install.sh
./install.sh
```

The installation process will take between 5 and 15 minutes, depending on your Internet connection. If you encounter an error, let us know in the [Issues section](https://github.com/AzuraCast/AzuraCast/issues).

Once the terminal-based installation is complete, you can visit your server's public IP address (`http://ip.of.your.server/`) to finish the web-based setup.

### Updating

AzuraCast also includes a handy updater script that pulls down the latest copy of the codebase from Git, flushes the site caches and makes any necessary database updates. Run these commands as any user with `sudo` permissions:

```bash
cd /var/azuracast/www

sudo chmod a+x update.sh
sudo ./update.sh
```

## Installing on DigitalOcean

Our friends at DigitalOcean offer fast, affordable, scalable hosting that is perfect for services like AzuraCast. Thanks to their support for custom intstallation metadata, you can spin up a new droplet and have a running AzuraCast instance without ever needing to leave your browser. Check out our [detailed DigitalOcean installation guide](/install_do.html) for instructions.

## Post-Installation Setup

Once installation is complete, you should immediately visit your server's public web address. This may be the IP of the server, a domain name (if you've registered one and pointed it at the server), or `localhost` if you're running AzuraCast on your personal computer.

The initial web setup consists of the following steps:
1. Creating a "Super Administrator" account with system-wide administratration permissions
2. Creating the first radio station that the system will manage
3. Customizing important AzuraCast settings, like the site's base URL and HTTPS settings

Don't worry if you aren't sure of these items yet; you can always make changes to any of the items after setup is complete.