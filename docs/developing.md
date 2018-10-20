---
title: Developing for AzuraCast
---

[[toc]]

## Best Practices

Development on the main AzuraCast application should always be applicable to a broad community of radio station operators and not specific features to one station or group of stations.

If you would like to build a set of features specific to one station or group of stations, you should take advantage of AzuraCast's plugin architecture. The plugin system takes advantage of event listeners that are built into AzuraCast itself. Check out the [example plugin](https://github.com/AzuraCast/example-plugin) for more details on what is possible via plugins.

## Setting Up a Local Environment

Regardless of your host operating system, it's highly recommended to use **Docker** when developing locally, as it offers portability, convenience, and a very close approximation of how AzuraCast runs on production environments.

For the steps below, we're assuming you've created a folder where you will store all of your AzuraCast-related projects, like `/home/myuser/azuracast/`.

You will need Git and Docker installed locally. If you're on Windows or Mac, the best way to use Docker is via the native [Docker Desktop](https://www.docker.com/products/docker-desktop) applications for those platforms.

For Windows, an installer tool like [Scoop](https://scoop.sh/) is highly recommended for dependencies like Git and your terminal of choice. A third-party shell client like 

### Clone the Repositories

Using Git, clone the AzuraCast core repository and the various Docker containers into a single folder. When developing locally, the Docker containers are built from scratch, so you will need those repositories to be alongside the main "AzuraCast" project in the same folder.

In the same folder, run your platform's equivalent of:

```bash
git clone https://github.com/AzuraCast/AzuraCast.git
git clone https://github.com/AzuraCast/docker-azuracast-web.git
git clone https://github.com/AzuraCast/docker-azuracast-nginx.git
git clone https://github.com/AzuraCast/docker-azuracast-db.git
git clone https://github.com/AzuraCast/docker-azuracast-influxdb.git
git clone https://github.com/AzuraCast/docker-azuracast-redis.git
git clone https://github.com/AzuraCast/docker-azuracast-radio.git
```

::: tip NOTE
All commands from this point forward should be run in the `AzuraCast` repository's folder. From the parent folder, `cd AzuraCast` to enter the core repository's directory.
:::

### Copy Default Files

Inside the `AzuraCast` repository, copy the example files into their proper locations:

```bash
cp azuracast.dev.env azuracast.env
cp docker-compose.dev.yml docker-compose.yml
```

### Modify the Environment File

AzuraCast can automatically load data "fixtures" which will preconfigure a sample station with sensible defaults, to avoid needing to complete the setup process every time.

To customize how the fixtures load in your environment, open the newly customized `azuracast.env` file and customize the following values:

```
INIT_BASE_URL=docker.local
INIT_INSTANCE_NAME=local development
INIT_ADMIN_USERNAME=
INIT_ADMIN_PASSWORD=
INIT_MUSIC_PATH=/var/azuracast/www/util/fixtures/init_music
```

### Build the Docker Containers

Build the Docker containers from your local copies by running:

```bash
docker-compose build
```

### Run the in-container installation

Get into the main CLI container by running, from the host computer:

```bash
docker-compose run --rm cli bash
```

Inside the terminal session that spawns, you should already be at `/var/azuracast/www` and logged in as the `azuracast` user.

To install the necessary dependencies using Composer, run:

```bash
composer install
```

If you want to use the data fixtures (see the .env setup above for the necessary customizations) to set up an initial environment for you, run:

```bash
azuracast_cli azuracast:setup --load-fixtures
```

...otherwise, run:

```bash
azuracast_cli azuracast:setup
```

You can now `exit` the CLI shell.

### Spin up the Docker containers

Now that your installation is set up and ready, you can spin up your full installation for the first time.

From the host computer, run:

```bash
docker-compose up -d
```

By default, AzuraCast will be available at http://localhost/. A self-signed TLS certificate is also provided out of the box, so you can take advantage of the HTTPS functionality after manually exempting the site via your browser.

### Building Static Assets

AzuraCast uses a special Docker container containing the full static asset build stack. This makes it very easy to rebuild the compiled assets after having made changes to the JS or SCSS files.

To access the static container, run:

```bash
docker-compose -f docker-compose.static.yml run --rm static
```

From inside the container, you can execute `gulp` (with no flags) to build all CSS and JS files.

### Building Documentation

AzuraCast also has a standalone Docker container (and Docker Compose file) for documentation.

To spin up a live instance that will automatically rebuild after any documentation changes, run:

```bash
docker-compose -f docker-compose.docs.yml build
docker-compose -f docker-compose.docs.yml up -d
```
