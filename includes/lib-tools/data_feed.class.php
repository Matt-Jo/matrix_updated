<?php
// we're working with large data sets and big-ish queries, so try and allow this to run with max resources
@ini_set("memory_limit","3072M");
@set_time_limit(0);

if (!defined('BASEPATH')) define('BASEPATH', realpath(__DIR__.'/../..'));
if (!defined('FEEDPATH')) define('FEEDPATH', BASEPATH.'/feeds');
if (!file_exists(FEEDPATH)) throw new Exception('Path for feed files ['.FEEDPATH.'] does not exist');

@include_once(BASEPATH.'/includes/engine/vendor/autoload.php');

abstract class data_feed {
	public static $DEBUG = FALSE;
	public static $TEST = FALSE;

	protected $file_repository = FEEDPATH;

	protected $file_type;
	protected $delimiter;
	protected $target;

	private $feed;
	protected $local_filename;
	protected $zip_filename;
	protected $gz_filename;
	protected $destination_filename;
	protected $output_header;
	protected $header = [];
	protected $data = [];
	protected $path;
	protected $pathdir;
	protected $no_local_file = false; //MMD - providing an option to not write a file to the FS

	protected $finalized = FALSE;
	private $file_success = [];

	protected $ftp_server;
	protected $ftp_port = 21;
	protected $ftp_user;
	protected $ftp_pass;
	protected $ftp_path;

	protected $email_address;
	protected $email_from = 'sales@cablesandkits.com';
	protected $email_subject = 'Data Feed from cablesandkits.com';
	protected $email_body = '';

	const FILE_NONE = 'none';
	const FILE_CSV = 'txt';
	const FILE_TXT = 'txt';
	const FILE_XLS = 'xls';
	const FILE_XML = 'xml';

	const OUTPUT_NONE = 'none';
	const OUTPUT_FILE = 'file';
	const OUTPUT_STD = 'stdout';
	const OUTPUT_FTP = 'ftp';
	const OUTPUT_FTPS = 'ftps';
	const OUTPUT_SFTP = 'sftp';
	const OUTPUT_EMAIL = 'email';

	const DELIM_NONE = NULL;
	const DELIM_COMMA = ',';
	const DELIM_TAB = "\t";

	const HIERARCHY_DELIM = '>';

	private $attributes_query = "";

	protected $category_hierarchy = FALSE;

	protected $needs_categories = TRUE;
	protected $needs_attributes = TRUE;

	protected $results = [];
	protected $max_category_depth = 0;

	protected $attribute_keys = [];

	protected $child_called = FALSE;

	//---------------------------------------------------------

	private $products_stock_control = array(
		'keys' => array('stock_id'),

		'query' => 'SELECT psc.stock_id, psc.stock_name as ipn, psc.warranty_id, psc.is_bundle, psc.stock_price, psc.dealer_price as stock_dealer_price, psc.serialized, CASE WHEN psc.serialized = 0 THEN psc.stock_quantity ELSE sq.quantity END as stock_quantity, psc.max_displayed_quantity, psc.always_available, psc.lead_time, psc.stock_weight, psc.drop_ship, psc.freight, c.conditions_name as stock_condition, CASE WHEN w.warranty_name IS NOT NULL THEN warranty_name ELSE \'None\' END as warranty, pscc.name as stock_category, psc.dlao_product FROM products_stock_control psc JOIN conditions c ON psc.conditions = c.conditions_id LEFT JOIN warranties w ON psc.warranty_id = w.warranty_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN (SELECT ipn as stock_id, COUNT(id) as quantity FROM serials WHERE status IN (2,3,6) GROUP BY ipn) sq ON psc.stock_id = sq.stock_id',

		'criteria' => array('psc.discontinued' => NULL, 'psc.freight' => NULL, 'psc.serialized' => NULL, 'psc.drop_ship' => NULL, 'psc.dlao_product' => 0),

		'default_sort' => 'psc.stock_name ASC'
	);

