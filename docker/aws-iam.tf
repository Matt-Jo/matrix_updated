####################################
# ECR section
########################

# iam policy used to deploy chronous-payments-api from a private ECR repo into ECS
data "aws_iam_policy_document" "payments_api_service_policy_doc" {
  statement {
    actions = [
      "sts:AssumeRole",
    ]

    principals {
      type = "Service"

      identifiers = [
        "ecs.amazonaws.com",
      ]
    }
  }
}

# iam role used to deploy chronous-payments-api from a private ECR repo into ECS
resource "aws_iam_role" "payments_api_service_role" {
  name               = "payments_api_service_role"
  path               = "/"
  assume_role_policy = "${data.aws_iam_policy_document.payments_api_service_policy_doc.json}"
}

# attach `arn:aws:iam::aws:policy/service-role/AmazonEC2ContainerServiceRole` to the iam role 
# used to deploy chronous-payments-api from a private ECR repo into ECS
resource "aws_iam_role_policy_attachment" "payments_api_service_role_attachment" {
  role       = "${aws_iam_role.payments_api_service_role.name}"
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonEC2ContainerServiceRole"
}



####################################
# CloudWatch section
########################

data "aws_iam_policy_document" "ecs_ec2" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type = "Service"
      identifiers = ["ecs.amazonaws.com", "ec2.amazonaws.com"]
    }
    effect    = "Allow"

  }
}

# 
resource "aws_iam_role" "ecs_ec2" {
  name = "ecs_ec2"
  assume_role_policy = "${data.aws_iam_policy_document.ecs_ec2.json}"
}

# 
resource "aws_iam_role_policy_attachment" "ecs_ec2_cloudwatch_role" {
  role = "${aws_iam_role.ecs_ec2.name}"
  policy_arn = "arn:aws:iam::aws:policy/CloudWatchLogsFullAccess"
}

resource "aws_iam_role_policy_attachment" "ecs_ec2-attachment" {
  role = "${aws_iam_role.ecs_ec2.name}"
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonEC2ContainerServiceforEC2Role"
}

resource "aws_iam_instance_profile" "ecs_ec2" {
  name = "ecs_ec2"
  role = "${aws_iam_role.ecs_ec2.name}"
}









####################################
# RDS section
########################

resource "aws_iam_role" "rds_s3_access_role" {
    name = "rds_s3_import"
    assume_role_policy = <<EOF
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "",
      "Effect": "Allow",
      "Principal": {
        "Service": "rds.amazonaws.com"
      },
      "Action": "sts:AssumeRole"
    }
  ]
}
EOF
}
resource "aws_iam_policy" "test" {
  name   = "rds_s3_import"
  policy = <<POLICY
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:*"
            ],
            "Resource": [
                "${aws_s3_bucket.xtrabackup.arn}",
                "${aws_s3_bucket.xtrabackup.arn}/*"
            ]
        }
    ]
}
POLICY
}


resource "aws_iam_policy_attachment" "test-attach" {
    name = "rds_s3_import"
    roles = [
        "${aws_iam_role.rds_s3_access_role.name}"
    ]
    policy_arn = "${aws_iam_policy.test.arn}"
}
