# Brief overview of docker and Makefile tools


Docker is the tool we use to build/destroy/start/stop our local infrastructure, and also to perform other operations like refreshing a self signed ssl certificate, clear logs, run automated tests, install third party dependencies.

## Basic Docker concepts:

Host: The machine where you installed docker

Docker image: Is like a class that serves as a model to start containers. Docker images are defined in files named Dockerfile.

Docker container: Is like an instance of a docker image

docker-compose.yml: Is the core definition of our infrastructure in a declarative way. Take a look at it, it's pretty self explanatory. See also a full reference to every concept about this file at https://docs.docker.com/compose/compose-file/

docker-compose.dev.yml: Settings only needed by our development environments. Contains an extra service for a mysql db mostly.

docker-compose.prod.yml: Production rules to build our infrastructure.

docker-compose.dev-macos.yml: Rules to build a development environment in MacOS. The only difference with other yml files, is that it relies on a plugin called docker-sync that emulates NFS with rsync or similar to speed up volumes mapped between a MacOS host and it's containers

docker-sync.yml: Ignore this file, is only needed by docker-compose.dev-macos.yml to fix MacOS volumes mapping performance issues.

apache/Dockerfile: Custom Image definition, based on apache2 official image ( see https://hub.docker.com/_/httpd ). Besides pulling httpd official image, it runs some commands at build time.

Other files inside apache/ folder are configuration files for apache
Every other folder inside https://github.com/guille-mas/Matrix-oSc/tree/php7/docker contains the same; a Dockerfile and configuration files for each container.

.env: Is the core file where you can configure settings that affect how docker will build the infraestructure. To override settings on this file, or add secrets to it, write  .myenv file next to it and define there those variables you would like to override.

See more at https://docs.docker.com/engine/docker-overview/


## Makefile:

Makefile was a standard, easy way, I found to wrap the logic of those operations, instead of having to write a shell scripts per command.


## The build process:

If you take a look at the **build** command in docker/Makefile, you will see this:

`build: clean setup start reload`

In Makefile syntax, it means that when you run "make build" is the same as running:

`make clean && make setup && make start && make reload`

Those commands where chained like that to ease the task of building the environment under different scenarios.

**clean:** tells docker to clean any custom built image from a previous built. Just in case it's not the first time you run a build.

**setup:** run some commands I had to run to fix some issues in MacOS. It will only do something on MacOS. You can see a mention of the issues each command solves from this line to this one . See the comments there, each one starts with "##" and should not be prepended by empty spaces to work on Windows.

**start:** This command does a lot of stuff..

1. If the OS is a MacOS ( and the variable OS as not been set at docker/.myenv to override that behaviour ), docker-compose will use docker-sync plugin to map volumes between your host and your containers. See line 34 of Makefile, where an IF checks if the OS is MacOS ("Darwin").

2. Create every folder needed for our webapp to work correctly ( apache logs, php logs, php composer vendors folder, mysql database folder, php composer cache, and mustache cache folder ). See Makefile:47

The command we run to do this is: 

`docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm utils_img mkdir -p /cak-logs/apache /cak-logs/mysql /cak-logs/php /cak-vendor  /cak-data/database /cak-data/composer/cache /cak-templates/cache`

### Explanation:

docker-compose is the tool that knows how each container should be wired (private net, public facing net, which ports to expose from each containers, which host folder should map to which folder inside a container, etc)

$(DOCKER_COMPOSE_FILE_LIST) is a variable set at runtime that contains a list of docker-compose.*.yml files to load. Each of those files are joined into a single list of settings that tells docker-compose how to build the stack. See here.

run --rm utils_img runs the command we want to run inside utils_img container.
Why do we need utils_img ? Because the syntax of commands to do simple stuff like removing/chowning/chmod/etc files or folders, differ between operating systems.
utils_img is a small custom linux container we have to run commands like "chmod", "chown", "rmdir", "rm", "touch", "mkdir" in a single OS, without having to write each flow for windows, macos and linux, and with the overhead of maintaining that logic across different OSs.

Finally, the command that is going to be executed inside utils_img container is mkdir -p /cak-logs/apache /cak-logs/mysql /cak-logs/php /cak-vendor  /cak-data/database /cak-data/composer/cache /cak-templates/cache 

What are those folders? /cak-logs and all the other folders are folders created inside that container, and synced with folders outside the container (in the host)
You can see to which folders in your machine are those folders synced at docker-compose.yml file, under volumes section of utils_img.

3. After those folders have been created, we can start our services knowing that those preconditions where accomplished.

The command we use is 

docker-compose $(DOCKER_COMPOSE_FILE_LIST) up -d --build

Again, docker-compose is the high level cli we use to manage our infrastructure, with a list of docker-compose.*.yml files containing the information docker-compose needs to build our multi container infraestructure.

docker-compose up tells docker-compose to start our containers based on those yaml files definitions

--build tells docker-compose to build those containers if they where not built yet ( in this specific flow we called make clean earlier, so docker-compose won't find any of our containers and will start the build process )

4. Once every container is started, we are going to fix any possible permissions issue to ensure apache and php can read and/or execute the files we granted access to inside docker-compose yaml files (see volumes section inside those files)

The command we use inside each container are highlighted below:

docker exec cak-apache **chown -R** ${WEB_USER}:${WEB_GROUP} ${APACHE_ROOT_DIR}/logs

docker exec cak-php **chown -R** ${WEB_USER}:${WEB_GROUP} ${PHP_LOG_DIR}

docker exec cak-apache **chown -R** ${WEB_USER}:${WEB_GROUP} ${PHP_APP_DIR}

docker exec cak-php **chown -R** ${WEB_USER}:${WEB_GROUP} ${PHP_APP_DIR}

docker exec cak-php **chmod 775 -R** ${PHP_APP_DIR}/includes/templates/cache

current environment variables are being loaded with the syntax `${ENVIRONMENT-VARIABLE-NAME} . You can check all those values in docker/.env and docker/.myenv files

