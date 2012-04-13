<?php
require_once('db.php');
require_once('include.util.php');
//ini_set('error_reporting', E_ALL ^ E_NOTICE);
if(isset($_POST['delsure']) and $_POST['delsure']=='Yes')	
{
	delete_trial();
	return ;
}
global $logger;
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
if(isset($_POST['source']) and isset($_POST['lid']))
{
//	pr($_POST['source']);
//	pr($_POST['lid']);
	
	link_trial();
}
elseif(!isset($_POST['lid'])) die('<br> No larvol id pased.');
$lid=$_POST['lid'];
global $db;

$query = "
		SELECT `larvol_id` FROM `data_nct` 
		WHERE `larvol_id` = $lid limit 1
		";
$res1 	= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		return($log);
	}
	$sourced=mysql_fetch_assoc($res1);
	if(isset($sourced['larvol_id'])) $sourced_trial='YES'; 
	else $sourced_trial='NO';


// get trial details 
if($sourced_trial=='NO')
{
	$query = 	"
				SELECT `source_id`,`is_sourceless` , `larvol_id`, `brief_title`
				FROM `data_manual` 
				WHERE `larvol_id` = $lid limit 1
				";
		$res1 		= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			echo $log;
			return($log);
		}
		$sourceless=mysql_fetch_assoc($res1);
		if(isset($sourceless['source_id']) and trim($sourceless['source_id'])<>'') $hnt=$sourceless['source_id'];
		else $hnt=$sourceless['source_id'];
	$data = array();
	if($hnt<>'')
	{
	$query = "select distinct source_id from data_trials  where ( source_id like '%$hnt%' ) and ( source_id <> '$hnt' ) order by source_id asc limit 100";
//	pr($query);
	$result =  mysql_query($query);

		while($row = mysql_fetch_assoc($result))
		{
			$data[] = $row['source_id'];
		}	

	}
}
else
{
	if($_POST['sourceless_only'] and $_POST['sourceless_only'] == 'YES')
	{
		header("Location: edit_trials.php?sourceless_only=YES&err_message=Cannot proceed: larvol_id " . $lid  . " is a sourced trial, which can only be linked to trials from another source.");
		exit;
	}
	else
	{
		header("Location: edit_trials.php?err_message=Cannot proceed: larvol_id " . $lid  . " is a sourced trial, which can only be linked to trials from another source.  Currently the system has trials from only one source.");
		exit;
	
	}

	$query = 	"
				SELECT `larvol_id`, `brief_title`
				FROM `data_nct` 
				WHERE `larvol_id` = $lid limit 1
				";
		$res1 		= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			echo $log;
			return($log);
		}
		$sourced=mysql_fetch_assoc($res1);
		if(isset($sourced['source_id']) and trim($sourced['source_id'])<>'') $hnt=$sourced['source_id'];
		//else $hnt=$sourced['source_id'];
		else $hnt='';
	$data = array();
	if($hnt<>'')
	{
	$query = "select distinct source_id from data_trials  where ( source_id like '%$hnt%' ) and ( source_id <> '$hnt' ) order by source_id asc limit 100";
//	pr($query);
	$result =  mysql_query($query);

		while($row = mysql_fetch_assoc($result))
		{
			$data[] = $row['source_id'];
		}	

	}
}
require_once('header.php');	

$cnt=count($data);	
	
