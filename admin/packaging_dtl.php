<?php
require_once('includes/application_top.php');
$act = strip_tags($_GET['action']);

if (isset($act)) {
	$oid = intval(strip_tags($_GET['oid']));
	require_once('includes/application_top.php');
	require_once("../includes/classes/shipping_methods.php");
	$sm = new shipping_methods;
	require_once("includes/classes/package_tracking.php");
	$pt = new package_tracking;
	$disp_pack = new display_packages;

	$updateArr = array();
	$packagesid = intval(strip_tags($_GET['pckgid']));

	# Used for package add/update
	$updateArr[$packagesid]['package_type_id'] = intval(strip_tags(@$_GET['package_type_id'])) > 0 ? intval(strip_tags($_GET['package_type_id'])) : 0;
	$updateArr[$packagesid]['order_package_length'] = floatval(strip_tags(@$_GET['order_package_length'])) > 0 ? floatval(strip_tags($_GET['order_package_length'])) : 0;
	$updateArr[$packagesid]['order_package_width'] = floatval(strip_tags(@$_GET['order_package_width'])) > 0 ? floatval(strip_tags($_GET['order_package_width'])) : 0;
	$updateArr[$packagesid]['order_package_height'] = floatval(strip_tags(@$_GET['order_package_height'])) > 0 ? floatval(strip_tags($_GET['order_package_height'])) : 0;
	$updateArr[$packagesid]['scale_weight'] = floatval(strip_tags(@$_GET['scale_weight'])) > 0 ? floatval(strip_tags($_GET['scale_weight'])) : 0;

	# 375 - default order weight
	//$default_order_weight = $pt->pt_get_from_order($oid, 'orders_weight');
	//MMD - we want to calculate the weight, not pull it off the orders table
	$order = new ck_sales_order($oid);
	$default_order_weight = $order->get_estimated_shipped_weight();
	//$default_order_weight = ($default_order_weight < 1) ? 1 : floor($default_order_weight);
	//MMD - 051210 - changing this section to round up rather than down
	$default_order_weight = ($default_order_weight < 1) ? 1 : ceil($default_order_weight);

	switch ($act) {
		# 375
		case 'quick_add_package':
			$updateArr[$packagesid]['package_type_id'] = 1000;
			$updateArr[$packagesid]['scale_weight'] = $default_order_weight;
			$pt->updateOrderPackage ($oid,$packagesid,$updateArr,$act);
			$ptMstrArr = $pt->pt_get_order_packages($oid);
			$disp_pack->dispPackageList ($oid,$ptMstrArr,$sm,$pt);
			exit;
			break;
		case 'add_package':
			# 375
			$numpackages = (intval(strip_tags($_GET['num_packages'])) > 0 && intval(strip_tags($_GET['num_packages'])) < 100) ? intval(strip_tags($_GET['num_packages'])) : 1;
			for ($n = 1;$n <= $numpackages; $n++) {
				$pt->updateOrderPackage($oid, $packagesid, $updateArr, $act);
			}
			$ptMstrArr = $pt->pt_get_order_packages($oid);
			$disp_pack->dispPackageList($oid, $ptMstrArr, $sm, $pt);
			exit;
			break;
		case 'update_package':
			$updateArr[$packagesid]['orders_tracking_id'] = intval(strip_tags($_GET['orders_tracking_id']));
			$updateArr[$packagesid]['tracking_num'] = strip_tags(str_replace(' ', '', $_GET['tracking_num']));
			$updateArr[$packagesid]['shipping_method_id'] = intval(strip_tags($_GET['shipping_method_id']));
			$updateArr[$packagesid]['cost'] = floatval(strip_tags(trim($_GET['cost'], '$USD '))) > 0 ? floatval(strip_tags(trim($_GET['cost'], '$USD '))) : 0;
			$pt->updateOrderPackage ($oid,$packagesid,$updateArr,$act);
			$ptMstrArr = $pt->pt_get_order_packages($oid);
			$disp_pack->dispPackageList ($oid,$ptMstrArr,$sm,$pt);
			exit;
			break;
		case 'delete_packages':
			$pt->updateOrderPackage ($oid,$packagesid,$updateArr,$act);
			$ptMstrArr = $pt->pt_get_order_packages($oid);
			$disp_pack->dispPackageList ($oid,$ptMstrArr,$sm,$pt);
			exit;
			break;
		case 'void_shipment':
			# Must pass order tracking id to be able to void it
			$updateArr[$packagesid]['orders_tracking_id'] = intval(strip_tags($_GET['ordtrackid']));

			# Label cancelation here!!!
			$pt->cancelFedexShipment($oid,$updateArr[$packagesid]['orders_tracking_id']);

			$pt->updateOrderPackage ($oid,$packagesid,$updateArr,$act);
			$ptMstrArr = $pt->pt_get_order_packages($oid);
			$disp_pack->dispPackageList ($oid,$ptMstrArr,$sm,$pt);
			exit;
			break;
		case 'void_package':
			# Must pass order tracking id to be able to void it
			$updateArr[$packagesid]['orders_tracking_id'] = intval(strip_tags($_GET['ordtrackid']));
			$pt->updateOrderPackage ($oid,$packagesid,$updateArr,$act);
			$ptMstrArr = $pt->pt_get_order_packages($oid);
			$disp_pack->dispPackageList ($oid,$ptMstrArr,$sm,$pt);
			exit;
			break;
		case 'package_details':
			$ptMstrArr = $pt->pt_get_order_packages($oid);
			$packship = strip_tags($_GET['packship']);
			$disp_pack->dispEditPackage ($oid,$packagesid,$sm,$pt,$ptMstrArr,$packship);
			exit;
			break;
		case 'package_list':
			$ptMstrArr = $pt->pt_get_order_packages($oid);
			$disp_pack->dispPackageList ($oid,$ptMstrArr,$sm,$pt);
			exit;
			break;
		case 'update_ups_tracking':
			$note = "First off, thank you for shopping with CablesAndKits! We just wanted to let you know that your order has shipped.\n\nYour tracking numbers(s):";
			$tracking_numbers = [];
			$imported_address_change = NULL;
			$imported_address_change_message = NULL;

			$tracking = prepared_query::fetch('SELECT op.void, utu.orders_id, op.orders_packages_id, utu.tracking_number, utu.voided, op.scale_weight, o.orders_weight, o.delivery_name AS original_attention, o.delivery_street_address AS original_address1, o.delivery_suburb AS original_address2, o.delivery_city AS original_city, o.delivery_postcode AS original_zipcode, o.delivery_state AS original_state, utu.total_weight, utu.cost, utu.company_name AS imported_company_name, utu.attention AS imported_attention, utu.address1 AS imported_address1, utu.address2 AS imported_address2, utu.address3 AS imported_address3, utu.city AS imported_city, utu. state AS imported_state, utu.zipcode AS imported_zipcode, ot.external_id, utu.service_type FROM orders o JOIN orders_packages op ON o.orders_id = op.orders_id JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = \'ot_shipping\' JOIN ck_ups_tracking_update utu ON op.orders_packages_id = utu.orders_packages_id OR o.orders_id = utu.orders_id LEFT JOIN ck_ups_tracking_update utu0 ON (utu.orders_packages_id = utu0.orders_packages_id OR o.orders_id = utu.orders_id) AND utu.ups_tracking_update_id < utu0.ups_tracking_update_id WHERE o.orders_id = :orders_id AND utu0.ups_tracking_update_id IS NULL', cardinality::SET, [':orders_id' => $oid]);

			foreach ($tracking as $tn) {

				$service_map = [];
				$service_map[$tn['external_id']] = $tn['external_id']; // this means we don't have to check for existence
				$service_map[64] = 18; // this will overwrite if the ID is 64
				$service_map[65] = 21; // this will overwrite if the ID is 65
				$service_map[48] = $tn['service_type']; // this allows free shipping to map to whatever was passed in
				$check_service_types = [
					$tn['external_id'], // either it matches exactly, or...
					$service_map[$tn['external_id']] // ... it matches a manually mapped value, or free shipping
				];
				if (in_array($tn['service_type'], $check_service_types)) {
					//run a few checks against the imported address information to make sure that it matches the original data and for each one that does not match we will consturct an alert to send back to the user. If any of the imported values equal NULL at this point it is assumed that we didn't receive this information back from the shipping system, so we can't check for a change, so do send an
					//process and compare attention to field -- this is unlikely to ever fail, but might as well include it for now
					if (strtolower(trim($tn['original_attention'])) != strtolower(trim($tn['imported_attention'])) && !empty($tn['imported_attention']) && !empty($tn['original_attention'])) {
						$imported_address_change[] = "Attention: " . $tn['original_attention'] . " -> " . $tn['imported_attention'];
					}
					//process and compare address1
					if (strtolower(trim($tn['original_address1'])) != strtolower(trim($tn['imported_address1'])) && !empty($tn['imported_address1']) && !empty($tn['original_address1'])) {
						$imported_address_change[] = 'Address 1: ' . $tn['original_address1'] . ' -> ' . $tn['imported_address1'];
					}
					//process and compare address2
					if (strtolower(trim($tn['original_address2'])) != strtolower(trim($tn['imported_address2'])) && !empty($tn['imported_address2']) && !empty($tn['original_address2'])) {
						$imported_address_change[] = 'Address 2: ' . $tn['original_address2'] . ' -> ' . $tn['imported_address2'];
					}
					//process and compare city
					if (strtolower(trim($tn['original_city'])) != strtolower(trim($tn['imported_city'])) && !empty($tn['imported_city']) && !empty($tn['original_city'])) {
						$imported_address_change[] = 'City: ' . $tn['original_city'] . ' -> ' . $tn['imported_city'];
					}
					//process the zip codes and compare them -- there will be a little extra handling for this one
					$imported_zip = strtolower(trim($tn['imported_zipcode']));
					$original_zip = strtolower(trim($tn['original_zipcode']));
					if ($original_zip != $imported_zip && !empty($original_zip) && !empty($imported_zip)) {
						//grab the first 5 characters of the zip
						if (strlen($imported_zip) > 5) $imported_zip = substr($imported_zip, 0, 5);
						if (strlen($original_zip) > 5) $original_zip = substr($original_zip, 0, 5);
						//compare the original and imported zip
						if ($imported_zip != $original_zip) $imported_address_change[] = 'Zipcode: ' . $tn['original_zipcode'] . ' -> ' . $tn['imported_zipcode'];
					}
					//end address check and prepare all the message fragments from above
					if (!empty($imported_address_change)) {
						$imported_address_change_message = "**The following items were changed by the external shipping system:**\n\n";
						foreach ($imported_address_change as $iac) {
							$imported_address_change_message .= $iac . "\n";
						}
						$imported_address_change_message .= "\n**Please confirm address change if neccessary**";
					}

					if ($tn['service_type'] == 'ups') {
						$void = $tn['voided'] == 'Y' ? 1 : 0;
						if ($tn['void'] != $void) {
							prepared_query::execute('UPDATE orders_packages SET void = ? WHERE orders_packages_id = ?', [$void, $tn['orders_packages_id']]);
						}

						if (!empty($tn['orders_weight']) && $tn['orders_weight'] > 0) {
							$cost = $tn['cost'] * ($tn['scale_weight'] / $tn['orders_weight']);
						}
						else {
							$cost = $tn['cost'] / count($tracking);
						}

						$data = [
							':tracking_number' => $tn['tracking_number'],
							':shipping_method_id' => $tn['external_id'],
							':cost' => $cost,
							':void' => $void,
							':orders_packages_id' => $tn['orders_packages_id']
						];

						prepared_query::execute('UPDATE orders_tracking SET tracking_num = :tracking_number, shipping_method_id = :shipping_method_id, cost = :cost, void = :void WHERE orders_packages_id = :orders_packages_id', $data);
					}
					else {
						$data = [
							':tracking_number' => $tn['tracking_number'],
							':shipping_method_id' => $tn['service_type'],
							':cost' => $tn['cost'],
							':orders_packages_id' => $tn['orders_packages_id']
						];

						prepared_query::execute('UPDATE orders_tracking SET tracking_num = :tracking_number, shipping_method_id = :shipping_method_id, cost = :cost WHERE orders_packages_id = :orders_packages_id', $data);
					}
					$tracking_numbers[] = $tn['tracking_number'];
				}
				else {
					echo json_encode(['shipping_type_error' => TRUE]);
					exit;
				}
			}

			//'<a href="https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums='.implode('%0A', $tracking_numbers).'">'..'</a>'

			$note .= implode(', ', $tracking_numbers)."\n\n";
			$note .= 'Please let us know if we can help out in any way with this or future orders!';

			$ptMstrArr = $pt->pt_get_order_packages($oid);
			ob_start();
			$disp_pack->dispPackageList ($oid,$ptMstrArr,$sm,$pt);
			$packing_lists = ob_get_clean();
			echo json_encode(['display' => $packing_lists, 'note' => $note, 'imported_address_change' => $imported_address_change_message]);
			exit;
			break;
		default:
			echo "Undefined Action";
			exit;
			break;
	}
}

