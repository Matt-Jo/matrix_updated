<?php
// per htaccess, this file will only be called if the requested file doesn't exist

// copied from https://www.php.net/manual/en/function.realpath.php since realpath() doesn't work on non-existent files and we're using it to get a safe path
function get_absolute_path($path) {
	$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	$parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
	$absolutes = array();
	foreach ($parts as $part) {
		if ('.' == $part) continue;
		if ('..' == $part) {
			array_pop($absolutes);
		}
		else {
			$absolutes[] = $part;
		}
	}
	return implode(DIRECTORY_SEPARATOR, $absolutes);
}

$imgpath = __DIR__.'/'.get_absolute_path($_GET['img']);

$meta = pathinfo($imgpath);

$imgref = $meta['dirname'].'/'.$meta['filename'];
$imgext = $meta['extension'];

$exts = [
	'jpg' => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'gif' => 'image/gif',
	'png' => 'image/png',
	'webp' => 'image/webp',
];

$foundimg = NULL;
$foundext = NULL;

foreach ($exts as $ext => $mime) {
	if (file_exists($imgref.'.'.$ext)) {
		$foundimg = $imgref.'.'.$ext;
		$foundext = $ext;
		break;
	}
}

if (empty($foundimg)) die('Could not find image');

header('Content-type: '.$exts[$imgext]);

switch ($foundext) {
	case 'jpg':
		$image = imagecreatefromjpeg($foundimg);
		break;
	case 'png':
		$image = imagecreatefrompng($foundimg);
		imagepalettetotruecolor($image);
		imagealphablending($image, TRUE);
		imagesavealpha($image, TRUE);
		break;
	default:
		break;
}

switch ($imgext) {
	case 'webp':
		imagewebp($image, $imgpath);
		break;
	default:
		break;
}

readfile($imgpath);
?>
