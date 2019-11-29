

# see https://hands-on.cloud/terraform-recipe-managing-auto-scaling-groups-and-load-balancers/
resource "aws_autoscaling_group" "payments_api" {
  # Force a redeployment when launch configuration changes.
  # This will reset the desired capacity if it was changed due to
  # autoscaling events.
  name = "${aws_launch_configuration.payments_api.name}-asg"
  vpc_zone_identifier  = module.vpc.public_subnets
  launch_configuration = "${aws_launch_configuration.payments_api.name}"

  desired_capacity = 1
  min_size         = 1
  max_size         = 1

  health_check_type    = "ELB"
  load_balancers= [
    "${aws_elb.web-elb.id}"
  ]

  enabled_metrics = [
    "GroupMinSize",
    "GroupMaxSize",
    "GroupDesiredCapacity",
    "GroupInServiceInstances",
    "GroupTotalInstances"
  ]

  metrics_granularity="1Minute"

  # Required to redeploy without an outage.
  lifecycle {
    create_before_destroy = true
  }

  depends_on = [
      "aws_elb.web-elb"
  ]
}


resource "aws_autoscaling_policy" "payments_api_policy_up" {
  name = "web_policy_up"
  scaling_adjustment = 1
  adjustment_type = "ChangeInCapacity"
  cooldown = 300
  autoscaling_group_name = "${aws_autoscaling_group.payments_api.name}"
}


resource "aws_cloudwatch_metric_alarm" "payments_api_cpu_alarm_up" {
  alarm_name = "web_cpu_alarm_up"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  evaluation_periods = "2"
  metric_name = "CPUUtilization"
  namespace = "AWS/EC2"
  period = "120"
  statistic = "Average"
  threshold = "80"

  dimensions = {
    AutoScalingGroupName = "${aws_autoscaling_group.payments_api.name}"
  }

  alarm_description = "This metric monitor EC2 instance CPU utilization"
  alarm_actions = ["${aws_autoscaling_policy.payments_api_policy_up.arn}"]
}

