![](https://github.com/SlvrEagle23/AzuraCast/raw/master/resources/azuracast.png)

# AzuraCast: A Self-Hosted Web Radio Manager

[![Code Climate](https://codeclimate.com/github/SlvrEagle23/AzuraCast/badges/gpa.svg)](https://codeclimate.com/github/SlvrEagle23/AzuraCast)
[![Test Coverage](https://codeclimate.com/github/SlvrEagle23/AzuraCast/badges/coverage.svg)](https://codeclimate.com/github/SlvrEagle23/AzuraCast/coverage)
[![Build Status](https://travis-ci.org/SlvrEagle23/AzuraCast.svg?branch=master)](https://travis-ci.org/SlvrEagle23/AzuraCast)

**AzuraCast** is a standalone, turnkey web radio management kit. Using its easy installer, you can go from a fresh Linux installation to a fully working web radio station in about 5 minutes. 

Although AzuraCast's code history is extensive, the AzuraCast project itself is fairly new. That's because AzuraCast was built on top of [PVLive](https://github.com/SlvrEagle23/Ponyville-Live), a project originally built for a single fan community, then expanded to serve radio stations of all types as a standalone piece of software.

**AzuraCast is currently in alpha.** The web application is stable and includes a number of powerful features, but if you want to keep up to date with the latest version of the software, keep in mind that updates may cause unexpected issues or data loss. Always make sure to keep your files backed up, especially the files contained in `/var/azuracast/stations`.

To use AzuraCast, you should have a basic understanding of the Linux shell terminal. Once installed, every aspect of your radio station can be managed via AzuraCast's web interface with no advanced Linux knowledge required.

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

### Supported AutoDJ Software

AzuraCast uses [LiquidSoap](http://liquidsoap.fm/) as an "AutoDJ" to shuffle songs and playlists and provide an always-online radio stream.
 
### Supported Radio Frontends

AzuraCast currently has modules to support these radio broadcasting services:
* [IceCast](http://icecast.org/) v2.4
* [ShoutCast](http://wiki.shoutcast.com/wiki/SHOUTcast_Broadcaster) v2.x

You can also use AzuraCast as a tool for syndicating remote stations. This allows you to take advantage of the powerful analytics and reporting tools in AzuraCast for any radio station that uses IceCast or ShoutCast 1 or 2.

### Supported Operating Systems

AzuraCast supports these operating systems and architectures out of the box:
* Ubuntu 14.04 LTS (Trusty) x64
* Ubuntu 16.04 LTS (Xenial) x64
* Ubuntu 16.04 LTS (Xenial) ARM

## Installing AzuraCast

### Installing on a Production Server

AzuraCast is highly optimized for speed and performance, and can run on very inexpensive hardware, from the Raspberry Pi 3 to the lowest-level VPSes offered by most providers.

Since AzuraCast installs its own radio tools, databases and web servers, you should always install AzuraCast on a "clean" server instance with no other web or radio software installed previously.

As the `root` user, execute these commands to set up your AzuraCast server:

```bash
apt-get update
apt-get install -q -y git

mkdir -p /var/azuracast/www
cd /var/azuracast/www
git clone https://github.com/SlvrEagle23/AzuraCast.git .

chmod a+x install.sh
./install.sh
```

The installation process will take between 5 and 15 minutes, depending on your Internet connection. If you encounter an error, let us know in the [Issues section](https://github.com/SlvrEagle23/AzuraCast/issues).

Once the terminal-based installation is complete, you can visit your server's public IP address (`http://ip.of.your.server/`) to finish the web-based setup.

#### Updating

AzuraCast also includes a handy updater script that pulls down the latest copy of the codebase from Git, flushes the site caches and makes any necessary database updates. Run these commands as any user with `sudo` permissions:

```bash
cd /var/azuracast/www

chmod a+x update.sh
./update.sh
```

### Local Development with Vagrant

To make local development and testing easier, AzuraCast also includes the necessary configuration to set up a Vagrant box on your computer.

See [the AzuraCast Wiki](https://github.com/SlvrEagle23/AzuraCast/wiki/Developing-Locally) for detailed instructions on the installation process.

## Screenshots

Take a look at samples of the AzuraCast interface on the [Screenshots](https://github.com/SlvrEagle23/AzuraCast/wiki/Screenshots) page on the Wiki.

## AzuraCast API

Once installed and running, AzuraCast exposes an API that allows you to monitor and interact with your stations.

Documentation about this API and its endpoints are available on the [AzuraCast API Documentation](http://azuracast.com/api/).

## License

AzuraCast is licensed under the [Apache license, version 2.0](https://github.com/SlvrEagle23/AzuraCast/blob/master/License.txt).

## Questions? Comments? Feedback?

AzuraCast is a volunteer project, and we depend on your support and feedback to keep growing.

Issues for this codebase are tracked in this repository's Issues section on GitHub. Anyone can create a new issue for the project, and you are encouraged to do so.

## Support AzuraCast Development

AzuraCast will always be available free of charge, but if you find the software useful and would like to support the project's lead developer, click the link below to buy me a coffee. Your support is greatly appreciated.

<a href='https://ko-fi.com/A736ATQ' target='_blank'><img height='32' style='border:0px;height:32px;' src='https://az743702.vo.msecnd.net/cdn/kofi1.png?v=b' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a> 

## Contribute to AzuraCast

This codebase is Free and Open Source Software, both to help our team maintain transparency and to encourage contributions from the developer community. If you see a bug or other issue with the codebase, please report an issue or submit a pull request!
