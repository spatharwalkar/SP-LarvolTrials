<?php
header('P3P: CP="CAO PSA OUR"');
require_once('krumo/class.krumo.php');
require_once('db.php');
if(!$db->loggedIn())
{
	//header('Location: ' . urlPath() . 'index.php');
	require('index.php');
	exit;
}
require_once('include.search.php');
session_start();

if(!isset($_POST['search']))
{
//Check for report (heatmap) mode
$report = NULL;
$row = NULL;
$col = NULL;
$rmode = false;
if(isset($_GET['report']))
{
	$reportc = htmlspecialchars($_GET['report']);
	$rowc = htmlspecialchars($_GET['row']);
	$colc = htmlspecialchars($_GET['col']);
	if(is_numeric($reportc))
	{
		$report = $reportc;
		$rmode = true;
	}
	if(is_numeric($rowc)) $row = $rowc;
	if(is_numeric($colc)) $col = $colc;
}
if(isset($_POST['report']))
{
	$reportc = htmlspecialchars($_POST['report']);
	$rowc = htmlspecialchars($_POST['row']);
	$colc = htmlspecialchars($_POST['col']);
	if(is_numeric($reportc))
	{
		$report = $reportc;
		$rmode = true;
	}
	if(is_numeric($rowc)) $row = $rowc;
	if(is_numeric($colc)) $col = $colc;
}
if($_GET['dontedit']) $rmode=false;

//check for competitor mode
$competitor = NULL;
$cmode = false;
if(isset($_GET['competitor']))
{
	$competitorc = htmlspecialchars($_GET['competitor']);
	$rowc = htmlspecialchars($_GET['row']);
	$colc = htmlspecialchars($_GET['col']);
	if(is_numeric($competitorc))
	{
		$competitor = $competitorc;
		$cmode = true;
	}
	if(is_numeric($rowc)) $row = $rowc;
	if(is_numeric($colc)) $col = $colc;
}
if(isset($_POST['competitor']))
{
	$competitorc = htmlspecialchars($_POST['competitor']);
	$rowc = htmlspecialchars($_POST['row']);
	$colc = htmlspecialchars($_POST['col']);
	if(is_numeric($competitorc))
	{
		$competitor = $competitorc;
		$cmode = true;
	}
	if(is_numeric($rowc)) $row = $rowc;
	if(is_numeric($colc)) $col = $colc;
}
if($_GET['dontedit']) $cmode=false;


//check for update scan mode
$umode = false;
$urep = NULL;
if(isset($_GET['urep']))
{
	$urepc = mysql_real_escape_string(htmlspecialchars($_GET['urep']));
	if(is_numeric($urepc))
	{
		$urep = $urepc;
		$umode = true;
	}
}
if(isset($_POST['urep']))
{
	$urepc = mysql_real_escape_string(htmlspecialchars($_POST['urep']));
	if(is_numeric($urepc))
	{
		$urep = $urepc;
		$umode = true;
	}
}

saveSearchPost();
require('header.php');
if($rmode) echo('<h2>This search will be stored in report ' . $report . ($row!==NULL ? (' row '.$row) : '')
					. ($col!==NULL ? (' column '.$col) : '') . '.</h2>');
if($cmode) echo('<h2>This search will be stored in CompetitorDashboard ' . $competitor . ($row!==NULL ? (' row '.$row) : '')
					. ($col!==NULL ? (' column '.$col) : '') . '.</h2>');
if($umode) echo('<h2>This search will be attached to UpdateReport ' . $urep . '.</h2>');
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

echo('<form name="searchform" method="post" class="search" action="'
	 	. ($rmode?'report_heatmap.php':($umode?'report_update.php':($cmode?'report_competitor.php':'search.php'))) . '">');
//Duplicate submit button at beginning of form to make "search" the default when user presses enter
echo(' &nbsp; &nbsp; <input name="search" type="submit" value="Search"/>');
if($rmode)
{
	echo('<input type="hidden" name="id" value="' . $report . '" />'
			. ($row!==NULL ? ('<input type="hidden" name="row" value="' . $row . '" />') : '')
			. ($col!==NULL ? ('<input type="hidden" name="col" value="' . $col . '" />') : '') );
}else if($cmode){
	echo('<input type="hidden" name="id" value="' . $competitor . '" />'
			. ($row!==NULL ? ('<input type="hidden" name="row" value="' . $row . '" />') : '')
			. ($col!==NULL ? ('<input type="hidden" name="col" value="' . $col . '" />') : '') );
}else if($umode){
	echo('<input type="hidden" name="urep" value="' . $urep . '" />');
}else{
	echo(saveSearchForm());
}

//alexvp search must remember  GET
$get=array();
foreach($_GET as $k=>$v)$get[]="$k=".urlencode($v);
$get=join("&",$get);
echo('<input type="hidden" name="getVars" value="' . $get . '" />');
//alexvp end

echo('<br clear="all" />');
echo('<fieldset><legend>Enter search parameters</legend>');
$tm_oldval = isset($_GET['time_machine']) ? $_GET['time_machine'] : $_POST['time_machine'];
$over_oldval = isset($_GET['override']) ? $_GET['override'] : $_POST['override'];
echo('<fieldset style="float:left;width:25em;"><legend>Time machine</legend>'
	. 'Enter a date (time optional) to search on; leave blank for current data (fastest)<br />'
	. '<input type="text" name="time_machine" value="' . $tm_oldval . '"/></fieldset>');
echo('<fieldset style="float:left;width:35em;"><legend>NCTid Override</legend>'
	. 'Enter a comma-delimited list of NCTids of records that must be returned by this search regardless of criteria<br />'
	. '<input type="text" name="override" value="' . $over_oldval . '" /></fieldset>' . '<br clear="all"/>');
echo(multiField('varchar+text') /*. multiField('int')*/);
echo(openSection('Global fields')
		. searchControl('larvol_id',false,1)
		. searchControl('import_time')
		. searchControl('institution_type', 'Institution Type (same as "Funded By" in ct.gov)')
		. '</table>'
		. '</fieldset>');
echo(CFCSearchControls());
echo('<br clear="all" />');
echo('<input name="search" type="submit" value="Search" />');
echo('</fieldset><input name="page" type="hidden" value="1" /></form>');
echo('<script type="text/javascript" src="checkall.js"></script>');

}else{
    
	require('header.php');
    validateInputPCRE($_POST);//alexvp added 

	$page = 0;
	if(isset($_POST['page']))
	{
		$page = $_POST['page'];
		if(isset($_POST['back'])) $page--;
		if(isset($_POST['next'])) $page++;
		if(isset($_POST['jump'])) $page = $_POST['jumpno'];
	}
	if($page < 1) $page = 1;
	if(isset($_POST['oldsearch']))
	{
		$_POST = unserialize(base64_decode($_POST['oldsearch']));
	}
	//array_walk_recursive($_POST,ref_mysql_escape);	//breaks regex by escaping backslashes
	$params = prepareParams($_POST);
	$list = array();
	if(is_array($_POST['display']))
	{
		foreach($_POST['display'] as $field => $ok)
		{
			if(!array_key_exists($field,$db->types)) continue;
			if($ok) $list[] = $field;
		}
	}

	$time_machine = strlen($_POST['time_machine']) ? strtotime($_POST['time_machine']) : NULL;
	$override = $_POST['override'];
	$override_arr = explode(',', $override);
	if($override_arr === false)
	{
		$override_arr = array();
	}else{
		foreach($override_arr as $key => $value)
		{
			$value = nctidToLarvolid($value);
			if($value === false)
			{
				unset($override_arr[$key]);
			}else{
				$override_arr[$key] = $value;
			}
		}
	}
	storeParams(array('params' => $params, 'time' => $time_machine, 'override' => $override_arr));
	$res = search($params,$list,$page,$time_machine,$override_arr);
	if($res === false)
		die('<br />Search failed. Tell development how you enountered this error, including the above message, if any. '
				. 'Go back to continue.');
	for($woo=0;$woo<2;$woo++) unset_nulls($res);
	$pstart = ($page-1) * $db->set['results_per_page'] + 1;
	$pend = $pstart + $db->set['results_per_page'] - 1;
	//$results = search($params,NULL,NULL,$time_machine);
	//if($results > $db->set['results_per_page'])
	//{
		echo('<form name="pager" method="post" action="search.php"><fieldset>'
	//		 	. '<legend>Page ' . $page . ' of ' . ceil($results / $db->set['results_per_page'])
			 	. '<legend>Page ' . $page . ' '
	//			. ': records ' . $pstart . '-' . (($page*$db->set['results_per_page']>$results)?$results:$pend) . ' of ' . $results
				. ': records '.$pstart.'-'.$pend.' of <iframe src="sessionSearchCount.php"></iframe>'
				. '</legend>'
				. '<input type="submit" name="jump" value="Jump" style="width:0;height:0;border:0;padding:0;margin:0;"/> '
				. '<input name="oldsearch" type="hidden" value="' . base64_encode(serialize($_POST)) . '" />'
				. '<input name="page" type="hidden" value="' . $page . '" /><input name="search" type="hidden" value="1" />'
				. ($pstart > 1 ? '<input type="submit" name="back" value="&lt; Back" />' : '')
				. ' <input type="text" name="jumpno" value="' . $page . '" size="6" />'
				. '<input type="submit" name="jump" value="Jump" /> '
				//. ($pend < $results ? '<input type="submit" name="next" value="Next &gt;" />' : '')
				. '<input type="submit" name="next" value="Next &gt;" />'
				. '</fieldset></form>');
	//}else{
	//	echo('<p>Displaying all ' . $results . ' results</p>');
	//}
	
	echo('<form name="getexcelform" method="post" action="excel.php">'
			//. '<input name="searchresults" type="hidden" value="' . base64_encode(serialize($res)) . '" />'
			. '<input name="params" type="hidden" value="' . base64_encode(serialize($params)) . '" />'
			. '<input name="list" type="hidden" value="' . base64_encode(serialize($list)) . '" />'
			. '<input name="time" type="hidden" value="' . base64_encode($time_machine) . '" />'
			. '<input name="override" type="hidden" value="' . base64_encode($override) . '" />'
			. '<input type="image" name="getexcel" src="images/excel.png" /></form>');
	echo('<form method="post" action="xml.php">'
			//. '<input name="searchresults" type="hidden" value="' . base64_encode(serialize($res)) . '" />'
			. '<input name="params" type="hidden" value="' . base64_encode(serialize($params)) . '" />'
			. '<input name="list" type="hidden" value="' . base64_encode(serialize($list)) . '" />'
			. '<input name="time" type="hidden" value="' . base64_encode($time_machine) . '" />'
			. '<input name="override" type="hidden" value="' . base64_encode($override) . '" />'
			. '<input type="image" src="images/xml.png" /></form>');
	echo('<form method="post" action="' . ($_POST['simple']?'search_simple.php':'search.php') . '">'
			. '<input name="oldsearch" type="hidden" value="' . base64_encode(serialize($_POST)) . '" />'
			. '<input type="submit" name="back2s" value="Back to search" /></form>');
	echo('<br clear="all" /><p>Follow the link above any result to see the full record.</p>');

	foreach($res as $id => $study)
	{
		$type = 'SrcNotFound';
		$source_id = '';
		$link = '';
		if(isset($study['NCT/nct_id']))
		{
			$type = 'NCT';
			$source_id = padnct($study['NCT/nct_id']);
			$link = 'http://clinicaltrials.gov/ct2/show/';
		}
		if(isset($study['PubMed/PMID']))
		{
			$type = 'PubMed';
			$source_id = $study['PubMed/PMID'];
			$link = 'http://www.ncbi.nlm.nih.gov/pubmed/';
		}
		for($woo=0;$woo<2;$woo++) unset_nulls($study);
		echo('<a href="inspect.php?larvol_id=' . $id . '">Study ' . $id
			. '</a><b>:</b> <a href="' . $link . $source_id . '">' . $source_id . ' [' . $type . ']</a><br />');
		krumo($study);
	}
}
echo('<script type="text/javascript" src="krumopen.js"></script>');

