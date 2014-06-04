<?php
require_once('db.php');
require_once('include.import.php');
echo '.<br>';
echo str_repeat ("   ", 3500);	
$mapping = array(
	'ISRCTN' => 'isrctn_id',
	'ClinicalTrials.gov identifier' => 'clinicaltrials_id',
	'Public title' => 'public_title',
	'Scientific title' => 'scientific_title',
	'Acronym' => 'acronym',
	'Serial number at source' => 'serial',
	'Study hypothesis' => 'hypothesis',
	'Lay summary' => 'lay_summary',
	'Ethics approval' => 'ethics_approval',
	'Study design' => 'study_design',
	'Countries of recruitment' => 'countries_recruitment',
	'Disease/condition/study domain' => 'disease_domain',
	'Participants - inclusion criteria' => 'inclusion_criteria',
	'Participants - exclusion criteria' => 'exclusion_criteria',
	'Anticipated start date' => 'anticipate_start_date',
	'Anticipated end date' => 'anticipate_end_date',
	'Status of trial' => 'status_trial',
	'Patient information material' => 'patient_info',
	'Target number of participants' => 'target_number',
	'Interventions' => 'interventions',
	'Primary outcome measure(s)' => 'primary_outcome',
	'Secondary outcome measure(s)' => 'secondary_outcome',
	'Sources of funding' => 'funding_source',
	'Trial website' => 'trial_website',
	'Publications' => 'publications',
	'Contact name' => 'contact_name',
// 0	'Address' => 'contact_address',
// 0   'City/town' => 'contact_city',
// 0   'Zip/Postcode' => 'contact_zip',
// 0   'Country' => 'contact_country',
// 0	'Tel' => 'contact_tel',
// 0	'Fax' => 'contact_fax',
// 0	'Email' => 'contact_email',
	'Sponsor' => 'sponsor_name',
// 1	'Address' => 'sponsor_address',
// 1	'City/town' => 'sponsor_city',
// 1	'Zip/Postcode' => 'sponsor_zip',
// 1	'Country' => 'sponsor_country',
	'Sponsor website:' => 'sponsor_website',
// 1	'Tel' => 'sponsor_tel',
// 1	'Fax' => 'sponsor_fax',
// 1	'Email' => 'sponsor_email',	
	'Date applied' => 'date_applied',
	'Last edited' => 'last_edit',
	'Date ISRCTN assigned' => 'assigned_date'
);
$address_counter = 0;
// DW
ini_set('max_execution_time', '360000'); //100 hours
ignore_user_abort(true);
ob_implicit_flush(true);
ob_end_flush();

//Globals
$days = 0;
$last_id = 0;
$id_field = 0;

if(isset($_GET['days']))
{
	$days_to_fetch = (int)$_GET['days'];
}
if(isset($days_to_fetch))	//$days_to_fetch comes from cron.php normally
{
	$days = (int)$days_to_fetch;
}else{
	die('Need to set $days_to_fetch or $_GET[' . "'days'" . ']');
}

$cron_run = isset($update_id); 	// check if being run by cron.php
if($cron_run)
{
	$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", updated_days='.$days.' WHERE update_id="' . $update_id . '"';
	$res = mysql_query($query) or die('Unable to update running' . mysql_error());
}


echo("Starting Search: <br>");

// Max Listings
$max = 100;

$Url = "http://www.controlled-trials.com/isrctn/search.html?sort=2&dir=asc&max=" . $max . "&Submit=SUBMIT";
$baseurl = "http://www.controlled-trials.com";

//*********
// FIRST CONNECT: Get Links or ISRCTNs
// ********
// 
$html = curl_start($Url);

// Get Items to fiquire out pages
$Html = $html;
$Html = strip_tags($Html);
$Html = preg_replace('/\s\s+/', ' ', $Html);
$lookpagevalue = "Showing records: 1 to " . $max . " of ";
$needle = 26 + strlen($max);
if (strpos($Html, $lookpagevalue) !== false) {
	$rowcount = substr($Html, strpos($Html, $lookpagevalue) + $needle, 20);
}
$i = strpos($rowcount, " ");
$rowcount = substr($rowcount, 0, $i);
if($cron_run)
	{
		$query = 'UPDATE update_status SET update_items_total="' . $rowcount . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
    	$res = mysql_query($query) or die('Unable to update running' . mysql_error());
	}

echo "<br>Records: " . $rowcount . "<br>";
//Find out how many pages that is
$pages=ceil(($rowcount/$max));
echo "Pages: ".$pages. "<br>";

// !!!!!!!TESTING SAKE ONLY.. REMOVE TO GET REAL VALUES!!!!!!!!!
//$rowcount = 20;
//$pages=2;
//*********
// SECOND CONNECT: Get all links on Pages
// ********

