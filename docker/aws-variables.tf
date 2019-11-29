## AWS settings credentials
#AWS_ACCESS_KEY_ID=
#AWS_SECRET_ACCESS_KEY=
## An AWS CLI profile defined at ~/.aws/config
#AWS_DEFAULT_PROFILE=default
#AWS_CLUSTER_NAME=cak-cluster-1
#AWS_LAUNCH_TYPE=EC2
#AWS_REGION_NAME=us-east-1
#AWS_EC2_CLUSTER_SIZE=1
#AWS_EC2_INSTANCE_TYPE=t2.micro
## Folder path to your installed aws cli
#AWS_CLI=
## AWS_ACCOUNT_ID is prepended to the base url of every cluster and image repository
#AWS_ACCOUNT_ID=
#AWS_AVAILABILITY_ZONE_1=
#AWS_AVAILABILITY_ZONE_2=
#AWS_SECURITY_GROUP_ID=
#AWS_LOGS_GROUP_NAME=cak-log-group


variable "project_name" {
    default = "cak" // COMPOSE_PROJECT_NAME
}

variable "aws_ec2_cluster" {
    default = "cak-cluster-1" // AWS_CLUSTER_NAME
}

variable "aws_ec2_bastion_cluster" {
    default = "bastion"
}

variable "aws_region" {
    default = "us-east-2" // AWS_REGION_NAME
    description = "AWS region"
}

variable "aws_launch_type" {
    default ="EC2" // AWS_LAUNCH_TYPE
    description = "Launch type for containers inside EC2 cluster"
}
variable "aws_ec2_cluster_type" {
    default = "t2.nano" // AWS_EC2_INSTANCE_TYPE
    description = "AWS EC2 cluster type"
}

variable "payments_api_exposed_port" {
    default = 80 // APACHE_EXPOSED_PORT
}

variable "db_password" {
    default = "pIMjqqSztIus1y7u" // MYSQL_PASSWORD
}

variable "vpc_cidr" {
    default = "10.2.0.0/16" // NETWORK_SUBNET
}

variable "vpc_private_subnets" {
    default = ["10.2.0.0/24", "10.2.1.0/24"]
}

variable "vpc_public_subnets" {
    default = ["10.2.2.0/24"]
}

variable "vpc_database_subnets" {
    default = ["10.2.3.0/24" , "10.2.4.0/24"]
}

variable "chronous_payments_production_image" {
    description = "Name and version of production container image"
    default = "chronous-payments-web-api"
}

variable "chronous_payments_production_image_version" {
    description = "Name and version of production container image"
    default = "1.0"
}
