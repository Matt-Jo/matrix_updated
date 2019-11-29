<?php
trait rest_service {
	private function new_rest_session($opts=NULL) {
		return new rest($opts);
	}
}
?>
