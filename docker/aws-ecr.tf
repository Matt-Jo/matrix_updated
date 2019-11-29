
resource "aws_ecr_repository" "payments_api" {
  name = "${var.chronous_payments_production_image}"
  provisioner "local-exec" {
  command = <<EOF
    eval $(aws ecr get-login --no-include-email)
    docker tag ${var.chronous_payments_production_image}:${var.chronous_payments_production_image_version} ${aws_ecr_repository.payments_api.repository_url}
    docker push ${aws_ecr_repository.payments_api.repository_url}
  EOF
  }
}

output "aws-ecr-repository-payments-api" {
  value = "${aws_ecr_repository.payments_api.repository_url}"
}
