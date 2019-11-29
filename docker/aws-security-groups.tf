
# Internal EC2 instance running docker containers
module "sg_ec2" {
  source      = "terraform-aws-modules/security-group/aws"
  version     = "3.1.0"
  name        = "sg_payments_api_internal"
  description = "EC2 payments api containers security group"
  vpc_id      = "${module.vpc.vpc_id}"


  ingress_with_cidr_blocks = [
    {
      # ping does not use ports cause its based on icmp
      from_port   = -1
      to_port     = -1
      protocol    = "icmp" 
      cidr_blocks = "${element(module.bastion.private_ip,0)}/32" 
      description = "allow ping from bastion"
    }
    ,
    {
      from_port   = 22
      to_port     = 22
      protocol    = "tcp"
      cidr_blocks = "${element(module.bastion.private_ip,0)}/32"
      description = "allow ssh ingress from bastion"
    }
  ]

  ingress_with_source_security_group_id = [
    {
      from_port   = 80
      to_port     = 80
      protocol    = "tcp"
      description = "allow http ingress through load balancer"
      source_security_group_id = "${module.elb_http.this_security_group_id}"
    }
  ]

  egress_rules        = ["all-all"]
}


# Bastion EC2 instance with SSH access
module "sg_prod_ssh" {
  source      = "terraform-aws-modules/security-group/aws"
  version     = "3.1.0"
  name        = "sg_bastion_ssh_only"
  description = "Allows SSH access"
  vpc_id      = "${module.vpc.vpc_id}"

  ingress_cidr_blocks = ["${chomp(data.http.owner_public_ip.body)}/32"]
  ingress_rules = ["ssh-tcp"]

  egress_with_cidr_blocks = [{
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = "0.0.0.0/0"
  }]
}

module "sg_rds" {
  source      = "terraform-aws-modules/security-group/aws"
  version     = "3.1.0"
  name        = "sg_rds"
  description = "Allow RDS inbound connections"
  vpc_id      = "${module.vpc.vpc_id}"

  egress_cidr_blocks  = ["0.0.0.0/0"]
  egress_rules        = ["all-all"]
  
  ingress_with_source_security_group_id = [
    {
      from_port   = 3306
      to_port     = 3306
      protocol    = "tcp"
      description = "allow connection from ec2 internal instance"
      source_security_group_id = "${module.sg_ec2.this_security_group_id}"
    }
  ]
}


module "elb_http" {
  source      = "terraform-aws-modules/security-group/aws"
  version     = "3.1.0"
  name        = "elb_http"
  description = "Allow HTTP traffic to instances through Elastic Load Balancer"
  vpc_id      = "${module.vpc.vpc_id}"

  ingress_cidr_blocks = ["${chomp(data.http.owner_public_ip.body)}/32"]
  ingress_rules       = ["http-80-tcp"]
  egress_rules        = ["all-all"]
}


data "http" "owner_public_ip" {
  url = "http://ipv4.icanhazip.com"
}

output "owner_public_ip" {
  value = "${chomp(data.http.owner_public_ip.body)}"
}