######################################

class display_packages {

	public function __construct() {
	}

	public function dispPackageList($oid, $ptMstrArr, $sm, $pt) {
		$rowcount = $numpackages = $totalpackageweight = 0;
		$trackArr = array();
		sort($ptMstrArr);
		$ordstatus = $pt->pt_get_order_status($oid);

		if (is_array($ptMstrArr) && count($ptMstrArr) > 0) { ?>
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td class="rowhdr" style="border-left:1px #000 solid;">Package ID</td>
					<td class="rowhdr">Size</td>
					<td class="rowhdr">Weight (lbs)</td>
					<td class="rowhdr">Tracking #</td>
					<td class="rowhdr">Method</td>
					<td class="rowhdr">Cost</td>
					<?php if ($ordstatus != 3 && $ordstatus != 6) { ?>
					<td class="rowhdr">Shipment Actions</td>
					<?php } ?>
					<td class="rowhdr">&nbsp;</td>
				</tr>
				<?php foreach ($ptMstrArr as $k => $ptArr) {
					$packageweight = 0;

					if (is_array($ptArr)) {
						$rowstyle = $rowcount % 2 == 0 ? "row1" : "row2";
						$trackArr = $pt->pt_get_order_tracking($ptArr['orders_packages_id']);
						$packageweight = $pt->pt_pckg_weight($ptArr); ?>
				<tr>
					<td class="<?= $rowstyle; ?>" style="border-left:1px #000 solid;"><?= $ptArr['void']?'<span class="lnthru">'.$ptArr['orders_packages_id'].'</span>':$ptArr['orders_packages_id']; ?></td>
					<td class="<?= $rowstyle; ?>"><?= $ptArr['void']?'<span class="lnthru">'.$ptArr['package_name'].'</span>':$ptArr['package_name']; ?></td>
					<td class="<?= $rowstyle; ?>"><?= $ptArr['void']?'<span class="lnthru">'.$packageweight.'</span>':$packageweight; ?></td>
					<td class="<?= $rowstyle; ?>"><?php $this->dispTrackNums($trackArr, $sm); ?></td>
					<td class="<?= $rowstyle; ?>"><?php $this->dispShipping($trackArr, $sm); ?></td>
					<td class="<?= $rowstyle; ?>"><?php $this->dispCost($trackArr); ?></td>
					<?php if ($ordstatus != 3 && $ordstatus != 6) { ?>
					<td class="<?= $rowstyle; ?>"><?php $this->dispShipActions($trackArr, $ptArr['orders_id'], $ptArr['orders_packages_id'], @$ptArr['orders_tracking_id']); ?></td>
					<?php } ?>
					<td class="<?= $rowstyle; ?>"><?php $this->dispTrackLabelLnk($trackArr, $sm, $ptArr['orders_id']); ?>&nbsp;</td>
				</tr>
						<?php $rowcount++;
						if (empty($ptArr['void'])) {
							if (!empty($trackArr) && $trackArr[0]['tracking_num']=='') $numpackages++;
							$totalpackageweight = $totalpackageweight + $packageweight;
						}
						unset($trackArr);
					}
				}

				if (!empty($numpackages)) { ?>
				<tr>
					<td style="border-left:1px #000 solid;border-bottom:1px#000 solid;border-right:1px #000 solid;padding:5px;text-align:right;font-weight:bold;font-family:calibri,arial,sans-serif;font-size:11pt;" class="rowftr" colspan="2"><?= $numpackages; ?> Package<?= $numpackages>1?'s':''; ?></td>
					<td class="rowftr" style="border-bottom:1px#000 solid;border-right:1px #000 solid;padding:5px;text-align:center;font-weight:bold;font-family:calibri,arial,sans-serif;font-size:11pt;"><?= $totalpackageweight; ?></td>
					<td class="rowftr" colspan="5" style="border-bottom:1px#000 solid;border-right:1px #000 solid;padding:5px;text-align:center;font-weight:bold;font-family:calibri,arial,sans-serif;font-size:11pt;"><input type="hidden" id="numpackages" value="<?= $numpackages; ?>" />&nbsp;</td>
				</tr>
				<?php } ?>
			</table>
		<?php }

		return true;
	}

