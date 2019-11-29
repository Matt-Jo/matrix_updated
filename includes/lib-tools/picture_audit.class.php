<?php
class picture_audit {

	public $stock_id;
	public $ipn;
	public $category;
	public $initial_problems = array('pic_audit' => NULL, 'pic_problem' => NULL);
	private $pic_base;
	private $products = array();
	public $prod_count = 0;
	public $status_count = 0;

	public static $imgfolder;

	public $problems = array(
		'naming_ipn' => FALSE,
		'naming_slot' => FALSE,
		'naming_path' => FALSE,
		'naming_uppercase_ext' => FALSE,
		'naming_product_consistency' => FALSE,
		'naming_off_product_consistency' => FALSE,

		'naming_child_preferred' => FALSE, // if the child products are named correctly but the parent IPN data is flawed, note that

		'slot_gaps' => FALSE,
		'newproduct' => FALSE,

		'broken_reference' => FALSE,
		'wrong_dimensions' => FALSE,
		//'missing_300_size' => FALSE,
		'missing_archive' => FALSE,
		'archive_dimensions' => FALSE
	);

	// here's a few defined conventions that we use for our images
	public static $replacements = array(
		// these are characters that, if the image name doesn't match the IPN, we can replace one filesystem friendly character with a FS unfriendly character to see if it will now match
		'$' => '/'
	);
	public static $PATH = 'p';

	// this gives us an easy loop control
	public static $image_fields = array(
		'image' => array(
			'slot' => 'a',
			'size' => 'sm'
		),
		'image_med' => array(
			'slot' => 'a',
			'size' => 'med'
		),
		'image_lrg' => array(
			'slot' => 'a',
			'size' => 'lrg'
		),
		'image_sm_1' => array(
			'slot' => 'b',
			'size' => 'sm'
		),
		'image_xl_1' => array(
			'slot' => 'b',
			'size' => 'lrg'
		),
		'image_sm_2' => array(
			'slot' => 'c',
			'size' => 'sm'
		),
		'image_xl_2' => array(
			'slot' => 'c',
			'size' => 'lrg'
		),
		'image_sm_3' => array(
			'slot' => 'd',
			'size' => 'sm'
		),
		'image_xl_3' => array(
			'slot' => 'd',
			'size' => 'lrg'
		),
		'image_sm_4' => array(
			'slot' => 'e',
			'size' => 'sm'
		),
		'image_xl_4' => array(
			'slot' => 'e',
			'size' => 'lrg'
		),
		'image_sm_5' => array(
			'slot' => 'f',
			'size' => 'sm'
		),
		'image_xl_5' => array(
			'slot' => 'f',
			'size' => 'lrg'
		),
		'image_sm_6' => array(
			'slot' => 'g',
			'size' => 'sm'
		),
		'image_xl_6' => array(
			'slot' => 'g',
			'size' => 'lrg'
		),
	);

	public static function field_list($source='ipn') {
		$list = array_keys(self::$image_fields);
		if (in_array(strtolower($source), array('product', 'products'))) {
			$list = array_map(function($field) { return 'products_'.$field; }, $list);
		}
		return $list;
	}

