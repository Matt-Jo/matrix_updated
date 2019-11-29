terraform {
  required_version  = "~> 0.12.3"

  required_providers {
    aws   = "~> 2.20.0"
    null  = "~> 2.1.2" 
    http  = "~> 1.1"
  }
}

provider "aws" {
  region = "${var.aws_region}"
}
