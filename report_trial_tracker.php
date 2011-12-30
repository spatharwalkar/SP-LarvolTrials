<?php
require_once('db.php');
require_once('report_common.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('include.search.php');
require('header.php');

echo('<script type="text/javascript" src="delsure.js"></script>');

postRL();
postEd();
echo(reportListCommon('rpt_trial_tracker'));
echo(editor());
echo('</body></html>');

//return html for report editor
function editor()
{
	global $db;
	if(!isset($_GET['id'])) return;
	$id = mysql_real_escape_string(htmlspecialchars($_GET['id']));
	if(!is_numeric($id)) return;
	$query = 'SELECT name,user,time FROM rpt_trial_tracker WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting report');
	$res = mysql_fetch_array($res) or die('Report not found.');
	$rptu = $res['user'];
	if($rptu !== NULL && $rptu != $db->user->id) return;	//prevent anyone from viewing others' private reports
	$name = $res['name'];
	$reportTime=$res['time'];
	$query = 'SELECT * FROM rpt_trial_tracker_trials WHERE report=' . $id;
	$res = mysql_query($query) or die('Bad SQL query getting report trials');
	$trials = array();
	while($trial = mysql_fetch_array($res))
	{
		$trials[$trial['num']] = $trial;
	}
	echo('<form name="getwordform" method="get" target="_blank" action="intermediary.php" class="onebutton" style="height:50px;">'
			. '<input type="hidden" name="id" value="' . $id . '" />'
			. '<input type="image" name="getword" src="images/word.png" title="Run"/>'
			. '<input type="image" name="getwebpage" src="images/weblink.png" title="Run" style="margin:8px;" /></form>'
			. '<br style="margin-top:55px;" clear="all"/>');
	$out = '<form action="report_trial_tracker.php" method="post"><fieldset><legend>Edit report ' . $id . '</legend>'
			. '<input type="hidden" name="id" value="' . $id . '" />'
			. '<label>Name: <input type="text" name="reportname" value="' . htmlspecialchars($name)
			. '"/></label>'
			. '<label>Report Time: <input type="text" name="reporttime" value="' . htmlspecialchars($reportTime)
			. '"/></label>';
	if($db->user->userlevel != 'user')
	{
		$out .= ' Ownership: '
			. '<label><input type="radio" name="own" value="global" '
			. ($rptu === NULL ? 'checked="checked"' : '')
			. '/>Global</label> '
			. '<label><input type="radio" name="own" value="mine" '
			. ($rptu !== NULL ? 'checked="checked"' : '')
			. '/>Mine</label>';
	}else{
		$out .= ' Ownership: ' . ($rptu === NULL ? 'Global' : 'Mine');
	}
	$out .= '<br clear="all"/>';
	if($db->user->userlevel != 'user' || $rptu !== NULL)
	{
		$out .= '<input type="submit" name="reportsave" value="Save edits" /> | '
			. '<input type="submit" name="addtrial" value="More trials" /> | '
			. '<input type="submit" name="deltrial" value="Less trials" /> | ';
	}
	$out .= '<input type="submit" name="reportcopy" value="Copy into new" /><br /><br />'
		. '<table><tr><th>NCTID</th>'
		. '<th>Tumor Type</th>'
		. '<th>Patient Populaton</th>'
		. '<th>Trials Details</th>'
		. '<th>Randomized Controlled Trial</th>'
		. '<th>Data Release</th></tr>';

	foreach($trials as $num => $trial)
	{
		$out .= '<tr><td><input type="text" name="trials[' . $num . '][nctid]" value="' . padnct($trial['nctid']) . '" /></td>'
			. '<td>' . makeDropdown('trials[' . $num . '][tumor_type]',getEnumValues('rpt_trial_tracker_trials','tumor_type'),false,$trial['tumor_type'],false) 
			. '</td>'
			. '<td><input type="text" name="trials[' . $num . '][patient_population]" value="' . $trial['patient_population'] . '" /></td>'
			. '<td><input type="text" name="trials[' . $num . '][trials_details]" value="' . $trial['trials_details'] . '" /></td>'
			. '<td><input type="text" name="trials[' . $num . '][randomized_controlled_trial]" value="' . $trial['randomized_controlled_trial'] . '" /></td>'
			. '<td><input type="text" name="trials[' . $num . '][data_release]" value="' . $trial['data_release'] . '" /></td></tr>';
	}	
	$out .= '</table></fieldset></form>';
	return $out;
}