	// this is copied from the auto-image manager for uploading images... we may not need the full structure as-is, but we can use it for certain checks
	public static $image_slots = array(
		'a' => array(
			'p' => array(
				'sm' => 'products_image',
				'med' => 'products_image_med',
				'lrg' => 'products_image_lrg'
			),
			'ipn' => array(
				'sm' => 'image',
				'med' => 'image_med',
				'lrg' => 'image_lrg'
			)
		),
		'b' => array(
			'p' => array(
				'sm' => 'products_image_sm_1',
				'lrg' => 'products_image_xl_1'
			),
			'ipn' => array(
				'sm' => 'image_sm_1',
				'lrg' => 'image_xl_1'
			)
		),
		'c' => array(
			'p' => array(
				'sm' => 'products_image_sm_2',
				'lrg' => 'products_image_xl_2'
			),
			'ipn' => array(
				'sm' => 'image_sm_2',
				'lrg' => 'image_xl_2'
			)
		),
		'd' => array(
			'p' => array(
				'sm' => 'products_image_sm_3',
				'lrg' => 'products_image_xl_3'
			),
			'ipn' => array(
				'sm' => 'image_sm_3',
				'lrg' => 'image_xl_3'
			)
		),
		'e' => array(
			'p' => array(
				'sm' => 'products_image_sm_4',
				'lrg' => 'products_image_xl_4'
			),
			'ipn' => array(
				'sm' => 'image_sm_4',
				'lrg' => 'image_xl_4'
			)
		),
		'f' => array(
			'p' => array(
				'sm' => 'products_image_sm_5',
				'lrg' => 'products_image_xl_5'
			),
			'ipn' => array(
				'sm' => 'image_sm_5',
				'lrg' => 'image_xl_5'
			)
		),
		'g' => array(
			'p' => array(
				'sm' => 'products_image_sm_6',
				'lrg' => 'products_image_xl_6'
			),
			'ipn' => array(
				'sm' => 'image_sm_6',
				'lrg' => 'image_xl_6'
			)
		)
	);

	private static $base_stmt;
	private static $prod_stmt;

	public function __construct($stock_id) {
		$this->stock_id = $stock_id;

		$this->pic_base = prepared_query::fetch('SELECT psc.stock_name, psc.pic_audit, psc.pic_problem, psci.*, pscc.name as category FROM products_stock_control psc LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id WHERE psc.stock_id = :stock_id', cardinality::ROW, [':stock_id' => $this->stock_id]);

		$this->ipn = $this->pic_base['stock_name'];
		$this->category = $this->pic_base['category'];

		$this->initial_problems['pic_audit'] = $this->pic_base['pic_audit'];
		$this->initial_problems['pic_problem'] = $this->pic_base['pic_problem'];

		$this->products = prepared_query::fetch('SELECT * FROM products WHERE stock_id = :stock_id', cardinality::SET, [':stock_id' => $this->stock_id]);

		foreach ($this->products as $idx => $product) {
			$this->prod_count++;
			if ($product['products_status'] == 1) $this->status_count++; // we have at least one product turned on
		}
	}

	public function check_naming() {
		$naming_problem = FALSE;

		$field_filled = TRUE;
		$expect_next_field = TRUE; // we begin by expeting the first image to have something, even if it's the newproduct.gif
		foreach (self::$image_fields as $field => $details) {
			if (empty($this->pic_base[$field])) {
				$field_filled = FALSE;

				// if we have the small image but not the large image then that's a problem, the large image without the small image already gets caught below
				if ($expect_next_field) $naming_problem = $this->problems['slot_gaps'] = TRUE;
			}
			elseif (empty($field_filled)) {
				// we found picture data in a later field after an earlier field was blank
				$naming_problem = $this->problems['slot_gaps'] = TRUE;
			}
			elseif (preg_match('/^image_(sm|med)/', $field)) $expect_next_field = TRUE;
			else $expect_next_field = FALSE;

			if (preg_match('/newproduct/', $this->pic_base[$field])) $naming_problem = $this->problems['newproduct'] = TRUE;

			if (!empty($this->pic_base[$field])) {
				$image = $this->file_parts($this->pic_base[$field]);

				if ($image['path'] != self::$PATH) $naming_problem = $this->problems['naming_path'] = TRUE;
				if ($image['ext'] == strtoupper($image['ext'])) $naming_problem = $this->problems['naming_uppercase_ext'] = TRUE;
				if (empty($image['slot']) || $image['slot'] != $details['slot']) $naming_problem = $this->problems['naming_slot'] = TRUE;
				if ($image['ipn'] != $this->ipn && $this->massage_ipn($image['ipn']) != $this->ipn) {
					$naming_problem = $this->problems['naming_ipn'] = TRUE;
					//echo '['.$field.'] ['.$this->pic_base[$field].'] ['.$image['ipn'].'] ['.$this->massage_ipn($image['ipn']).'] ['.$this->ipn.'] ['.($this->massage_ipn($image['ipn']) != $this->ipn).']<br>';
				}
			}

			foreach ($this->products as $idx => $product) {
				// if we're consistent, then no need to bother checking further on this front
				if ($this->pic_base[$field] == $product['products_'.$field]) continue;

				if ($product['products_status'] == 1) $naming_problem = $this->problems['naming_product_consistency'] = TRUE;
				else $naming_problem = $this->problems['naming_off_product_consistency'] = TRUE;

				if (preg_match('/newproduct/', $product['products_'.$field])) $naming_problem = $this->problems['newproduct'] = TRUE;
				elseif ($this->problems['naming_ipn']) {
					$products_image = $this->file_parts($product['products_'.$field]);

					if ($products_image['ipn'] == $this->ipn || $this->massage_ipn($products_image['ipn']) == $this->ipn) $naming_problem = $this->problems['naming_child_preferred'];
				}
			}
		}

		return !$naming_problem;
	}

