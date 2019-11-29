<?php
interface ck_merchandising_container_interface {
	public function is_active(); // is the current container active at all?
	public function is_viewable(); // is the current container viewable in the current context?

	// these methods consolidate all of the necessary merchandising data for front end templates and schema.org markup
	public function get_template($key=NULL);
	// schema is contained in template, but also available separately to use as necessary
	public function get_schema($key=NULL);

	// URLs could be built in a number of ways
	public function get_url(ck_merchandising_container_interface $context=NULL);
	// if we're on a page that we know refers to this container, but it's not the right URL, perform a redirect
	public function url_is_correct();
	public function redirect_if_necessary();
	// if another URL should be the canonical URL, use it.
	public function get_canonical_url();

	// Page Header/Tab Titles could be built in a number of ways
	public function get_title();

	// Meta Description could be built in a number of ways
	public function get_meta_description();

	// general handling
	public function activate();
	public function deactivate();

	// set forwarding
	public function set_as_primary_container($canonical, $redirect);
	public function remove_as_primary_container();
}
?>
