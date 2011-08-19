<?php
$settingsArray = parse_ini_file('setup/settings.ini');
foreach($settingsArray as $settings=>$value)
{
	define($settings,$value);
}
//Don't change these
define('HASH_ALGO', 'tiger192,4');
ini_set('magic_quotes_gpc','Off');
ini_set('magic_quotes_runtime','Off');
ini_set('magic_quotes_sybase','Off');
ini_set('register_globals','0');
?>