//process POST for editor
function postEd()
{
	global $db;
	if(!isset($_POST['id'])) return;
	$id = mysql_real_escape_string($_POST['id']);
	if(!is_numeric($id)) return;

	$_GET['id'] = $id;	//This is so the editor will load the report we are about to (maybe?) save
	
	// block any user from modifying other peoples private reports and block non-admins from modifying global reports
	$query = 'SELECT user FROM rpt_trial_tracker WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting user for report id');
	$res = mysql_fetch_assoc($res);
	if($res === false) continue;
	$rptu = $res['user'];
	if($rptu !== NULL && $rptu != $db->user->id) return;

	// "Copy into new" is the exception for non-admins sending POSTdata about global reports
	if(isset($_POST['reportcopy']))
	{
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		$query = 'SELECT name FROM rpt_trial_tracker WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query getting old data');
		$res = mysql_fetch_array($res);
		if($res === false) return; //not found
		$oldname = mysql_real_escape_string($res['name']);
		$query = 'INSERT INTO rpt_trial_tracker SET name="Copy of ' . (strlen($oldname) ? $oldname : ('report '.$id))
			. '",user=' . $db->user->id;
		mysql_query($query) or die('Bad SQL Query saving report');
		$newid = mysql_insert_id();
		$query = 'SELECT * FROM rpt_trial_tracker_trials WHERE report=' . $id;
		$res = mysql_query($query) or die('Bad SQL query getting report info');
		$query = 'INSERT INTO rpt_trial_tracker_trials (report,num,nctid,tumor_type,patient_population,trials_details,randomized_controlled_trial,data_release) SELECT '
			. $newid . ',num,nctid,tumor_type,patient_population,trials_details,randomized_controlled_trial,data_release FROM rpt_trial_tracker_trials WHERE report=' . $id;
		mysql_query($query) or die('Bad SQL query copying data');
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
		$_GET['id'] = $newid;
	}
	
	if($rptu === NULL && $db->user->userlevel == 'user') return;

	$maxvar = 0;
	if(isset($_POST['addtrial']) || isset($_POST['deltrial']))
	{
		$query = 'SELECT MAX(num) AS "prevnum" FROM rpt_trial_tracker_trials WHERE report=' . $id
				. ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting max trial number');
		$res = mysql_fetch_array($res);
		if($res !== false) $maxvar = $res['prevnum'];
	}
	if(isset($_POST['addtrial']))
	{
		$query = 'INSERT INTO rpt_trial_tracker_trials SET report=' . $id . ',nctid="",'
				. 'num=' . ($maxvar + 1);
		mysql_query($query) or die('Bad SQL Query adding trial');
	}
	if(isset($_POST['deltrial']))
	{
		$query = 'DELETE FROM rpt_trial_tracker_trials WHERE report=' . $id . ' AND num=' . $maxvar
						. ' LIMIT 1';
		mysql_query($query) or die('Bad SQL Query removing trial');
	}
	if(isset($_POST['reportsave']))
	{
		$owner = $_POST['own'] == 'global' ? 'NULL' : $db->user->id;
		$query = 'UPDATE rpt_trial_tracker SET name="'. mysql_real_escape_string($_POST['reportname'])
			. '",user=' . $owner
			. ',output_template="' . mysql_real_escape_string($_POST['output_template'])
			. '",time="' . mysql_real_escape_string($_POST['reporttime'])
			. '",edited="' . mysql_real_escape_string($_POST['reportchangedfrom'])
			. '" WHERE id=' . $id . ' LIMIT 1';
		mysql_query($query) or die('Bad SQL Query saving report');
		foreach($_POST['trials'] as $num => $trial)
		{
			$query = 'UPDATE rpt_trial_tracker_trials '
				. 'SET nctid="' . unpadnct(($trial[nctid]))
				. '", tumor_type="' . mysql_real_escape_string($trial[tumor_type])
				. '", patient_population="' . mysql_real_escape_string($trial[patient_population])
				. '", trials_details="' . mysql_real_escape_string($trial[trials_details])
				. '", randomized_controlled_trial="' . mysql_real_escape_string($trial[randomized_controlled_trial])
				. '", data_release="' . mysql_real_escape_string($trial[data_release])
				. '" WHERE report=' . $id . ' AND num=' . $num . ' LIMIT 1';
			mysql_query($query) or die('Bad SQL Query saving trials');
		}
	}
}

//processes POST for report list
function postRL()
{
	global $db;
	if(isset($_POST['makenew']))
	{
		mysql_query('INSERT INTO rpt_trial_tracker SET name="",`time`="today",`edited`="today",user=' . $db->user->id) or die('Bad SQL query creating report');
		$_GET['id'] = mysql_insert_id();
		$id = $_GET['id'];

		$query = 'INSERT INTO rpt_trial_tracker_trials SET report=' . $id . ',nctid="",num=1';
		mysql_query($query) or die('Bad SQL Query adding trial');
		$query = 'INSERT INTO rpt_trial_tracker_trials SET report=' . $id . ',nctid="",num=2';
		mysql_query($query) or die('Bad SQL Query adding trial');
	}
	if(isset($_POST['delrep']) && is_array($_POST['delrep']))
	{
		foreach($_POST['delrep'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			$query = 'SELECT user FROM rpt_trial_tracker WHERE id=' . $id . ' LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting userid for report');
			$res = mysql_fetch_assoc($res);
			if($res === false) continue;
			$ru = $res['user'];
			if($ru == $db->user->id || ($db->user->userlevel != 'user' && $ru === NULL))
				mysql_query('DELETE FROM rpt_trial_tracker WHERE id=' . $id . ' LIMIT 1') or die('Bad SQL query deleting report');
		}
	}
	/*if(isset($_POST['getwebpage_x']))
	{
		$url = 'location:intermediary.php?id=' . $_POST['id'] . 'target="_blank"';
		header($url);
		exit;
	}
	if(isset($_POST['getword_x']))
	{
		echo '<pre>';print_r($_POST);
		exit;
	}*/
}
?>