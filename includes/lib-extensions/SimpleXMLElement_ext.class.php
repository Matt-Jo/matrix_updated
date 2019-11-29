<?php
/* BUILD XML STRUCTURE AS FOLLOWS:
// this is all the code necessary to build and return an XML document

$xml = SimpleXMLElement_ext::_new(); // without any xml data to initialize, it sets the base element to <root></root>
$xml->element((object) array('attributes' => array('attribute1' => 'value', 'attribute2' => 'value2'), 'text' => 'element data 1'));
$xml->element[] = "element data 2";
$xml->container->another_element[] = "another_element data 1";
$xml->container->another_element[] = "another_element data 2";
$xml->container[]->third_group = "third_group data 1";
// can set attributes and text nodes for intermediate elements as you're going through the full structure, and any text node that has HTML elements is put into a CDATA node
$xml->container(2, (object) array('attributes' => array('attr1' => 'val1', 'attr2' => 'val2'), 'text' => 'CDATA SECTION & HTML CODE'))->fourth_group = "fourth_group data 1";

echo $xml->asXML();

// ... returns a page formatted as follows (spacing added here for legibility):

<root>
	<element attribute1="value" attribute2="value2">element data 1</element>
	<element>element data 2</element>
	<container>
		<another_element>another_element data 1</another_element>
		<another_element>another_element data 2</another_element>
	</container>
	<container>
		<third_group>third_group data 1</third_group>
	</container>
	<container attr1="val1" attr2="val2">
		<![CDATA[CDATA SECTION & HTML CODE]]>
		<fourth_group>fourth_group data 1</fourth_group>
	</container>
</root>
*/

class SimpleXMLElement_ext extends SimpleXMLElement {

private static $xslt;

public static function _new($xml=NULL, $xslt=NULL, $nocdata=NULL, $ns=NULL, $prefix=TRUE) {
	if (!$xml) $xml = '<?xml version="1.0" encoding="UTF-8"?><root></root>';
	self::$xslt = $xslt;
	$nocdata?$nocdata=LIBXML_NOCDATA:NULL;
	return new SimpleXMLElement_ext($xml, $nocdata, file_exists($xml), $ns, $prefix);
}

public function attribute_array() {
	$arr = array();
	foreach ($this->attributes() as $key => $value) {
		$arr[$key] = "$value";
	}
	return $arr;
}

public function __call($key, $args) {
	$input = $this->parse_input($args);
	if ($input->index == -1 || !isset($this->{$key}[$input->index])) {
		$ch = $this->addChild($key, '', $input->namespace);
		$input->text?$ch->addText($input->text):NULL;
		$input->index = count($this->{$key})-1;
	}
	elseif ($input->text)
		$this->{$key}[$input->index]->addText($input->text);

	if ($input->attributes) {
		foreach ($input->attributes as $attr => $value) {
			unset($this->{$key}[$input->index][$attr]);
			if (!is_object($this->{$key}[$input->index])) { echo "<pre>KEY: $key\nINPUT INDEX: $input->index\nATTR: $attr\nVALUE: $value\n"; debug_print_backtrace(); echo '</pre>'; throw new Exception('SimpleXMLElement_ext STRUCTURE ERROR'); }
			$this->{$key}[$input->index]->addAttribute($attr, $value);
		}
	}

	return $this->{$key}[$input->index];
}

public function addText($text) {
	$dom = dom_import_simplexml($this);
	$text = $text==htmlspecialchars($text)?$dom->ownerDocument->createTextNode($text):$dom->ownerDocument->createCDATASection($text);
	$dom->appendChild($text);
}

private function parse_input($args=array()) {
	$index = -1;
	$text = NULL;
	$attributes = array();
	$namespace = NULL;

	foreach ($args as $arg) {
		if (is_int($arg))
			$index = $arg;
		elseif (is_string($arg))
			$text = $arg;
		elseif (is_array($arg))
			$attributes = $arg;
		elseif (is_object($arg)) {
			$arg->text?$text = $arg->text:NULL;
			$arg->attributes?$attributes = $arg->attributes:NULL;
			$arg->namespace?$namespace = " $arg->namespace":NULL;
		}
	}

	return (object) array('index' => $index, 'text' => $text, 'attributes' => $attributes, 'namespace' => $namespace);
}

public function transform($xslt=NULL) {
	!$xslt?$xslt = self::$xslt:NULL;
	$dom = new DOMDocument();
	if (is_file($xslt)) $dom->load($xslt);
	else $dom->loadXML($xslt);
	$proc = new XSLTProcessor();
	$proc->importStylesheet($dom);

	return $proc->transformToXML(dom_import_simplexml($this)->ownerDocument);
}

}
?>