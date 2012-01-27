<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('searchhandler_new.php');
function getStudyCatId($nctid)
{
global $logger;
if(!isset($nctid) or empty($nctid)) return false;
$query = 'SELECT studycat from data_values where val_int = "' . $nctid . '" and field = "1" limit 1 ';
	if(!$resu = mysql_query($query))
	{
		$log='Bad SQL query getting  studycat .<br>Query=' . $query;
		$logger->fatal($log);
		echo $log;
		exit;
	}
//	$productz = mysql_fetch_assoc($resu);
	$resu=mysql_fetch_array($resu);
	return $resu['studycat'];
}


function tindex($sourceid,$cat,$productz=NULL,$up_id=NULL,$cid=NULL,$productID=NULL)
{
	
	if($cat=='products') 
	{
		$table='product_trials'; 
		$field='product';
	}
	else 
	{
		$table='area_trials'; 
		$field='area';
	}
	global $logger;
	global $now;
	global $db;
	$DTnow = date('Y-m-d H:i:s',$now);
	if(!isset($i)) $i=0;
	if(is_null($productz))
	{
		$productz=array();
		if(is_null($productID))	$query = 'SELECT id,name,searchdata from '. $cat .' where searchdata IS NOT NULL and  searchdata <>"" ';
		else $query = 'SELECT id,name,searchdata from '. $cat .' where searchdata IS NOT NULL and  searchdata <>"" and id="' . $productID .'"' ;
		if(!$resu = mysql_query($query))
		{
			$log='Bad SQL query getting  details from '. $cat .' table.<br>Query=' . $query;
			$logger->fatal($log);
			echo $log;
			exit;
		}
	//	$productz = mysql_fetch_assoc($resu);
		while($productz[]=mysql_fetch_array($resu));
	}
	if(!is_null($cid) and !empty($cid) and $cid>0) $startid=$cid; 
	else $startid=0;
	if(count($productz)>0)
	{
		foreach ($productz as $key=>$value)
		{
			
			if(!isset($value['id']) or empty($value['id'])) break;
			$cid=$value['id'];
			$searchdata = $value['searchdata'];
			
			$pid=$value['id'];
			if(!is_null($productz) and $pid>=$startid)	
			{
				$cid=$value['id'];
				$query=buildQuery($searchdata);
				if($query=='Invalid Json') 
				{
					echo '<br> Invalid JSON in table <b>'. $cat .'</b> and id=<b>'.$cid.'</b> : <br>';
					echo $searchdata;
					echo '<br>';
					
					continue;
				}
				if(!$resu = mysql_query($query))
				{
					$log='Bad SQL query getting larvol_id from data_trials table.<br>Query=' . $query;
					$logger->fatal($log);
					echo $log;
					exit;
				}
				$nctidz=array();
				while($nctidz[]=mysql_fetch_array($resu));
				

				foreach($nctidz as $key => $value)
				{
					if( isset($sourceid) and !is_null($sourceid) and !empty($sourceid) )
					{
						$srch = array_search($sourceid, $value); 
						if($srch!==false) echo ''; else continue;
					}
					
					$indextable=$cat=='products' ? 'product_trials' : 'area_trials';
					$larvol_id=$value['larvol_id'];
					if(!isset($larvol_id) or empty($larvol_id) or is_null($larvol_id)) 
					{
						continue;
					}
					else
					{
					
						if(trial_indexed($larvol_id,$cat,$cid))
						{
							echo '<br>Larvol ID:'.$larvol_id . ' is already indexed. <br>';
						}
						else
						{
							echo '<br>Indexing Larvol ID:'.$larvol_id . '<br>';
							$query='INSERT INTO `'. $table .'` (`'. $field .'`, `trial`) VALUES ("' . $cid . '", "' . $larvol_id .'") ';
							$res = mysql_query($query);
							if($res === false)
							{
								$log = 'Bad SQL query pre-indexing trial***. Query : ' . $query . '<br> MySql Error:'.mysql_error();
								$logger->fatal($log);
								die($log);
								unset($log);
							}
						
						}
					}
				
				}
				
				$proc_id = getmypid();
				$i++;
				$ttype=$cat=='products' ? 'PRODUCT' : 'AREA';
				$query = 'SELECT update_items_progress,update_items_total FROM update_status_fullhistory WHERE update_id="' . $up_id .'" and trial_type="' . $ttype . '" limit 1 ' ;
				$res = mysql_query($query) or die('Bad SQL query selecting row from update_status_fullhistory. Query='.$query);
				$res = mysql_fetch_array($res) ;
				if ( isset($res['update_items_progress'] ) and $res['update_items_progress'] > 0 ) $updtd_items=((int)$res['update_items_progress']); else $updtd_items=0;
				if ( isset($res['update_items_total'] ) and $res['update_items_total'] > 0 ) $tot_items=((int)$res['update_items_total']); else $tot_items=0;
		
				$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $proc_id  .'" , update_items_progress= "' . ( ($tot_items >= $updtd_items+$i) ? ($updtd_items+$i) : $tot_items  ) . '" , status="2", current_nctid="'. $cid .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" and trial_type= "' . $ttype . '"  ';
				$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory1. Query:' . $query);
				@flush();
				
				
			}
	
			
		}
	}
}

function trial_indexed($larvol_id,$cat,$cid)
{
	$indextable=$cat=='products' ? 'product_trials' : 'area_trials';
	$columnname=$cat=='products' ? 'product' : 'area';
//	$nctid=padnct($nctid);
	global $logger;
//	$query = 'SELECT trial from  ' . $indextable . ' where trial="' . $nctid . '" LIMIT 1';
	$query = 'SELECT trial from  ' . $indextable . ' where trial="' . $larvol_id . '" and `'. $columnname .'`= "' . $cid . '" ';
	$res1 		= mysql_query($query) ;
	if($res1===false)
	{
	
	$log = 'Bad SQL query checking trial index status. Query=' . $query;
	$logger->fatal($log);
	die($log);

	}
	$resu=array();
	while($resu[]=mysql_fetch_array($res1));
	$res2=$resu;
	foreach ($res2 as $key=>$value)
	{
		if(!isset($value) or empty($value) or is_null($value) ) unset($resu[$key]);
	}
	if(count($resu)>0)
	{
		return($resu);
	}
	else
	{
		return false;
	}

}

function get_product_field($pid,$catid)
{
	global $logger;

	$query = 'SELECT id,name FROM data_fields where name ="' . $pid . '" and category="' . $catid .'" LIMIT 1';
	$resu 		= mysql_query($query) ;
	if($resu===false)
	{
	$log = 'Bad SQL query getting product field id. Query=' . $query;
	$logger->fatal($log);
	die($log);

	}
	
	$res1 = array(); while($res2 = mysql_fetch_array($resu)) $res1[] = $res2; 
	if (count($res1)== 0) 
	{
		$y=add_field($pid,$catid);
		return $y; 
	}
	else return($res1[0]['id']);

}
function add_field($pid,$catid)
{
	return true;
	global $logger;
	$query = 'INSERT into `data_fields` (`name`, `type`, `category`) VALUES ("' . $pid . '", "bool", "' . $catid .'") ';
	$resu 		= mysql_query($query) ;
	if($resu===false)
	{
		$log = 'Bad SQL query adding field to data_fieds. Query=' . $query;
		$logger->fatal($log);
		die($log);

	}
	else $insid=mysql_insert_id();
	
	return $insid;
	
}

?>