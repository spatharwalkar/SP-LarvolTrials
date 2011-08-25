<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('run_updatereport.php');
if(isset($_GET['run']))
{
	$id = mysql_real_escape_string($_GET['run']);
	if(is_numeric($id))
	{
		runUpdateReport($id);
		exit;
	}
}
$_GET['header'] = '<style type="text/css" media="all">.items th{text-align:center;}.items input{float:none;}</style>';
require('header.php');

echo('<script type="text/javascript" src="delsure.js"></script>');

$activeUpdated = false;
$crit = array();
$lock = false;
$lockd = '';

postEd();
$err = postRL();
searchPost();
echo(reportList($err));

?><p>Click on a scan name to load that update scan. Once one is loaded, you can click on the Excel icon to run it and download the output.</p><?php

echo(urEditor());

echo('</body></html>');

//process postdata for editor
function postEd()
{
	global $crit;
	global $db;
	$id = (isset($_POST['id'])) ? mysql_real_escape_string($_POST['id']) : '';
	if(!is_numeric($id)) return;
	
	if(isset($_POST['save']))
	{
		$name = '"' . mysql_real_escape_string($_POST['name']) . '"';
		$footnotes = '"' . mysql_real_escape_string($_POST['footnotes']) . '"';
		$description = '"' . mysql_real_escape_string($_POST['description']) . '"';
		$getnew = strlen($_POST['getnew']) ? 1 : 0;
		$start = '';
		$end = '';
		if(strlen($_POST['start']) && (strtotime($_POST['start']) !== false))
			$start = '"'.mysql_real_escape_string($_POST['start']).'"';
		else
			$start = 'NULL';

		if(strlen($_POST['end']) && (strtotime($_POST['end']) !== false))
			$end = '"'.mysql_real_escape_string($_POST['end']).'"';
		else
			$end = 'NULL';

		$crit = array();
		$crit['watch'] = isset($_POST['watch'])?$_POST['watch']:'';
		$crit['from'] = isset($_POST['from'])?$_POST['from']:'';
		$crit['req_from'] = isset($_POST['req_from'])?$_POST['req_from']:'';
		$crit['to'] = isset($_POST['to'])?$_POST['to']:'';
		$crit['req_to'] = isset($_POST['req_to']) ? $_POST['req_to'] : '';
		unset_nulls($crit);
		$crit = base64_encode(serialize($crit));
		$query = 'UPDATE rpt_update SET name=' . $name . ',getnew=' . $getnew . ',start=' . $start . ',end=' . $end
				. ',criteria="' . $crit . '",footnotes=' . $footnotes . ',description=' . $description
				. ($db->user->userlevel != 'user' ? ',user=' . ($_POST['own'] == 'global' ? 'NULL' : $db->user->id) : '')
				. (strlen($_POST['deletesearch']) ? ',searchdata=NULL' : '')
				. ' WHERE id=' . $id
				. ' AND (user=' . $db->user->id . ($db->user->userlevel != 'user' ? ' OR user IS NULL' : '') . ')'
				. ' LIMIT 1';
		mysql_query($query) or die('Bad SQL query updating report');
		$_GET['id'] = $id;
	}else if(isset($_POST['urcopy'])){
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		$query = 'SELECT name,start,end,criteria,searchdata,getnew,footnotes,description FROM rpt_update WHERE id=' . $id
				. ' AND (user=' . $db->user->id . ' OR user IS NULL)'
				. ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting reportdata');
		$res = mysql_fetch_assoc($res);
		if($res === false) return;
		$res['name'] = 'Copy of ' . (strlen($res['name'])?$res['name']:'report '.$id);
		$res = array_map('nrescnq',$res);
		$res['user'] = $db->user->id;
		$query = 'INSERT INTO rpt_update (' . implode(',',array_keys($res)) . ') VALUES(' . implode(',',$res) . ')';
		mysql_query($query) or die('Bad SQL query copying updatereport');
		$_GET['id'] = mysql_insert_id();
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
	}
}

