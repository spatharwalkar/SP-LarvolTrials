<?php
require_once 'db.php';
require_once 'include.page.php';
ini_set('max_execution_time','360000');	//100 hours

/**
* @name fetch_li_therapeuticareas
* @tutorial Function to sync LI therapeuticareas with LT therapeuticareas. All data are retrieved from LI in xml format.
* @param String $lastrun Last Run where the number on the end is a unix timestamp for
* how early you want to go back looking for changes. Subtract 24 hours from the timestamp
* to avoid timezone errors. Passing 0 returns the ID of every TherapeuticArea
* Only do this when lastrun is at the defult value (when the item has never been run).
*/
function fetch_li_therapeuticareas($lastrun)
{
	//calculate 24 hours from last run.
	$lastRunMinus24H = strtotime('- 24 hours',$lastrun);
	
	//fetch all available li therapeuticareas within the $lastRunMinus24H timeframe.
	
	$liXmlTherapeuticAreaList = file_get_contents(LI_API.'?tablename=therapeuticareas&timestamp='.$lastRunMinus24H);
	$xmlImportTherapeuticAreaList = new DOMDocument();
	$xmlImportTherapeuticAreaList->loadXML($liXmlTherapeuticAreaList);
	
	//get total number therapeuticareas 
	$total_therapeuticareas=0;
	foreach($xmlImportTherapeuticAreaList->getElementsByTagName('therapeutic_area_id') as $TherapeuticArea_ID)
	{
		$total_therapeuticareas++;
	}

	//** STATUS DISPLAY

	$prid = getmypid();

	$query = 'INSERT into update_status_fullhistory (process_id,status,update_items_total,start_time,trial_type,item_id) 
	  VALUES ("'. $prid .'","'. 2 .'",
	  "' . $total_therapeuticareas . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "' . "LI_IMPORT" . '" , "' . 0 . '" ) ;';
	 if( $total_therapeuticareas>1 ) mysql_query($query);
	 $up_id=mysql_insert_id();

	// STATUS DISPLAY **/
	$i=1;
	foreach($xmlImportTherapeuticAreaList->getElementsByTagName('therapeutic_area_id') as $TherapeuticArea_ID)
	{
		$TherapeuticArea_ID = $TherapeuticArea_ID->nodeValue;
		$out = fetch_li_therapeuticarea_individual($TherapeuticArea_ID);
		
		if($out['exitProcess'])	
		{
			$query = '	update update_status_fullhistory set er_message="Bad Therapeutic Area XML data",	update_items_progress='. $i .', 
				status=3, updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  
				where update_id= "'. $up_id .'" limit 1'  ; 
			if(!$res = mysql_query($query))
			{
				global $logger;
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
			}
			return array('exitProcess'=> true);
		}

		$query = '	update update_status_fullhistory set process_id="'. $prid . '",er_message="",status="'. 2 . '",
					trial_type="' . "LI_IMPORT" . '", update_items_total=' . $total_therapeuticareas . ',
					update_items_progress=' . ++$i .' , updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  
					where update_id= "'. $up_id .'" limit 1'  ; 

		if( $total_therapeuticareas>1 and isset($up_id) and isset($prid) )
		{
			if(!$res = mysql_query($query))
			{
				global $logger;
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				mysql_query('ROLLBACK');
				echo $log;
				return false;
			}
		}
	}
	$query = '	update update_status_fullhistory set er_message="",	update_items_progress='. $total_therapeuticareas .', 
				status=0, updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  
				where update_id= "'. $up_id .'" limit 1'  ; 
	if(!$res = mysql_query($query))
	{
		global $logger;
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		$logger->error($log);
		mysql_query('ROLLBACK');
		echo $log;
		return false;
	}
}
/**
 * @name fetch_li_therapeuticarea_individual
 * @tutorial Function to sync individual LI therapeuticareas with LT therapeuticareas. All data are retrieved from LI in xml format.
 * @param String $TherapeuticArea_ID = therapeuticareas.LI_id in LT 
 */
function fetch_li_therapeuticarea_individual($TherapeuticArea_ID)
{
	$liXmlTherapeuticArea = file_get_contents(LI_API.'?tablename=therapeuticareas&id='.$TherapeuticArea_ID);
	$xmlImportTherapeuticArea = new DOMDocument();
	$xmlImportTherapeuticArea->loadXML($liXmlTherapeuticArea);
	$out = parseTherapeuticAreasXmlAndSave($xmlImportTherapeuticArea,'therapeuticareas');
	echo 'Imported '.$out['success'].' records, Failed entries '.$out['fail']."<br/>\n";
	
	if($out['exitProcess'])	
	{
		echo "<br/>\nTherapeutic Area Fetch process exited due to bad data<br/>\n";
		return array('exitProcess'=> true);
	} else return array('exitProcess'=> false);
}