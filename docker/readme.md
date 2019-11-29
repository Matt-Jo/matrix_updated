# AWS ECS

# Production environment

This section contains information on what is needed to deploy a working chronous-payment api instance on top of AWS ECS

Roles needed:

* Infrastructure maintainer: Matt
* Production docker image maintainer: ?

**Basic Infrastructure details needed to setup a minimum viable infrastructure on EC2:**

1. **[1 EC2 instance](https://aws.amazon.com/ec2/instance-types/)** ("worker") responsible for running chronous-payments-web-api:1.0 image
    1. Choose an **"ECS-optimized Amazon Linux 2"** AMI to create your EC2 instance. See ["We recommend that you use the Amazon ECS-optimized Amazon Linux 2 AMI for your container instances"](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/ecs-optimized_AMI.html) \
    The ECS agent is in charge of running docker containers. If you use a different image you will have to configure it yourself.
1. **[1 ECR repository](https://docs.aws.amazon.com/AmazonECR/latest/userguide/Repositories.html)** as a private repository where to push production versions of chronous-payments-web-api
    1. Allow someone at the dev team to push new updates to the production docker image
1. Latest version of **a chronous-payment docker image** pushed to the ECR repository
1. **[1 AWS ECS task](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/task_definitions.html)**
    1. docker image url: the url of the ECR repository
    1. essential: TRUE
    1. port mappings between the worker EC2 instance port we want to expose to the private subnet, and port 80 at chronous-payments-web-api docker container
    1. memory and cpu limits
    1. with aws logs enabled
1. **[1 AWS ECS service](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/ecs_services.html)**
    1. launch type: "EC2"
    1. deployment_maximum_percent: 100
    1. deployment_minimum_healthy_percent: 0
    1. desired_count: 1
    1. cluster: the "worker" EC2 instance
    1. task definition: the previously mentioned ECS task
1. **1 private database subnet** with a cidr block like "10.2.3.0/24"
1. **1 private subnet** with a cidr block like "10.2.0.0/24"
1. **1 Security Group** to allow ingress to the HTTP port of chronous-payments-web-api
    1. attached to Matrix-oSc VPC
    1. assigned to the EC2 instance
    1. allow ingress from private subnet cidr block into port 80, protocol tcp, http
    1. allow egress from anywhere and from any port/protocol
1. **1 Security Group** to allow ingress to port 3306 of RDS database instance
    1. attached to Matrix-oSc VPC
    1. assigned to the RDS instance
    1. allow ingress from private subnet cidr block or from members of the previously created security group, into port 3306, from port 3306, protocol tcp
    1. allow egress from anywhere and from any port/protocol
1. If you want to ssh into the EC2 instance, from a security and stability point of view, an intermediary between the world and your EC2 instance open ports is desirable; a bastion aws instance, attached to the same VPN and private subnets.

----------

# Staging environment

## Intro

The deployment environment handles creation and destruction of production like environments on the cloud. The deployment environment has been used only on macos so far, but all the tools on which it depends are available on most linux distros

## Pre requisites

1. git
1. terraform
1. docker
1. ssh-keygen
1. a backup of a mysql instance containing paymentsdb database available at `docker/data/db-backup.tar.gz`
1. a docker image registered locally as `chronous-payments-web-api`

Temporarily, you can get the files mentioned above at https://drive.google.com/drive/folders/1d2rCWHwD8jPzq21TvX7R7Zq5HKefrXuz?usp=sharing
You can get also the docker image from a tar file located at the same url. You can register it locally by running `docker load < chronous-payments-web-api.tar`

## Initial Setup

1. `git clone --single-branch --branch php7 https://github.com/guille-mas/Matrix-oSc.git`
1. `cd Matric-oSc/docker`
1. get a backup of a mysql instance containing paymentsdb database available at `docker/data/db-backup.tar.gz`
1. get a distributable image of chronous-payment project, and run `docker load < file-containing-docker-image.tar`
1. double check `~/.aws/credentials` exists and contains valid credentials to deploy to AWS cloud
1. double check `docker/aws-variables.tf`
1. run `terraform init`

## Howto Deploy infrastructure

Deployment is only triggered if changes are approved first

1. run `terraform apply`
1. Review carefully terraform's plan and confirm if OK

## Howto Update infrastructure

1. run `terraform apply`
1. Review carefully terraform's plan and confirm if OK

## Howto deploy a new local docker image

1. run `terraform taint aws_launch_configuration.payments_api` `terraform taint aws_ecr_repository.payments_api`
1. run `terraform apply`
1. Review carefully terraform's plan and confirm if OK

## Howto Destroy infrastructure

You should not do this on production to avoid down times. Is it better to force a new blue/green deployment to introduce changes.

1. run `terraform destroy`. This command will only destroy the infrastructure managed by terraform.
1. Review carefully terraform's plan and confirm if OK

## Infrastructure state

After every update to the AWS cloud, made with terraform, the following files are created in your computer:

1. `.terraform/`
1. `terraform.tfstate`
1. `terraform.tfstate.backup`

Manage each environment ( testing and production ) on different folders, instead of changing environment variables to attempt update an infrastructure deployed into another region or account. Doing that will cause inconsistencies between the AWS infrastructure and the local terraform state mapping of that infrastructure, causing errors and inconsistencies across every workflow.

## Howto SSH into

1. Use "-A" for ssh key forwarding between you -> the bastion host -> the protected EC2 instance \
`ssh -A ec2-user@<bastion-public-ip>`
1. Once inside the bastion, ssh into the internal EC2 cluster running docker images \
`ssh ec2-user@<cak-cluster-1-private-ip>`

If you have troubles to get access into the bastion. Run `ssh-add -k aws-ssh` inside this folder. If you still experience issues, then your public ip might have changed and you will have to run `terraform apply` to whitelist yourself at the bastion instance.

----------

**List of automated infrastructure currently available:**

## EC2

1. `aws_ami.latest_ecs` : Latest ECS optimized AMI
1. `aws_key_pair.auth`  : An ssh key pair named aws-ssh that allows access into bastion server
1. `aws_ecs_cluster.cluster` : A logical group of EC2 instances
1. `module.bastion` : An EC2 instance with administration capabilities
1. `aws_launch_configuration.payments_api`: Launch configuration used by the autoscaling group. This component is part of the blue/green deployment feature
1. `aws_ecs_task_definition.payments_api` : A deployment task sets what docker image should be deployed and what resources should be assigned to the deployed container
1. `aws_ecs_service.payments_api_service` : An ECS Service is like a contract that specifies how a deployed task should look; into which cluster will be deployed, number of instances, and the number of healthy instances that are acceptable for the service. ECS ensures the requirements are accomplished.
1. deployed EC2 instances are of type t2.nano

## ECR

ECR is the private repository where we store our docker images

1. `aws_ecr_repository.payments_api` : The ECR repository where we store chronous-payments-api docker image

## IAM

Used by EC2 deployments

1. `aws_iam_role.payments_api_service_role` : iam role used to deploy chronous-payments-api from a private ECR repo into ECS
1. `aws_iam_role_policy_attachment.payments_api_service_role_attachment` : attach `arn:aws:iam::aws:policy/service-role/AmazonEC2ContainerServiceRole` to the iam role used to deploy chronous-payments-api from a private ECR repo into ECS

Used by CloudWatch

1. `aws_iam_role.ecs_ec2` : iam role used by cloudwatch
1. `aws_iam_role_policy_attachment.ecs_ec2_cloudwatch_role` : attach "arn:aws:iam::aws:policy/CloudWatchLogsFullAccess" to an iam role
1. `aws_iam_role_policy_attachment.ecs_ec2-attachment` : attachment used by cloudwatch_role
1. `aws_iam_instance_profile.ecs_ec2` : iam profile used by cloudwatch

Used by RDS

Following iam items allows RDS instance to access S3 buckets during provisioning of the RDS instances

1. `aws_iam_role.rds_s3_access_role`
1. `aws_iam_role.test`
1. `aws_iam_policy_attachment.test-attach`

## CloudWatch

1. `aws_cloudwatch_log_group.payments_api` : cloudwatch log group

## VPC

1. 1 vpc at us-east-2, covering 3 availability zones: us-east-2a , us-east-2b, us-east-2c
1. 1 public subnet ("10.2.2.0/24")
1. 2 private subnets (["10.2.0.0/24", "10.2.1.0/24"])
1. 2 database private subnets (["10.2.3.0/24" , "10.2.4.0/24"])
1. 1 nat gateway per subnet
1. 1 elastic load balancer provides access to internal ec2 instance HTTP port without exposing them
    1. 1 health check against /gettoken web api endpoint that check every 30 seconds if the containers are working. If not, a new deployment is triggered in the background to replace the non working container

## RDS

1. `aws_db_instance.cak` : RDS instance provisioned with payments and Matrix-oSc db
    1. mysql version 5.6.41
    1. provisioned from an S3 bucket
    1. instance type: db.t2.small

## S3 (used by RDS)

These components are used solely to provision an RDS instance from a mysql 5.6 backup made with percona-xtrabackup

1. `aws_s3_bucket.xtrabackup`
1. `aws_s3_bucket_object.xtrabackup`

## Security Groups

1. `sg_payments_api_internal` (module.sg_ec2) :
    1. Allows EC2 instance in private subnet to be pinged by the bastion instance only
    1. Allows SSH into the private EC2 instance from the bastion instance only
    1. Allows HTTP access from the elastic load balancer only
        1. For production this should not be allowed, since payments api should not be exposed to the outside
        2. Instead, Matrix-oSc should be allowed to access payments-api port 80, across private subnets
    1. Allow egress to any ip and from any port/protocol inside the private subnets

1. `sg_bastion_ssh_only` (module.sg_prod_ssh) :
    1. Allows SSH into public EC2 bastion, only from current owner´s public ip (ssh key pair is required as well as defined elsewhere)
    1. Allow egress to any ip and from any port/protocol

1. `sg_rds` (module.sg_rds) :
    1. Allow access through port 3306 from ec2 internal instances (currently only 1; payments-api)

1. `elb_http` (module.elb_http) :
    1. Allow access through port 80 of the elastic load balancer only from administrator's public IP
    1. Allow egress to any ip and from any port/protocol
