<?php
require_once('db.php');
if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require('header.php');

echo('<script type="text/javascript" src="delsure.js"></script>');
echo('<br /><div style="text-align:center;color:green;font-weight:bold;">Caution: all times are in local server time which is '
	 . date_default_timezone_get() . '</div>');

postRL();
postEd();
echo(reportList());
echo(editor());
echo('</body></html>');

//return html for item editor
function editor()
{
	global $db;
	if(!isset($_GET['id'])) return;
	$id = mysql_real_escape_string(htmlspecialchars($_GET['id']));
	if(!is_numeric($id)) return;
	$query = 'SELECT `name`,`fetch`,`runtimes`,`emails`,`format`, `calc_HM`, `upm_status` FROM schedule WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting item'.mysql_error()."<br />".$query);
	$rpt = mysql_fetch_assoc($res) or die('Item not found.');
	
	if($rpt['calc_HM']==1)
		$chkd=" checked='checked' ";
	else
		$chkd="";
	if($rpt['upm_status']==1)
		$chkd2=" checked='checked' ";
	else
		$chkd2="";
	
	$out = '<form action="schedule.php" method="post"><fieldset class="schedule"><legend>Edit schedule item ' . $id . '</legend>'
			. '<input type="hidden" name="id" value="' . $id . '" />'
			. '<input type="submit" name="reportsave" value="Save edits" /><br clear="all"/>'
			. '<label>Name: <input type="text" name="name" value="' . htmlspecialchars($rpt['name']) . '"/></label><br />'
			. '<label>Calculate Master HM cells?: <input type="checkbox" name="mhm" value="calc" ' . $chkd . ' /></label><br />'  // checkbox for calulating Master HM cells
			. '<label>Refresh UPM status?: <input type="checkbox" name="upm_status" value="upm_s" ' . $chkd2 . ' /></label><br />'  // checkbox for updating status of UPMs.
			. '<label>Update database (fetch)?: '
			//. '<input type="checkbox" name="fetch"'	. ($rpt['fetch'] ? 'checked="checked"' : '') . '/>'
			. makeDropdown('fetch',getEnumValues('schedule','fetch'),false,$rpt['fetch'])
			. '</label><br clear="all"/>';
	$reports = array();
	$query = 'SELECT id,`name`,`category` FROM rpt_heatmap'; // . ' WHERE user IS NULL OR user=' . $db->user->id;
	$res = mysql_query($query) or die('Bad SQL query getting heatmap names');
	$reportsArr = array();
	$categoryArr = array();
	while($row = mysql_fetch_assoc($res))
	{
		$reportsArr[] = $row;
		$categoryArr[$row['category']] = $row['category'];

	}
	sort($categoryArr);
	foreach($categoryArr as $category)
	{
		$i=0;
		foreach($reportsArr as $row)
		{
			if($i==0)
			$reports['-'.$category] = $category;
			if($row['category']==$category)
			$reports['h' . $row['id']] = '&nbsp;&nbsp;&nbsp;&nbsp;Heatmap ' . $row['id'] . ': ' . $row['name'];
		}		
	}
	$query = 'SELECT id,`name` FROM rpt_update'; // . ' WHERE user IS NULL OR user=' . $db->user->id;
	$res = mysql_query($query) or die('Bad SQL query getting update-scan names');
	while($row = mysql_fetch_assoc($res))
	{
		$reports['u' . $row['id']] = 'Update Scan ' . $row['id'] . ': ' . $row['name'];
	}
	
	//put product/areas schedule list prodcuts=1 areas=2 using bitmask.
	$LISync['LI_sync1'] = 'LI Product Sync';
	$LISync['LI_sync2'] = 'LI Areas Sync';
	//end
	
	$selectedreports = array();
	$query = 'SELECT heatmap FROM schedule_heatmaps WHERE schedule=' . $id;
	$res = mysql_query($query) or die('Bad SQL query getting associated heatmaps');
	while($row = mysql_fetch_assoc($res))
	{
		$selectedreports[] = 'h' . $row['heatmap'];
	}
	$query = 'SELECT updatescan FROM schedule_updatescans WHERE schedule=' . $id;
	$res = mysql_query($query) or die('Bad SQL query getting associated updatescans');
	while($row = mysql_fetch_assoc($res))
	{
		$selectedreports[] = 'u' . $row['updatescan'];
	}
	
	//li sync preselection
	$query = "select `LI_sync` from `schedule` where id=$id";
	$res = mysql_query($query) or die('Bad SQL query getting LI sync data.');
	while($row = mysql_fetch_assoc($res))
	{
		switch($row['LI_sync'])
		{
			case '1':
				$selectedLISync[] = 'LI_sync' . $row['LI_sync'];
				break;
			case '2':
				$selectedLISync[] = 'LI_sync' . $row['LI_sync'];
				break;
			case '3':
				$selectedLISync[] = 'LI_sync1';
				$selectedLISync[] = 'LI_sync2';
				break;
		}
	}
	//end
	$out .= '<label>Run these reports: ' . makeDropdown('reports',$reports,10,$selectedreports,true) . '</label><br clear="all"/>'
			.'<label>Run these LI sync: ' . makeDropdown('li_sync',$LISync,7,$selectedLISync,true) . '</label><br clear="all"/>'
			. '<label>Send output to these emails (comma-delimited): <input type="text" name="emails" value="'
			. htmlspecialchars($rpt['emails']) . '"/></label><br clear="all"/>';
	$out .= '<label>Format: '.makeDropdown('format', getEnumValues('schedule', 'format'), false, $rpt['format']).'</label><br clear="all"/>';
	$hours = array();
	$days = array();
	for($power = 0; $power < 24; ++$power)
	{
		$hour = pow(2, $power);
		if($rpt['runtimes'] & $hour) $hours[] = $hour;
	}
	for($power = 24; $power < 31; ++$power)
	{
		$day = pow(2, $power);
		if($rpt['runtimes'] & $day) $days[] = $day;
	}
	$allhours = array();
	for($hour = 0; $hour < 24; ++$hour) $allhours[pow(2, $hour)] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
	$alldays = array();
	$daynames = array(24 => 'Monday', 25 => 'Tuesday', 26 => 'Wednesday', 27 => 'Thursday',
					  28 => 'Friday', 29 => 'Saturday', 30 => 'Sunday');
	for($day = 24; $day < 31; ++$day) $alldays[pow(2, $day)] = $daynames[$day];
	$out .= '<label>On these hours: ' . makeDropdown('hours',$allhours,10,$hours,true) . '</label>';
	$out .= '<label>On these days: ' . makeDropdown('days',$alldays,7,$days,true) . '</label>';
	$out .= '</fieldset></form>';
	return $out;
}

