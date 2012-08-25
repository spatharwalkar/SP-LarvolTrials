<?
//ini_set('error_reporting', E_ALL );
ini_set('memory_limit','256M');
ini_set('max_execution_time','36000');	//10 hours
require_once('db.php');
require_once('include.util.php');
require_once('header.php');

//allow only admins to continue
if($db->loggedIn() and ($db->user->userlevel=='admin'||$db->user->userlevel=='root'))
{
	//continue;
}
else
{
	die(' Plelase login as admin to use this feature.');
}

// get all secondary ids and org ids into an arrays
$query = "select a.secondary_id,a.org_study_id,a.larvol_id,nct_id from data_nct a";
$sec_ids=array();	
$org_ids=array();	
$nctids=array();
$allsourceids=array();
$res1 		= mysql_query($query) ;
while($row = mysql_fetch_assoc($res1))
{
	$sec_ids[$row['secondary_id']] =  $row['larvol_id'];
	$org_ids[$row['org_study_id']] = $row['larvol_id'];
	$nc= padnct($row['nct_id']);
	$nctids[$nc] =  $row['larvol_id'];
	$allsourceids[$nc] =  $row['larvol_id'];
}

// get all eudract ids into an array
$query ="select a.eudract_id,a.larvol_id,a.nct_id from data_eudract a";
$eud_ids=array();	
$euids=array();	
$res1 		= mysql_query($query) ;
while($row = mysql_fetch_assoc($res1))
{
	$eud_ids[$row['eudract_id']] = $row['larvol_id'];
	$euids[$row['nct_id']] = $row['eudract_id'];
	$allsourceids[$row['eudract_id']] = $row['larvol_id'];
}
//delete unwanted  secondary_ids.

foreach($sec_ids as $key=>$val)
{
	if (array_key_exists($key, $eud_ids) and !empty($key) and $eud_ids[$key]<>$val ) 
	{
		continue;
	}
	else 
	{
		unset($sec_ids[$key]);
	}
}
//delete unwanted  org_study_ids.
foreach($org_ids as $key=>$val)
{
	if (array_key_exists($key, $eud_ids)and !empty($key) and $eud_ids[$key]<>$val) 
	{
		continue;
	}
	else 
	{
		unset($org_ids[$key]);
	}
}
//delete unwanted  eudract_ids.
foreach($eud_ids as $key=>$val)
{
	$as=array_search($key,$euids);
	if ( isset($as) and $as)
	{
		continue;
	}
	else 
	{
		unset($eud_ids[$key]);
	}
}
foreach($euids as $key=>$val)
{
	if (array_key_exists($key, $nctids)and !empty($key) and $eud_ids[$val]<>$nctids[$key]) 
	{
		$eud_ids[$key]=$eud_ids[$val];
		unset($eud_ids[$val]);
		continue;
	}
	else 
	{
		unset($eud_ids[$val]);
	}
}

$org_ids=array_merge($sec_ids,$org_ids,$eud_ids);
unset($sec_ids);
$i=0;
if(isset($_POST['autolink_all']) and $_POST['autolink_all']='YES')
{
	foreach($org_ids as $key=>$oid)
	{
		$i++;
		$sid=trim($key);
		$source=substr(trim($key),0,3);
		if($source=='NCT') $source='EUDRACT';
		else $source='NCT';
		$lid=$oid;
		$strr=autolink_trials($sid,$lid,$source,$i);
		/*
		if($i>3)
		{
			exit;
		}
		*/
	}
}
echo '
<script type="text/javascript">
function confirmlinking()
{ 
	if(confirm("Are you sure you want to automatically link ALL the trials in the suggested list ?"))
	{
		document.forms["linkallauto"].submit();
	}
}
</script>
<div style="font-family: Helvetica;font-size:14px;padding-left:200px;" >
		<form name="linkallauto" id="linkallauto" method="post" action="universal_linking.php">
		<input type="hidden" name="autolink_all" id="autolink_all" value="YES">
		<input type="submit" style="font-family: Helvetica;font-size:14px;" value="Link all suggested trials automatically" onclick="confirmlinking();return false;" /></form></div><br>
		<div style="clear:both"></div>
		<div style="font-family: Helvetica;font-size:14px;padding-left:200px;" >
	  ';

echo '<br><b><span style="color:red;font-size:17px;">  &nbsp;  &nbsp;  &nbsp;  &nbsp;    &nbsp;    &nbsp;    &nbsp;   &nbsp;  &nbsp;  List of suggested trials</span></b>
		 ';

