<?php
class file_writer {
	public static function write($data, $path, $reverse=FALSE) {
		$pathinfo = pathinfo($path);

		$dir = !empty($pathinfo['dirname'])?$pathinfo['dirname']:getcwd();
		$file = $pathinfo['basename'];
		$name = $pathinfo['filename'];

		if (!is_dir($dir)) throw new fileWriterException('Path ['.$dir.'] is not a directory');
		if (!is_writable($dir)) throw new fileWriterException('Path ['.$dir.'] is not writable');

		if (empty($name)) throw new fileWriterException('File ['.$path.'] doesn\'t have a usable filename');

		if ($reverse) $name = strrev($name);

		for ($i=0; $i<min(2, strlen($name)); $i++) {
			$dir .= '/'.$name[$i];
			if (!is_dir($dir)) mkdir($dir);
		}

		$final_path = $dir.'/'.$file;

		if (!file_put_contents($final_path, $data)) throw new fileWriterException('File ['.$final_path.'] could not be written');
	}

	public static function get_path($path, $reverse=FALSE) {
		$pathinfo = pathinfo($path);

		$dir = !empty($pathinfo['dirname'])?$pathinfo['dirname']:getcwd();
		$file = $pathinfo['basename'];
		$name = $pathinfo['filename'];

		if ($reverse) $name = strrev($name);

		for ($i=0; $i<min(2, strlen($name)); $i++) {
			$dir .= '/'.$name[$i];
		}

		$final_path = $dir.'/'.$file;

		return $final_path;
	}
}

class fileWriterException extends Exception {
}
?>
