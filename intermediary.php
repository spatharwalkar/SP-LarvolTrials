<?php
header('P3P: CP="CAO PSA OUR"');
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Search Results</title>
<link href="css/intermediary.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-18240582-3']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
  
  function applyfilter(value) {
  
	  if(value == 'inactive') {
	  
	  document.getElementById('filteropt').innerHTML = 'Mouseover<br/>'+
	  '<input type="checkbox" name="wh" value="1" />Withheld<br/>'+
	  '<input type="checkbox" name="afm" value="1" />Approved for marketing<br/>' +
	  '<input type="checkbox" name="tna" value="1" />Temporarily not available<br/>' + 
	  '<input type="checkbox" name="nla" value="1" />No Longer Available<br/>' + 
	  '<input type="checkbox" name="wd" value="1" />Withdrawn<br/>' + 
	  '<input type="checkbox" name="t" value="1" />Terminated<br/>' +
	  '<input type="checkbox" name="s" value="1" />Suspended<br/>' +
	  '<input type="checkbox" name="c" value="1" />Completed<br/>';
	  
	  } else {
	  
	  document.getElementById('filteropt').innerHTML = 'Mouseover<br/>'+
	  '<input type="checkbox" name="nyr" value="1" />Not yet recruiting<br/>' +
	  '<input type="checkbox" name="r" value="1" />Recruiting<br/>' + 
	  '<input type="checkbox" name="ebi" value="1" />Enrolling by invitation<br/>' + 
	  '<input type="checkbox" name="anr" value="1" />Active, not recruiting<br/>' + 
	  '<input type="checkbox" name="a" value="1" />Available<br/>' ;
	  
	  }
  }
</script>
</head>
<body>
<div style="text-align:center;"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" /></div><br />
<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');

if(!isset($_GET['params'])) die('cell not set');

$excel_params = unserialize(gzinflate(base64_decode($_GET['params'])));
$gentime = $excel_params['rundate'];
$name = $excel_params['name'];
$time_machine = $excel_params['time'];
$results = $excel_params['count'];
$rowlabel = $excel_params['rowlabel'];
$columnlabel = $excel_params['columnlabel'];

if($excel_params['params'] === NULL)
{
	$packedLeadingIDs = gzinflate(base64_decode($_GET['leading']));
	$leadingIDs = unpack('l*', $packedLeadingIDs);
	if($packedLeadingIDs === false) $leadingIDs = array();
	$sp = new SearchParam();
	$sp->field = 'larvol_id';
	$sp->action = 'search';
	$sp->value = implode(' OR ', $leadingIDs);
	$excel_params = array($sp);
}else{
	$excel_params = $excel_params['params'];
}

if($excel_params === false)
{
	$results = count($leadingIDs);
}

$page = 1;
if(isset($_GET['page'])) $page = mysql_real_escape_string($_GET['page']);
if(isset($_GET['jump']) && isset($_GET['jumpno'])) $page = mysql_real_escape_string($_GET['jumpno']);
if(!is_numeric($page)) die('non-numeric page');

if(isset($_GET['back'])) --$page;
if(isset($_GET['next'])) ++$page;

$nodata = array('action' => array(), 'searchval' => array());

$params = array();
if($_GET['sort'] == 'phase' || $_GET['sort'] == 'enrollment' || $_GET['sort'] == 'overall_status'
	|| $_GET['sort'] == 'phased' || $_GET['sort'] == 'enrollmentd' || $_GET['sort'] == 'overall_statusd')
{
	$sp = new SearchParam();
	$realfieldname = ((substr($_GET['sort'], -1) == 'd') ? (substr($_GET['sort'],0,strlen($_GET['sort'])-1)) : $_GET['sort']);
	$sp->field = '_' . getFieldId('NCT', $realfieldname);
	$sp->action = ((substr($_GET['sort'], -1) == 'd') ? 'descending' : 'ascending');
	$params[] = $sp;
}

$fid = array();
$fid['nct_id'] 				= '_' . getFieldId('NCT', 'nct_id');
$fid['overall_status'] 		= '_' . getFieldId('NCT', 'overall_status');
$fid['brief_title'] 		= '_' . getFieldId('NCT', 'brief_title');
$fid['condition'] 			= '_' . getFieldId('NCT', 'condition');
$fid['intervention_name'] 	= '_' . getFieldId('NCT', 'intervention_name');
$fid['phase'] 				= '_' . getFieldId('NCT', 'phase');
$fid['enrollment'] 			= '_' . getFieldId('NCT', 'enrollment');
$fid['enrollment_type'] 	= '_' . getFieldId('NCT', 'enrollment_type');
$fid['start_date'] 			= '_' . getFieldId('NCT', 'start_date');
$fid['primary_completion_date'] = '_' . getFieldId('NCT', 'primary_completion_date');
$fid['completion_date'] 	= '_' . getFieldId('NCT', 'completion_date');

