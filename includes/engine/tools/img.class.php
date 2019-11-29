<?php
class img extends media {

private $path;

public function __construct($request, $size) {
	$this->path = pathinfo($request);
	parent::__construct($this->path['extension']);

	if (!preg_match('#/([^/]+).css$#', $_REQUEST['r'], $matches)) {
		echo prepared_query::fetch("SELECT mini FROM context_media WHERE media_type = 'css' AND context = 'all'", cardinality::SINGLE);
		return;
	}
	else {
		$hash = $matches[1];
		if ($mini = prepared_query::fetch("SELECT mini FROM context_media WHERE content_hash = ?", cardinality::SINGLE, array($hash))) {
			echo $mini;
			return;
		}
		else {
			echo prepared_query::fetch("SELECT mini FROM context_media WHERE media_type = 'css' AND context = 'all'", cardinality::SINGLE);
			return;
		}
	}
}

public static function get_hash() {
	// ***implement***
}

public static function get_files($files=NULL) {
	if (empty($files)) {
		$files = fs::get_files(FSPATH.'/media-files/css/', NULL, FALSE, FALSE, FSPATH.'/media-files/css/');
	}
	elseif (is_scalar($files)) {
		if (is_file($files)) $files = array($files);
		else return FALSE;
	}
	elseif (!is_array($files)) {
		return FALSE;
	}

	$codes = array();
	foreach ($files as $file) {
		preg_match('#/(.*).css$#', $file, $matches);
		$filename = $matches[1];
		$code = file_get_contents($file);
		if (preg_match('#/*** \[(.*)\] ***/#', $code, $matches)) {
			$description = $matches[1];
		}
		else { $description = null; }

		// ***handle setting context in the css file***

		prepared_query::execute('INSERT INTO css (css_name, description, code) VALUES (:css_name, :description, :code) ON DUPLICATE KEY UPDATE description = VALUES(description), code = VALUES(code)', [':css_name' => $filename, ':description' => $description, ':code' => $code]);
	}
}

public static function condense($context=NULL) {

	require(self::$path_to_minify);

	if (($blocks = prepared_query::fetch("SELECT context, code FROM css WHERE active = true AND context IN ('all', :context) ORDER BY context", cardinality::SET, [':context' => $context])) || ($blocks = prepared_query::fetch("SELECT context, code FROM css WHERE active = true ORDER BY context", cardinality::SET))) {
		$style_sheets = array();
		foreach ($blocks as $block) {
			isset($style_sheets[$block->context])?$style_sheets[$block->context] .= "\n".$block->code:$style_sheets[$block->context] = $block->code;
		}

		foreach ($style_sheets as $context => $style_sheet) {
			if ($context != 'all') $style_sheet = $style_sheets['all']."\n".$style_sheet;

			$mini = Minify_CSS::minify($style_sheet);
			$md5 = md5($mini);

			prepared_query::execute("INSERT INTO context_media (media_type, context, pretty, mini, content_hash) VALUES ('css', ?, ?, ?, ?) ON DUPLICATE KEY UPDATE pretty = VALUES(pretty), mini = VALUES(mini), content_hash = VALUES(content_hash)", array($context, $style_sheet, $mini, $md5));
		}
	}
}

}
?>