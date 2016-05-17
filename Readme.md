![AzuraCast](https://raw.githubusercontent.com/SlvrEagle23/AzuraCast/master/resources/azuracast.png)

**WARNING: This project is in very early development, and is not yet ready for use in development or production environments! Follow the project for updates as it rolls out into pre-alpha, alpha and release states.**

**AzuraCast** is a standalone web radio management kit.
 
You can either use AzuraCast to spin up a brand new radio station from the ground up (using its built-in installer scripts), or use the web application to manage an existing radio setup.

Currently, AzuraCast supports [LiquidSoap](http://liquidsoap.fm/) for playlist and station setup, and [IceCast](http://icecast.org/) for broadcasting to the web. AzuraCast has been tested to work with Ubuntu 14.04 LTS edition.

AzuraCast offers the following functionality to radio station operators:

* Create and manage administrator accounts to delegate station management to others
* Upload and manage playlists
* Hourly and daily station listener statistics
* Listener metrics arranged by time of day and day of week 
* A timeline of all songs played in the last 48 hours

## Installing on a Production Server

**NOTE: This application is in early development stages. Lots of essential features work, but additional features are being added constantly, and things may change rapidly in subsequent updates. Use at your own risk!**

As the `root` user, execute the following commands to begin installation and setup of a fresh AzuraCast server:

```bash
apt-get update && apt-get install -q -y git

mkdir -p /var/azuracast/www
cd /var/azuracast/www
git clone https://github.com/SlvrEagle23/AzuraCast.git .

chmod a+x install.sh
./install.sh
```

### Updating

AzuraCast also includes a handy updater script that pulls down the latest copy of the codebase from Git, flushes the site caches and makes any necessary database updates. You should also run this script as the `root` user.

```bash
cd /var/azuracast/www

chmod a+x update.sh
./update.sh
```

## Local Development with Vagrant

This application supports **Vagrant** for local development and testing before launching a production station.

* Clone this repository to your hard drive.
* Install [Vagrant](http://www.vagrantup.com/) for your OS.
* Install [VirtualBox](https://www.virtualbox.org/wiki/Downloads) for your OS.
* Open a command line prompt at the root of this repo.
* Type `vagrant up` in the command line.

If you don't already have the Vagrant box downloaded, this process may take several minutes (or even hours, depending on your bandwidth). The box image is cached locally, though, making future vagrant runs easy.

### SSH

You can connect to the Vagrant VM by typing `vagrant ssh` into the command line of the host computer. By default, the server is also available at `localhost:2222`.

### Web Server

The web server is configured by default to respond to `http://localhost:8080`.

The web application resides by default in the `/var/azuracast/www` directory inside the Vagrant virtual machine.

### Database

MySQL can be accessed directly by connecting to the VirtualBox instance via SSH tunnel, using the SSH username `vagrant` and password `vagrant`.

The default MySQL `root` password is `password`.

### Common Tasks

The Vagrant virtual machine is automatically configured with Composer (PHP dependencies), NPM (Grunt and Bower) and other important tools pre-installed.

Stylesheets are managed by Grunt, which is installed on the Vagrant VM by default. To make changes to stylesheets:

* Connect to the server via SSH.
* `cd /var/azuracast/build`
* `grunt watch`
* Make changes to LESS files in the `web/static/less` directory.

Do not directly make changes to the compiled CSS files, as they will automatically be overwritten by this automated system.

## Questions? Comments? Feedback?

AzuraCast is a volunteer project, and we depend on your support and feedback to keep growing.

Issues for this codebase are tracked in this repository's Issues section on GitHub. Anyone can create a new issue for the project, and you are encouraged to do so.

## Contribute to AzuraCast

This codebase is Free and Open Source Software, both to help our team maintain transparency and to encourage contributions from the developer community. If you see a bug or other issue with the codebase, please report an issue or submit a pull request!