<?php
	if (!defined('CONFIRM_DELETE')) define('CONFIRM_DELETE', 'Are you sure you want to remove this package?');
	if (!defined('DELETE_PACKAGE')) define('DELETE_PACKAGE', 'Delete package');
	if (!defined('CREATE_NEW_PACKAGE')) define('CREATE_NEW_PACKAGE', 'Create new package');
	if (!defined('HEADING_ACTION')) define('HEADING_ACTION', 'Action');
	if (!defined('HEADING_ID')) define('HEADING_ID', 'Package ID');
	if (!defined('HEADING_NAME')) define('HEADING_NAME', 'Name');
	if (!defined('HEADING_NAME_TEXT')) define('HEADING_NAME_TEXT', 'Enter a name, such as a carton part number to uniquely identify this packaging part');
	if (!defined('HEADING_DESCRIPTION')) define('HEADING_DESCRIPTION', 'Description');
	if (!defined('HEADING_DESCRIPTION_TEXT')) define('HEADING_DESCRIPTION_TEXT', 'A description of the package (e.g. Medium box with velcro strip, 36" UPS Tube, Cardboard Widget Holder, etc.)');
	if (!defined('HEADING_LENGTH')) define('HEADING_LENGTH', 'Length');
	if (!defined('HEADING_LENGTH_TEXT')) define('HEADING_LENGTH_TEXT', 'Enter the package length in the system\'s unit of measurement.');
	if (!defined('HEADING_WIDTH')) define('HEADING_WIDTH', 'Width');
	if (!defined('HEADING_WIDTH_TEXT')) define('HEADING_WIDTH_TEXT', 'Enter the package width in the system\'s unit of measurement.');
	if (!defined('HEADING_HEIGHT')) define('HEADING_HEIGHT', 'Height');
	if (!defined('HEADING_HEIGHT_TEXT')) define('HEADING_HEIGHT_TEXT', 'Enter the package height in the system\'s unit of measurement.');
	if (!defined('HEADING_EMPTY_WEIGHT')) define('HEADING_EMPTY_WEIGHT', 'Empty Weight');
	if (!defined('HEADING_EMPTY_WEIGHT_TEXT')) define('HEADING_EMPTY_WEIGHT_TEXT', 'The empty weight of the package, including packing material, tape, metal bands, etc.');
	if (!defined('HEADING_MAX_WEIGHT')) define('HEADING_MAX_WEIGHT', 'Maximum Weight');
	if (!defined('HEADING_MAX_WEIGHT_TEXT')) define('HEADING_MAX_WEIGHT_TEXT', 'This package\'s maximum weight capacity in your system\'s unit of weight. Leave blank to disable maximum weight restriction.');
	if (!defined('HEADING_COST')) define('HEADING_COST', 'Cost');
	if (!defined('HEADING_COST_TEXT')) define('HEADING_COST_TEXT', 'The relative cost or preference to use this package. Lower numbered packages will be used before higher numbered packages (e.g. Given two containers of the same dimensions, use a cardboard container before one of metal.)');
	if (!defined('HEADING_DELETE')) define('HEADING_DELETE', 'delete');
	if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Packaging');
	if (!defined('HEADING_INFO')) define('HEADING_INFO', 'Package Details');
	if (!defined('ICON_INFO')) define('ICON_INFO', 'Info');
	if (!defined('INFO_CHANGE_PASSWORD')) define('INFO_CHANGE_PASSWORD', 'Change password');
	if (!defined('INFO_USERNAME')) define('INFO_USERNAME', 'Username:');
	if (!defined('MIN_LENGTH_NOT_MET')) define('MIN_LENGTH_NOT_MET', 'The package length must be set to a nonzero positive real number.');
	if (!defined('MIN_WIDTH_NOT_MET')) define('MIN_WIDTH_NOT_MET', 'The package width must be set to a nonzero positive real number.');
	if (!defined('MIN_HEIGHT_NOT_MET')) define('MIN_HEIGHT_NOT_MET', 'The package height must be set to a nonzero positive real number.');
	if (!defined('MIN_EMPTY_WEIGHT_NOT_MET')) define('MIN_EMPTY_WEIGHT_NOT_MET', 'The empty package weight must be set to zero or a positive real number.');
	if (!defined('MIN_MAX_WEIGHT_NOT_MET')) define('MIN_MAX_WEIGHT_NOT_MET', 'The maximum package weight must be set to zero (disabled) or a positive real number.');

	if (!defined('NEW_PACKAGE')) define('NEW_PACKAGE', 'Create new package');
	if (!defined('NO_PACKAGES_DEFINED')) define('NO_PACKAGES_DEFINED', "No packages have been defined.");
	if (!defined('UPDATE_PACKAGE')) define('UPDATE_PACKAGE', 'Update package');
?>