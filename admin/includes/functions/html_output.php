<?php
/*
 $Id: html_output.php,v 1.1.1.1 2004/03/04 23:39:54 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

////
// The HTML href link wrapper function
 function tep_href_link($page = '', $parameters = '', $connection = 'NONSSL') {
	if ($page == '') {
	die('</td></tr></table></td></tr></table><br><br><font color="#ff0000"><b>Error!</b></font><br><br><b>Unable to determine the page link!<br><br>Function used:<br><br>tep_href_link(\''.$page.'\', \''.$parameters.'\', \''.$connection.'\')</b>');
	}
	if ($connection == 'NONSSL') {
	$link = HTTP_SERVER.DIR_WS_ADMIN;
	} elseif ($connection == 'SSL') {
	if (ENABLE_SSL == 'true') {
		$link = HTTPS_SERVER.DIR_WS_ADMIN;
	} else {
		$link = HTTP_SERVER.DIR_WS_ADMIN;
	}
	} else {
	die('</td></tr></table></td></tr></table><br><br><font color="#ff0000"><b>Error!</b></font><br><br><b>Unable to determine connection method on a link!<br><br>Known methods: NONSSL SSL<br><br>Function used:<br><br>tep_href_link(\''.$page.'\', \''.$parameters.'\', \''.$connection.'\')</b>');
	}
	if ($parameters == '') {
	$link = $link.$page;
	} else {
	$link = $link.$page.'?'.$parameters;
	}

	while ( (substr($link, -1) == '&') || (substr($link, -1) == '?') ) $link = substr($link, 0, -1);

	return $link;
 }

////
// The HTML image wrapper function
 function tep_image($src, $alt = '', $width = '', $height = '', $params = '') {
	$image = '<img src="'.$src.'" border="0" alt="'.$alt.'"';
	if (!empty($alt)) {
	$image .= ' title=" '.$alt.' "';
	}
	if (!empty($width)) {
	$image .= ' width="'.$width.'"';
	}
	if (!empty($height)) {
	$image .= ' height="'.$height.'"';
	}
	if (!empty($params)) {
	$image .= ' '.$params;
	}
	$image .= '>';

	return $image;
 }

////
// The HTML form submit button wrapper function
// Outputs a button in the selected language
 function tep_image_submit($image, $alt = '', $parameters = '') {

	$image_submit = '<input type="image" src="'.tep_output_string(DIR_WS_LANGUAGES.$_SESSION['language'].'/images/buttons/'.$image).'" border="0" alt="'.tep_output_string($alt).'"';

	if (tep_not_null($alt)) $image_submit .= ' title=" '.tep_output_string($alt).' "';

	if (tep_not_null($parameters)) $image_submit .= ' '.$parameters;

	$image_submit .= '>';

	return $image_submit;
 }

////
// Draw a 1 pixel black line
 function tep_black_line() {
	return tep_image(DIR_WS_IMAGES.'pixel_black.gif', '', '100%', '1');
 }

////
// Output a separator either through whitespace, or with an image
 function tep_draw_separator($image = 'pixel_black.gif', $width = '100%', $height = '1') {
	return tep_image('/admin/'.DIR_WS_IMAGES.$image, '', $width, $height);
 }

////
// Output a function button in the selected language
 function tep_image_button($image, $alt = '', $params = '') {

	return tep_image(DIR_WS_LANGUAGES.$_SESSION['language'].'/images/buttons/'.$image, $alt, '', '', $params);
 }


////
// Output a form
 function tep_draw_form($name, $action, $parameters = '', $method = 'post', $params = '') {
	$form = '<form name="'.tep_output_string($name).'" action="';
	if (tep_not_null($parameters)) {
	$form .= '/admin/'.$action.'?'.$parameters;
	} else {
	$form .= '/admin/'.$action;

	}
	$form .= '" method="'.tep_output_string($method).'"';
	if (tep_not_null($params)) {
	$form .= ' '.$params;
	}
	$form .= '>';

	return $form;
 }

////
// Output a form input field
 function tep_draw_input_field($name, $value = '', $parameters = '', $required = false, $type = 'text', $reinsert_value = true) {
	$field = '<input type="'.tep_output_string($type).'" name="'.tep_output_string($name).'"';

	if (isset($GLOBALS[$name]) && ($reinsert_value == true) && is_string($GLOBALS[$name])) {
	$field .= ' value="'.tep_output_string(stripslashes($GLOBALS[$name])).'"';
	} elseif (tep_not_null($value)) {
	$field .= ' value="'.tep_output_string($value).'"';
	}

	if (tep_not_null($parameters)) $field .= ' '.$parameters;

	$field .= '/ >';

	if ($required == true) $field .= TEXT_FIELD_REQUIRED;

	return $field;
 }

////
// Output a form password field
 function tep_draw_password_field($name, $value = '', $required = false) {
	$field = tep_draw_input_field($name, $value, 'maxlength="40"', $required, 'password', false);

	return $field;
 }

////
// Output a form filefield
 function tep_draw_file_field($name, $required = false) {
	$field = tep_draw_input_field($name, '', '', $required, 'file');

	return $field;
 }

function tep_draw_selection_field($name, $type, $value='', $checked=false, $compare='', $parameter='') {
	$selection = '<input type="'.$type.'" name="'.$name.'"';
	if ($value != '') $selection .= ' value="'.$value.'"';

	if ($checked == true || (!empty($GLOBALS[$name]) && $GLOBALS[$name] == 'on') || ($value && !empty($GLOBALS[$name]) && $GLOBALS[$name] == $value) || ($value && $value == $compare)) $selection .= ' CHECKED';

	if ($parameter != '') $selection .= ' '.$parameter;

	$selection .= '>';

	return $selection;
}

////
// Output a form checkbox field
 function tep_draw_checkbox_field($name, $value = '', $checked = false, $compare = '', $parameter = '') {
	return tep_draw_selection_field($name, 'checkbox', $value, $checked, $compare, $parameter);
 }


////
// Output a form radio field
 function tep_draw_radio_field($name, $value = '', $checked = false, $compare = '', $parameter = '') {
	return tep_draw_selection_field($name, 'radio', $value, $checked, $compare, $parameter);
 }
//Admin end

////
// Output a form textarea field
 function tep_draw_textarea_field($name, $wrap, $width, $height, $text = '', $parameters = '', $reinsert_value = true) {
	$field = '<textarea id="'.tep_output_string($name).'" name="'.tep_output_string($name).'" wrap="'.tep_output_string($wrap).'" cols="'.tep_output_string($width).'" rows="'.tep_output_string($height).'"';

	if (tep_not_null($parameters)) $field .= ' '.$parameters;

	$field .= '>';

	if ( (isset($GLOBALS[$name])) && ($reinsert_value == true) ) {
	$field .= htmlspecialchars($GLOBALS[$name]);
	} elseif (tep_not_null($text)) {
	$field .= $text;
	}

	$field .= '</textarea>';

	return $field;
 }

/*Tracking contribution begin*/
////
// Output a form textbox field
 function tep_draw_textbox_field($name, $size, $numchar, $value = '', $params = '', $reinsert_value = true) {
	$field = '<input type="text" name="'.$name.'" size="'.$size.'" maxlength="'.$numchar.'" value="';
	if ($params) $field .= ''.$params;
	$field .= '';
	if ( ($GLOBALS[$name]) && ($reinsert_value) ) {
	$field .= $GLOBALS[$name];
 } elseif ($value != '') {
	$field .= trim($value);
	} else {
	$field .= trim($GLOBALS[$name]);
	}
	$field .= '">';

	return $field;
 }
