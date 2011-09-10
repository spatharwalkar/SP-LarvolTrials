<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.import.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
ini_set('max_execution_time','360000');	//100 hours
//add new record on submitting the add new record button
if($_POST['add_new_record'])
{
	$query = "insert into clinical_study (institution_type,import_time,last_change) values('other',now(),now())";
	$res = mysql_query($query);
	if($res)
	{
		$larvol_id = mysql_insert_id();
		$redirectUrl = 'inspect.php?larvol_id='.$larvol_id;
		header('Location: '. urlPath() . $redirectUrl);
		
	}
	else 
	{
		die('Insert new record failed.');
	}
	
}
require('header.php');


echo '<div class="error">Under Development</div>';

?>
<form name="entry" id="entry" method="post" action="entry.php">
<div style="padding-left:20px">
<br/><input type="submit" name="add_new_record" value="Add New Record"/><br/><br/>
Studies with no source category entry

<?php
$sourceIds = null;
foreach($db->sources as $source)
{
	if($source->getSourceId())
	$sourceIds[] =  $source->getSourceId();
}

$sourceIds = implode(',',$sourceIds);
// old query. 
//echo $query = "SELECT clinical_study.larvol_id FROM clinical_study WHERE larvol_id NOT IN(SELECT DISTINCT data_cats_in_study.larvol_id FROM data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id WHERE `field` IN(".$sourceIds."))";
//die;
/*$query = 	"SELECT DISTINCT clinical_study.larvol_id AS larvol_id
			from clinical_study
			LEFT JOIN 
			(
			SELECT DISTINCT data_cats_in_study.larvol_id
			FROM data_values
			LEFT JOIN data_cats_in_study ON data_values.studycat = data_cats_in_study.id
			WHERE `field` = 1
			
			) AS res
			ON res.larvol_id=clinical_study.larvol_id
			WHERE
			res.larvol_id IS NULL";*/

$query1 = "SELECT DISTINCT data_cats_in_study.larvol_id FROM data_values , data_cats_in_study where data_values.studycat=data_cats_in_study.id and `data_values`.`field` IN(".$sourceIds.")";
$query2 = "select larvol_id from clinical_study";
$result1 = mysql_query($query1);
while($row1 = mysql_fetch_assoc($result1))
{
	$sourceLarvolIds[] = $row1['larvol_id'];
}
$result2 = mysql_query($query2);
$entryArr = array();
ob_implicit_flush(true);
while($row2 = mysql_fetch_assoc($result2))
{

	if(!in_array($row2['larvol_id'],$sourceLarvolIds))
	{
		echo '<br/><a alt="edit" target="_blank" href="inspect.php?larvol_id='.$row2['larvol_id'].'&inspect=Lookup"><img src="images/jedit.png"/>'.$row2['larvol_id'].'</a>';

	}
	
}
?>
</div>
</form>