	private $products = array(
		'keys' => array('products_id', 'stock_id'),

		'query' => 'SELECT p.products_id, p.products_status, p.products_model as model_number, p.stock_id, pd.products_name as name, m.manufacturers_id, m.manufacturers_name as manufacturer, pd.products_description as description, pd.products_head_desc_tag as head_description, p.products_price, p.products_dealer_price, p.products_quantity, p.products_weight, p.amazon_upc, s.specials_qty, s.specials_new_products_price as specials_price, DATE(s.specials_date_added) as s_date_added, CASE WHEN s.expires_date IS NULL THEN DATE(DATE_ADD(now(), interval 30 day)) ELSE DATE(s.expires_date) END as s_date_end, s.status as s_status, p.products_image, p.products_image_med, p.products_image_lrg as image_url, p.products_image_sm_1, p.products_image_xl_1, p.products_image_sm_2, p.products_image_xl_2, p.products_image_sm_3, p.products_image_xl_3, p.products_image_sm_4, p.products_image_xl_4, p.products_image_sm_5, p.products_image_xl_5, p.products_image_sm_6, p.products_image_xl_6 FROM products p JOIN products_description pd ON p.products_id = pd.products_id JOIN manufacturers m ON p.manufacturers_id = m.manufacturers_id LEFT JOIN specials s ON s.products_id = p.products_id AND s.status = 1 AND (s.expires_date IS NULL OR s.expires_date > now())',

		'criteria' => array('p.products_status' => 1),

		'default_sort' => 'p.products_model ASC, p.products_id ASC'
	);

	private $product_categories = array(
		'keys' => array('categories_id', 'parent_id', 'products_id', 'canonical_category_id'),

		'query' => 'SELECT c.categories_id, c.parent_id, c.item_type, c.product_type, c.google_category_id, cd.categories_name as category, cd.categories_description as description, cd.categories_head_desc_tag as head_description, cd.shopping_com_category as site_category, ptc.products_id, c.canonical_category_id, aci.ebay_category1_id, aci.ebay_shop_category1_id FROM products_to_categories ptc JOIN categories c ON ptc.categories_id = c.categories_id JOIN categories_description cd ON c.categories_id = cd.categories_id LEFT JOIN abx_category_info aci ON aci.categories_id = c.categories_id and aci.categories_site = \'US\'',

		'criteria' => array('ptc.products_id' => NULL),

		'default_sort' => 'c.categories_id DESC'
	);

	private $parent_categories = array(
		'keys' => array('categories_id', 'parent_id', 'canonical_category_id'),

		'query' => 'SELECT c.categories_id, c.parent_id, c.item_type, c.product_type, c.google_category_id, cd.categories_name as category, cd.categories_description as description, cd.categories_head_desc_tag as head_description, cd.shopping_com_category as site_category, c.canonical_category_id FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE c.categories_id IN (SELECT DISTINCT parent_id FROM categories)',

		'criteria' => [],

		'default_sort' => 'c.categories_id ASC'
	);

	private $attributes = array(
		'keys' => array('products_id', 'attribute_key'),

		'query' => 'SELECT DISTINCT a.products_id, a.attribute_key, a.subheading, a.value FROM ck_attributes a',

		//MMD - special handling for the best sellers attribute since it is internal
		'criteria' => array('(a.internal = 0 or a.attribute_key_id = 78)'),

		'default_sort' => 'a.model_number ASC, a.attribute_key ASC'
	);

	private $missing_attributes = array(
		'keys' => array('products_id', 'attribute_key'),

		'query' => 'SELECT DISTINCT aa.products_id, aa.attribute_key, NULL as subheading, \'Unset\' as value, aa.required FROM ck_attribute_assignments aa LEFT JOIN ck_attributes a ON aa.products_id = a.products_id AND aa.attribute_key_id = a.attribute_key_id',

		'criteria' => array('a.attribute_id' => array('IS', 'NULL')),

		'default_sort' => 'a.model_number ASC, a.attribute_key ASC'
	);

	private $orders = array(
		'keys' => array('orders_id', 'customers_id'),

		'query' => 'SELECT DISTINCT o.orders_id, o.date_purchased, o.customers_id, o.customers_company, c.customers_firstname, c.customers_lastname, c.customers_newsletter, c.customer_type, o.customers_email_address, o.customers_street_address as address1, o.customers_suburb as address2, o.customers_city, o.customers_state, o.customers_postcode as customers_zip, o.customers_country, o.customers_telephone, o.delivery_name, o.delivery_street_address as delivery_address1, o.delivery_suburb as delivery_address2, o.delivery_city, o.delivery_state, o.delivery_postcode as delivery_zip, o.delivery_country FROM orders o JOIN customers c ON o.customers_id = c.customers_id JOIN acc_invoices ai ON o.orders_id = ai.inv_order_id',

		'criteria' => [],

		'default_sort' => 'o.date_purchased DESC'
	);

