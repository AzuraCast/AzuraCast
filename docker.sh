#!/usr/bin/env bash

# This is a general-purpose function to ask Yes/No questions in Bash, either
# with or without a default answer. It keeps repeating the question until it
# gets a valid answer.
ask() {
    # https://djm.me/ask
    local prompt default reply

    while true; do

        if [[ "${2:-}" = "Y" ]]; then
            prompt="Y/n"
            default=Y
        elif [[ "${2:-}" = "N" ]]; then
            prompt="y/N"
            default=N
        else
            prompt="y/n"
            default=
        fi

        # Ask the question (not using "read -p" as it uses stderr not stdout)
        echo -n "$1 [$prompt] "

        read reply

        # Default?
        if [[ -z "$reply" ]]; then
            reply=${default}
        fi

        # Check if the reply is valid
        case "$reply" in
            Y*|y*) return 0 ;;
            N*|n*) return 1 ;;
        esac

    done
}

#
# Run the initial installer of Docker and AzuraCast.
# Usage: ./docker.sh install
#
install() {
    if [[ $(which docker) && $(docker --version) ]]; then
        echo "Docker is already installed! Continuing..."
    else
        if ask "Docker does not appear to be installed. Install Docker now?" Y; then
            curl -fsSL get.docker.com -o get-docker.sh
            sh get-docker.sh
            rm get-docker.sh

            if [[ $EUID -ne 0 ]]; then
                sudo usermod -aG docker `whoami`

                echo "You must log out or restart to apply necessary Docker permissions changes."
                echo "Restart, then continue installing using this script."
                exit
            fi
        fi
    fi

    if [[ $(which docker-compose) && $(docker-compose --version) ]]; then
        echo "Docker Compose is already installed! Continuing..."
    else
        if ask "Docker Compose does not appear to be installed. Install Docker Compose now?" Y; then
            if [[ ! $(which git) ]]; then
                echo "Git does not appear to be installed."
                echo "Install git using your host's package manager,"
                echo "then continue installing using this script."
                exit 1
            fi

            if [[ ! $(which curl) ]]; then
                echo "cURL does not appear to be installed."
                echo "Install curl using your host's package manager,"
                echo "then continue installing using this script."
                exit 1
            fi

            COMPOSE_VERSION=`git ls-remote https://github.com/docker/compose | grep refs/tags | grep -oP "[0-9]+\.[0-9][0-9]+\.[0-9]+$" | tail -n 1`
            sudo sh -c "curl -L https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose"
            sudo chmod +x /usr/local/bin/docker-compose
            sudo sh -c "curl -L https://raw.githubusercontent.com/docker/compose/${COMPOSE_VERSION}/contrib/completion/bash/docker-compose > /etc/bash_completion.d/docker-compose"
        fi
    fi

    if [[ ! -f .env ]]; then
        echo "Writing default .env file..."
        curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/.env > .env
    fi

    if [[ ! -f azuracast.env ]]; then
        echo "Creating default AzuraCast settings file..."
        curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/azuracast.sample.env > azuracast.env
    fi

    if [[ ! -f docker-compose.yml ]]; then
        echo "Retrieving default docker-compose.yml file..."
        curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-compose.sample.yml > docker-compose.yml
    fi

    docker-compose pull
    docker-compose run --user="azuracast" --rm web azuracast_install
    docker-compose up -d
}

#
# Update the Docker images and codebase.
# Usage: ./docker.sh update
#
update() {
    docker-compose down
    docker-compose rm -f

    if ask "Update docker-compose.yml file? This will overwrite any customizations you made to this file?" N; then

        cp docker-compose.yml docker-compose.backup.yml
        echo "Your existing docker-compose.yml file has been backed up to docker-compose.backup.yml."

        curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-compose.sample.yml > docker-compose.yml
        echo "New docker-compose.yml file loaded."

    fi

    if [[ ! -f azuracast.env ]]; then

        curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/azuracast.sample.env > azuracast.env
        echo "Default environment file loaded."
        
    fi

    docker volume rm azuracast_www_data
    docker volume rm azuracast_tmp_data

    docker-compose pull
    docker-compose run --user="azuracast" --rm web azuracast_update
    docker-compose up -d

    docker rmi $(docker images | grep "none" | awk '/ / { print $3 }') 2> /dev/null

    echo "Update complete!"
}

#
# Update this Docker utility script.
# Usage: ./docker.sh update-self
#
update-self() {
    curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker.sh > docker.sh
    chmod a+x docker.sh

    echo "New Docker utility script downloaded."
}

#
# Run a CLI command inside the Docker container.
# Usage: ./docker.sh cli [command]
#
cli() {
    docker-compose run --user="azuracast" --rm web azuracast_cli $*
}

#
# Enter the bash terminal of the running web container.
# Usage: ./docker.sh bash
#
bash() {
    docker-compose exec --user="azuracast" web bash
}

