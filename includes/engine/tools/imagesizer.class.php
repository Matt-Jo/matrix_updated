<?php
class imagesizer {

public static $map = array(
	'sm' => array('width' => 75, 'height' => 50),
	'med' => array('width' => 150, 'height' => 100),
	'dow' => array('width' => 180, 'height' => 120),
	//'300' => array('width' => 300, 'height' => 200),
	'lrg' => array('width' => 750, 'height' => 500),
	'archive' => array('width' => 2400, 'height' => 1600)
);

public static $newproduct = array(
	'sm' => 'newproduct_sm.gif',
	'med' => 'newproduct_med.gif',
	//'300' => 'newproduct_300.gif',
	'lrg' => 'newproduct.gif'
);

public static $map_default = 'lrg';

public $status = 0;
public $file;

public function __construct($source, $dimensions, $target_fs, $target) {
	if ($this->file = self::resize($source, $dimensions, $target_fs, $target)) $this->status = 1;
}

public static function resize($source, $dimensions, $target_fs, $target, $force=FALSE) {
	$source_path = pathinfo($source);
	$target_path = pathinfo($target_fs.'/'.$target);
	$config = service_locator::get_config_service();

	if (!$force && (is_file($target_fs.'/'.$target) || !$config->is_production())) return $target;

	try {
		$image = new Imagick($source);
		//$dims = $image->getImageGeometry(); // for now, don't care about the source dimensions, just resize the thing as requested

		$image->resizeImage($dimensions['width'], $dimensions['height'], imagick::FILTER_LANCZOS, .9, 1);
		//ini_set('display_errors', 1);
		$image->writeImage($target_fs.'/'.$target);
		$image->clear();
		return $target;
	}
	catch (Exception $e) {
		/*echo '<pre>';
		print_r($e);
		echo '</pre>';*/
		if (!empty($image)) $image->clear();
		return FALSE;
	}
}

/*public static function ref_300($source_ref) {
	return preg_replace('/(_(sm|med|dow|lrg))?.(jpg|gif|png)$/', '_300.$3', $source_ref);
}*/
public static function ref_dow($source_ref) {
	return preg_replace('/(_(sm|med|lrg))?.(jpg|gif|png)$/', '_dow.$3', $source_ref);
}
public static function ref_med($source_ref) {
	return preg_replace('/(_(sm|dow|lrg))?.(jpg|gif|png)$/', '_med.$3', $source_ref);
}
public static function ref_sm($source_ref) {
	return preg_replace('/(_(dow|med|lrg))?.(jpg|gif|png)$/', '_sm.$3', $source_ref);
}

public static function dim($source) {
	$image = new Imagick($source);
	$dim = $image->getImageGeometry(); // array('width' => xxx, 'height' => xxx)
	$image->clear();
	return $dim;
}

}
?>