echo('</body></html>');

//returns HTML for the search control for the MultiField
function multiField($allowedType = 'varchar')
{
	global $db;
	global $rmode;
	$numericField = in_array($allowedType, array('int', 'date'));
	if(!is_array($_POST['multifields'])) $_POST['multifields'] = array($_POST['multifields']);
	$out .= '<fieldset><legend>Multi Field (' . $allowedType . ')</legend>'
			. '<table cellspacing="2" cellpadding="2">'
			. '<tr><th scope="col"' . ($numericField ? ' class="numeric"' : '') .'>Search Fields?</th>'
			. '<th scope="col">Search value</th></tr>'
			. '<tr><td>'
			. '<select name="multifields[' . $allowedType . '][]" size="4" multiple="multiple" style="max-width:inherit;">'		
			;//. '<option value=""> </option>';
	$typeForQuery = '"' . implode('","', explode('+', $allowedType)) . '"';
	foreach($db->types as $field => $type)
	{
		$disp = $field;
		if(substr($field,0,1) == '_')
		{
			$query = 'SELECT data_fields.name AS "field",data_categories.name AS "cat" FROM '
					. 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
					. 'WHERE data_fields.id=' . substr($field,1) . ' AND data_fields.`type` IN(' . $typeForQuery . ') LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting field name');
			$res = mysql_fetch_assoc($res);
			if($res === false) continue;	//Custom field not found.
			//in special modes, exclude fields not relevant to the mode
			if($rmode && ($res['cat'] != 'NCT') && in_array($res['cat'], $db->sourceCats)) continue;
			$disp = $res['cat'] . '/' . $res['field'];
		}
		else if(strpos($field,'/') === false)
		{
			continue;
			//$disp = 'clinical_study/' . $field;
		}
		$oldval = '';
		if(isset($_POST['multifields'][$allowedType]) && in_array($field,$_POST['multifields'][$allowedType]))
			$oldval = 'selected="selected"';
		$out .= '<option value="' . $field . '"' . $oldval . '>' . $disp . '</option>';
	}
	$out .= '</select>'
			. '</td><td><input type="text" name="multivalue[' . $allowedType . ']" value="'
			. htmlspecialchars($_POST['multivalue'][$allowedType])
			. '"/></td></tr></table>'
			. '</fieldset>';
	return $out;
}

