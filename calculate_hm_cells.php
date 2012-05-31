<?php
require_once('db.php');
require_once('include.util.php');
ini_set('max_execution_time', '360000'); //100 hours
ignore_user_abort(true);

$data=array();$isactive=array();$instype=array();$ldate=array();$phases=array();$ostatus=array();$cnt_total=0;


if(isset($_GET['area']) or isset($_GET['product']))
{
	$parameters=$_GET;
	if(!calc_cells($parameters))	echo '<br><b>Could complete calculating cells, there was an error.<br></b>';
	echo '<br>All done.<br>';
}
elseif( isset($_GET['calc']) and ($_GET['calc']=="all") )
{
	$parameters=NULL;
	if(!calc_cells($parameters))	echo '<br><b>Could complete calculating cells, there was an error.<br></b>';
	echo '<br>All done.<br>';
}

function calc_cells($parameters,$update_id=NULL)
{
	/*
	pr($parameters);
	pr('ABOVE PARAMETERS');
	$x=array();
	$x['area']=29;
	pr($x);
	*/

	global $data,$isactive,$instype,$ldate,$phases,$ostatus,$cnt_total;
	$data=array();$isactive=array();$instype=array();$ldate=array();$phases=array();$ostatus=array();$cnt_total=0;
	$cron_run = isset($update_id); 	// check if being run by cron.php
	
	$display_status='NO';
	$id = mysql_real_escape_string($_GET['id']);
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
	elseif(isset($parameters['area']))
	{
	
		$display_status='YES';
		$query = '	select max(update_id) as maxid from  update_status_fullhistory ';
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
		$row = mysql_fetch_assoc($res);
		$update_id=$row['maxid']+1;
		
		
		$query = '	select update_id,trial_type,status from update_status_fullhistory where 
					trial_type="RECALC=' . $id . '" and status="2" ' ;
		
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
		$x=mysql_fetch_assoc($res);
		if(isset($x['update_id']))
		{
			$update_id=$x['update_id'];

		}
		else
		{			
			
			
			$query = '	INSERT into update_status_fullhistory SET 
						start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", 
						updated_days="0", status="2", trial_type="RECALC=' . $id . '", update_id="' . $update_id . '"';
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
	//get area ids
	if(isset($parameters['area']))
	{
		$query='select `id` from areas where `id`="'.$parameters['area'].'"';
	}
	
	else // no area given, select all .
	{
		$query='select `id` from areas order by `id`';
		
		// update query to fire the trigger (non-change)
		$activate_trigger=' update upm set event_description=event_description
							where 
							(   
								end_date <= left(now(),10) and 
								id > 0 and 
								status="Upcoming" 
							)';
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
	
	$totalcount=($x*$y)/4;
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
	elseif($display_status=='YES')
	{
	    $query = '	UPDATE update_status_fullhistory SET update_items_total=update_items_total+' . $totalcount . ',
					status="2", update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" 
					WHERE update_id="' . $update_id . '"';
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
			$data=array();$isactive=array();$instype=array();$ldate=array();$phases=array();$ostatus=array();$cnt_total=0;
		
			if(!$pv['id'] or is_null($pv['id']) or empty($pv['id']))
			{
				echo '<br> ';
				continue;
			}      
			/*
			$query_m='	SELECT a.trial from area_trials a 
						JOIN product_trials p
						ON a.trial=p.trial
						where a.area="'.$av['id'].'" and p.product="'.$pv['id'].'"';
			*/
			$query_m='	SELECT a.trial,d.source_id,d.is_active,d.institution_type,d.lastchanged_date,d.firstreceived_date,d.phase,d.overall_status from area_trials a 
						JOIN product_trials p ON a.`trial`=p.`trial`
						LEFT JOIN data_trials d ON p.`trial`=d.`larvol_id`
						where a.`area`="'.$av['id'].'" and p.`product`="'.$pv['id'].'" ';
			
			if(!$res = mysql_query($query_m))
					{
						$log='There seems to be a problem with the SQL Query:'.$query_m.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
			$phasez=array();
			
			$overall_statuses=array();
			$overall_statuses['not_yet_recruiting']=0;
			$overall_statuses['recruiting']=0;
			$overall_statuses['enrolling_by_invitation']=0;
			$overall_statuses['active_not_recruiting']=0;
			$overall_statuses['completed']=0;
			$overall_statuses['suspended']=0;
			$overall_statuses['terminated']=0;
			$overall_statuses['withdrawn']=0;
			$overall_statuses['available']=0;
			$overall_statuses['no_longer_available']=0;
			$overall_statuses['approved_for_marketing']=0;
			$overall_statuses['no_longer_recruiting']=0;
			$overall_statuses['withheld']=0;
			$overall_statuses['temporarily_not_available']=0;
			$overall_statuses['ongoing']=0;
			$overall_statuses['not_authorized']=0;
			$overall_statuses['prohibited']=0;
			$overall_statuses['new_trials']=0;

			
			while ($row = mysql_fetch_assoc($res))
			{	
				if($row["trial"])
				{
					$data[] = $row["trial"];
					$isactive[] = $row["is_active"];
					$instype[] = $row["institution_type"];
					$ldate[] = $row["lastchanged_date"];
					$phases[] = $row["phase"];
					if($row["phase"]<>'N/A') $phasez[] = $row["phase"];
					$ostatus[] = $row["overall_status"];
					$cnt_total++;

					/*********** trial counts according to overallstatus values ***********/

					$base_date = date('Y-m-d', strtotime("-7 days"));
					if($row["firstreceived_date"]>=$base_date) $overall_statuses['new_trials']=$overall_statuses['new_trials']+1;
					
					if($row["lastchanged_date"]>=$base_date)
					{
					
						switch ($row["overall_status"]) 
						{
							case 'Not yet recruiting':
								$overall_statuses['not_yet_recruiting']=$overall_statuses['not_yet_recruiting']+1;
								break;
							case 'Recruiting':
								$overall_statuses['recruiting']=$overall_statuses['recruiting']+1;
								break;
							case 'Enrolling by invitation':
								$overall_statuses['enrolling_by_invitation']=$overall_statuses['enrolling_by_invitation']+1;
								break;
							case 'Active, not recruiting':
								$overall_statuses['active_not_recruiting']=$overall_statuses['active_not_recruiting']+1;
								break;
							case 'Completed':
								$overall_statuses['completed']=$overall_statuses['completed']+1;
								break;
							case 'Suspended':
								$overall_statuses['suspended']=$overall_statuses['suspended']+1;
								break;
							case 'Terminated':
								$overall_statuses['terminated']=$overall_statuses['terminated']+1;
								break;
							case 'Withdrawn':
								$overall_statuses['withdrawn']=$overall_statuses['withdrawn']+1;
								break;
							case 'Available':
								$overall_statuses['available']=$overall_statuses['available']+1;
								break;
							case 'No Longer Available':
								$overall_statuses['no_longer_available']=$overall_statuses['no_longer_available']+1;
								break;
							case 'Approved for marketing':
								$overall_statuses['approved_for_marketing']=$overall_statuses['approved_for_marketing']+1;
								break;
							case 'No longer recruiting':
								$overall_statuses['no_longer_recruiting']=$overall_statuses['no_longer_recruiting']+1;
								break;
							case 'Withheld':
								$overall_statuses['withheld']=$overall_statuses['withheld']+1;
								break;
							case 'Temporarily Not Available':
								$overall_statuses['temporarily_not_available']=$overall_statuses['temporarily_not_available']+1;
								break;
							case 'Ongoing':
								$overall_statuses['ongoing']=$overall_statuses['ongoing']+1;
								break;
							case 'Not Authorized':
								$overall_statuses['not_authorized']=$overall_statuses['not_authorized']+1;
								break;
							case 'Prohibited':
								$overall_statuses['prohibited']=$overall_statuses['prohibited']+1;
								break;
						}
						
					}
					else
					{
					
					
						$query_dh= 'select larvol_id,overall_status_prev,overall_status_lastchanged from data_history 
									where overall_status_lastchanged is not null  and  larvol_id='. $row["trial"] . '
									limit 1 ';
						
						if(!$res = mysql_query($query_dh))
								{
									$log='There seems to be a problem with the SQL Query:'.$query_dh.' Error:' . mysql_error();
									global $logger;
									$logger->error($log);
									echo $log;
									return false;
								}
						while ($row = mysql_fetch_assoc($res))
						{	
							if($row["larvol_id"])
							{
								switch ($row["overall_status_prev"]) 
								{
									case 'Not yet recruiting':
										$overall_statuses['not_yet_recruiting']=$overall_statuses['not_yet_recruiting']+1;
										break;
									case 'Recruiting':
										$overall_statuses['recruiting']=$overall_statuses['recruiting']+1;
										break;
									case 'Enrolling by invitation':
										$overall_statuses['enrolling_by_invitation']=$overall_statuses['enrolling_by_invitation']+1;
										break;
									case 'Active, not recruiting':
										$overall_statuses['active_not_recruiting']=$overall_statuses['active_not_recruiting']+1;
										break;
									case 'Completed':
										$overall_statuses['completed']=$overall_statuses['completed']+1;
										break;
									case 'Suspended':
										$overall_statuses['suspended']=$overall_statuses['suspended']+1;
										break;
									case 'Terminated':
										$overall_statuses['terminated']=$overall_statuses['terminated']+1;
										break;
									case 'Withdrawn':
										$overall_statuses['withdrawn']=$overall_statuses['withdrawn']+1;
										break;
									case 'Available':
										$overall_statuses['available']=$overall_statuses['available']+1;
										break;
									case 'No Longer Available':
										$overall_statuses['no_longer_available']=$overall_statuses['no_longer_available']+1;
										break;
									case 'Approved for marketing':
										$overall_statuses['approved_for_marketing']=$overall_statuses['approved_for_marketing']+1;
										break;
									case 'No longer recruiting':
										$overall_statuses['no_longer_recruiting']=$overall_statuses['no_longer_recruiting']+1;
										break;
									case 'Withheld':
										$overall_statuses['withheld']=$overall_statuses['withheld']+1;
										break;
									case 'Temporarily Not Available':
										$overall_statuses['temporarily_not_available']=$overall_statuses['temporarily_not_available']+1;
										break;
									case 'Ongoing':
										$overall_statuses['ongoing']=$overall_statuses['ongoing']+1;
										break;
									case 'Not Authorized':
										$overall_statuses['not_authorized']=$overall_statuses['not_authorized']+1;
										break;
									case 'Prohibited':
										$overall_statuses['prohibited']=$overall_statuses['prohibited']+1;
										break;
								}
								
							}
						}
					
					
					
					
					
					}
					
					/*********** END : trial counts according to overallstatus values ***********/
				}
			}
			
			$cnt_total=count($data);
			
			if(!$cnt_total or $cnt_total<1) 
			{
				
				if($counter>=20000)
				{
					$counter=0;
					echo '<br>20000 records added, sleeping 1 second....'.str_repeat("  ",800);
					sleep(1);
				}
				add_data($av['id'],$pv['id'],0,0,0,'none','N/A',$overall_statuses);
				$progress_count ++;
				if($cron_run)
				{
					$query = '	UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",
								update_items_progress="' . $progress_count . '", status="2" 
								WHERE update_id="' . $update_id . '"';
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
				}
				elseif($display_status=='YES')
				{
					$query = '	UPDATE update_status_fullhistory SET status="2", 
								updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",
								update_items_progress=update_items_progress+' . $progress_count . ' 
								WHERE update_id="' . $update_id . '"';
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
			$cnt_active=0;
			foreach($isactive as $act)
			{
				if($act==1 or $act=="1")
				{
					$cnt_active++;
				}
			}
			$cnt_active_indlead=0;
			foreach($instype as $key=>$act)
			{
				if( ($isactive[$key]==1 or $isactive[$key]=="1") and $act=='industry_lead_sponsor' )
				{
					$cnt_active_indlead++;
				}
			}
			
			if(!empty($phasez))
				$max_phase = max($phasez);
			else
				$max_phase = 'N/A';
			
			$bomb=getBombdtl();
			if($counter>=5000)
			{
				$counter=0;
				echo '<br>5000 records added, sleeping 1 second ....'.str_repeat("  ",800);
				sleep(1);
			}
			
			add_data($av['id'],$pv['id'],$cnt_total,$cnt_active,$cnt_active_indlead,$bomb,$max_phase,$overall_statuses);
			$progress_count ++;
			if($cron_run)
			{
				$query = 'UPDATE update_status SET status="2", updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress="' . $progress_count . '" WHERE update_id="' . $update_id . '"';
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					global $logger;
					$logger->error($log);
					echo $log;
					return false;
				}
			}
			elseif($display_status=='YES')
			{
				$query = 'UPDATE update_status_fullhistory SET status="2", updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress=update_items_progress+' . $progress_count . ' WHERE update_id="' . $update_id . '"';
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
		/*
		$query = 'UPDATE update_status 
		SET status="0", WHERE update_id="' . $update_id . '"';
		
		*/
		
		$query = 'UPDATE `update_status` 
				  SET `status`="0", `end_time`="' . date("Y-m-d H:i:s", strtotime('now')) . '" 
				  WHERE `update_id`="' . $update_id . '"';
				  
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
	}
//	echo '<br>All Done.';

	//activate the trigger if required.
	if( isset($activate_trigger) and !empty($activate_trigger) )
	{
		echo '<br> Activating trigger to update status...<br>';
		if(!$res = mysql_query($activate_trigger))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
	}

	return true;
}			

function add_data($arid,$prid,$cnt_total,$cnt_active,$cnt_active_indlead,$bomb,$max_phase,$overall_statuses=null)
{
/*********/
global $data,$isactive,$instype,$ldate,$phases,$ostatus,$cnt_total;
	$query='SELECT `area`,`product`
			from rpt_masterhm_cells
			where `area`="'.$arid.'" and `product`="'.$prid.'" limit 1';
			/*
			pr('---------------------------');
			pr($overall_statuses);
			pr('---------------------------');
			*/
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
				`count_active`,count_active_indlead,highest_phase,
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
		$highest_phase_old = $row["highest_phase"];
		
		//if there is a difference in counts, then update the _prev fields
		$aa='';$bb='';$cc='';$dd='';
		if($count_active_old<>$cnt_active) $aa='`count_active_prev` = "'. $count_active_old .'",';
		if($count_total_old<>$cnt_total) $bb='`count_total_prev` = "'. $count_total_old .'",';
		if($cnt_indlead_old<>$cnt_active_indlead) $cc='`count_active_indlead_prev` = "'. $cnt_indlead_old .'",';
		if($highest_phase_old<>$max_phase) $dd='`highest_phase_prev` = "'. $highest_phase_old .'",';
		
		
		if( empty($aa) && empty($bb) && empty($cc) && empty($dd))
		{
		$query='UPDATE rpt_masterhm_cells 
				SET 
				`count_active` ="'. $cnt_active.'",
				`count_active_indlead` ="'. $cnt_active_indlead.'",
				`bomb_auto` = "'. $bomb .'",
				`highest_phase` = "'. $max_phase .'",
				`count_total` = "'. $cnt_total .'",
				`not_yet_recruiting` = "'. $overall_statuses['not_yet_recruiting'] .'",
				`recruiting` = "'. $overall_statuses['recruiting'] .'",
				`enrolling_by_invitation` = "'. $overall_statuses['enrolling_by_invitation'] .'",
				`active_not_recruiting` = "'. $overall_statuses['active_not_recruiting'] .'",
				`completed` = "'. $overall_statuses['completed'] .'",
				`suspended` = "'. $overall_statuses['suspended'] .'",
				`terminated` = "'. $overall_statuses['terminated'] .'",
				`withdrawn` = "'. $overall_statuses['withdrawn'] .'",
				`available` = "'. $overall_statuses['available'] .'",
				`no_longer_available` = "'. $overall_statuses['no_longer_available'] .'",
				`approved_for_marketing` = "'. $overall_statuses['approved_for_marketing'] .'",
				`no_longer_recruiting` = "'. $overall_statuses['no_longer_recruiting'] .'",
				`withheld` = "'. $overall_statuses['withheld'] .'",
				`temporarily_not_available` = "'. $overall_statuses['temporarily_not_available'] .'",
				`ongoing` = "'. $overall_statuses['ongoing'] .'",
				`not_authorized` = "'. $overall_statuses['not_authorized'] .'",
				`prohibited` = "'. $overall_statuses['prohibited'] .'",
				`new_trials` = "'. $overall_statuses['new_trials'] .'",
				`last_calc` = "'. $curtime .'" where
				`area`="'.$arid.'" and `product`="'.$prid.'" 
				';
		}
		else
		{

			$query='UPDATE rpt_masterhm_cells 
				SET 
				`count_active` ="'. $cnt_active.'",
				`count_active_indlead` ="'. $cnt_active_indlead.'",
				`bomb_auto` = "'. $bomb .'",
				`highest_phase` = "'. $max_phase .'",
				`not_yet_recruiting` = "'. $overall_statuses['not_yet_recruiting'] .'",
				`recruiting` = "'. $overall_statuses['recruiting'] .'",
				`enrolling_by_invitation` = "'. $overall_statuses['enrolling_by_invitation'] .'",
				`active_not_recruiting` = "'. $overall_statuses['active_not_recruiting'] .'",
				`completed` = "'. $overall_statuses['completed'] .'",
				`suspended` = "'. $overall_statuses['suspended'] .'",
				`terminated` = "'. $overall_statuses['terminated'] .'",
				`withdrawn` = "'. $overall_statuses['withdrawn'] .'",
				`available` = "'. $overall_statuses['available'] .'",
				`no_longer_available` = "'. $overall_statuses['no_longer_available'] .'",
				`approved_for_marketing` = "'. $overall_statuses['approved_for_marketing'] .'",
				`no_longer_recruiting` = "'. $overall_statuses['no_longer_recruiting'] .'",
				`withheld` = "'. $overall_statuses['withheld'] .'",
				`temporarily_not_available` = "'. $overall_statuses['temporarily_not_available'] .'",
				`ongoing` = "'. $overall_statuses['ongoing'] .'",
				`not_authorized` = "'. $overall_statuses['not_authorized'] .'",
				`prohibited` = "'. $overall_statuses['prohibited'] .'",
				`new_trials` = "'. $overall_statuses['new_trials'] .'",
				`count_total` = "'. $cnt_total .'",'
				. $aa . $bb . $cc . $dd .
				'`count_lastchanged` = "'. $curtime .'",
				`highest_phase_lastchanged` = "'. $curtime .'",
				`last_calc` = "'. $curtime .'",
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
				`not_yet_recruiting` = "'. $overall_statuses['not_yet_recruiting'] .'",
				`recruiting` = "'. $overall_statuses['recruiting'] .'",
				`enrolling_by_invitation` = "'. $overall_statuses['enrolling_by_invitation'] .'",
				`active_not_recruiting` = "'. $overall_statuses['active_not_recruiting'] .'",
				`completed` = "'. $overall_statuses['completed'] .'",
				`suspended` = "'. $overall_statuses['suspended'] .'",
				`terminated` = "'. $overall_statuses['terminated'] .'",
				`withdrawn` = "'. $overall_statuses['withdrawn'] .'",
				`available` = "'. $overall_statuses['available'] .'",
				`no_longer_available` = "'. $overall_statuses['no_longer_available'] .'",
				`approved_for_marketing` = "'. $overall_statuses['approved_for_marketing'] .'",
				`no_longer_recruiting` = "'. $overall_statuses['no_longer_recruiting'] .'",
				`withheld` = "'. $overall_statuses['withheld'] .'",
				`temporarily_not_available` = "'. $overall_statuses['temporarily_not_available'] .'",
				`ongoing` = "'. $overall_statuses['ongoing'] .'",
				`not_authorized` = "'. $overall_statuses['not_authorized'] .'",
				`prohibited` = "'. $overall_statuses['prohibited'] .'",
				`new_trials` = "'. $overall_statuses['new_trials'] .'",
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


function getBombdtl()
{
	global $data,$isactive,$instype,$ldate,$phases,$ostatus,$cnt_total;
	global $logger;
		
	if (count($data) == 0)
		return "";
	
	$bombStatuses = '"Active, not recruiting","Not yet recruiting","Recruiting","Enrolling by invitation"';
	$past = "'".date("Y-m-d H:i:s", time() - (int)(540*24*3600))."'";
	
	$tmpphase=array();
	foreach($ldate as $key=>$ld)
	{
		if($ld < $past)
			continue;
		else
			$tmpphase[]=$data[$key];
	}
	$phase = max($tmpphase);
	$bomb="large";
	foreach($ostatus as $key=>$ld)
	{
		if	(
				( 
					($ld =="Active, not recruiting")or($ld =="Not yet recruiting")or( $ld =="Recruiting")or ($ld == "Enrolling by invitation") 
				)
				and $phases[$key] == $phase
				and $ldate[$key] < $past 
			)
			$bomb="small";
		else
			continue;
	}
	
	
	return $bomb;
	
}


?>  