	private $invoice_dates = array(
		'keys' => array('invoice_id', 'orders_id'),

		'query' => 'SELECT MIN(invoice_id) as invoice_id, inv_order_id as orders_id, MIN(inv_date) as inv_date FROM acc_invoices WHERE inv_order_id IS NOT NULL AND credit_memo = 0 GROUP BY inv_order_id',

		'criteria' => [],

		'default_sort' => 'inv_order_id DESC'
	);

	private $order_totals = array(
		'keys' => array('orders_id', 'class', 'shipping_method_id'),

		'query' => "SELECT ai.invoice_id, ai.inv_order_id as orders_id, ait.invoice_total_description as title, CASE WHEN ait.invoice_total_line_type = 'ot_coupon' THEN ABS(ait.invoice_total_price)*-1 ELSE ait.invoice_total_price END as value, ait.invoice_total_line_type as class, sm.shipping_code as shipping_method_id, CONCAT_WS(' ', sm.carrier, sm.name) as shipping_method FROM acc_invoice_totals ait JOIN acc_invoices ai ON ait.invoice_id = ai.invoice_id JOIN orders o ON ai.inv_order_id = o.orders_id LEFT JOIN orders_total ot ON ai.inv_order_id = ot.orders_id AND ait.invoice_total_line_type = ot.class AND ot.class = 'ot_shipping' LEFT JOIN shipping_methods sm ON ot.external_id = sm.shipping_code WHERE (ait.invoice_total_line_type NOT IN ('ot_coupon', 'ot_subtotal') OR (ait.invoice_total_line_type = 'ot_coupon' AND ait.invoice_total_price != 0)) AND ai.inv_order_id IS NOT NULL AND ai.credit_memo = 0",

		'where_there' => TRUE,

		'criteria' => [],

		'default_sort' => 'ai.inv_order_id DESC'
	);

	private $order_products = array(
		'keys' => array('products_id', 'orders_id'),

		'query' => 'SELECT DISTINCT op.orders_id, op.products_id, op.avg_price, op.quantity, rp.returned_quantity FROM (SELECT orders_id, products_id as products_id, SUM(final_price*products_quantity)/SUM(products_quantity) as avg_price, SUM(products_quantity) as quantity FROM orders_products WHERE final_price > 0 GROUP BY orders_id, products_id) op JOIN orders o ON op.orders_id = o.orders_id JOIN acc_invoices ai ON o.orders_id = ai.inv_order_id LEFT JOIN (SELECT r.order_id as orders_id, op.products_id as products_id, SUM(rp.quantity) as returned_quantity FROM rma r JOIN rma_product rp ON r.id = rp.rma_id AND rp.received_date IS NOT NULL JOIN orders_products op ON rp.order_product_id = op.orders_products_id GROUP BY r.order_id, op.products_id) rp ON o.orders_id = rp.orders_id AND op.products_id = rp.products_id',

		'criteria' => [],

		'default_sort' => ''
	);

	//---------------------------------------------------------

	public function __construct($target=self::OUTPUT_FILE, $delimeter=self::DELIM_TAB, $file_type=self::FILE_TXT) {
		if (!$this->child_called) {
			throw new Exception("The ".__CLASS__." class constructor must be superseded by a child constructor.");
			return FALSE;
		}

		$this->repath();

		if (!$this->no_local_file && !is_writeable($this->file_repository)) {
			throw new Exception("Local Directory [$this->file_repository] is not writeable. Please check permissions.");
			return FALSE;
		}

		if (!$this->no_local_file && is_file($this->file_path) && !is_writeable($this->file_path)) {
			throw new Exception("Local File [$this->local_filename] is not writeable. Please check permissions.");
			return FALSE;
		}

		if ($target == self::OUTPUT_EMAIL && !$this->email_address) {
			throw new Exception("Email was selected as the target for this feed, but no email address was provided.");
			return FALSE;
		}
		elseif (in_array($target, array(self::OUTPUT_FTP, self::OUTPUT_FTPS, self::OUTPUT_SFTP)) && (!$this->ftp_server || !$this->ftp_user || ($this->ftp_user != 'anonymous' && !$this->ftp_pass))) {
			throw new Exception(strtoupper($target)." was selected as the target for this feed, but some FTP connection details were missing.");
			return FALSE;
		}
		/*elseif ($target == self::OUTPUT_SFTP) {
			throw new Exception("SFTP is not yet implemented");
			return FALSE;
		}*/

		$this->file_type = $file_type;
		$this->target = $target;
		$this->delimeter = $delimeter;

		if (!service_locator::get_config_service()->is_production()) {
			//self::$DEBUG = TRUE;
			self::$TEST = TRUE;
		}
	}

