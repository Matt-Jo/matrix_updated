
# See https://letslearndevops.com/2018/08/23/terraform-get-latest-centos-ami/
data "aws_ami" "latest_ecs" {
  most_recent = true

  owners = ["amazon"]

  filter {
    name = "name"
    values = [
      "amzn2-ami-ecs-hvm-*-x86_64-ebs"
    ]
  }

  filter {
    name = "owner-alias"

    values = [
      "amazon",
    ]
  }
}


resource "aws_key_pair" "auth" {
  key_name   = "aws-ssh"
  public_key = "${file("./aws-ssh.pub")}"
}


resource "aws_ecs_cluster" "cluster" {
  name = "${var.aws_ec2_cluster}"
}

module "bastion" {
  source                        = "terraform-aws-modules/ec2-instance/aws"
  version                       = "2.6.0"
  instance_type                 = "${var.aws_ec2_cluster_type}"
  name                          = "${var.aws_ec2_bastion_cluster}"
  ami                           = "${data.aws_ami.latest_ecs.id}"
  key_name                      = "${aws_key_pair.auth.key_name}"
  associate_public_ip_address   = true
  subnet_ids                    = module.vpc.public_subnets
  instance_count                = 1
  vpc_security_group_ids        = ["${module.sg_prod_ssh.this_security_group_id}"]
  monitoring                    = true
}



resource "null_resource" "pre-flight" {
  provisioner "local-exec" {
    command = <<EOF
      # Generate a script to run at the new EC2 instance once created
	    docker-compose -f docker-compose.yml run --rm utils_img \
	      sh -c 'cat /docker/aws/scripts/instance-setup.sh.raw | envsubst > /docker/aws/scripts/instance-setup.sh'
      # Enable tagging (--tags):
	    aws ecs put-account-setting --name containerInstanceLongArnFormat --value enabled
	    aws ecs put-account-setting --name serviceLongArnFormat --value enabled
	    aws ecs put-account-setting --name taskLongArnFormat --value enabled
  EOF
  }
}


# See https://medium.com/@endofcake/using-terraform-for-zero-downtime-updates-of-an-auto-scaling-group-in-aws-60faca582664
resource "aws_launch_configuration" "payments_api" {
  name_prefix          = "${var.aws_ec2_cluster}"
  image_id             = "${data.aws_ami.latest_ecs.id}"
  iam_instance_profile = "${aws_iam_instance_profile.ecs_ec2.name}"
  security_groups      = ["${module.sg_ec2.this_security_group_id}"]
  user_data            = templatefile("${path.module}/user_data.sh",{ cluster_name = "${var.aws_ec2_cluster}" })
  instance_type        = "${var.aws_ec2_cluster_type}"
  key_name             = "${aws_key_pair.auth.key_name}"

  lifecycle {
    # blue/green deployments by creating a new launch configuration before destroying the previous
    create_before_destroy = true
  }
}


data "aws_ecs_task_definition" "payments_api" {
  task_definition = "${aws_ecs_task_definition.payments_api.arn}"
}


resource "aws_ecs_task_definition" "payments_api" {
    family = "chronous-payment"

    container_definitions = <<DEFINITION
[
  {
    "name": "payments_api",
    "image": "${aws_ecr_repository.payments_api.repository_url}",
    "essential": true,
    "portMappings": [
      {
        "containerPort": ${var.payments_api_exposed_port},
        "hostPort": ${var.payments_api_exposed_port}
      }
    ],
    "memory": 128,
    "cpu": 0,
    "logConfiguration": {
      "logDriver": "awslogs",
      "options": {
        "awslogs-region": "${data.aws_region.current.name}",
        "awslogs-group": "payments_api",
        "awslogs-stream-prefix": "cak-ecs"
      }
    }
  }
]
DEFINITION

    depends_on      = [
      "aws_ecr_repository.payments_api"
      ]
}

data "aws_availability_zones" "available" {}


resource "aws_ecs_service" "payments_api_service" {
    name            = "payments_api-service"
    cluster         = "${var.aws_ec2_cluster}"
    task_definition = "${aws_ecs_task_definition.payments_api.arn}"
    launch_type     = "${var.aws_launch_type}"
    desired_count = 1

    deployment_maximum_percent = 100
    deployment_minimum_healthy_percent = 0
}


output "aws_ecs_available_availability_zones" {
  value = data.aws_availability_zones.available.names
}

output "elb_dns" {
  value = "${aws_elb.web-elb.dns_name}"
}

output "bastion_public_ip" {
  value = element(module.bastion.public_ip,0)
}
