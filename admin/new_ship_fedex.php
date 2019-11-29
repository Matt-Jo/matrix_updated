<?php
$oid = intval(strip_tags($_GET['oid']));
$act = strip_tags($_GET['action']);
$actionArr = array('new_ship', 'send_ship', 'print_label', 'print_2_warehouse', 'ship_label_options', 'update_order');

if (empty($oid) && $act != 'update_order') die("Invalid Order Number");

if (empty($act) || !in_array($act, $actionArr, true)) die("Invalid Action");

require_once('includes/application_top.php');

$dh = new disp_html;

require_once("includes/classes/package_tracking.php");
$pt = new package_tracking;

$url = !empty($_GET['url'])?trim(strip_tags($_GET['url'])):NULL;

function print2warehouse($tracknum) {
	// when ready to move to production, restore commented out PROD values below & comment DEV lines
	$station = '';
	if (isset($_SESSION['station'])) $station = $_SESSION['station'].'/';

	// PROD
	//$conn_id = ftp_connect('ftplabels.cablesandkits.com') or die("Couldn't connect to ftplabels.cablesandkits.com");
	$conn_id = ftp_connect('10.0.80.132') or die("Couldn't connect to 10.0.80.132");
	// DEV $conn_id = ftp_connect('10.20.10.5') or die("Couldn't connect to ftplabels.cablesandkits.com");
	$login_result = ftp_login($conn_id, 'CKUPLOAD', 'k1ts.789');
	ftp_pasv($conn_id, true);
	// PROD
	$upload = @ftp_put($conn_id, $station.$tracknum.'.png', DIR_FS_CATALOG.'admin/images/fedex/'.$tracknum.'.png', FTP_BINARY);
	// DEV $upload = ftp_put($conn_id,'/png/'.$tracknum.'.png', 'c:/dev/admin/images/fedex/'.$tracknum.'.png', FTP_BINARY);
	ftp_close($conn_id);
	return true;
}

switch ($act) {
	case 'ship_label_options':
		$order = new ck_sales_order($oid);
		$update = ['fedex_signature_type' => $_GET['signature_type'], 'fedex_bill_type' => $_GET['payment_type']];
		if ($order->get_shipping_method()['carrier'] == 'FedEx') $update['fedex_account_number'] = $_GET['account_number'];
		else $update['ups_account_number'] = $_GET['account_number'];
		$order->update($update);
		exit;
		break;
	case 'print_label':
		$dh->label($oid,$url,'');
		exit;
		break;
	case 'print_2_warehouse':
		$urlArr = explode('/', $url);
		$tracknum = str_replace(".png", "", strtolower(end($urlArr)));
		if (print2warehouse($tracknum)) {
			$msg = 'Label was sent to the warehouse.';
		}
		$dh->label($oid,$url,$msg);
		exit;
		break;
	case 'new_ship':
		$dh->close();
		$dh->new_ship($oid,$pt,strip_tags($_GET['addrerrs']));
		exit;
		break;
	case 'update_order':
		//do nothing - this is here so we can include this file from orders_new to auto print labels
		break;
	case 'send_ship':
		new_ship_fedex_send_ship($oid, $_GET['payee_account_num'], $_GET['bill_type'], $_GET['signature_type'], $_GET['print_label'], true);
		break;
	default:
		# 395
		$dh->close();
		echo "Invalid action";
		exit;
		break;
}

