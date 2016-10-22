# AzuraCast: A Self-Hosted Web Radio Manager

[![Code Climate](https://codeclimate.com/github/SlvrEagle23/AzuraCast/badges/gpa.svg)](https://codeclimate.com/github/SlvrEagle23/AzuraCast)
[![Test Coverage](https://codeclimate.com/github/SlvrEagle23/AzuraCast/badges/coverage.svg)](https://codeclimate.com/github/SlvrEagle23/AzuraCast/coverage)
[![Build Status](https://travis-ci.org/SlvrEagle23/AzuraCast.svg?branch=master)](https://travis-ci.org/SlvrEagle23/AzuraCast)

**AzuraCast** is a standalone, turnkey web radio management kit. Using its easy installer, you can go from a fresh Linux installation to a fully working web radio station in about 5 minutes. 

Under the hood, AzuraCast uses [LiquidSoap](http://liquidsoap.fm/) as an "AutoDJ" and [IceCast](http://icecast.org/) for broadcasting and live DJs. Once installed, every aspect of your radio station can be managed via AzuraCast's web interface with no advanced Linux knowledge required.

Although AzuraCast's code history is extensive, the AzuraCast project itself is fairly new. That's because AzuraCast was built on top of [PVLive](https://github.com/SlvrEagle23/Ponyville-Live), a project originally built for a single fan community, then expanded to serve radio stations of all types as a standalone piece of software.

**AzuraCast is currently in alpha.** The web application is stable and includes a number of powerful features, but if you want to keep up to date with the latest version of the software, keep in mind that updates may cause unexpected issues or data loss. Always make sure to keep your files backed up, especially the files contained in `/var/azuracast/stations`.

AzuraCast supports the following operating systems and architectures out of the box:
* Ubuntu 14.04 LTS (Trusty) x64
* Ubuntu 16.04 LTS (Xenial) x64
* Ubuntu 16.04 LTS (Xenial) ARM

With AzuraCast, you can:

* **Manage your Media:** Upload songs from the web, organize music into folders, and preview songs in your browser.
* **Create Playlists:** Set up standard playlists that play all the time, scheduled playlists for time periods, or special playlists that play once per x songs, or once per x minutes.
* **Set Up Live DJs:** Enable or disable live broadcasting from streamers/DJs, and create individual accounts for each streamer to use.
* **Take Listener Requests:** Let your listeners request specific songs from your playlists, currently via an API with a web interface coming soon.
* **Analytics and Reports:** Keep track of every aspect of your station's listeners over time. View reports of each song's performance and
* **Station Autopilot:** AzuraCast can automatically assign songs to a playlist based on the song's impact on listener numbers. 
* **Delegate Management:** Create and remove separate administrator accounts for each station manager.
* ...and more.

## Installing AzuraCast

### Installing on a Production Server

See [the AzuraCast Wiki](https://github.com/SlvrEagle23/AzuraCast/wiki/Deploying-to-an-Existing-Server) for more information on installing to a production server.

### Local Development with Vagrant

See [the AzuraCast Wiki](https://github.com/SlvrEagle23/AzuraCast/wiki/Developing-Locally) for more information on developing locally with Vagrant.

## Screenshots

Take a look at samples of the AzuraCast interface on the [Screenshots](https://github.com/SlvrEagle23/AzuraCast/wiki/Screenshots) page on the Wiki.

## AzuraCast API

Once installed and running, AzuraCast exposes an API that allows you to monitor and interact with your stations.

Documentation about this API and its endpoints are available on the [AzuraCast APIary Documentation](http://docs.azuracast.apiary.io/).

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
