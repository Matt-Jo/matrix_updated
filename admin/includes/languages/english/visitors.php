<?php
/*
 $Page ID: visitors.php,v2.3, Dated: 01 April 2005 [Visitors File] Ian-San Phocea$
 http://www.gowebtools.com

 The Exchange Project - Community Made Shopping!
 http://www.theexchangeproject.org

 Copyright (c) 2000,2001 The Exchange Project

 Released under the GNU General Public License
*/

if (!defined('TOP_BAR_TITLE')) define('TOP_BAR_TITLE', 'Web Stats');
if (!defined('NAVBAR_TITLE')) define('NAVBAR_TITLE', 'Web Stats');
if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Visitor Web Stats');
if (!defined('CHART_BOX_TITLE')) define('CHART_BOX_TITLE', 'Visitor Charts');
if (!defined('CHART_TITLE')) define('CHART_TITLE', 'Last Access:');
if (!defined('HEADING_TITLE_SEARCH')) define('HEADING_TITLE_SEARCH', 'Search:');

if (!defined('TABLE_HEADING_NUMBER')) define('TABLE_HEADING_NUMBER', 'Id.');
if (!defined('TABLE_HEADING_DATE')) define('TABLE_HEADING_DATE', 'First Hit');
if (!defined('TABLE_HEADING_TRACE_DATE')) define('TABLE_HEADING_TRACE_DATE', 'Date');
if (!defined('TABLE_HEADING_TRACE_TIME')) define('TABLE_HEADING_TRACE_TIME', 'Time');
if (!defined('TABLE_HEADING_ONLINE')) define('TABLE_HEADING_ONLINE', 'Time Online<br/>H:M:S');
if (!defined('TABLE_HEADING_COUNTER')) define('TABLE_HEADING_COUNTER', 'Hits Today');
if (!defined('TABLE_HEADING_CUSTOMER')) define('TABLE_HEADING_CUSTOMER', 'Customer ID');
if (!defined('TABLE_HEADING_IP')) define('TABLE_HEADING_IP', 'IP Address');
if (!defined('TABLE_HEADING_BLANGUAGE')) define('TABLE_HEADING_BLANGUAGE', 'Browser Language');
if (!defined('TABLE_HEADING_LANGUAGE')) define('TABLE_HEADING_LANGUAGE', 'Site Language');
if (!defined('TABLE_HEADING_REFERER')) define('TABLE_HEADING_REFERER', 'Referring URL');
if (!defined('TABLE_HEADING_URI')) define('TABLE_HEADING_URI', 'Visited Page');
if (!defined('TABLE_HEADING_KEYWORD_NAME')) define('TABLE_HEADING_KEYWORD_NAME', 'Keywords');
if (!defined('TABLE_HEADING_KEYWORD_NUMBER')) define('TABLE_HEADING_KEYWORD_NUMBER', 'Times Used');
if (!defined('TABLE_HEADING_FOOTER_COUNT')) define('TABLE_HEADING_FOOTER_COUNT', 'Total Hits:');

if (!defined('STATISTICS_TYPE_REPORT_A')) define('STATISTICS_TYPE_REPORT_A', 'Visits');
if (!defined('STATISTICS_TYPE_REPORT_B')) define('STATISTICS_TYPE_REPORT_B', 'Hits');
if (!defined('STATISTICS_TYPE_REPORT_C')) define('STATISTICS_TYPE_REPORT_C', 'Other');

if (!defined('STATISTICS_HEADING_HOURS')) define('STATISTICS_HEADING_HOURS', 'Hours');
if (!defined('STATISTICS_HEADING_DAYS')) define('STATISTICS_HEADING_DAYS', 'Days');
if (!defined('STATISTICS_HEADING_OTHER_DATE')) define('STATISTICS_HEADING_OTHER_DATE', 'Other Date / Time');
if (!defined('STATISTICS_HEADING_OTHER_VALUE')) define('STATISTICS_HEADING_OTHER_VALUE', 'Other Value');
if (!defined('STATISTICS_HEADING_X')) define('STATISTICS_HEADING_X', 'Chart X-Axis');
if (!defined('STATISTICS_HEADING_Y')) define('STATISTICS_HEADING_Y', 'Y-Axis');

