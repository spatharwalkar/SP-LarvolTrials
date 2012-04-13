<?php
require_once('db.php');
require_once('include.util.php');
ini_set('max_execution_time', '360000'); //100 hours
ignore_user_abort(true);
if(isset($_GET['area']) or isset($_GET['product']))
{
	$parameters=$_GET;
	if(!calc_cells($parameters))	echo '<br><b>Could complete calculating cells, there was an error.<br></b>';
}
elseif( isset($_GET['calc']) and ($_GET['calc']=="all") )
{
	$parameters=NULL;
	if(!calc_cells($parameters))	echo '<br><b>Could complete calculating cells, there was an error.<br></b>';
}

function calc_cells($parameters,$update_id=NULL)
{

	$cron_run = isset($update_id); 	// check if being run by cron.php
	
	
	if($cron_run)
	{
		$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", updated_days="0" WHERE update_id="' . $update_id . '"';
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
	}

	//get area ids
	if(isset($parameters['area']))
	{
		$query='select `id` from areas where `id`="'.$parameters['area'].'"';
	}
	
	else // no area given, select all .
	{
		$query='select `id` from areas order by `id`';
	}
	$res = mysql_query($query);
	if($res === false)
	{
		$log = 'Bad SQL query getting `id` from areas mysql_error=' . mysql_error() . ' query=' . $query;
		global $logger;
		$logger->fatal($log);
		echo($log);
		return false;
	}
	$areaids=array();
	while($areaids[]=mysql_fetch_assoc($res));

	//get product ids
	if(isset($parameters['product']))
	{
		$query='select `id` from products where `id`="'.$parameters['product'].'"';
	}
	else // no product given, select all products.
	{
		$query='select id from products order by `id` ';
	}
	$res = mysql_query($query);
	if($res === false)
	{
		$log = 'Bad SQL query getting id from products mysql_error=' . mysql_error() . ' query=' . $query;
		global $logger;
		$logger->fatal($log);
		echo ($log);
		return false;
	}
	$productids=array();
	while($productids[]=mysql_fetch_assoc($res));

	$x=count($areaids); $y=count($productids);
	$totalcount=$x*$y;
	if($cron_run)
	{
	    $query = 'UPDATE update_status SET update_items_total="' . $totalcount . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
	}
	//pr($areaids);
	//pr($productids);
	$counter=0;
	$progress_count = 0;
	foreach ($areaids as $ak=>$av)
	{
	
		if(!$av['id'] or is_null($av['id']) or empty($av['id']))
		{
			echo '<br>';
			continue;
		}

		
		foreach($productids as $pk=>$pv)
		{
		
			if(!$pv['id'] or is_null($pv['id']) or empty($pv['id']))
			{
				echo '<br> ';
				continue;
			}
		
			$query='SELECT a.trial from area_trials a 
					JOIN product_trials p
					ON a.trial=p.trial
					where a.area="'.$av['id'].'" and p.product="'.$pv['id'].'"';
			
			if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
			$data=array();
			while ($row = mysql_fetch_assoc($res))
			{	
				if($row["trial"])
					$data[] = $row["trial"];
			}
			// $cnt_tl=count($data);
			$cnt_total=count($data);
			//echo $cnt_total;
			if(!$cnt_total or $cnt_total<1) 
			{
				
				if($counter>=20000)
				{
					$counter=0;
					echo '<br>20000 records added, sleeping 1 second....'.str_repeat("  ",800);
					sleep(1);
				}
				add_data($av['id'],$pv['id'],0,0,0,'none','N/A');
				$progress_count ++;
				if($cron_run)
				{
					$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress="' . $progress_count . '" WHERE update_id="' . $update_id . '"';
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
				}
				
				$counter++;
				continue;
			}

	//		pr($data);
			$query='SELECT a.trial,d.is_active,d.institution_type 
					from area_trials a 
					JOIN product_trials p ON a.`trial`=p.`trial`
					JOIN data_trials d ON p.`trial`=d.`larvol_id`
					where a.`area`="'.$av['id'].'" and p.`product`="'.$pv['id'].'" ';
			
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				global $logger;
				$logger->error($log);
				echo $log;
				return false;
			}
			$data1=array();
			$data2=array();
			$temp_total=0;
			while ($row = mysql_fetch_assoc($res))
			{	
				if( $row["trial"] && ($row["is_active"]=="1" or $row["is_active"]==1)  )
				{
					$data1[] = $row["trial"];
				}
				if( $row["trial"] && ( $row["is_active"]=="1" or $row["is_active"]==1 ) && $row["institution_type"]=='industry_lead_sponsor'  )
				{
					$data2[] = $row["trial"];
				}
				
				$temp_total++;
			} 
			$cnt_total=$temp_total;

			if($data1[0])
			{
			
				$cnt_active=count($data1);			
			}
			else
				$cnt_active=0;	
			if($data2[0])
			{
			
				$cnt_active_indlead=count($data2);			
			}
			else
				$cnt_active_indlead=0;	
				
			$ids = implode(",", $data);
			
			$query='SELECT max(`phase`) as max_phase from data_trials 
					where `phase`<>"N/A" and `larvol_id` IN (' . $ids . ')' ;
			//pr($query);
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				global $logger;
				$logger->error($log);
				echo $log;
				return false;
			}
			
			$row = mysql_fetch_assoc($res);
			
			$max_phase = $row["max_phase"];
			
			$bomb=getBombdtl($data);
			if($counter>=5000)
			{
				$counter=0;
				echo '<br>5000 records added, sleeping 1 second ....'.str_repeat("  ",800);
				sleep(1);
			}
			
			add_data($av['id'],$pv['id'],$cnt_total,$cnt_active,$cnt_active_indlead,$bomb,$max_phase);
			$progress_count ++;
			if($cron_run)
			{
				$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress="' . $progress_count . '" WHERE update_id="' . $update_id . '"';
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					global $logger;
					$logger->error($log);
					echo $log;
					return false;
				}
			}
		}

	}
	if($cron_run)
	{
		$query = 'UPDATE update_status SET end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
	}
	echo '<br>All Done.';
	return true;
}			