	protected function dispShipActions($inArr, $oid, $ordpackid, $ordtrackid) {
		$hasvoid = 0;

		if (!count($inArr)) { ?>
			<a class="shiplnk" href="<?= $_SERVER['PHP_SELF']; ?>" onclick="return dispEditPack('<?= $oid; ?>', '<?= $ordpackid; ?>', 'ship');">Ship</a>&nbsp;
			<a class="shiplnk" href="<?= $_SERVER['PHP_SELF']; ?>" onclick="return dispEditPack('<?= $oid; ?>', '<?= $ordpackid; ?>', 'track');">Edit</a>&nbsp;
			<a class="shiplnk" href="<?= $_SERVER['PHP_SELF']; ?>" onclick="return (window.confirm('Are you sure you wish to delete this package?')?runPackage('<?= $oid; ?>', '<?= $ordpackid; ?>', '<?= $ordtrackid; ?>', 'delete_packages', 'editPackagesDiv'):false);">Delete</a>
		<?php }
		else {
			# check for void first
            foreach($inArr as $k => $vArr) {
				if (!empty($vArr['void'])) {
					$hasvoid = $vArr['void'];
					break;
				}
			}

			foreach ($inArr as $k => $secArr) {
				if ($secArr['void'] && $secArr['date_time_voided']) {
					$void_time = new DateTime($secArr['date_time_voided']); ?>
					<span class="lnthru"><?= $void_time->format('m/d/Y h:i:s a'); ?></span>
				<?php }
				else {
					if (!$secArr['tracking_num'] && !$secArr['shipping_method_id'] && !$secArr['cost']) { ?>
						<a class="shiplnk" href="<?= $_SERVER['PHP_SELF']; ?>" onclick="return dispEditPack('<?= $oid; ?>', '<?= $secArr['orders_packages_id']; ?>', 'ship');">Ship</a>&nbsp;
						<a class="shiplnk" href="<?= $_SERVER['PHP_SELF']; ?>" onclick="return dispEditPack('<?= $oid; ?>', '<?= $secArr['orders_packages_id']; ?>', 'track');">Edit</a>&nbsp;
						<?php if (!empty($hasvoid)) { ?>
							<a class="shiplnk" href="<?= $_SERVER['PHP_SELF']; ?>" onclick="return (window.confirm('Are you sure you wish to delete this package?') ? runPackage('<?= $oid; ?>', '<?= $secArr['orders_packages_id']; ?>', '<?= $secArr['orders_tracking_id']; ?>', 'void_package', 'editPackagesDiv'):false);">Void</a>
						<?php }
						else { ?>
							<a class="shiplnk" href="<?= $_SERVER['PHP_SELF']; ?>" onclick="return (window.confirm('Are you sure you wish to delete this package?') ? runPackage('<?= $oid; ?>', '<?= $secArr['orders_packages_id']; ?>', '<?= $secArr['orders_tracking_id']; ?>', 'delete_packages', 'editPackagesDiv') : false);">Delete</a>
						<?php }
					}
					else { ?>
						<a class="shiplnk" href="<?= $_SERVER['PHP_SELF']; ?>" onclick="return (window.confirm('Are you sure you wish to void this tracking shipment?') ? runPackage('<?= $oid; ?>', '<?= $secArr['orders_packages_id']; ?>', '<?= $secArr['orders_tracking_id']; ?>', 'void_shipment', 'editPackagesDiv') : false);">Void</a>
					<?php }
				}
				echo "<br>";
			}

		}
		return true;
	}

