<?php
//failsafe settings array just in case settings array fails to load or values are missing.
$settingsFailSafe = array(
	'DB_SERVER'=>'127.0.0.1',
	'DB_NAME'=>'clinicaltrials',
	'DB_USER'=>'clinical_user',
	'DB_PASS'=>'password1234',
	'DB_TEMP'=>'LT_temp',
	'MAIL_ENABLED'=>'true',
	'MAX_EMAIL_FILES'=>'10',
	'YOURLS_USER'=>'root',
	'YOURLS_PASS'=>'password',
	'YOURLS_URL'=>'http://localhost/s/yourls-api.php',
	'SITE_NAME'=>'Larvol Trials'
);
$settingsFailSafeKeys = array_keys($settingsFailSafe);

$settingsArray = parse_ini_file('setup/settings.ini');
if($settingsArray===false || !is_array($settingsArray) || count($settingsArray)<1)
{
	$logger->error('Settings.ini parsing failed.Loading values from settings_temp.ini Loading values from failsafe array.');
	foreach($settingsFailSafe as $settings=>$value)
	{
		define($settings,$value);
	}	
}
else
{
	$settingsArrayKeys = array_keys($settingsArray);
	$extraFailSafe = array_diff($settingsFailSafeKeys,$settingsArrayKeys);
	foreach($extraFailSafe as $extraFailSafeKey)
	{
		$logger->warn('Settings.ini failed to load the essential data for '.$extraFailSafeKey.'. Loading the data from fail safe array.');
		$settingsArray[$extraFailSafeKey] = $settingsFailSafe[$extraFailSafeKey];
	}
	foreach($settingsArray as $settings=>$value)
	{
		define($settings,$value);
	}
}
//Don't change these
define('HASH_ALGO', 'tiger192,4');
//define('LI_API', 'http://apistage.larvolinsight.com/api.ashx');
define('LI_API', 'http://apidemo.larvolinsight.com/api.ashx');
ini_set('magic_quotes_gpc','Off');
ini_set('magic_quotes_runtime','Off');
ini_set('magic_quotes_sybase','Off');
ini_set('register_globals','0');
header("X-UA-Compatible: IE=Edge");
?>