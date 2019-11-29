<?php
// this could just as easily be a separate, non-abstract class, but it makes more sense to access these properties and methods from the class we're using to figure our rates & etc
abstract class shipit {
	private static $sameday_hour = 20; // if we receive the order before 8PM, we'll ship it same day
	private static $earliest_hour = 9; // if we need to ship it at a later date, this is the earliest on that day we can know it will be handled

	private static $ship_time; // we can store our figured time here, so we can use it to display in any format
	public static $today; // if we want to set "today", this is mostly for testing purposes

	public static function reasonable_ship_time($time=NULL) { // allow passing in an arbitrary time, specifically useful for leadtimes
		$result = (object) array('ship_time' => NULL, 'saturday_delivery' => FALSE);

		empty($time)||!($time instanceof DateTime)?$time = new DateTime():NULL; // if we weren't passed in a time object, init to now
		$wday = $time->format('N'); // 1 (monday) thru 7 (sunday)
		$hour = $time->format('H'); // 24 hour time

		// we account for exactness of time in our shipping process, i.e. if we receive an order at 7:59:59 PM, then we may process it after 8PM on the same day

		if ($wday >= 1 && $wday <= 5 && $hour < self::$sameday_hour) { // monday thru friday, we can get it out same day if we receive it before our time limit
			//$time->add(new DateInterval('PT5M')); // add 5 minutes, probably no appreciable difference here
			$result->ship_time = $time; // don't pre-format it, just pass it back and we can format it later

			// thursday or friday mean that saturday delivery is an option
			if ($wday >= 4) $result->saturday_delivery = TRUE;
		}
		elseif ($wday >= 1 && $wday <= 4) { // monday thru thursday, it can go out first thing tomorrow morning
			$time->add(new DateInterval('P1D')); // set to tomorrow
			$time->setTime(self::$earliest_hour, 0); // set to earliest time on that day it would be handled
			$result->ship_time = $time;

			// if it's ultimately shipping out on thursday or friday, saturday delivery is an option
			if ($wday >= 3) $result->saturday_delivery = TRUE;
		}
		elseif ($wday >= 5) { // friday thru sunday
			$time->add(new DateInterval('P'.(8-$wday).'D')); // set to monday
			$time->setTime(self::$earliest_hour, 0); // set to earliest time on that day it would be handled
			$result->ship_time = $time;
		}

		self::$ship_time = $time;

		return $result;
	}

	public static function display_ship_time($format=NULL) {
		if (empty(self::$ship_time)) self::reasonable_ship_time();

		if ($format) return self::$ship_time->format($format);

		$today = !empty(self::$today)?self::$today:new DateTime();

		// if we didn't pass in a format, use our default format
		if (self::$ship_time->format('Y m d') === $today->format('Y m d')) return 'Today'; // if it's shipping today, say today
		elseif (self::$ship_time->format('N') > $today->format('N')) return self::$ship_time->format('l'); // if it's shipping later this week, display the day-of-week name
		else return self::$ship_time->format('M d'); // Jan-Dec, two digit day
	}

	//--------------------------------------------------------------------------------------------------------------------------

	public static $box_tare_weight = 0.8; // this is the average weight an empty box will add to the total package weight
	public static $box_tare_factor = 0.08; // this is the average error factor to expect with a figured shipping weight
	public static $max_package_weight = 50; // if total weight exceeds this amount, we must split into multiple boxes

	private static $total_weight; // calculated product weight plus our figured tare weight
	private static $total_packages; // the total number of packages that we must ship
	private static $package_weight; // the weight in each package

	public static function build_packages($total_weight) {
		self::$total_weight = $total_weight;
		// add the greater of the base tare weight or the tare factor on the box weight
		if (self::$box_tare_weight >= self::$total_weight * self::$box_tare_factor) self::$total_weight += self::$box_tare_weight;
		else self::$total_weight += self::$total_weight * self::$box_tare_factor;

		if (self::$total_weight > self::$max_package_weight) { // split into many boxes
			self::$total_packages = ceil(self::$total_weight/self::$max_package_weight);
			self::$package_weight = self::$total_weight/self::$total_packages;
		}
		else {
			self::$total_packages = 1;
			self::$package_weight = self::$total_weight;
		}

		return array('package_weight' => self::$package_weight, 'total_packages' => self::$total_packages);
	}
}
?>