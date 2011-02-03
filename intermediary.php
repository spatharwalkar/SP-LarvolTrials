<?php
header('P3P: CP="CAO PSA OUR"');
session_start();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
if($excel_params['params'] === NULL)
{
	if(!isset($_GET['leading'])) die('No search terms and no ID list');
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
$fid['nct_id'] = '_' . getFieldId('NCT', 'nct_id');
$fid['overall_status'] = '_' . getFieldId('NCT', 'overall_status');
$fid['brief_title'] = '_' . getFieldId('NCT', 'brief_title');
$fid['condition'] = '_' . getFieldId('NCT', 'condition');
$fid['intervention_name'] = '_' . getFieldId('NCT', 'intervention_name');
$fid['phase'] = '_' . getFieldId('NCT', 'phase');
$fid['enrollment'] = '_' . getFieldId('NCT', 'enrollment');
$fid['start_date'] = '_' . getFieldId('NCT', 'start_date');
$fid['primary_completion_date'] = '_' . getFieldId('NCT', 'primary_completion_date');
$fid['completion_date'] = '_' . getFieldId('NCT', 'completion_date');

$displist = array('NCTID' => 'NCT/nct_id', 'Conditions' => 'NCT/condition', 'Interventions' => 'NCT/intervention_name', 'Phase' => 'NCT/phase', 'Enrollment' => 'NCT/enrollment', 'Start date' => 'NCT/start_date', 'Primary completion date' => 'NCT/primary_completion_date', 'Completion date' => 'NCT/completion_date');
$params = array_merge($params, $excel_params);
$res = search($params,$fid,$page,$time_machine);
//storeParams(array('params' => $params, 'time' => $time_machine));	//used for iframe result count
$pstart = ($page-1) * $db->set['results_per_page'] + 1;
$pend = $pstart + $db->set['results_per_page'] - 1;
$pages = ceil($results / $db->set['results_per_page']);
$last = ($page*$db->set['results_per_page']>$results) ? $results : $pend;

//echo('Results of ' . $name . '<br />run on ' . $gentime);
echo('Results of report run on ' . $gentime);

$pager='';
if($results > $db->set['results_per_page'])
{
	$sort = '';
	if($_GET['sort'] == 'phase') $sort = '&amp;sort=phase';
	if($_GET['sort'] == 'enrollment') $sort = '&amp;sort=enrollment';
	if($_GET['sort'] == 'overall_status') $sort = '&amp;sort=overall_status';
	if($_GET['sort'] == 'phased') $sort = '&amp;sort=phased';
	if($_GET['sort'] == 'enrollmentd') $sort = '&amp;sort=enrollmentd';
	if($_GET['sort'] == 'overall_statusd') $sort = '&amp;sort=overall_statusd';
	echo('<h3>Found ' . $results . ' studies</h3>');
	if($pstart > 1)
	{
		$pager .= '<a href="intermediary.php?params=' . rawurlencode($_GET['params'])
			. '&amp;page=' . ($page-1) . '&amp;leading=' . rawurlencode($_GET['leading'])
			. $sort . '">&lt;&lt; Previous Page (' . ($pstart-$db->set['results_per_page']) . '-' . ($pstart-1) . ')</a>';
	}
	$pager .= ' &nbsp; &nbsp; &nbsp; Studies Shown (' . $pstart . '-' . $pend . ') &nbsp; &nbsp; &nbsp; ';
	if($pend < $results)
	{
		$nextlast = ($last+$db->set['results_per_page']);
		if($nextlast > $results) $nextlast = $results;
		$pager .= '<a href="intermediary.php?params=' . rawurlencode($_GET['params'])
			. '&amp;page=' . ($page+1) . '&amp;leading=' . rawurlencode($_GET['leading'])
			. $sort . '">Next Page (' . ($pstart+$db->set['results_per_page']) . '-' . $nextlast . ') &gt;&gt;</a>';
	}
	echo($pager);
}else{
	echo('<h3>Displaying all ' . $results . ' results</h3>');
}
echo('<br clear="all"/>');
echo('<form method="get" action="intermediary.php"><input type="submit" value="Sort"/> by <select name="sort">'
	. '<option'
		. ($_GET['sort']!='phase' && $_GET['sort']!='enrollment' && $_GET['sort']!='overall_status' ? ' selected="selected"' : '')
		. '>none</option>'
	. '<option value="phase"' . ($_GET['sort']=='phase' ? ' selected="selected"' : '') . '>phase</option>'
	. '<option value="enrollment"' . ($_GET['sort']=='enrollment' ? ' selected="selected"' : '') . '>enrollment</option>'
	. '<option value="overall_status"' . ($_GET['sort']=='overall_status' ? ' selected="selected"' : '') . '>status</option>'
	. '<option value="phased"' . ($_GET['sort']=='phased' ? ' selected="selected"' : '') . '>phase (Desc)</option>'
	. '<option value="enrollmentd"' . ($_GET['sort']=='enrollmentd' ? ' selected="selected"' : '') . '>enrollment (Desc)</option>'
	. '<option value="overall_statusd"' . ($_GET['sort']=='overall_statusd' ? ' selected="selected"' : '') . '>status (Desc)</option>'
	. '</select>'
	. '<input type="hidden" name="params" value="' . $_GET['params'] . '"/>'
	. '<input type="hidden" name="leading" value="' . $_GET['leading'] . '"/>'
	. '</form>');
echo('<table cellspacing="10"><tr><th>Rank</th><th>Status</th><th style="text-align:left;">Study</th></tr>');
$relrank = 0;
foreach($res as $id => $study)
{
	$fullid = padnct($study['NCT/nct_id']);
	$study['NCT/nct_id'] = $fullid;
	for($woo=0;$woo<2;$woo++)
		unset_nulls($study);
	echo('<tr><th>' . ($pstart + $relrank++) . '</th><th class="status"'
		. ($study['NCT/overall_status'] == 'Recruiting' ? ' style="color:#008800;"' : '') . '>' . $study['NCT/overall_status']
		. '</th><td><a href="http://clinicaltrials.gov/ct2/show/' . $fullid . '">' . $study['NCT/brief_title'] . '</a><dl>');
	foreach($displist as $dname => $fqname)
	{
		$val = $study[$fqname];
		echo('<dt>' . $dname . ':</dt><dd>' . (is_array($val) ? implode(', ', $val) : $val) . '</dd>');
	}
	echo('</dl></td></tr>');
	//krumo($study);
}
echo('</table>');
echo($pager);
echo('</body></html>');
?>