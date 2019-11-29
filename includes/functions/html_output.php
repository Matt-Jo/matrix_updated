<?php
function tep_image($src, $alt = '', $width = '', $height = '', $parameters = '') {
	if ( (empty($src) || ($src == DIR_WS_IMAGES)) && (IMAGE_REQUIRED == 'false') ) {
		return false;
	}

	// alt is added to the img tag even if it is null to prevent browsers from outputting
	// the image filename as default
	$image = '<img src="'.tep_output_string($src).'" border="0" alt="'.tep_output_string($alt).'"';

	if (tep_not_null($alt)) {
		$image .= ' title="'.tep_output_string($alt).'"';
	}

	if (tep_not_null($width) && tep_not_null($height)) {
		$image .= ' width="'.tep_output_string($width).'" height="'.tep_output_string($height).'"';
	}

	if (tep_not_null($parameters)) $image .= ' '.$parameters;

	$image .= '>';

	return $image;
}

function tep_image_button($image, $alt = '', $parameters = '') {
	return tep_image('templates/Pixame_v1/images/buttons/'.$_SESSION['language'].'/'.$image, $alt, '', '', $parameters);
}

function tep_draw_separator($image = 'pixel_black.gif', $width = '100%', $height = '1') {
	return tep_image(DIR_WS_IMAGES.$image, 'Seperator Image', $width, $height);
}

function tep_draw_pull_down_menu($name, $values, $default = '', $parameters = '', $required = false) {
	$field = '<select name="'.tep_output_string($name).'"';

	if (tep_not_null($parameters)) $field .= ' '.$parameters;

	$field .= '>';

	if (empty($default) && isset($GLOBALS[$name])) $default = stripslashes($GLOBALS[$name]);

	for ($i=0, $n=sizeof($values); $i<$n; $i++) {
		$field .= '<option value="'.tep_output_string($values[$i]['id']).'"';
		if ($default == $values[$i]['id']) {
			$field .= ' SELECTED';
		}

		$field .= '>'.tep_output_string($values[$i]['text'], array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')).'</option>';
	}
	$field .= '</select>';

	if ($required == true) $field .= TEXT_FIELD_REQUIRED;

	return $field;
}

function tep_get_country_list($name, $selected = '', $parameters = '') {
	$countries_array = array(array('id' => '', 'text' => 'Please Select'));
	$countries = prepared_query::fetch('SELECT countries_id, countries_name FROM countries ORDER BY countries_name', cardinality::SET);

	for ($i=0, $n=sizeof($countries); $i<$n; $i++) {
		$countries_array[] = array('id' => $countries[$i]['countries_id'], 'text' => $countries[$i]['countries_name']);
	}

	return tep_draw_pull_down_menu($name, $countries_array, $selected, $parameters);
}
?>
