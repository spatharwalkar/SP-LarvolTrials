<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('include.search.php');
require('header.php');

listSearchProc();

echo(listSearchForm());
echo('<p>Select the checkbox next to a field to include that field in the results table. '
		. 'Use the controls to the right of a field to control which records are returned.</p>'
		. '<p>When searching on text fields (blue with free input) you can use '
		. '<a href="http://en.wikipedia.org/wiki/Perl_Compatible_Regular_Expressions">'
		. 'Perl-compatible regular expressions</a> which provide capabilities for pattern matching and boolean logic '
		. '(multiple values, etc). Regex use is detected by the use of forward-slash separators -- without them, the input '
		. 'string will be searched for directly (must match entire value exactly to return a result).'
		. '<br />Search in a range of values in a numeric or date field (these are green)'
		. ' with a capital TO (e.g. "50 TO 60").'
		. ' You can include multiple ranges or values joined by an OR.'
		. '<br /><b>Dates</b> are entered in the form YYYY-MM-DD. '
		. '<b>Ages</b> are always in years unless the field name suggests otherwise.'
		. '<br />Select multiple items in a listbox by Ctrl-clicking additional values.'
		. '<br />You can search for records that EXCLUDE your input rather than including it with the "Not" checkbox, except for '
		. 'text fields, which instead have a full negative search field.</p>'
		. '<p><a href="#" onclick="checkAll()">Select All</a> | <a href="#" onclick="uncheckAll()">Select None</a></p>');

echo('<form name="searchform" method="post" class="search" action="' . 'search.php' . '">');
//Duplicate submit button at beginning of form to make "search" the default when user presses enter
echo(' &nbsp; &nbsp; <input name="search" type="submit" value="Search"/>');
echo('<input name="simple" type="hidden" value="true" />');
echo(saveSearchForm());

echo('<fieldset><legend>Search parameters</legend>');
echo(openSection('NCT')
		. searchControl('_'.getFieldId('NCT','nct_id'),'nct id',true)
		. searchControl('_'.getFieldId('NCT','overall_status'),'overall status',true,3)
		. searchControl('_'.getFieldId('NCT','lastchanged_date'),'lastchanged date')
		. searchControl('_'.getFieldId('NCT','firstreceived_date'),'firstreceived date')
		. searchControl('_'.getFieldId('NCT','brief_title'),'brief title',true)
		. searchControl('_'.getFieldId('NCT','acronym'),'acronym')
		. searchControl('_'.getFieldId('NCT','official_title'),'official title')
		. searchControl('_'.getFieldId('NCT','condition'),'condition')
		. searchControl('_'.getFieldId('NCT','lead_sponsor'),'lead sponsor')
		. searchControl('_'.getFieldId('NCT','collaborator'),'collaborator')
		. searchControl('_'.getFieldId('NCT','source'),'source')
		. searchControl('_'.getFieldId('NCT','brief_summary'),'brief summary')
		. searchControl('_'.getFieldId('NCT','detailed_description'),'detailed description')
		. searchControl('_'.getFieldId('NCT','start_date'),'start date')
		. searchControl('_'.getFieldId('NCT','end_date'),'end date')
		. searchControl('_'.getFieldId('NCT','phase'),'phase')
		. searchControl('_'.getFieldId('NCT','study_type'),'study type')
		. searchControl('_'.getFieldId('NCT','enrollment'),'enrollment')
		. searchControl('_'.getFieldId('NCT','criteria'),'criteria')
		. searchControl('_'.getFieldId('NCT','healthy_volunteers'),'healthy volunteers')
		. searchControl('_'.getFieldId('NCT','intervention_name'), 'intervention name')
		. searchControl('_'.getFieldId('NCT','intervention_other_name'), 'intervention other name')
		. '<tr><td colspan="8">&nbsp;</td></tr>');
$otherFields = array('download_date','has_dmc','why_stopped','completion_date','completion_date_type','primary_completion_date','primary_completion_date_type','study_design','number_of_arms','number_of_groups','enrollment_type','biospec_retention','biospec_descr','study_pop','sampling_method','gender','minimum_age','maximum_age','verification_date','responsible_party_name_title','responsible_party_organization','org_study_id','nct_alias','secondary_id','oversight_authority','rank','arm_group_label','arm_group_type','arm_group_description','intervention_type','intervention_description','link_url','link_description','primary_outcome_measure','primary_outcome_timeframe','primary_outcome_safety_issue','secondary_outcome_measure','secondary_outcome_timeframe','secondary_outcome_safety_issue','reference_citation','reference_PMID','results_reference_citation','results_reference_PMID','investigator_name','investigator_degrees','investigator_role','overall_official_name','overall_official_degrees','overall_official_role','overall_official_affiliation');
foreach($otherFields as $fname)	echo(searchControl('_'.getFieldId('NCT',$fname),str_replace('_',' ',$fname)));
		
echo('</table></fieldset>');
echo(openSection('Global fields') . searchControl('institution_type', 'Institution Type (same as "Funded By")')
		. '</table></fieldset>');