$links = array();
/**************/
$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="ISRCTN" order by update_id desc limit 1' ;
$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
$res = mysql_fetch_array($res) ;
$newrecord=true;	
if ( isset($res['process_id']) )
{
	$pid = getmypid();
	$pr_id = $pid;
	$up_id= ((int)$res['update_id']);
	$page_count = ((int)$res['update_items_progress']);
	$current_record=$page_count;	
	$page_count = floor(($page_count/100));
	$maxid = ((int)$res['update_items_total']); 
	$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message="", process_id = "'.$pr_id.'"  WHERE update_id = "' . $up_id .'" ;' ;
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
	$newrecord=false;
	
}
elseif ( isset($_GET['pages']) ) 
{
	$current_record=$_GET['pages'];
	$page_count = floor(($current_record/100));
//	$newrecord=false;
}
else 
{
/*******************/
	$current_record=0;
	$page_count = 0;
	$newrecord=true;
}

$link_count = 0;
while ($page_count <= $pages) 
{
	$url = "http://www.controlled-trials.com/isrctn/" . ($link_count + $current_record + 1) . "/" . $max . "/3/desc/";
		$html = curl_start($url);

	// GET LINKS
	$doc = new DOMDocument();
	for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
		echo('.');
		$done = @$doc->loadHTML($html);
	}

	// Look for Form
	// lucky we can use this cause elements are not labeled.. They close the form
	// at the bottom of page
	$tables = $doc->getElementsByTagName('form');
	$datatable = NULL;
	foreach ($tables as $table) {
		foreach ($table->attributes as $attr) {
			if ($attr->name == 'action' && $attr->value == '/isrctn/search.html') {
				$right = true;
				break;
			}
		}
		if ($right == true) {
			$datatable = $table;
			break;
		}
	}
	if ($datatable == NULL) {
		echo('<br>No Search Form Found.' . "\n<br />");
		exit();
	}
	unset($tables);

	//Now that we found the table, go through its TDs to find the ones with NCTIDs
	$tds = $datatable->getElementsByTagName('a');
	foreach ($tds as $td) {
		foreach ($td->attributes as $attr) {
			if ($attr->name == 'href') {
				// Get rid of page links..
				if (!strstr($attr->value, "isrctn/")) {
					$links[] = $baseurl . $attr->value;
				}
			}
		}
	}
	unset($datatable);

	// Open each Page:
	$link_count = count($links);
	//$link_count += $current_record;
	$page_count=$page_count+1;
	echo '<br>Processing page '.$page_count.' of '.$pages.'<br>';
}

echo "<br>Links: " . $link_count . "<br>";

$i = 0;
//*********
// THIRD CONNECT: Get Each Study
// ********
// !!!!!!!For Testing PURPOSES REMOVE!!!!!!!!!!
//$link_count = 150;
$progress_count=0;
while ($i < $link_count) {
	$link = $links[$i];
	gotostudy($link);
	$i2= $i + $current_record ;
	if($cron_run)
		{
		  	$query = 'UPDATE update_status SET updated_time="' ;
			$query .= date('Y-m-d H:i:s', strtotime('now')) ;
			$query.= '",update_items_progress="' ;
			$query.= $i + 1 ;
			$query .= '" WHERE update_id="' ;
			$query .= $update_id . '"';
	        $res = mysql_query($query) or die('Unable to update running');
		}

	$i = $i + 1;
	if($newrecord)
	{
		$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
		$res = mysql_query($query) or die('Bad SQL query finding highest update id');
		$res = mysql_fetch_array($res) ;
		$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
		//$fid = getFieldId('isrctn','isrctn_id');
		$pid = getmypid();
	
		$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,max_nctid,trial_type) 
			  VALUES ("'.$up_id.'","'. $pid .'","'. 2 .'",
			  "' . $rowcount . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "'. $rowcount .'", "ISRCTN"  ) ;';
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
		$newrecord=false;
	}
	else
	{
		$query = ' UPDATE  update_status_fullhistory SET update_items_progress= "' . $i2 . '" , status="2", current_nctid="'. $i2 .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" ;' ;
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	}
	
	
}

if($cron_run)
	{
		
    	$query = 'UPDATE update_status SET status="'.COMPLETED.'", updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_complete_time ="' . date("Y-m-d H:i:s", strtotime('now')) . '",   end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  WHERE update_id="' . $update_id . '"';
	    $res = mysql_query($query) or die('Unable to update running');
	}
else
	{
		$query = ' UPDATE  update_status_fullhistory SET status="0",  updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" ;' ;
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	}


echo("<br>Total Processed Count=" . $link_count . "<br>");
echo("Done");

