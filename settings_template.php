<?php
/* Database login settings.
	You must change these to point to the correct database.
*/
define('DB_SERVER', '127.0.0.1');
define('DB_NAME', 'clinicaltrials');
define('DB_USER', 'clinical_user');
define('DB_PASS', 'password1234');
//Name of secondary database for temporary usage
define('DB_TEMP', 'LT_temp');

//Enable or disable mail. If disabled, mail contents will be written to a text file.
define('MAIL_ENABLED', true);
define('MAX_EMAIL_FILES', 10);  // maximum no. of email files to keep (including attachments)

/* YOURLS options.
	You must change these to reflect how you set up YOURLS
*/
define('YOURLS_USER', 'root');
define('YOURLS_PASS', 'password');
define('YOURLS_URL', 'http://localhost/s/yourls-api.php');

/* Miscellaneous site options.
	Customize these however you wish.
*/
define('SITE_NAME','Larvol Trials');	//name used by the site to refer to itself in communiction with users

//Don't change these
define('HASH_ALGO', 'tiger192,4');
ini_set('magic_quotes_gpc','Off');
ini_set('magic_quotes_runtime','Off');
ini_set('magic_quotes_sybase','Off');
ini_set('register_globals','0');
?>