echo('<br clear="all" />');
echo('<input name="search" type="submit" value="Search" />');
echo('</fieldset><input name="page" type="hidden" value="1" /></form>');
echo('<script type="text/javascript" src="checkall.js"></script>');

echo('</body></html>');

/*returns HTML form code for the named field
	$checked is just the default value and can be overridden if set to true
	If $checked is set to 1, the box will always be checked and disabled
*/
function searchControl($fieldname, $alias=false, $checked=false, $multi=false)
{
	global $db;
	
	if((isset($_GET['load']) || isset($_POST['searchname'])) && $checked !== 1)
		$checked = isset($_POST['display'][$fieldname]) ? true : false;	
	
	$enumvals = NULL;
	$CFid = NULL;
	$numericField = false;
	$regex = false;
	if(substr($fieldname,0,1) == '_')
	{
		$CFid = substr($fieldname,1);
		$query = 'SELECT type FROM data_fields WHERE id=' . $CFid . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting field type');
		$res = mysql_fetch_assoc($res);
		if($res === false) return;
		$CFtype = $res['type'];
		switch($CFtype)
		{
			case 'bool':
			$enumvals = array(0,1);
			break;
			
			case 'int':
			case 'datetime':
			case 'date':
			$numericField = true;
			break;
			
			case 'char':
			case 'varchar':
			case 'text':
			$regex = true;
			break;
			
			case 'enum':
			$query = 'SELECT id,value FROM data_enumvals WHERE field=' . $CFid;
			$res = mysql_query($query) or die('Bad SQL query getting field enumvals');
			while($ev = mysql_fetch_assoc($res))
			{
				$enumvals[$ev['id']] = $ev['value'];
			}
			break;
		}
	}else{
		switch($db->types[$fieldname])
		{
			case 'enum':
			$fd = explode('/',$fieldname);
			if(!isset($fd[1]))
			{
				$fd[1] = $fd[0];
				$fd[0] = 'clinical_study';
			}
			$enumvals = getEnumValues($fd[0],$fd[1]);
			break;
			
			case 'int':
			case 'datetime':
			case 'date':
			$numericField = true;
			break;
			
			case 'tinyint':
			$enumvals = array(0,1);
			break;
			
			case 'char':
			case 'varchar':
			case 'text':
			$regex = true;
			break;
		}
	}
	
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
	if(!isset($_POST['action'][$fieldname])) $_POST['action'][$fieldname] = '0';
	$acsAscending = (isset($acs['ascending']))?$acs['ascending']:'';
	$acsDescending = (isset($acs['descending']))?$acs['descending']:'';
	$acsRequire = (isset($acs['require']))?$acs['require']:'';
	$acsSearch = (isset($acs['search']))?$acs['search']:'';
	$acs = array($_POST['action'][$fieldname] => ' checked="checked"');
	$out = '<tr><th><input type="checkbox" class="dispCheck" name="display[' . $fieldname . ']" '
			. ($checked?'checked="checked" ':'') . ($checked === 1 ? 'disabled="disabled" ' : '') . '/></th>'
			. '<th' . ($numericField ? ' class="numeric"' : '') . '>' . $f . '</th>'
			. '<td><input type="radio" name="action[' . $fieldname . ']" value="0"' . $acs['0'] . ' />'
			. '<img src="images/nop.png" alt="No Action"/></td>'
			. '<td><input type="radio" name="action[' . $fieldname . ']" value="ascending"' . $acsAscending . ' />'
			. '<img src="images/asc.png" alt="Sort Ascending" title="Ascending"/></td>'
			. '<td><input type="radio" name="action[' . $fieldname . ']" value="descending"' . $acsDescending . ' />'
			. '<img src="images/des.png" alt="Sort Descending" title="Descending"/></td> '
			. '<td> &nbsp;<input type="radio" name="action[' . $fieldname . ']" value="require"' . $acsRequire . ' />'
			. '<img src="images/check.png" alt="Require"/></td> '
			. '<td class="psval"><input type="radio" name="action[' . $fieldname . ']" value="search"' . $acsSearch . ' />'
			. '<img src="images/search.png" alt="Search on:"/>:';
	if($enumvals === NULL)
	{
		$searchvalPost = (isset($_POST['searchval'][$fieldname]))?$_POST['searchval'][$fieldname]:null;
		$out .= '<input type="text" name="searchval[' . $fieldname . ']" value="'
					. htmlspecialchars($searchvalPost) . '"/>';
	}else{
		$searchvalPost = (isset($_POST['searchval'][$fieldname]))?$_POST['searchval'][$fieldname]:null;
		$size = ($multi === false) ? ((count($enumvals)>2)?3:false) : $multi;
		$out .= makeDropdown('searchval[' . $fieldname . ']', $enumvals, $size, $searchvalPost, $CFid!==NULL);
	}
	$negatePost = (isset($_POST['negate'][$fieldname]))?$_POST['negate'][$fieldname]:null;
	$out .= '</td><th class="not"><input type="' . ($regex ? 'text' : 'checkbox') . '" name="negate[' . $fieldname . ']" '
			. ($regex ? ('value="' . $negatePost . '"') : ($negatePost?'checked="checked"':''))
			. '/></th></tr>';
	return $out;
}


