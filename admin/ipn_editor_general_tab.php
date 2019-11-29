<?php
require_once('ipn_editor_top.php');

$ipn_header = $ipn->get_header();
$inventory = $ipn->get_inventory();

$forecast = new forecast($ipn->id());
$ipn_frcst = $forecast->build_report('ALL');
$ipn_frcst = !empty($ipn_frcst[0])?$ipn_frcst[0]:NULL;
$quantity_to_order = $ipn_frcst['reorder_qty'];

$single_day = $forecast->daily_qty($ipn_frcst);

$days_supply = !$inventory['available']?0:(!$single_day?999999:ceil($inventory['available']/($single_day)));
$days_supply_display = number_format($days_supply, 1);

$days_indicator = $forecast->days_indicator_color($ipn->get_header(), $days_supply);

$stock_status = 0; // 0 is in our target range, -1 is below min, 1 is above max
$stock_diff = 0;
$stock_desc = '';
$stock_pctg = 0;
if ($inventory['available'] < $ipn_frcst['target_min_qty']) {
	$stock_status = -1;
	$stock_diff = $ipn_frcst['target_min_qty'] - $inventory['available'];
	$stock_desc = 'Under';
	$stock_pctg = ( $ipn_frcst['target_min_qty'] != 0 ? round(($stock_diff / $ipn_frcst['target_min_qty']) * 100) : 'N/A');
}
elseif ($inventory['available'] > $ipn_frcst['target_max_qty']) {
	$stock_status = 1;
	$stock_diff = $inventory['available'] - $ipn_frcst['target_max_qty'];
	$stock_desc = 'Over';
	$stock_pctg = round(($stock_diff / $inventory['available']) * 100);
}

$admin = ck_admin::login_instance();
$vendors = prepared_query::fetch('SELECT vendors_id as id, vendors_company_name as `text` FROM vendors ORDER BY vendors_company_name ASC', cardinality::SET);
$conditions = prepared_query::fetch('SELECT conditions_id as id, conditions_name as `text` FROM conditions', cardinality::SET);
$warranties = prepared_query::fetch('SELECT * FROM warranties', cardinality::SET);
$dealer_warranties = prepared_query::fetch('SELECT * FROM dealer_warranties', cardinality::SET);
$ipn_categories = prepared_query::fetch('SELECT * FROM products_stock_control_categories ORDER BY name ASC', cardinality::SET); ?>
<style>
	#bundle-pricing-impact-form div { margin:10px; }
