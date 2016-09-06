![AzuraCast](https://raw.githubusercontent.com/SlvrEagle23/AzuraCast/master/resources/azuracast.png)

**NOTE: This project is currently in the Alpha stage of development. The web application is stable and includes a number of powerful features, and can be used in a development (and, in some cases, a production) environment; however, new updates are released very frequently, and these updates may result in loss of data. You should maintain frequent backups of files, especially files contained in `/var/azuracast/stations`.**

**AzuraCast** is a standalone turnkey web radio management kit.
 
You can either use AzuraCast to spin up a brand new radio station from the ground up (using its built-in installer scripts), or use the web application to manage an existing radio setup.

AzuraCast uses [LiquidSoap](http://liquidsoap.fm/) for "AutoDJ" and song requests and [IceCast](http://icecast.org/) for broadcasting and accepting live streamers.

AzuraCast supports the following host operating systems, with more to come:
* Ubuntu 14.04 (Trusty Tahr)

With AzuraCast, radio station owners can:

* Automate the process of setting up and running both a broadcasting service (IceCast) and an AutoDJ service (LiquidSoap)
* Accept song requests (with a configurable delay) via an API
* Manage streamer/DJ accounts for live broadcasting
* Create individual administrator accounts to delegate station management to others
* Upload and manage playlists and media files directly from the web
* Automatically assign songs to playlists based on their previous performance
* View detailed statistics about listeners and peak/low audience times

## Installing on a Production Server

See [the AzuraCast Wiki](https://github.com/SlvrEagle23/AzuraCast/wiki/Deploying-to-an-Existing-Server) for more information on installing to a production server.

## Local Development with Vagrant

See [the AzuraCast Wiki](https://github.com/SlvrEagle23/AzuraCast/wiki/Developing-Locally) for more information on developing locally with Vagrant.

## AzuraCast API

Once installed and running, AzuraCast exposes an API that allows you to monitor and interact with your stations.

Documentation about this API and its endpoints are available on the [AzuraCast APIary Documentation](http://docs.azuracast.apiary.io/).

## License

AzuraCast is licensed under the [Apache license, version 2.0](https://github.com/SlvrEagle23/AzuraCast/blob/master/License.txt).

## Questions? Comments? Feedback?

AzuraCast is a volunteer project, and we depend on your support and feedback to keep growing.

Issues for this codebase are tracked in this repository's Issues section on GitHub. Anyone can create a new issue for the project, and you are encouraged to do so.

## Contribute to AzuraCast

This codebase is Free and Open Source Software, both to help our team maintain transparency and to encourage contributions from the developer community. If you see a bug or other issue with the codebase, please report an issue or submit a pull request!