$displist = array('Phase' => 'NCT/phase', 'Enrollment' => 'NCT/enrollment', 'Status' => 'NCT/overall_status', 
								'Primary Database' => 'NCT/nct_id', 'Study Dates' => 'NCT/start_date', 
								'Conditions' => 'NCT/condition', 'Interventions' => 'NCT/intervention_name');

$params = array_merge($params, $excel_params);
//$res = search($params,$fid,$page,$time_machine);

//differentiating betwen active and inactive category of records.
$arr = search($params,$fid,NULL,$time_machine);

$activecount 	= 0;
$totactivecount	= 0;
$activephase 	= array();
$activearray 	= array();
$actfilterarr 	= array('nyr'=>'Not yet recruiting', 'r'=>'Recruiting', 'ebi'=>'Enrolling by invitation', 
						'anr'=>'Active, not recruiting', 'a'=>'Available');

$inactivecount 	= 0;
$totinactivecount= 0;
$inactivephase 	= array();
$inactivearray 	= array();
$inactfilterarr = array('wh'=>'Withheld', 'afm'=>'Approved for marketing',
						'tna'=>'Temporarily not available', 'nla'=>'No Longer Available', 'wd'=>'Withdrawn', 't'=>'Terminated',
						's'=>'Suspended', 'c'=>'Completed');

$actflag = 0;$inactflag = 0;

if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) || isset($_GET['a'])) 
	$actflag = 1; // checking if any of the active filters are set

if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) || isset($_GET['wd']) 
	|| isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 	
	$inactflag = 1;	 // checking if any of the inactive filters are set
	
foreach($arr as $key=>$val) { 

	if($val['NCT/overall_status'] == 'Not yet recruiting' || $val['NCT/overall_status'] == 'Recruiting' || 
		$val['NCT/overall_status'] == 'Enrolling by invitation' || $val['NCT/overall_status'] == 'Active, not recruiting' || 
		$val['NCT/overall_status'] == 'Available') {
		
		if($actflag == 1) { //if any of the active filters are set then only those records are shown
		
			$vall = implode(",",array_keys($actfilterarr, $val['NCT/overall_status']));
			if(array_key_exists($vall, $_GET)) { 
			
				$activecount++;
				$activearray[] = $val;
			} 
		} else {
	
			$activecount++;
			$activearray[] = $val;
		}
		$activephase[] = $val['NCT/phase'];
		$totactivecount++;
		
	} else if($val['NCT/overall_status'] == 'Withheld' || $val['NCT/overall_status'] == 'Approved for marketing' || 
		$val['NCT/overall_status'] == 'Temporarily not available' || $val['NCT/overall_status'] == 'No Longer Available' || 
		$val['NCT/overall_status'] == 'Withdrawn' || $val['NCT/overall_status'] == 'Terminated' || 
		$val['NCT/overall_status'] == 'Suspended' || $val['NCT/overall_status'] == 'Completed') {
		
		if($inactflag == 1) { //if any of the inactive filters are set then only those records are shown
		
			$vall = implode(",",array_keys($inactfilterarr, $val['NCT/overall_status']));
			if(array_key_exists($vall, $_GET)) {
			
				$inactivecount++;
				$inactivearray[] = $val;
			} 
		} else {
			
			$inactivecount++;
			$inactivearray[] = $val;
		}
		$inactivephase[] = $val['NCT/phase'];
		$totinactivecount++;
	}
}

//checking which type of records(active/inactiv) needs to b shown
$var = (isset($_GET["active"])) ? ($_GET["active"].'array') : 'activearray' ; 

$count = count($$var);

$activestatus 		= '<input type="checkbox" name="nyr" value="1" ' 
					.($_GET['nyr'] ? ' checked="checked"' : ''). ' />Not yet recruiting<br/>'
					.'<input type="checkbox" name="r" value="1" ' 
					.($_GET['r'] ? ' checked="checked"' : ''). ' />Recruiting<br/>'
					.'<input type="checkbox" name="ebi" value="1" ' 
					.($_GET['ebi'] ? ' checked="checked"' : ''). ' />Enrolling by invitation<br/>'
					.'<input type="checkbox" name="anr" value="1"' 
					.($_GET['anr'] ? ' checked="checked"' : ''). '  />Active, not recruiting<br/>'
					.'<input type="checkbox" name="a" value="1" ' 
					.($_GET['a'] ? ' checked="checked"' : ''). ' />Available<br/>';
					
