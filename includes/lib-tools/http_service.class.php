<?php
trait http_service {
	private function new_http_session($opts=NULL) {
		return new request($opts);
	}
}
?>
