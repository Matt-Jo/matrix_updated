<?php
interface nav_service_interface {
	// this is the ajax return
	public function build_json();
	// this runs the query against the implemented service
	public function query();
	// this consumes the results from the implemented service
	public function parse_results($results);
}
?>