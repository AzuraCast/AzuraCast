![](https://github.com/AzuraCast/AzuraCast/raw/master/resources/azuracast.png)

# AzuraCast: A Self-Hosted Web Radio Manager

[![Build Status](https://travis-ci.org/AzuraCast/AzuraCast.svg?branch=master)](https://travis-ci.org/AzuraCast/AzuraCast)
[![Apache 2.0 License](https://img.shields.io/github/license/azuracast/azuracast.svg)]()
[![Docker Pulls](https://img.shields.io/docker/pulls/azuracast/azuracast_web.svg)](https://hub.docker.com/r/azuracast/azuracast_web/)
[![Twitter Follow](https://img.shields.io/twitter/follow/azuracast.svg?style=social&label=Follow)](https://twitter.com/azuracast)

**AzuraCast** is a self-hosted, all-in-one web radio management kit. Using its easy installer and powerful but intuitive web interface, you can start up a fully working web radio station in a few quick minutes. 

AzuraCast works for web radio stations of all types and sizes, and is built to run on even the most affordable VPS web hosts. The project is named after Azura Peavielle, the mascot of [its predecessor project](https://github.com/SlvrEagle23/Ponyville-Live). AzuraCast also has its own project mascot, [Azura Ruisselante](https://azuracast.com/mascot.html) created by the talented artist [Tyson Tan](https://tysontan.deviantart.com/).

**AzuraCast is currently in beta.** Many web radio stations already run AzuraCast, but keeping your server up-to-date with the latest code from the GitHub repository is strongly recommended for security, bug fixes and new feature releases. It's unlikely, but updates may result in unexpected issues or data loss, so always make sure to keep your station's media files backed up in a second location.

To install AzuraCast, you should have a basic understanding of the Linux shell terminal. Once installed, every aspect of your radio station can be managed via AzuraCast's simple to use web interface.

## Live Demo

Want to see AzuraCast for yourself? Check out [screenshots](https://azuracast.com/screenshots.html) or visit
our demo site at [demo.azuracast.com](https://demo.azuracast.com/):

* Username: `demo@azuracast.com`
* Password: `demo`

## Install AzuraCast

- **[Docker Installation (Recommended)](https://azuracast.com/install.html#using-docker-recommended)**: Docker offers an easy-to-use experience with prebuilt images. Updates are simple and AzuraCast won't interfere with other software on your server. You should use this method whenever possible.

- **[Traditional Installation](https://azuracast.com/install.html#traditional-installation)**: For advanced users, if you want more customizability or need to run a leaner installation, you can use the Traditional installation method to install AzuraCast on Ubuntu servers.

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

## AzuraCast API

Once installed and running, AzuraCast exposes an API that allows you to monitor and interact with your stations. Documentation about this API and its endpoints are available on the [AzuraCast API Documentation](https://azuracast.com/api/).

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