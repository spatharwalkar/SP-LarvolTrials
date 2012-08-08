<?
//ini_set('error_reporting', E_ALL );
ini_set('memory_limit','256M');
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
$res1 		= mysql_query($query) ;
while($row = mysql_fetch_assoc($res1))
{
	$sec_ids[$row['secondary_id']] =  $row['larvol_id'];
	$org_ids[$row['org_study_id']] = $row['larvol_id'];
	$nc= padnct($row['nct_id']);
	$nctids[$nc] = 1;
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
}
//delete unwanted  secondary_ids.

foreach($sec_ids as $key=>$val)
{
	if (array_key_exists($key, $eud_ids) and !empty($key)) 
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
	if (array_key_exists($key, $eud_ids)and !empty($key)) 
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
	if (array_key_exists($key, $nctids)and !empty($key)) 
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
echo '<div style="font-family: Helvetica;font-size:14px;padding-left:200px;" >';
echo '<br><b><span style="color:red;font-size:17px;">  &nbsp;  &nbsp;  &nbsp;  &nbsp;    &nbsp;    &nbsp;    &nbsp;   &nbsp;  &nbsp;  List of suggested trials</b><br></span>';



echo '<br><table>';
echo '<tr>';
echo '<td style="font-family: Helvetica;font-size:15; padding-left:5px;" ><b> &nbsp; S.No. &nbsp; </b></td>';
echo '<td style="font-family: Helvetica;font-size:15; padding-left:5px;" ><b> &nbsp; Larvol ID &nbsp; </b></td>';
echo '<td style="font-family: Helvetica;font-size:15; padding-left:5px;" ><b> &nbsp; Matched NCT/EudraCT No. &nbsp; </b></td>';
echo '<td style="font-family: Helvetica;font-size:15; padding-left:5px;" ><b> &nbsp; Action &nbsp; </b></td>';
echo '</tr>';
$i=1;
foreach($org_ids as $key=>$oid)
{
	echo '<tr><td style="font-family: Helvetica;  font-size:14;padding-left:5px;"> &nbsp;  &nbsp;  &nbsp;  '.$i++.' </td>';
	echo '<td style="font-family: Helvetica;  font-size:14;padding-left:5px;"> &nbsp;  &nbsp;  &nbsp;  '.$oid .' </td>';
	echo '<td style="font-family: Helvetica;  font-size:14;padding-left:5px;"> &nbsp;  &nbsp;  &nbsp;  '.$key .' </td>';	
	echo '<td style="font-family: Helvetica;  font-size:14;padding-left:5px;"> 
		  <form method="post" action="link_trials.php?lid='.$oid.'">
		  <input type="submit" value="Link"></form></td>';
	echo '</tr>';
}
echo '</table>';
echo '</div>';
?>