<?php
class item_popup {
	private $db;

	private $counter;
	private $display;

	private $ipn;
	private $stock_id;
	private $products_id;
	private $model_number;

	private $output = '';

	// match a value that may come in with its actual database column name
	private $aliases = array(
		'ipn' => 'stock_name',
		'product_id' => 'products_id',
		'model' => 'products_model',
		'model_number' => 'products_model',
		'ipn_id' => 'stock_id' // this one may or may not be the correct mapping in any given scenario, it may actually map to 'stock_name', but we don't really need to be that flexible
	);

	public function __construct($display, $db, $details=array()) {
		if (!isset($GLOBALS['item_popup_counter']) || !$GLOBALS['item_popup_counter']) {
			echo '<script src="includes/javascript/item_popup.js?v=1.1.0"></script>';
			$GLOBALS['item_popup_counter'] = 0;
		}
		$this->counter = ++$GLOBALS['item_popup_counter'];

		$this->display = $display;
		if ($details) $this->show($details);
	}

	public function __toString() {
		return $this->output;
	}

	public function show($details) {
		$data = $this->build_data($details);
		if (empty($data)) {
			$this->output = $this->display;
			return FALSE;
		}

		ob_start(); ?>
	<div id="item_popup_<?php echo $this->counter; ?>" class="item_popup" data-popctr="0" data-lockctr="0" data-inctx="0" data-allhalt="0"><div class="spcr">&Xi;&nbsp;<?php echo $this->display; ?>&nbsp;&Xi;</div><div class="ctrl"><span class="lock">[L]</span><br/><span class="close">[X]</span></div><div class="status"> </div></div>
	<div class="item_popup_details item_popup_<?php echo $this->counter; ?>">
		<div class="item_popup_links">
			<a href="/product_info.php?products_id=<?= $data['products_id']; ?>" target="_blank">[MODEL: <?= $data['model_number']; ?>]</a> |
			<a href="/admin/ipn_editor.php?ipnId=<?= urlencode($data['ipn']); ?>" target="_blank">[IPN: <?= $data['ipn']; ?>]</a>
		</div>
		<div class="item_popup_imgs">
			<div class="context_image">
				<img src="https://media.cablesandkits.com/<?= $data['products_image_lrg']; ?>" class="in-context imgpop_<?php echo $this->counter; ?>_0"/>
				<?php // there are anywhere from 1 to 6 images in the carousel at the moment.
				for ($i=1; $i<=6; $i++) {
					if (trim($data['products_image_sm_'.$i])) { ?>
				<img src="https://media.cablesandkits.com/<?php echo $data['products_image_xl_'.$i]; ?>" class="imgpop_<?php echo $this->counter; ?>_<?= $i; ?>"/>
					<?php }
				} ?>
			</div>
			<div class="carousel">
				<img src="https://media.cablesandkits.com/<?= $data['products_image']; ?>" class="in-context" data-target="imgpop_<?php echo $this->counter; ?>_0"/>
				<?php // there are anywhere from 1 to 6 images in the carousel at the moment.
				for ($i=1; $i<=6; $i++) {
					if (trim($data['products_image_sm_'.$i])) { ?>
				<br/><img src="https://media.cablesandkits.com/<?php echo $data['products_image_sm_'.$i]; ?>" data-target="imgpop_<?php echo $this->counter; ?>_<?= $i; ?>"/>
					<?php }
				} ?>
			</div>
		</div>
	</div><div class="image_popup_flow">&nbsp;</div>
		<?php
		$this->output = ob_get_clean();
		return TRUE;
	}

	private function build_data($details) {
		foreach ($details as $key => $val) {
			if (isset($this->aliases[$key]) && !isset($details[$this->aliases[$key]])) $details[$this->aliases[$key]] = $val;
		}
		if (empty($details['stock_name']) && empty($details['stock_id']) && empty($details['products_id'])) {
			return array();
		}

		if (!empty($details['products_id'])) {
			return prepared_query::fetch('SELECT p.products_id, p.products_model as model_number, p.products_image, p.products_image_med, p.products_image_lrg, p.products_image_sm_1, p.products_image_xl_1, p.products_image_sm_2, p.products_image_xl_2, p.products_image_sm_3, p.products_image_xl_3, p.products_image_sm_4, p.products_image_xl_4, p.products_image_sm_5, p.products_image_xl_5, p.products_image_sm_6, p.products_image_xl_6, pd.products_name, psc.stock_id, psc.stock_name as ipn, psc.stock_description as ipn_name FROM products p JOIN products_description pd ON p.products_id = pd.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE p.products_id = :products_id', cardinality::ROW, [':products_id' => $details['products_id']]);
		}
		elseif (!empty($details['stock_id'])) {
			return prepared_query::fetch('SELECT p.products_id, p.products_model as model_number, p.products_image, p.products_image_med, p.products_image_lrg, p.products_image_sm_1, p.products_image_xl_1, p.products_image_sm_2, p.products_image_xl_2, p.products_image_sm_3, p.products_image_xl_3, p.products_image_sm_4, p.products_image_xl_4, p.products_image_sm_5, p.products_image_xl_5, p.products_image_sm_6, p.products_image_xl_6, pd.products_name, psc.stock_id, psc.stock_name as ipn, psc.stock_description as ipn_name FROM products p JOIN products_description pd ON p.products_id = pd.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE psc.stock_id = :stock_id ORDER BY p.products_id', cardinality::ROW, [':stock_id' => $details['stock_id']]);
		}
		else { // stock_name
			return prepared_query::fetch('SELECT p.products_id, p.products_model as model_number, p.products_image, p.products_image_med, p.products_image_lrg, p.products_image_sm_1, p.products_image_xl_1, p.products_image_sm_2, p.products_image_xl_2, p.products_image_sm_3, p.products_image_xl_3, p.products_image_sm_4, p.products_image_xl_4, p.products_image_sm_5, p.products_image_xl_5, p.products_image_sm_6, p.products_image_xl_6, pd.products_name, psc.stock_id, psc.stock_name as ipn, psc.stock_description as ipn_name FROM products p JOIN products_description pd ON p.products_id = pd.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE psc.stock_name LIKE :stock_name ORDER BY p.products_id', cardinality::ROW, [':stock_name' => $details['stock_name']]);
		}
	}
}
?>
