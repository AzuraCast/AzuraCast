#!/usr/bin/env bash

# This is a general-purpose function to ask Yes/No questions in Bash, either
# with or without a default answer. It keeps repeating the question until it
# gets a valid answer.
ask() {
    # https://djm.me/ask
    local prompt default reply

    while true; do

        if [ "${2:-}" = "Y" ]; then
            prompt="Y/n"
            default=Y
        elif [ "${2:-}" = "N" ]; then
            prompt="y/N"
            default=N
        else
            prompt="y/n"
            default=
        fi

        # Ask the question (not using "read -p" as it uses stderr not stdout)
        echo -n "$1 [$prompt] "

        # Read the answer (use /dev/tty in case stdin is redirected from somewhere else)
        read reply </dev/tty

        # Default?
        if [ -z "$reply" ]; then
            reply=$default
        fi

        # Check if the reply is valid
        case "$reply" in
            Y*|y*) return 0 ;;
            N*|n*) return 1 ;;
        esac

    done
}

#
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

    if [[ $(which docker-compose) ]]; then
        echo "Docker Compose is already installed! Continuing..."
    else
        if ask "Docker Compose does not appear to be installed. Install Docker Compose now?" Y; then
            COMPOSE_VERSION=`git ls-remote https://github.com/docker/compose | grep refs/tags | grep -oP "[0-9]+\.[0-9][0-9]+\.[0-9]+$" | tail -n 1`
            sudo sh -c "curl -L https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose"
            sudo chmod +x /usr/local/bin/docker-compose
            sudo sh -c "curl -L https://raw.githubusercontent.com/docker/compose/${COMPOSE_VERSION}/contrib/completion/bash/docker-compose > /etc/bash_completion.d/docker-compose"
        fi
    fi

    if [ ! -f .env ]; then
        echo "Writing default .env file..."
        curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/.env > .env
    fi

    if [ ! -f docker-compose.yml ]; then
        echo "Retrieving default docker-compose.yml file..."
        curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-compose.sample.yml > docker-compose.yml
    fi

    docker-compose pull
    docker-compose run --rm cli azuracast_install
    docker-compose up -d
}

#
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

    docker-compose pull
    docker-compose run --rm cli azuracast_update
    docker-compose up -d

    docker rmi $(docker images | grep "none" | awk '/ / { print $3 }')
}

#
# Usage:
# ./docker.sh backup [/custom/backup/dir/custombackupname.tar.gz]
#
backup() {
    APP_BASE_DIR=$(pwd)

    BACKUP_PATH=${1:-"./backup.tar.gz"}
    BACKUP_DIR=$(cd `dirname "$BACKUP_PATH"` && pwd)
    BACKUP_FILENAME=`basename "$BACKUP_PATH"`

    cd $APP_BASE_DIR

    docker-compose down

    docker run --rm -v $BACKUP_DIR:/backup \
        -v azuracast_db_data:/azuracast/db \
        -v azuracast_influx_data:/azuracast/influx \
        -v azuracast_station_data:/azuracast/stations \
        busybox tar zcvf /backup/$BACKUP_FILENAME /azuracast

    docker-compose up -d
}

#
# Usage:
# ./docker.sh restore [/custom/backup/dir/custombackupname.tar.gz]
#
restore() {
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
# Usage: ./docker.sh uninstall
#
uninstall() {
    if ask "This operation is destructive and will wipe your existing Docker containers. Continue? [y/N] " N; then

        docker-compose down -v
        docker stop $(docker ps -a -q)
        docker rm $(docker ps -a -q)
        docker volume prune -f

    fi
}

$*