function add_data($arid,$prid,$cnt_total,$cnt_active,$cnt_active_indlead,$bomb,$max_phase)
{
/*********/

	$query='SELECT `area`,`product`
			from rpt_masterhm_cells
			where `area`="'.$arid.'" and `product`="'.$prid.'" limit 1';


	$curtime = date('Y-m-d H:i:s');
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
	$row = mysql_fetch_assoc($res);
	
	if($row["area"])
	{
		//get existing counts before updating
		$query='select 
				`count_active`,count_active_indlead,
				`count_total` from rpt_masterhm_cells  where
				`area`="'.$arid.'" and `product`="'.$prid.'" 
				';
				
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
		
		$row = mysql_fetch_assoc($res);
		$count_active_old = $row["count_active"];
		$cnt_indlead_old = $row["count_active_indlead"];
		$count_total_old = $row["count_total"];
		
		//if there is a difference in counts, then update the _prev fields
		$aa='';$bb='';$cc='';
		if($count_active_old<>$cnt_active) $aa='`count_active_prev` = "'. $count_active_old .'",';
		
		if($count_total_old<>$cnt_total) $bb='`count_total_prev` = "'. $count_total_old .'",';
			
		if($cnt_indlead_old<>$cnt_active_indlead) $cc='`count_active_indlead_prev` = "'. $cnt_indlead_old .'",';
		if( empty($aa) && empty($bb) && empty($cc) )
		{
		$query='UPDATE rpt_masterhm_cells 
				SET 
				`count_active` ="'. $cnt_active.'",
				`count_active_indlead` ="'. $cnt_active_indlead.'",
				`bomb_auto` = "'. $bomb .'",
				`highest_phase` = "'. $max_phase .'",
				`count_total` = "'. $cnt_total .'",
				`last_update` = "'. $curtime .'" where
				`area`="'.$arid.'" and `product`="'.$prid.'" 
				';
		}
		else
		{
//			pr('AA='.$aa);pr('bb='.$bb);pr('cc='.$cc);
			$query='UPDATE rpt_masterhm_cells 
				SET 
				`count_active` ="'. $cnt_active.'",
				`count_active_indlead` ="'. $cnt_active_indlead.'",
				`bomb_auto` = "'. $bomb .'",
				`highest_phase` = "'. $max_phase .'",
				`count_total` = "'. $cnt_total .'",'
				. $aa . $bb . $cc .
				'`count_lastchanged` = "'. $curtime .'",
				`last_update` = "'. $curtime .'" where
				`area`="'.$arid.'" and `product`="'.$prid.'" 
				';
		}
				
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
		
	}
	else
	{
		$query='INSERT INTO rpt_masterhm_cells 
				SET 
				`product` = "'. $prid .'",
				`area` = "'. $arid .'",
				`count_active` ="'. $cnt_active.'",
				`count_active_indlead` ="'. $cnt_active_indlead.'",
				`bomb_auto` = "'. $bomb .'",
				`highest_phase` = "'. $max_phase .'",
				`count_total` = "'. $cnt_total .'",
				`last_update` = "'. $curtime .'"

				';
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
	
	
	}
	/**************/
	$curtime = date('Y-m-d H:i:s');
	
	echo '<br>'. $curtime . ' Area id : '. $arid .' Product id : '. $prid . ' - done.'. str_repeat("  ",800)  ;
	
	
}


function getBombdtl($ids)
{
	global $logger;
		
	if (count($ids) == 0)
		return "";
	
	$bombStatuses = '"Active, not recruiting","Not yet recruiting","Recruiting","Enrolling by invitation"';
	$ids = implode(",", $ids);
	
//	pr($ids);
	$past = "'".date("Y-m-d H:i:s", time() - (int)(540*24*3600))."'";
	
	$get_bomb_query1 = "
			SELECT larvol_id FROM data_trials where 
			larvol_id IN (".$ids.") and
			lastchanged_date < ".$past ;
	

	if(!$rs = mysql_query($get_bomb_query1))
		{
			$log='There seems to be a problem with the SQL Query:'.$get_bomb_query1.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}		
		
	$trials = array();
	while ($row = mysql_fetch_assoc($rs))
		$trials[] = $row["larvol_id"];
	if (count($trials) == 0)
		return "";
	$trials = implode(",", $trials);
	$get_bomb_query2 = "SELECT MAX(phase) AS phase FROM data_trials where
			larvol_id IN (".$ids.") AND larvol_id NOT IN (".$trials.")";
	if(!$rs = mysql_query($get_bomb_query2))
		{
			$log='There seems to be a problem with the SQL Query:'.$get_bomb_query2.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		
	$row = mysql_fetch_assoc($rs);
	$phase = $row["phase"];
	$get_bomb_query3 = "SELECT larvol_id FROM data_trials where
			`phase` = '".$phase."' AND larvol_id IN (".$ids.") AND
			larvol_id NOT IN (".$trials.") AND
			overall_status IN (".$bombStatuses.")";

	if(!$rs = mysql_query($get_bomb_query3))
		{
			$log='There seems to be a problem with the SQL Query:'.$get_bomb_query3.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		

	if (mysql_fetch_assoc($rs))
		return "small";
	return "large";
}


?>  