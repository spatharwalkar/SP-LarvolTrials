<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// ----------------------------------------------------------------------------------------
//	HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

$baseurl = urlPath();
$sigmapos = strpos($baseurl,"sigma");
if($sigmapos !== false)	$baseurl = substr($baseurl,0,$sigmapos);
$baseurl .= 'hybridauth/';

return 
	array(
		"base_url" => $baseurl, 

		"providers" => array ( 
			// openid providers

			"LinkedIn" => array ( 
				"enabled" => true,
				"keys"    => array ( "key" => LINKEDIN_KEY, "secret" => LINKEDIN_SECRET ) 
			),
		),

		// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
		"debug_mode" => false,

		"debug_file" => "",
	);