//return html for the updatereport editor
function urEditor()
{
	global $crit;
	global $db;
	global $lock;
	global $lockd;
	if(!isset($_GET['id'])) return;
	$id = mysql_real_escape_string(htmlspecialchars($_GET['id']));
	if(!is_numeric($id)) return;
	$query = 'SELECT name,start,end,criteria,searchdata,getnew,user,footnotes,description FROM rpt_update WHERE id='
			. $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting report');
	$res = mysql_fetch_assoc($res);
	if($res === false) return 'Report not found.';
	$rptu = $res['user'];
	if($rptu != $db->user->id && $rptu !== NULL) return;
	$lock = $db->user->userlevel == 'user' && $rptu === NULL;
	$lockd = $lock ? ' disabled="disabled"' : '';
	$name = strlen($res['name']) ? htmlspecialchars($res['name']) : '';
	$start = strlen($res['start']) ? htmlspecialchars($res['start']) : '';
	$end = strlen($res['end']) ? htmlspecialchars($res['end']) : '';
	$footnotes = htmlspecialchars($res['footnotes']);
	$description = htmlspecialchars($res['description']);
	$getnew = $res['getnew'] == 1;
	$crit = array();
	if(isset($res['criteria']) && $res['criteria'] !== NULL)
	{
		$crit = unserialize(base64_decode($res['criteria']));
	}
	$out = '<form method="post" action="report_update.php" class="search"><fieldset><legend>Edit '
			. (strlen($name)?$name:'report '.$id) . '</legend>'
			. '<a href="report_update.php?run=' . $id . '"><img src="images/excel.png" title="Run" alt="Run" style="border:0;"/></a>'
			. '<br /><br />'
			. ($lock ? '' : '<input type="submit" name="save" value="Save edits"/> ')
			. ' <input type="submit" name="urcopy" value="Copy into new"/>'
			. '<input type="hidden" name="id" value="' . $id . '"/><br /><br /><label>Name: <input type="text" name="name" value="'
			. $name . '"' . $lockd . '/></label>';
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
	$out .= '<br />Date range (blank=unbounded):'
			. '<input type="text" name="start" value="' . $start . '" class="date_input"' . $lockd . '/> to '
			. '<input type="text" name="end" value="' . $end . '" class="date_input"' . $lockd . '/><br />Search criteria: ';
	if(isset($res['searchdata']) && $res['searchdata'] !== NULL)
	{
		$out .= '[Search] <a href="search.php?'	. 'urep=' . $id . '">' . ($lock ? 'View' : 'Edit') . '</a>';
		if(!$lock)
		{
			$out .= ' - <label style="display:inline;">'
				. '<input type="checkbox" name="deletesearch" style="position:relative;top:3px;"/> delete</label>';
		}
	}else{
		$out .= '[Empty]';
		if(!$lock) $out .= ' <a href="search.php?urep=' . $id . '">Add</a>';
	}
	$out .= '<br clear="all"/><label style="display:inline;position:relative;top:3px;"><input type="checkbox" name="getnew" '
		. ($getnew?'checked="checked" ':'') . 'style="position:relative;top:1px;"' . $lockd . '/> Detect new records</label>'
		. '<br clear="all"/><br />'
		. '<fieldset><legend>Footnotes</legend><textarea name="footnotes" cols="45" rows="5">' . $footnotes . '</textarea></fieldset>'
		. '<fieldset><legend>Description</legend><textarea name="description" cols="45" rows="5">' . $description
		. '</textarea></fieldset><br clear="all"/>';
	$out .= '<p><a href="#" onclick="checkAll()">Select All</a> | <a href="#" onclick="uncheckAll()">Select None</a></p>'
		. CFCSearchControls()
		. '</fieldset></form>'
		. '<script type="text/javascript" src="checkall.js"></script>';
		return $out;
}

//returns HTML for all custom field search controls
function CFCSearchControls()
{
	$out = '';
	mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
	$query = 'SELECT id,name FROM data_categories';
	$res = mysql_query($query) or die('Bad SQL query getting categories');
	$even = false;
	while($cat = mysql_fetch_assoc($res))
	{
		$out .= openSection($cat['name']);
		$query = 'SELECT id,name FROM data_fields WHERE category=' . $cat['id'];
		$res2 = mysql_query($query) or die('Bad SQL query getting fields for category');
		while($field = mysql_fetch_assoc($res2))
		{
			$out .= searchControl($field['id'], str_replace('_',' ',$field['name']));
		}
		$out .= '</table></fieldset>' . ($even ? '' : '<br clear="all" />');
		$even = !$even;
	}
	mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
	return $out;
}

//process postdata incoming from search page
function searchPost()
{
	global $db;
	if(isset($_POST['urep']))
	{
		$id = mysql_real_escape_string($_POST['urep']);
		if(is_numeric($id))
		{
		    validateInputPCRE($_POST);//alexvp added 
			unset($_POST['urep']);
			unset($_POST['searchname']);
			$query = 'UPDATE rpt_update SET searchdata="' . base64_encode(serialize($_POST)) . '" WHERE id=' . $id
					. ' AND (user=' . $db->user->id . ($db->user->userlevel != 'user' ? ' OR user IS NULL' : '') . ')'
					. ' LIMIT 1';
			mysql_query($query) or die('Bad SQL query storing search in updatereport');
			$_GET['id'] = $id;
		}
	}
}

//returns HTML to begin a criteria section
function openSection($name)
{
	$out = '<fieldset><legend>' . $name . '</legend>'
		. '<table><tr><th colspan="2">Scan for changes?</th>'
		. '<th>Only changes FROM: ?</th><th>Only changes TO: ?</th></tr>';
	return $out;
}

function closeSection()
{
	return '</table></fieldset>';
}

