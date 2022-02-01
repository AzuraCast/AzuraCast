#!/usr/bin/env bash
# shellcheck disable=SC2145,SC2178,SC2120,SC2162

# Functions to manage .env files
__dotenv=
__dotenv_file=
__dotenv_cmd=.env

.env() {
  REPLY=()
  [[ $__dotenv_file || ${1-} == -* ]] || .env.--file .env || return
  if declare -F -- ".env.${1-}" >/dev/null; then
    .env."$@"
    return
  fi
  return 64
}

.env.-f() { .env.--file "$@"; }

.env.get() {
  .env::arg "get requires a key" "$@" &&
    [[ "$__dotenv" =~ ^(.*(^|$'\n'))([ ]*)"$1="(.*)$ ]] &&
    REPLY=${BASH_REMATCH[4]%%$'\n'*} && REPLY=${REPLY%"${REPLY##*[![:space:]]}"}
}

.env.parse() {
  local line key
  while IFS= read -r line; do
    line=${line#"${line%%[![:space:]]*}"} # trim leading whitespace
    line=${line%"${line##*[![:space:]]}"} # trim trailing whitespace
    if [[ ! "$line" || "$line" == '#'* ]]; then continue; fi
    if (($#)); then
      for key; do
        if [[ $key == "${line%%=*}" ]]; then
          REPLY+=("$line")
          break
        fi
      done
    else
      REPLY+=("$line")
    fi
  done <<<"$__dotenv"
  ((${#REPLY[@]}))
}

.env.export() { ! .env.parse "$@" || export "${REPLY[@]}"; }

.env.set() {
  .env::file load || return
  local key saved=$__dotenv
  while (($#)); do
    key=${1#+}
    key=${key%%=*}
    if .env.get "$key"; then
      REPLY=()
      if [[ $1 == +* ]]; then
        shift
        continue # skip if already found
      elif [[ $1 == *=* ]]; then
        __dotenv=${BASH_REMATCH[1]}${BASH_REMATCH[3]}$1$'\n'${BASH_REMATCH[4]#*$'\n'}
      else
        __dotenv=${BASH_REMATCH[1]}${BASH_REMATCH[4]#*$'\n'}
        continue # delete all occurrences
      fi
    elif [[ $1 == *=* ]]; then
      __dotenv+="${1#+}"$'\n'
    fi
    shift
  done
  [[ $__dotenv == "$saved" ]] || .env::file save
}

.env.puts() { echo "${1-}" >>"$__dotenv_file" && __dotenv+="$1"$'\n'; }

.env.generate() {
  .env::arg "key required for generate" "$@" || return
  .env.get "$1" && return || REPLY=$("${@:2}") || return
  .env::one "generate: ouptut of '${*:2}' has more than one line" "$REPLY" || return
  .env.puts "$1=$REPLY"
}

.env.--file() {
  .env::arg "filename required for --file" "$@" || return
  __dotenv_file=$1
  .env::file load || return
  (($# < 2)) || .env "${@:2}"
}

.env::arg() { [[ "${2-}" ]] || {
  echo "$__dotenv_cmd: $1" >&2
  return 64
}; }

.env::one() { [[ "$2" != *$'\n'* ]] || .env::arg "$1"; }

.env::file() {
  local REPLY=$__dotenv_file
  case "$1" in
  load)
    __dotenv=
    ! [[ -f "$REPLY" ]] || __dotenv="$(<"$REPLY")"$'\n' || return
    ;;
  save)
    if [[ -L "$REPLY" ]] && declare -F -- realpath.resolved >/dev/null; then
      realpath.resolved "$REPLY"
    fi
    { [[ ! -f "$REPLY" ]] || cp -p "$REPLY" "$REPLY.bak"; } &&
      printf %s "$__dotenv" >"$REPLY.bak" && mv "$REPLY.bak" "$REPLY"
    ;;
  esac
}

# Shortcut to convert semver version (x.yyy.zzz) into a comparable number.
version-number() {
  echo "$@" | awk -F. '{ printf("%03d%03d%03d\n", $1,$2,$3); }'
}

# Get the current release channel for AzuraCast
get-release-channel() {
  local AZURACAST_VERSION="latest"
  if [[ -f .env ]]; then
    .env --file .env get AZURACAST_VERSION
    AZURACAST_VERSION="${REPLY:-latest}"
  fi

  echo "$AZURACAST_VERSION"
}

get-release-branch-name() {
  if [[ $(get-release-channel) == "stable" ]]; then
    echo "stable"
  else
    echo "main"
  fi
}

# This is a general-purpose function to ask Yes/No questions in Bash, either
# with or without a default answer. It keeps repeating the question until it
# gets a valid answer.
ask() {
  # https://djm.me/ask
  local prompt default reply

  while true; do

    if [[ "${2:-}" == "Y" ]]; then
      prompt="Y/n"
      default=Y
    elif [[ "${2:-}" == "N" ]]; then
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
    Y* | y*) return 0 ;;
    N* | n*) return 1 ;;
    esac

  done
}

# Generate a prompt to set an environment file value.
envfile-set() {
  local VALUE INPUT

  .env --file .env

  .env get "$1"
  VALUE=${REPLY:-$2}

  echo -n "$3 [$VALUE]: "
  read INPUT

  VALUE=${INPUT:-$VALUE}

  .env set "${1}=${VALUE}"
}

#
# Configure the ports used by AzuraCast.
#
setup-ports() {
  envfile-set "AZURACAST_HTTP_PORT" "80" "Port to use for HTTP connections"
  envfile-set "AZURACAST_HTTPS_PORT" "443" "Port to use for HTTPS connections"
  envfile-set "AZURACAST_SFTP_PORT" "2022" "Port to use for SFTP connections"
}

#
# Configure the settings used by LetsEncrypt.
#
setup-letsencrypt() {
  envfile-set "LETSENCRYPT_HOST" "" "Domain name (example.com) or names (example.com,foo.bar) to use with LetsEncrypt"
  envfile-set "LETSENCRYPT_EMAIL" "" "Optional e-mail address for expiration updates"
}

#
# Configure release mode settings.
#
setup-release() {
  set -e
  if [[ ! -f .env ]]; then
    curl -fsSL https://raw.githubusercontent.com/AzuraCast/AzuraCast/main/sample.env -o .env
  fi

  local AZURACAST_VERSION="latest"
  if ask "Prefer stable release versions of AzuraCast?" N; then
    AZURACAST_VERSION="stable"
  fi
  set +e

  .env --file .env set AZURACAST_VERSION=${AZURACAST_VERSION}
}

check-install-requirements() {
  local CURRENT_OS CURRENT_ARCH REQUIRED_COMMANDS SCRIPT_DIR

  set -e

  echo "Checking installation requirements for AzuraCast..."

  CURRENT_OS=$(uname -s)
  if [[ $CURRENT_OS == "Linux" ]]; then
    echo -en "\e[32m[PASS]\e[0m Operating System: ${CURRENT_OS}\n"
  else
    echo -en "\e[41m[FAIL]\e[0m Operating System: ${CURRENT_OS}\n"

    echo "       You are running an unsupported operating system."
    echo "       Automated AzuraCast installation is not currently supported on this"
    echo "       operating system."
    exit 1
  fi

  CURRENT_ARCH=$(uname -m)
  if [[ $CURRENT_ARCH == "x86_64" ]]; then
    echo -en "\e[32m[PASS]\e[0m Architecture: ${CURRENT_ARCH}\n"
  elif [[ $CURRENT_ARCH == "aarch64" ]]; then
    echo -en "\e[32m[PASS]\e[0m Architecture: ${CURRENT_ARCH}\n"
  else
    echo -en "\e[41m[FAIL]\e[0m Architecture: ${CURRENT_ARCH}\n"

    echo "       You are running an unsupported processor architecture."
    echo "       Automated AzuraCast installation is not currently supported on this "
    echo "       operating system."
    exit 1
  fi

  REQUIRED_COMMANDS=(curl awk)
  for COMMAND in "${REQUIRED_COMMANDS[@]}" ; do
    if [[ $(command -v "$COMMAND") ]]; then
      echo -en "\e[32m[PASS]\e[0m Command Present: ${COMMAND}\n"
    else
      echo -en "\e[41m[FAIL]\e[0m Command Present: ${COMMAND}\n"

      echo "       ${COMMAND} does not appear to be installed."
      echo "       Install ${COMMAND} using your host's package manager,"
      echo "       then continue installing using this script."
      exit 1
    fi
  done

  if [[ $EUID -ne 0 ]]; then
    if [[ $(command -v sudo) ]]; then
      echo -en "\e[32m[PASS]\e[0m User Permissions\n"
    else
      echo -en "\e[41m[FAIL]\e[0m User Permissions\n"

      echo "       You are not currently the root user, and "
      echo "       'sudo' does not appear to be installed."
      echo "       Install sudo using your host's package manager,"
      echo "       then continue installing using this script."
      exit 1
    fi
  else
    echo -en "\e[32m[PASS]\e[0m User Permissions\n"
  fi

  SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
  if [[ $SCRIPT_DIR == "/var/azuracast" ]]; then
    echo -en "\e[32m[PASS]\e[0m Installation Directory\n"
  else
    echo -en "\e[93m[WARN]\e[0m Installation Directory\n"
    echo "       AzuraCast is not installed in /var/azuracast, as is recommended"
    echo "       for most installations. This will not prevent AzuraCast from"
    echo "       working, but you will need to update any instructions in our"
    echo "       documentation to reflect your current directory:"
    echo "       $SCRIPT_DIR"
  fi

  echo -en "\e[32m[PASS]\e[0m All requirements met!\n"

  set +e
}

install-docker() {
  set -e

  curl -fsSL get.docker.com -o get-docker.sh
  sh get-docker.sh
  rm get-docker.sh

  if [[ $EUID -ne 0 ]]; then
    sudo usermod -aG docker "$(whoami)"

    echo "You must log out or restart to apply necessary Docker permissions changes."
    echo "Restart, then continue installing using this script."
    exit 1
  fi

  set +e
}

install-docker-compose() {
  set -e
  echo "Installing Docker Compose..."

  curl -fsSL -o docker-compose https://github.com/docker/compose/releases/download/v2.2.2/docker-compose-linux-$(uname -m)

  ARCHITECTURE=amd64
  if [ "$(uname -m)" = "aarch64" ]; then
    ARCHITECTURE=arm64
  fi
  curl -fsSL -o docker-compose-switch https://github.com/docker/compose-switch/releases/download/v1.0.2/docker-compose-linux-${ARCHITECTURE}

  if [[ $EUID -ne 0 ]]; then
    sudo chmod a+x ./docker-compose
    sudo chmod a+x ./docker-compose-switch

    sudo mv ./docker-compose /usr/libexec/docker/cli-plugins/docker-compose
    sudo mv ./docker-compose-switch /usr/local/bin/docker-compose
  else
    chmod a+x ./docker-compose
    chmod a+x ./docker-compose-switch

    mv ./docker-compose /usr/libexec/docker/cli-plugins/docker-compose
    mv ./docker-compose-switch /usr/local/bin/docker-compose
  fi

  echo "Docker Compose updated!"
  set +e
}

run-installer() {
  local AZURACAST_RELEASE_BRANCH
  AZURACAST_RELEASE_BRANCH=$(get-release-branch-name)

  if [[ ! -f .env ]]; then
    curl -fsSL https://raw.githubusercontent.com/AzuraCast/AzuraCast/$AZURACAST_RELEASE_BRANCH/sample.env -o .env
  fi
  if [[ ! -f azuracast.env ]]; then
    curl -fsSL https://raw.githubusercontent.com/AzuraCast/AzuraCast/$AZURACAST_RELEASE_BRANCH/azuracast.sample.env -o azuracast.env
  fi
  if [[ ! -f docker-compose.yml ]]; then
    curl -fsSL https://raw.githubusercontent.com/AzuraCast/AzuraCast/$AZURACAST_RELEASE_BRANCH/docker-compose.sample.yml -o docker-compose.yml
  fi

  touch docker-compose.new.yml

  curl -fsSL https://raw.githubusercontent.com/AzuraCast/AzuraCast/$AZURACAST_RELEASE_BRANCH/docker-compose.installer.yml -o docker-compose.installer.yml
  docker-compose -p azuracast_installer -f docker-compose.installer.yml pull
  docker-compose -p azuracast_installer -f docker-compose.installer.yml run --rm installer install "$@"

  rm docker-compose.installer.yml
}

#
# Run the initial installer of Docker and AzuraCast.
# Usage: ./docker.sh install
#
install() {
  check-install-requirements

  if [[ $(command -v docker) && $(docker --version) ]]; then
    echo "Docker is already installed! Continuing..."
  else
    if ask "Docker does not appear to be installed. Install Docker now?" Y; then
      install-docker
    fi
  fi

  if [[ $(command -v docker-compose) ]]; then
    echo "Docker Compose is already installed. Continuing..."
  else
    if ask "Docker Compose does not appear to be installed. Install Docker Compose now?" Y; then
      install-docker-compose
    fi
  fi

  setup-release

  run-installer "$@"

  # Installer creates a file at docker-compose.new.yml; copy it to the main spot.
  if [[ -s docker-compose.new.yml ]]; then
    if [[ -f docker-compose.yml ]]; then
      rm docker-compose.yml
    fi

    mv docker-compose.new.yml docker-compose.yml
  fi

  # If this script is running as a non-root user, set the PUID/PGID in the environment vars appropriately.
  if [[ $EUID -ne 0 ]]; then
    .env --file .env set AZURACAST_PUID="$(id -u)"
    .env --file .env set AZURACAST_PGID="$(id -g)"
  fi

  docker-compose pull
  docker-compose run --rm --user="azuracast" web azuracast_install "$@"
  docker-compose up -d
  exit
}

install-dev() {
  if [[ $(command -v docker) && $(docker --version) ]]; then
    echo "Docker is already installed! Continuing..."
  else
    if ask "Docker does not appear to be installed. Install Docker now?" Y; then
      install-docker
    fi
  fi

  if [[ $(command -v docker-compose) ]]; then
    echo "Docker Compose is already installed. Continuing..."
  else
    if ask "Docker Compose does not appear to be installed. Install Docker Compose now?" Y; then
      install-docker-compose
    fi
  fi

  if [[ ! -d ../docker-azuracast-radio ]]; then
    if ask "Clone related repositories?" Y; then
      git clone https://github.com/AzuraCast/docker-azuracast-db.git ../docker-azuracast-db
      git clone https://github.com/AzuraCast/docker-azuracast-redis.git ../docker-azuracast-redis
      git clone https://github.com/AzuraCast/docker-azuracast-radio.git ../docker-azuracast-radio
    fi
  fi

  if [[ ! -f docker-compose.yml ]]; then
    cp docker-compose.sample.yml docker-compose.yml
  fi
  if [[ ! -f docker-compose.override.yml ]]; then
    cp docker-compose.dev.yml docker-compose.override.yml
  fi
  if [[ ! -f .env ]]; then
    cp dev.env .env
  fi
  if [[ ! -f azuracast.env ]]; then
    cp azuracast.dev.env azuracast.env

    echo "Customize azuracast.env file now before continuing. Re-run this command to continue installation."
    exit
  fi

  # If this script is running as a non-root user, set the PUID/PGID in the environment vars appropriately.
  if [[ $EUID -ne 0 ]]; then
    .env --file .env set AZURACAST_PUID="$(id -u)"
    .env --file .env set AZURACAST_PGID="$(id -g)"
  fi

  docker-compose build
  docker-compose run --rm --user="azuracast" web azuracast_install "$@"

  docker-compose -f frontend/docker-compose.yml build
  docker-compose -f frontend/docker-compose.yml run --rm frontend npm run dev-build

  docker-compose up -d
  exit
}

#
# Update the Docker images and codebase.
# Usage: ./docker.sh update
#
update() {
  echo "[NOTICE] Before you continue, please make sure you have a recent snapshot of your system and or backed it up."
  if ask "Are you ready to continue with the update?" Y; then

    # Check for a new Docker Utility Script.
    local AZURACAST_RELEASE_BRANCH
    AZURACAST_RELEASE_BRANCH=$(get-release-branch-name)

    curl -fsSL https://raw.githubusercontent.com/AzuraCast/AzuraCast/$AZURACAST_RELEASE_BRANCH/docker.sh -o docker.new.sh

    local UTILITY_FILES_MATCH
    UTILITY_FILES_MATCH="$(
      cmp --silent docker.sh docker.new.sh
      echo $?
    )"

    local UPDATE_UTILITY=0
    if [[ ${UTILITY_FILES_MATCH} -ne 0 ]]; then
      if ask "The Docker Utility Script has changed since your version. Update to latest version?" Y; then
        UPDATE_UTILITY=1
      fi
    fi

    if [[ ${UPDATE_UTILITY} -ne 0 ]]; then
      mv docker.new.sh docker.sh
      chmod a+x docker.sh

      echo "A new Docker Utility Script has been downloaded."
      echo "Please re-run the update process to continue."
      exit
    else
      rm docker.new.sh
    fi

    local dc_config_test=$(docker-compose config)
    if [ $? -ne 0 ]; then
      if ask "Docker Compose needs to be updated to continue. Update to latest version?" Y; then
        install-docker-compose
      fi
    fi

    run-installer --update "$@"

    # Check for updated Docker Compose config.
    local COMPOSE_FILES_MATCH

    if [[ ! -s docker-compose.new.yml ]]; then
      curl -fsSL https://raw.githubusercontent.com/AzuraCast/AzuraCast/$AZURACAST_RELEASE_BRANCH/docker-compose.sample.yml -o docker-compose.new.yml
    fi

    COMPOSE_FILES_MATCH="$(
      cmp --silent docker-compose.yml docker-compose.new.yml
      echo $?
    )"

    if [[ ${COMPOSE_FILES_MATCH} -ne 0 ]]; then
      docker-compose -f docker-compose.new.yml pull
      docker-compose down

      cp docker-compose.yml docker-compose.backup.yml
      mv docker-compose.new.yml docker-compose.yml
    else
      rm docker-compose.new.yml

      docker-compose pull
      docker-compose down
    fi

    docker volume rm azuracast_www_vendor
    docker volume rm azuracast_tmp_data
    docker volume rm azuracast_redis_data

    docker-compose run --rm --user="azuracast" web azuracast_update "$@"
    docker-compose up -d

    if ask "Clean up all stopped Docker containers and images to save space?" Y; then
      docker system prune -f
    fi

    echo "Update complete!"
  fi
  exit
}

#
# Update this Docker utility script.
# Usage: ./docker.sh update-self
#
update-self() {
  local AZURACAST_RELEASE_BRANCH
  AZURACAST_RELEASE_BRANCH=$(get-release-branch-name)

  curl -fsSL https://raw.githubusercontent.com/AzuraCast/AzuraCast/$AZURACAST_RELEASE_BRANCH/docker.sh -o docker.sh
  chmod a+x docker.sh

  echo "New Docker utility script downloaded."
  exit
}

#
# Run a CLI command inside the Docker container.
# Usage: ./docker.sh cli [command]
#
cli() {
  docker-compose run --rm --user="azuracast" web azuracast_cli "$@"
  exit
}

#
# Enter the bash terminal of the running web container.
# Usage: ./docker.sh bash
#
bash() {
  docker-compose exec --user="azuracast" web bash
  exit
}

#
# Enter the MariaDB database management terminal with the correct credentials.
#
db() {
  local MYSQL_HOST MYSQL_PORT MYSQL_USER MYSQL_PASSWORD MYSQL_DATABASE

  .env --file azuracast.env get MYSQL_HOST
  MYSQL_HOST="${REPLY:-mariadb}"

  .env --file azuracast.env get MYSQL_PORT
  MYSQL_PORT="${REPLY:-3306}"

  .env --file azuracast.env get MYSQL_USER
  MYSQL_USER="${REPLY:-azuracast}"

  .env --file azuracast.env get MYSQL_PASSWORD
  MYSQL_PASSWORD="${REPLY:-azur4c457}"

  .env --file azuracast.env get MYSQL_DATABASE
  MYSQL_DATABASE="${REPLY:-azuracast}"

  docker-compose run --rm mariadb mysql --user=${MYSQL_USER} --password=${MYSQL_PASSWORD} \
    --host=${MYSQL_HOST} --port=${MYSQL_PORT} --database=${MYSQL_DATABASE}

  exit
}

#
# Back up the Docker volumes to a .tar.gz file.
# Usage:
# ./docker.sh backup [/custom/backup/dir/custombackupname.zip]
#
backup() {
  local BACKUP_PATH BACKUP_DIR BACKUP_FILENAME BACKUP_EXT
  BACKUP_PATH=$(readlink -f ${1:-"./backup.tar.gz"})
  BACKUP_DIR=$(dirname -- "$BACKUP_PATH")
  BACKUP_FILENAME=$(basename -- "$BACKUP_PATH")
  BACKUP_EXT="${BACKUP_FILENAME##*.}"
  shift

  # Prepare permissions
  if [[ $EUID -ne 0 ]]; then
    .env --file .env set AZURACAST_PUID="$(id -u)"
    .env --file .env set AZURACAST_PGID="$(id -g)"
  fi

  docker-compose run --rm web azuracast_cli azuracast:backup "/var/azuracast/backups/${BACKUP_FILENAME}" "$@"

  # Move from Docker volume to local filesystem
  docker run --rm -v "azuracast_backups:/backup_src" \
  -v "$BACKUP_DIR:/backup_dest" \
  busybox mv "/backup_src/${BACKUP_FILENAME}" "/backup_dest/${BACKUP_FILENAME}"
}

#
# Restore an AzuraCast backup into Docker.
# Usage:
# ./docker.sh restore [/custom/backup/dir/custombackupname.zip]
#
restore() {
  local BACKUP_PATH BACKUP_DIR BACKUP_FILENAME BACKUP_EXT
  BACKUP_PATH=$(readlink -f ${1:-"./backup.tar.gz"})
  BACKUP_DIR=$(dirname -- "$BACKUP_PATH")
  BACKUP_FILENAME=$(basename -- "$BACKUP_PATH")
  BACKUP_EXT="${BACKUP_FILENAME##*.}"
  shift

  if [[ ! -f .env ]] || [[ ! -f azuracast.env ]]; then
    echo "AzuraCast hasn't been installed yet on this server."
    echo "You should run './docker.sh install' first before restoring."
    exit 1
  fi

  if [[ ! -f ${BACKUP_PATH} ]]; then
    echo "File '${BACKUP_PATH}' does not exist. Nothing to restore."
    exit 1
  fi

  if ask "Restoring will remove any existing AzuraCast installation data, replacing it with your backup. Continue?" Y; then
    docker-compose down -v

    docker volume create azuracast_backups

    # Move from local filesystem to Docker volume
    docker run --rm -v "$BACKUP_DIR:/backup_src" \
      -v "azuracast_backups:/backup_dest" \
      busybox mv "/backup_src/${BACKUP_FILENAME}" "/backup_dest/${BACKUP_FILENAME}"

    # Prepare permissions
    if [[ $EUID -ne 0 ]]; then
      .env --file .env set AZURACAST_PUID="$(id -u)"
      .env --file .env set AZURACAST_PGID="$(id -g)"
    fi

    docker-compose run --rm web azuracast_restore "/var/azuracast/backups/${BACKUP_FILENAME}" "$@"

    # Move file back from volume to local filesystem
    docker run --rm -v "azuracast_backups:/backup_src" \
      -v "$BACKUP_DIR:/backup_dest" \
      busybox mv "/backup_src/${BACKUP_FILENAME}" "/backup_dest/${BACKUP_FILENAME}"

    docker-compose down
    docker-compose up -d
  fi

  exit
}

#
# Restore the Docker volumes from a legacy backup format .tar.gz file.
# Usage:
# ./docker.sh restore [/custom/backup/dir/custombackupname.tar.gz]
#
restore-legacy() {
  local APP_BASE_DIR BACKUP_PATH BACKUP_DIR BACKUP_FILENAME

  APP_BASE_DIR=$(pwd)

  BACKUP_PATH=${1:-"./backup.tar.gz"}
  BACKUP_DIR=$(cd "$(dirname "$BACKUP_PATH")" && pwd)
  BACKUP_FILENAME=$(basename "$BACKUP_PATH")

  cd "$APP_BASE_DIR" || exit

  if [ -f "$BACKUP_PATH" ]; then
    docker-compose down

    docker volume rm azuracast_db_data azuracast_station_data
    docker volume create azuracast_db_data
    docker volume create azuracast_station_data

    docker run --rm -v "$BACKUP_DIR:/backup" \
      -v azuracast_db_data:/azuracast/db \
      -v azuracast_station_data:/azuracast/stations \
      busybox tar zxvf "/backup/$BACKUP_FILENAME"

    docker-compose up -d
  else
    echo "File $BACKUP_PATH does not exist in this directory. Nothing to restore."
    exit 1
  fi

  exit
}

#
# DEVELOPER TOOL:
# Access the static console as a developer.
# Usage: ./docker.sh static [static_container_command]
#
static() {
  docker-compose -f frontend/docker-compose.yml down -v
  docker-compose -f frontend/docker-compose.yml build
  docker-compose --env-file=.env -f frontend/docker-compose.yml run --rm frontend "$@"
  exit
}

#
# Stop all Docker containers and remove related volumes.
# Usage: ./docker.sh uninstall
#
uninstall() {
  if ask "This operation is destructive and will wipe your existing Docker containers. Continue?" N; then

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

  exit
}

#
# Create and link a LetsEncrypt SSL certificate.
# Usage: ./docker.sh letsencrypt-create
#
letsencrypt-create() {
  setup-letsencrypt
  docker-compose down
  docker-compose up -d
  exit
}

#
# Utility script to facilitate switching ports.
# Usage: ./docker.sh change-ports
#
change-ports() {
  setup-ports

  docker-compose down
  docker-compose up -d
}

# Ensure we're in the same directory as this script.
cd "${BASH_SOURCE%/*}/" || exit

"$@"
