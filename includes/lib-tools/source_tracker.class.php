<?php
/* this class is set up to track source details potentially across a multitude of lifetime options.
for now we're tracking it just-in-time for order entry */
class source_tracker {

// this is used as a namespace in the storage method of choice if the specified data lifetime is longer than just this page
const tracker_prefix = 'source-tracker-';
const lifetime = 'page';

// name intended to have least likely chance of overlapping with something that might be used
private $class_vars = array();

public function __construct($source_utmz=NULL) {
	// we record source info at construction here, because it could potentially have a different lifetime. The other source details are likely only relevant at the end of the process.
	if ($source_utmz) $this->record_start($source_utmz);
}

public function record_start($source_utmz) {
	$this->utmz = $source_utmz;
	if ($source_utmz && preg_match_all('/(utm[a-z]+)=([^|]+)/i', $source_utmz, $matches)) {
		foreach ($matches[1] as $idx => $key) {
			$val = $matches[2][$idx];
			$this->$key = $val;
		}
	}
}

public function record_end($db, $orders_id, $admin_id, $channel) {
	// we're recording this as a separate query so that we don't have to interrupt any other interaction processes. We can just drop the class in and make the call so long as the needed data is in context

	if ($this->gclid || $this->utmgclid) {
		$this->source = 'google';
		$this->medium = 'cpc';
	}
	if (!empty($admin_id)) {
		$this->source = NULL;
		$this->medium = NULL;
		$this->campaign = NULL;
		$this->term = NULL;
		$this->content = NULL;
	}

	prepared_query::execute('UPDATE orders SET admin_id = ?, channel = ?, source = ?, medium = ?, campaign = ?, keyword = ?, content = ?, utmz = ? WHERE orders_id = ?', array($admin_id, $channel, $this->source, $this->medium, $this->campaign, $this->term, $this->content, $this->utmz, $orders_id));
}

public function admin_id() {
	// negotiate the appropriate admin_id based on cookie/session data
	if (isset($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id'])) return $_SESSION['admin_id'];
	if (isset($_SESSION['admin_login_id']) && is_numeric($_SESSION['admin_login_id'])) return $_SESSION['admin_login_id'];
	
	if (!empty($_SESSION['admin']) && ($_SESSION['admin'] === 'true')) return $_COOKIE['admin_login_id'];
	
	
	return null;
	
	
}

public function __set($key, $val) {
	switch ($key) {
		case 'utmcsr': $key = 'source'; break;
		case 'utmcmd': $key = 'medium'; break;
		case 'utmctr': $key = 'term'; break;
		case 'utmcct': $key = 'content'; break;
		case 'utmccn': $key = 'campaign'; break;
	}
	switch (self::lifetime) {
		case 'page':
			// for the moment, 'page' is the only option and, thus, the default
		default:
			return $this->class_vars[$key] = $val;
			break;
	}
}

public function __get($key) {
	switch (self::lifetime) {
		case 'page':
			// for the moment, 'page' is the only option and, thus, the default
		default:
			return isset($this->class_vars[$key])?$this->class_vars[$key]:NULL;
			break;
	}
}

}
?>