	protected function repath() {
		$this->file_path = $this->file_repository.'/'.$this->local_filename;
	}

	// we've broken (more sprained, since it's still an integer) this API with the addition of $maxkeep
	protected function remove_old_feeds($path, $fragment, $maxkeep=2, $timeframe=NULL, $periods=NULL) {
		if (!empty($periods) && empty($timeframe)) $timeframe = 60*60*24; // one day
		if (!empty($timeframe) && empty($periods)) $periods = 1;
		$files = [];
		if ($dir = opendir($path)) {
			while (FALSE !== ($file = readdir($dir))) {
				// if the file matches our fragment, and is older than 1 day old
				if (preg_match("/^$fragment/", $file)) {
					$files[filemtime($path.'/'.$file)] = $path.'/'.$file;
				}
			}
			closedir($dir);
		}
		if (!empty($files)) {
			krsort($files);
			$fcount = 0;
			foreach ($files as $timestamp => $file) {
				// only setting $maxkeep to -1 (or any negative number) will allow you to keep an unlimited number of files, which is almost certainly never the desire
				if (($maxkeep >= 0 && ++$fcount > $maxkeep) || (!empty($periods) && ($timestamp <= time()-($timeframe*$periods)))) unlink($file);
			}
		}
	}

	// for the moment, this ignores canonical links. we may want a way to factor that in, but we've only guaranteed to get all parent categories, not all canonical categories
	public static function build_category_hierarchy($parent_id, $categories, &$hierarchy=[]) {
		foreach ($categories as $category) {
			if ($category['categories_id'] == $parent_id) {
				$hierarchy[] = $category;
				if ($category['parent_id']) self::build_category_hierarchy($category['parent_id'], $categories, $hierarchy);
				break;
			}
		}
		return $hierarchy;
	}

	// this doesn't really work at the moment, without addressing the AND/OR criteria issue if there is more than one selected criteria
	protected function query($type, $criteria=[], $run=TRUE) {
		$query = $this->{$type}['query'];
		$params = [];
		$criteria_found = !empty($this->{$type}['where_there']);
		$first = !$criteria_found;
		foreach ($criteria as $key => $val) {

			//MMD - if the value is null we want to just skip this one
			if ($val == null) {
				continue;
			}

			if (empty($criteria_found)) {
				$query .= ' WHERE ';
				$criteria_found = TRUE;
			}
			if (!$first) $query .= ' AND ';
			$first = FALSE;
			if (is_array($val) && is_array($val[0])) {
				$vals = [];
				foreach ($val as $valopt) {
					if ($valopt[1] == 'NULL') $vals[] = $key.' '.$valopt[0].' NULL';
					else {
						$vals[] = $key.' '.$valopt[0].' ?';
						$params[] = $valopt[1];
					}
				}
				$query .= implode(' AND ', $vals);
			}
			elseif (is_array($val)) {
				if ($val[1] == 'NULL') $query .= $key.' '.$val[0].' NULL';
				else {
					$query .= $key.' '.$val[0].' ?';
					$params[] = $val[1];
				}
			}
			else {
				$query .= $key .' = ?';
				$params[] = $val;
			}
		}

		foreach ($this->{$type}['criteria'] as $key => $val) {
			if (!is_null($val) && !in_array($key, array_keys($criteria))) {
				if (empty($criteria_found)) {
					$query .= ' WHERE ';
					$criteria_found = TRUE;
				}
				if (!$first) $query .= ' AND ';
				$first = FALSE;
				if (is_array($val)) {
					if ($val[1] == 'NULL') $query .= $key.' '.$val[0].' NULL';
					else {
						$query .= $key.' '.$val[0].' ?';
						$params[] = $val[1];
					}
				}
				else {
					$query .= $key .' = ?';
					$params[] = $val;
				}
			}
		}

		if (!empty($this->{$type}['default_sort'])) $query .= ' ORDER BY '.$this->{$type}['default_sort'];

		if (!empty($run)) {
			$result = prepared_query::fetch($query, cardinality::SET, $params);
			/*if (self::$TEST) {
				echo '<pre>';
				echo $query."\n";
				print_r($params);
				print_r($result);
				echo '</pre>';
			}*/
			return $result;
		}
		else return array($query, $params);
	}

