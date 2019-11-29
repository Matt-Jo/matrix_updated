<?php
class printfile {
	private static $print_stations = [];

	private static $upc_label_files = [
		'serial' => [
			'file-name' => 'serial_printing.csv',
			'file-header' => ['IPN', 'SERIAL', 'QTY', 'MAC'],
		],
		'nonserial' => [
			'file-name' => 'nonserial_printing.csv',
			'file-header' => ['IPN', 'QTY'],
		],
		'bin' => [
			'file-name' => 'bin_printing.csv',
			'file-header' => ['IPN', 'BIN1', 'BIN2', 'BIN3', 'QTY'],
		],
	];

	private static $ftp = [
		'host' => '10.0.80.132', //'ftplabels.cablesandkits.com',
		'port' => 21,
		'timeout' => 5,
	];

	private static $credentials = [
		'username' => 'CKUPLOAD',
		'password' => 'k1ts.789',
	];

	private static $reload_paths = FALSE;

	private static $session_key = 'receiving_station';

	private static $dir;

	public static $default_station_id = 0;

	private $ft;
	private $local_path;
	private $destination_path;

	public function __construct($filetype) {
		self::init_stations();

		if (empty(self::$upc_label_files[$filetype])) throw new printfileException('File Type ['.$filetype.'] does not exist.');
		$this->ft = $filetype;

		if (self::station_is_set()) self::set_station();

		self::$dir = realpath(__DIR__.'/../../admin/data_management'); // since it's an expression, it needs to be loaded here

		if (self::$reload_paths) $this->reload_paths();
	}

	public function reload_paths() {
		$this->local_path = self::$dir.'/'.self::$upc_label_files[$this->ft]['file-name'];
		$this->destination_path = self::$print_stations[$_SESSION[self::$session_key]]['folder'].'/'.self::$upc_label_files[$this->ft]['file-name'];

		self::$reload_paths = FALSE;
	}

	public function write($data) {
		if (!self::station_is_set()) throw new printfileException('Cannot print without first selecting a print station.');

		if (self::$reload_paths) $this->reload_paths();

		if (empty($this->local_path) || empty($this->destination_path)) throw new printfileException('File paths are not correct - could not write.');

		$fp = fopen($this->local_path, 'w');
		fputcsv($fp, self::$upc_label_files[$this->ft]['file-header']);
		foreach ($data as $row) fputcsv($fp, $row);
		fclose($fp);
	}

	public function send_print() {
		if (!self::station_is_set()) throw new printfileException('Cannot print without first selecting a print station.');

		if (self::$reload_paths) $this->reload_paths();

		if (empty($this->local_path) || empty($this->destination_path)) throw new printfileException('File paths are not correct - could not send print job.');

		if (!service_locator::get_config_service()->is_production()) return TRUE;

		if ($conn_id = ftp_connect(self::$ftp['host'], self::$ftp['port'], self::$ftp['timeout'])) {
			ftp_login($conn_id, self::$credentials['username'], self::$credentials['password']);
			ftp_pasv($conn_id, TRUE);
			$upload = @ftp_put($conn_id, $this->destination_path, $this->local_path, FTP_BINARY);
			ftp_close($conn_id);

			return TRUE;
		}
	}

	public static function get_stations() {
		self::init_stations();

		return self::$print_stations;
	}

	public static function station_is_set() {
		return isset($_SESSION[self::$session_key]);
	}

	public static function init_stations($reload=FALSE) {
		if (empty(self::$print_stations) || $reload) {
			$stations = prepared_query::fetch('SELECT * FROM ck_print_stations WHERE active = 1', cardinality::SET);

			foreach ($stations as $station) {
				self::$print_stations[$station['print_station_id']] = ['folder' => $station['ftp_folder'], 'name' => $station['name']];
			}
		}
	}

	public static function is_selected($station_id) {
		return $station_id==$_SESSION[self::$session_key];
	}

	public static function set_station($station_id=NULL) {
		self::init_stations();

		if (is_null($station_id)) $station_id = $_SESSION[self::$session_key];
		if (empty(self::$print_stations[$station_id])) throw new printfileException('Print Station ['.$station_id.'] does not exist.');

		self::$reload_paths = TRUE;

		return $_SESSION[self::$session_key] = $station_id;
	}
}

class printfileException extends Exception {
}
?>