//************ FUNCTIONS ****************//
//************ FUNCTIONS ****************//
//************ FUNCTIONS ****************//

function curl_start($url) {

	$cookieFilenameLogin = "/tmp/hypo_login.cookie";

	$headers[] = 'Host: "http://www.controlled-trials.com';
	$headers[] = 'User Agent: Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0';
	$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
	$headers[] = 'Accept-Language: en-us;q=0.5';
	$headers[] = 'Accept-Encoding: gzip, deflate';
	$headers[] = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
	$headers[] = 'Keep-Alive: 115';
	$headers[] = 'Connection: keep-alive';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSLVERSION, 3);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	//   curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilenameLogin);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilenameLogin);
	curl_setopt($ch, CURLOPT_URL, $url);


	$Html = curl_exec($ch);
	$Html = @mb_convert_encoding($Html, 'HTML-ENTITIES', 'utf-8');

	curl_close($ch);
	unset($ch);

	return $Html;
}

function gotostudy($link) {

	$html = curl_start($link);
	// Create Dom
	$doc = new DOMDocument();
	for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
		echo('.');
		$done = @$doc->loadHTML($html);
	}
	unset($html);

	$study = array();
	// Look For FieldName and FieldValue values
	// Need to go tru doc and store FieldName and FieldValues Respectively
	$tds = $doc->getElementsByTagName('td');
	foreach ($tds as $td) {
		foreach ($td->attributes as $attr) {
			if ($attr->name == 'id' && $attr->value == 'FieldName') {
				$fieldname = $td->nodeValue;
			}
			if ($attr->name == 'id' && $attr->value == 'FieldValue') {
				$fieldvalue = $td->nodeValue;
				// Since have value now store it
				$fieldname = str_replace('&nbsp;', "", $fieldname);
				$fieldname = preg_replace('/\s+/', ' ', trim($fieldname));
				$fieldname = preg_replace("/[^A-Za-z0-9\s\s+\.\:\-\/%+\(\)\*\&\$\#\!\@\"\';\n\t\r]/", "", $fieldname);

				$dbfieldname = get_mapping($fieldname);
				set_field($study, $dbfieldname, $fieldvalue);
			}
		}
	}

	unset($tds);
	unset($doc);

	echo ("<br>......Storing in DB.....");

	addRecord($study, "isrctn");

	$values = sizeof($study, 0);
 //   var_dump($study);

	echo ("Finished Processing: " . $study['isrctn_id'][0] . "- Values Parsed: " . $values . "<br>");
	unset($study);
	$progress_count++;
	
	echo str_repeat ("   ", 3500);	


}

function set_field(&$array, $fieldname, $fieldvalue) {
	$fieldvalue = preg_replace('/\s+/', ' ', trim($fieldvalue));
	$fieldvalue = preg_replace("/[^A-Za-z0-9\s\s+\.\:\-\/%+\(\)\*\&\$\#\!\@\"\';\n\t\r]/", "", $fieldvalue);

	// Date Conversions
	if (($fieldname == "last_edit") ||
			($fieldname == "date_applied") ||
			($fieldname == "assigned_date") ||
			($fieldname == "anticipate_start_date") ||
			($fieldname == "anticipate_end_date")
	) {
		$pieces = explode("/", $fieldvalue);
		$fieldvalue = $pieces[2] . "-" . $pieces[1] . "-" . $pieces[0];
	}

	$array[$fieldname] = $fieldvalue;
}

function get_mapping($fieldname) {

	global $mapping;
	global $address_counter;

	// Check For Address.. This is on every record. This will tell us if we are
	// in the contact or sponsor section... 
	if ($fieldname == " Address") {
		
		if ($address_counter == 2)
		{
			// New Record Start Over
			$address_counter=0;
		}
		$address_counter = $address_counter + 1;
	}
	
	if (($fieldname == " Address") ||
			($fieldname == " City/town") ||
			($fieldname == " Zip/Postcode") ||
			($fieldname == " Country") ||
			($fieldname == " Tel") ||
			($fieldname == " Fax") ||
			($fieldname == " Email")
	) {
		$fieldname=strtolower($fieldname);
		$fieldname=trim($fieldname);
		
		if ($fieldname=="city/town") 
		{
			$fieldname="city";
		}
		else if ($fieldname=="zip/postcode") 
		{
			$fieldname="zip";
		}
		
		if ($address_counter == 1) {
			//Contact Section
			$value = "contact_" . $fieldname;
		} else if ($address_counter == 2) {
			//Sponsor Section
			$value = "sponsor_" . $fieldname;
		}
	} else {
		$value = $mapping[$fieldname];
	}

	return $value;
}

?>  