$inactivestatus 	= '<input type="checkbox" name="wh" value="1" ' 
					.($_GET['wh'] ? ' checked="checked"' : ''). ' />Withheld<br/>'
					.'<input type="checkbox" name="afm" value="1" ' 
					.($_GET['afm'] ? ' checked="checked"' : ''). ' />Approved for marketing<br/>'
					.'<input type="checkbox" name="tna" value="1" ' 
					.($_GET['tna'] ? ' checked="checked"' : ''). '/>Temporarily not available<br/>'
					.'<input type="checkbox" name="nla" value="1" ' 
					.($_GET['nla'] ? ' checked="checked"' : ''). '/>No Longer Available<br/>'
					.'<input type="checkbox" name="wd" value="1" ' 
					.($_GET['wd'] ? ' checked="checked"' : ''). '/>Withdrawn<br/>'
					.'<input type="checkbox" name="t" value="1" ' 
					.($_GET['t'] ? ' checked="checked"' : ''). '/>Terminated<br/>'
					.'<input type="checkbox" name="s" value="1" ' 
					.($_GET['s'] ? ' checked="checked"' : ''). '/>Suspended<br/>'
					.'<input type="checkbox" name="c" value="1" ' 
					.($_GET['c'] ? ' checked="checked"' : ''). '/>Completed<br/>';

//storeParams(array('params' => $params, 'time' => $time_machine));	//used for iframe result count
$pstart = ($page-1) * $db->set['results_per_page'] + 1;
$pend = $pstart + $db->set['results_per_page'] - 1;
$pages = ceil($count / $db->set['results_per_page']);
$last = ($page*$db->set['results_per_page']>$count) ? $count : $pend;

//echo('Results of ' . $name . '<br />run on ' . $gentime);

//displaying row label and column label
echo('<center><u>' . $rowlabel . ' trials in ' . $columnlabel . '</u></center><br/>Results of report run on ' . $gentime);