	//---------------------------------------------------------

	public function __destruct() {
		if (!$this->finalized) $this->write();
	}

	public function write() {
		if ($this->finalized) return; // we'll have to manually clear this if we want to write more than once
		$this->finalized = TRUE;

		$this->file_success['xml'] = NULL;
		$this->file_success['xls'] = NULL;
		$this->file_success['txt'] = NULL;
		$this->file_success['zip'] = NULL;
		$this->file_success['gz'] = NULL;

		if ($this->file_type != self::FILE_NONE) {
			if ($this->file_type == self::FILE_XML) $this->write_xml();
			elseif ($this->file_type == self::FILE_XLS) $this->write_xls();
			else $this->write_txt(); // $this->file_type == self::FILE_TXT

			$this->data = [];

			if ($this->zip_filename) $this->write_zip();
			elseif ($this->gz_filename) $this->write_gz();
		}

		if (self::$DEBUG) return;

		try {
			$this->output();
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	private function write_xml() {
		if ($this->data->asXML($this->file_path)) $this->file_success['xml'] = TRUE;
		else $this->file_success['xml'] = FALSE;
		// pretty print, for debugging
		/*$dom = new DOMDocument('1.0');
		$dom->preserveWhiteSpace = FALSE;
		$dom->formatOutput = TRUE;
		$dom->loadXML($this->data->asXML());
		if ($dom->save($this->file_path)) $this->file_success['xml'] = TRUE;*/

		return $this->file_success['xml'];
	}

	private function write_xls() {
		throw new Exception('Writing Excel files is not yet implemented');
		return FALSE;
	}

	private function write_txt() {
		$feed = @fopen($this->file_path, 'w');
		if ($this->header) {
			fwrite($feed, implode($this->delimeter, $this->header)."\n");
		}
		foreach ($this->data as $idx => $row) {
			if ($idx == count($this->data)-1) fwrite($feed, implode($this->delimeter, $row));
			else fwrite($feed, implode($this->delimeter, $row)."\n");
		}
		fclose($feed);
		return $this->file_success['txt'] = TRUE;
	}

	private function write_zip() {
		$zip = new ZipArchive;
		$zippath = $this->file_repository.'/'.$this->zip_filename;
		if ($zip->open($zippath, ZipArchive::OVERWRITE)) {
			if ($zip->addFile($this->file_path, $this->destination_filename)) {
				$this->file_success['zip'] = TRUE;
			}
			else $this->file_success['zip'] = FALSE;
			$zip->close();
		}
		else $this->file_success['zip'] = FALSE;

		return $this->file_success['zip'];
	}

	private function write_gz() {
		$gzpath = $this->file_repository.'/'.$this->gz_filename;
		if ($gz = @gzopen($gzpath, 'w9')) {
			gzwrite($gz, file_get_contents($this->file_path));
			gzclose($gz);
			$this->file_success['gz'] = TRUE;
		}
		else $this->file_success['gz'] = FALSE;

		return $this->file_success['gz'];
	}

	public function output() {
		if ($this->target == self::OUTPUT_NONE) return;
		elseif ($this->target == self::OUTPUT_EMAIL) $this->send_email();
		elseif (in_array($this->target, [self::OUTPUT_FTP, self::OUTPUT_FTPS])) $this->send_ftp();
		elseif ($this->target == self::OUTPUT_SFTP) $this->send_sftp();
		elseif ($this->target == self::OUTPUT_STD) {
			if (!empty($this->output_header)) {
				$output_filename = !empty($this->destination_filename)?$this->destination_filename:$this->local_filename;
				header('Content-disposition: attachment; filename='.$output_filename);
				switch ($this->file_type) {
					case 'csv':
						header('Content-Type: text/csv');
						break;
					case 'xml':
						header('Content-Type: text/xml');
						break;
					case 'xls':
						header('Content-Type: application/vnd.ms-excel');
						break;
					case 'xlsx':
						header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
						break;
					case 'txt':
					default:
						header('Content-Type: text/tab-separated-values');
						break;
				}
			}
			echo file_get_contents($this->file_path);
		}
	}

	private function send_email(): bool {
		$mailer = service_locator::get_mail_service();
		
		$mail = $mailer->create_mail()
			->set_from($this->email_from)
			->set_subject($this->email_subject)
			->set_body($this->email_body)
			//->add_to($this->email_address)
			->create_attachment(file_get_contents($this->file_path), $this->destination_filename);

		if (is_array($this->email_address)) {
			foreach ($this->email_address as $ea) {
				$mail->add_to($ea);
			}
		}
		else $mail->add_to($this->email_address);

		$mailer->send($mail);

		return TRUE;
	}

	private function send_ftp() {
		if ($this->target == self::OUTPUT_FTP && !($ftp = ftp_connect($this->ftp_server, $this->ftp_port))) {
			throw new Exception("FTP connection to $this->ftp_server failed.");
			return FALSE;
		}
		elseif ($this->target == self::OUTPUT_FTPS && !($ftp = ftp_ssl_connect($this->ftp_server, $this->ftp_port))) {
			throw new Exception("FTPS connection to $this->ftp_server:$this->ftp_port failed.");
			return FALSE;
		}

		if (!ftp_login($ftp, $this->ftp_user, $this->ftp_pass)) {
			throw new Exception("FTP login to $this->ftp_server with provided username/pass failed.");
			return FALSE;
		}

		if ($this->ftp_path && !ftp_chdir($ftp, $this->ftp_path)) {
			throw new Exception("Changing to $this->ftp_path on FTP server failed.");
			return FALSE;
		}

		ftp_pasv($ftp, TRUE);
		if ($this->file_success['zip']) {
			if (self::$TEST) {
				debug_tools::mark('Skip send Zip');
				return TRUE;
			}
			// zip success is only set to true when we've both requested a zip file and it's successfully been created
			if (!ftp_put($ftp, $this->destination_filename, $this->file_repository.'/'.$this->zip_filename, FTP_BINARY)) {
				throw new Exception("Upload of $this->file_repository/$this->zip_filename to $this->ftp_server/$this->ftp_path/$this->destination_filename failed.");
				return FALSE;
			}
		}
		elseif ($this->file_success['gz']) {
			if (self::$TEST) {
				debug_tools::mark('Skip send GZ');
				return TRUE;
			}
			// gz success is only set to true when we've both requested a gz file and it's successfully been created
			if (!ftp_put($ftp, $this->destination_filename, $this->file_repository.'/'.$this->gz_filename, FTP_BINARY)) {
				throw new Exception("Upload of $this->file_repository/$this->gz_filename to $this->ftp_server/$this->ftp_path/$this->destination_filename failed.");
				return FALSE;
			}
		}
		else {
			if (self::$TEST) {
				debug_tools::mark('Skip send File');
				return TRUE;
			}
			if (!ftp_put($ftp, $this->destination_filename, $this->file_path, ($this->file_type==self::FILE_XLS?FTP_BINARY:FTP_ASCII))) {
				throw new Exception("Upload of $this->file_path to $this->ftp_server/$this->ftp_path/$this->destination_filename failed.");
				return FALSE;
			}
		}

		ftp_close($ftp);
		return TRUE;
	}

	private function send_sftp() {
		/*if (!class_exists('Net_SFTP')) {
			throw new Exception("SFTP class is not available");
			return FALSE;
		}*/
		if (empty($this->sftp)) {
			if (!($this->sftp = new phpseclib\Net\SFTP($this->ftp_server))) {
				throw new Exception("SFTP connection to $this->ftp_server failed.");
				return FALSE;
			}
			if (!$this->sftp->login($this->ftp_user, $this->ftp_pass)) {
				throw new Exception("SFTP login to $this->ftp_server with provided username/pass failed.");
				return FALSE;
			}
		}

		if ($this->file_success['zip']) {
			if (self::$TEST) return TRUE;
			if (!$this->sftp->put($this->ftp_path.'/'.$this->destination_filename, $this->file_repository.'/'.$this->zip_filename, phpseclib\Net\SFTP::SOURCE_LOCAL_FILE)) {
				throw new Exception("Upload of $this->file_repository/$this->zip_filename to $this->ftp_server/$this->ftp_path/$this->destination_filename failed.");
				return FALSE;
			}
		}
		elseif ($this->file_success['gz']) {
			if (self::$TEST) return TRUE;
			if (!$this->sftp->put($this->ftp_path.'/'.$this->destination_filename, $this->file_repository.'/'.$this->gz_filename, phpseclib\Net\SFTP::SOURCE_LOCAL_FILE)) {
				throw new Exception("Upload of $this->file_repository/$this->gz_filename to $this->ftp_server/$this->ftp_path/$this->destination_filename failed.");
				return FALSE;
			}
		}
		else {
			if (self::$TEST) return TRUE;
			if (!$this->sftp->put($this->ftp_path.'/'.$this->destination_filename, $this->file_path, phpseclib\Net\SFTP::SOURCE_LOCAL_FILE)) {
				throw new Exception("Upload of $this->file_path to $this->ftp_server/$this->ftp_path/$this->destination_filename failed.");
				return FALSE;
			}
		}

		return TRUE;
	}

	//---------------------------------------------------------

	protected function query_category_data() {
		$product_categories = [];
		$parent_categories = [];

		if ($prodcats = $this->query('product_categories')) {
			if ($this->category_hierarchy && ($pcats = $this->query('parent_categories'))) {
				foreach ($pcats as $pcat) {
					$parent_categories[$pcat['categories_id']] = $pcat;
				}
				unset($pcats);
			}

			$this->max_category_depth = 1;

			foreach ($prodcats as $prodcat) {
				if (!isset($product_categories[$prodcat['products_id']])) $product_categories[$prodcat['products_id']] = [];
				$idx = count($product_categories[$prodcat['products_id']]);
				$product_categories[$prodcat['products_id']][$idx] = [$prodcat];

				if ($this->category_hierarchy && $prodcat['parent_id'] && !empty($parent_categories)) {
					self::build_category_hierarchy($prodcat['parent_id'], $parent_categories, $product_categories[$prodcat['products_id']][$idx]);
					$this->max_category_depth = max(count($product_categories[$prodcat['products_id']][$idx]), $this->max_category_depth);
				}
			}
		}

		return $product_categories;
	}

	protected function query_attribute_data() {
		$product_attributes = [];

		if ($attributes = $this->query('attributes')) {
			// missing attributes are attributes that are determined to be relevant for a given item, but are not filled in for that item
			foreach ($attributes as $attribute) {
				if (!in_array($attribute['attribute_key'], $this->attribute_keys)) $this->attribute_keys[] = $attribute['attribute_key'];

				if (!isset($product_attributes[$attribute['products_id']])) $product_attributes[$attribute['products_id']] = [];
				if (!isset($product_attributes[$attribute['products_id']][$attribute['attribute_key']])) $product_attributes[$attribute['products_id']][$attribute['attribute_key']] = [];

				$product_attributes[$attribute['products_id']][$attribute['attribute_key']][] = $attribute;
			}
			unset($attributes); // manage memory

			if ($mattrs = $this->query('missing_attributes')) {
				foreach ($mattrs as $mattr) {
					//if (!in_array($mattr['attribute_key'], $this->attribute_keys)) $this->attribute_keys[] = $mattr['attribute_key'];

					if (!isset($product_attributes[$mattr['products_id']])) $product_attributes[$mattr['products_id']] = [];
					if (!isset($product_attributes[$mattr['products_id']][$mattr['attribute_key']])) $product_attributes[$mattr['products_id']][$mattr['attribute_key']] = [];

					$product_attributes[$mattr['products_id']][$mattr['attribute_key']][] = $mattr;
				}
				unset($mattrs); // manage memory
			}
		}

		$this->attribute_keys = array_unique($this->attribute_keys);

		// we use a user defined sort rather than a built in because this is the only way to get a natural, case insensetive sort in PHP 5.3 while breaking the key association
		usort($this->attribute_keys, function($a, $b) { return strnatcasecmp($a, $b); });

		return $product_attributes;
	}

	protected function query_product_data($limit=NULL) {
		$this->results = [];

		if ($this->needs_categories) $product_categories = $this->query_category_data();
		if ($this->needs_attributes) $product_attributes = $this->query_attribute_data();

		$criteria = [];
		if ($limit === 0 || $limit === '0') $criteria['psc.dlao_product'] = NULL;

		$products_stock_control = [];
		if ($pscs = $this->query('products_stock_control', $criteria)) {
			foreach ($pscs as $psc) {
				$products_stock_control[$psc['stock_id']] = $psc;
			}
		}
		unset($pscs);

		$criteria = [];
		if ($limit === 0 || $limit === '0') $criteria['p.products_status'] = NULL;

		// this assumes that there is a globally defined $ckdb variable that supports the requested methods
		if ($products = $this->query('products', $criteria)) {
			foreach ($products as $product) {
				if (empty($products_stock_control[$product['stock_id']])) continue; // if there's no IPN for this product, we don't want to be outputting it
				foreach ($products_stock_control[$product['stock_id']] as $column => $value) $product[$column] = $value;

				$data = (object) array('details' => $product, 'categories' => [], 'attributes' => []);
				if (!empty($product_categories[$product['products_id']])) $data->categories = $product_categories[$product['products_id']];
				if (!empty($product_attributes[$product['products_id']])) $data->attributes = $product_attributes[$product['products_id']];

				$this->results[] = $data;
			}
		}

		return $this->results;
	}

	protected function query_order_data($criteria) {
		$this->results = [];

		$order_totals = [];
		if ($ots = $this->query('order_totals', $criteria)) {
			foreach ($ots as $ot) {
				if (!isset($order_totals[$ot['invoice_id']])) $order_totals[$ot['invoice_id']] = [];
				if (!isset($order_totals[$ot['invoice_id']][$ot['class']])) $order_totals[$ot['invoice_id']][$ot['class']] = array('total' => 0, 'details' => []);
				$order_totals[$ot['invoice_id']][$ot['class']]['total'] += $ot['value'];
				if ($ot['class'] == 'ot_shipping') {
					if (!empty($ot['shipping_method'])) $order_totals[$ot['invoice_id']][$ot['class']]['method'] = $ot['shipping_method'];
					elseif (empty($order_totals[$ot['invoice_id']][$ot['class']]['method'])) $order_totals[$ot['invoice_id']][$ot['class']]['method'] = $ot['title'];
				}
				$order_totals[$ot['invoice_id']][$ot['class']]['details'][] = $ot;
			}
		}

		$orders_to_invoices = [];
		if (!empty($criteria['DATE(ai.inv_date)'])) {
			if ($invoices = prepared_query::fetch('SELECT MIN(invoice_id) as invoice_id, inv_order_id as orders_id, MIN(inv_date) as inv_date FROM acc_invoices ai WHERE inv_order_id IS NOT NULL AND credit_memo = 0 AND DATE(inv_date) '.$criteria['DATE(ai.inv_date)'][0][0].' ? AND DATE(inv_date) '.$criteria['DATE(ai.inv_date)'][1][0].' ? GROUP BY inv_order_id', cardinality::SET, array($criteria['DATE(ai.inv_date)'][0][1], $criteria['DATE(ai.inv_date)'][1][1]))) {
				foreach ($invoices as $invoice) {
					$orders_to_invoices[$invoice['orders_id']] = $invoice;
				}
			}
		}
		elseif (!empty($criteria['o.orders_id'])) {
			if ($invoices = prepared_query::fetch('SELECT MIN(invoice_id) as invoice_id, inv_order_id as orders_id, MIN(inv_date) as inv_date FROM acc_invoices ai WHERE inv_order_id = ? AND credit_memo = 0 GROUP BY inv_order_id', cardinality::SET, array($criteria['o.orders_id']))) {
				foreach ($invoices as $invoice) {
					$orders_to_invoices[$invoice['orders_id']] = $invoice;
				}
			}
		}

		$order_products = [];
		if ($products = $this->query('order_products', $criteria)) {
			foreach ($products as $product) {
				if (!isset($order_products[$product['orders_id']])) $order_products[$product['orders_id']] = [];
				$order_products[$product['orders_id']][] = $product;
			}
		}

		if ($orders = $this->query('orders', $criteria)) {
			foreach ($orders as $order) {
				if (!isset($orders_to_invoices[$order['orders_id']])) continue; // this order wasn't invoiced in the period
				if (!isset($order_products[$order['orders_id']])) continue; // this order only had free items
				$invoice = $orders_to_invoices[$order['orders_id']];
				$order['inv_date'] = $invoice['inv_date'];
				$order['totals'] = $order_totals[$invoice['invoice_id']];
				$order['products'] = $order_products[$order['orders_id']];

				$this->results[] = (object) $order;
			}
		}

		return $this->results;
	}
}
?>
