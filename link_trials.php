<?php
//connect to Sphinx
if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
require_once('db.php');
require_once('include.util.php');
//ini_set('error_reporting', E_ALL ^ E_NOTICE);
//allow only admins to continue
if($db->loggedIn() and ($db->user->userlevel=='admin'||$db->user->userlevel=='root'))
{
	//continue;
}
else
{
	die(' Plelase login as admin to use this feature.');
}
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

if(!isset($_REQUEST['lid'])) die('<br> No larvol id pased.');

$lid=$_REQUEST['lid'];

global $db;

$source='NCT';
$sourced_trial;
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
else
{
	//search in eudaract also
	$query = "
		SELECT `larvol_id`,`nct_id` FROM `data_eudract` 
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
	if(isset($sourced['larvol_id']))
	{
		$sourced_trial='YES';
		$source = 'EUDRACT';
		$nct_i = $sourced['nct_id'];
	}
	else $sourced_trial='NO';
}
if(isset($_POST['source']) and isset($_POST['lid']))
{
	//	pr($_POST['source']);
	//	pr($_POST['lid']);

	link_trial();
}

$brief_title;
$source_id;

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
	$brief_title = $sourceless['brief_title'];
	$source_id = $sourceless['source_id'];
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
	//	else
	//	{
	//		header("Location: edit_trials.php?err_message=Cannot proceed: larvol_id " . $lid  . " is a sourced trial, which can only be linked to trials from another source.  Currently the system has trials from only one source.");
	//		exit;
	//
	//	}

	$data = array();

	//Get Brief Title and Source Id for display

	$query = 	"
				SELECT `larvol_id`, `source_id`, `brief_title`, `org_study_id`,`secondary_id`
				FROM `data_trials` 
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
	$brief_title = $sourced['brief_title'];
	$source_id = $sourced['source_id'];
	$orgid=$sourced['org_study_id'];
	$secid=$sourced['secondary_id'];

	if($source == 'NCT')
	{
		//Search In Eudract on nctid field
		/************** EUDRACT SEARCH *********************/
		$query = 	"
				SELECT larvol_id, eudract_id
				FROM data_eudract
				WHERE ( nct_id = '" . $source_id  . "' or 
					    eudract_id = '" . $orgid  . "' or
						eudract_id = '" . $secid ."' )" ;
				
		$res1 		= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			echo $log;
			return($log);
		}
		while($row = mysql_fetch_assoc($res1))
		{
			$data[] = $row['eudract_id'];
		}
		/************** EUDRACT SEARCH *********************/

	}
	else if($source == 'EUDRACT')
	{

		//Search In NCT on org_study_id and secondary_id field
		/************** NCT SEARCH *********************/
		$query = 	"
				SELECT larvol_id, nct_id
				FROM data_nct
				WHERE ((org_study_id ='".  $source_id  . "') OR (nct_id ='".  unpadnct($nct_i)  . "') OR (secondary_id = '" . $source_id ."' ))";
		$res1 		= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			echo $log;
			return($log);
		}
		while($row = mysql_fetch_assoc($res1))
		{
			$data[] = $row['nct_id'];
		}
		/************** NCT SEARCH *********************/

	}

}