//returns HTML for all custom field search controls
function CFCSearchControls()
{
	global $db;
	global $rmode;
	$out = '';
	mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
	$query = 'SELECT id,name FROM data_categories';
	$res = mysql_query($query) or die('Bad SQL query getting categories');
	$even = false;
	while($cat = mysql_fetch_assoc($res))
	{
		//in special modes, exclude fields not relevant to the mode
		if($rmode && in_array($cat['name'], $db->sourceCats) && ($cat['name'] != 'NCT')) continue;
		$out .= openSection($cat['name']);
		$query = 'SELECT id,name FROM data_fields WHERE category=' . $cat['id'];
		$res2 = mysql_query($query) or die('Bad SQL query getting fields for category');
		while($field = mysql_fetch_assoc($res2))
		{
			$is_id = in_array($cat['name'] . '/' . $field['name'], $db->sourceIdFields);
			$out .= searchControl('_'.$field['id'], str_replace('_',' ',$field['name']), $is_id);
		}
		$out .= '</table></fieldset>' . ($even ? '' : '<br clear="all" />');
		$even = !$even;
	}
	mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
	return $out;
}

/*returns HTML form code for the named field
	$checked is just the default value and can be overridden if set to true
	If $checked is set to 1, the box will always be checked and disabled
*/
function searchControl($fieldname, $alias=false, $checked=false, $multi=false)
{
	global $db;
	if((isset($_GET['load']) || isset($_POST['searchname'])) && $checked !== 1)
		$checked = $_POST['display'][$fieldname] ? true : false;	
	
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
	$acs = array($_POST['action'][$fieldname] => ' checked="checked"');
	$out = '<tr><th><input type="checkbox" class="dispCheck" name="display[' . $fieldname . ']" '
			. ($checked?'checked="checked" ':'') . ($checked === 1 ? 'disabled="disabled" ' : '') . '/></th>'
			. '<th' . ($numericField ? ' class="numeric"' : '') . '>' . $f . '</th>'
			. '<td><input type="radio" name="action[' . $fieldname . ']" value="0"' . $acs['0'] . ' />'
			. '<img src="images/nop.png" alt="No Action"/></td>'
			. '<td><input type="radio" name="action[' . $fieldname . ']" value="ascending"' . $acs['ascending'] . ' />'
			. '<img src="images/asc.png" alt="Sort Ascending" title="Ascending"/></td>'
			. '<td><input type="radio" name="action[' . $fieldname . ']" value="descending"' . $acs['descending'] . ' />'
			. '<img src="images/des.png" alt="Sort Descending" title="Descending"/></td> '
			. '<td> &nbsp;<input type="radio" name="action[' . $fieldname . ']" value="require"' . $acs['require'] . ' />'
			. '<img src="images/check.png" alt="Require"/></td> '
			. '<td class="psval"><input type="radio" name="action[' . $fieldname . ']" value="search"' . $acs['search'] . ' />'
			. '<img src="images/search.png" alt="Search on:"/>:';
	if($enumvals === NULL)
	{
		$out .= '<input type="text" name="searchval[' . $fieldname . ']" value="'
					. htmlspecialchars($_POST['searchval'][$fieldname]) . '"/>';
	}else{
		$size = ($multi === false) ? ((count($enumvals)>2)?3:false) : $multi;
		$out .= makeDropdown('searchval[' . $fieldname . ']', $enumvals, $size, $_POST['searchval'][$fieldname], $CFid!==NULL);
	}
	$out .= '</td><th class="not"><input type="' . ($regex ? 'text' : 'checkbox') . '" name="negate[' . $fieldname . ']" '
			. ($regex ? ('value="' . $_POST['negate'][$fieldname] . '"') : ($_POST['negate'][$fieldname]?'checked="checked"':''))
			. '/></th><th class="not"><input type="checkbox" name="weak[' . $fieldname . ']" '
			. ($_POST['weak'][$fieldname]?'checked="checked"':'') . '/></th></tr>';
	return $out;
}

