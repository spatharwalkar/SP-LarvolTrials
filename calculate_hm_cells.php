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
	else
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
	else
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
			$logger->error($log);
			echo $log;
			return false;
		}
	}
	//pr($areaids);pr($productids);
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
			$cnt_total=count($data);
			
			if(!$cnt_total or $cnt_total<1) 
			{
				
				if($counter>=1000)
				{
					$counter=0;
					echo '<br>1000 records added, sleeping 2 seconds....'.str_repeat("  ",800);
					sleep(2);
				}
				add_data($av['id'],$pv['id'],0,0,'none','N/A');
				$progress_count ++;
				if($cron_run)
				{
					$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress="' . $progress_count . '" WHERE update_id="' . $update_id . '"';
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
				}
				
				$counter++;
				continue;
			}

	//		pr($data);
			$query='SELECT a.trial
					from area_trials a 
					JOIN product_trials p ON a.`trial`=p.`trial`
					JOIN data_trials d ON p.`trial`=d.`larvol_id`
					where a.`area`="'.$av['id'].'" and p.`product`="'.$pv['id'].'" and d.`is_active`="1"	';
			
			if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
			$data1=array();
			while ($row = mysql_fetch_assoc($res))
			{	
				if($row["trial"])
					$data1[] = $row["trial"];
			}
			$cnt_total=count($data1);

			if($data1[0])
			{
			
				$cnt_active=count($data1);			
			}
			else
				$cnt_active=0;	
				
			$ids = implode(",", $data);
			
			$query='SELECT max(`phase`) as max_phase from data_trials 
					where `phase`<>"N/A" and `larvol_id` IN (' . $ids . ')' ;
			//pr($query);
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			$row = mysql_fetch_assoc($res);
			
			$max_phase = $row["max_phase"];
			
			$bomb=getBombdtl($data);
			if($counter>=1000)
			{
				$counter=0;
				echo '<br>1000 records added, sleeping 2 seconds....'.str_repeat("  ",800);
				sleep(2);
			}
			
			add_data($av['id'],$pv['id'],$cnt_total,$cnt_active,$bomb,$max_phase);
			$progress_count ++;
			if($cron_run)
			{
				$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress="' . $progress_count . '" WHERE update_id="' . $update_id . '"';
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
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
			$logger->error($log);
			echo $log;
			return false;
		}
	}
	echo '<br>All Done.';
	return true;
}			
function add_data($arid,$prid,$cnt_total,$cnt_active,$bomb,$max_phase)
{
/*********/

	$query='SELECT `area`,`product`
			from rpt_masterhm_cells
			where `area`="'.$arid.'" and `product`="'.$prid.'" limit 1';


	$curtime = date('Y-m-d H:i:s');
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$row = mysql_fetch_assoc($res);
	
	if($row["area"])
	{
		$query='UPDATE rpt_masterhm_cells SET 
				`count_total` = "'. $cnt_total .'",
				`count_active` ="'. $cnt_active.'",
				`bomb_auto` = "'. $bomb .'",
				`highest_phase` = "'. $max_phase .'",
				`last_update` = "'. $curtime .'" where
				`area`="'.$arid.'" and `product`="'.$prid.'" 
				';
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		
	}
	else
	{
		$query='INSERT INTO rpt_masterhm_cells SET 
				`product` = "'. $prid .'",
				`area` = "'. $arid .'",
				`count_total` = "'. $cnt_total .'",
				`count_active` ="'. $cnt_active.'",
				`bomb_auto` = "'. $bomb .'",
				`highest_phase` = "'. $max_phase .'",
				`last_update` = "'. $curtime .'"

				';
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
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