	public function file_parts($file) {
		$file = trim($file);
		if (empty($file)) return NULL;

		$result = array('path' => NULL, 'filename' => NULL, 'ipn' => NULL, 'slot' => NULL, 'size' => NULL, 'ext' => NULL);
		$parts = pathinfo($file);

		$result['path'] = $parts['dirname'];
		$result['filename'] = $parts['filename'];
		$result['ext'] = @$parts['extension'];

		preg_match('/^(.+?)([a-g])(_(300|med|sm))?$/', $parts['filename'], $matches);
		if (!empty($matches)) {
			$result['ipn'] = $matches[1];
			$result['slot'] = $matches[2];
			if (!empty($matches[4])) $result['size'] = $matches[4];
		}
		else {
			$result['ipn'] = $parts['filename'];
			// we don't recognize the format
		}

		return $result;
	}

	private function massage_ipn($ipn) {
		foreach (self::$replacements as $from => $to) {
			if (!preg_match('/\\'.$from.'/', $ipn)) continue;

			$ipn = preg_replace('/\\'.$from.'/', $to, $ipn);
		}

		return $ipn;
	}

	public function check_filesystem() {
		$fs_problem = FALSE;
		foreach ($this->products as $product) {
			foreach (self::$image_fields as $field => $details) {
				$products_field = 'products_'.$field;

				if (empty($product[$products_field])) continue;
				if (preg_match('/newproduct/', $product[$products_field])) continue;

				if (!file_exists(self::$imgfolder.'/'.$product[$products_field])) $fs_problem = $this->problems['broken_reference'] = TRUE;
				else {
					$dim = imagesizer::dim(self::$imgfolder.'/'.$product[$products_field]);
					if ($dim['width'] != imagesizer::$map[$details['size']]['width'] || $dim['height'] != imagesizer::$map[$details['size']]['height'])
						$fs_problem = $this->problems['wrong_dimensions'] = TRUE;
				}

				if ($details['size'] == 'lrg') {
					//if (!file_exists(self::$imgfolder.'/'.imagesizer::ref_300($product[$products_field]))) $fs_problem = $this->problems['missing_300_size'] = TRUE;

					$img = $this->file_parts($product[$products_field]);

					if (!file_exists(self::$imgfolder.'/archive/'.$img['filename'].'.'.$img['ext'])) $fs_problem = $this->problems['missing_archive'] = TRUE;
					else {
						$dim = imagesizer::dim(self::$imgfolder.'/archive/'.$img['filename'].'.'.$img['ext']);
						if ($dim['width'] != imagesizer::$map['archive']['width'] || $dim['height'] != imagesizer::$map['archive']['height'])
							$fs_problem = $this->problems['archive_dimensions'] = TRUE;
					}
				}
			}
		}

		return !$fs_problem;
	}

