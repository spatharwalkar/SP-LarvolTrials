<?php
require_once('db.php');
require_once('include.util.php');
//ini_set('error_reporting', E_ALL ^ E_NOTICE);
global $logger;
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
if(isset($_POST['source']) and isset($_POST['lid']))
{
	link_trial();
}
elseif(!isset($_POST['lid'])) die('<br> No larvol id pased.');
$lid=$_POST['lid'];
require_once('header.php');	
global $db;


// get trial details 
$query = 	"
			SELECT `source`,`is_sourceless` , `larvol_id`, `brief_title`
			FROM `data_manual` 
			WHERE `larvol_id` = $lid limit 1
			";
	$res1 		= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		die($log);
	}
	$sourceless=mysql_fetch_assoc($res1);
	if(isset($sourceless['source']) and trim($sourceless['source'])<>'') $hnt=",hint:'".$sourceless['source']."'";
	else $hnt='';

//auto suggest
?>
<script type="text/javascript">
function confirmlinking()
{ 
	if(confirm("Are you sure you want to link to this trial?"))
	{
		document.forms["link"].submit();
	}
}
$(document).ready(function(){
	var options1,c,d;

	jQuery(function(){
	  options1 = { serviceUrl:'autosuggest.php',params:{table:'data_trials',field:'source_id'<?php echo $hnt;?>} };
	  
	  if($('#linked_trial').length>=0)
	  c = $('#linked_trial').autocomplete(options1);
	  d = $('#name').autocomplete(options1);
	});
	$(".ajax").colorbox({
		onComplete:function(){ loadQueryData($('#searchdata').val());},
		onClosed:function(){ newSearch(); },
		inline:true, 
		width:"100%",
		height:"100%"
			});
	$("#inline_outer").hide();
});
</script>
<!-- main form -->
<div style= "padding-top:10px;padding-left:20px;">
<table  >
	<form name="link" id="link" method="post" action="link_trials.php">

	<tr>
		<td>
			Larvol Id
		</td>
		<td>
			<input type="text" name="lid" id="lid" value="<? echo $lid; ?>" readonly="readonly" size="150">
		</td>
	</tr>
	<tr>
		<td>
			Brief Title
		</td>
		<td>
			<input type="text" value="<? echo $sourceless['brief_title'] ?>"  size="150" readonly="readonly">
		</td>
	</tr>
	<tr>
		<td>
			Source
		</td>
		<td>
			<input type="text" name="source" value="<? echo $sourceless['source'] ?>"  size="150" readonly="readonly">
		</td>
	</tr>
	<tr>
	<td style="color:red">
			Link with source trial
		</td>
		<td>
			<input type="text" name="linked_trial" id="linked_trial"  size="150">
		</td>
	</tr>
	<tr>
		<td colspan="2">
			
		
			<input type="submit" name="submitit"
			value="Submit this form"
			onclick="confirmlinking();return false;" /> 
		</td>
	</tr>
	</form>
</table>
</div>
<?
function link_trial()
{
global $logger;
$lid=$_POST['lid'];
$sid=$_POST['linked_trial'];
//******* link 
	$query = "
			SELECT `source`,`is_sourceless`,`brief_summary` 
			FROM `data_manual` 
			WHERE `larvol_id` = '$lid' limit 1
			";
	$res1 		= mysql_query($query) ;
	
	
	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		return $log;
	}
	
	$hint=mysql_fetch_assoc($res1);
//	pr($hint);

	
	
	
	
	$query = "
			SELECT `source_id`,`larvol_id`,`brief_title` 
			FROM `data_trials` 
			WHERE `source_id` = '$sid' limit 1
			";
	$res1 		= mysql_query($query) ;
	
	
	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		return $log;
	}
	
	$hint=mysql_fetch_assoc($res1);
	//pr($hint);

	$query = '
			UPDATE data_manual 
			set larvol_id="'  . $hint['larvol_id'] . '", is_sourceless=NULL 
			WHERE `larvol_id` ="' .  $lid .'" limit 1
			';
	$res1 		= mysql_query($query) ;
	

$_POST['sourceless_only']='YES';
header("Location: edit_trials.php?sourceless_only=YES");
pr('<br><b><span style="color:green">Trial linked to SOURCE ID:'.$sid.', larvol id:'. $hint['larvol_id'] .'.</span></b>');
//require_once('edit_trials.php');
exit;
}	
?>

