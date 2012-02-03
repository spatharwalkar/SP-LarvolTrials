<?php
require_once('db.php');
require_once('include.util.php');
ini_set('max_execution_time', '360000'); //100 hours

if(isset($_GET['area']) or isset($_GET['product']))
{
	$parameters=$_GET;
	calc_cells($parameters);
}
elseif( isset($_GET['calc']) and ($_GET['calc']=="all") )
{
	$parameters=NULL;
	calc_cells($parameters);
}

function calc_cells($parameters,$update_id=NULL)
{

	$cron_run = isset($update_id); 	// check if being run by cron.php
	
	if($cron_run)
	{
		$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", updated_days="0" WHERE update_id="' . $update_id . '"';
		$res = mysql_query($query) or die('Unable to update running' . mysql_error());
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
		die($log);
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
		die($log);
	}
	$productids=array();
	while($productids[]=mysql_fetch_assoc($res));

	$x=count($areaids); $y=count($productids);
	$totalcount=$x*$y;
	if($cron_run)
	{
	    $query = 'UPDATE update_status SET update_items_total="' . $totalcount . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
    	$res = mysql_query($query) or die('Unable to update running' . mysql_error());
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
			
			$res = mysql_query($query);
			if($res === false)
			{
				$log = 'Bad SQL query getting data . mysql_error=' . mysql_error() . ' query=' . $query;
				global $logger;
				$logger->fatal($log);
				die($log);
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
					$res = mysql_query($query) or die('Unable to update running');
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

			$res = mysql_query($query);
			
			if($res === false)
			{
				$log = 'Bad SQL query getting data. mysql_error=' . mysql_error() . ' query=' . $query;
				global $logger;
				$logger->fatal($log);
				die($log);
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
			$res = mysql_query($query);
			
			if($res === false)
			{
				$log = 'Bad SQL query getting max phase. mysql_error=' . mysql_error() . ' query=' . $query;
				global $logger;
				$logger->fatal($log);
				die($log);
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
				$res = mysql_query($query) or die('Unable to update running');
			}
		}

	}
	if($cron_run)
	{
		$query = 'UPDATE update_status SET end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
		$res = mysql_query($query) or die('Unable to update running' . mysql_error());
	}
	echo '<br>All Done.';
}			
function add_data($arid,$prid,$cnt_total,$cnt_active,$bomb,$max_phase)
{
/*********/

	$query='SELECT `area`,`product`
			from rpt_masterhm_cells
			where `area`="'.$arid.'" and `product`="'.$prid.'" limit 1';

	$res = mysql_query($query);
	$curtime = date('Y-m-d H:i:s');
	if($res === false)
	{
		$log = 'Bad SQL query getting data from rpt_masterhm_cells. mysql_error=' . mysql_error() . ' query=' . $query;
		global $logger;
		$logger->fatal($log);
		die($log);
	}
	$row = mysql_fetch_assoc($res);
//			pr($row);
	if($row["area"])
	{
		$query='UPDATE rpt_masterhm_cells SET 
				`count_total` = "'. $cnt_total .'",
				`count_active` ="'. $cnt_active.'",
				`bomb_auto` = "'. $bomb .'",
				`highest_phase` = "'. $max_phase .'",
				`last_update` = "'. $curtime .'"

				';
		$res = mysql_query($query);
		if($res === false)
		{
			$log = 'Bad SQL query saving data in rpt_masterhm_cells. Mysql_error=' . mysql_error() . '.  Mysql Query=' . $query;
			global $logger;
			$logger->fatal($log);
			die($log);
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
		$res = mysql_query($query);
		if($res === false)
		{
			$log = 'Bad SQL query saving data in rpt_masterhm_cells.  Mysql_error=' . mysql_error() . '.  Mysql Query=' . $query;
			global $logger;
			$logger->fatal($log);
			die($log);
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
	
			
	if(!$rs = mysql_query($get_bomb_query1)) die('error in getting larvol id from larvol_trials. Query='.$get_bomb_query1) ;
	
	$trials = array();
	while ($row = mysql_fetch_assoc($rs))
		$trials[] = $row["larvol_id"];
	if (count($trials) == 0)
		return "";
	$trials = implode(",", $trials);
	$get_bomb_query2 = "SELECT MAX(phase) AS phase FROM data_trials where
			larvol_id IN (".$ids.") AND larvol_id NOT IN (".$trials.")";
	if(!$rs = mysql_query($get_bomb_query2)) die('error in getting max(phase) from larvol_trials. Query='.$get_bomb_query2) ;

	$row = mysql_fetch_assoc($rs);
	$phase = $row["phase"];
	$get_bomb_query3 = "SELECT larvol_id FROM data_trials where
			`phase` = '".$phase."' AND larvol_id IN (".$ids.") AND
			larvol_id NOT IN (".$trials.") AND
			overall_status IN (".$bombStatuses.")";
	if(!$rs = mysql_query($get_bomb_query3)) die('error in getting larvol_id from larvol_trials. Query='.$get_bomb_query3) ;
	
	if (mysql_fetch_assoc($rs))
		return "small";
	return "large";
}


?>  