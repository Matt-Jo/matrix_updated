<?php
if (!defined('PUBLIC_CERTS')) define('PUBLIC_CERTS', DIR_FS_CATALOG.'includes/public-certs/cacert.pem'); // define the concatenation here, since we can't do that in the class definition
if (!function_exists('http_build_url')) {
	// minimally re-create the http_build_url so we have a companion to parse_url
	function http_build_url($url=NULL, $parts=NULL, $flags=NULL, $new_url=NULL) {
		// we expect certain rules to be followed in the URL data passed in
		if (empty($url) || !is_array($url) || empty($url['host'])) throw new Exception('This is a minimal re-creation of the full http_build_url function provided through PECL, please install the full version if you would like to use all of the features');
		// the other variables are supported only so that we can provide a meaningful error if someone attempts to use them
		if (!empty($parts) || !empty($flags) || !empty($new_url)) throw new Exception('This is a minimal re-creation of the full http_build_url function provided through PECL, please install the full version if you would like to use all of the features');

		$built_url = '';
		$built_url .= !empty($url['scheme'])?$url['scheme']:'http'; // we can default this if necessary
		$built_url .= '://';
		$built_url .= !empty($url['user'])?(!empty($url['pass'])?$url['user'].':'.$url['pass'].'@':$url['user'].'@'):'';
		$built_url .= $url['host'];
		$built_url .= !empty($url['port'])?':'.$url['port']:'';
		$built_url .= '/'; // always include a trailing slash after the hostname, whether there's a path or not
		$built_url .= !empty($url['path'])?($url['path'][0]=='/'?substr($url['path'], 1):$url['path']):''; // if we have a leading slash, omit it because we've already included one
		$built_url .= !empty($url['query'])?($url['query'][0]=='?'?$url['query']:'?'.$url['query']):''; // we assume that this is already encoded properly
		$built_url .= !empty($url['fragment'])?($url['fragment'][0]=='#'?$url['fragment']:'#'.$url['fragment']):'';

		return $built_url;
	}
}

class request {

	protected $ch; // curl handle
	protected $request; // last request cached
	protected $data; // last data cached
	protected $result; // last result cached
	protected $info; // the curlinfo info about the last call

	private $opts = array(
		CURLOPT_CONNECTTIMEOUT	=> 10, //----------------- timeout an unsuccessful connection after 10 seconds
		CURLOPT_FOLLOWLOCATION	=> TRUE, //--------------- if a redirect is sent, follow it
		CURLOPT_AUTOREFERER		=> TRUE, //--------------- if a redirect is sent, send the redirecting page as the referring page
		CURLOPT_MAXREDIRS		=> 50, //----------------- if multiple redirects are sent, follow up to 50 and then quit
		CURLOPT_RETURNTRANSFER	=> TRUE, //--------------- return query result as a string instead of printing out
		CURLOPT_ENCODING		=> '', //----------------- accept all supported encoding types
		CURLOPT_HTTPAUTH		=> CURLAUTH_ANYSAFE, //--- if HTTP authentication is used, use any safe method
		CURLOPT_SSL_VERIFYHOST	=> 2, //------------------ check the common name and verify against hostname provided
		CURLOPT_CAINFO			=> PUBLIC_CERTS, //------- the cert bundle provided on the cURL website: http://curl.haxx.se/docs/caextract.html
		CURLOPT_SSL_VERIFYPEER	=> TRUE, //--------------- since we've included the CA bundle, we can verify our peers to protect against man in the middle attacks
		CURLOPT_COOKIEFILE		=> '', //----------------- this turns on cookie handling for the life of the handle, without loading any previously existing cookies (for which we'd need a filepath)
		//CURLOPT_COOKIEJAR		=> SOMEFILE, //----------- this would store cookies beyond the life of the handle, and we could specify it for COOKIEFILE to load on startup (but requires a writeable file)
	); // our default options

	public function __construct($opts=array()) {
		$this->ch = curl_init();

		// set default options, or use the passed in option if it's supplied
		if (!empty($opts)) {
			foreach ($opts as $opt => $val) { $this->opts[$opt] = $val; }
		}
		curl_setopt_array($this->ch, $this->opts);
	}

	// useful options that could be set per application:
	// CURLOPT_USERPWD: 'username:password' - HTTP Authentication without passing it in the URL
	// CURLOPT_USERAGENT: 'browser user agent' - for pages that require a particular user agent to return a relevant page
	// CURLOPT_REFERRER: 'referring URL' - for pages that require the referrer to be a local page
	public function opt($opt, $val=NULL) {
		if (!empty($val)) {
			$this->opts[$opt] = $val;
			return curl_setopt($this->ch, $opt, $val);
		}
		else return isset($this->opts[$opt])?$this->opts[$opt]:NULL;
	}

	public function __destruct() {
		curl_close($this->ch);
	}

	public function new_session() {
		// don't send any previously existing session cookies
		curl_setopt($this->ch, CURLOPT_COOKIESESSION, TRUE); // do I need to turn this off to allow subsequent requests to belong to the new session? or does this only live for one request?
	}

	public function request() {
		return $this->request;
	}

	public function result() {
		// accessor to get the last cached result
		return $this->result;
	}

