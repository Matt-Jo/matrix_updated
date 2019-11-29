<?php
class address_check {
	protected $selfurl;

	public function __construct() {
		$this->selfurl = $_SERVER['PHP_SELF'];
	}

	public function disp_ship_address($addArr) {
		$dispaddr = $errdiv = $noerrdiv = "";
		$haserror = 0;

		if (is_array($addArr) && count($addArr) > 0) {
			# Company
			if (strlen($addArr['company']) > 35) {
				$dispaddr .= "<div class=\"shipaddrErrRow\">";
				$dispaddr .= "<a href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">";
				$dispaddr .= "<img id=\"company\" border=\"0\" src=\"../images/icons/error.gif\" alt=\"\" onmouseover=\"dispError('{$addArr['orders_id']}',this.id)\" onmouseout=\"closeErrDiv(this.id+'Err')\" /></a> {$addArr['company']} </div>".PHP_EOL;
				$haserror = 1;
			}
			elseif (!empty($addArr['company'])) $dispaddr .= "<div class=\"shipaddrRow\"> {$addArr['company']} </div>".PHP_EOL;

			$dispaddr .= "<div id=\"companyErr\" style=\"display:none; position:absolute;\"></div>".PHP_EOL;

			# Name
			if (!$addArr['name'] || strlen($addArr['name']) > 35) {
				$dispaddr .= "<div class=\"shipaddrErrRow\">";
				$dispaddr .= "<a href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">";
				$dispaddr .= "<img id=\"usrname\" border=\"0\" src=\"../images/icons/error.gif\" alt=\"\" onmouseover=\"dispError('{$addArr['orders_id']}',this.id)\" onmouseout=\"closeErrDiv(this.id+'Err')\" /></a> {$addArr['name']} </div>".PHP_EOL;
				$haserror = 1;
			}
			else $dispaddr .= "<div class=\"shipaddrRow\"> {$addArr['name']} </div>".PHP_EOL;

			$dispaddr .= "<div id=\"usrnameErr\" style=\"display:none; position:absolute;\"></div>".PHP_EOL;
	 
			# Street 1
			if (!$addArr['street_address'] || strlen($addArr['street_address']) > 35 || self::isPO($addArr['street_address'])) { # 410
				$dispaddr .= "<div class=\"shipaddrErrRow\">";
				$dispaddr .= "<a href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">";
				$dispaddr .= "<img id=\"address1\" border=\"0\" src=\"../images/icons/error.gif\" alt=\"\" onmouseover=\"dispError('{$addArr['orders_id']}',this.id)\" onmouseout=\"closeErrDiv(this.id+'Err')\" /></a> {$addArr['street_address']} </div>".PHP_EOL;
				$haserror = 1;
			}
			else $dispaddr .= "<div class=\"shipaddrRow\"> {$addArr['street_address']} </div>".PHP_EOL;

			$dispaddr .= "<div id=\"address1Err\" style=\"display:none; position:absolute;\"></div>".PHP_EOL;

			# Street 2
			if (strlen($addArr['suburb']) > 35) {
				$dispaddr .= "<div class=\"shipaddrErrRow\">";
				$dispaddr .= "<a href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">";
				$dispaddr .= "<img id=\"address2\" border=\"0\" src=\"../images/icons/error.gif\" alt=\"\" onmouseover=\"dispError('{$addArr['orders_id']}',this.id)\" onmouseout=\"closeErrDiv(this.id+'Err')\" /></a> {$addArr['suburb']} </div>".PHP_EOL;
				$haserror = 1;
			}
			elseif (!empty($addArr['suburb'])) $dispaddr .= "<div class=\"shipaddrRow\"> {$addArr['suburb']} </div>".PHP_EOL;

			$dispaddr .= "<div id=\"address2Err\" style=\"display:none; position:absolute;\"></div>".PHP_EOL;

			#378a - City, State & ZIP to be in the same line
			# City
			if (!$addArr['city'] || strlen($addArr['city']) > 20 || !$addArr['state'] || !self::checkZip($addArr['postcode'],$addArr['country'])) {
				$dispaddr .= "<div class=\"shipaddrErrRow\">";
				if (!$addArr['city'] || strlen($addArr['city']) > 20) {
					$dispaddr .= "<a href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">";
					$dispaddr .= "<img id=\"city\" border=\"0\" src=\"../images/icons/error.gif\" alt=\"\" onmouseover=\"dispError('{$addArr['orders_id']}',this.id)\" onmouseout=\"closeErrDiv(this.id+'Err')\" /></a> ".($addArr['city'] ? $addArr['city'] : "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;") .", ".PHP_EOL;
					$haserror = 1;
				}
				else $dispaddr .= " {$addArr['city']}, ".PHP_EOL;

				# State
				if (empty($addArr['state'])) {
					$dispaddr .= "<a href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">";
					$dispaddr .= "<img id=\"state\" border=\"0\" src=\"../images/icons/error.gif\" alt=\"\" onmouseover=\"dispError('{$addArr['orders_id']}',this.id)\" onmouseout=\"closeErrDiv(this.id+'Err')\" /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".PHP_EOL;
					$haserror = 1;
				}
				else $dispaddr .= " {$addArr['state']} ".PHP_EOL;

				# Postal Code
				if (!self::checkZip($addArr['postcode'],$addArr['country'])) {
					$dispaddr .= "<a href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">";
					$dispaddr .= "<img id=\"postcode\" border=\"0\" src=\"../images/icons/error.gif\" alt=\"\" onmouseover=\"dispError('{$addArr['orders_id']}',this.id)\" onmouseout=\"closeErrDiv(this.id+'Err')\" /></a> {$addArr['postcode']} ".PHP_EOL;
					$haserror = 1;
				}
				else $dispaddr .= " {$addArr['postcode']} ".PHP_EOL;

				$dispaddr .= "</div>".PHP_EOL;
			}
			else $dispaddr .= "<div class=\"shipaddrRow\"> {$addArr['city']}, {$addArr['state']} {$addArr['postcode']} </div>".PHP_EOL;

			$dispaddr .= "<div id=\"cityErr\" style=\"display:none; position:absolute;\"></div>".PHP_EOL;
			$dispaddr .= "<div id=\"stateErr\" style=\"display:none; position:absolute;\"></div>".PHP_EOL;
			$dispaddr .= "<div id=\"postcodeErr\" style=\"display:none; position:absolute;\"></div>".PHP_EOL;

			# Country
			if (empty($addArr['country'])) {
				$dispaddr .= "<div class=\"shipaddrErrRow\">";
				$dispaddr .= "<a href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">";
				$dispaddr .= "<img id=\"country\" border=\"0\" src=\"../images/icons/error.gif\" alt=\"\" onmouseover=\"dispError('{$addArr['orders_id']}',this.id)\" onmouseout=\"closeErrDiv(this.id+'Err')\" /></a> {$addArr['country']} </div>".PHP_EOL;
				$haserror = 1;
			}
			else $dispaddr .= "<div class=\"shipaddrRow\"> {$addArr['country']} </div>".PHP_EOL;

			$dispaddr .= "<div id=\"countryErr\" style=\"display:none; position:absolute;\"></div>".PHP_EOL;

			# Telephone
			if (!self::checkPhone($addArr['telephone'])) {
				$dispaddr .= "<div class=\"shipaddrErrRow\">";
				$dispaddr .= "<a href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">";
				$dispaddr .= "<img id=\"telephone\" border=\"0\" src=\"../images/icons/error.gif\" alt=\"\" onmouseover=\"dispError('{$addArr['orders_id']}',this.id)\" onmouseout=\"closeErrDiv(this.id+'Err')\" /></a> {$addArr['telephone']} </div>".PHP_EOL;
				$haserror = 1;
			}
			else $dispaddr .= "<div class=\"shipaddrRow\"> {$addArr['telephone']} </div>".PHP_EOL;

			$dispaddr .= "<div id=\"telephoneErr\" style=\"display:none; position:absolute;\"></div>".PHP_EOL;
		}

		$dispaddr .= "<input type=\"hidden\" id=\"addrerrors\" value=\"$haserror\" />".PHP_EOL;

		if (!empty($haserror)) {
			$errdiv = "<div id=\"shipaddrDiv\" style=\"border:1px #f00 solid;\">";
			$errdiv .= "<div id=\"shipaddrTopDiv\">";
			$errdiv .= "<div><img src=\"../images/icons/warning.gif\" alt=\"\" /> [<a class=\"shipaddrLnk\" href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">edit</a>]</div>";
			$errdiv .= "<div>";
			$errdiv .= "<div style=\"padding-top:5px;\">$dispaddr</div>";
			$errdiv .= "</div>";
			$dispaddr = $errdiv.PHP_EOL;
		}
		else {
			$noerrdiv = "<div id=\"shipaddrDiv\" style=\"border:1px #fff solid;\">";
			$noerrdiv .= "<div id=\"shipaddrNoErrDiv\">";
			$noerrdiv .= "<div>[<a class=\"shipaddrLnk\" href=\"$this->selfurl\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','correct_ship');\">edit</a>]</div>";
			$noerrdiv .= "<div>";
			$noerrdiv .= "<div style=\"padding-top:5px;\">$dispaddr</div>";
			$noerrdiv .= "</div>";
			$dispaddr = $noerrdiv.PHP_EOL;
		}
		return $dispaddr;
	}

	public function edit_ship_address($addArr) {
		$dispaddr = $errdiv = "";
		$countryArr = $statesArr = array();
		$countryArr = self::getCountries();
		$countryid = self::getCountryId($addArr['country']);
		$statesArr = self::getStates($countryid);

		if (is_array($addArr) && count($addArr) > 0) {
			# Company
			if (strlen($addArr['company']) > 35) {
				$dispaddr .= "<div class=\"shipaddrErrRow\"><input id=\"sa_company\" type=\"text\" value=\"{$addArr['company']}\" class=\"sa_inputErr\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"35\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}
			else {
				$dispaddr .= "<div class=\"shipaddrRow\"><input id=\"sa_company\" type=\"text\" value=\"{$addArr['company']}\" class=\"sa_input\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"35\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}

			# Name
			if (!$addArr['name'] || strlen($addArr['name']) > 35) {
				$dispaddr .= "<div class=\"shipaddrErrRow\"><input id=\"sa_name\" type=\"text\" value=\"{$addArr['name']}\" class=\"sa_inputErr\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"35\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}
			else {
				$dispaddr .= "<div class=\"shipaddrRow\"><input id=\"sa_name\" type=\"text\" value=\"{$addArr['name']}\" class=\"sa_input\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"35\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}

			# Street 1
			if (!$addArr['street_address'] || strlen($addArr['street_address']) > 35 || self::isPO($addArr['street_address'])) { # 410
				$dispaddr .= "<div class=\"shipaddrErrRow\"><input id=\"sa_street_address\" type=\"text\" value=\"{$addArr['street_address']}\" class=\"sa_inputErr\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"35\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}
			else {
				$dispaddr .= "<div class=\"shipaddrRow\"><input id=\"sa_street_address\" type=\"text\" value=\"{$addArr['street_address']}\" class=\"sa_input\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"35\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}

			# Street 2
			if (strlen($addArr['suburb']) > 35) {
				$dispaddr .= "<div class=\"shipaddrErrRow\"><input id=\"sa_suburb\" type=\"text\" value=\"{$addArr['suburb']}\" class=\"sa_inputErr\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"35\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}
			else {
				$dispaddr .= "<div class=\"shipaddrRow\"><input id=\"sa_suburb\" type=\"text\" value=\"{$addArr['suburb']}\" class=\"sa_input\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"35\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}

			# City
			if (!$addArr['city'] || strlen($addArr['city']) > 20) {
				$dispaddr .= "<div class=\"shipaddrErrRow\"><input id=\"sa_city\" type=\"text\" value=\"{$addArr['city']}\" class=\"sa_inputErr\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"20\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}
			else {
				$dispaddr .= "<div class=\"shipaddrRow\"><input id=\"sa_city\" type=\"text\" value=\"{$addArr['city']}\" class=\"sa_input\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" maxlength=\"20\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}

			# State
			$dispaddr .= "<div id=\"stateDiv\">".PHP_EOL;
			$dispaddr .= self::mkStateDiv($statesArr, $addArr);
			$dispaddr .= "</div>".PHP_EOL;

			# Postal Code
			if (!self::checkZip($addArr['postcode'],$addArr['country'])) {
				$dispaddr .= "<div class=\"shipaddrErrRow\"><input id=\"sa_postcode\" type=\"text\" value=\"{$addArr['postcode']}\" class=\"sa_inputErr\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" /></div>".PHP_EOL;
			}
			else {
				$dispaddr .= "<div class=\"shipaddrRow\"><input id=\"sa_postcode\" type=\"text\" value=\"{$addArr['postcode']}\" class=\"sa_input\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" /></div>".PHP_EOL;
			}

			# Country
			if (is_array($countryArr) && count($countryArr) > 0 ) {
				if (empty($addArr['country'])) {
					$dispaddr .= "<div class=\"shipaddrErrRow\">". PHP_EOL;
				}
				else {
					$dispaddr .= "<div class=\"shipaddrRow\">".PHP_EOL;
				}

				$dispaddr .= "<select id=\"sa_country\" class=\"sa_sel\" onchange=\"getStateOptions(this.value,'{$addArr['orders_id']}');\">".PHP_EOL;
				foreach ($countryArr as $countryid => $cntryArr) {
					$selected = ($addArr['country']==$cntryArr['countries_name'] || $addArr['country']==$cntryArr['countries_iso_code_2']) ? " selected=\"selected\"" : "";
					$dispaddr .= "<option value=\"$countryid\"$selected>{$cntryArr['countries_name']}</option>".PHP_EOL;
				}

				$dispaddr .= "</select>".PHP_EOL;
				$dispaddr .= "</div>".PHP_EOL;
			}

			# Telephone
			if (!self::checkPhone($addArr['telephone'])) {
				$dispaddr .= "<div class=\"shipaddrErrRow\"><input id=\"sa_telephone\" type=\"text\" value=\"{$addArr['telephone']}\" class=\"sa_inputErr\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}
			else {
				$dispaddr .= "<div class=\"shipaddrRow\"><input id=\"sa_telephone\" type=\"text\" value=\"{$addArr['telephone']}\" class=\"sa_input\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" onkeyup=\"writeMessage (this.id,'f');\" onfocus=\"writeMessage (this.id,'f');\" onblur=\"writeMessage (this.id,'b')\" /></div>".PHP_EOL;
			}
		}

		$errdiv = "<div id=\"shipaddrEditDiv\">";
		$errdiv .= "<div id=\"shipaddrTopEditDiv\">";
		$errdiv .= "<div style=\"float:right;\">";
		$errdiv .= "<input class=\"sa_button\" type=\"button\" name=\"close\" value=\"x\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','display_ship');\" />&nbsp;";
		$errdiv .= "</div><br />";
		$errdiv .= "<div>";
		$errdiv .= "<div style=\"padding-top:5px;\">$dispaddr</div>";
		$errdiv .= "<div style=\"float:right;\">";
		$errdiv .= "<input class=\"sa_button\" type=\"button\" name=\"save_addr\" value=\"Save\" onclick=\"return correctShipAdd('{$addArr['orders_id']}','save_ship');\" />&nbsp;";
		$errdiv .= "</div>";
		$dispaddr = $errdiv.PHP_EOL;

		return $dispaddr;
	}

	public function mkStateDiv($statesArr, $addArr) {
		if (is_array($statesArr) && count($statesArr)) {
			$dispstate = "<select id=\"sa_state\" class=\"sa_sel\">".PHP_EOL;
			$dispstate .= "<option value=\"0\">Select One</option>".PHP_EOL;
			foreach ($statesArr as $stateid => $stArr) {
				if ($stArr['countries_name'] == $addArr['country']) {
					$selected = ($addArr['state'] == $stArr['zone_name'] || $addArr['state'] == $stArr['zone_code']) ? " selected=\"selected\"" : "";
					$dispstate .= "<option value=\"$stateid\"{$selected}>{$stArr['zone_name']}</option>".PHP_EOL;
				}
			}
			$dispstate .= "</select>".PHP_EOL;
		}
		else {
			$dispstate = "<div class=\"shipaddrRow\"><input id=\"sa_state_name\" type=\"text\" value=\"{$addArr['state']}\" class=\"sa_input\" onkeypress=\"saCatchEvent(event,'{$addArr['orders_id']}','save_ship');\" /></div>".PHP_EOL;
		}

		return $dispstate;
	}

	########################
	##### Data Objects #####
	########################

	public function getCountries() {
		$cntryArr = prepared_query::keyed_set_fetch("select * from countries", 'countries_id');
		return $cntryArr;
	}

	public function getCountryId($countryname) {
		$id = prepared_query::fetch("select countries_id from countries where countries_name = :country_name limit 1", cardinality::SINGLE, [':country_name' => $countryname]);
		if (empty($id)) $id = 0;
		return $id;
	}

	public function getCountryName($countryid) {
		$datArr = array();
		$countryname = "";
		if (intval($countryid) > 0) {
			$countryname = prepared_query::fetch("select countries_name from countries where countries_id = :countries_id limit 1", cardinality::SINGLE, [':countries_id' => $countryid]);
		}
		return $countryname;
	}

	public function getStates($country) {
		if (intval($country) > 0) {
			return prepared_query::keyed_set_fetch("select * from zones left join countries on zones.zone_country_id = countries.countries_id where zones.zone_country_id = :country", 'zone_id', [':country' => $country]);
		}
		return NULL;
	}

	public function getStateName($zoneid) {
		$datArr = array();
		$zonename = "";
		if (intval($zoneid) > 0) {
			$zonename = prepared_query::fetch("select zone_name from zones where zones.zone_id = :zone_id", cardinality::SINGLE, [':zone_id' => $zoneid]);
		}
		return $zonename;
	}

	########################
	##### Util Objects #####
	########################

	private function checkZip($zip, $country) {
		$err = 0;
		$country = strtolower($country);
		if ($country == '223' || $country == 'us' || $country == 'united states') {
			$zip = str_replace("-", "", $zip);
			$zip = str_replace(".", "", $zip);
			$zip = trim($zip);
			if (strlen($zip) == 5 || strlen($zip) == 9) {
				$err = 1;
			}
			else {
				$err = 0;
			}
		}
		else {
			$err = 1;
		}
		return $err;
	}

	private function checkPhone($phone) {
		$err = 0;
		$phone = self::cleanPhone($phone);
		$err = (strlen($phone) == 10) ? 1 : 0;
		return $err;
	}

	public function cleanPhone($phone) {
		return preg_replace('/[^\da-zA-Z]/', '', strip_tags($phone));
	}

	# 410
	private function isPO($addr) {
		$removeArr = array('(',')','-','.',' ','/');
		$addr = strip_tags($addr);
		$ispo = (substr(strtolower(str_replace($removeArr, '', $addr)), 0, 5) == "pobox") ? 1 : 0;
		return $ispo;
	}
}
?>
