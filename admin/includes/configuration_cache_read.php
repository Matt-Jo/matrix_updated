<?php
$configs = prepared_query::fetch('SELECT configuration_key as cfgKey, configuration_value as cfgValue FROM configuration ORDER BY cfgKey', cardinality::SET);
foreach ($configs as $configuration) {
	if (!defined($configuration['cfgKey'])) define($configuration['cfgKey'], $configuration['cfgValue']);
}