function openSection($name)
{
	return '<fieldset><legend>' . $name . '</legend>'
		. '<table><tr><th colspan="2">Info</th><th colspan="7">Search Actions</th></tr>'
		. '<tr><td class="req">List</td><th width="145">Field</th>'
		. '<td class="req">None</td><th colspan="2">Sort</th><th class="req">Require</th><th>Search on</th>'
		. '<td>Negate</td><td class="req">Weak</td></tr>';
}

//returns HTML for new-search-saver
function saveSearchForm()
{
	global $db;
	$out = '<fieldset><legend>Save search parameters</legend>Name: <input type="text" name="searchname" value="'
			. htmlspecialchars($_POST['searchname']) . '"/> ';
	if($db->user->userlevel != 'user')
	{
		$out .= '<input type="submit" value="Save (normal)"/> <input type="submit" name="saveglobal" value="Save (global)"/>';
	}else{
		$out .= '<input type="submit" value="Save"/>';
	}
	$out .= '</fieldset>';
	return $out;
}

//processes postdata for saving new searches
function saveSearchPost()
{
	global $db;
	if(!isset($_POST['searchname']) || !strlen($_POST['searchname'])) return;
	$name = mysql_real_escape_string($_POST['searchname']);
	$user = isset($_POST['saveglobal']) ? NULL : $db->user->id;

	$searchdata = $_POST;
	//if saving a search while in the "store in a report" state, DON'T save that state in the saved search
	$badkeys = array('report','row','col','urep','competitor');
	foreach($badkeys as $key)
	{
		if(isset($searchdata[$key])) unset($searchdata[$key]);
	}
	$uclause = ($db->user->userlevel != 'user' && $user === NULL) ? 'user IS NULL' : ('user=' . $user);
	$query = 'DELETE FROM saved_searches WHERE ' . $uclause . ' AND name="' . $name . '" LIMIT 1';
	mysql_query('BEGIN') or die("Couldn't begin SQL query");
	mysql_query($query) or die('Bad SQL query clearing old data');
	$uclause = ($db->user->userlevel != 'user' && $user === NULL) ? 'NULL' : $user;
	$query = 'INSERT INTO saved_searches SET user=' . $uclause . ',name="' . $name . '",searchdata="'
				. base64_encode(serialize($searchdata)) . '"';
	mysql_query($query) or die('Bad SQL query adding saved search');
	$miid = mysql_insert_id();
	mysql_query('COMMIT') or die("Couldn't commit SQL query");
	
	//Escape route for when this is triggered from the Simple Search form
	if($_POST['simple'])
	{
		header('Location: ' . urlPath() . 'search_simple.php?load='.$miid);
		exit;
	}
}

