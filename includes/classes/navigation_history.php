<?php
class navigationHistory {
	var $path, $snapshot;

	function __construct() {
		$this->reset();
	}

	function reset() {
		$this->path = [];
		$this->snapshot = [];
	}

	function add_current_page() {
		global $PHP_SELF, $cPath;

		$set = 'true';
		for ($i=0, $n=sizeof($this->path); $i<$n; $i++) {
			if (($this->path[$i]['page'] == basename($PHP_SELF))) {
				if (isset($cPath)) {
					if (!isset($this->path[$i]['get']['cPath'])) continue;
					else {
						if ($this->path[$i]['get']['cPath'] == $cPath) {
							array_splice($this->path, ($i+1));
							$set = 'false';
							break;
						}
						else {
							$old_cPath = explode('_', $this->path[$i]['get']['cPath']);
							$new_cPath = explode('_', $cPath);

							for ($j=0, $n2=sizeof($old_cPath); $j<$n2; $j++) {
								if ($old_cPath[$j] != $new_cPath[$j]) {
									array_splice($this->path, ($i));
									$set = 'true';
									break 2;
								}
							}
						}
					}
				}
				else {
					$set = 'true';
					break;
				}
			}
		}

		if ($set == 'true') {
			$this->path[] = [
				'page' => basename($PHP_SELF),
				'get' => $_GET
			];
		}
	}

	function set_snapshot($page = '') {
		global $PHP_SELF;

		if (is_array($page)) {
			$this->snapshot = [
				'page' => $page['page'],
				'get' => $page['get'],
				'post' => $page['post']
			];
		}
		else {
			$this->snapshot = [
				'page' => basename($PHP_SELF),
				'get' => $_GET,
				'post' => $_POST
			];
		}
	}

	function clear_snapshot() {
		$this->snapshot = [];
	}

	function array_to_string($array, $exclude = '', $equals = '=', $separator = '&') {
		if (!is_array($exclude)) $exclude = [];

		$get_string = '';
		if (sizeof($array) > 0) {
		    foreach($array as $key => $value) {
				if ( (!in_array($key, $exclude)) && ($key != 'x') && ($key != 'y') ) {
					$get_string .= $key.$equals.$value.$separator;
				}
			}
			$remove_chars = strlen($separator);
			$get_string = substr($get_string, 0, -$remove_chars);
		}

		return $get_string;
	}
}
?>
