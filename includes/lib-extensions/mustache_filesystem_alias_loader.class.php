<?php
// copied, with slight modifications, from https://stackoverflow.com/a/14900573/545332
// (written by the creator/maintainer of the PHP mustache library)
class mustache_filesystem_alias_loader extends Mustache_Loader_FilesystemLoader implements Mustache_Loader_MutableLoader {
	private $aliases = [];

	public function __construct($base_dir, $opts=[], Array $aliases=[]) {
		parent::__construct($base_dir, $opts);
		if (!empty($aliases)) $this->setTemplates($aliases);
	}

	public function load($name) {
		if (!isset($this->aliases[$name])) {
			throw new Mustache_Exception_UnknownTemplateException($name);
		}

		return parent::load($this->aliases[$name]);
	}

	// setTemplates/setTemplate are required by the MutableLoader interface
	public function setTemplates(Array $templates) {
		$this->aliases = $templates;
	}

	public function setTemplate($name, $template) {
		$this->aliases[$name] = $template;
	}

	public function add_templates(Array $templates) {
		foreach ($templates as $name => $template) {
			$this->aliases[$name] = $template;
		}
	}

	public function remove_templates($names) {
		if (!is_array($names)) $names = [$names];
		foreach ($names as $name) {
			unset($this->aliases[$name]);
		}
	}

	public function reset_templates() {
		$this->aliases = [];
	}
}
?>