define('STATISTICS_TYPE_REPORT_1', 'Recent 24 Hours');
define('STATISTICS_TYPE_REPORT_2', 'By Days this Month');
define('STATISTICS_TYPE_REPORT_3', 'By Months this Year');
define('STATISTICS_TYPE_REPORT_4', 'Sum all-time by Year');
define('STATISTICS_TYPE_REPORT_5', 'Sum all-time by Hour');
define('STATISTICS_TYPE_REPORT_6', 'By Day of Month this Year');

define('STATISTICS_TYPE_REPORT_7', 'Average all per IP by Days');
define('STATISTICS_TYPE_REPORT_8', 'Sum all-time by Browser Language');
define('STATISTICS_TYPE_REPORT_9', 'Sum all-time by Minutes Online');
define('STATISTICS_TYPE_REPORT_10', 'Search Engine Keywords for past '.KEYWORD_DURATION.' Days');
define('STATISTICS_TYPE_REPORT_11', 'Sum all-time by IP Country');
define('STATISTICS_TYPE_REPORT_12', 'Sum all-time by Browser Language');
define('STATISTICS_TYPE_REPORT_13', 'Sum all-time by IP Country');

define('STATISTICS_TYPE_REPORT_20', 'Yesterday by Hours');
define('STATISTICS_TYPE_REPORT_21', 'Same day Last Week');
define('STATISTICS_TYPE_REPORT_22', 'By Days last 2 Months');

define('STATISTICS_TYPE_REPORT_23', 'Recent 24 Hours');
define('STATISTICS_TYPE_REPORT_24', 'By Days this Month');
define('STATISTICS_TYPE_REPORT_25', 'By Months this Year');
define('STATISTICS_TYPE_REPORT_26', 'Sum all-time by Year');
define('STATISTICS_TYPE_REPORT_27', 'Sum all-time by Hour');
define('STATISTICS_TYPE_REPORT_28', 'By Day of Month this Year');

define('STATISTICS_TYPE_REPORT_29', 'Yesterday by Hours');
define('STATISTICS_TYPE_REPORT_30', 'Same day Last Week');
define('STATISTICS_TYPE_REPORT_31', 'By Days last 2 Months');
define('STATISTICS_TYPE_REPORT_32', 'This Year Trend');
define('STATISTICS_TYPE_REPORT_33', 'This Year Trend');

define('STATISTICS_TYPE_REPORT_34', 'Sum all-time by Minutes Online');
define('STATISTICS_TYPE_REPORT_35', 'By Day of the Week this Year');
define('STATISTICS_TYPE_REPORT_36', 'By Day of the Week this Year');
define('STATISTICS_TYPE_REPORT_37', 'Sum by Quarters this Year');
define('STATISTICS_TYPE_REPORT_38', 'Sum by Quarters this Year');

define('STATISTICS_TYPE_REPORT_39', 'Sum by Week this Year');
define('STATISTICS_TYPE_REPORT_40', 'Sum by Week this Year');
define('STATISTICS_TYPE_REPORT_41', 'Average all by Hour');
define('STATISTICS_TYPE_REPORT_42', 'By Months last Year');
define('STATISTICS_TYPE_REPORT_43', 'By Days last Month');
define('STATISTICS_TYPE_REPORT_44', 'By Days last Month');
define('STATISTICS_TYPE_REPORT_45', 'By Months last Year');

if (!defined('HEADING_TYPE_DAILY')) define('HEADING_TYPE_DAILY', 'One Month');
if (!defined('HEADING_TYPE_MONTHLY')) define('HEADING_TYPE_MONTHLY', 'One Year');
if (!defined('HEADING_TYPE_YEARLY')) define('HEADING_TYPE_YEARLY', 'All Years');

if (!defined('TITLE_TYPE')) define('TITLE_TYPE', 'Type:');
if (!defined('TITLE_YEAR')) define('TITLE_YEAR', 'Year:');
if (!defined('TITLE_MONTH')) define('TITLE_MONTH', 'Month:');