	protected function dispCost($inArr) {
		foreach ($inArr as $k => $secArr) {
			if ($secArr['void'] && $secArr['cost']) { ?>
				<span style="color:#f00;text-decoration: line-through;">$<?= number_format($secArr['cost'], 2); ?></span>
			<?php }
			else {
				echo $secArr['cost']?'$'.number_format($secArr['cost'], 2):'&nbsp;';
			}
			echo "<br>";
		}
		return true;
	}

	protected function dispShipping($inArr, $sm) {
		foreach ($inArr as $k => $secArr) {
			if (!empty($secArr['void'])) { ?>
				<span style="color:#f00;text-decoration: line-through;"><?= $sm->sm_short_description($secArr['shipping_method_id']); ?></span>
			<?php }
			else {
				echo $sm->sm_short_description($secArr['shipping_method_id']);
			}
			echo "<br>";
		}
		return true;
	}

	protected function dispTrackNums($inArr, $sm) {
		foreach ($inArr as $k => $secArr) {
			if (!empty($secArr['void'])) { ?>
				<span style="color:#f00;text-decoration: line-through;"><?= $secArr['tracking_num']; ?></span>
			<?php }
			else {
				$carrier = intval($secArr['shipping_method_id'])>0&&$secArr['tracking_num']?$sm->sm_details(intval($secArr['shipping_method_id']),'carrier'):'';

				switch ($carrier) {
					case 'FedEx':
						$lnk = 'http://www.fedex.com/Tracking/Detail?ascend_header=1&totalPieceNum=&clienttype=dotcom&cntry_code=us&tracknumber_list='.$secArr['tracking_num'].'&language=english&trackNum='.$secArr['tracking_num'].'&pieceNum';
						break;
					case 'UPS':
						$lnk = 'http://www.ups.com/WebTracking/processInputRequest?loc=en_US&Requester=NOT&tracknum='.$secArr['tracking_num'].'&AgreeToTermsAndConditions=yes';
						break;
					case 'USPS':
						$lnk = 'http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum='.$secArr['tracking_num'];
						break;
					default:
						$lnk = '';
						break;
				}

				if (!empty($lnk)) {
					echo $secArr['tracking_num']; ?>
					&nbsp;&nbsp;<a class="shiplnk" href="<?= $lnk; ?>" onclick="window.open(this.href, 'nw');return false;">Track </a>
				<?php }
				else {
					echo $secArr['tracking_num'];
				}
			}
			echo "<br>";
		}
		return true;
	}

