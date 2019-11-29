<?php
class gapi {

	private $oauth = array(
		'client_id' => '923830549311.apps.googleusercontent.com',
		'client_secret' => '',
		'redirect_uri' => '',
		'developer_key' => '',
		'email' => '923830549311@developer.gserviceaccount.com',
		'password' => 'notasecret',
		'key_file' => 'google-api-php-client/d0519f538d3424b71a535fe744f46b0882248cf5-privatekey.p12',
		'urls' => array('https://www.googleapis.com/auth/analytics.readonly'),
		'analytics_id' => 'ga:8750333' //'ga:64451516'
	);
	private $application_name = array('Google_AnalyticsService' => 'Cables And Kits GAnalytics Integration');
	private $client;
	private $service;

	public $service_name;

	public function __construct($service_name='Google_AnalyticsService') {
		$this->service_name = $service_name;
		$this->oauth = (object) $this->oauth;

		require_once(DIR_FS_CATALOG.'/includes/lib-apis/google-api-php-client/src/Google_Client.php');
		require_once(DIR_FS_CATALOG.'/includes/lib-apis/google-api-php-client/src/contrib/'.$this->service_name.'.php');

		$this->client = new Google_Client();
		$this->client->setApplicationName($this->application_name[$this->service_name]);

		$this->client->setAssertionCredentials(new Google_AssertionCredentials($this->oauth->email, $this->oauth->urls, file_get_contents(__DIR__.'/'.$this->oauth->key_file)));
		$this->client->setClientId($this->oauth->client_id);
		$this->client->setAccessType('offline_access');
		$this->client->setUseObjects(true);

		$this->service = new $service_name($this->client);
	}

	public function product_traffic($product_id, $start_date, $end_date) {
		$sdt = new DateTime($start_date);
		$results = $this->service->data_ga->get($this->oauth->analytics_id, $start_date, $end_date, 'ga:visitors,ga:visits,ga:pageviews', ['filters' => 'ga:pagePath=~-p-'.$product_id.'\\.,ga:pagePath=~/pro-'.$product_id.'/', 'dimensions' => 'ga:week,ga:nthWeek,ga:year']);
		$columns = [];
		foreach ($results->columnHeaders as $headidx => $col) {
			$columns[$col->name] = $headidx;
		}
		$traffic = [];
		$week_adjust = 0;
		foreach ($results->rows as $idx => $week) {
			$date = new DateTime;
			$date->setISODate($week[$columns['ga:year']], $week[$columns['ga:week']]-$week_adjust);
			if ($idx == 0) {
				if ($date > $sdt) $week_adjust = 1;
				$date->setISODate($week[$columns['ga:year']], $week[$columns['ga:week']]-$week_adjust);
			}
			$traffic[$week[$columns['ga:nthWeek']]] = (object) array('date' => $date, 'visitors' => $week[$columns['ga:visitors']], 'entrances' => $week[$columns['ga:visits']], 'pageviews' => $week[$columns['ga:pageviews']]);
		}
		ksort($traffic);
		return (object) array('traffic_details' => $traffic, 'visitors' => $results->totalsForAllResults['ga:visitors'], 'entrances' => $results->totalsForAllResults['ga:visits'], 'pageviews' => $results->totalsForAllResults['ga:pageviews']);
	}

	// this stuff is used for per-user authentication, rather than per-app authentication
	// this block would go in the constructor
	/*$this->oauth->client_id?$this->client->setClientId($this->oauth->client_id):NULL;
	$this->oauth->client_secret?$this->client->setClientSecret($this->oauth->client_secret):NULL;
	$this->oauth->redirect_uri?$this->client->setRedirectUri($this->oauth->redirect_uri):NULL;
	$this->oauth->developer_key?$this->client->setDeveloperKey($this->oauth->developer_key):NULL;
	// set the service
	$this->auth();
	// if we got this far, we're authenticated*/
	/*private function auth() {
		// if we've already got a token stored in the session, we're already authorized
		if ($this->authdata('token')) {
			$this->client->setAccessToken($this->authdata('token'));
			return;
		}

		// otherwise, if we've got a code, then we've received authorization and we just need to store it
		if (isset($_GET['code'])) {
			$this->client->authenticate();
			$this->authdata('token', $this->client->getAccessToken());
			unset($_GET['code']);
			//return;

			$redirect = '/admin/ipn_editor.php?'.$this->authdata('qstring');
			header('Location: '.$redirect);
			exit();
		}

		// otherwise, if the access token isn't set (as we expect it not to be), go get it
		if (!$this->client->getAccessToken()) {
			$authUrl = $this->client->createAuthUrl();
			$this->authdata('qstring', $_SERVER['QUERY_STRING']);

			echo $authUrl;
			exit();

			header('Location: '.$authUrl);
			exit();

			print "<a class='login' href='$authUrl'>Connect Me!</a>";
		}
	}

	public function logout() {
		unset($_SESSION['gapi.token']);
	}

	private function authdata($key, $val=NULL) {
		if ($val) return $_SESSION['gapi.'.$key] = $val;
		else return $_SESSION['gapi.'.$key];
	}*/

}
?>