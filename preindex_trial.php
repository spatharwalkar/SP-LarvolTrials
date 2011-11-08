<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');

function getStudyCat($nctid)
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

function tindex($id,$cat)
{
	if($cat=='products') $catid='8'; else $catid='9'; 
	global $logger;
	global $now;
	global $db;
	$DTnow = date('Y-m-d H:i:s',$now);
	
	$productz=array();
	if(!is_numeric($id)) return false;
	$query = 'SELECT id,name,searchdata from '. $cat .' where searchdata IS NOT NULL ';
	if(!$resu = mysql_query($query))
	{
		$log='Bad SQL query getting  details from '. $cat .' table.<br>Query=' . $query;
		$logger->fatal($log);
		echo $log;
		exit;
	}
//	$productz = mysql_fetch_assoc($resu);
	while($productz[]=mysql_fetch_array($resu));
	
	if(count($productz)>0)
	{
		foreach ($productz as $key=>$value)
		{
			
			if(!isset($value['id']) or empty($value['id'])) break;
			$xy = (removeNullSearchdata(unserialize(base64_decode($value['searchdata']))));
			$xy = array('action' => $xy['action'], 'searchval' => $xy['searchval'], 
									'negate' => $xy['negate'],
									'multifields' => $xy['multifields'], 
									'multivalue' => $xy['multivalue'], 'weak' => $xy['weak']);
									
	

			$xy = prepareParams($xy);
	
			$pid=$value['id'];
		
			$srch= search_single_trial($xy,NULL,$id);
			
			if($srch)
			{
				$pfid=get_product_field($pid,$catid);
//				if($pfid and $pfid=='YES') $pfid=get_product_field($pid,$catid);
		
				if(trial_indexed($id,$pfid))
				{
					$query='update data_values set field="' . $pfid . '", `added`="' . $DTnow . '", val_bool="1" where studycat=' . $id . ' and field="' . $catid .'" limit 1 ';
					$res = mysql_query($query);
					if($res === false)
					{
						$log = 'Bad SQL query pre-indexing trial. Query:' . $query;
						$logger->fatal($log);
						die($log);
						unset($log);
					}
				
				}
						
				else
				{
					$query='INSERT INTO `data_values` (`field`, `studycat`, `val_bool`, `added`) VALUES ("' . $pfid . '", "' . $id .'", "1", "' . $DTnow . '") ';
					$res = mysql_query($query);
					if($res === false)
					{
						$log = 'Bad SQL query pre-indexing trial. Query:' . $query;
						$logger->fatal($log);
						die($log);
						unset($log);
					}
				
				}
			}
		}
	}
}

function trial_indexed($id,$pfid)
{
	global $logger;
	$query = 'SELECT studycat FROM data_values where studycat=' . $id . ' and field=' . $pfid . ' LIMIT 1';
	$resu 		= mysql_query($query) ;
	if($resu===false)
	{
	
	$log = 'Bad SQL query checking trial index status. Query=' . $query;
	$logger->fatal($log);
	die($log);

	}
	
	$resu = mysql_fetch_array($resu);
	if ($resu === false) return false; 
	else return($resu);

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