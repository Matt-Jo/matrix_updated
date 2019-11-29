<?php
/*
 $Id: box.php,v 1.1.1.1 2004/03/04 23:39:44 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

class box {
	public function __construct() {
		$this->heading = array();
		$this->contents = array();
	}

	public function infoBox($heading, $contents) {
		$this->table_row_parameters = 'class="infoBoxHeading"';
		$this->table_data_parameters = 'class="infoBoxHeading"';
		$this->heading = $this->tableBlock($heading);

		$this->table_row_parameters = '';
		$this->table_data_parameters = 'class="infoBoxContent"';
		$this->contents = $this->tableBlock($contents);

		return $this->heading.$this->contents;
	}

     /**
      * @param $heading
      * @param $contents
      * @return string
      */
	public function menuBox($heading, $contents) {
		global $menu_dhtml;			// add for dhtml_menu

		if ($menu_dhtml == false ) {	// add for dhtml_menu
			$this->table_data_parameters = 'class="menuBoxHeading"';
			if (!empty($heading[0]['link'])) {
				$this->table_data_parameters .= ' onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\''.$heading[0]['link'].'\'"';
				$heading[0]['text'] = '&nbsp;<a href="'.$heading[0]['link'].'" class="menuBoxHeadingLink">'.$heading[0]['text'].'</a>&nbsp;';
			}
			else {
				$heading[0]['text'] = '&nbsp;'.$heading[0]['text'].'&nbsp;';
			}
			$this->heading = $this->tableBlock($heading);
			$this->table_data_parameters = 'class="menuBoxContent"';
			$this->contents = $this->tableBlock($contents);
			return $this->heading.$this->contents. ( $dhtml_contents ?? '');
			// ## add for dhtml_menu
		}
		else {
			$selected = substr(strrchr ($heading[0]['link'], '='), 1);
			$dhtml_contents = $contents[0]['text'];
			$change_style = array ('<br>'=>' ','<BR>'=>' ', 'a href='=> 'a class="menuItem" href=','class="menuBoxContentLink"'=>' ');
			$dhtml_contents = strtr($dhtml_contents,$change_style);
			$dhtml_contents = '<div id="'.$selected.'Menu" class="menu" onmouseover="menuMouseover(event)">'. $dhtml_contents.'</div>';
			return $dhtml_contents;
		}
		// ## eof add for dhtml_menu
	}

	public $table_border = '0';
	public $table_width = '100%';
	public $table_cellspacing = '0';
	public $table_cellpadding = '2';
	public $table_parameters = '';
	public $table_row_parameters = '';
	public $table_data_parameters = '';

	public function tableBlock($contents) {
		$tableBox_string = '';

		$form_set = false;
		if (isset($contents['form'])) {
			$tableBox_string .= $contents['form']."\n";
			$form_set = true;
			array_shift($contents);
		}

		$tableBox_string .= '<table border="'.$this->table_border.'" width="'.$this->table_width.'" cellspacing="'.$this->table_cellspacing.'" cellpadding="'.$this->table_cellpadding.'"';
		if (tep_not_null($this->table_parameters)) $tableBox_string .= ' '.$this->table_parameters;
		$tableBox_string .= '>'."\n";

		for ($i=0, $n=sizeof($contents); $i<$n; $i++) {
			$tableBox_string .= ' <tr';
			if (tep_not_null($this->table_row_parameters)) $tableBox_string .= ' '.$this->table_row_parameters;
			if (isset($contents[$i]['params']) && tep_not_null($contents[$i]['params'])) $tableBox_string .= ' '.$contents[$i]['params'];
			$tableBox_string .= '>'."\n";

			if (isset($contents[$i][0]) && is_array($contents[$i][0])) {
				for ($x=0, $y=sizeof($contents[$i]); $x<$y; $x++) {
					if (isset($contents[$i][$x]['text']) && tep_not_null(isset($contents[$i][$x]['text']))) {
						$tableBox_string .= '	<td';
						if (isset($contents[$i][$x]['align']) && tep_not_null($contents[$i][$x]['align'])) $tableBox_string .= ' align="'.$contents[$i][$x]['align'].'"';
						if (isset($contents[$i][$x]['params']) && tep_not_null(isset($contents[$i][$x]['params']))) {
							$tableBox_string .= ' '.$contents[$i][$x]['params'];
						}
						elseif (tep_not_null($this->table_data_parameters)) {
							$tableBox_string .= ' '.$this->table_data_parameters;
						}
						$tableBox_string .= '>';
						if (isset($contents[$i][$x]['form']) && tep_not_null($contents[$i][$x]['form'])) $tableBox_string .= $contents[$i][$x]['form'];
						$tableBox_string .= $contents[$i][$x]['text'];
						if (isset($contents[$i][$x]['form']) && tep_not_null($contents[$i][$x]['form'])) $tableBox_string .= '</form>';
						$tableBox_string .= '</td>'."\n";
					}
				}
			}
			else {
				$tableBox_string .= '	<td';
				if (isset($contents[$i]['align']) && tep_not_null($contents[$i]['align'])) $tableBox_string .= ' align="'.$contents[$i]['align'].'"';
				if (isset($contents[$i]['params']) && tep_not_null($contents[$i]['params'])) {
					$tableBox_string .= ' '.$contents[$i]['params'];
				}
				elseif (tep_not_null($this->table_data_parameters)) {
					$tableBox_string .= ' '.$this->table_data_parameters;
				}
				$tableBox_string .= '>'.$contents[$i]['text'].'</td>'."\n";
			}

			$tableBox_string .= ' </tr>'."\n";
		}

		$tableBox_string .= '</table>'."\n";

		if ($form_set == true) $tableBox_string .= '</form>'."\n";

		return $tableBox_string;
	}
}
?>
