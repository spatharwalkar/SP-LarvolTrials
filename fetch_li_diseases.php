<?php
require_once 'db.php';
require_once 'include.page.php';
ini_set('max_execution_time','360000');	//100 hours

/**
* @name fetch_li_diseases
* @tutorial Function to sync LI diseases with LT diseases. All data are retrieved from LI in xml format.
* @param String $lastrun Last Run where the number on the end is a unix timestamp for
* how early you want to go back looking for changes. Subtract 24 hours from the timestamp
* to avoid timezone errors. Passing 0 returns the ID of every disease
* Only do this when lastrun is at the defult value (when the item has never been run).
* @author Jithu Thomas
*/
function fetch_li_diseases($lastrun)
{
	//calculate 24 hours from last run.
	$lastRunMinus24H = strtotime('- 24 hours',$lastrun);
	
	//fetch all available li diseases within the $lastRunMinus24H timeframe.
	
	$liXmlDiseaseList = file_get_contents(LI_API.'?tablename=areas&timestamp='.$lastRunMinus24H);
	$xmlImportDiseaseList = new DOMDocument();
	$xmlImportDiseaseList->loadXML($liXmlDiseaseList);
	
	//get total number diseases 
	$total_diseases=0;
	foreach($xmlImportDiseaseList->getElementsByTagName('area_id') as $Disease_ID)
	{
		$total_diseases++;
	}

	//** STATUS DISPLAY
	$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
	if(!$res = mysql_query($query))
	{
		global $logger;
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		$logger->error($log);
		mysql_query('ROLLBACK');
		echo $log;
		return false;
	}
	$res = mysql_fetch_array($res) ;
	$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
	$prid = getmypid();

	$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,trial_type,item_id) 
	  VALUES ("'.$up_id.'","'. $prid .'","'. 2 .'",
	  "' . $total_diseases . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "' . "LI_IMPORT" . '" , "' . 0 . '" ) ;';
	 if( $total_diseases>1 ) mysql_query($query);

	// STATUS DISPLAY **/
	$i=1;
	foreach($xmlImportDiseaseList->getElementsByTagName('area_id') as $Disease_ID)
	{
		$Disease_ID = $Disease_ID->nodeValue;
		$out = fetch_li_disease_individual($Disease_ID);
		
		if($out['exitProcess'])	
		{
			$query = '	update update_status_fullhistory set er_message="Bad Disease XML data",	update_items_progress='. $i .', 
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
					trial_type="' . "LI_IMPORT" . '", update_items_total=' . $total_diseases . ',
					update_items_progress=' . ++$i .' , updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  
					where update_id= "'. $up_id .'" limit 1'  ; 

		if( $total_diseases>1 and isset($up_id) and isset($prid) )
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
	$query = '	update update_status_fullhistory set er_message="",	update_items_progress='. $total_diseases .', 
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
 * @name fetch_li_disease_individual
 * @tutorial Function to sync individual LI diseases with LT diseases. All data are retrieved from LI in xml format.
 * @param String $Disease_ID = diseases.LI_id in LT 
 * @author Jithu Thomas
 */
function fetch_li_disease_individual($Disease_ID)
{
	$liXmlDisease = file_get_contents(LI_API.'?tablename=areas&id='.$Disease_ID);
	$xmlImportDisease = new DOMDocument();
	$xmlImportDisease->loadXML($liXmlDisease);
	$out = parseDiseasesXmlAndSave($xmlImportDisease,'diseases');
	echo 'Imported '.$out['success'].' records, Failed entries '.$out['fail']."<br/>\n";
	
	if($out['exitProcess'])	
	{
		echo "<br/>\nDisease Fetch process exited due to bad data<br/>\n";
		return array('exitProcess'=> true);
	} else return array('exitProcess'=> false);	
}

?>