echo '<br><table>';
echo '<tr>';
echo '<td style="font-family: Helvetica;font-size:15; padding-left:5px;" ><b> &nbsp; S.No. &nbsp; </b></td>';
echo '<td style="font-family: Helvetica;font-size:15; padding-left:5px;" ><b> &nbsp; Larvol ID &nbsp; </b></td>';
echo '<td style="font-family: Helvetica;font-size:15; padding-left:5px;" ><b> &nbsp; Matched NCT/EudraCT No. &nbsp; </b></td>';
echo '<td style="font-family: Helvetica;font-size:15; padding-left:5px;" ><b> &nbsp; Action &nbsp; </b></td>';
echo '</tr>';
$j=1;
foreach($org_ids as $key=>$oid)
{
	echo '<tr><td style="font-family: Helvetica;  font-size:14;padding-left:5px;"> &nbsp;  &nbsp;  &nbsp;  '.$j++.' </td>';
	echo '<td style="font-family: Helvetica;  font-size:14;padding-left:5px;"> &nbsp;  &nbsp;  &nbsp;  '.$oid .' </td>';
	echo '<td style="font-family: Helvetica;  font-size:14;padding-left:5px;"> &nbsp;  &nbsp;  &nbsp;  '.$key .' </td>';	
	echo '<td style="font-family: Helvetica;  font-size:14;padding-left:5px;"> 
		  <form method="post" action="link_trials.php?lid='.$oid.'">
		  <input type="submit" value="Link"></form></td>';
	echo '</tr>';
}
echo '</table>';
echo '</div>';

function autolink_trials($sid,$lid,$source,$counter)
{
	
	global $logger,$allsourceids;
	if($source == 'NCT')
	{
		$query = "
				SELECT `source_id`,`larvol_id`,`brief_title` 
				FROM `data_trials` 
				WHERE `source_id` = '$sid' limit 1
				";

		$res1 	= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
			return $log;
		}

		$source_trial=mysql_fetch_assoc($res1);
	
		// check if manual entry exists for both larvol ids, and if yes, then linking not possible.
		$query = "
				SELECT `larvol_id`
				FROM `data_manual` 
				WHERE larvol_id in 
				(" . $source_trial['larvol_id'] . "," . $lid . ")
				
				";

		$res1 	= mysql_query($query) ;
		$num_rows = mysql_num_rows($res1);
		if($num_rows>1)
		{
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .' becuase manual entries exist for both trials.</span></b>');
			return;
		}
		//
		
		// link data_nct data (change larvol id)
		$query = '
				UPDATE data_nct 
				set larvol_id="'  . $source_trial['larvol_id'] . '"
				WHERE `larvol_id` ="' .  $lid .'" limit 1
				';
		$res1 		= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
			return $log;
		}
		
		$query = '
				UPDATE data_manual 
				set larvol_id="'  . $source_trial['larvol_id'] . '"
				WHERE `larvol_id` ="' .  $lid .'" limit 1
				';
		$res1 		= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
			return $log;
		}

		$query = '
				DELETE FROM `data_trials` 
				where larvol_id="' .  $lid .'" limit 1
				';
		$res1 		= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
			
			return $log;
		}
		// update sphinx index
		if(isset($lid) and !empty($lid) and $lid>0)
		{
			global $sphinx;
			delete_sphinx_index($lid);
		}
		$osid = array_search($lid, $allsourceids);
		pr('<br><b><span style="color:black">'.$counter.'. Larvol id/Source Id : ' .  $lid .'/'.$osid .' has been linked to SOURCE ID:'.$sid.' and larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
		//require_once('edit_trials.php');
		return true;

	}
	elseif($source == 'EUDRACT')
	{
		$query = "
			SELECT `source_id`,`larvol_id`,`brief_title` 
			FROM `data_trials` 
			WHERE `source_id` = '$sid' limit 1
			";

		$res1 	= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
			return $log;
		}

		$source_trial=mysql_fetch_assoc($res1);
		
		// check if manual entry exists for both larvol ids, and if yes, then linking not possible.
		$query = "
				SELECT `larvol_id`
				FROM `data_manual` 
				WHERE larvol_id in 
				(" . $source_trial['larvol_id'] . "," . $lid . ")
				
				";

		$res1 	= mysql_query($query) ;
		$num_rows = mysql_num_rows($res1);
		if($num_rows>1)
		{
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .' becuase manual entries exist for both trials.</span></b>');
			return;
		}
		//
		
		
		
		// update data_eudract data (change larvol id)
		$query = '
			UPDATE data_eudract 
			set larvol_id="'  . $source_trial['larvol_id'] . '" 
			WHERE `larvol_id` ="' .  $lid .'" limit 1
			';
		$res1 		= mysql_query($query) ;

		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
			return $log;
		}
		
		$query = '
			UPDATE data_manual 
			set larvol_id="'  . $source_trial['larvol_id'] . '" 
			WHERE `larvol_id` ="' .  $lid .'" limit 1
			';
		$res1 		= mysql_query($query) ;

		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
			return $log;
		}
		//delete the trial from data trial as it is no longer needed

		$query = '
			DELETE FROM `data_trials` 
			where larvol_id="' .  $lid .'" limit 1
			';
		$res1 		= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			$osid = array_search($lid, $allsourceids);
			pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
			return $log;
		}
		
		// update sphinx index
		if(isset($lid) and !empty($lid) and $lid>0)
		{
			global $sphinx;
			delete_sphinx_index($lid);
		}

		$osid = array_search($lid, $allsourceids);
		pr('<br><b><span style="color:black">'.$counter.'. Larvol id/Source Id : ' .  $lid .'/'.$osid .' has been linked to SOURCE ID:'.$sid.' and larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
		return true;
	}			

}


?>