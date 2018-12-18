![](https://github.com/AzuraCast/AzuraCast/raw/master/resources/azuracast.png)

# AzuraCast: A Self-Hosted Web Radio Manager

[![Build Status](https://travis-ci.com/AzuraCast/AzuraCast.svg?branch=master)](https://travis-ci.com/AzuraCast/AzuraCast)
[![Apache 2.0 License](https://img.shields.io/github/license/azuracast/azuracast.svg)]()
[![Docker Pulls](https://img.shields.io/docker/pulls/azuracast/azuracast_web.svg)](https://hub.docker.com/r/azuracast/azuracast_web/)
[![Twitter Follow](https://img.shields.io/twitter/follow/azuracast.svg?style=social&label=Follow)](https://twitter.com/azuracast)

**AzuraCast** is a self-hosted, all-in-one web radio management suite. Using its easy installer and powerful but intuitive web interface, you can start up a fully working web radio station in a few quick minutes. 

AzuraCast works for web radio stations of all types and sizes, and is built to run on even the most affordable VPS web hosts. AzuraCast's mascot is [Azura Ruisselante](https://azuracast.com/mascot.html), created by [Tyson Tan](https://tysontan.deviantart.com/).

**AzuraCast is currently in beta.** Many web radio stations already run AzuraCast, but keeping your server up-to-date with the latest code from the GitHub repository is strongly recommended for security, bug fixes and new feature releases. It's unlikely, but updates may result in unexpected issues or data loss, so always make sure to keep your station's media files backed up in a second location.

To install AzuraCast, you should have a basic understanding of the Linux shell terminal. Once installed, every aspect of your radio station can be managed via AzuraCast's web interface.

## Live Demo

Want to see AzuraCast for yourself? Check out [screenshots](https://azuracast.com/screenshots.html) or visit
our demo site at [demo.azuracast.com](https://demo.azuracast.com/):

* Username: `demo@azuracast.com`
* Password: `demo`

## Install AzuraCast

- **[Docker Installation (Recommended)](https://azuracast.com/install.html#using-docker-recommended)**: Docker offers an easy-to-use experience with prebuilt images. Updates are simple and AzuraCast won't interfere with other software on your server. You should use this method whenever possible.

- **[Traditional Installation](https://azuracast.com/install.html#traditional-installation)**: For advanced users, if you want more customizability or need to run a leaner installation, you can use the Traditional installation method to install AzuraCast on Ubuntu servers.

## Features

#### For Radio Stations

- **Rich Media Management:** Upload songs, edit metadata, preview songs and organize music into folders from your browser.
- **Playlists:** Add music to standard-rotation playlists (in sequential or shuffled playback order) or schedule a playlist to play at a scheduled time, or once per x songs/minutes/etc.
- **Live DJs:** Set up individual DJ/streamer accounts and see who's currently streaming from your station's profile page.
- **Web DJ:** Broadcast live directly from your browser, with no extra software needed, with AzuraCast's built-in Web DJ tool.
- **Public Pages:** AzuraCast includes embeddable public pages that you can integrate into your existing web page or use as the basis for your own customized player.
- **Listener Requests:** Let your listeners request specific songs from your playlists, both via an API and a simple public-facing listener page.
- **Remote Relays:** Broadcast your radio signal (including live DJs) to any remote server running Icecast or SHOUTcast.
- **Web Hooks:** Integrate your station with Slack, Discord, TuneIn, Twitter and more by setting up web hooks that connect to third-party services.
- **Detailed Analytics and Reports:** Keep track of every aspect of your station's listeners over time. View reports of each song's impact on your listener count. You can also generate a report that's compatible with SoundExchange for US web radio royalties.

#### For Server Administrators

- **Role-based User Management:** Assign global and per-station permissions to a role, then add users to those roles to control access.
- **Custom Branding:** Modify every aspect of both the internal and public-facing AzuraCast pages by supplying your own custom CSS and JavaScript. 
- **Authenticated RESTful API:** Individual users in the system can create API keys which have the same permissions they have in the system. The AzuraCast API is a powerful and [well-documented](https://www.azuracast.com/api/index.html) tool for interacting with installations. 
- **Web Log Viewing:** Quickly diagnose problems affecting any part of the AzuraCast system through the system-wide web log viewer.
- **Automatic Radio Proxies:** Many users can't connect directly to radio station ports (i.e. 8000) by default, so AzuraCast includes an automatic nginx proxy that lets listeners connect via the http (80) and https (443) ports. These proxies are also compatible with services like CloudFlare.

### What's Included

Whether you're using the traditional installer or Docker containers, AzuraCast will automatically retrieve and install these components for you:

#### Radio Software

* **[Liquidsoap](https://www.liquidsoap.info/)** as the always-playing "AutoDJ"
* **[Icecast 2.4](https://icecast.org/)** as a radio broadcasting frontend (Icecast-KH installed on supported platforms)

For x86/x64 installations, [SHOUTcast 2 DNAS](http://wiki.shoutcast.com/wiki/SHOUTcast_DNAS_Server_2) can also be used as a broadcasting frontend. SHOUTcast is non-free software and does not come bundled with AzuraCast, but can be installed via the administration panel after AzuraCast has been installed.

#### Supporting Software

* **[NGINX](https://www.nginx.com)** for serving web pages and the radio proxy
* **[MariaDB](https://mariadb.org/)** as the primary database
* **[PHP 7.2](https://secure.php.net/)** powering the web application
* **[InfluxDB](https://www.influxdata.com/)** for time-series based statistics
* **[Redis](https://redis.io/)** for sessions, database and general caching 

## AzuraCast API

Once installed and running, AzuraCast exposes an API that allows you to monitor and interact with your stations. Documentation about this API and its endpoints are available on the [AzuraCast API Documentation](https://www.azuracast.com/api/index.html).

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
