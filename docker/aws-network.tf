
module "vpc" {
  source                        = "terraform-aws-modules/vpc/aws"
  version                       = "2.9.0"
  name                          = "cak-vpc"
  cidr                          = "${var.vpc_cidr}"
  azs                           = [
    "${var.aws_region}a", 
    "${var.aws_region}b",
    "${var.aws_region}c"
    ]
  private_subnets               = var.vpc_private_subnets
  public_subnets                = var.vpc_public_subnets
  database_subnets              = var.vpc_database_subnets
  enable_nat_gateway            = true
}


# elastic load balancer provides access to internal ec2 instance HTTP port without exposing them
resource "aws_elb" "web-elb" {
  name = "web-elb"
  security_groups = ["${module.elb_http.this_security_group_id}"]
  subnets = module.vpc.public_subnets
  cross_zone_load_balancing   = true
  health_check {
    healthy_threshold = 2
    unhealthy_threshold = 2
    timeout = 3
    interval = 30
    target = "HTTP:80/gettoken"
  }
  listener {
    lb_port = 80
    lb_protocol = "http"
    instance_port = "80"
    instance_protocol = "http"
  }

  depends_on = [module.vpc]
}