</style>
<table>
	<tr>
		<td width="60%" valign="top">
			<form method="post" id="save_general" class="<?= !empty($ipn->get_price('customers'))?'special-pricing':''; ?>" name="save_general" action="/admin/ipn_editor.php">
				<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
				<input type="hidden" name="action" value="save_general">
				<input type="hidden" name="sub-action" value="update">
				<table cellspacing="2px" cellpadding="2px" border="0">
					<tbody>
						<?php if ($ipn->get_header('creation_reviewed') == 0) { ?>
						<tr id="mark-creation-reviewed-tr">
							<td class="main" valign="top">Creation Needs Review:</td>
							<td>
							<?php if (!empty($admin->has_legacy_permission_for('ipn_reviewer'))) { ?>
								<button type="button" id="mark-creation-review" data-stock-id="<?= $ipn->id(); ?>">Mark as reviewed</button>
							<?php }
							else { ?>
								<i>creation needs reviewed</i>
							<?php } ?>
							</td>
						</tr>
						<?php } ?>
						<tr>
							<td nowrap class="main" valign="top">Description:</td>
							<td class="main" valign="top"><textarea name="stock_description" rows="4" cols="25"><?= $ipn_header['stock_description']; ?></textarea></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top" colspan="2"><?= (new item_popup('Image', service_locator::get_db_service(), ['stock_id' => $ipn->id()])); ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Bin Location:</td>
							<td class="main" valign="top"><input type="text" name="stock_location" value="<?= $ipn_header['bin1']; ?>"></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Bin Location #2:</td>
							<td class="main" valign="top"><input type="text" name="stock_location_2" value="<?= $ipn_header['bin2']; ?>"></td>
						</tr>

						<?php if ($ipn->has_package()) {
							$package = $ipn->get_package(); ?>
						<tr>
							<td colspan="2"><hr></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Package Name:</td>
							<td class="main" valign="top"><?= $package['package_name']; ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Package Length:</td>
							<td class="main" valign="top"><?= $package['length']; ?>"</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Package Width:</td>
							<td class="main" valign="top"><?= $package['width']; ?>"</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Package Height:</td>
							<td class="main" valign="top"><?= $package['height']; ?>"</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Package Logoed:</td>
							<td class="main" valign="top"><?= empty($package['logoed'])?'No':'Yes'; ?></td>
						</tr>
						<tr>
							<td colspan="2"><hr></td>
						</tr>
						<?php }
						elseif ($ipn->can_be_package()) { ?>
						<tr>
							<td nowrap class="main" valign="top">Shipping Package:</td>
							<td class="main" valign="top"><input type="checkbox" name="is_package" id="is_package"></td>
						</tr>
						<style>
							.package_toggle { display:none; }
						</style>
						<tr class="package_toggle package_attributes">
							<td colspan="2"><hr></td>
						</tr>
						<tr class="package_toggle package_attributes">
							<td nowrap class="main" valign="top">Package Name:</td>
							<td class="main" valign="top"><input type="input" name="package_name" id="package_name" placeholder="Example: 12 x 12 x 12"></td>
						</tr>
						<tr class="package_toggle package_attributes">
							<td nowrap class="main" valign="top">Package Length:</td>
							<td class="main" valign="top"><input type="input" name="package_length" id="package_length" placeholder="Example: 12"></td>
						</tr>
						<tr class="package_toggle package_attributes">
							<td nowrap class="main" valign="top">Package Width:</td>
							<td class="main" valign="top"><input type="input" name="package_width" id="package_width" placeholder="Example: 12"></td>
						</tr>
						<tr class="package_toggle package_attributes">
							<td nowrap class="main" valign="top">Package Height:</td>
							<td class="main" valign="top"><input type="input" name="package_height" id="package_height" placeholder="Example: 12"></td>
						</tr>
						<tr class="package_toggle package_attributes">
							<td nowrap class="main" valign="top">Package Logoed:</td>
							<td class="main" valign="top">
								<select name="package_logoed" id="package_logoed">
									<option value="0">No</option>
									<option value="1">Yes</option>
								</select>
							</td>
						</tr>
						<tr class="package_toggle package_attributes">
							<td colspan="2"><hr></td>
						</tr>
						<?php } ?>

						<tr>
							<td nowrap class="main" valign="top">Conditioning Notes:</td>
							<td class="main" valign="top"><textarea name="conditioning_notes" rows="4" cols="25"><?= $ipn_header['conditioning_notes']; ?></textarea></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Condition:</td>
							<td class="main" valign="top"><?= tep_draw_pull_down_menu('conditions', $conditions, $ipn_header['conditions']); ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Discontinued:</td>
							<td class="main" valign="top"><input type="checkbox" name="discontinued" id="discontinued" <?= $ipn->is('discontinued')?'checked':''; ?>></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Bundled Product:</td>
							<td class="main" valign="top"><input type="checkbox" name="is_bundle" id="is_bundle" <?= $ipn->is('is_bundle')?'checked':''; ?>></td>
						</tr>

						<?php if (!($inventory['on_hand'] > 0 || $ipn->has_po_reviews() || $ipn->has_receiving_history())) { ?>
						<tr>
							<td nowrap class="main" valign="top">Do Not Buy:</td>
							<td class="main" valign="top">
								<input type="checkbox" name="donotbuy" id="donotbuy" <?= $ipn->is('donotbuy')?'checked':''; ?>>
								<?php if ($ipn->is('donotbuy')) {
									echo $ipn_header['donotbuy_admin'].' on '.$ipn_header['donotbuy_date']->format('m/d/Y');
								} ?>
							</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Serialized:</td>
							<td class="main" valign="top"><input type="checkbox" id="serialized" name="serialized" <?= $ipn->is('serialized')?'checked':''; ?>></td>
						</tr>
						<?php }
						elseif ($ipn->is('serialized')) { ?>
						<tr>
							<td nowrap class="main" valign="top">Serialized:</td>
							<td class="main" valign="top">Y</td>
						</tr>
						<input type="hidden" id="serialized" name="serialized" value="on">
						<?php }
						else { ?>
						<tr>
							<td nowrap class="main" valign="top">Serialized:</td>
							<td class="main" valign="top">N</td>
						</tr>
						<?php } ?>
						<tr>
							<td nowrap class="main" valign="top">
								Retail Price:
								<i style="font-size: 10px;">
									Updated:
									<?php if ($ch = $ipn->get_change_history(['Stock Price Change', 'Stock Price Confirmation'])) echo $ch[0]['change_date']->format('m/d/Y'); ?>
								</i>
							</td>
							<td class="main" valign="top">
								<?php if ($ipn->is('is_bundle') && $ipn->get_header('bundle_price_flows_from_included_products') > 0) { ?>
									<span><i><?= CK\text::monetize($ipn->get_default_listing()->get_price('original')); ?> [Bundle Price]</i></span>
								<?php }
								else { ?>
								<input type="text" name="stock_price" id="stock_price" value="<?= $ipn_header['stock_price']; ?>" data-old-price="<?= $ipn_header['stock_price']; ?>">
								<input type="checkbox" name="confirm_stock_price" id="confirm-stock-price">
								<label for="confirm-stock-price">Confirm Price</label>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">
								Reseller Price:
								<i style="font-size: 10px;">
									Updated:
									<?php if ($ch = $ipn->get_change_history(['Dealer Price Change', 'Dealer Price Confirmation'])) echo $ch[0]['change_date']->format('m/d/Y'); ?>
								</i>
							</td>
							<td class="main" valign="top">
								<?php if ($ipn->is('is_bundle') && $ipn->get_header('bundle_price_flows_from_included_products') > 0) { ?>
									<span><i><?= CK\text::monetize($ipn->get_default_listing()->get_price('dealer')); ?> [Bundle Price]</i></span>
								<?php }
								else { ?>
								<input type="text" name="dealer_price" id="dealer_price" value="<?= $ipn_header['dealer_price']; ?>" data-old-price="<?= $ipn_header['dealer_price']; ?>">
								<input type="checkbox" name="confirm_dealer_price" id="confirm-dealer-price">
								<label for="confirm-dealer-price">Confirm Price</label>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">
								Wholesale High Price:
								<i style="font-size: 10px;">
									Updated:
									<?php if ($ch = $ipn->get_change_history(['Wholesale High Price Change', 'Wholesale High Price Confirmation'])) echo $ch[0]['change_date']->format('m/d/Y'); ?>
								</i>
							</td>
							<td class="main" valign="top">
								<?php if ($ipn->is('is_bundle') && $ipn->get_header('bundle_price_flows_from_included_products') > 0) { ?>
									<span><i><?= CK\text::monetize($ipn->get_default_listing()->get_price('wholesale_high')); ?> [Bundle Price]</i></span>
								<?php }
								else { ?>
								<input type="text" name="wholesale_high_price" id="wholesale_high_price" value="<?= CK\text::demonetize($ipn_header['wholesale_high_price']); ?>" data-old-price="<?= $ipn_header['wholesale_high_price']; ?>">
								<input type="checkbox" name="confirm_wholesale_high_price" id="confirm-wholesale-high-price">
								<label for="confirm-wholesale-high-price">Confirm Price</label>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">
								Wholesale Low Price:
								<i style="font-size: 10px;">
									Updated:
									<?php if ($ch = $ipn->get_change_history(['Wholesale Low Price Change', 'Wholesale Low Price Confirmation'])) echo $ch[0]['change_date']->format('m/d/Y'); ?>
								</i>
							</td>
							<td class="main" valign="top">
								<?php if ($ipn->is('is_bundle') && $ipn->get_header('bundle_price_flows_from_included_products') > 0) { ?>
									<span><i><?= CK\text::monetize($ipn->get_default_listing()->get_price('wholesale_low')); ?> [Bundle Price]</i></span>
								<?php }
								else { ?>
								<input type="text" name="wholesale_low_price" id="wholesale_low_price" value="<?= CK\text::demonetize($ipn_header['wholesale_low_price']); ?>" data-old-price="<?= $ipn_header['wholesale_low_price']; ?>">
								<input type="checkbox" name="confirm_wholesale_low_price" id="confirm-wholesale-low-price">
								<label for="confirm-wholesale-low-price">Confirm Price</label>
								<?php } ?>
							</td>
						</tr>
						<?php if ($ipn->has_special_prices()) { ?>
						<tr>
							<td colspan="2" style="text-align:center;">
								<strong style="color:#990;">Specials Price(s): <?= implode(', ', array_map(function($special) { return CK\text::monetize($special['price']); }, $ipn->get_special_prices())); ?></strong>
							</td>
						</tr>
						<?php } ?>
						<tr>
							<td nowrap class="main" valign="top">Average cost:</td>
							<td nowrap class="main" valign="top"><?= CK\text::monetize($ipn->get_avg_cost()); ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Price Review Frequency</td>
							<td class="main" valign="top" nowrap>
								<input type="text" name="pricing_review" size="10" value="<?= $ipn_header['pricing_review']; ?>" id="pricing_review" disabled="true">
								<span style="font-family:arial; font-size:12px; display:none;" id="pr_default_text">DEFAULT</span>
								<input type="button" value="Use Default" id="pr_use_default" style="display:none;">
								<input type="checkbox" id="change-frequency-checkbox" name="update_pricing_review">
								<label for="change-frequency-checkbox">Change Frequency</label>
							</td>
						</tr>
						<tr><td colspan="2">&nbsp;</td></tr>

						<tr>
							<td nowrap class="main" valign="top">Total Quantity On Hand:</td>
							<td class="main" valign="top">
								<?= $inventory['on_hand']; ?>
								<?php if (!$ipn->is('serialized')) { ?>
								<span style="font-size: 9px;">(Last manual confirmation: <?= !empty($ipn_header['last_quantity_change'])?$ipn_header['last_quantity_change']->format('m/d/y'):'Never'; ?>)</span>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">On Hold:</td>
							<td class="main" valign="top"><?= $inventory['on_hold']; ?>&nbsp;</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Salable:</td>
							<td class="main" valign="top"><?= $inventory['salable']; ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Allocated from Stock:</td>
							<td class="main" valign="top">
								<?= $inventory['local_allocated'].'&nbsp;'; ?>
								<?php if ($inventory['ca_allocated'] > 0) { ?>
								<i>(<?= $inventory['ca_allocated']; ?> on pending Channel Advisor orders)</i>
								<?php } ?>
							</td>
						</tr>
						<style>
							.no-requiring-ipns td { font-weight:bold; }
						</style>
						<tr class="<?= $ipn->has_requiring_ipns()?'no-requiring-ipns':''; ?>">
							<td nowrap class="main" valign="top">Available:</td>
							<td class="main" valign="top"><?= $inventory['available']; ?></td>
						</tr>
						<?php if ($ipn->has_requiring_ipns()) {
							$accumulative_parent_qty = 0;
							foreach ($ipn->get_requiring_ipns() as $parent_ipn) {
								$accumulative_parent_qty += ($parent_ipn->get_inventory('available')>0?$parent_ipn->get_inventory('available'):0);
							} ?>
						<tr>
							<td nowrap class="main" valign="top">Parent Available Quantity:</td>
							<td class="main" valign="top"><?= $accumulative_parent_qty; ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top" style="font-weight: bold;">Adjusted Available Quantity:</td>
							<td class="main" valign="top" style="font-weight: bold;"><?= $inventory['available'] - $accumulative_parent_qty; ?></td>
						</tr>
						<?php } ?>

						<tr>
							<td nowrap class="main" valign="top" style="font-weight: bold;">Stock Analysis:</td>
							<td class="main" valign="top" style="background-color:#<?= $days_indicator; ?>;">
								<style>
									.stock-analysis { width:100%; }
									.stock-analysis th, .stock-analysis td { padding:6px 8px; text-align:center; }
									.stock-analysis th { border-style:solid; border-color:#333; border-width:2px 0px 1px 0px; }
								</style>
								<table class="stock-analysis" cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<?= $stock_status<0?'<th>Actual</th>':''; ?>
											<th>Min.</th>
											<?= $stock_status==0?'<th>Actual</th>':''; ?>
											<th>Max.</th>
											<?= $stock_status>0?'<th>Actual</th>':''; ?>
											<th>#/day</th>
										</tr>
										<tr>
											<?= $stock_status<0?'<td>'.$inventory['available'].'</td>':''; ?>
											<td><?= ceil($ipn_header['min_inventory_level'] * $single_day); ?></td>
											<?= $stock_status==0?'<td>'.$inventory['available'].'</td>':''; ?>
											<td><?= $ipn_frcst['target_max_qty']; ?></td>
											<?= $stock_status>0?'<td>'.$inventory['available'].'</td>':''; ?>
											<td><?= number_format($single_day, 2); ?></td>
										</tr>
										<tr>
											<th colspan="2">Days Supply</th>
											<th colspan="2"><?php if (!empty($stock_status)) { ?>Severity<?php } ?></th>
										</tr>
										<tr>
											<td colspan="2"><?= $days_supply_display; ?></td>
											<td colspan="2"><?php if (!empty($stock_status)) { echo $stock_pctg.'% '.$stock_desc; } ?></td>
										</tr>
									</tbody>
								</table>
								<!--
								<?= $days_supply_display; ?> Days Supply
								<?php if (!empty($stock_diff)) { ?>
								<br><span style="background-color:#f1f1f1;"> <?= $stock_diff; ?> <?= $stock_desc; ?> </span> &nbsp;<?= $stock_desc=='Over'?'Max.':'Min.'; ?> Stock Levels
								<br><?= $stock_pctg; ?>% Off
								<?php } ?>
								-->
							</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Maximum Displayed to Customer:</td>
							<td class="main" valign="top"><input type="text" name="max_displayed_quantity" value="<?= $ipn_header['max_displayed_quantity']; ?>"></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Minimum Inventory Level (Days):</td>
							<td class="main" valign="top"><input type="text" name="min_inventory_level" id="min_inventory_level" value="<?= $ipn_header['min_inventory_level']; ?>"></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Target Inventory Level (Days):</td>
							<td class="main" valign="top"><input type="text" name="target_inventory_level" id="target_inventory_level" value="<?= $ipn_header['target_inventory_level']; ?>"></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Maximum Inventory Level (Days):</td>
							<td class="main" valign="top"><input type="text" name="max_inventory_level" id="max_inventory_level" value="<?= $ipn_header['max_inventory_level']; ?>"></td>
						</tr>

						<tr><td colspan="2">&nbsp;</td></tr>
						<tr>
							<td nowrap class="main" valign="top">On Order:</td>
							<td class="main" valign="top"><?= $inventory['on_order']; ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">On Order Allocated:</td>
							<td class="main" valign="top"><?= $inventory['po_allocated']; ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">On Order Unallocated:</td>
							<td class="main" valign="top"><?= $inventory['adjusted_on_order']; ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Order Recommendation:</td>
							<td nowrap class="main" valign="top"><?= max((int) $quantity_to_order, 0); ?> <a href="#" class="explain-reorder"><small>(Explain)</small></a></td>
						</tr>
						<tr class="explain-reorder-details" style="display:none;">
							<td colspan="2">
								<style>
									.daily { color:#66c; }
									.target { color:#6cc; }
									.lead-time { color:#c6c; }
									.lead-time-qty { color:#a3a; }
									.pre-lead-time-available { color:#c66; }
									.available { color:#6c6; }
									.on-order { color:#cc6; }
									.conditioning { color:#66c; }
									.formula { text-align:right; font-family:Consolas, monaco, monospace; width:250px; }
									.formula-separator { width:60px; float:right; }
									.clearfix { clear:both; }
									.mid-separator { border-top:1px dashed #6a6; }
								</style>
								<div class="legend">
									<!--span class="daily" title="What we've figured for our daily run rate">[daily]</span-->
									<span class="target" title="The daily run rate x target inventory level days">[target]</span>
									<span class="lead-time" title="The lead time set on the preferred vendor">[lead time]</span>
									<span class="lead-time-qty" title="The qty needed to cover the leadtime">[lead time qty]</span>
									<span class="pre-lead-time-available" title="The total quantity available prior to getting more in from the vendor">[pre-lead-time availbale]</span><br>
									<span class="available" title="The on hand, unheld, unallocated stock">[available]</span>
									<span class="on-order" title="The on order qty used here is what will arrive *before* the lead time on the preferred vendor">[on order]</span>
									<span class="conditioning" title="On hold qtys in conditioning are considered available">[in conditioning]</span>
								</div>
								<div class="formula">
									<hr>
									<span class="available"><?= $ipn_frcst['available_quantity']; ?></span><br>
									<span class="on-order"><?= $ipn_frcst['on_order']; ?></span><br>
									+ <span class="conditioning"><?= $ipn_frcst['quarantine_available_qty']; ?></span><br>
									<hr class="formula-separator"><div class="clearfix"></div>
									<span class="pre-lead-time-available"><?= $ipn_frcst['available_quantity'] + $ipn_frcst['on_order'] + $ipn_frcst['quarantine_available_qty']; ?></span><br>
									<hr>
									<span class="target"><?= $ipn_frcst['initial_target_qty']; ?></span><br>
									- <span class="pre-lead-time-available"><?= $ipn_frcst['available_quantity'] + $ipn_frcst['on_order'] + $ipn_frcst['quarantine_available_qty']; ?></span><br>
									<hr class="formula-separator"><div class="clearfix"></div>
									<?= $ipn_frcst['initial_target_qty'] - ($ipn_frcst['available_quantity'] + $ipn_frcst['on_order'] + $ipn_frcst['quarantine_available_qty']); ?><br>
									<hr>
									<?= $ipn_frcst['initial_target_qty'] - ($ipn_frcst['available_quantity'] + $ipn_frcst['on_order'] + $ipn_frcst['quarantine_available_qty']); ?><br>
									+ <span class="lead-time-qty"><?= ceil($ipn_frcst['lead_time'] * $single_day); ?></span><br>
									<hr class="formula-separator"><div class="clearfix"></div>
									<?= $ipn_frcst['initial_target_qty'] - ($ipn_frcst['available_quantity'] + $ipn_frcst['on_order'] + $ipn_frcst['quarantine_available_qty']) + ceil($ipn_frcst['lead_time'] * $single_day); ?>
									<hr>
									(reorder qty needed prior to <span class="lead-time"><?= $ipn_frcst['lead_factor']; ?></span> days)
									<hr>
								</div>
							</td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Target Buy Price:</td>
							<td nowrap class="main" valign="top"><?= CK\text::monetize($ipn_header['target_buy_price']); ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Target Minimum Quantity On Hand:</td>
							<td class="main" valign="top"><?= $ipn_header['target_min_qty']; ?></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Target Maximum Quantity On Hand:</td>
							<td class="main" valign="top"><?= $ipn_header['target_max_qty']; ?></td>
						</tr>

						<tr><td colspan="2">&nbsp;</td></tr>
						<tr>
							<td nowrap class="main" valign="top">Dropship IPN:</td>
							<td class="main" valign="top"><input type="checkbox" name="drop_ship" <?= $ipn->is('drop_ship')?'checked':''; ?>></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Non-stock IPN:</td>
							<td class="main" valign="top"><input type="checkbox" name="non_stock" <?= $ipn->is('non_stock')?'checked':''; ?>></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">Freight (ineligible for free shipping):</td>
							<td class="main" valign="top"><input type="checkbox" name="freight" <?= $ipn->is('freight')?'checked':''; ?>></td>
						</tr>

						<tr>
							<td nowrap class="main" valign="top">Direct Link Admin Only Products:</td>
							<td class="main" valign="top"><input type="checkbox" name="dlao_product" <?= $ipn->is('dlao_product')?'checked':''; ?>></td>
						</tr>

						<tr>
							<td nowrap class="main" valign="top">Special Order Only:</td>
							<td class="main" valign="top"><input type="checkbox" name="special_order_only" <?= $ipn->is('special_order_only')?'checked':''; ?>></td>
						</tr>

						<tr>
							<td nowrap class="main" valign="top">Total Weight (w/included options):</td>
							<td class="main" valign="top">
								<?= $ipn->get_total_weight(); ?>&nbsp;
								<span style="font-size: 9px;">(Last manual confirmation on <?= !empty($ipn_header['last_weight_change'])?$ipn_header['last_weight_change']->format('m/d/y'):'Never'; ?>)</span>
							</td>
						</tr>

						<tr><td colspan="2">&nbsp;</td></tr>
						<tr>
							<td nowrap class="main" valign="top">Preferred Vendor</td>
							<td class="main" valign="top"><?= $ipn_header['vendors_company_name']; ?></td>
						</tr>

						<tr><td colspan="2">&nbsp;</td></tr>
						<tr>
							<td nowrap class="main" valign="top">ECCN Code:</td>
							<td class="main" valign="top"><input type="text" name="eccn_code" value="<?= $ipn_header['eccn_code']; ?>" maxlength="5"></td>
						</tr>
						<tr>
							<td nowrap class="main" valign="top">HTS Code:</td>
							<td class="main" valign="top"><input type="text" name="hts_code" value="<?= $ipn_header['hts_code']; ?>" maxlength="10"></td>
						</tr>

						<tr><td colspan="2">&nbsp;</td></tr>
						<tr>
							<td nowrap class="main" valign="top">Stock ID:</td>
							<td class="main" valign="top"><?= $ipn->id(); ?></td>
						</tr>
						<tr><td colspan="2">&nbsp;</td></tr>
					</tbody>
				</table>
				<input type="submit" value="Save changes">
			</form>
		</td>
		<td valign="top">
			<style>
				.update-box { border: 1px solid black; font-family:verdana; font-size: 10px; padding: 5px; }
			</style>
			<?php if ($admin->has_legacy_permission_for('update_ipn_quantity')) { ?>
			<div class="update-box">
				<b>Manual Quantity Update:</b><br><br>
				<form id="quantity_update" name="quantity_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="quantity_update">
					<input type="hidden" id="old-stock-qty" value="<?= $inventory['on_hand']; ?>">
					<?php if (!$ipn->is('serialized')) { ?>
					Change Quantity By:&nbsp;&nbsp;<input type="text" id="quantity_change" name="quantity" value="0"><br><br>
					&nbsp;&nbsp;<input type="radio" name="update_direction" value="increase">&nbsp;Increase<br>
					&nbsp;&nbsp;<input type="radio" id="update_direction_decrease" name="update_direction" value="decrease">&nbsp;Decrease<br><br>
					&nbsp;&nbsp;<input type="checkbox" name="quantity_confirmed">&nbsp;Manually Confirmed<br><br>
					<input type="submit" value="Update Quantity">
					<?php }
					else { ?>
					<input type="hidden" name="quantity_confirmed" value="on">
					<input type="submit" value="Confirm Quantity">
					<?php } ?>
				</form>
			</div>
			<br>
			<?php }
			if ($ipn->is('is_bundle')) { ?>
				<div class="update-box">
					<b>Bundle Price Settings:</b>
					<form name="bundle_pricing_impact" id="bundle-pricing-impact-form" method="post" action="/admin/ipn_editor.php">
						<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
						<input type="hidden" name="action" value="set-bundle-pricing-impact">
						<div>
							<div><i>Retail based on Included Products:</i> <?= CK\text::monetize($ipn->get_default_listing()->get_price('bundle_original')); ?></div>
							<div><i>Reseller Price Based on Included Products:</i> <?= CK\text::monetize($ipn->get_default_listing()->get_price('bundle_dealer')); ?></div>
							<div><i>Wholesale High Price Based on Included Products:</i> <?= CK\text::monetize($ipn->get_default_listing()->get_price('bundle_wholesale_high')); ?></div>
							<div><i>Wholesale Low Price Based on Included Products:</i> <?= CK\text::monetize($ipn->get_default_listing()->get_price('bundle_wholesale_low')); ?></div>
						</div>
						<div>
							<label for="pricing-flow">Set Bundle Pricing</label>
							<select name="pricing_flow" id="pricing-flow">
								<option value="0" <?= $ipn->get_header('bundle_price_flows_from_included_products')==0?'selected':'';?>>Directly</option>
								<option value="1" <?= $ipn->get_header('bundle_price_flows_from_included_products')==1?'selected':'';?>>Option Flow, % Modifier</option>
								<option value="2" <?= $ipn->get_header('bundle_price_flows_from_included_products')==2?'selected':'';?>>Option Flow, $ Modifier</option>
							</select>
						</div>
						<div>
							<label for="price_modifier">Price Modifier</label>
							<input type="text" name="price_modifier" placeholder="<?= !empty($ipn->get_header('bundle_price_modifier'))?$ipn->get_header('bundle_price_modifier'):'e.g 10'; ?>" value="<?= $ipn->get_header('bundle_price_modifier'); ?>">
							<select name="signum" id="signum">
								<option value="0" <?= $ipn->get_header('bundle_price_signum')==0?'selected':''; ?>>Discount</option>
								<option value="1" <?= $ipn->get_header('bundle_price_signum')==1?'selected':''; ?>>Upcharge</option>
							</select>
						</div>
						<button type="submit">Set Pricing</button>
					</form>
				</div>
				<br>
			<?php }

			if (!empty($admin->has_legacy_permission_for('update_ipn_weight')) && !$ipn->is('is_bundle')) { ?>
			<div class="update-box">
				<b>Weight Update:</b><br><br>
				<form name="weight_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="weight_update">
					Weight:&nbsp;&nbsp;<input type="text" name="weight" value="<?= $ipn_header['stock_weight']; ?>"><br><br>
					&nbsp;&nbsp;<input type="checkbox" name="weight_confirmed">&nbsp;Manually Confirmed<br><br>
					<input type="submit" value="Update Weight">
				</form>
			</div>
			<br>
			<?php }

			if (!empty($admin->has_legacy_permission_for('update_ipn_average_cost'))) {
				if (!$ipn->is('serialized')) { ?>
			<div class="update-box">
				<b>Average Cost Update:</b><br><br>
				<form name="weight_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="average_cost_update">
					Average Cost:&nbsp;&nbsp;<input type="text" name="average_cost" value="<?= $ipn_header['average_cost']; ?>"><br><br>
					<input type="submit" value="Update Average Cost">
				</form>
			</div>
			<br>
				<?php } ?>
			<div class="update-box">
				<b>Target Buy Price Update:</b><br><br>
				<form name="tbp_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="target_buy_price_update">
					Target Buy Price:&nbsp;&nbsp;<input type="text" name="target_buy_price" value="<?= $ipn_header['target_buy_price']; ?>"><br><br>
					<input type="submit" value="Update/confirm Target Buy Price">
				</form>
			</div>
			<br>
			<?php }

			if (!empty($admin->has_legacy_permission_for('rename_ipn'))) { ?>
			<div class="update-box">
				<b>Rename IPN:</b><br><br>
				<form name="name_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="name_update">
					Name:&nbsp;&nbsp;<input type="text" name="stock_name" value="<?= $ipn_header['ipn']; ?>"><br><br>
					<input type="submit" value="Update Name">
				</form>
			</div>
			<br>
			<?php }

			if (!empty($admin->has_legacy_permission_for('update_target_min_qty'))) { ?>
			<div class="update-box">
				<b>Target Min Qty:</b><br><br>
				<form name="target_min_qty_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="target_min_qty_update">
					Target Min Qty:&nbsp;&nbsp;<input type="text" name="target_min_qty" value="<?= $ipn_header['target_min_qty']; ?>"><br><br>
					<input type="submit" value="Update Target Min Qty">
				</form>
			</div>
			<br>
			<?php }

			if (!empty($admin->has_legacy_permission_for('update_target_max_qty'))) { ?>
			<div class="update-box">
				<b>Target Max Qty:</b><br><br>
				<form name="target_max_qty_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="target_max_qty_update">
					Target Max Qty:&nbsp;&nbsp;<input type="text" name="target_max_qty" value="<?= $ipn_header['target_max_qty']; ?>"><br><br>
					<input type="submit" value="Update Target Max Qty">
				</form>
			</div>
			<br>
			<?php }

			if (!empty($admin->has_legacy_permission_for('mark_as_reviewed'))) { ?>
			<div class="update-box">
				<b>Mark As Reviewed:</b><br><br>
				<form name="mark_as_reviewed_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="mark_as_reviewed_update">
					Review includes:<br>
					<ul>
						<li>Evaluate Sales Volume</li>
						<li>Compare Price to Competition</li>
						<li>Review Product Description</li>
						<li>Review Short Description and Title</li>
						<li>Review eBay title and eBay completed sales</li>
						<li>Review Product Keywords.</li>
					</ul>
					<input type="submit" value="Mark as reviewed">
				</form>
			</div>
			<br>
			<?php }

			if (!empty($admin->has_legacy_permission_for('change_ipn_category'))) { ?>
			<div class="update-box">
				<b>Category:</b><br><br>
				<form name="category_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="category_update">
					Inventory Category:
					<select name="category_id">
						<option >Not Set</option>
						<?php foreach ($ipn_categories as $cat) { ?>
						<option value="<?= $cat['categories_id']; ?>" <?= $ipn_header['products_stock_control_category_id']==$cat['categories_id']?'selected':''; ?>><?= $cat['name']; ?></option>
						<?php } ?>
					</select>
					<input type="submit" value="Update Category">
				</form>
			</div>
			<br>
			<?php }

			if (!empty($admin->has_legacy_permission_for('change_warranties'))) { ?>
			<div class="update-box">
				<b>Warranties:</b><br><br>
				<form name="warranty_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="warranty_update">
					Warranty:&nbsp;&nbsp;
					<select name="warranty">
						<option >Not Set</option>
						<?php foreach ($warranties as $warranty) { ?>
						<option value="<?= $warranty['warranty_id']; ?>" <?= $ipn_header['warranty_id']==$warranty['warranty_id']?'selected':''; ?> ><?= $warranty['warranty_name']; ?></option>
						<?php } ?>
					</select>
					<br><br>
					<input type="submit" value="Update Warranties">
				</form>
			</div>
			<br>
			<?php }

			if (!empty($admin->has_legacy_permission_for('change_dealer_warranties'))) { ?>
			<div class="update-box">
				<b>Dealer Warranties:</b><br><br>
				<form name="dealer_warranty_update" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" value="dealer_warranty_update">
					Dealer Warranty:&nbsp;&nbsp;
					<select name="dealer_warranty">
						<?php foreach ($dealer_warranties as $dealer_warranty) { ?>
						<option value="<?= $dealer_warranty['dealer_warranty_id']; ?>" <?= $ipn_header['dealer_warranty_id']==$dealer_warranty['dealer_warranty_id']?'selected':''; ?>><?= $dealer_warranty['dealer_warranty_name']; ?></option>
						<?php } ?>
					</select>
					<br><br>
					<input type="submit" value="Update Dealer Warranties">
				</form>
			</div>
			<br>
			<?php }

			if ($admin->is_top_admin()) { ?>
			<div class="update-box">
				<b>Delete:</b> delete the ipn from the database.<br><br>
				<form name="stock_delete" id="stock_delete" action="/admin/ipn_editor.php" method="post">
					<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" name="action" value="save_general">
					<input type="hidden" name="sub-action" name="action" value="delete">
					<input type="submit" value="Delete Stock Item">
				</form>
				<script>
					jQuery('#stock_delete').submit(function(e) {
						if (!confirm('Are you sure you want to delete this IPN')) e.preventDefault();
					});
				</script>
			</div>
			<?php } ?>
			<br>
		</td>
	</tr>
</table>
<script>
	jQuery('#is_package').click(function() {
		jQuery('.package_attributes').toggleClass('package_toggle');
		jQuery('.package_attributes').attr('required');
	});

	jQuery('#save_general').submit(function(e) {
		if (!jQuery('#discontinued').is(':checked') && (parseInt(jQuery('#min_inventory_level').val()) <= 0 || parseInt(jQuery('#target_inventory_level').val()) <= 0 || parseInt(jQuery('#max_inventory_level').val()) <= 0)) {
			alert('The values for Minimum, Target, and Maximum Inventory Levels must be greater than 0');
			e.preventDefault();
		}
	});

	jQuery('#quantity_update').submit(function(e) {
		if (jQuery('#update_direction_decrease').is(':checked')) {
			var old_qty = jQuery('#old-stock-qty').val();
			var change_qty = jQuery('#quantity_change').val();
			if (parseInt(change_qty) > parseInt(old_qty)) {
				alert('The quantity change you specified would result in a negative quantity on hand for this IPN. Please correct this number and try again.');
				e.preventDefault();
			}
		}
	});

	jQuery('.explain-reorder').click(function(e) {
		e.preventDefault();
		jQuery('.explain-reorder-details').toggle();
	});
	
	jQuery('#change-frequency-checkbox').on('click', function () {
		if (jQuery(this).prop('checked') == true) {
			jQuery('#pricing_review').show();
			jQuery('#pricing_review').prop('disabled', false);
			jQuery('#pr_use_default').show();
		}
		else {
			jQuery('#pricing_review').show();
			jQuery('#pricing_review').prop('disabled', 'disabled');
			jQuery('#pr_use_default').hide();
		}
	});
	
	jQuery('#pr_use_default').on('click', function () {
		jQuery('#pricing_review').val('0');
		jQuery('#pricing_review').hide();
		jQuery('#pr_default_text').show();
	});

	jQuery('#mark-creation-review').on('click', function () {
		jQuery.ajax({
			url: '/admin/ipn-creation-review-dashboard',
			method: 'POST',
			dataType: 'json',
			data: { ajax:1, action:'mark_creation_reviewed', stock_id:jQuery(this).data('stock-id') },
			success: function (data) {
				if (data) jQuery('#mark-creation-reviewed-tr').remove();
			}
		})
	});
</script>