//returns HTML for saved searches controller
function listSearchForm()
{
	global $db;
	global $rmode;
	global $cmode;
	global $report;
	global $competitor;
	global $row;
	global $col;
	global $umode;
	global $urep;
	
	$repq = '';
	if($rmode) $repq = '&report=' . $report . '&row=' . $row . '&col=' . $col;
	if($cmode) $repq = '&competitor=' . $competitor . '&row=' . $row . '&col=' . $col;
	if($umode) $repq = '&urep=' . $urep;
	
	$out = '<form method="post" action="search.php" class="lisep" style="float:right;z-index:100">'
			. '<fieldset><legend>Load saved search</legend><ul>';
	$query = 'SELECT id,name,user FROM saved_searches WHERE user=' . $db->user->id . ' OR user IS NULL ORDER BY user';
	$res = mysql_query($query) or die('Bad SQL query getting saved search list');
	while($ss = mysql_fetch_assoc($res))
	{
		$global = $ss['user'] === NULL;
		$adm = $db->user->userlevel != 'user';
		$out .= '<li' . ($global ? ' class="global"' : '') . '><a href="search.php?load='
				. $ss['id'] . $repq . '">' . htmlspecialchars($ss['name'])
				. ( (!$global || ($global && $adm))
								 ? ' <img src="images/edit.png" width="14" height="14" border="0" alt="edit"/>'
								 : '')
				. ' </a> &nbsp; '
				. ( (!$global || ($global && $adm))
						? '<input type="image" src="images/not.png" name="delsch[' . $ss['id'] . ']" alt="delete" title="delete"/>'
						: '')
				. '</li>';
	}
	$out .= '</ul></fieldset></form>';
	return $out;
}