	public function get($url=NULL, $data='', $https=NULL) {
		if (empty($url) && empty($data) && empty($https)) return $this->result; // if we're not passing anything in, just return the last cached result
		elseif ($url === TRUE) $url = $this->request; // TRUE forces a new request from the last request, though we still allow overriding $data and $https

		$parts = parse_url($url);
		if (empty($parts['scheme'])) $parts['scheme'] = $https?'https':'http';
		// a simple concatenation of the querystring is OK, though not ideal, since PHP systems will just overwrite previous entries but not all will work that way
		if (is_array($data)) $parts['query'] = @($parts['query'].'&'.http_build_query($data)); // explicitly concat with the value of parts[query] rather than .= because it might not exist (and suppress warnings)
		elseif (!empty($data)) $parts['query'] = @($parts['query'].'&'.$data);

		$this->request = http_build_url($parts);
		$this->data = NULL;

		curl_setopt($this->ch, CURLOPT_URL, $this->request);
		// per http://stackoverflow.com/questions/4163865/how-to-reset-curlopt-customrequest
		// once we start setting CURLOPT_CUSTOMREQUEST, we have to continue setting it for each subsequent request
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($this->ch, CURLOPT_HTTPGET, TRUE);

		$this->reset_info();
		$this->result = curl_exec($this->ch);
		// we could at some future point implement more robust caching
		return $this->result;
	}

	private function encode_request_data($data) {
		if (!empty($this->opts[CURLOPT_HTTPHEADER])) {
			foreach ($this->opts[CURLOPT_HTTPHEADER] as $header) {
				$parts = preg_split('/:\s*/', $header, 2);
				if (strtolower($parts[0]) == 'content-type') {
					if ($parts[1] == 'application/json') {
						$json = json_encode($data);

						$this->opts[CURLOPT_HTTPHEADER][] = 'Content-Length: '.strlen($json);
						$this->opt(CURLOPT_HTTPHEADER, $this->opts[CURLOPT_HTTPHEADER]);

						return $json;
					}
				}
			}
		}
		return $data;
	}

	public function _custom($method='POST', $url=NULL, $data='', $https=NULL) {
		// get is handled differently from other things
		if (strtoupper($method) == 'GET') return $this->get($url, $data, $https);

		if (empty($url) && empty($data) && empty($https)) { $url = $this->request; $data = $this->data; } // if we're not passing anything in, re-perform the last request
		elseif ($url === TRUE) { $url = $this->request; $data = empty($data)||$data===TRUE?$this->data:$data; } // To be consistent with get(), we explicitly using the last request, optionally allowing us to override $data and $https

		$parts = parse_url($url);
		if (empty($parts['scheme'])) $parts['scheme'] = $https?'https':'http';

		$this->request = http_build_url($parts);

		curl_setopt($this->ch, CURLOPT_URL, $this->request);
		curl_setopt($this->ch, CURLOPT_HTTPGET, FALSE);
		if (empty($method) || strtoupper($method) == 'POST') curl_setopt($this->ch, CURLOPT_POST, TRUE);
		// per http://stackoverflow.com/questions/4163865/how-to-reset-curlopt-customrequest
		// once we start setting CURLOPT_CUSTOMREQUEST, we have to continue setting it for each subsequent request
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

		$this->data = $this->encode_request_data($data);

		// this automatically handles a string formatted as a querystring (application/x-www-form-urlencoded), and an array (multipart/form-data)
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->data);

		$this->reset_info();
		$this->result = curl_exec($this->ch);
		// we could at some future point implement more robust caching
		return $this->result;
	}

	public function post($url=NULL, $data='', $https=NULL) {
		return $this->_custom('POST', $url, $data, $https);
	}

	public function put($url, $data='', $https=NULL) {
		return $this->_custom('PUT', $url, $data, $https);
	}

	public function patch($url, $data='', $https=NULL) {
		return $this->_custom('PATCH', $url, $data, $https);
	}

	public function delete($url, $data='', $https=NULL) {
		return $this->_custom('DELETE', $url, $data, $https);
	}

	private function reset_info() {
		$this->info = NULL;
	}

	private function set_info() {
		$this->info = curl_getinfo($this->ch);
		//$this->info['__headers'] = curl_getinfo($this->ch, CURLINFO_HEADER_OUT);
		$this->info['__error'] = curl_error($this->ch);
	}

	public function status() {
		if (empty($this->info)) $this->info = curl_getinfo($this->ch);
		return $this->info['http_code'];
	}

	public function debug($print=FALSE) {
		if (empty($this->info)) $this->info = curl_getinfo($this->ch);
		$this->set_info();
		if (!empty($print)) {
			echo '<pre>';
			print_r($this->info);
			echo '</pre>';
		}
		else return $this->info;
	}

	public function debug_data($print=FALSE) {
		if (!empty($print)) {
			echo '<pre>';
			print_r($this->data);
			echo '</pre>';
		}
		else return $this->data;
	}

	public function debug_opts($print=FALSE) {
		if (!empty($print)) {
			echo '<pre>';
			print_r($this->opts);
			echo '</pre>';
		}
		else return $this->opts;
	}
}
?>