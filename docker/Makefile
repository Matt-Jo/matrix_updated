include .env .myenv

export
OS := $(shell uname)

DOCKER_COMPOSE_FILE_LIST := -f docker-compose.yml

AWS_ECS_COMPOSE_FILE_LIST := -f docker-compose.aws-ecs.yml
AWS_ECS_PARAMS_FILE := --ecs-params ecs-params.yml

AWS_EC2_KEYPAIR := ${COMPOSE_PROJECT_NAME}-key-pair

## Useful to tag built images. Git tags might be better (using minor or major version changes to notify the need of rebuilding images)
GIT_CURRENT_HASH=$(shell git rev-parse HEAD)
GIT_EMAIL=$(shell git config user.email )

## Set of tags generated at runtime
## Current tags are: EnvironmentType, AppName, Version, Owner, ClusterName
## Example: AppName=cak, EnvironmentType=prod, Version=1.2, Owner:guilledevel@gmail.com, ClusterName:cak-cluster-1
## --tags='env=${CAK_ENVIRONMENT},commit=${GIT_CURRENT_HASH}'
DEPLOY_TAGS := "AppName=${COMPOSE_PROJECT_NAME},EnvironmentType=${CAK_ENVIRONMENT},Version=${GIT_CURRENT_HASH},Owner=${GIT_EMAIL},ClusterName=${AWS_CLUSTER_NAME}"


########################################################################
## Local environment:
########################################################################



## docker host provision pre required steps to run the local environment
## most probably you will run this command only once, before running `make build for the first time
init: setup self-ssl reload


setup: 
ifeq ($(OS),Darwin)  
## see: https://help.rubygems.org/discussions/problems/31074-timeout-error
	networksetup -setv6off Wi-Fi
	networksetup -setv6automatic Wi-Fi
endif

## Run automated tests
## Work in progress
tests:
## @todo: execute inside docker php container
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm php_img ${PHP_APP_DIR}/includes/engine/vendor/bin/phpunit --bootstrap .${PHP_APP_DIR}/kernel.php ${PHP_APP_DIR}/tests/aws_ses_mail_service_test.class.php

## Build all containers locally
start:
## Initialization of required folder structure
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm utils_img mkdir -p /cak-logs/apache /cak-logs/mysql /cak-logs/php /cak-vendor  /cak-data/database /cak-data/composer/cache /cak-templates/cache
## Build (if needed) and start the infraestructure
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) up -d --build
## Fix permissions inside apache and php containers
	docker exec -d ${COMPOSE_PROJECT_NAME}-apache chown -R ${WEB_USER}:${WEB_GROUP} ${APACHE_ROOT_DIR}/logs
	docker exec -d ${COMPOSE_PROJECT_NAME}-php chown -R ${WEB_USER}:${WEB_GROUP} ${PHP_LOG_DIR}
	docker exec -d ${COMPOSE_PROJECT_NAME}-apache chown -R ${WEB_USER}:${WEB_GROUP} ${PHP_APP_DIR}
	docker exec -d ${COMPOSE_PROJECT_NAME}-php chown -R ${WEB_USER}:${WEB_GROUP} ${PHP_APP_DIR}
	docker exec -d ${COMPOSE_PROJECT_NAME}-php chmod 775 -R ${PHP_APP_DIR}/includes/templates/cache
## Finally, output all containers logs together, prefixing each line by the name of the container where that line was created
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) logs --follow

build-prod-tarball:
## Pack source code in tarballs	
#	git archive --format=tar.gz --prefix=git-1.4.0/ v1.4.0 >git-1.4.0.tar.gz
## Pack application source code into a deployable tarball
# Compress the backup result
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm utils_img \
	tar --exclude='.git' \
		--exclude='./docker' \
		--exclude='./guille' \
		--exclude='./includes/engine/vendor' \
		-zvcf /code-tarball/code.tar.gz /source

## Stop all Docker containers
stop: 
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) stop

clean:
	-docker-compose $(DOCKER_COMPOSE_FILE_LIST) down --volumes
	-docker-compose $(DOCKER_COMPOSE_FILE_LIST) down --rmi local --remove-orphans
ifeq ($(CAK_ENVIRONMENT),dev)
	-docker volume prune
endif

