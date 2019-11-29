<?php

class api_after_ship extends ck_master_api {
	private static $key = '4d39f7be-2b2a-427a-add2-e7f75dbf2333';

	public static function detect_courier_by_tracking ($tracking_number) {
		$courier = new AfterShip\Couriers(self::$key);
		return $courier->detect($tracking_number);
	}

	public static function create_tracking (Array $data) {
		// Expected Data: Order Id and Tracking Number and Courier
		$tracking = new AfterShip\Trackings(self::$key);
		$tracking_info = ['slug' => strtolower($data['slug']), 'customer_name' => $data['customer_name'], 'emails' => $data['emails'], 'order_id' => $data['order_id']];
		return $tracking->create($data['tracking_number'], $tracking_info);
	}

	public static function delete_tracking (Array $data) {
		// Expected Data: Tracking Number and Courier as slug
		$tracking = new AfterShip\Trackings(self::$key);
		$tracking->delete(strtolower($data['slug']), $data['tracking_number']);
	}

	public static function get_couriers () {
		$couriers = new AfterShip\Couriers(self::$key);
		return $couriers->get();
	}
}

class CKAfterShipApiException extends CKApiException {
}
?>