$cnt=count($data);
/*
    if($cnt == 0)
	{
		header("Location: edit_trials.php?err_message=Cannot proceed: larvol_id " . $lid  . " is not matched with any other trial.");
		exit;

	}
*/	
require_once('header.php');
$table='data_trials';
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
$(document).ready(function(){
	var options,a,b;

	jQuery(function(){
	  options = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'source_id',c_lid:<?php echo "$lid"?> } };
	  	  
	  if($('#linkedtrial1').length>=0)
	  a = $('#linkedtrial1').autocomplete(options);
	  b = $('#source_id').autocomplete(options);
	  
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
<div style="padding-top: 10px; padding-left: 20px;">
<table>
	<form name="link" id="link" method="post" action="link_trials.php">

	<tr>
		<td>Larvol Id</td>
		<td colspan="3"><input type="text" name="lid" id="lid" value="<?php echo $lid; ?>"
			readonly="readonly" size="150"></td>
	</tr>
	<tr>
		<td>Brief Title</td>
		<td colspan="3"><input type="text" value="<?php echo $brief_title; ?>" size="150"
			readonly="readonly"></td>
	</tr>
	<tr>
		<td>Source Id:</td>
		<td colspan="3"><input type="text" name="source" value="<?php echo $source_id ?>"
			size="150" readonly="readonly"></td>
	</tr>
	
	<tr>

	<td colspan="4" style="background-color: white; padding-left:65px;" ><br>Select a trial from the suggested trials list and click <b>LINK Trials</b> button.  If the list is empty, search for a trial by manually entering the source id.<br></td>
	</tr>
	
	<tr>
		<td style="background-color: white; " >&nbsp;</td>
	<td  valign="top"  style="background-color: white; padding-left:145px;"  >

		<input type="submit" name="submitit" value="LINK Trial(s)"  style=" font-size: 18px; color: red; valign: top"
			onclick="confirmlinking();return false;" /></td>	
	</tr>
	
	
	<tr>
		<td
			style="background-color: white; font-size: 12px; color: darkred; valign: top">&nbsp;</td>
		<td colspan="1" 
			style="background-color: white; font-size: 12px; color: darkred; valign: top">
		Suggested trials <BR>
		<!--<input type="text" name="linked_trial" id="linked_trial"  size="150"> -->
		<select name="linkedtrial" id="ltrial" size="<?php echo $cnt; ?>">

		<?php
		foreach($data as $option)
		{ ?>
			<option value="<?php echo $option ?>"><?php echo $option ?></option>
			<?php
		}?>
		</select></td>
			
		<td colspan="1" align="left" valign="top" 
			style="background-color: white; font-size: 12px; color: darkred; ">
			Search trials manually (enter source id)<BR>
			<!--<input type="text" name="linked_trial" id="linked_trial"  size="150"> -->
			<input type="text" name="linkedtrial1" id="linkedtrial1" value="" size="30" maxlength="40" />
		</td>
		<?php $space=str_repeat('&nbsp;',80);
		?>
		<td colspan="1" align="left" valign="top" style="background-color: white;" ><?php echo $space; ?></td>
		
	</tr>

	</form>
</table>
</div>
		<?php
		// function to link sourceless trial to sourced trial.

		function link_trial()
		{
			global $logger;
			$lid=$_POST['lid'];
			if(isset($_POST['linkedtrial'])) $sid=$_POST['linkedtrial'];
			else $sid=$_POST['linkedtrial1'];
			//pr('posts');
			//pr($_POST);
			//pr('postsend');
			//******* pick the larvol id to be linked to
			global $sourced_trial;
			global $source;
				
			if($sourced_trial == 'NO')
			{

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
				// update sphinx index
				if(isset($lid) and !empty($lid) and $lid>0)
				{
					global $sphinx;
					delete_sphinx_index($lid);
				}

				$_POST['sourceless_only']='YES';
				header("Location: edit_trials.php?sourceless_only=YES");
				pr('<br><b><span style="color:green">Trial linked to SOURCE ID:'.$sid.', larvol id:'. $hint['larvol_id'] .'.</span></b>');
				//require_once('edit_trials.php');
				exit;
			}
			
			else if($source == 'NCT')
			{
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

				// update data_nct data (change larvol id)
				$query = '
				UPDATE data_nct 
				set larvol_id="'  . $hint['larvol_id'] . '"
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
				
				$query = '
				UPDATE data_manual 
				set larvol_id="'  . $hint['larvol_id'] . '"
				WHERE `larvol_id` ="' .  $lid .'" limit 1
				';
				$res1 		= mysql_query($query) ;

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
				// update sphinx index
				if(isset($lid) and !empty($lid) and $lid>0)
				{
					global $sphinx;
					delete_sphinx_index($lid);
				}

				$_POST['sourceless_only']='NO';
				header("Location: edit_trials.php?sourceless_only=NO");
				pr('<br><b><span style="color:green">Trial linked to SOURCE ID:'.$sid.', larvol id:'. $hint['larvol_id'] .'.</span></b>');
				//require_once('edit_trials.php');
				exit;

			}
			else if($source == 'EUDRACT')
			{
				$sid=padnct($sid);
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

				// update data_eudract data (change larvol id)
				$query = '
				UPDATE data_eudract 
				set larvol_id="'  . $hint['larvol_id'] . '" 
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
				
				$query = '
				UPDATE data_manual 
				set larvol_id="'  . $hint['larvol_id'] . '" 
				WHERE `larvol_id` ="' .  $lid .'" limit 1
				';
				$res1 		= mysql_query($query) ;

				if($res1===false)
				{
					$log = 'Bad SQL query. Query=' . $query;
					$logger->fatal($log);
					echo $log;
					return $log;
				}
				
				/***********get all existing eudract data and merge it with nct data (only if no nct data exists for that field) */
		
				$query = 
					'select brief_title,acronym,official_title,lead_sponsor,collaborator,inclusion_criteria,exclusion_criteria,
					`condition`,source_id FROM `data_trials` 
					where larvol_id="' .  $lid .'" limit 1';
				$res1 		= mysql_query($query) ; // eudract data.
				$res1=mysql_fetch_assoc($res1);
				if($res1===false)
				{
					$log = 'Bad SQL query. Query=' . $query;
					$logger->fatal($log);
					$osid = array_search($lid, $allsourceids);
					pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
					return $log;
				}
				$lay_title =$res1['brief_title'];
				$abbr_title =$res1['acronym'];
				$full_title =$res1['official_title'];
				$sponsor_name =$res1['lead_sponsor'];
				$support_org_name =$res1['collaborator'];
				$inclusion_criteria =$res1['inclusion_criteria'];
				$exclusion_criteria =$res1['exclusion_criteria'];
				$condition =$res1['condition'];
				$eudract_id=$res1['source_id'];
				
				
				$query = 
					'select brief_title,`phase`,acronym,official_title,lead_sponsor,collaborator,inclusion_criteria,exclusion_criteria,
					`condition`,source_id FROM `data_trials` 
					where larvol_id="' . $hint['larvol_id'] .'" limit 1';
				$res1 		= mysql_query($query) ; // NCT data.
				$res1=mysql_fetch_assoc($res1);
				if($res1===false)
				{
					$log = 'Bad SQL query. Query=' . $query;
					$logger->fatal($log);
					$osid = array_search($lid, $allsourceids);
					pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
					return $log;
				}
				$Nlay_title =$res1['brief_title'];
				$Nabbr_title =$res1['acronym'];
				$Nfull_title =$res1['official_title'];
				$Nsponsor_name =$res1['lead_sponsor'];
				$Nsupport_org_name =$res1['collaborator'];
				$Ninclusion_criteria =$res1['inclusion_criteria'];
				$Nexclusion_criteria =$res1['exclusion_criteria'];
				$Ncondition =$res1['condition'];
				//pick NCT's phase
				$Nphase =$res1['phase'];
				$fldlst="";
				if(empty($Nlay_title) and !empty($lay_title)) $fldlst .= " , brief_title =  '". $lay_title."'" ;
				//
				//if(empty($Nabbr_title) and !empty($abbr_title)) $fldlst .= " , acronym =  '". $abbr_title."'" ;
				if(empty($Nfull_title) and !empty($full_title)) $fldlst .= " , official_title =  '". $full_title."'" ;
				if(empty($Nsponsor_name) and !empty($Nsponsor_name)) $fldlst .= " , lead_sponsor =  '". $sponsor_name."'" ;
				if(empty($Nsupport_org_name) and !empty($support_org_name)) $fldlst .= " , collaborator =  '". $support_org_name."'" ;
				if(empty($Ninclusion_criteria) and !empty($inclusion_criteria)) $fldlst .= " , inclusion_criteria =  '". $inclusion_criteria."'";
				if(empty($Nexclusion_criteria) and !empty($exclusion_criteria)) $fldlst .= " , exclusion_criteria =  '". $exclusion_criteria."'" ;
				if(!empty($Nphase)) 	$fldlst .= " , `phase` =  '". $Nphase."'" ;
				if(empty($Ncondition) and !empty($condition)) $fldlst .= " , `condition` =  '". $condition."'" ;
				
				$update_q='update data_trials set source_id = CONCAT(source_id ,"`","'.$eudract_id .'") ' ;
				$update_q .=  $fldlst;
				$update_q .= ' where larvol_id="' . $hint['larvol_id'] .'" limit 1'; 
				$res1 		= mysql_query($update_q) ;
				if($res1===false)
				{
					$log = 'Bad SQL query. Query=' . $query;
					$logger->fatal($log);
				//	$osid = array_search($lid, $allsourceids);
					pr('<br><b><span style="color:red">'.$counter.'. COULD NOT LINK Larvol id/Source Id : ' .  $lid .'/'.$osid .' to SOURCE ID:'.$sid.' / larvol id:'. $source_trial['larvol_id'] .'.</span></b>');
					return $log;
				}
				
				
				/******************/	
				
				
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
					echo $log;
					return $log;
				}
				
				// update sphinx index
				if(isset($lid) and !empty($lid) and $lid>0)
				{
					global $sphinx;
					delete_sphinx_index($lid);
				}


				$_POST['sourceless_only']='NO';
				header("Location: edit_trials.php?sourceless_only=NO");
				pr('<br><b><span style="color:green">Trial linked to SOURCE ID:'.$sid.', larvol id:'. $hint['larvol_id'] .'.</span></b>');
				//require_once('edit_trials.php');
				exit;

			}			
		
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
						// update sphinx index
						if(isset($larvol_id) and !empty($larvol_id) and $larvol_id>0)
						{
							global $sphinx;
							delete_sphinx_index($larvol_id);
						}
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