if (!defined('TOTAL_HITS')) define('TOTAL_HITS', 'Total Hits:');
if (!defined('BUTTON_REFRESH')) define('BUTTON_REFRESH', 'refresh table');
if (!defined('RANGE_TO')) define('RANGE_TO', 'To:');
if (!defined('RANGE_FROM')) define('RANGE_FROM', 'From:');

// How many Countries shall I show in the Country Chart excluding Robots and Others?
// Default number is set to 19 in the main program, change it here if you wish.
if (!defined('NO_COUNTRIES_FOR_CHART')) define('NO_COUNTRIES_FOR_CHART', '19');

$GEOIP_COUNTRY_NAMES = array(
"Unknown/LAN", "Asia/Pacific Region", "Europe", "Andorra", "United Arab Emirates",
"Afghanistan", "Antigua and Barbuda", "Anguilla", "Albania", "Armenia",
"Netherlands Antilles", "Angola", "Antarctica", "Argentina", "American Samoa",
"Austria", "Australia", "Aruba", "Azerbaijan", "Bosnia and Herzegovina",
"Barbados", "Bangladesh", "Belgium", "Burkina Faso", "Bulgaria", "Bahrain",
"Burundi", "Benin", "Bermuda", "Brunei Darussalam", "Bolivia", "Brazil",
"Bahamas", "Bhutan", "Bouvet Island", "Botswana", "Belarus", "Belize",
"Canada", "Cocos (Keeling) Islands", "Congo, The Democratic Republic of the",
"Central African Republic", "Congo", "Switzerland", "Cote D'Ivoire", "Cook
Islands", "Chile", "Cameroon", "China", "Colombia", "Costa Rica", "Cuba", "Cape
Verde", "Christmas Island", "Cyprus", "Czech Republic", "Germany", "Djibouti",
"Denmark", "Dominica", "Dominican Republic", "Algeria", "Ecuador", "Estonia",
"Egypt", "Western Sahara", "Eritrea", "Spain", "Ethiopia", "Finland", "Fiji",
"Falkland Islands (Malvinas)", "Micronesia, Federated States of", "Faroe
Islands", "France", "France, Metropolitan", "Gabon", "United Kingdom",
"Grenada", "Georgia", "French Guiana", "Ghana", "Gibraltar", "Greenland",
"Gambia", "Guinea", "Guadeloupe", "Equatorial Guinea", "Greece", "South Georgia
and the South Sandwich Islands", "Guatemala", "Guam", "Guinea-Bissau",
"Guyana", "Hong Kong", "Heard Island and McDonald Islands", "Honduras",
"Croatia", "Haiti", "Hungary", "Indonesia", "Ireland", "Israel", "India",
"British Indian Ocean Territory", "Iraq", "Iran, Islamic Republic of",
"Iceland", "Italy", "Jamaica", "Jordan", "Japan", "Kenya", "Kyrgyzstan",
"Cambodia", "Kiribati", "Comoros", "Saint Kitts and Nevis", "Korea, Democratic
People's Republic of", "Korea, Republic of", "Kuwait", "Cayman Islands",
"Kazakstan", "Lao People's Democratic Republic", "Lebanon", "Saint Lucia",
"Liechtenstein", "Sri Lanka", "Liberia", "Lesotho", "Lithuania", "Luxembourg",
"Latvia", "Libyan Arab Jamahiriya", "Morocco", "Monaco", "Moldova, Republic
of", "Madagascar", "Marshall Islands", "Macedonia, the Former Yugoslav Republic
of", "Mali", "Myanmar", "Mongolia", "Macau", "Northern Mariana Islands",
"Martinique", "Mauritania", "Montserrat", "Malta", "Mauritius", "Maldives",
"Malawi", "Mexico", "Malaysia", "Mozambique", "Namibia", "New Caledonia",
"Niger", "Norfolk Island", "Nigeria", "Nicaragua", "Netherlands", "Norway",
"Nepal", "Nauru", "Niue", "New Zealand", "Oman", "Panama", "Peru", "French
Polynesia", "Papua New Guinea", "Philippines", "Pakistan", "Poland", "Saint
Pierre and Miquelon", "Pitcairn", "Puerto Rico", "Palestinian Territory,
Occupied", "Portugal", "Palau", "Paraguay", "Qatar", "Reunion", "Romania",
"Russian Federation", "Rwanda", "Saudi Arabia", "Solomon Islands",
"Seychelles", "Sudan", "Sweden", "Singapore", "Saint Helena", "Slovenia",
"Svalbard and Jan Mayen", "Slovakia", "Sierra Leone", "San Marino", "Senegal",
"Somalia", "Suriname", "Sao Tome and Principe", "El Salvador", "Syrian Arab
Republic", "Swaziland", "Turks and Caicos Islands", "Chad", "French Southern
Territories", "Togo", "Thailand", "Tajikistan", "Tokelau", "Turkmenistan",
"Tunisia", "Tonga", "East Timor", "Turkey", "Trinidad and Tobago", "Tuvalu",
"Taiwan", "Tanzania, United Republic of", "Ukraine",
"Uganda", "United States Minor Outlying Islands", "United States", "Uruguay",
"Uzbekistan", "Holy See (Vatican City State)", "Saint Vincent and the
Grenadines", "Venezuela", "Virgin Islands, British", "Virgin Islands, U.S.",
"Vietnam", "Vanuatu", "Wallis and Futuna", "Samoa", "Yemen", "Mayotte",
"Yugoslavia", "South Africa", "Zambia", "Zaire", "Zimbabwe"
);