//process POST for editor
function postEd()
{
	global $db;
	if(!isset($_POST['id'])) return;
	$id = mysql_real_escape_string($_POST['id']);
	if(!is_numeric($id)) return;
	
	$_GET['id'] = $id;	//This is so the editor will load the item we are about to (maybe?) save
	if(isset($_POST['reportsave']))
	{
		$runtimes = 0;
		if(is_array($_POST['hours'])) foreach($_POST['hours'] as $hour) $runtimes |= $hour;
		if(is_array($_POST['days']))  foreach($_POST['days'] as $day)   $runtimes |= $day;
		$name = mysql_real_escape_string($_POST['name']);
		$emails = mysql_real_escape_string($_POST['emails']);
		$fetch = mysql_real_escape_string($_POST['fetch']);
		$format = mysql_real_escape_string($_POST['format']);
		$query = 'UPDATE schedule SET `name`="' . $name . '",emails="' . $emails . '",`fetch`="' . $fetch . '",runtimes=' . $runtimes . ',format="' . $format . '"'
					. ' WHERE id=' . $id . ' LIMIT 1';
		mysql_query($query) or die('Bad SQL Query saving item');
		$query = 'DELETE FROM schedule_heatmaps WHERE schedule=' . $id;
		mysql_query($query) or die('Bad SQL query updating report associations2');
		$query = 'DELETE FROM schedule_updatescans WHERE schedule=' . $id;
		mysql_query($query) or die('Bad SQL query updating report associations3');
		if(is_array($_POST['reports']))
		{
			foreach($_POST['reports'] as $rep)
			{
				$continueFlag = 0;
				$type = substr($rep, 0, 1);
				$num = substr($rep, 1);
				$query = 'INSERT INTO ';
				switch($type)
				{
					case 'h':
					$query .= 'schedule_heatmaps SET heatmap=' . $num;
					break;
					case 'u':
					$query .= 'schedule_updatescans SET updatescan=' . $num;
					default:
					$continueFlag=1;
					break;
				}

				if($continueFlag == 1)
				continue;
				$query .= ',schedule=' . $id;
				mysql_query($query) or die('Bad SQL query saving report associations'.mysql_error().'<br />'.$query);
			}
		}
		if(is_array($_POST['li_sync']))
		{
			foreach($_POST['li_sync'] as $rep)
			{
				$LI_sync_type = explode('LI_sync',$rep);
				if(is_array($LI_sync_type) && count($LI_sync_type)==2)
				{
					$LI_sync_array[] = $LI_sync_type[1];
				}				
				
			}	

		}
		//save li sync variables
		if(is_array($LI_sync_array) && count($LI_sync_array)>0)
		{
			$query = "UPDATE `schedule` SET `LI_sync`=".implode('|',$LI_sync_array)." where id=".$id;
			mysql_query($query) or die('Bad SQL query saving product/areas sync data'.mysql_error().'<br />'.$query);
		}
		else
		{
			$query = "UPDATE `schedule` SET `LI_sync`=null where id=".$id;;
			mysql_query($query) or die('Bad SQL query saving product/areas sync data'.mysql_error().'<br />'.$query);
		}

		// save changes of master HM calculation 
		global $logger;
		//	echo '<pre>'; print_r($_GET);  print_r($_POST); echo '</pre>';

		if( isset($_POST['mhm']) and $_POST['mhm']=='calc' )  
		{
			$query = 'UPDATE `schedule` SET `calc_HM`="1" where `id`="'.$id . '" limit 1';
		}
		else
		{
			$query = 'UPDATE `schedule` SET `calc_HM`= NULL where `id`="'.$id . '" limit 1';		
		}
		if( isset($_POST['upm_status']) and $_POST['upm_status']=='upm_s' )  
		{
			$query = 'UPDATE `schedule` SET `upm_status`="1" where `id`="'.$id . '" limit 1';
		}
		else
		{
			$query = 'UPDATE `schedule` SET `upm_status`= NULL where `id`="'.$id . '" limit 1';		
		}
			
		if(!mysql_query($query))
		{
			$log='Error saving changes to schedule: ' . mysql_error() . '('. mysql_errno() .'), Query:' . $query;
			$logger->fatal($log);
			die($log);
		}
		
		
	}
}

