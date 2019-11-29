<?php

abstract class media {

private $content_types = array(
	'css' => 'text/css',
	'js' => 'text/javascript',
	'png' => 'image/png',
	'jpeg' => 'image/jpeg',
	'jpg' => 'image/jpg',
	'gif' => 'image/gif',
	'pdf' => 'application/pdf',
	'mp3' => 'audio/mpeg',
	'wav' => 'audio/x-wav',
	'mpg' => 'video/mpeg',
	'mov' => 'video/quicktime',
	'avi' => 'video/x-msvideo'
);

protected $content_type;

public function __construct($type=NULL) {
	empty($type)?$type=get_class($this):NULL;
	$this->content_type = $this->content_types[$type];
	header("Content-Type: $this->content_type");
}

}

?>