if (!defined('ERROR_GRAPHS_DIRECTORY_DOES_NOT_EXIST')) define('ERROR_GRAPHS_DIRECTORY_DOES_NOT_EXIST', 'Error: Graphs directory does not exist. Please create a \'graphs\' directory inside \'images\'.');
if (!defined('ERROR_GRAPHS_DIRECTORY_NOT_WRITEABLE')) define('ERROR_GRAPHS_DIRECTORY_NOT_WRITEABLE', 'Error: Graphs directory is not writeable.');

if (!defined('SORT_UP')) define('SORT_UP', 'Sort Ascending');
if (!defined('SORT_DOWN')) define('SORT_DOWN', 'Sort Descending');
if (!defined('TOP')) define('TOP', 'Return to Top');

if (!defined('DISPLAY_HITS')) define('DISPLAY_HITS', 'Page Views');
if (!defined('CHART_TITLE')) define('CHART_TITLE', 'Web Stats - '.DISPLAY_HITS);
if (!defined('TABLE_HEADING_TABLE')) define('TABLE_HEADING_TABLE', DISPLAY_HITS);
if (!defined('CHART_X')) define('CHART_X', TABLE_HEADING_NUMBER);
if (!defined('CHART_Y')) define('CHART_Y', 'Actual');

if (!defined('TEXT_HEADING_DELETE')) define('TEXT_HEADING_DELETE', 'Cleanup Options');
if (!defined('TEXT_FOOTER_DELETE')) define('TEXT_FOOTER_DELETE', 'Select option above and confirm on next Page');

if (!defined('TEXT_HEADING_EMPTY')) define('TEXT_HEADING_EMPTY', 'Delete All');
if (!defined('TEXT_HEADING_ROBOTS')) define('TEXT_HEADING_ROBOTS', 'Delete All Robots');
if (!defined('TEXT_HEADING_GUESTS')) define('TEXT_HEADING_GUESTS', 'Delete All Guests');
if (!defined('TEXT_HEADING_DATE')) define('TEXT_HEADING_DATE', 'Delete By Date');
if (!defined('TEXT_HEADING_BY_ID')) define('TEXT_HEADING_BY_ID', 'Delete by ID');

