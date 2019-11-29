<?php
/*
 $Id: usps.php,v 1.47 2003/04/08 23:23:42 dgw_ Exp $
 ++++ modified as USPS Methods 2.7 03/26/04 by Brad Waite and Fritz Clapp ++++
 ++++ incorporating USPS revisions to service names ++++
 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

require_once(DIR_FS_CATALOG.'includes/classes/http_client.php');

class usps_rates {
	var $countries;

	// class constructor
	function __construct() {
		$this->types = [
			'Express' => 'Express',
			'First Class' => 'First-Class Mail',
			'Priority' => 'Priority',
			'Parcel' => 'Parcel',
			'BPM' => 'Bound Printed Material',
			'Library' => 'Library',
			'Media' => 'Media Mail'
		];

		$this->intl_types = [
			'Global Express' => 'Global Express Guaranteed',
			'Global Express Non-Doc Rect' => 'Global Express Guaranteed Non-Document Rectangular',
			'Global Express Non-Doc Non-Rect' => 'Global Express Guaranteed Non-Document Non-Rectangular',
			//'Express Mail Int' => 'Express Mail International (EMS)',
			//'Express Mail Int Flat Rate Env' => 'Express Mail International (EMS) Flat Rate Envelope',
			'Priority Mail Int' => 'Priority Mail International',
			'Priority Mail Int Flat Rate Env' => 'Priority Mail International Flat Rate Envelope',
			'Priority Mail Int Flat Rate Box' => 'Priority Mail International Flat Rate Box',
			'First-Class Mail Int' => 'First-Class Mail International'
		];

		$this->countries = $this->country_list();

		$this->sm_map = [
			'Global Express Guaranteed' => 38, # 'Global Express'
			'Global Express Guaranteed Non-Document Rectangular' => 39, #'Global Express Non-Doc Rect',
			'Global Express Guaranteed Non-Document Non-Rectangular' => 40, #'Global Express Non-Doc Non-Rect',
			//'Express Mail International (EMS)' => 41, # 'Express Mail Int',
			//'Express Mail International (EMS) Flat Rate Envelope' => 42, # 'Express Mail Int Flat Rate Env',
			'Priority Mail International' => 43, # 'Priority Mail Int',
			'Priority Mail International Flat Rate Envelope' => 44, # 'Priority Mail Int Flat Rate Env',
			'Priority Mail International Flat Rate Box' => 45, # 'Priority Mail Int Flat Rate Box',
			'First-Class Mail International' => 46, # 'First-Class Mail Int');
			'EXPRESS' => 31,
			'FIRST CLASS' => 33,
			'PRIORITY' => 32,
			'PARCEL' => 34
		];

		# 359
		$this->us_territories[] = 4; # American Samoa AS
		$this->us_territories[] = 139; # Federated States of Micronesia FM
		$this->us_territories[] = 88; # Guam GU
		$this->us_territories[] = 172; # Puerto Rico PR
		$this->us_territories[] = 232; # U.S. Virgin Islands VI
	}

	// class methods
	//delivery_address = ck_address2
	//shipping_weight = weight per box
	//shipping_num_boxes = number of boxes
	function get_quotes(ck_address2 $delivery_address, $shipping_weight, $shipping_num_boxes = 1) {
		global $transittime;
		$this->_setContainer('Variable');
		$this->_setSize('REGULAR');

		// usps doesnt accept zero weight
		$shipping_weight = ($shipping_weight < 0.1 ? 0.1 : $shipping_weight);
		$shipping_pounds = floor ($shipping_weight);
		$shipping_ounces = round(16 * ($shipping_weight - floor($shipping_weight)));
		$this->_setWeight($shipping_pounds, $shipping_ounces);

		// Added by Kevin Chen (kkchen@uci.edu); Fixes the Parcel Post Bug July 1, 2004
		// Refer to http://www.usps.com/webtools/htm/Domestic-Rates.htm documentation
		// Thanks Ryan
		if ($shipping_pounds > 35 || ($shipping_pounds == 0 && $shipping_ounces < 6)) {
			$this->_setMachinable('False');
		}
		else {
			$this->_setMachinable('True');
		}
		// End Kevin Chen July 1, 2004

		if (in_array('Display weight', explode(', ', MODULE_SHIPPING_USPS_OPTIONS))) {
			$shiptitle = ' ('.$shipping_num_boxes.' x '.$shipping_weight.'lbs)';
		}
		else {
			$shiptitle = '';
		}

		$uspsQuote = $this->_getQuote($delivery_address);
		$methods = [];
		if (is_array($uspsQuote)) {
			if (isset($uspsQuote['error'])) {
				$methods[] = $uspsQuote['error'];
			}
			else {
				$size = sizeof($uspsQuote);
				for ($i=0; $i<$size; $i++) {
					$type = key($uspsQuote[$i]);
					$cost = current(array_values($uspsQuote[$i]));

					//MMD - 033012
					if (strstr($type, 'Express Mail')) {
						$type = "EXPRESS";
					}
					elseif(strstr($type, 'Priority Mail')) {
						$type = "Priority Mail International";
					}
					elseif(strstr($type, 'Parcel Post')) {
						$type = "PARCEL";
					}

					$shipping_method_id = $this->sm_map[$type]; # 334

					$methods[] = [
						'id' => $type,
						'title' => $title,
						'shipping_method_id' => $shipping_method_id, # 334
						'cost' => ($cost + MODULE_SHIPPING_USPS_HANDLING) * $shipping_num_boxes
					];
				}
			}
		}
		else {
			$methods[] = MODULE_SHIPPING_USPS_TEXT_ERROR;
		}

		return $methods;
	}

	function _setService($service) {
		$this->service = $service;
	}

	function _setWeight($pounds, $ounces=0) {
		$this->pounds = $pounds;
		$this->ounces = $ounces;
	}

	function _setContainer($container) {
		$this->container = $container;
	}

	function _setSize($size) {
		$this->size = $size;
	}

	function _setMachinable($machinable) {
		$this->machinable = $machinable;
	}

	//delivery_address = ck_address2
	function _getQuote(ck_address2 $delivery_address) {
		global $transittime;

		if (in_array('Display transit time', explode(', ', MODULE_SHIPPING_USPS_OPTIONS))) $transit = TRUE;
		if ($delivery_address->get_header('countries_id') == SHIPPING_ORIGIN_COUNTRY || in_array($delivery_address->get_header('countries_id'), $this->us_territories)) { # 359
			$request = '<RateV4Request USERID="'.MODULE_SHIPPING_USPS_USERID.'" PASSWORD="'.MODULE_SHIPPING_USPS_PASSWORD.'">';
			$services_count = 0;

			if (isset($this->service)) {
				$this->types = [$this->service => $this->types[$this->service]];
			}

			$dest_zip = str_replace(' ', '', $delivery_address->get_header('postcode'));
			if ($delivery_address->get_header('countries_iso_code_2') == 'US') $dest_zip = substr($dest_zip, 0, 5);

			reset($this->types);
			$allowed_types = array_map(strtoupper, explode(", ", MODULE_SHIPPING_USPS_TYPES));

			while (list($key, $value) = each($this->types)) {
				if (!in_array(strtoupper($key), $allowed_types)) continue;

				$request .= '<Package ID="'.$services_count.'">'.
					'<Service>'.$key.'</Service>' .
					'<FirstClassMailType>PARCEL</FirstClassMailType>' .
					'<ZipOrigination>'.SHIPPING_ORIGIN_ZIP.'</ZipOrigination>' .
					'<ZipDestination>'.$dest_zip.'</ZipDestination>' .
					'<Pounds>'.$this->pounds.'</Pounds>' .
					'<Ounces>'.$this->ounces.'</Ounces>' .
					'<Container>'.$this->container.'</Container>' .
					'<Size>'.$this->size.'</Size>' .
					'<Machinable>'.$this->machinable.'</Machinable>' .
					'</Package>';

				if ($transit) {
					$transitreq = 'USERID="'.MODULE_SHIPPING_USPS_USERID .
						'" PASSWORD="'.MODULE_SHIPPING_USPS_PASSWORD.'">' .
						'<OriginZip>'.SHIPPING_ORIGIN_ZIP.'</OriginZip>' .
						'<DestinationZip>'.$dest_zip.'</DestinationZip>';

					switch ($key) {
						case 'Express':
							$transreq[$key] = 'API=ExpressMail&XML='.urlencode( '<ExpressMailRequest '.$transitreq.'</ExpressMailRequest>');
							break;
						case 'Priority':
							$transreq[$key] = 'API=PriorityMail&XML='.urlencode( '<PriorityMailRequest '.$transitreq.'</PriorityMailRequest>');
							break;
						case 'Parcel':
							$transreq[$key] = 'API=StandardB&XML='.urlencode( '<StandardBRequest '.$transitreq.'</StandardBRequest>');
							break;
						default:
							$transreq[$key] = '';
							break;
					}
				}

				$services_count++;
			}

			$request .= '</RateV4Request>';

			$request = 'API=RateV4&XML='.urlencode($request);
		}
		else {
			//MMD - 033012 - need to update the contents of the XML Request below to be accurate.
			$request = '<IntlRateV2Request USERID="'.MODULE_SHIPPING_USPS_USERID.'" PASSWORD="'.MODULE_SHIPPING_USPS_PASSWORD.'">' .
				'<Package ID="0">' .
				'<Pounds>'.$this->pounds.'</Pounds>' .
				'<Ounces>'.$this->ounces.'</Ounces>' .
				'<MailType>Package</MailType>' .
				'<GXG><POBoxFlag>N</POBoxFlag><GiftFlag>N</GiftFlag></GXG>' .
				'<ValueOfContents>100.00</ValueOfContents>' .
				'<Country>'.$this->countries[$delivery_address->get_header('countries_iso_code_2')].'</Country>' .
				'<Container>RECTANGULAR</Container>' .
				'<Size>REGULAR</Size><Width></Width><Length></Length><Height></Height><Girth></Girth>' .
				'</Package>' .
				'</IntlRateV2Request>';

			$request = 'API=IntlRateV2&XML='.urlencode($request);
		}

		switch (MODULE_SHIPPING_USPS_SERVER) {
			case 'production':
				$usps_server = 'production.shippingapis.com';
				$api_dll = 'shippingapi.dll';
				break;
			case 'test':
			default:
				$usps_server = 'stg-production.shippingapis.com';
				$api_dll = 'shippingapi.dll';
				break;
			// EOM - USPS May14 mod
		}

		$body = '';
		$http = new httpClient();
		if ($http->Connect($usps_server, 80)) {
			$http->addHeader('Host', $usps_server);
			$http->addHeader('User-Agent', 'osCommerce');
			$http->addHeader('Connection', 'Close');
			if ($http->Get('/'.$api_dll.'?'.$request)) $body = $http->getBody();

			if ($transit && is_array($transreq) && ($delivery_address->get_header('countries_iso_code_2') == STORE_COUNTRY)) {
				foreach($transreq as $key => $value) {
					if ($http->Get('/'.$api_dll.'?'.$value)) $transresp[$key] = $http->getBody();
				}
			}

			$http->Disconnect();
		}
		else {
			return false;
		}

		$response = [];
		while (true) {
			if ($start = strpos($body, '<Package ID=')) {
				$body = substr($body, $start);
				$end = strpos($body, '</Package>');
				$response[] = substr($body, 0, $end+10);
				$body = substr($body, $end+9);
			}
			else {
				break;
			}
		}

		$rates = [];
		if ($delivery_address->get_header('countries_id') == SHIPPING_ORIGIN_COUNTRY || in_array($delivery_address->get_header('countries_id'), $this->us_territories)) { # 359
			if (sizeof($response) == '1') {
				if (ereg('<Error>', $response[0])) {
					$number = ereg('<Number>(.*)</Number>', $response[0], $regs);
					$number = $regs[1];
					$description = ereg('<Description>(.*)</Description>', $response[0], $regs);
					$description = $regs[1];

					return ['error' => $number.' - '.$description];
				}
			}

			$n = sizeof($response);
			for ($i=0; $i<$n; $i++) {
				//if (strpos($response[$i], '<Postage>')) {
				if (strpos($response[$i], '<Postage')) { //MMD - 033012
					//$service = ereg('<Service>(.*)</Service>', $response[$i], $regs);
					$service = ereg('<MailService>(.*)</MailService>', $response[$i], $regs); //MMD - 033012
					$service = $regs[1];
					$postage = ereg('<Rate>(.*)</Rate>', $response[$i], $regs); //MMD - 033012
					$postage = $regs[1];

					$rates[] = [$service => $postage];

					if (!empty($transit)) {
						switch ($service) {
							case 'Express':
								$time = ereg('<MonFriCommitment>(.*)</MonFriCommitment>', $transresp[$service], $tregs);
								$time = $tregs[1];
								if ($time == '' || $time == 'No Data') {
									$time = '1 - 2 '.MODULE_SHIPPING_USPS_TEXT_DAYS;
								}
								else {
									$time = 'Tomorrow by '.$time;
								}
								break;
							case 'Priority':
								$time = ereg('<Days>(.*)</Days>', $transresp[$service], $tregs);
								$time = $tregs[1];
								if ($time == '' || $time == 'No Data') {
									$time = '2 - 3 '.MODULE_SHIPPING_USPS_TEXT_DAYS;
								}
								elseif ($time == '1') {
									$time .= ' '.MODULE_SHIPPING_USPS_TEXT_DAY;
								}
								else {
									$time .= ' '.MODULE_SHIPPING_USPS_TEXT_DAYS;
								}
								break;
							case 'Parcel':
								$time = ereg('<Days>(.*)</Days>', $transresp[$service], $tregs);
								$time = $tregs[1];
								if ($time == '' || $time == 'No Data') {
									$time = '4 - 7 '.MODULE_SHIPPING_USPS_TEXT_DAYS;
								}
								elseif ($time == '1') {
									$time .= ' '.MODULE_SHIPPING_USPS_TEXT_DAY;
								}
								else {
									$time .= ' '.MODULE_SHIPPING_USPS_TEXT_DAYS;
								}
								break;
							case 'First Class':
								$time = '2 - 5 '.MODULE_SHIPPING_USPS_TEXT_DAYS;
								break;
							default:
								$time = '';
								break;
						}
						if ($time != '') $transittime[$service] = ' ('.$time.')';
					}
				}
			}
		}
		else {
			if (ereg('<Error>', $response[0])) {
				$number = ereg('<Number>(.*)</Number>', $response[0], $regs);
				$number = $regs[1];
				$description = ereg('<Description>(.*)</Description>', $response[0], $regs);
				$description = $regs[1];

				return ['error' => $number.' - '.$description];
			}
			else {
				$body = $response[0];
				$services = [];
				while (true) {
					if ($start = strpos($body, '<Service ID=')) {
						$body = substr($body, $start);
						$end = strpos($body, '</Service>');
						$services[] = substr($body, 0, $end+10);
						$body = substr($body, $end+9);
					}
					else {
						break;
					}
				}
				$allowed_types = [];
				// the for loop is modified to prevent failure in case the new codes have not been set
				foreach ( explode(", ", MODULE_SHIPPING_USPS_TYPES_INTL) as $value ) {
					if (isset($this->intl_types[$value])) $allowed_types[$value] = $this->intl_types[$value];
				}
				// EOM - USPS May14 mod

				$size = sizeof($services);
				for ($i=0, $n=$size; $i<$n; $i++) {
					if (strpos($services[$i], '<Postage>')) {
						// BEGIN FIX 1/2/11
						//MMD - due to API changes on 1/2/11, we need to grab the ID attribute
						//off the Service tag and map it to the appropriate text string
						// ID				Text String
						// 4				Global Express
						// 1				Express Mail Int
						// 2				Priority Mail Int
						$service_id_mappings = [
							'4' => 'Global Express Guaranteed',
							'1' => 'Express Mail International (EMS)',
							'2' => 'Priority Mail International',
						];
						$first_quote_pos = strpos($services[$i], '"');
						$id_pos = $first_quote_pos + 1;
						$second_quote_pos = strpos($services[$i], '"', $id_pos);
						$id_length = $second_quote_pos - $id_pos;
						$service_id = substr($services[$i], $id_pos, $id_length);

						$service = "";
						if (isset($service_id_mappings[$service_id])) {
							$service = $service_id_mappings[$service_id];
						}
						//END FIX 1/2/11
						$postage = ereg('<Postage>(.*)</Postage>', $services[$i], $regs);
						$postage = $regs[1];
						$time = ereg('<SvcCommitments>(.*)</SvcCommitments>', $services[$i], $tregs);
						$time = $tregs[1];
						$time = preg_replace('/Weeks$/', MODULE_SHIPPING_USPS_TEXT_WEEKS, $time);
						$time = preg_replace('/Days$/', MODULE_SHIPPING_USPS_TEXT_DAYS, $time);
						$time = preg_replace('/Day$/', MODULE_SHIPPING_USPS_TEXT_DAY, $time);

						if ( !in_array($service, $allowed_types) ) continue;
						if (isset($this->service) && ($service != $this->service) ) {
							continue;
						}

						$rates[] = [$service => $postage, 'time' => $time];
						if ($time != '') $transittime[$service] = ' ('.$time.')';
					}
				}
			}
		}
		return ((sizeof($rates) > 0) ? $rates : false);
	}

	function country_list() {
		$list = [
			'AF' => 'Afghanistan',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa', # 359
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BA' => 'Bosnia-Herzegovina',
			'BW' => 'Botswana',
			'BR' => 'Brazil',
			'VG' => 'British Virgin Islands',
			'BN' => 'Brunei Darussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'MM' => 'Burma',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island (Australia)',
			'CC' => 'Cocos Island (Australia)',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CG' => 'Congo (Brazzaville),Republic of the',
			'ZR' => 'Congo, Democratic Republic of the',
			'CK' => 'Cook Islands (New Zealand)',
			'CR' => 'Costa Rica',
			'CI' => 'Cote d\'Ivoire (Ivory Coast)',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'TP' => 'East Timor (Indonesia)',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands',
			'FO' => 'Faroe Islands',
			'FM' => 'Federated States of Micronesia', # 359
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia, Republic of',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GB' => 'Great Britain and Northern Ireland',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GT' => 'Guatemala',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'GU' => 'Guam',	# 359
			'HT' => 'Haiti',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Laos',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia, Republic of',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte (France)',
			'MX' => 'Mexico',
			'MD' => 'Moldova',
			'MC' => 'Monaco (France)',
			'MN' => 'Mongolia',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'KP' => 'North Korea (Korea, Democratic People\'s Republic of)',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn Island',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico', # 359
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russia',
			'RW' => 'Rwanda',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts (St. Christopher and Nevis)',
			'LC' => 'Saint Lucia',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome and Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'YU' => 'Serbia-Montenegro',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovak Republic',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia (Falkland Islands)',
			'KR' => 'South Korea (Korea, Republic of)',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TG' => 'Togo',
			'TK' => 'Tokelau (Union) Group (Western Samoa)',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'VI' => 'U.S. Virgin Islands', # 359
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VA' => 'Vatican City',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam',
			'WF' => 'Wallis and Futuna Islands',
			'WS' => 'Western Samoa',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe'
		];

		return $list;
	}
}
?>
