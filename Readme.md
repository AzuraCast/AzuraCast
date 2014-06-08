# PVLive: The Ponyville Live! Web Application

![PVL Logo](https://bytebucket.org/bravelyblue/pvlive/raw/072a2925f95b5dc05375b6ca0d39205194b1c04e/web/resources/bitbucket.png)

---

PVLive is the flagship web application for the [Ponyville Live!](http://ponyvillelive.com/) network, maintained by Bravely Blue Media LLC.

The PVLive application is built on several powerful technologies:

* [Twitter Bootstrap 2](http://getbootstrap.com/2.3.2/) (Frontend UI)
* [ZendFramework 1.x](http://framework.zend.com/) (PHP MVC Framework)
* [Doctrine 2](http://www.doctrine-project.org/) (PHP Database ORM / Abstraction)
* [Composer](https://getcomposer.org/) (Dependencies)
* [Vagrant](http://www.vagrantup.com/) (Local Development)

Behind the scenes, the system is powered by a LNMP (Linux, [nginx](http://nginx.org/), [MySQL](http://www.mysql.com/) and PHP) stack.

## Contribute!

The PVLive codebase has now been made public, both to help our team maintain transparency and to encourage contributions from the developer community. If you see a bug or other issue with the codebase, please report an issue or submit a pull request!

## Deploying Locally

It is now very easy to set up the PVLive application for local development. Just follow these steps:

* Clone this repository to your hard drive.
* Install [Vagrant](http://www.vagrantup.com/) for your OS.
* Install [VirtualBox](https://www.virtualbox.org/wiki/Downloads) for your OS.
* Open a command line prompt at the root of this repo.
* Type `vagrant up` in the command line.

In a few minutes, a full VM will be deployed and customized on your computer, complete with a working copy of this application! Ports are automatically followed as below.

A new super-administrator account will also be created, with the username `admin@ponyvillelive.com` and the password `password`.

### SSH

You can connect to the Vagrant VM by typing `vagrant ssh` into the command line of the host computer.

### Web Server

The web server is configured by default to respond to either `localhost:8080` or `www.pvlive.dev:8080`. The latter must be entered into your hosts file to properly resolve.

The web application resides by default in the `/var/www/vagrant/` directory.

### Database

MySQL can be accessed directly by connecting to `localhost/127.0.0.1` port `33060`.

The `root` password is `password`.