if (!defined('TEXT_EDIT_EMPTY')) define('TEXT_EDIT_EMPTY', 'Press confirm to remove All Entries:');
if (!defined('TEXT_EDIT_ROBOTS')) define('TEXT_EDIT_ROBOTS', 'Press confirm to remove All Entries made by Robots:');
if (!defined('TEXT_EDIT_GUESTS')) define('TEXT_EDIT_GUESTS', 'Press confirm to remove All Entries made by Guests:');
if (!defined('TEXT_EDIT_DATE')) define('TEXT_EDIT_DATE', 'Press confirm to remove All Entries up to the Date shown below:');
if (!defined('TEXT_EDIT_BY_ID')) define('TEXT_EDIT_BY_ID', 'Press confirm to remove a single Entry for the ID you enter below:');

if (!defined('TITLE_DAY')) define('TITLE_DAY', 'Before Day');
if (!defined('TITLE_MONTH')) define('TITLE_MONTH', 'Before Month');
if (!defined('TITLE_YEAR')) define('TITLE_YEAR', 'Before Year');

if (!defined('IMAGE_DELETE_BY_ID')) define('IMAGE_DELETE_BY_ID', 'Delete by ID');
if (!defined('IMAGE_DELETE_DATE')) define('IMAGE_DELETE_DATE', 'Delete by Date');
if (!defined('IMAGE_DELETE_ROBOTS')) define('IMAGE_DELETE_ROBOTS', 'Delete all Robots');
if (!defined('IMAGE_DELETE_GUESTS')) define('IMAGE_DELETE_GUESTS', 'Delete all Guests');

if (!defined('ROBOT_SWITCH_LIMITED')) define('ROBOT_SWITCH_LIMITED', 'Click Red to exclude Robots');
if (!defined('ROBOT_SWITCH_FULL')) define('ROBOT_SWITCH_FULL', 'Click Green to include Robots');
if (!defined('VISITOR_ICON_FULL_ACTIVE')) define('VISITOR_ICON_FULL_ACTIVE', 'Robots included');
if (!defined('VISITOR_ICON_LIMITED')) define('VISITOR_ICON_LIMITED', 'Click to exclude Robots');
if (!defined('VISITOR_ICON_FULL')) define('VISITOR_ICON_FULL', 'Click to include Robots');
if (!defined('VISITOR_ICON_LIMITED_ACTIVE')) define('VISITOR_ICON_LIMITED_ACTIVE', 'Robots excluded');

if (!defined('GUEST_SWITCH_FULL')) define('GUEST_SWITCH_FULL', 'Click Green to only include customers');
if (!defined('GUEST_SWITCH_LIMITED')) define('GUEST_SWITCH_LIMITED', 'Click on Red to see all traces');
if (!defined('VISITOR_ICONG_FULL_ACTIVE')) define('VISITOR_ICONG_FULL_ACTIVE', 'Customers only');
if (!defined('VISITOR_ICONG_LIMITED')) define('VISITOR_ICONG_LIMITED', 'Click to see all traces');
if (!defined('VISITOR_ICONG_FULL')) define('VISITOR_ICONG_FULL', 'Click to only include customers');
if (!defined('VISITOR_ICONG_LIMITED_ACTIVE')) define('VISITOR_ICONG_LIMITED_ACTIVE', 'All traces Shown');

if (!defined('GUEST')) define('GUEST', 'Guest');
if (!defined('BUTTON_REFRESH_CHART')) define('BUTTON_REFRESH_CHART', 'refresh chart');
if (!defined('BUTTON_REFRESH_TEXT')) define('BUTTON_REFRESH_TEXT', '<font class="smalltext" color="#FF0000">Click Here to update the Chart Image</font>');

if (!defined('ERROR_NO_DATA')) define('ERROR_NO_DATA', 'Nothing to delete!');
if (!defined('POPUP_CLOSE')) define('POPUP_CLOSE', 'Add more items or <a href="Javascript:close()">[Close Window]</a>');
if (!defined('BOX_TITLE_TRACE')) define('BOX_TITLE_TRACE', 'Visitor Trace');

if (!defined('TABLE_ROOT')) define('TABLE_ROOT', 'Root Directory');
if (!defined('TABLE_DIRECT')) define('TABLE_DIRECT', 'Direct');
if (!defined('TABLE_HEADING_HOST')) define('TABLE_HEADING_HOST', 'Host');

?>
