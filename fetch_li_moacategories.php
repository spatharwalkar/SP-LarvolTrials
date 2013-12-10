<?php
require_once 'db.php';
require_once 'include.page.php';
ini_set('max_execution_time','360000');	//100 hours

/**
* @name fetch_li_moacategories
* @tutorial Function to sync LI moacategories with LT moacategories. All data are retrieved from LI in xml format.
* @param String $lastrun Last Run where the number on the end is a unix timestamp for
* how early you want to go back looking for changes. Subtract 24 hours from the timestamp
* to avoid timezone errors. Passing 0 returns the ID of every Moacategory
* Only do this when lastrun is at the defult value (when the item has never been run).
*/
function fetch_li_moacategories($lastrun)
{
	//calculate 24 hours from last run.
	$lastRunMinus24H = strtotime('- 24 hours',$lastrun);
	
	//fetch all available li moacategories within the $lastRunMinus24H timeframe.
	
	$liXmlMoacategoryList = file_get_contents(LI_API.'?tablename=moacategories&timestamp='.$lastRunMinus24H);
	$xmlImportMoacategoryList = new DOMDocument();
	$xmlImportMoacategoryList->loadXML($liXmlMoacategoryList);
	
	//get total number moacategories 
	$total_moacategories=0;
	foreach($xmlImportMoacategoryList->getElementsByTagName('moa_category_id') as $Moacategory_ID)
	{
		$total_moacategories++;
	}

	//** STATUS DISPLAY

	$prid = getmypid();

	$query = 'INSERT into update_status_fullhistory (process_id,status,update_items_total,start_time,trial_type,item_id) 
	  VALUES ("'. $prid .'","'. 2 .'",
	  "' . $total_moacategories . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "' . "LI_IMPORT" . '" , "' . 0 . '" ) ;';
	 if( $total_moacategories>1 ) mysql_query($query);
	 $up_id=mysql_insert_id();

	// STATUS DISPLAY **/
	$i=1;
	foreach($xmlImportMoacategoryList->getElementsByTagName('moa_category_id') as $Moacategory_ID)
	{
		$Moacategory_ID = $Moacategory_ID->nodeValue;
		$out = fetch_li_moacategory_individual($Moacategory_ID);
		
		if($out['exitProcess'])	
		{
			$query = '	update update_status_fullhistory set er_message="Bad Moa category XML data",	update_items_progress='. $i .', 
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
					trial_type="' . "LI_IMPORT" . '", update_items_total=' . $total_moacategories . ',
					update_items_progress=' . ++$i .' , updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  
					where update_id= "'. $up_id .'" limit 1'  ; 

		if( $total_moacategories>1 and isset($up_id) and isset($prid) )
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
	$query = '	update update_status_fullhistory set er_message="",	update_items_progress='. $total_moacategories .', 
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
 * @name fetch_li_moacategory_individual
 * @tutorial Function to sync individual LI moacategories with LT moacategories. All data are retrieved from LI in xml format.
 * @param String $Moacategory_ID = moacategories.LI_id in LT 
 */
function fetch_li_moacategory_individual($Moacategory_ID)
{
	$liXmlMoacategory = file_get_contents(LI_API.'?tablename=moacategories&id='.$Moacategory_ID);
	$xmlImportMoacategory = new DOMDocument();
	$xmlImportMoacategory->loadXML($liXmlMoacategory);
	$out = parseMoacategoriesXmlAndSave($xmlImportMoacategory,'moacategories');
	echo 'Imported '.$out['success'].' records, Failed entries '.$out['fail']."<br/>\n";
	
	if($out['exitProcess'])	
	{
		echo "<br/>\nMoa category Fetch process exited due to bad data<br/>\n";
		return array('exitProcess'=> true);
	} else return array('exitProcess'=> false);
}

//controller
$timeStamp = '';
$timeStamp = $_GET['li_moacategory_sync_timestamp'];
if($timeStamp !=='' && is_numeric($timeStamp))
{
	fetch_li_moacategories($timeStamp);
}
$fetchLiScriptMoacategoryIndividualId = $_GET['fetch_li_script_moacategory_individual_id'];
if($fetchLiScriptMoacategoryIndividualId !='')
{
	fetch_li_moacategory_individual($fetchLiScriptMoacategoryIndividualId);
}
