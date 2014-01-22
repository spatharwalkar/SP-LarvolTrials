<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('preindex_trial.php');
require_once('db.php');
require_once('include.import.php');
require_once('eudract_common.php');
require_once('include.import.eudract.history.php');
ini_set('max_execution_time', '360000'); //100 hours
ignore_user_abort(true);

//Globals
global $logger;
$days = 0;
$last_id = 0;
$id_field = 0;

echo("\n<br />" . 'Starting FULL REFRESH ... ' . "\n<br />");

echo('Searching for  records...' . "\n<br />");
$ids = getAllEudraIDs();
if (count($ids) == 0) 
{
    echo('There are none!' . "\n<br />");
	return false;
} 


	$count=count($ids);
	$update_id="1";
	$query = 'SELECT update_id AS maxid FROM update_status where update_id="1" ' ;
	$res = mysql_query($query) ;
	if(!$res = mysql_query($query))
		{
			$log='Unable to get max id from update_status_fullhistory. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			return false;
		}
	$res = mysql_fetch_array($res) ;
	if(!isset($res['maxid']))
	{
	$query = '	INSERT INTO update_status SET 
				update_id="1",
				start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", 
				updated_days="'.$days.'" ,
				status="2",
				update_items_total="' . $count . '",
				update_items_progress="0",
				update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"'; 
		if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;

			return false;
		}
	}
	else
	{
	$query = '	UPDATE update_status SET 
				start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", 
				updated_days="'.$days.'" ,
				update_items_total="' . $count . '",
				status="2",
				update_items_progress="0",
				update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"
				WHERE update_id="1"'
				; 
		if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			return false;
		}
	}
	

$count = count($ids);
echo("<br /><br /> New Updates : " . $count . "\n<br />");

	    $query = 'UPDATE update_status SET update_items_total="' . $count . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="1"';
    	
		if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;

			return false;
		}

	
    //Import the XML for all these new records
    echo('Fetching record content...' . "\n<br />");
    $progress_count = 0;
    
    
    foreach ($ids as $key => $value) 
	{
//		foreach ($value as $country) 
//		{
		//scrape_history($key . ' - ' . $country);
		scrape_history($key , $value);
		echo str_repeat("   ",500).'<br>';
			$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress = update_items_progress+1 WHERE update_id="1"';
			
			if(!$res = mysql_query($query))
			{
				$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
				$logger->fatal($log);
				echo $log;
				
				return false;
			}
		
//		}
	}
    	$query = '	UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_complete_time ="' . date("Y-m-d H:i:s", strtotime('now')) . '",
					end_time="' . date("Y-m-d H:i:s", strtotime('now')) .'", update_items_progress=update_items_total WHERE update_id="1"';
		if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			
			return false;
		}
		
		if(!mysql_query('COMMIT'))
			{
			$log='There seems to be a problem while committing the transaction Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			mysql_query('ROLLBACK');
			echo $log;
			return false;
			}

echo('Done with everything.');

function getallEudraIDs() 
{
		$url = "https://www.clinicaltrialsregister.eu/ctr-search/search?query="  ;
		$Html = curl_start($url);
		$linesHtml = preg_split('/\n/', $Html);
		$pages = 1;
		foreach ($linesHtml as $lineHtml) 
		{
			if (strpos($lineHtml, 'Displaying page 1 of') !== false) 
			{
				$pages = substr($lineHtml, strpos($lineHtml, 'Displaying page 1 of ') + 21, 120);
				$i = strpos($pages, ".");
				$pages = substr($pages, 0, $i);
				echo("<br>Retrieved pages=$pages<br>");
				break;
			}
		}

		unset($linesHtml);
		unset($lineHtml);

		$ids = array();

		for ($page = 1; $page <= $pages; ++$page) {
			$fake = mysql_query('SELECT larvol_id FROM data_trials LIMIT 1'); //keep alive
			@mysql_fetch_array($fake);
			$url = "https://www.clinicaltrialsregister.eu/ctr-search/search?query=&page=" . $page ;
			
			$Html = curl_start($url);


			$doc = new DOMDocument();
			for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
				echo('.');
				$done = @$doc->loadHTML($Html);
			}
			unset($Html);
			$tables = $doc->getElementsByTagName('table');
			$datatable = NULL;
			$pageids = array();
			foreach ($tables as $table) {
				$right = false;
				foreach ($table->attributes as $attr) {
					if ($attr->name == 'class' && $attr->value == 'result') {
						$right = true;
						break;
					}
				}
				if ($right == true) {
					$datatable = $table;
				}
				else
				{
					continue;
				}
				 
				//Now that we found the table, go through its TDs to find the ones with NCTIDs
				$tds = $datatable->getElementsByTagName('td');

				//$countries = array();
				$eudra_number='';
				foreach ($tds as $td) {
					 
					$number_pos = strpos($td->nodeValue,'EudraCT Number:');
					$country_pos = strpos($td->nodeValue,'Country:');
					//start date
					$startdate = strpos($td->nodeValue,'Start Date');
					
					if($startdate === false) 
					{

					}
					else 
					{
						$pageids[$eudra_number]['start_date']=substr(trim($td->nodeValue),-10);
					}

					if($number_pos === false) {

					}
					else {
						$eudra_number=trim($td->nodeValue);
						$eudra_number = unpadeudra($eudra_number);
						//$eudra_number = substr($eudra_number, strpos($eudra_number, 'EudraCT Number: ') + strlen('EudraCT Number: '));
						$pageids[$eudra_number] = array();

					}
					 
					if($country_pos === false) {

					}
					else {
						$hrefs = $td->getElementsByTagName('a');
						foreach ($hrefs as $href) {
							$pageids[$eudra_number][] = trim($href->nodeValue);
						}
						 
					}
					
					 
				}
			}
			unset($tables);
			echo('Page ' . $page . ': ' . implode(', ', array_keys($pageids)) . "\n<br />");
			$ids = array_merge($ids, $pageids);
		}
		return $ids;
	}


?>  