/*Tracking contribution end*/

////
// Output a form hidden field
 function tep_draw_hidden_field($name, $value = '', $parameters = '') {
	$field = '<input type="hidden" name="'.tep_output_string($name).'"';

	if (tep_not_null($value)) {
	$field .= ' value="'.tep_output_string($value).'"';
	} elseif (isset($GLOBALS[$name]) && is_string($GLOBALS[$name])) {
	$field .= ' value="'.tep_output_string(stripslashes($GLOBALS[$name])).'"';
	}

	if (tep_not_null($parameters)) $field .= ' '.$parameters;

	$field .= '>';

	return $field;
 }

////
// Output a form pull down menu
 function tep_draw_pull_down_menu($name, $values, $default = '', $parameters = '', $required = false) {
	$field = '<select id="'. tep_output_string($name).'" name="'.tep_output_string($name).'"';

	if (tep_not_null($parameters)) $field .= ' '.$parameters;

	$field .= '>';

	if (empty($default) && isset($GLOBALS[$name])) $default = stripslashes($GLOBALS[$name]);

	for ($i=0, $n=sizeof($values); $i<$n; $i++) {
	$field .= '<option value="'.tep_output_string($values[$i]['id']).'" id="'.tep_output_string($values[$i]['id']).'"';
	if ($default == $values[$i]['id']) {
		$field .= ' selected="selected" ';
	}

	$field .= '>'.tep_output_string($values[$i]['text'], array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')).'</option>';
	}
	$field .= '</select>';

	if ($required == true) $field .= TEXT_FIELD_REQUIRED;

	return $field;
 }
?>
