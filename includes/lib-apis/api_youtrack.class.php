<?php
class api_youtrack extends ck_master_api {
	use rest_service;

	public $rest; //private

	public static $request_options = [ // protected
		CURLOPT_HTTPHEADER => [
			'Authorization: Bearer perm:amFzb24uc2hpbm5AY2FibGVzYW5ka2l0cy5jb20=.Y2stYXBp.z7WpIefTN372prjl95E5FNpidmT7mD',
			'Accept: application/json'
		]
	];

	private static $url = 'https://cablesandkits.myjetbrains.com/youtrack';

	private static $actions = [
		'create-issue' => [
			'method' => 'PUT',
			'url' => '/rest/issue',
			'querystring' => TRUE
		],
		'set-fields' => [
			'method' => 'POST',
			'url' => NULL,
			'querystring' => TRUE
		],
		'add-attachment' => [
			'method' => 'POST',
			'url' => NULL,
			'querystring' => FALSE
		]
	];

	public function __construct() {
		$this->rest = $this->new_rest_session(self::$request_options);
	}

	public function run($service, $data, $url=NULL) { // private
		if (empty($url)) {
			$url = self::$url.self::$actions[$service]['url'];
		}

		if (self::$actions[$service]['querystring']) {
			$url .= '?'.http_build_query($data);
			$data = NULL;
		}

		return $this->rest->send(self::$actions[$service]['method'], [], $url, $data);
	}

	public function submit($details) {
		
		$issue = [
			'project' => 'A',
			'summary' => 'Matrix Bug: '.$details['summary'],
			'description' => '',
			'priority' => $details['stuck']?'Show Stopper - P1':'Efficiency Problem - P2',
		];

		$body = [];

		$body[] = $details['url'];
		$body[] = $details['description'];
		$body[] = 'Querystring: '.$details['querystring'];
		$body[] = 'Postvars: '.$details['postvars'];
		$body[] = 'Session: '.$details['sessvars'];
		$body[] = 'Benefactor: '.$details['user'];
		if (!$details['workaround']) $body[] = 'No Workaround';
		
		$issue['description'] = implode("\n\n", $body);

		$issue_link = NULL;

		$response = $this->run('create-issue', $issue);
		if ($this->rest->statusOK()) {
			$link = trim($this->rest->header()['Location']);

			$issue_link = preg_replace('#/rest#', '', $link);

			$command = ['command' => []];
			if (!$details['workaround']) $command['command'][] = 'Workaround None';
			$command['command'][] = 'Benefactor '.$details['user']; // this one needs to be last because it's a free form text field, it'll try to consume any text that comes after it

			$command['command'] = implode(' ', $command['command']);

			$response = $this->run('set-fields', $command, $link.'/execute');

			$header = self::$request_options[CURLOPT_HTTPHEADER];
			$header[] = 'Content-Type: multipart/form-data';
			$this->rest->opt(CURLOPT_HTTPHEADER, $header);

			$response = $this->run('add-attachment', ['name' => $details['page_source']], $link.'/attachment');

			if (!empty($details['screenshot'])) $response = $this->run('add-attachment', ['name' => $details['screenshot']], $link.'/attachment');
		}
		else {
			// send notice to user that the issue didn't submit
		}

		return $issue_link;
	}
}
?>