/*returns HTML form code for the named field
	$checked is just the default value and can be overridden
*/
function searchControl($fieldname, $alias=false)
{
	global $db;
	global $crit;
	global $lock;
	global $lockd;
	$checked = (isset($crit['watch'][$fieldname]) && $crit['watch'][$fieldname]) ? true : false;	

	$f='';
	if($alias === false)
	{
		$f = explode('/',$fieldname);
		$f = end($f);
		$f = str_replace('_',' ',$f);
		$f = str_replace('-',': ',$f);
	}else{
		$f = $alias;
	}
	
	$out = '<tr><th><input type="checkbox" class="dispCheck" name="watch[' . $fieldname . ']" '
			. ($checked?'checked="checked" ':'') . $lockd . '/></th>'
			. '<th width="150">' . $f . '</th>'
			. '<td><input type="checkbox" name="req_from[' . $fieldname . ']" '
				. ((isset($crit['req_from'][$fieldname])&& $crit['req_from'][$fieldname])?'checked="checked" ':'') . $lockd . '/>'
				. '<input type="text" name="from[' . $fieldname . ']" value="'
				. htmlspecialchars(isset($crit['from'][$fieldname])?$crit['from'][$fieldname]:'') . '"' . $lockd . '/></td>'
			. '<td><input type="checkbox" name="req_to[' . $fieldname . ']" '
				. ((isset($crit['req_to'][$fieldname]) && $crit['req_to'][$fieldname])?'checked="checked" ':'') . $lockd . '/>'
				. '<input type="text" name="to[' . $fieldname . ']" value="'
				. htmlspecialchars(isset($crit['to'][$fieldname])?$crit['to'][$fieldname]:'') . '"' . $lockd . '/></td>'
			. '</tr>';
	return $out;
}

//return html for the report list
function reportList($disperr)
{
	global $db;
	global $activeUpdated;
	$out = '<div style="display:block;float:left;"><form method="post" action="report_update.php" class="lisep">'
			. '<input type="submit" name="makenew" value="Create new" style="float:none;" /></form><br clear="all"/>'
			. '<form name="reportlist" method="post" action="report_update.php" class="lisep">'
			. '<fieldset><legend>Select UpdateReport</legend>';
	mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
	$query = 'SELECT id,name,user FROM rpt_update WHERE user IS NULL or user=' . $db->user->id . ' ORDER BY user';
	$res = mysql_query($query) or die('Bad SQL query retrieving updatereport names');
	$out .= '<table width="100%" class="items"><tr><th>Load</th><th>Del</th></tr>';
	while($row = mysql_fetch_array($res))
	{
		$out .= '<tr><td><ul class="tablelist"><li class="' . ($row['user'] === NULL ? 'global' : '')
				. '"><a href="report_update.php?id=' . $row['id'] . '">'
				. htmlspecialchars(strlen($row['name'])>0?$row['name']:('(report '.$row['id'].')'))
				. '</a></li></ul></td><th>';
		if($row['user'] !== NULL || ($row['user'] == NULL && $db->user->userlevel != 'user'))
		{
			$out .= '<label class="lbldelc"><input type="checkbox" class="delrep" name="delrep[' . $row['id']
					. ']" title="Delete" /></label>';
		}
		$out .= '</th></tr>';
	}
	$out .= '<tr><th>&nbsp;</th><th><div class="tar"><input type="submit" value="Delete" title="Delete" onclick="return chkbox();"/></div></th></tr>';
	mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
	$out .= '</table><br />';
	if(strlen($disperr)) $out .= '<br clear="all"/><span class="error">' . $disperr . '</span>';
	if(strlen($activeUpdated)) $out .= '<br clear="all"/><span class="info">Selections updated!</span>';
	$out .= '</fieldset></form></div>';
	return $out;
}

/*processes POST for report list
	Return an error string
*/
function postRL()
{
	global $db;
	global $activeUpdated;
	mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
	if(isset($_POST['makenew']))
	{
		$query = 'INSERT INTO rpt_update SET name="",user=' . $db->user->id;
		mysql_query($query) or die('Bad SQL query creating updatereport');
		$_GET['id'] = mysql_insert_id();
		$id = $_GET['id'];
	}
	if(isset($_POST['delrep']) && is_array($_POST['delrep']))
	{
		foreach($_POST['delrep'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			$query = 'DELETE FROM rpt_update WHERE id=' . $id
					. ' AND (user=' . $db->user->id . ($db->user->userlevel != 'user' ? ' OR user IS NULL' : '') . ')'
					. ' LIMIT 1';
			mysql_query($query) or die('Bad SQL query deleting updatereport');
		}
	}
	if(isset($_POST['setactive']))
	{
		mysql_query('DELETE FROM rpt_update_recieve WHERE user=' . $db->user->id) or die('Bad SQL query clearing status');
		if(isset($_POST['active']) && is_array($_POST['active']))
		{
			foreach($_POST['active'] as $id)
			{
				$query = 'SELECT user FROM rpt_update WHERE id=' . $id . ' LIMIT 1';
				$res = mysql_query($query) or die('Bad SQL query checking ownership');
				$res = mysql_fetch_assoc($res);
				if($res === false) continue;	//report not found: ignore it
				//if report is someone else's: do not let user subscribe
				if($res['user'] !== NULL && $res['user'] != $db->user->id) continue;
				$query = 'INSERT INTO rpt_update_recieve SET user=' . $db->user->id . ',updatescan=' . $id;
				mysql_query($query) or die('Bad SQL query updating status');
			}
		}
		$activeUpdated = true;
	}
	mysql_query('COMMIT') or die("Couldn't commit SQL transaction");

	return '';
}

?>