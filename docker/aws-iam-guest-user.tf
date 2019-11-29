
data "aws_iam_policy_document" "ec2_guest" {
  statement {
    actions = ["ec2:Describe*"]
    resources = ["*"]
  }
}

resource "aws_iam_policy" "ec2_guest" {
 name = "ec2-read-only"
 policy = "${data.aws_iam_policy_document.ec2_guest.json}"
}

resource "aws_iam_user_policy_attachment" "test-attach" {
 user = "${aws_iam_user.ec2_guest.id}"
 policy_arn = "${aws_iam_policy.ec2_guest.arn}"
}

resource "aws_iam_user" "ec2_guest" {
  name = "guest"
  force_destroy = true
}

resource "aws_iam_account_password_policy" "strict" {
  minimum_password_length        = 8
  require_lowercase_characters   = true
  require_numbers                = true
  require_uppercase_characters   = true
  require_symbols                = true
  allow_users_to_change_password = true
}

output "guest_user" {
 value = "${aws_iam_user.ec2_guest}"
}