//auto suggest
?>
<script type="text/javascript">
function confirmlinking()
{ 
	if(confirm("Are you sure you want to link these trials?"))
	{
		document.forms["link"].submit();
	}
}

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
			Source Id:
		</td>
		<td>
			<input type="text" name="source" value="<? echo $sourceless['source_id'] ?>"  size="150" readonly="readonly">
		</td>
	</tr>
	<tr>
	<td style="background-color: white;font-size:12px;color:darkred;valign:top">&nbsp;</td>
	<td style="background-color: white;font-size:12px;color:darkred;valign:top">
			Link with source trial
			<BR>
			<!--<input type="text" name="linked_trial" id="linked_trial"  size="150"> -->
			<select name="linkedtrial" id="ltrial" size="<?php echo $cnt; ?>" >
			
			<?php 
			foreach($data as $option) 
			{ ?>
				<option value="<?php echo $option ?>" >
					 <?php echo $option ?>
				</option>
			<?php 
			}?>
			</select>
		</td>
	</tr>
	<tr>
		<td style="background-color: white;font-size:12px;color:darkred;valign:top">&nbsp;</td>
	<td style="background-color: white;font-size:12px;color:darkred;valign:top">
			
		&nbsp;
			<input type="submit" name="submitit"
			value="Link trial(s)"
			onclick="confirmlinking();return false;" /> 
		</td>
	</tr>
	</form>
</table>
</div>
<?
// function to link sourceless trial to sourced trial.

function link_trial()
{
global $logger;
$lid=$_POST['lid'];
$sid=$_POST['linkedtrial'];
//pr('posts');
//pr($_POST);
//pr('postsend');
//******* pick the larvol id to be linked to 

	$query = "
			SELECT `source_id`,`larvol_id`,`brief_title` 
			FROM `data_trials` 
			WHERE `source_id` = '$sid' limit 1
			";

	$res1 	= mysql_query($query) ;
//	pr($query);
	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		return $log;
	}
	
	$hint=mysql_fetch_assoc($res1);
	
// update sourceless data (change larvol id)
	$query = '
			UPDATE data_manual 
			set larvol_id="'  . $hint['larvol_id'] . '", is_sourceless=NULL 
			WHERE `larvol_id` ="' .  $lid .'" limit 1
			';
	$res1 		= mysql_query($query) ;
//	pr($query);

	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		return $log;
	}
//delete the sourceless trial from data trial as it is no longer needed

	$query = '
			DELETE FROM `data_trials` 
			where larvol_id="' .  $lid .'" limit 1
			';
	$res1 		= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		return $log;
	}
	

$_POST['sourceless_only']='YES';
header("Location: edit_trials.php?sourceless_only=YES");
pr('<br><b><span style="color:green">Trial linked to SOURCE ID:'.$sid.', larvol id:'. $hint['larvol_id'] .'.</span></b>');
//require_once('edit_trials.php');
exit;
}	

function delete_trial()
{
global $logger;
/**/
$larvol_id=$_POST['lid2'];
	$query = 'SELECT `larvol_id`,is_sourceless FROM data_manual where `larvol_id`="' . $larvol_id . '"  LIMIT 1';

	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$res = mysql_fetch_assoc($res);
	$exists = $res !== false;
	if($exists)
	{
		$is_sourceless = $res['is_sourceless'];
	}
	
	if(isset($is_sourceless) and !is_null($is_sourceless) and $is_sourceless=="1") // sourceless, so delete the trial.
	{
	
		$query ='delete from data_manual where `larvol_id`="' . $larvol_id . '"  LIMIT 1'; 
		$res1 		= mysql_query($query) ;
		$ok=false;
		if($res1===false)
		{
			$log = 'Bad SQL query, could not delete trial from data manual. Query=' . $query;
			$logger->fatal($log);
			echo $log;
			return false;
		}
		else $ok=true;
		
		$query ='delete from data_trials where `larvol_id`="' . $larvol_id . '"  LIMIT 1'; 
		$res1 		= mysql_query($query) ;
		
		if($res1===false)
		{
			$log = 'Bad SQL query, could not delete trial from data trials. Query=' . $query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			return false;
		}
		else
		{
			if($ok===true)
			{
			//echo '<br><b><span style="color:red;font-size=+4;">Deleted the trial</span><b/>';
				header("Location: edit_trials.php?sourceless_only=YES&deleted_trial=". $larvol_id);
				exit;
			
			}
		}
		
		
	}
	else  // not sourceless, so dont delete.
	{
		
		header("Location: edit_trials.php?sourceless_only=YES&err_message=WARNING : " . $larvol_id  . " is a sourced trial, and it cannot be deleted.");
		exit;
		
	}
		
/**/


}
?>

