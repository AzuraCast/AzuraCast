---
title: About AzuraCast
---

**AzuraCast** is a self-hosted, all-in-one web radio management kit. Using its easy installer and powerful but intuitive web interface, you can start up a fully working web radio station in a few quick minutes. 

AzuraCast works for web radio stations of all types and sizes, and is built to run on even the most affordable VPS web hosts. The project is named after Azura Peavielle, the mascot of [its predecessor project](https://github.com/SlvrEagle23/Ponyville-Live). AzuraCast also has its own project mascot, [Azura Ruisselante](https://github.com/AzuraCast/AzuraCast/wiki/Meet-Azura-Ruisselante) created by the talented artist [Tyson Tan](https://tysontan.deviantart.com/).

**AzuraCast is currently in beta.** Many web radio stations already run AzuraCast, but keeping your server up-to-date with the latest code from the GitHub repository is strongly recommended for security, bug fixes and new feature releases. It's unlikely, but updates may result in unexpected issues or data loss, so always make sure to keep your station's media files backed up in a second location.

To install AzuraCast, you should have a basic understanding of the Linux shell terminal. Once installed, every aspect of your radio station can be managed via AzuraCast's simple to use web interface.

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

* **[Liquidsoap](https://www.liquidsoap.info/)** as the always-playing "AutoDJ"
* **[Icecast 2.4](https://icecast.org/)** as a radio broadcasting frontend (Icecast-KH installed on supported platforms)
* **[SHOUTcast 2 DNAS](http://wiki.shoutcast.com/wiki/SHOUTcast_DNAS_Server_2)** as an alternative radio frontend (x86/x64 only)

#### Supporting Software

* **[NGINX](https://www.nginx.com)** for serving web pages and the radio proxy
* **[MariaDB](https://mariadb.org/)** as the primary database
* **[PHP 7.2](https://secure.php.net/)** powering the web application
* **[InfluxDB](https://www.influxdata.com/)** for time-series based statistics
* **[Redis](https://redis.io/)** for sessions, database and general caching 

## License

AzuraCast is licensed under the [Apache license, version 2.0](https://github.com/AzuraCast/AzuraCast/blob/master/LICENSE.txt). This project is free and open-source software, and pull requests are always welcome.

## Questions? Comments? Feedback?

AzuraCast is a volunteer project, and we depend on your support and feedback to keep growing. Issues for this codebase are tracked using [GitHub Issues](https://github.com/AzuraCast/AzuraCast/issues/new). Anyone can create a new issue for the project, and if you have any problems with your installation or ideas for new features to add, you are encouraged to do so.

## Friends of AzuraCast

We would like to thank the following organizations for their support of AzuraCast's ongoing development:

- [DigitalOcean](https://m.do.co/c/21612b90440f) for generously providing the server resources we use for our demonstration instance, our staging and testing environments, and more
- [JetBrains](https://www.jetbrains.com/) for making our development faster, easier and more productive with tools like PhpStorm
- [CrowdIn](https://crowdin.com/) for giving us a simple and powerful tool to help translate our application for users around the world
- The creators and maintainers of the many free and open-source tools that AzuraCast is built on, who have done so much to help move FOSS forward