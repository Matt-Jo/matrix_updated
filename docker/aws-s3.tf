
############################################
# S3 bucket to provision RDS instance
################################

resource "aws_s3_bucket" "xtrabackup" {
  bucket = "rds-access-s3-bucket"
  region = "${data.aws_region.current.name}"
}

resource "aws_s3_bucket_object" "xtrabackup" {
  bucket = "${aws_s3_bucket.xtrabackup.id}"
  key    = "data/db-backup.tar.gz"
  source = "data/db-backup.tar.gz"

  etag = "${filemd5("data/db-backup.tar.gz")}"
}