function openSection($name)
{
	return '<fieldset><legend>' . $name . '</legend>'
		. '<table><tr><th colspan="2">Info</th><th colspan="6">Search Actions</th></tr>'
		. '<tr><td>List</td><th width="150">Field</th>'
		. '<td>None</td><th colspan="2">Sort</th><th class="req">Require</th><th>Search on</th><td>Not</td></tr>';
}

//returns HTML for new-search-saver
function saveSearchForm()
{
	global $db;
	$searchnamePost = (isset($_POST['searchname']))?$_POST['searchname']:'';
	$out = '<fieldset><legend>Save search parameters</legend>Name: <input type="text" name="searchname" value="'
			. htmlspecialchars($searchnamePost) . '"/> ';
	if($db->user->userlevel != 'user')
	{
		$out .= '<input type="submit" value="Save (normal)"/> <input type="submit" name="saveglobal" value="Save (global)"/>';
	}else{
		$out .= '<input type="submit" value="Save"/>';
	}
	$out .= '</fieldset>';
	return $out;
}

//returns HTML for saved searches controller
function listSearchForm()
{
	global $db;
	global $rmode;
	global $report;
	global $row;
	global $col;
	
	$repq = '';
	if($rmode) $repq = '&report=' . $report . '&row=' . $row . '&col=' . $col;
	
	$out = '<form method="post" action="search_simple.php" class="lisep" style="float:right;z-index:100">'
			. '<fieldset><legend>Load saved search</legend>';
	$query = 'SELECT id,name,user FROM saved_searches WHERE user=' . $db->user->id . ' OR user IS NULL ORDER BY user';
	$res = mysql_query($query) or die('Bad SQL query getting saved search list');
	$num_rows = mysql_num_rows($res);
	if($num_rows>0)
	$out.='<ul>';
	while($ss = mysql_fetch_assoc($res))
	{
		$global = $ss['user'] === NULL;
		$adm = $db->user->userlevel != 'user';
		$out .= '<li' . ($global ? ' class="global"' : '') . '><a href="search_simple.php?load=' . $ss['id'] . $repq
				. '">' . htmlspecialchars($ss['name'])
				. ( (!$global || ($global && $adm))
								 ? ' <img src="images/edit.png" width="14" height="14" border="0" alt="edit"/>'
								 : '')
				. ' </a> &nbsp; '
				. ( (!$global || ($global && $adm))
						? '<input type="image" src="images/not.png" name="delsch[' . $ss['id'] . ']" alt="delete" title="delete"/>'
						: '')
				. '</li>';

	}
	if($num_rows>0)
	$out.='</ul>';	
	$out .= '</fieldset></form>';
	return $out;
}

//processes GET/POST for saved searches controller (view,delete)
function listSearchProc()
{
	global $db;
	if(isset($_GET['load']) && is_numeric($_GET['load']))
	{
		$ssid = mysql_real_escape_string($_GET['load']);
		$query = 'SELECT searchdata FROM saved_searches WHERE id=' . $ssid . ' AND (user=' . $db->user->id . ' or user IS NULL)'
				. ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting searchdata');
		$row = mysql_fetch_array($res);
		if($row === false) return;	//In this case, either the ID is invalid or it doesn't belong to the current user.
		$_POST = unserialize(base64_decode($row['searchdata']));
	}
	$rowGet = (isset($_GET['row']))?$_GET['row']:'';
	$colGet = (isset($_GET['col']))?$_GET['col']:'';
	if(is_numeric($rowGet) && is_numeric($rowGet) && is_numeric($colGet))
	{
		$ssid = mysql_real_escape_string($_GET['rload']);
		$rrow = mysql_real_escape_string($_GET['row']);
		$rcol = mysql_real_escape_string($_GET['col']);
		$query = 'SELECT searchdata FROM report_cells WHERE report=' . $ssid . ' AND `row`=' . $rrow . ' AND `column`=' . $rcol
					. ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting searchdata');
		$row = mysql_fetch_array($res);
		if($row === false) return;	//In this case, the report cell doesn't exist
		$_POST = unserialize(base64_decode($row['searchdata']));
	}
	if(isset($_POST['back2s']) && strlen($_POST['oldsearch']))
	{
		$_POST = unserialize(base64_decode($_POST['oldsearch']));
	}
	if(isset($_POST['delsch']) && is_array($_POST['delsch']))
	{
		foreach($_POST['delsch'] as $ssid => $coord)
		{
			$query = 'DELETE FROM saved_searches WHERE id=' . mysql_real_escape_string($ssid) . ' AND (user='
						. $db->user->id . ($db->user->userlevel != 'user' ? ' OR user IS NULL' : '') . ') LIMIT 1';
			mysql_query($query) or die('Bad SQL query deleting saved search');
		}
	}
}

?>