
data "aws_region" "current" {}



# mysql db with name paymentservicedb
# provisioned on an AWS RDS instance
resource "aws_db_instance" "cak" {
  allocated_storage           = 20
  engine                      = "mysql"
  engine_version              = "5.6.41"
  auto_minor_version_upgrade  = true
  # import from S3 bucket doesnt work on instances smaller than "db.t2.small"
  instance_class              = "db.t2.small"
  name                        = "db"
  username                    = "dev"
  password                    = "${var.db_password}"
  skip_final_snapshot         = true
  vpc_security_group_ids      = ["${module.sg_rds.this_security_group_id}"]
  db_subnet_group_name        = "${module.vpc.database_subnet_group}"

  s3_import {
      source_engine = "mysql"
      source_engine_version = "5.6" # older version supported by aws
      bucket_name = "${aws_s3_bucket.xtrabackup.bucket}"
      bucket_prefix = "${aws_s3_bucket_object.xtrabackup.key}"
      ingestion_role = "${aws_iam_role.rds_s3_access_role.arn}"
  }

  depends_on = [
    "aws_iam_role.rds_s3_access_role", 
    "aws_s3_bucket.xtrabackup"
    ]
}

output "rds_endpoint" {
  value = "${aws_db_instance.cak.endpoint}"
}
