<?php
// we build the requirement structure here since we're not implementing an autoloader at this time.
require_once('product_navigation.class.php');
class search extends product_navigation {

	// the main search field reference
	const search_query_key = 'keywords';

	// terms is a tokenized array of the text search entered by the customer
	public $terms = array();

	// complex terms are any words that may be used to perform complex groupings
	private $complex_terms = array(
		'and',
		'or'
	);
	// complex structures are non-word characters that may be used to perform complex groupings
	// they may or may not be individual tokens (space separated characters)
	private $complex_structures = array(
		'(',
		')',
		'"',
		"'"
	);

	public $hide_refinements = array();


	// initialize the query into usable structures for search
	public function __construct() {
		parent::__construct();

		// handle the gathering and parsing of the search terms
		if (in_array(self::search_query_key, array_keys($this->_query)))
			$search_string = strtolower(trim($this->_query[self::search_query_key]));

		if (!$search_string) $this->errs[] = 'Please enter at least one search term.';
		else $this->consume_terms($search_string);

		foreach ($this->_query as $key => $val) {
			if ($key != self::search_query_key) $this->nav_fields[$key] = $val;
		}
	}

	// handle tokenizing and formatting the text search terms appropriately
	private function consume_terms($search_string) {
		// this will parse the submitted search string and format it appropriately for sending to the search API
		// for the time being, we're just splitting the words on spaces and not supporting a more complex query language.
		// we're actually *removing* complex query groupings here, we may pre-process those separately later to support
		// complex interactions on our end since the specific search API may not support those

		// for now, remove any complex structures
		/*if ($this->complex_structures)
			$search_string = preg_replace('/['.implode('', $this->complex_structures).']/', '', $search_string);*/

		$tokens = preg_split('/\s+/', $search_string);

		// for now, remove any complex terms
		/*if ($this->complex_terms)
			$tokens = array_diff($tokens, $this->complex_terms); // array_diff only returns non-matched elements from the *first* array*/

		// remove trailing equals signs from any terms. If we want to remove any equals sign whether it comes in the middle of the word or not then we can put that before the split
		foreach ($tokens as &$token) $token = preg_replace('/=$/', '', $token);

		$this->terms = $tokens;
	}

}
?>