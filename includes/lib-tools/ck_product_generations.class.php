<?php
class ck_product_generations {
	private $generations;

	public function __construct(Array $products_ids) {
		$this->reset();
		$products = array_flip($products_ids);
		$this->create_generation($products);
	}

	public function reset() {
		$this->generations = [];
	}

	public function get_current_level() {
		return count($this->generations);
	}

	public function get_current_index() {
		return count($this->generations)-1;
	}

	public function create_generation(Array $products_ids) {
		$products = [];

		$next_level = $this->get_current_index() + 1;

		foreach ($products_ids as $products_id => $parent) {
			if (!($parent instanceof ck_product_node)) {
				if ($next_level != 0) throw new CKProductGenerationsException('Nodes without a parent must be added in the first generation');
				$node = new ck_product_node($products_id);
			}
			else $node = new ck_product_node($products_id, $parent);

			$products[$products_id] = $node;
		}

		$this->generations[$next_level] = $products;
	}

	public function append_generation(Array $products) {
		$this->generations[$this->get_current_index() + 1] = $products;
	}

	public function get_current_generation() {
		return $this->generations[$this->get_current_index()];
	}

	public function get_generation($index) {
		return $this->generations[$index];
	}
}

class ck_product_node {
	private $products_id;
	private $links = [];
	private $previous;

	private $props = [];

	public function __construct($products_id, ck_product_node $link=NULL) {
		$this->reset();

		$this->products_id = $products_id;

		$this->previous = $link;
		if (!empty($link)) $link->link($this);
	}

	public function reset() {
		$this->previous = NULL;
		$this->links = [];
	}

	public function id() {
		return $this->products_id;
	}

	public function __toString() {
		return $this->id();
	}

	public function previous() {
		return $this->previous;
	}

	public function next() {
		return $this->links;
	}

	public function link(ck_product_node $link) {
		$this->links[] = $link;
	}

	public function progenitor() {
		if ($this->is_progenitor()) return $this;
		else return $this->previous->progenitor();
	}

	public function is_progenitor() {
		return empty($this->previous);
	}

	public function set_prop($prop, $value) {
		$this->props[$prop] = $value;
	}

	public function unset_prop($prop) {
		unset($this->props[$prop]);
	}

	public function has_prop($prop) {
		if (isset($this->props[$prop])) return TRUE;
		else return FALSE;
	}

	public function get_prop($prop) {
		if ($this->has_prop($prop)) return $this->props[$prop];
		else return NULL;
	}

	public function get_props() {
		return $this->props;
	}

	public function has_prop_tree($prop) {
		if ($this->has_prop($prop)) return TRUE;
		elseif (!$this->is_progenitor()) return $this->previous->has_prop_tree($prop);
		else return FALSE;
	}

	public function get_prop_tree($prop) {
		if ($this->has_prop($prop)) return $this->get_prop($prop);
		elseif (!$this->is_progenitor()) return $this->previous->get_prop_tree($prop);
		else return NULL;
	}

	public function get_next_prop_node($prop) {
		if ($this->has_prop($prop)) return $this;
		elseif (!$this->is_progenitor()) return $this->previous->get_next_prop_node($prop);
		else return NULL;
	}

	public function get_family($rev=FALSE) {
		$nodes = [];

		$generation = $this->progenitor();

		do {
			$nodes[] = $generation;
			if (is_object($generation)) {
				$generation = $generation->next();
			}
			else {
				$generation = array_reduce($generation, function($nodes, $node) {
					$nodes = array_merge($nodes, $node->next());
				}, []);
			}
		}
		while ($generation);

		return $rev?array_reverse($nodes):$nodes;
	}
}

class CKProductGenerationsException extends Exception {
}
?>