	protected function dispTrackLabelLnk($inArr, $sm, $oid) {
		foreach ($inArr as $k => $secArr) {
			if (empty($secArr['void'])) {
				$carrier = intval($secArr['shipping_method_id'])>0&&$secArr['tracking_num']?$sm->sm_details(intval($secArr['shipping_method_id']), 'carrier'):'';
				$lnk = ($secArr['tracking_num']) ? ("images/fedex/".trim($secArr['tracking_num']).".png") : "";
				if ($carrier == 'FedEx' && $secArr['tracking_num'] && $lnk) { ?>
					<a class="shiplnk" href="<?= $lnk; ?>" onclick="return dispPackLabel('<?= $oid; ?>', this.href, 'print_label');" target='_blank'>Label</a>
				<?php }
			}
		}
		return true;
	}

	public function dispEditPackage($oid, $packagesid, $sm, $pt, $ptMstrArr, $packship) {
		$packArr = $pt->getPackagesArr();
		$shipMethodsArr = $sm->getShippingMethods();
		$selPackageArr = array();
		$modalaction = ($packagesid ? "Update" : "Add a").(($packship	=== "ship") ? " Tracking" : " Package");
		$cannoteditpackage = 0;

		# 375 - default order weight
		$default_weight = $pt->pt_get_from_order($oid, 'orders_weight');
		//$default_weight = ($default_weight < 1) ? 1 : floor($default_weight);
		//MMD - 05-12-10 - round up instead of down
		$default_weight = ($default_weight < 1) ? 1 : ceil($default_weight);

		foreach ($ptMstrArr as $k => $vArr) {
			if ($vArr['orders_packages_id'] == $packagesid) {
				$selPackageArr = $vArr;
				$trackArr = $pt->pt_get_order_tracking($vArr['orders_packages_id']);
				foreach ($trackArr as $k => $trckArr) {
					if (empty($trckArr['void'])) {
						$selPackageArr['orders_tracking_id'] = $trckArr['orders_tracking_id'];
						$selPackageArr['tracking_num'] = $trckArr['tracking_num'];
						$selPackageArr['shipping_method_id'] = $trckArr['shipping_method_id'];
						$selPackageArr['cost'] = $trckArr['cost'];
						$cannoteditpackage = $trckArr['tracking_num'] ? 1 : $cannoteditpackage;
					}
				}
			}
		}

		if ($packship	=== "ship") {
			$cannoteditpackage = 1;
		}

		$disabledpack = $cannoteditpackage ? " disabled" : ""; ?>
		<div style="text-align:right;"><a href="<?= $_SERVER['PHP_SELF']; ?>" onclick="return closeeditpackDiv();">[close]</a></div>
		<table border="0" cellpadding="2" cellspacing="0" width="100%">
			<tr>
				<td colspan="2" class="lblhdr"><?= $modalaction; ?></td>
			</tr>
			<?php if (!empty($packagesid)) { ?>
			<tr>
				<td class="lblhdrright">Package Id:</td>
				<td class="pckgtxt"><?= $packagesid; ?></td>
			</tr>
			<?php }
			else { ?>
			<!--input type="hidden" value="1" id="num_packages" name="num_packages"-->
			<tr>
				<td class="lblhdrright">Number of Packages:</td>
				<td class="pckgtxt">
					<select id="num_packages">
						<?php for ($n=1; $n<=100; $n++) { ?>
						<option value="<?= $n; ?>"><?= $n; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<?php } ?>
			<tr>
				<td class="lblhdrright">Size:</td>
				<td>
					<?php if (!empty($packArr)) { ?>
					<style>
						.promoted-packages { text-align:justify; margin-bottom:4px; }
						.auto-pack-select { margin:2px 4px; }
					</style>
					<div class="promoted-packages">
						<?php foreach ($packArr as $package_type) {
							if (!$package_type['promote']) continue; ?>
						<input type="button" value="<?= $package_type['package_name']; ?>" data-pack-id="<?= $package_type['package_type_id']; ?>" class="auto-pack-select">
						<?php } ?>
					</div>
					<select id="update_package_id" onchange="return showPackageDim();" <?= $disabledpack; ?>>
						<option value="1">NO BOX</option>
						<?php foreach ($packArr as $package_type) { ?>
						<option value="<?= $package_type['package_type_id']; ?>" <?= @$selPackageArr['package_type_id']==$package_type['package_type_id']?'selected':'' ?>><?= $package_type['package_name']; ?></option>
						<?php } ?>
					</select>
					<script>
						jQuery('.auto-pack-select').click(function() {
							jQuery('#update_package_id').val(jQuery(this).attr('data-pack-id'));
							jQuery('#pckgDimDiv').hide();
						});
					</script>
					<?php } ?>
					<div id="pckgDimDiv">
						<span class="lblhdrright">L:</span> <input type="text" id="update_length" value="<?= @$selPackageArr['order_package_length']; ?>" class="dimInput" <?= $disabledpack; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<span class="lblhdrright">W:</span> <input type="text" id="update_width" value="<?= @$selPackageArr['order_package_width']; ?>" class="dimInput" <?= $disabledpack; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<span class="lblhdrright">H:</span> <input type="text" id="update_height" value="<?= @$selPackageArr['order_package_height']; ?>" class="dimInput" <?= $disabledpack; ?>>
					</div>
				</td>
			</tr>
			<tr>
				<td class="lblhdrright">Weight (lbs):</td>
				<td><input type="text" id="update_scale_weight" value="<?= $packagesid ? $selPackageArr['scale_weight'] : $default_weight; ?>" onkeypress="catchEvent(event, '<?= $oid; ?>', '<?= $packagesid; ?>');" <?= $disabledpack; ?>></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<?php if ($packagesid && $packship !== 'track') { ?>
			<tr>
				<td class="lblhdrright">Tracking #:</td>
				<td><input type="text" id="update_tracking_num" value="<?= $selPackageArr['tracking_num']; ?>"></td>
			</tr>
			<tr>
				<td class="lblhdrright">Method:</td>
				<td>
					<?php if (is_array($shipMethodsArr) && count($shipMethodsArr) > 0) { ?>
					<select id="update_shipping_method_id">
						<option value="0"> Select One </option>
						<?php foreach ($shipMethodsArr as $k => $vArr) {
							if (!empty($selPackageArr['shipping_method_id'])) {
								$selected = ($selPackageArr['shipping_method_id'] == $k) ? "selected" : "";
							}
							elseif (!empty($selPackageArr['default_shipping_method_id'])) {
								$selected = ($selPackageArr['default_shipping_method_id'] == $k) ? "selected" : "";
							} ?>
						<option value="<?= $k; ?>" <?= $selected; ?>><?= $vArr['carrier'].' '.$vArr['name']; ?></option>
						<?php } ?>
					</select>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td class="lblhdrright">Cost:</td>
				<td><input type="text" id="update_cost" name="update_cost" value="<?= number_format($selPackageArr['cost'], 2); ?>" onkeypress="catchEvent(event, '<?= $oid; ?>', '<?= $packagesid; ?>');"></td>
			</tr>
			<?php }
			else { ?>
			<tr>
				<td colspan="2">
					<input type="hidden" id="update_tracking_num" value="<?= @$selPackageArr['tracking_num']; ?>">
					<input type="hidden" id="update_shipping_method_id" value="<?= @$selPackageArr['shipping_method_id']; ?>">
					<input type="hidden" id="update_cost" value="<?= @$selPackageArr['cost']; ?>">
				</td>
			</tr>
			<?php } ?>
			<tr>
				<td colspan="2"><input type="hidden" id="update_orders_tracking_id" value="<?= @$selPackageArr['orders_tracking_id']; ?>"><input type="hidden" id="packship" value="<?= $packship; ?>"></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="button" name="updatebutton" value="<?= $modalaction; ?>" onclick="return updatePackage('<?= $oid; ?>', '<?= $packagesid; ?>', true);">
					<?php if ($modalaction == 'Add a Package') { ?>
					<input type="button" name="updatebutton" value="Add &amp; Repeat" onclick="return updatePackage('<?= $oid; ?>', '<?= $packagesid; ?>', false);">
					<?php } ?>
				</td>
			</tr>
		</table>
		<?php return true;
	}

	public function __destruct() {
		return true;
	}
} ?>