//processes GET/POST for saved searches controller (view,delete)
function listSearchProc()
{
	global $db;

	//alexvp set oldsearch to TOP priority
	if(isset($_POST['back2s']) && strlen($_POST['oldsearch']))	//load search as previously entered after going Back To Search
	{
		$_POST = unserialize(base64_decode($_POST['oldsearch']));
		return; 
	}

	if(isset($_GET['load']) && is_numeric($_GET['load']))	//load search from Saved Search
	{
		$ssid = mysql_real_escape_string($_GET['load']);
		$query = 'SELECT searchdata FROM saved_searches WHERE id=' . $ssid . ' AND (user=' . $db->user->id . ' or user IS NULL)'
				. ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting searchdata');
		$row = mysql_fetch_array($res);
		if($row === false) return;	//In this case, either the ID is invalid or it doesn't belong to the current user.
		$_POST = unserialize(base64_decode($row['searchdata']));
	}
	if(is_numeric($_GET['rload']))	//load search from heatmap
	{
		$ssid = mysql_real_escape_string($_GET['rload']);
		$query = '';
		if(is_numeric($_GET['row']) && is_numeric($_GET['col']))	//cell
		{
			$rrow = mysql_real_escape_string($_GET['row']);
			$rcol = mysql_real_escape_string($_GET['col']);
			$query = 'SELECT searchdata FROM rpt_heatmap_cells WHERE report=' . $ssid
						. ' AND `row`=' . $rrow . ' AND `column`=' . $rcol . ' LIMIT 1';
		}else if(is_numeric($_GET['row']) || is_numeric($_GET['col'])){	//header
			$type = '';
			$num = '';
			if(is_numeric($_GET['row']))
			{
				$type = '"row"';
				$num = mysql_real_escape_string($_GET['row']);
			}else{
				$type = '"column"';
				$num = mysql_real_escape_string($_GET['col']);
			}
			$query = 'SELECT searchdata FROM rpt_heatmap_headers WHERE report=' . $ssid . ' AND num=' . $num . ' AND type=' . $type
					. ' LIMIT 1';
		}else{	//search that covers the whole report
			$query = 'SELECT searchdata FROM rpt_heatmap WHERE id=' . $ssid . ' LIMIT 1';
		}
		$res = mysql_query($query) or die('Bad SQL query getting searchdata');
		$row = mysql_fetch_array($res);
		if($row === false) return;	//In this case, the item doesn't exist
		$_POST = unserialize(base64_decode($row['searchdata']));
	}
	if(is_numeric($_GET['cload']))	//load search from competitordashboard
	{
		$ssid = mysql_real_escape_string($_GET['cload']);
		$query = '';
		if(is_numeric($_GET['row']) && is_numeric($_GET['col']))	//cell
		{
			$rrow = mysql_real_escape_string($_GET['row']);
			$rcol = mysql_real_escape_string($_GET['col']);
			$query = 'SELECT searchdata FROM rpt_competitor_cells WHERE report=' . $ssid
					. ' AND `row`=' . $rrow . ' AND `column`=' . $rcol . ' LIMIT 1';
		}else if(is_numeric($_GET['row']) || is_numeric($_GET['col'])){	//header
			$type = '';
			$num = '';
			if(is_numeric($_GET['row']))
			{
				$type = '"row"';
				$num = mysql_real_escape_string($_GET['row']);
			}else{
				$type = '"column"';
				$num = mysql_real_escape_string($_GET['col']);
			}
			$query = 'SELECT searchdata FROM rpt_competitor_headers WHERE report=' . $ssid . ' AND num=' . $num . ' AND type=' . $type
					. ' LIMIT 1';
		}else{	//search that covers the whole report
			$query = 'SELECT searchdata FROM rpt_competitor WHERE id=' . $ssid . ' LIMIT 1';
		}
		$res = mysql_query($query) or die('Bad SQL query getting searchdata');
		$row = mysql_fetch_array($res);
		if($row === false) return;	//In this case, the item doesn't exist
		$_POST = unserialize(base64_decode($row['searchdata']));
		
		//Display only results in what was found to be the highest phase when the Dash was run
		$_POST['action']['phase'] = 'search';
		$_POST['searchval']['phase'] = array(urldecode($_GET['phase']));
	}
	if(is_numeric($_GET['urep']) && !isset($_GET['load']))	//load search from update scan
	{
		$ssid = mysql_real_escape_string($_GET['urep']);
		$query = 'SELECT searchdata FROM rpt_update WHERE id=' . $ssid . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting searchdata');
		$row = mysql_fetch_array($res);
		if($row === false) return;	//In this case, the report cell doesn't exist
		if($row['searchdata'] === NULL) return; //In this case, this is an add rather than an edit, so load nothing
		$_POST = unserialize(base64_decode($row['searchdata']));
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