## Clean files inside persistent docker volumes used by our containers
clean-containers:
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm utils_img rm -rf /cak-data/* /cak-logs/* /cak-vendor/*

## Stops services and wipes current mysql db
## DB will be rebuilt on next environment restart
clear-db: stop
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm utils_img rm -rf /cak-database/*

## Run composer service to install all vendors
## Useful when you have changes on composer.json
deps:
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) up -d --no-deps --build composer_img

watch-logs:
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) logs --follow

clear-logs:
	docker exec -it ${COMPOSE_PROJECT_NAME}-apache find ${APACHE_LOG_DIR} ! -type d -exec rm '{}' \;
	docker exec -it ${COMPOSE_PROJECT_NAME}-mysql find ${MYSQL_LOG_DIR} ! -type d -exec rm '{}' \;
	docker exec -it ${COMPOSE_PROJECT_NAME}-php find ${PHP_LOG_DIR} ! -type d -exec rm '{}' \;

## Start Letsencrypt certbot for automatic renewal of free SSL certificates
## Requires a public domain
## This flow might need some adjustments to work
auto-ssl:
	docker run -it --rm --name ${COMPOSE_PROJECT_NAME}-ssl-certbot-init \
	-v "${PWD}/data/certbot/conf:/etc/letsencrypt" \
	-v "${PWD}/data/certbot/lib/letsencrypt:/var/lib/letsencrypt" \
	-v "${PWD}/data/certbot/lib/letsencrypt-initialization:/var/log/letsencrypt" \
	certbot/certbot certonly -d ${APACHE_SUBDOMAIN}.cablesandkits.com


## Dummy certs for local development
## @todo: execute inside container 
self-ssl:
## See: https://letsencrypt.org/docs/certificates-for-localhost/
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm utils_img \
	sh -c \
	' \
	printf "[dn]\nCN=${APACHE_SUBDOMAIN}.cablesandkits.com\n[req]\ndistinguished_name = dn\n[EXT]\nsubjectAltName=DNS:${APACHE_SUBDOMAIN}.cablesandkits.com\nkeyUsage=digitalSignature\nextendedKeyUsage=serverAuth" > /tmp/cak-cert-info.txt && \
	openssl req -x509 -out /apache/server.crt -keyout /apache/server.key \
	-newkey rsa:2048 -nodes -sha256 \
	-subj "/CN=${APACHE_SUBDOMAIN}.cablesandkits.com" -extensions EXT -config /tmp/cak-cert-info.txt'

## Reload configuration
reload:
## refresh config.ini
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm utils_img \
	sh -c 'cat /php/config/webapp/config/config.ini.raw | envsubst > /php/config/webapp/config/config.ini'


build: clean setup start reload
## reload apache
	docker exec -it ${COMPOSE_PROJECT_NAME}-apache apachectl -k restart

## End of Local environment
########################################################################






########################################################################
## DEPLOY
########################################################################

## 1) Upload built images to AWS ECR Repo:
## Setup AWS Elastic Container Repository
## and upload every custom image to the cloud
deploy-setup-ecr:
## Upload local images
	@echo "Uploading local docker pre built images to ECR ..."
#	@echo "Uploading chronous-payments php-apache container ..."
#	ecs-cli push \
	--cluster-config ${AWS_CLUSTER_NAME} \
	--tags ${DEPLOY_TAGS} \
	chronous-payment_php_apache_img
##  1 repo per image. Then use tags to  specify the environment along with a version system. I like to use the git commit sha so I can easily relate an image back to a commit to help with debugging. something like: `$ENVIRONMENT:$SHA1`
	@echo "Uploading cak_apache_img ..."
	ecs-cli push \
	--cluster-config ${AWS_CLUSTER_NAME} \
	--tags ${DEPLOY_TAGS} \
	cak_apache_img
	@echo Uploading cak_php_img ...
	ecs-cli push \
	--cluster-config ${AWS_CLUSTER_NAME} \
	--tags ${DEPLOY_TAGS} \
	cak_php_img
	@echo "Uploading cak_utils_img ..."
	ecs-cli push \
	--cluster-config ${AWS_CLUSTER_NAME} \
	--tags ${DEPLOY_TAGS} \
	cak_utils_img
	@echo "Done!"


## 2) Creates a cluster:
## See https://docs.aws.amazon.com/AmazonECS/latest/developerguide/ecs-cli-tutorial-ec2.html
deploy-setup:
## Generate a script to run at the new EC2 instance once created
	docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm utils_img \
	sh -c 'cat /docker/aws/scripts/instance-setup.sh.raw | envsubst > /docker/aws/scripts/instance-setup.sh'
## Enable tagging (--tags):
	${AWS_CLI} ecs put-account-setting --name containerInstanceLongArnFormat --value enabled --region ${AWS_REGION_NAME}
	${AWS_CLI} ecs put-account-setting --name serviceLongArnFormat --value enabled --region ${AWS_REGION_NAME}
	${AWS_CLI} ecs put-account-setting --name taskLongArnFormat --value enabled --region ${AWS_REGION_NAME}
## Create a VPC and Subnets
	@echo "Creating ssh key pair for bastion container..."
	-chmod 664 ssh-key.pem
	${AWS_CLI} ec2 create-key-pair \
	--key-name ${AWS_EC2_KEYPAIR} \
	--query 'KeyMaterial' \
	--output text > ssh-key.pem
	chmod 400 ssh-key.pem
## Create cluster configuration
	@echo "Creating cluster configuration..."
	ecs-cli configure --cluster ${AWS_CLUSTER_NAME} \
	--default-launch-type ${AWS_LAUNCH_TYPE} \
	--region ${AWS_REGION_NAME} \
	--config-name ${AWS_CLUSTER_NAME}
## Create cluster
	@echo "Creating cluster..."
	ecs-cli up \
	--tags  ${DEPLOY_TAGS} \
	--keypair ${AWS_EC2_KEYPAIR} \
	--capability-iam \
	--size ${AWS_EC2_CLUSTER_SIZE} \
	--azs ${AWS_AVAILABILITY_ZONE_1},${AWS_AVAILABILITY_ZONE_2} \
	--cidr 0.0.0.0/0 \
	--port 80 \
	--instance-type ${AWS_EC2_INSTANCE_TYPE} \
	--launch-type ${AWS_LAUNCH_TYPE} \
	--cluster-config ${AWS_CLUSTER_NAME} \
	--extra-user-data ./aws/scripts/instance-setup.sh
## Get my current public ip
	@$(eval MY_PUBLIC_IP := $(shell curl -4 -s v4.ifconfig.co))
	@read -p "Enter Security Group created Id: " security_group_id ; \
	${AWS_CLI} ec2 authorize-security-group-ingress --group-id $$security_group_id --protocol tcp --port 22 --cidr ${MY_PUBLIC_IP}/32 ; \
	${AWS_CLI} ec2 authorize-security-group-ingress --group-id $$security_group_id --protocol tcp --port 443 --cidr 0.0.0.0/0 ; \
	echo "\nAdd this line to your .myenv file: \n\n AWS_SECURITY_GROUP_ID=$$security_group_id\n\n"

## Creates an automated deployment configuration that can be executed later with `make deploy-code`
## See https://docs.aws.amazon.com/codedeploy/latest/userguide/deployments-create-cli.html
## See https://docs.aws.amazon.com/codedeploy/latest/userguide/deployments-create-cli-github.html
## See https://docs.aws.amazon.com/codedeploy/latest/userguide/instances-ec2-configure.html
deploy-setup-codedeploy:
## 0) Populate appspec.yml with env vars and copy it to root folder
#	docker-compose $(DOCKER_COMPOSE_FILE_LIST) run --rm utils_img \
	sh -c 'cat /docker/appspec.raw.yml | envsubst > /source/appspec.yml'
## 1) create application "cak"
	${AWS_CLI} deploy create-application \
	--application-name ${COMPOSE_PROJECT_NAME} \
	--compute-platform Server
## 2) Create IAM role
	${AWS_CLI} iam create-role --role-name ${COMPOSE_PROJECT_NAME}-codedeploy-service-role --assume-role-policy-document file://code-deploy-iam-role.json
## Wait some seconds for the role to be ready
	sleep 7s
## Attach policy to role
	${AWS_CLI} iam attach-role-policy --role-name ${COMPOSE_PROJECT_NAME}-codedeploy-service-role --policy-arn arn:aws:iam::aws:policy/service-role/AWSCodeDeployRole
## 3) create deployment group "cak"
	${AWS_CLI} deploy create-deployment-group \
	--ec2-tag-filters Key=EnvironmentType,Type=KEY_AND_VALUE,Value=${CAK_ENVIRONMENT} \
	--application-name ${COMPOSE_PROJECT_NAME} \
	--deployment-group-name ${COMPOSE_PROJECT_NAME}-${CAK_ENVIRONMENT} \
	--service-role-arn arn:aws:iam::${AWS_ACCOUNT_ID}:role/${COMPOSE_PROJECT_NAME}-codedeploy-service-role \
	--deployment-config-name CodeDeployDefault.OneAtATime
## Create deployment configuration: (not needed, already specified default config rules on previous command with --deployment-config-name)
##	${AWS_CLI} deploy create-deployment-config --deployment-config-name ThreeQuartersHealthy --minimum-healthy-hosts type=FLEET_PERCENT,value=75


## Fetch specific Github version of our codebase and mount it into an AWS EBS volume
## Trigger an "in-place" deployment
deploy-code:
	${AWS_CLI} deploy create-deployment \
	--application-name ${COMPOSE_PROJECT_NAME} \
	--deployment-config-name CodeDeployDefault.OneAtATime \
	--deployment-group-name ${COMPOSE_PROJECT_NAME}-${CAK_ENVIRONMENT} \
	--description "Github Deployment POC" \
	--github-location repository=${GITHUB_REPO},commitId=${GIT_CURRENT_HASH}


## Start AWS EC2 containers
deploy-start:
	ecs-cli compose $(DOCKER_COMPOSE_FILE_LIST) $(AWS_ECS_COMPOSE_FILE_LIST) service up \
	--tags ${DEPLOY_TAGS} \
	--cluster-config ${AWS_CLUSTER_NAME} \
	--create-log-groups \
	--timeout 10
## View the Running Containers on a Cluster:
	ecs-cli ps


## Remove EC2 automated deploy configuration
deploy-clean-codedeploy:
	-${AWS_CLI} deploy delete-application --application-name ${COMPOSE_PROJECT_NAME}
	-${AWS_CLI} iam detach-role-policy --role-name ${COMPOSE_PROJECT_NAME}-codedeploy-service-role --policy-arn arn:aws:iam::aws:policy/service-role/AWSCodeDeployRole
	-${AWS_CLI} iam delete-role --role-name ${COMPOSE_PROJECT_NAME}-codedeploy-service-role
	-${AWS_CLI} deploy delete-deployment-group --application-name ${COMPOSE_PROJECT_NAME} --deployment-group-name ${COMPOSE_PROJECT_NAME}-${CAK_ENVIRONMENT}


## Remove EC2 cluster
deploy-clean:
	-chmod 664 ssh-key.pem
	-${AWS_CLI} ec2 delete-key-pair \
	--key-name ${AWS_EC2_KEYPAIR}
	-rm ssh-key.pem
## delete the service so that it stops the existing containers and does not try to run any more tasks
	-ecs-cli compose $(DOCKER_COMPOSE_FILE_LIST) $(AWS_ECS_COMPOSE_FILE_LIST) service rm \
	--cluster-config ${AWS_CLUSTER_NAME}
##  take down your cluster
	-ecs-cli down --force --cluster-config ${AWS_CLUSTER_NAME}
## Delete log group
	-${AWS_CLI} logs delete-log-group --log-group-name ${AWS_LOGS_GROUP_NAME}


## Upload Custom images to AWS ECR
deploy-clean-ecr:
## Delete EC Repository and its images...
	$(info Removing Elastic Container Repositories and images (ECR) ... )
	-${AWS_CLI} ecr delete-repository --force --repository-name cak_apache_img
	-${AWS_CLI} ecr delete-repository --force --repository-name cak_php_img
	-${AWS_CLI} ecr delete-repository --force --repository-name cak_utils_img
#	-${AWS_CLI} ecr delete-repository --force --repository-name chronous-payment_php_apache_img


## Updates the CIDR block to allow ssh access from my current public IP
fix-ssh:
	@$(eval MY_PUBLIC_IP := $(shell curl -4 -s v4.ifconfig.co))
	${AWS_CLI} ec2 update-security-group-rule-descriptions-ingress --group-id ${AWS_SECURITY_GROUP_ID} --ip-permissions '[{"IpProtocol": "tcp", "FromPort": 22, "ToPort": 22, "IpRanges": [{"CidrIp": "${MY_PUBLIC_IP}/32", "Description": "SSH access from ${GIT_EMAIL} personal computer"}]}]'

##	END OF DEPLOY SECTION
################################################################


################################################################
##	AWS - TERRAFORM
################################################################

aws-fix-ssh:
# setup ssh key
	ssh-add -k aws-ssh

aws-backup-local-db:
	docker run --rm -it \
		--network="host" \
		-v "${PWD}/data/database:${MYSQL_DATA_DIR}" \
		-v "${PWD}/data/db-backup:/xtrabackup_backupfiles" \
		perconalab/percona-xtrabackup \
		--backup \
		--host=0.0.0.0 \
		--user=${MYSQL_ROOT_USER} \
		--password=${MYSQL_ROOT_PASSWORD} \
		--verbose
# Compress the backup result
	tar -zcvf data/db-backup.tar.gz data/db-backup