function new_ship_fedex_send_ship($oid, $payee_account_num, $bill_type, $signature_type, $print_label, $do_output) {
	$dh = new disp_html;
	$pt = new package_tracking;

	require_once(DIR_FS_CATALOG."includes/functions/fedex_webservices.php");

	$the_order = new ck_sales_order($oid);

	//MMD - first we figure out the service type
	# Shipping Method
	$shipping_method_id = $pt->pt_get_order_shipping_method($oid);
	$shipping_method_id = ($shipping_method_id > 17) ? 9 : $shipping_method_id; # ensure that id is Fedex
	$service_type = $pt->pt_get_original_shipper_code($shipping_method_id);

	//and then account for the saturday delivery scenario
	# Saturday delivery
	$saturday_delivery = false;
	if ($service_type == 'PRIORITY_OVERNIGHT_SAT') {
		$saturday_delivery = true;
		$service_type = 'PRIORITY_OVERNIGHT'; # 2 Priority Overnight
	}
	elseif ($service_type == 'FEDEX_2_DAY_SAT') {
		$saturday_delivery = true;
		$service_type = 'FEDEX_2_DAY'; # 2nd Day Air
	}

	//Now determine the origin address
	//by default the origin addess is null - meaning the function will use the office address
	$origin_address = null;
	if ($the_order->is('dropship')) {
		$origin_address = $the_order->get_addr();
		$origin_address->normalize_phone();
	}

	//Now we get the destination address
	$destination_address = $the_order->get_ship_address();
	$destination_address->normalize_phone();

	//Now we build the payment info
	$payment_info = array();
	$payment_info['country'] = 'US';
	$payment_info['account_number'] = $payee_account_num;
	switch ($bill_type) {
		case '1':
			$payment_info['type'] = 'SENDER';
			break;
		case '3':
			$payment_info['type'] = 'THIRD_PARTY';
			break;
		case '2':
		case '5':
			$payment_info['type'] = 'RECIPIENT';
			break;
	}
	//MMD - the odd case that we are definitely billing sender and the account number is not set
	if ($payment_info['type'] == 'SENDER' || empty($payment_info['account_number'])) {
		$payment_info['account_number'] = '285019516';
	}

	//MMD - this is a fix for debugging in dev - if the the account number is set to the store's account number
	// we use the constant since we may be in the development environment
	if ($payment_info['account_number'] == '285019516') {
		$payment_info['account_number'] = FEDEX_WS_ACCOUNT_NUMBER;
	}

	//prepare the signature option
	$signature_option = null;
	switch ($signature_type) {
		case '2':
			$signature_option = 'INDIRECT';
			break;
		case '3':
			$signature_option = 'DIRECT';
			break;
		case '4':
			$signature_option = 'ADULT';
			break;
		default:
			$signature_option = 'NO_SIGNATURE_REQUIRED';
			break;
	}

	//now we get the packages and loop through them generating labels
	$package_list = prepared_query::fetch('SELECT op.*, ot.orders_tracking_id FROM orders_packages op LEFT JOIN orders_tracking ot ON op.orders_packages_id = ot.orders_packages_id AND ot.void = 0 WHERE orders_id = :orders_id', cardinality::SET, [':orders_id' => $oid]);
	$package_count = 1;
	$total_packages = $pt->pt_get_order_pack_count($oid, FALSE);
	$master_tracking_number = null;
	$tracking_numbers = array();
	$error_array = array();
	foreach ($package_list as $package) {
		if ($package['void'] == '0') {
			$packageData = array();
			$packageData['weight'] = $package['scale_weight'];

			//calculate the dimensions
			$packageData['dimensions'] = array();
			$height = $package['order_package_height'];
			$width = $package['order_package_width'];
			$length = $package['order_package_length'];
			if ($package['package_type_id'] > 2) {
				$package_type = prepared_query::fetch('select * from package_type pt where pt.package_type_id = :package_type_id', cardinality::ROW, [':package_type_id' => $package['package_type_id']]);
				$height = $package_type['package_height'];
				$width = $package_type['package_width'];
				$length = $package_type['package_length'];
			}
			$packageData['dimensions']['height'] = $height > 1 ? $height : 3;
			$packageData['dimensions']['width'] = $width > 1 ? $width : 7;
			$packageData['dimensions']['length'] = $length > 1 ? $length : 10;

			try {
				$labelData = fws_generate_shipping_labels($service_type, $origin_address, $destination_address, $packageData, $payment_info, $master_tracking_number, $package_count, $total_packages, $saturday_delivery, $signature_option, '', $the_order->get_ref_po_number() );
			} catch (Exception $e) {
				$error_array[] = $e;
			}

			if (!empty($package['orders_tracking_id'])) {
				prepared_query::execute('UPDATE orders_tracking SET tracking_num = ?, shipping_method_id = ?, date_time_created = NOW(), cost = ? WHERE orders_tracking_id = ?', array(@$labelData['tracking_number'], $shipping_method_id, @$labelData['tracking_cost'], $package['orders_tracking_id']));
			}
			else {
				//update order tracking
				prepared_query::execute('INSERT INTO orders_tracking (orders_packages_id, tracking_num, shipping_method_id, date_time_created, cost) VALUES (?, ?, ?, now(), ?)', array($package['orders_packages_id'], @$labelData['tracking_number'], $shipping_method_id, @$labelData['tracking_cost']));
			}

			$tracking_numbers[] = @$labelData['tracking_number'];

			//update master tracking number
			if ($master_tracking_number == null) {
				$master_tracking_number = @$labelData['master_tracking'];
			}

			//print the label if told to do so
			if ($print_label && service_locator::get_config_service()->is_production()) {
				print2warehouse(@$labelData['tracking_number']);
			}

			$package_count++;
		}
	}

	if ($do_output) {
		if (!empty($error_array)) {
			$dh->close();
			echo "<div style='font-weight: bold;'>";
			echo $error_array[0]->getMessage();
			echo " %%error%%</div>";
			exit;
		}

		$sm = prepared_query::fetch('SELECT carrier, name FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::ROW, [':shipping_method_id' => $shipping_method_id]);
		$display_string = !empty($sm['carrier'])?$sm['carrier'].' - '.$sm['name']:$sm['name'];

			$dh->send_ship($tracking_numbers, $display_string);

			exit;
	}
	else {
		if (!empty($error_array)) {
			return false;
		}
		return true;
	}

}

# UI class
class disp_html {
	protected $selfurl;

	public function __construct() {
		$this->selfurl = $_SERVER['PHP_SELF'];
	}

	public static function close () {
		echo "<div style=\"text-align:right;\"><a href=\"{$_SERVER['PHP_SELF']}\" onclick=\"return closeNewFedexShip();\">[close]</a></div>". PHP_EOL;
		return true;
	}

	public static function label ($oid,$url,$msg='') {
		echo "<div style=\"width:400px;height:30px;text-align:right;vertical-align:middle;background-color:#d8d8d8;\">";
		echo "<a href=\"$url\" onclick=\"return dispPackLabel('$oid',this.href,'print_2_warehouse');\">[print to warehouse]</a>&nbsp;";
		echo "<a href=\"$url\" onclick=\"return printLabel('printLabel');\">[print locally]</a>&nbsp;";
		echo "<a href=\"$url\" onclick=\"return closeNewFedexShip();\">[close]</a>";
		echo "</div>". PHP_EOL;
		if ($msg) {
			echo "<div style=\"font-weight:bold;text-align:center;height:15px;background-color:#ff0;\">$msg</div>";
		}
		echo "<div id=\"printLabel\"><img src=\"$url\" width=\"400\" height=\"600\" alt=\"\" /></div";

		return true;
	}

	public static function new_ship ($oid,$pt,$addrerrs) {
		$pack_count = $pt->pt_get_order_pack_count($oid,0);

		if (empty($pack_count)) {
			echo "<div style=\"text-align:center;vertical-align:text-bottom;height:60px;font-weight:bold;\"><br /><br />Please, add packages before attempting to ship them!</div>";
			exit;
		}

		if (!empty($addrerrs)) {
			echo "<div style=\"text-align:center;vertical-align:text-bottom;height:60px;font-weight:bold;\"><br /><br />Please, correct shipping address errors!</div>";
			exit;
		}

		$orderArr = prepared_query::fetch('SELECT o.customers_fedex, o.fedex_bill_type, o.fedex_account_number, o.fedex_signature_type, ot.external_id FROM orders o JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = :shipping WHERE o.orders_id = :orders_id', cardinality::ROW, [':orders_id' => $oid, ':shipping' => 'ot_shipping']);
		$packages = $pt->pt_get_order_packages($oid);

		// arrays for signature services
		$signature_type = array();
		$signature_type['0'] = 'None Required';
		$signature_type['2'] = 'Anyone can sign (res only)';
		$signature_type['3'] = 'Signature Required';
		$signature_type['4'] = 'Adult Signature';

		$billTypeArr = array();
		if ($orderArr['customers_fedex'] != 'N/A' && $orderArr['customers_fedex']) {
			if ($orderArr['external_id'] == 9 || $orderArr['external_id'] == 15) { # FedEx Ground or Ground Int
				$billTypeArr[5] = 'Bill Recipient';
				} else {
				$billTypeArr[2] = 'Bill Recipient';
				}
				$billTypeArr[1] = 'Bill Sender (Prepaid)';
		}
		else {
			$billTypeArr[1] = 'Bill Sender (Prepaid)';
			if ($orderArr['external_id'] == 9 || $orderArr['external_id'] == 15) { # FedEx Ground or Ground Int
				$billTypeArr[5] = 'Bill Recipient';
			}
			else {
				$billTypeArr[2] = 'Bill Recipient';
			}
		}
		$billTypeArr[3] = 'Bill Third Party';

		echo "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"90%\" align=\"center\">".PHP_EOL;
		echo "<tr>".PHP_EOL;
		echo "<td class=\"lblhdrrightund\">Required Fields</td>".PHP_EOL;
		echo "<td>&nbsp;</td>".PHP_EOL;
		echo "</tr>".PHP_EOL;
		echo "<tr>".PHP_EOL;
		echo "<td class=\"pckgtxtright\">Payment Type:</td>".PHP_EOL;
		echo "<td class=\"pckgtxtright\"><input type=\"hidden\" id=\"bill_type\" value=\"".$orderArr['fedex_bill_type']."\"/>".PHP_EOL;
		echo $billTypeArr[$orderArr['fedex_bill_type']]."</td>".PHP_EOL;
		echo "</tr>".PHP_EOL;
		echo "<tr>".PHP_EOL;
		echo "<td class=\"pckgtxtright\">Account Number:</td>".PHP_EOL;
		echo "<td class=\"pckgtxtright\"><input id=\"payee_account_num\" type=\"hidden\" value=\"".$orderArr['fedex_account_number'] ."\" />". $orderArr['fedex_account_number'] ."</td>".PHP_EOL;
		echo "</tr>".PHP_EOL;
		echo "<tr>".PHP_EOL;
		echo "<td class=\"lblhdrrightund\">Optional Fields</td>".PHP_EOL;
		echo "<td>&nbsp;</td>".PHP_EOL;
		echo "</tr>".PHP_EOL;
		echo "<tr>".PHP_EOL;
		echo "<td class=\"pckgtxtright\">Signature Options:</td>".PHP_EOL;
		echo "<td class=\"pckgtxtright\"><input type=\"hidden\" id=\"signature_type\" value=\"".$orderArr['fedex_signature_type']."\"/>".PHP_EOL;
		echo $signature_type[$orderArr['fedex_signature_type']]."</td>".PHP_EOL;
		echo "</tr>".PHP_EOL;
		echo "<tr>".PHP_EOL;
		echo "<td colspan=\"2\">&nbsp;</td>".PHP_EOL;
		echo "</tr>".PHP_EOL;
		echo "<tr>".PHP_EOL;
		echo "<td colspan=\"2\">";
		(new disp_html)->package_list($oid,$packages,$pt);
		echo "</td>".PHP_EOL;
		echo "</tr>".PHP_EOL;
		echo "<tr>".PHP_EOL;
		echo "<td colspan=\"2\">&nbsp;</td>".PHP_EOL;
		echo "</tr>".PHP_EOL;
		echo "<tr>".PHP_EOL;
		echo "<td colspan=\"2\">";
		echo "<input id=\"print_label\" type=\"checkbox\" value=\"1\" checked=\"checked\" /><label class=\"pckgtxtright\" for=\"print_label\">&nbsp;Print Shipping Label to Warehouse</label>";
		echo "<div id=\"submitdiv\"><input type=\"button\" id=\"submitbutton\" value=\"Submit\" onclick=\"return sendNewFedexShip('{$oid}');\" /></div>";
		echo "</td>".PHP_EOL;
		echo "</tr>".PHP_EOL;
		echo "</table>".PHP_EOL;
		return true;
	}

	# 395
	public static function send_ship ($trckNumArr, $method) {
		$tracknumtext = "";
		if (is_array($trckNumArr) && count($trckNumArr) > 0) {
			echo "Thank you for your order! It has been processed and is shipping today. Your tracking information is below:".PHP_EOL.PHP_EOL;
			foreach ($trckNumArr as $unused => $val) {
				echo $method.': '.$val.PHP_EOL;
			}
			echo PHP_EOL;
			echo "Please let us know if we can help you with anything else!";
		}
		return true;
	}


	protected static function package_list ($oid,$ptMstrArr,$pt) {
		$rowcount = $numpackages = $totalpackageweight = 0;
		sort($ptMstrArr);
		$ordstatus = $pt->pt_get_order_status($oid);

		if (is_array($ptMstrArr) && count($ptMstrArr) > 0) {
			echo "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">".PHP_EOL;
			echo "<tr>".PHP_EOL;
			echo "<td class=\"rowhdr\" style=\"border-left:1px #000 solid;\">Package ID</td>".PHP_EOL;
			echo "<td class=\"rowhdr\">Size</td>".PHP_EOL;
			echo "<td class=\"rowhdr\">Weight (lbs)</td>".PHP_EOL;
			echo "</tr>".PHP_EOL;
			foreach ($ptMstrArr as $k => $ptArr) {
				$packageweight = 0;
				if (is_array($ptArr) && !$ptArr['void']) {

				$trackArr = $pt->pt_get_order_tracking ($ptArr['orders_packages_id']);
				if (!$trackArr[0]['tracking_num'] || $trackArr[0]['tracking_num'] == '') {
						$rowstyle = $rowcount % 2 == 0 ? "row1" : "row2";
					$packageweight = $pt->pt_pckg_weight($ptArr);
					echo "<tr>".PHP_EOL;
					echo "<td class=\"$rowstyle\" style=\"border-left:1px #000 solid;\">{$ptArr['orders_packages_id']}</td>".PHP_EOL;
					echo "<td class=\"$rowstyle\">{$ptArr['package_name']}</td>".PHP_EOL;
					echo "<td class=\"$rowstyle\">$packageweight</td>".PHP_EOL;
					echo "</tr>".PHP_EOL;
					$numpackages++;
					$totalpackageweight = $totalpackageweight + $packageweight;
					$rowcount++;
				}
				}
			}

			echo "</table>".PHP_EOL;
		}
	}


	public function __destruct () {
		return true;
	}
}

class debug {
	public function __construct () {
		return true;
	}

	public function debugLog ($str) {
		$debug = 0;
		if (!empty($debug)) {
			$logfile = $_ENV["TMP"]."/debug.log";
			$debugfile = fopen($logfile,"a");
			fwrite($debugfile,"$str\n");
			fclose($debugfile);
		}
		return true;
	}

	public function __destruct () {
		return true;
	}
}
?>
