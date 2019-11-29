
resource "aws_cloudwatch_log_group" "payments_api" {
  name              = "payments_api"
  retention_in_days = 1
}


