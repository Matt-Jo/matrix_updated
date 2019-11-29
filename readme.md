## Environment requirements

1. GIT
1. Docker 2.0
1. Docker engine version 18.09
1. Docker Compose version 1.23
1. GNU Make ( for Windows use "MinGW Installation Manager" or chocolatey )

# GIT

## Setup repo as remote

1. `git remote add front-end git@github.com:CablesAndKits/front-end.git`
2. `git remote -v` check to make sure you've crated it
3. `git clone front-end`
4. `git pull front-end <project branch>`

### Workflow

1. `git status`
2. `git diff`
3. `git add`
4. `git commit`
5. `git push <remote repo> <remote branch>` -- the remote branch and local branch need to be the same name for this shorthand
6. `git push <remote repo> <local branch>:<remote branch>`
7. Once the new branch is created in the remote repo with the changes got to github and create a pull request against the project branch

### Repo Settings

1. Add new user to the front-end team, which is granted write access to repo or add user to the repo
2. Restrict master and main project branch

# Infrastructure overview

Creation of the local environment; operating system, libraries, and setup of the architecture is defined as code inside docker folder.

Each component of the architecture is packaged as a linux container.
Containers behave as separated computers with their own isolated resources.
Most containers can read from some folders inside the repo; Apache and PHP containers can read the source files in this repo, and whatever change you do on the source will be reflected instantly inside your containers and thus noticeable when you run the project locally, while developing or locally testing code changes.

`docker/.env` is the place to store any core setting that affects the behaviour of the app. 

`docker/.myenv` is the recommended place to store secret credentials or override variables defined at `docker/.env`.

Important: **Do not commit passwords or secrets to the repo**

Important: **`docker/.myenv` should not be commited.**

# Commands

## Commands for local development environment

1. `make start`\
starts the environment
1. `make stop`\
stops the environment
1. `make reload`\
load new configuration and restarts the environment
1. `make deps`\
update every third party library registered with composer
1. `make clean`\
Stop and destroy environment
1. `make build`\
fresh rebuild of the environment

### Useful Docker commands

1. `docker-compose run composer_img require --ignore-platform-reqs <composer-vendor-name>`\
add a new third party library to the project
1. `docker container ls` \
List all running containers
1. `docker exec -it <container-name> <bash|sh|ash>` \
Access shell inside container

# Local environment setup

## Windows only

1. Add `127.0.0.1  container.cablesandkits.com` to C:\Windows\System32\Drivers\etc\hosts
1. Install GNU Make: `choco install make` ( see https://chocolatey.org/install )

## MacOS and Linux only

1. Add `0.0.0.0  container.cablesandkits.com` to /etc/hosts

## Remaining steps for every OS

1. Get Docker at https://www.docker.com/products/docker-desktop
1. Add the root folder of your local repository at Docker Desktop: Preferences -> File Sharing
1. Create a file .myenv and set a value for every environment variable commented out at .env
1. Download folder db from https://drive.google.com/open?id=11L2tgZsGsWwzqs-GB0foO5zinudpECUD into docker/mysql/db folder
1. `make init`
1. `make build`


## PHP debugger

PHP debugger runs on the server running the php interpreter, waiting for an http request containing the param `XDEBUG_SESSION_START=PHPSTORM`. For simplicity a browser extension is recommended to append the flag to every request.
Once we send the activation flag to the web server, a debugging session will start and the debugger will attempt to connect back to us on port 9001.

You can easily setup Microsoft VSCode or an IDE like PHPStorm to attach to a remote debugging session.

### VSCODE setup

1. On the left main menu click on "Extensions"
1. Search for "felixfbecker.php-debug" in the marketplace
1. Install and enable the extension; a new button will appear at the main menu with the label "Debug"
1. Click "Debug"
1. Click the cog icon "Open launch.json" at the Debug top bar
1. Push the following json into "configurations" array \

`"configurations": [...,{
    "name": "container.cablesandkits.com",
    "type": "php",
    "request": "launch",
    "port": 9001,
    "pathMappings": {
        "/usr/local/apache2/htdocs": "${workspaceFolder}"
    }
}]`

### PHPSTORM setup

https://www.jetbrains.com/help/phpstorm/configuring-xdebug.html

### Recommended Chrome extension

https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc

## Database dumps for development environments

1. Use docker/mysql/scripts/prepare-db-for-development.sql to output a reduced version of a live database
1. Save the db dump into ./docker/mysql/db/ with extensions .sql or .sql.gz. File names must start with "N-" where N is a number between 2 and 998 (eg: ./docker/mysql/db/2-db-dump.sql.gz or ./docker/mysql/db/2-db-dump.sql). SQL files will be executed in alphabetical order starting with 1-before-import.sql script.
1. `make build`

For big sql dumps, the process might take some time. All containers might be up while in the background, remaining tables are being created and populated. Trying to use the app while tables are missing will cause PDO connection errors.
You can see the progress of background tasks in the log with `make watch-logs`. Look for this line to know the import is done:

`cak_mysql_con    | /usr/local/bin/docker-entrypoint.sh: running /docker-entrypoint-initdb.d/999-after-import.sql`

## SSL certificates for local development

1. Generate a new self signed ssl certificate: `make self-ssl`
1. Trust your self signed certificate in your operating system as follows

### MacOS

![Alt text](documentation/trust-ssl-cert-in-macos.gif?raw=true "Trust self signed cert in macos")

### Windows 10

![Alt text](documentation/trust-ssl-cert-in-windows10.jpg?raw=true "Trust self signed cert in Windows 10")
