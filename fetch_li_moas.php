<?php
require_once 'db.php';
require_once 'include.page.php';
ini_set('max_execution_time','360000');	//100 hours

/**
* @name fetch_li_moas
* @tutorial Function to sync LI moas with LT moas. All data are retrieved from LI in xml format.
* @param String $lastrun Last Run where the number on the end is a unix timestamp for
* how early you want to go back looking for changes. Subtract 24 hours from the timestamp
* to avoid timezone errors. Passing 0 returns the ID of every moa
* Only do this when lastrun is at the defult value (when the item has never been run).
*/
function fetch_li_moas($lastrun)
{
	//calculate 24 hours from last run.
	$lastRunMinus24H = strtotime('- 24 hours',$lastrun);
	
	//fetch all available li moas within the $lastRunMinus24H timeframe.
	
	$liXmlMoaList = file_get_contents(LI_API.'?tablename=moas&timestamp='.$lastRunMinus24H);
	$xmlImportMoaList = new DOMDocument();
	$xmlImportMoaList->loadXML($liXmlMoaList);
	
	//get total number moas 
	$total_moas=0;
	foreach($xmlImportMoaList->getElementsByTagName('moa_id') as $Moa_ID)
	{
		$total_moas++;
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
	  "' . $total_moas . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "' . "LI_IMPORT" . '" , "' . 0 . '" ) ;';
	 if( $total_moas>1 ) mysql_query($query);

	// STATUS DISPLAY **/
	$i=1;
	foreach($xmlImportMoaList->getElementsByTagName('moa_id') as $Moa_ID)
	{
		$Moa_ID = $Moa_ID->nodeValue;
		fetch_li_moa_individual($Moa_ID);

		$query = '	update update_status_fullhistory set process_id="'. $prid . '",er_message="",status="'. 2 . '",
					trial_type="' . "LI_IMPORT" . '", update_items_total=' . $total_moas . ',
					update_items_progress=' . ++$i .' , updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  
					where update_id= "'. $up_id .'" limit 1'  ; 

		if( $total_moas>1 and isset($up_id) and isset($prid) )
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
	$query = '	update update_status_fullhistory set er_message="",	update_items_progress='. $total_moas .', 
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
 * @name fetch_li_moa_individual
 * @tutorial Function to sync individual LI moas with LT moas. All data are retrieved from LI in xml format.
 * @param String $Moa_ID = moas.LI_id in LT 
 */
function fetch_li_moa_individual($Moa_ID)
{
	$liXmlMoa = file_get_contents(LI_API.'?tablename=moas&id='.$Moa_ID);
	$xmlImportMoa = new DOMDocument();
	$xmlImportMoa->loadXML($liXmlMoa);
	$out = parseMoasXmlAndSave($xmlImportMoa,'moas');
	echo 'Imported '.$out['success'].' records, Failed entries '.$out['fail']."<br/>\n";	
}

//controller
$timeStamp = '';
$timeStamp = $_GET['li_moa_sync_timestamp'];
if($timeStamp !=='' && is_numeric($timeStamp))
{
	fetch_li_moas($timeStamp);
}
$fetchLiScriptMoaIndividualId = $_GET['fetch_li_script_moa_individual_id'];
if($fetchLiScriptMoaIndividualId !='')
{
	fetch_li_moa_individual($fetchLiScriptMoaIndividualId);
}