$pager='';
if($count > $db->set['results_per_page'])
{
	$sort = '';
	if($_GET['sort'] == 'phase') $sort = '&amp;sort=phase';
	if($_GET['sort'] == 'enrollment') $sort = '&amp;sort=enrollment';
	if($_GET['sort'] == 'overall_status') $sort = '&amp;sort=overall_status';
	if($_GET['sort'] == 'phased') $sort = '&amp;sort=phased';
	if($_GET['sort'] == 'enrollmentd') $sort = '&amp;sort=enrollmentd';
	if($_GET['sort'] == 'overall_statusd') $sort = '&amp;sort=overall_statusd';
	
	foreach($actfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
	foreach($inactfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }

	echo('<h3>Found ' . $results . ' studies - Displaying '.$count. (($_GET["active"]) ? (' '.$_GET["active"]) : ' active' ) 
	.' results</h3>');
	if($pstart > 1)
	{
		$pager .= '<a href="intermediary.php?params=' . rawurlencode($_GET['params'])
			. '&amp;page=' . ($page-1) . '&amp;leading=' . rawurlencode($_GET['leading'])
			. $sort . '">&lt;&lt; Previous Page (' . ($pstart-$db->set['results_per_page']) . '-' . ($pstart-1) 
			. ')</a>&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	$pager .= 'Studies Shown (' . $pstart . '-' . $pend . ') &nbsp; &nbsp; &nbsp; ';
	if($pend < $count)
	{
		$nextlast = ($last+$db->set['results_per_page']);
		if($nextlast > $count) $nextlast = $results;
		$pager .= '<a href="intermediary.php?params=' . rawurlencode($_GET['params'])
			. '&amp;page=' . ($page+1) . '&amp;leading=' . rawurlencode($_GET['leading'])
			. $sort . '">Next Page (' . ($pstart+$db->set['results_per_page']) . '-' . $nextlast . ') &gt;&gt;</a>';
	}
	echo($pager);
}else{
	echo('<h3>Displaying ' . $count . (($_GET["active"]) ? (' '.$_GET["active"]) : ' active' ) .' results</h3>');
}
echo('<br clear="all"/><br/>');
echo('<form method="get" action="intermediary.php">'
	.'<table border="0" width="50%" cellpadding="2" cellspacing="2">'
    .'<tr><td>List</td><td><input type="radio" name="active" checked="checked" value="active" 
	onchange="javascript: applyfilter(this.value);" /> <span style="color: #00B050;">'
	.$totactivecount.' Active Records </span>');
	if(!empty($activephase)) { 
		echo ((count($activephase) > 1) ? '(Highest Phase: '.max($activephase) : $activephase[0]) . ')';
	} 
echo ('<br/><input type="radio" name="active" value="inactive" ' .
	($_GET['active']=='inactive' ? ' checked="checked"' : '')
	. 'onchange="javascript: applyfilter(this.value);" /> <span style="color: #FF0000;">'
	.$totinactivecount.' InactiveRecords </span>');
	if(!empty($inactivephase)) { 
		echo ((count($inactivephase) > 1) ? '(Highest Phase: '.max($inactivephase) : $inactivephase[0]) . ')';
	}	
echo ('</td></tr><tr><td>Sort by</td><td><select name="sort">'
    .'<option'
	. ($_GET['sort']!='phase' && $_GET['sort']!='enrollment' && $_GET['sort']!='overall_status' ? ' selected="selected"' : '')
	. '>none</option>'
    .'<option value="phase"' . ($_GET['sort']=='phase' ? ' selected="selected"' : '') . '>phase</option>'
    .'<option value="enrollment"' . ($_GET['sort']=='enrollment' ? ' selected="selected"' : '') . '>enrollment</option>'
    .'<option value="overall_status"'.($_GET['sort']=='overall_status' ? ' selected="selected"' : '') . '>overall_status</option>'
    .'<option value="phased"' . ($_GET['sort']=='phased' ? ' selected="selected"' : '') . '>phase (Desc)</option>'
    .'<option value="enrollmentd"' . ($_GET['sort']=='enrollmentd' ? ' selected="selected"' : '') . '>enrollment (Desc)</option>'
    .'<option value="overall_statusd"' .($_GET['sort']=='overall_statusd' ? ' selected="selected"' : '') . '>overall_status (Desc)</option>'
    .'</select></td></tr>'
    .'<tr><td>Filter</td><td id="filteropt"  class="permSel">Mouseover<br/>'
    .(isset($_GET["active"]) ? ${$_GET["active"].'status'} : $activestatus)
    .'</td></tr><tr><td colspan="2"><input type="submit" value="Show"/></td></tr></table>'
	. '<input type="hidden" name="params" value="' . $_GET['params'] . '"/>'
	. '<input type="hidden" name="leading" value="' . $_GET['leading'] . '"/>'
	. '</form>');
echo('<table width="95%" border="0" cellpadding="2" cellspacing="20">');
$relrank = 0;

foreach($$var as $id => $study)
{ 
	$study['NCT/nct_id'] = padnct($study['NCT/nct_id']);
	for($woo=0;$woo<2;$woo++)
		unset_nulls($study);
	echo('<tr><th>' . ($pstart + $relrank++) . '.</th>'
	. '<td><table width="100%" border="0" class="manage" cellpadding="2" cellspacing="2">'
	. '<tr><th colspan="3"><a href="http://clinicaltrials.gov/ct2/show/' . $study['NCT/nct_id'] . '">' 
	. $study['NCT/brief_title']. '</a></th></tr>'
	. '<tr style="background-color:#D8D3E0;">');
	
	foreach($displist as $dname => $fqname)
	{ 
		$val = $study[$fqname];
		
		if($fqname == "NCT/enrollment"){ 
			echo '<td><span>' . $dname . ':</span> ' . $study["NCT/enrollment"] . ' ('.$study["NCT/enrollment_type"].') </td>';  
		} 
		else if($fqname == "NCT/nct_id") { 
			echo '<td colspan="2"><span>' .$dname . ':</span> Clinicaltrials.gov ('.$study["NCT/nct_id"].')</td>';
		} 
		else if($fqname == "NCT/start_date") {
		
			echo '<td><span>' . $dname . ':</span> ' . date('m/Y',strtotime($study["NCT/start_date"])) 
			. ($study["NCT/primary_completion_date"] != '' ? (' -- '. date('m/Y',strtotime($study["NCT/primary_completion_date"]))) : '' );
			
			if($study["NCT/start_date"] != '' && $study["NCT/primary_completion_date"] != '') { 
			
				echo ' <br/>(Est. completion: <b style="color:#FF0000">' .floor((strtotime($study["NCT/primary_completion_date"]) - 
				strtotime($study["NCT/start_date"])) / (30*60*60*24) ). 'm</b>)';
			}
			echo '</td></tr><tr style="background-color:#D8D3E0;">';
			
		} else if($fqname == "NCT/overall_status") {
			echo '<td><span>' . $dname . ':</span><b> ' . $study[$fqname] . ' </b></td>'
			.'</tr><tr style="background-color:#EDEAF0;">';
			
		} else if($fqname == "NCT/condition") {
			echo '<td colspan="2"><span>' . $dname . ':</span> ' . (is_array($val) ? implode(', ', $val) : $val) . '</td>';
		} else {
			echo '<td><span>' . $dname . ':</span> ' . (is_array($val) ? implode(', ', $val) : $val) . '</td>';
		}
	}
	echo '</tr></table></td></tr>';
	//krumo($study);
}
echo('</table>');
echo($pager);
echo('</body></html>');
?>

