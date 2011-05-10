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
require('header.php');


$redirectUrl = null;
echo '<div class="error">Under Development</div>';

//add new record on submitting the add new record button
if($_POST['add_new_record'])
{
	$query = "insert into clinical_study (institution_type,import_time,last_change) values('other',now(),now())";
	$res = mysql_query($query);
	if($res)
	{
		$larvol_id = mysql_insert_id();
		$redirectUrl = 'inspect.php?larvol_id='.$larvol_id;
		
	}
	else 
	{
		die('Insert new record failed.');
	}
	
}
?>
<form name="entry" id="entry" method="post" action="entry.php">
<div style="padding-left:20px">
<br/><input type="submit" name="add_new_record" value="Add New Record"/><br/><br/>
Studies with no source category entry

<?php
$sourceIds = null;
foreach($db->sources as $source)
{
	$sourceIds[] =  $source->getSourceId();
}
$sourceIds = implode(',',$sourceIds);
$query = "SELECT clinical_study.larvol_id FROM clinical_study WHERE larvol_id NOT IN(     SELECT data_cats_in_study.larvol_id FROM data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id WHERE `field` IN(".$sourceIds."))";
$res = mysql_query($query) or die('Bad SQL query getting field enumvals');
while($rw = mysql_fetch_assoc($res))
{
	echo '<br/><a alt="edit" href="inspect.php?larvol_id='.$rw['larvol_id'].'&inspect=Lookup"><img src="images/jedit.png"/>'.$rw['larvol_id'].'</a>';
}
?>
</div>
</form>
<?php 
if($redirectUrl)
{
?>
<script type="text/javascript">
document.location='<?php echo $redirectUrl?>';
</script>
<?php }?>
