<?php
abstract class ck_channel extends ck_archetype {

private $default_template = 'ck_channel.mustache.html';

protected function __construct() {
	parent::__construct();
	// empty, but we cascade up in our child classes in case we should want to invoke some functionality, so leave the stub here
}

public static function add_new_entity_block($admin_action=NULL, $source_page=NULL, $target_page=NULL) {
	$content = new ck_content();

	$content->block_label = static::$internal_interface['primary_unit']['label'];
	$content->block_key = static::$internal_interface['primary_unit']['key'];

	$content->admin_action = $admin_action?$admin_action:$_SERVER['PHP_SELF'];
	$content->source_page = $source_page?$source_page:$_SERVER['PHP_SELF'];
	$content->target_page = $target_page?$target_page:$_SERVER['PHP_SELF'];

	$primary_table = static::$internal_interface['primary_table'];

	$content->fields = self::parse_fields_for_block($primary_table);

	if (static::$internal_interface['administered_tables']) {
		$accessory_fields = array();
		foreach (static::$internal_interface['administered_tables'] as $table) {
			$accessory_group = array('accessory_key' => static::$internal_interface['tables'][$table]['key'], 'accessory_name' => static::$internal_interface['tables'][$table]['label'], 'fields' => self::parse_fields_for_block($table));
			// if it's a one-to-many relationship, we can add several of these for our primary table
			static::$internal_interface['tables'][$table]['relationships'][$primary_table]['relationship']=='MANY'?$accessory_group['multiple']=TRUE:NULL;
			$accessory_fields[] = $accessory_group;
		}
		$content->accessory_fields = $accessory_fields;
	}

	// I'm creating this as an anonymous method mainly because it feels better to do this as a function rather than manage procedurally here...
	// don't have an articulated reason for that yet, so I may not stick with it, but for now I'm going with my gut.
	$content->_prepare = function() use ($content) {
		$self = $content; // make it explicit that we're using and modifying our owned properties

		$fields = $self->fields;

		foreach ($fields as $field_key => &$field) {
			$output_tokens = array();
			$class_found = FALSE;

			$output_tokens[] = 'name="'.$field['field_key'].'"';
			if (!empty($field['required'])) $output_tokens[] = 'required';
			if (!empty($field['attributes'])) {
				foreach ($field['attributes'] as $attribute => &$value) {
					if ($value === TRUE) $output_tokens[] = $attribute;
					elseif ($attribute == 'class') {
						$class_found = TRUE;
						$classes = preg_split('/\s+/', $value);
						if (!in_array('input', $classes)) {
							$classes[] = 'input';
							$value = implode(' ', $classes);
						}
						$output_tokens[] = $attribute.'="'.$value.'"';
					}
					else $output_tokens[] = $attribute.'="'.$value.'"';
				}
			}
			if (!$class_found) $output_tokens[] = 'class="input"';

			switch ($field['tag']) {
				case 'select':
					array_unshift($output_tokens, '<select');
					// add the tag close to the last attribute, since we need to get into the options
					$output_tokens[count($output_tokens)-1] = $output_tokens[count($output_tokens)-1].'>';
					$output_tokens[] = '<option value=""></option>';
					if (!empty($field['options'])) {
						foreach ($field['options'] as $option) {
							$output_tokens[] = '<option';
							!empty($option['selected'])?$output_tokens[] = 'selected':NULL;
							$output_tokens[] = 'value="'.$option['value'].'">'.$option['value'].'</option>';
						}
					}
					$output_tokens[] = '</select>';
					break;
				case 'textarea':
					array_unshift($output_tokens, '<textarea');
					if (!empty($field['wrap'])) $output_tokens[] = 'wrap="'.$field['wrap'].'"';
					$output_tokens[] = 'rows="'.$field['rows'].'"';
					// handle the last attribute:
					$endtag = 'cols="'.$field['cols'].'">';
					$endtag .= isset($field['value'])?$field['value']:'';
					$endtag .= '</textarea>';
					$output_tokens[] = $endtag;
					break;
				default:
					// everything else is an input
					array_unshift($output_tokens, '<input');
					if (isset($field['value'])) $output_tokens[] = 'value="'.$field['value'].'"';
					// the only reason we put this last is to put the closing bracket in with a token we know will be used, so it won't be separated by a space
					$output_tokens[] = 'type="'.$field['tag'].'">';
			}
			$fields[$field_key]['field_display'] = implode(' ', $output_tokens);
		}

		$self->fields = $fields;

		if ($accessory_fields = $self->accessory_fields) { // since the &__get return by reference doesn't seem to be working appropriately...
			foreach ($accessory_fields as $afidx => &$accessory_group) {
				foreach ($accessory_group['fields'] as $field_key => &$field) {
					$output_tokens = array();
					$class_found = FALSE;

					$arr = !empty($accessory_group['multiple'])?'[0]':''; // might we have to handle several of this input?

					$output_tokens[] = 'name="'.$field['field_key'].$arr.'"';
					if (!empty($field['required'])) $output_tokens[] = 'required';
					if (!empty($field['attributes'])) {
						foreach ($field['attributes'] as $attribute => &$value) {
							if ($value === TRUE) $output_tokens[] = $attribute;
							elseif ($attribute == 'class') {
								$class_found = TRUE;
								$classes = preg_split('/\s+/', $value);
								if (!in_array('input', $classes)) {
									$classes[] = 'input';
									$value = implode(' ', $classes);
								}
								$output_tokens[] = $attribute.'="'.$value.'"';
							}
							else $output_tokens[] = $attribute.'="'.$value.'"';
						}
					}
					if (!$class_found) $output_tokens[] = 'class="input"';

					switch ($field['tag']) {
						case 'select':
							array_unshift($output_tokens, '<select');
							// add the tag close to the last attribute, since we need to get into the options
							$output_tokens[count($output_tokens)-1] = $output_tokens[count($output_tokens)-1].'>';
							$output_tokens[] = '<option value=""></option>';
							if (!empty($field['options'])) {
								foreach ($field['options'] as $option) {
									$output_tokens[] = '<option';
									!empty($option['selected'])?$output_tokens[] = 'selected':NULL;
									$output_tokens[] = 'value="'.$option['value'].'">'.$option['value'].'</option>';
								}
							}
							$output_tokens[] = '</select>';
							break;
						case 'textarea':
							array_unshift($output_tokens, '<textarea');
							if (!empty($field['wrap'])) $output_tokens[] = 'wrap="'.$field['wrap'].'"';
							$output_tokens[] = 'rows="'.$field['rows'].'"';
							// handle the last attribute:
							$endtag = 'cols="'.$field['cols'].'">';
							$endtag .= isset($field['value'])?$field['value']:'';
							$endtag .= '</textarea>';
							$output_tokens[] = $endtag;
							break;
						default:
							// everything else is an input
							array_unshift($output_tokens, '<input');
							if (isset($field['value'])) $output_tokens[] = 'value="'.$field['value'].'"';
							// the only reason we put this last is to put the closing bracket in with a token we know will be used, so it won't be separated by a space
							$output_tokens[] = 'type="'.$field['tag'].'">';
					}
					$accessory_fields[$afidx]['fields'][$field_key]['field_display'] = implode(' ', $output_tokens);
				}
			}
			$self->accessory_fields = $accessory_fields;
		}
	};

	$content->_prepare();
	$GLOBALS['cktpl']->content('ck_channel.mustache.html', $content);
}

public static function parse_fields_for_block($table) {
	$fields = array();
	foreach (static::$internal_interface['tables'][$table]['fields'] as $field_key => $details) {
		if (in_array($details['default'], array('AUTO_INCREMENT', 'DEFAULT CURRENT_TIMESTAMP'))) continue; // skip the defaults we most likely don't want to override
		if (!empty(static::$internal_interface['tables'][$table]['relationships'])) {
			foreach (static::$internal_interface['tables'][$table]['relationships'] as $table => $rel) {
				if ($field_key == $rel['key']) continue 2; // skip the foreign keys that will be automatically populated
			}
		}

		$field = array('field_label' => ucwords(preg_replace('/_/', ' ', $field_key)), 'field_key' => $field_key, 'attributes' => array());

		preg_match('/^([A-Z]+)(\((.*)\))?$/', $details['type'], $matches);
		array_shift($matches); // get rid of the whole thing
		$field_type = strtoupper(array_shift($matches)); // match the actual type code
		array_shift($matches); // get rid of the parenthesis, if there are any
		$field_limits = array_shift($matches); // get the limit data, if any
		$field['field_type'] = $field_type;

		$default = $details['default']?preg_replace("/^DEFAULT '?(.+)'?$/i", '$1', $details['default']):NULL;

		$details['required']=='NOT NULL'?$field['required'] = TRUE:NULL;

		$comment = !empty($details['comment'])?trim(preg_replace("/^COMMENT '(.+)'$/i", '$1', $details['comment'])):NULL;
		// check to make sure it wasn't an empty comment
		if (!empty($comment)) $field['comment'] = $comment;

		// need to also account for fields that represent an enumerated ID from another table
		switch ($field_type) {
			case 'TINYINT':
				$field['tag'] = 'checkbox';
				$default&&$default==1?$field['attributes']['checked'] = TRUE:NULL;
				break;
			case 'CHAR':
			case 'VARCHAR':
				$field['tag'] = 'text';
				if (!empty($field_limits)) {
					$field['attributes']['maxlength'] = $field_limits;
					$width = $field_limits * 10; // roughly the number of pixels to accommodate a full field
					$field['attributes']['style'] = 'width:100%;max-width:'.$width.'px;';
				}
				!is_null($default)?$field['value'] = $default:NULL;
				break;
			case 'TEXT':
				$field['tag'] = 'textarea';
				$field['attributes']['rows'] = 3;
				$field['attributes']['cols'] = 50;
				$field['attributes']['wrap'] = 'soft';
				!is_null($default)?$field['value'] = $default:NULL;
				break;
			case 'SET':
				$field['attributes']['multiple'] = TRUE;
			case 'ENUM':
				$field['tag'] = 'select';

				$options = preg_split("/'\s*,\s*'/", trim($field_limits, "'"));

				if ($field_type == 'SET') {
					if (count($options) < 5) $field['attributes']['size'] = 3;
					else $field['attributes']['size'] = 5;
				}
				else {
					$field['attributes']['size'] = 1;
				}

				$field['options'] = array();
				foreach ($options as $option) {
					$opt = array('value' => $option);
					!is_null($default)&&$default==$option?$opt['selected'] = TRUE:NULL;
					$field['options'][] = $opt;
				}

				break;
			case 'DATE':
			case 'DATETIME':
			case 'TIMESTAMP':
				$field['tag'] = 'date';
				!is_null($default)?$field['value'] = $default:NULL;
				break;
			case 'SMALLINT':
			case 'MEDIUMINT':
			case 'INTEGER':
			case 'INT':
			case 'BIGINT':
			case 'FLOAT':
			case 'DOUBLE':
			case 'DECIMAL':
			case 'NUMERIC':
			default:
				$field['tag'] = 'text';
				$field['attributes']['style'] = 'width:35px;';
				!is_null($default)?$field['value'] = $default:NULL;
				break;
		}

		// apparently, the return by reference isn't working appropriately (???), but using an array and assigning it after the fact should be more efficient anyway.
		//$content->fields[$field['field_key']] = $field;
		$fields[] = $field;
	}
	return $fields;
}

}
?>