//return html for the item list
function reportList()
{
	global $db;
	$out = '<div style="display:block;float:left;"><form method="post" action="schedule.php" class="lisep">'
			. '<input type="submit" name="makenew" value="Create new" style="float:none;" /></form><br clear="all"/>'
			. '<form name="reportlist" method="post" action="schedule.php" class="lisep" onsubmit="return delsure();">'
			. '<fieldset><legend>Select Schedule Item</legend><ul>';
	$query = 'SELECT id,`name` FROM schedule';
	$res = mysql_query($query) or die('Bad SQL query retrieving schedule item names');
	while($row = mysql_fetch_array($res))
	{
		$out .= '<li><a href="schedule.php?id=' . $row['id'] . '">'
				. htmlspecialchars(strlen($row['name'])>0?$row['name']:('(item '.$row['id'].')')) . '</a>';
		$out .= ' &nbsp; &nbsp; &nbsp; <input type="image" name="delrep[' . $row['id']
				. ']" src="images/not.png" title="Delete"/>';
		$out .= '</li>';
	}
	$out .= '</ul></fieldset></form></div>';
	return $out;
}

//processes POST for item list
function postRL()
{
	global $db;
	if(isset($_POST['makenew']))
	{
		mysql_query('INSERT INTO schedule SET `name`="",lastrun="' . date('Y-m-d H:i:s') . '"') or die('Bad SQL query creating item');
		$_GET['id'] = mysql_insert_id();
		$id = $_GET['id'];
	}
	if(isset($_POST['delrep']) && is_array($_POST['delrep']))
	{
		foreach($_POST['delrep'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			mysql_query('DELETE FROM schedule WHERE id=' . $id . ' LIMIT 1') or die('Bad SQL query deleting item');
		}
	}
}
?>