#
# Back up the Docker volumes to a .tar.gz file.
# Usage:
# ./docker.sh backup [/custom/backup/dir/custombackupname.tar.gz]
#
backup() {
    APP_BASE_DIR=$(pwd)

    BACKUP_PATH=${1:-"./backup.tar.gz"}
    BACKUP_DIR=$(cd `dirname "$BACKUP_PATH"` && pwd)
    BACKUP_FILENAME=`basename "$BACKUP_PATH"`
    shift

    cd $APP_BASE_DIR

    if [ ! -f .env ]; then
        echo "Writing default .env file..."
        curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/.env > .env
    fi

    docker-compose run --rm --user="azuracast" \
        -v $BACKUP_DIR:/backup \
        web azuracast_cli azuracast:backup /backup/$BACKUP_FILENAME $*
}

#
# Restore an AzuraCast backup into Docker.
# Usage:
# ./docker.sh restore [/custom/backup/dir/custombackupname.tar.gz]
#
restore() {
    APP_BASE_DIR=$(pwd)

    BACKUP_PATH=${1:-"./backup.tar.gz"}
    BACKUP_DIR=$(cd `dirname "$BACKUP_PATH"` && pwd)
    BACKUP_FILENAME=`basename "$BACKUP_PATH"`
    shift

    cd $APP_BASE_DIR

    if [ ! -f .env ]; then
        echo "Writing default .env file..."
        curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/.env > .env
    fi

    if [ -f $BACKUP_PATH ]; then
        docker-compose run --rm --user="azuracast" \
            -v $BACKUP_DIR:/backup \
            web azuracast_restore /backup/$BACKUP_FILENAME $*

        docker-compose up -d
    else
        echo "File $BACKUP_PATH does not exist in this directory. Nothing to restore."
        exit 1
    fi
    echo 'test'
}

#
# Restore the Docker volumes from a legacy backup format .tar.gz file.
# Usage:
# ./docker.sh restore [/custom/backup/dir/custombackupname.tar.gz]
#
restore-legacy() {
    APP_BASE_DIR=$(pwd)

    BACKUP_PATH=${1:-"./backup.tar.gz"}
    BACKUP_DIR=$(cd `dirname "$BACKUP_PATH"` && pwd)
    BACKUP_FILENAME=`basename "$BACKUP_PATH"`

    cd $APP_BASE_DIR

    if [ -f $BACKUP_PATH ]; then
        docker-compose down

        docker volume rm azuracast_db_data azuracast_influx_data azuracast_station_data
        docker volume create azuracast_db_data
        docker volume create azuracast_influx_data
        docker volume create azuracast_station_data

        docker run --rm -v $BACKUP_DIR:/backup \
            -v azuracast_db_data:/azuracast/db \
            -v azuracast_influx_data:/azuracast/influx \
            -v azuracast_station_data:/azuracast/stations \
            busybox tar zxvf /backup/$BACKUP_FILENAME

        docker-compose up -d
    else
        echo "File $BACKUP_PATH does not exist in this directory. Nothing to restore."
        exit 1
    fi
}

#
# DEVELOPER TOOL:
# Access the static console as a developer.
# Usage: ./docker.sh static [static_container_command]
#
static() {
    docker-compose -f docker-compose.static.yml build
    docker-compose -f docker-compose.static.yml run --rm static $*
}

#
# DEVELOPER TOOL:
# Run the full test suite.
#
dev-tests() {
    dev-lint
    dev-phpstan
    dev-codeception
}

#
# DEVELOPER TOOL:
# Run linter across all PHP code in the app.
#
dev-lint() {
    docker-compose exec --user="azuracast" web composer phplint -- $*
}

#
# DEVELOPER TOOL:
# Run PHPStan for static analysis.
#
dev-phpstan() {
    docker-compose exec --user="azuracast" web composer phpstan -- $*
}

#
# DEVELOPER TOOL:
# Run codeception for unit testing.
#
dev-codeception() {
    docker-compose -f docker-compose.sample.yml -f docker-compose.testing.yml build web
    docker-compose -f docker-compose.sample.yml -f docker-compose.testing.yml run --user="azuracast" --rm web composer codeception -- $*
}

#
# Stop all Docker containers and remove related volumes.
# Usage: ./docker.sh uninstall
#
uninstall() {
    if ask "This operation is destructive and will wipe your existing Docker containers. Continue? [y/N] " N; then

        docker-compose down -v
        docker-compose rm -f
        docker volume prune -f

        echo "All AzuraCast Docker containers and volumes were removed."
        echo "To remove *all* Docker containers and volumes, run:"
        echo "  docker stop \$(docker ps -a -q)"
        echo "  docker rm \$(docker ps -a -q)"
        echo "  docker volume prune -f"
        echo ""
    fi
}

#
# Create and link a LetsEncrypt SSL certificate.
# Usage: ./docker.sh letsencrypt-create domainname.example.com
#
letsencrypt-create() {
    docker-compose exec --user="azuracast" web letsencrypt_connect $*
}

#
# Renew an existing LetsEncrypt SSL certificate
# Usage: ./docker.sh letsencrypt-renew
#
letsencrypt-renew() {
    docker-compose exec --user="azuracast" web letsencrypt_renew $*
}

$*