	public function show_records() { ?>
		<table cellpadding="0" cellspacing="0" border="0" class="show-db-details">
			<thead>
				<tr>
					<th>NAME</th>
					<th>ON?</th>
					<th>A SM</th>
					<th>A MED</th>
					<th>A LRG</th>
					<th>B SM</th>
					<th>B LRG</th>
					<th>C SM</th>
					<th>C LRG</th>
					<th>D SM</th>
					<th>D LRG</th>
					<th>E SM</th>
					<th>E LRG</th>
					<th>F SM</th>
					<th>F LRG</th>
					<th>G SM</th>
					<th>G LRG</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th><?php echo $this->ipn; ?></th>
					<th></th>
					<th><?php echo $this->pic_base['image']; ?><br>[<a href="/images/<?php echo $this->pic_base['image']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_med']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_med']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_med']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_lrg']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_lrg']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_lrg']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_sm_1']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_sm_1']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_sm_1']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_xl_1']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_xl_1']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_xl_1']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_sm_2']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_sm_2']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_sm_2']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_xl_2']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_xl_2']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_xl_2']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_sm_3']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_sm_3']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_sm_3']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_xl_3']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_xl_3']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_xl_3']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_sm_4']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_sm_4']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_sm_4']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_xl_4']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_xl_4']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_xl_4']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_sm_5']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_sm_5']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_sm_5']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_xl_5']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_xl_5']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_xl_5']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_sm_6']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_sm_6']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_sm_6']; ?>" target="_blank"> CDN </a>]</th>
					<th><?php echo $this->pic_base['image_xl_6']; ?><br>[<a href="/images/<?php echo $this->pic_base['image_xl_6']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?php echo $this->pic_base['image_xl_6']; ?>" target="_blank"> CDN </a>]</th>
				</tr>
				<?php foreach ($this->products as $product) { ?>
				<tr>
					<td><?= $product['products_model']; ?></td>
					<td><?php echo $product['products_status']?'ON':'OFF'; ?></td>
					<td><?= $product['products_image']; ?><br>[<a href="/images/<?= $product['products_image']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_med']; ?><br>[<a href="/images/<?= $product['products_image_med']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_med']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_lrg']; ?><br>[<a href="/images/<?= $product['products_image_lrg']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_lrg']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_sm_1']; ?><br>[<a href="/images/<?= $product['products_image_sm_1']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_sm_1']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_xl_1']; ?><br>[<a href="/images/<?= $product['products_image_xl_1']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_xl_1']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_sm_2']; ?><br>[<a href="/images/<?= $product['products_image_sm_2']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_sm_2']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_xl_2']; ?><br>[<a href="/images/<?= $product['products_image_xl_2']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_xl_2']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_sm_3']; ?><br>[<a href="/images/<?= $product['products_image_sm_3']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_sm_3']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_xl_3']; ?><br>[<a href="/images/<?= $product['products_image_xl_3']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_xl_3']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_sm_4']; ?><br>[<a href="/images/<?= $product['products_image_sm_4']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_sm_4']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_xl_4']; ?><br>[<a href="/images/<?= $product['products_image_xl_4']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_xl_4']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_sm_5']; ?><br>[<a href="/images/<?= $product['products_image_sm_5']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_sm_5']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_xl_5']; ?><br>[<a href="/images/<?= $product['products_image_xl_5']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_xl_5']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_sm_6']; ?><br>[<a href="/images/<?= $product['products_image_sm_6']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_sm_6']; ?>" target="_blank"> CDN </a>]</td>
					<td><?= $product['products_image_xl_6']; ?><br>[<a href="/images/<?= $product['products_image_xl_6']; ?>" target="_blank"> SVR </a>] [<a href="http://media.cablesandkits.com/<?= $product['products_image_xl_6']; ?>" target="_blank"> CDN </a>]</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php }

	//--------------------------------------

	public function __get($key) {
		if ($key != 'db') return NULL;
		return self::get_db();
	}

	// wrangle the database, if we want to set it explicitly for the call or the class (otherwise, fall back to the global instance)
	protected static $db = NULL;
	public static function set_db($db) {
		static::$db = $db;
	}
	// this allows us to use dependancy injection without requiring it
	protected static function get_db($db=NULL) {
        return $db ?? self::$db ?? service_locator::get_db_service() ?? NULL;
	}

	//--------------------------------------

	// reports happen live, because they're easily accomplished with a few simple queries
	public static function report_list($fs_limit) {
		$field_issues = self::get_db()->fetch_all("SELECT DISTINCT psc.stock_id, psc.stock_name FROM products_stock_control psc JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id WHERE psci.image NOT LIKE CONCAT('p/', psc.stock_name, '%a_sm.jpg') OR psci.image_med NOT LIKE CONCAT('p/', psc.stock_name, '%a_med.jpg') OR psci.image_lrg NOT LIKE CONCAT('p/', psc.stock_name, '%a.jpg') OR psci.image_sm_1 NOT LIKE CONCAT('p/', psc.stock_name, '%b_sm.jpg') OR psci.image_xl_1 NOT LIKE CONCAT('p/', psc.stock_name, '%b.jpg') OR psci.image_sm_2 NOT LIKE CONCAT('p/', psc.stock_name, '%c_sm.jpg') OR psci.image_xl_2 NOT LIKE CONCAT('p/', psc.stock_name, '%c.jpg') OR psci.image_sm_3 NOT LIKE CONCAT('p/', psc.stock_name, '%d_sm.jpg') OR psci.image_xl_3 NOT LIKE CONCAT('p/', psc.stock_name, '%d.jpg') OR psci.image_sm_4 NOT LIKE CONCAT('p/', psc.stock_name, '%e_sm.jpg') OR psci.image_xl_4 NOT LIKE CONCAT('p/', psc.stock_name, '%e.jpg') OR psci.image_sm_5 NOT LIKE CONCAT('p/', psc.stock_name, '%f_sm.jpg') OR psci.image_xl_5 NOT LIKE CONCAT('p/', psc.stock_name, '%f.jpg') OR psci.image_sm_6 NOT LIKE CONCAT('p/', psc.stock_name, '%g_sm.jpg') OR psci.image_xl_6 NOT LIKE CONCAT('p/', psc.stock_name, '%g.jpg')");

		$matching_issues = self::get_db()->fetch_all('SELECT DISTINCT psc.stock_id, psc.stock_name FROM products_stock_control psc JOIN products p ON psc.stock_id = p.stock_id JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id WHERE psci.image != p.products_image OR psci.image_med != p.products_image_med OR psci.image_lrg != p.products_image_lrg OR psci.image_sm_1 != p.products_image_sm_1 OR psci.image_xl_1 != p.products_image_xl_1 OR psci.image_sm_2 != p.products_image_sm_2 OR psci.image_xl_2 != p.products_image_xl_2 OR psci.image_sm_3 != p.products_image_sm_3 OR psci.image_xl_3 != p.products_image_xl_3 OR psci.image_sm_4 != p.products_image_sm_4 OR psci.image_xl_4 != p.products_image_xl_4 OR psci.image_sm_5 != p.products_image_sm_5 OR psci.image_xl_5 != p.products_image_xl_5 OR psci.image_sm_6 != p.products_image_sm_6 OR psci.image_xl_6 != p.products_image_xl_6 ORDER BY psc.stock_id');

		$fs_issues = self::get_db()->fetch_all('SELECT DISTINCT psc.stock_id, psc.stock_name FROM products_stock_control psc WHERE psc.pic_problem = TRUE');

		$result = array();

		$fs_counter = 0;

		foreach ($field_issues as $issue) {
			$audit = new picture_audit($issue['stock_id']);
			$nm = $audit->check_naming();

			$fs_counter++;
			//echo '<br>';
			if ($fs_counter <= $fs_limit) $fs = $audit->check_filesystem();
			else {
				$fs = TRUE;
				$audit->problems['broken_reference'] = 0;
				$audit->problems['wrong_dimensions'] = 0;
				//$audit->problems['missing_300_size'] = 0;
				$audit->problems['missing_archive'] = 0;
			}

			if ($nm && $fs) continue;
			else $result[$issue['stock_id']] = $audit;
		}

		foreach ($matching_issues as $issue) {
			if (isset($result[$issue['stock_id']])) continue;

			$audit = new picture_audit($issue['stock_id']);
			$nm = $audit->check_naming();

			$fs_counter++;
			//echo '<br>';
			if ($fs_counter <= $fs_limit) $fs = $audit->check_filesystem();
			else {
				$fs = TRUE;
				$audit->problems['broken_reference'] = 0;
				$audit->problems['wrong_dimensions'] = 0;
				//$audit->problems['missing_300_size'] = 0;
				$audit->problems['missing_archive'] = 0;
			}

			if ($nm && $fs) continue;
			else $result[$issue['stock_id']] = $audit;
		}

		foreach ($fs_issues as $issue) {
			if (isset($result[$issue['stock_id']])) continue;

			$audit = new picture_audit($issue['stock_id']);
			$nm = $audit->check_naming();

			$fs_counter++;
			//echo '<br>';
			if ($fs_counter <= $fs_limit) $fs = $audit->check_filesystem();
			else {
				$fs = TRUE;
				$audit->problems['broken_reference'] = 0;
				$audit->problems['wrong_dimensions'] = 0;
				//$audit->problems['missing_300_size'] = 0;
				$audit->problems['missing_archive'] = 0;
			}

			if ($nm && $fs) continue;
			else $result[$issue['stock_id']] = $audit;
		}

		usort($result, function($a, $b) { if ($a->ipn == $b->ipn) { return 0; } return ($a->ipn < $b->ipn)?-1:1; });

		return $result;
	}

	//--------------------------------------

	// audits happen on a schedule because they can be more time consuming and resource intensive
	private static $daily_limit = 500;
	public static function audit_list() {
		// ORDER BY - ASC will sort NULL values first
		return self::get_db()->fetch_all('SELECT DISTINCT psc.stock_id, psc.stock_name, psc.pic_audit, psc.pic_problem /*, psci.* */ FROM products_stock_control psc LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id ORDER BY psc.pic_audit ASC LIMIT '.self::$daily_limit);
	}

	private static $audit_checker = array();
	public static function check_audit_loop($stock_id) {
		if (in_array($stock_id, self::$audit_checker)) return TRUE;
		self::$audit_checker[] = $stock_id;
		return FALSE;
	}

	private static $audit_start;
	private static $audit_checkpoint;
	private static $audit_limit = 1200; // if the audit runs in under 20 minutes, we can run another batch

	public static function start_audit() {
		self::$audit_start = time();
	}

	public static function checkpoint_audit() {
		if (empty(self::$audit_start)) {
			self::start_audit();
			return TRUE;
		}

		self::$audit_checkpoint = time(); // we store this just in case we want to use the checkpoint in the future

		if (self::$audit_checkpoint - self::$audit_start < self::$audit_limit) return TRUE;
		else return FALSE;
	}

	public static function record_audit_result($stock_id, $pic_problem=0) {
		prepared_query::execute('UPDATE products_stock_control SET pic_audit = NOW(), pic_problem = ? WHERE stock_id = ?', array($pic_problem, $stock_id));
	}

	//--------------------------------------

	public static function audit_follow_up() {
		$ipns = self::get_db()->fetch_all('SELECT psc.stock_id, psc.stock_name, psc.pic_audit, psc.pic_problem, psci.* FROM products_stock_control psc LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id WHERE psc.pic_problem IS NOT NULL AND psc.pic_problem > 0');

		foreach ($ipns as $ipn) {
			$audit = new picture_audit($ipn['stock_id']);
			if (!$audit->check_filesystem()) {
				picture_audit::record_audit_result($ipn['stock_id'], 1);
			}
			else {
				picture_audit::record_audit_result($ipn['stock_id']);
			}
		}
	}
}
// we're in httpdocs/admin/includes/library and we need to get to httpdocs
picture_audit::$imgfolder = dirname(dirname(__DIR__)).'/images';
?>