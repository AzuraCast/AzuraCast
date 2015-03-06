# PVLive: The Ponyville Live! Web Application

![PVL Logo](https://raw.githubusercontent.com/BravelyBlue/PVLive/master/web/resources/bitbucket.png)

---

PVLive is the flagship web application for the [Ponyville Live!](http://ponyvillelive.com/) network, maintained by Bravely Blue Media LLC.

The PVLive application is built on several powerful technologies:

* [Twitter Bootstrap 2](http://getbootstrap.com/2.3.2/) (Frontend UI)
* [Phalcon](http://phalconphp.com/en/) (PHP MVC Framework)
* [Doctrine 2](http://www.doctrine-project.org/) (PHP Database ORM / Abstraction)
* [Composer](https://getcomposer.org/) (Dependencies)
* [Vagrant](http://www.vagrantup.com/) (Local Development)
* [Compass/SCSS](http://sass-lang.com/) (Stylesheet Coolness)

Behind the scenes, the system is powered by a LEMP (Linux, [nginx](http://nginx.org/), [MySQL](http://www.mysql.com/) and PHP) stack.

## Contribute!

The PVLive codebase has now been made public, both to help our team maintain transparency and to encourage contributions from the developer community. If you see a bug or other issue with the codebase, please report an issue or submit a pull request!

## Developing Locally

Want to help improve the PVL application codebase? Now you can run your own local virtual machine for development! Follow these steps to get started:

* Clone this repository to your hard drive.
* Install [Vagrant](http://www.vagrantup.com/) for your OS.
* Install [the Vagrant hostsupdater plugin](https://github.com/cogitatio/vagrant-hostsupdater) via Vagrant.
* Install [VirtualBox](https://www.virtualbox.org/wiki/Downloads) for your OS.
* Open a command line prompt at the root of this repo.
* Type `vagrant up` in the command line.

If you don't already have the Vagrant box downloaded, this process may take several minutes (or even hours, depending on your bandwidth). The box image is cached locally, though, making future vagrant runs easy.

**Note**: You will only see live production-grade data from the main PVL server if you have the proper API key set in `app/config/apis.conf.php`. This API key is private, and available upon request from [pr@ponyvillelive.com](mailto:pr@ponyvillelive.com).

A new super-administrator account will also be created, with the username `admin@ponyvillelive.com` and the password `password`. Ports are automatically followed as below.

### SSH

You can connect to the Vagrant VM by typing `vagrant ssh` into the command line of the host computer.

### Web Server

The web server is configured by default to respond to `dev.pvlive.me:8080`. The URL should automatically be added to your hosts file by the Vagrant hostsupdater plugin.

The web application resides by default in the `/var/www/vagrant/` directory.

### Database

MySQL can be accessed directly by connecting to the VirtualBox instance via SSH tunnel, using the SSH username `vagrant` and password `vagrant`.

The default MySQL `root` password is `password`.

## Questions? Comments? Feedback?

Ponyville Live! is a volunteer project, and we depend on your support and feedback to keep growing.

Issues for the PVLive codebase are tracked in this repository's Issues section on Github. Anyone can create a new issue for the project, and you are encouraged to do so.

If you have any further questions, comments or suggestions, just visit the [Contact Us](http://ponyvillelive.com/contact) page for more information on how to reach our team.