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
	  
	  document.getElementById('filteropt').innerHTML = 
		  '<input type="checkbox" name="wh" id="wh" value="1" /><label for="wh">Withheld</label><br/>'+
		  '<input type="checkbox" name="afm" id="afm" value="1" /><label for="afm">Approved for marketing</label><br/>' +
		  '<input type="checkbox" name="tna" id="tna" value="1" /><label for="tna">Temporarily not available</label><br/>' + 
		  '<input type="checkbox" name="nla" id="nla" value="1" /><label for="nla">No Longer Available</label><br/>' + 
		  '<input type="checkbox" name="wd" id="wd" value="1" /><label for="wd">Withdrawn</label><br/>' + 
		  '<input type="checkbox" name="t" id="t" value="1" /><label for="t">Terminated</label><br/>' +
		  '<input type="checkbox" name="s" id="s" value="1" /><label for="s">Suspended</label><br/>' +
		  '<input type="checkbox" name="c" id="c" value="1" /><label for="c">Completed</label><br/>';
	  
	  } else if(value == 'active') {
	  
	  document.getElementById('filteropt').innerHTML = 
		  '<input type="checkbox" name="nyr" id="nyr" value="1" /><label for="nyr">Not yet recruiting</label><br/>' +
		  '<input type="checkbox" name="r" id="r" value="1" /><label for="r">Recruiting</label><br/>' + 
		  '<input type="checkbox" name="ebi" id="ebi" value="1" /><label for="ebi">Enrolling by invitation</label><br/>' + 
		  '<input type="checkbox" name="anr" id="anr" value="1" /><label for="anr">Active, not recruiting</label><br/>' + 
		  '<input type="checkbox" name="a" id="a" value="1" /><label for="a">Available</label><br/>' ;
	  
	  } else {
	  
	  document.getElementById('filteropt').innerHTML = 
		  '<input type="checkbox" name="wh" id="wh" value="1" /><label for="wh">Withheld</label><br/>'+
		  '<input type="checkbox" name="afm" id="afm" value="1" /><label for="afm">Approved for marketing</label><br/>' +
		  '<input type="checkbox" name="tna" id="tna" value="1" /><label for="tna">Temporarily not available</label><br/>' + 
		  '<input type="checkbox" name="nla" id="nla" value="1" /><label for="nla">No Longer Available</label><br/>' + 
		  '<input type="checkbox" name="wd" id="wd" value="1" /><label for="wd">Withdrawn</label><br/>' + 
		  '<input type="checkbox" name="t" id="t" value="1" /><label for="t">Terminated</label><br/>' +
		  '<input type="checkbox" name="s" id="s" value="1" /><label for="s">Suspended</label><br/>' +
		  '<input type="checkbox" name="c" id="c" value="1" /><label for="c">Completed</label><br/>' +
		  '<input type="checkbox" name="nyr" id="nyr" value="1" /><label for="nyr">Not yet recruiting</label><br/>' +
		  '<input type="checkbox" name="r" id="r" value="1" /><label for="r">Recruiting</label><br/>' + 
		  '<input type="checkbox" name="ebi" id="ebi" value="1" /><label for="ebi">Enrolling by invitation</label><br/>' + 
		  '<input type="checkbox" name="anr" id="anr" value="1" /><label for="anr">Active, not recruiting</label><br/>' + 
		  '<input type="checkbox" name="a" id="a" value="1" /><label for="a">Available</label><br/>' ;

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

$excel_params 	= unserialize(gzinflate(base64_decode($_GET['params'])));
$gentime 		= $excel_params['rundate'];
$name 			= $excel_params['name'];
$time_machine 	= $excel_params['time'];
$results 		= $excel_params['count'];
$rowlabel 		= $excel_params['rowlabel'];
$columnlabel 	= $excel_params['columnlabel'];
$bomb			= $excel_params['bomb'];


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
$fid['acronym'] 			= '_' . getFieldId('NCT', 'acronym');

$displist = array('Enrollment' => 'NCT/enrollment', 'Status' => 'NCT/overall_status', 
								'Conditions' => 'NCT/condition', 'Interventions' => 'NCT/intervention_name',
								'Study Dates' => 'NCT/start_date', 'Phase' => 'NCT/phase');

$params = array_merge($params, $excel_params);
//$res = search($params,$fid,$page,$time_machine);

//differentiating betwen active and inactive category of records.
$arr = search($params,$fid,NULL,$time_machine);

$activecount 	= 0;$totactivecount	= 0;
$activephase 	= array();$activearray 	= array();
$actfilterarr 	= array('nyr'=>'Not yet recruiting', 'r'=>'Recruiting', 'ebi'=>'Enrolling by invitation', 
						'anr'=>'Active, not recruiting', 'a'=>'Available');

$inactivecount 	= 0;$totinactivecount= 0;
$inactivephase 	= array();$inactivearray 	= array();
$inactfilterarr = array('wh'=>'Withheld', 'afm'=>'Approved for marketing',
						'tna'=>'Temporarily not available', 'nla'=>'No Longer Available', 'wd'=>'Withdrawn', 't'=>'Terminated',
						's'=>'Suspended', 'c'=>'Completed');
						
//options added for third option as 'All'
$allarray 	= array();$allfilterarr = array();$allcount = 0;
$allfilterarr = array_merge($actfilterarr, $inactfilterarr);


$actflag = 0;$inactflag = 0;$allflag = 0;

if($_GET['list'] == 'inactive') { $inactflag = 1;  // checking if any of the inactive filters are set
} else if($_GET['list'] == 'all') { $allflag = 1; } // checking if any of the all filters are set
else { $actflag = 1;} // checking if any of the active filters are set

$current_yr	= date('Y');
$second_yr	= date('Y')+1;
$third_yr	= date('Y')+2;

foreach($arr as $key=>$val) { 

if($val['NCT/overall_status'] == 'Not yet recruiting' || $val['NCT/overall_status'] == 'Recruiting' || 
		$val['NCT/overall_status'] == 'Enrolling by invitation' || $val['NCT/overall_status'] == 'Active, not recruiting' || 
		$val['NCT/overall_status'] == 'Available') {
	$activephase[] = $val['NCT/phase'];
	$totactivecount++;
} else { 

	$inactivephase[] = $val['NCT/phase'];
	$totinactivecount++;
}

if($inactflag == 1) { 

	if($val['NCT/overall_status'] == 'Withheld' || $val['NCT/overall_status'] == 'Approved for marketing' || 
			$val['NCT/overall_status'] == 'Temporarily not available' || $val['NCT/overall_status'] == 'No Longer Available' || 
			$val['NCT/overall_status'] == 'Withdrawn' || $val['NCT/overall_status'] == 'Terminated' || 
			$val['NCT/overall_status'] == 'Suspended' || $val['NCT/overall_status'] == 'Completed') {
			
		if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) || isset($_GET['wd']) 
			|| isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) {
			
			$vall = implode(",",array_keys($inactfilterarr, $val['NCT/overall_status']));
			if(array_key_exists($vall, $_GET)) {
			
				$inactivecount++;
				$inactivearray[] = $val;	
			} 
		} else {
				$inactivecount++;
				$inactivearray[] = $val;	
		}
	}
} else if($allflag == 1) { 
	if($val['NCT/overall_status'] == 'Not yet recruiting' || $val['NCT/overall_status'] == 'Recruiting' || 
			$val['NCT/overall_status'] == 'Enrolling by invitation' || $val['NCT/overall_status'] == 'Active, not recruiting' || 
			$val['NCT/overall_status'] == 'Available' || $val['NCT/overall_status'] == 'Withheld' || 
			$val['NCT/overall_status'] == 'Approved for marketing' || $val['NCT/overall_status'] == 'Temporarily not available' || 
			$val['NCT/overall_status'] == 'No Longer Available' || $val['NCT/overall_status'] == 'Withdrawn' || 
			$val['NCT/overall_status'] == 'Terminated' || $val['NCT/overall_status'] == 'Suspended' || 
			$val['NCT/overall_status'] == 'Completed') {
			
			if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) || isset($_GET['a']) 
			|| isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) || isset($_GET['wd']) 
			|| isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) {	
			
			$vall = implode(",",array_keys($allfilterarr, $val['NCT/overall_status']));
			if(array_key_exists($vall, $_GET)) {
				$allcount++;
				$allarray[] = $val;	
			} 
		} else {
			$allcount++;
			$allarray[] = $val;	
		}
	}	
} else {

		if($val['NCT/overall_status'] == 'Not yet recruiting' || $val['NCT/overall_status'] == 'Recruiting' || 
			$val['NCT/overall_status'] == 'Enrolling by invitation' || $val['NCT/overall_status'] == 'Active, not recruiting' || 
			$val['NCT/overall_status'] == 'Available') {
			
			if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) || isset($_GET['a'])) {
			
				$vall = implode(",",array_keys($actfilterarr, $val['NCT/overall_status']));
				if(array_key_exists($vall, $_GET)) { 
					$activecount++;
					$activearray[] = $val;	
				} 
			} else {
				$activecount++;
				$activearray[] = $val;	
			}	
		}
	}

}

//checking which type of records(active/inactiv) needs to b shown
$var = (isset($_GET["list"])) ? ($_GET["list"].'array') : 'activearray' ; 

$count = count($$var);


$activestatus 		= '<input type="checkbox" name="nyr" id="nyr" value="1" ' 
					.($_GET['nyr'] ? ' checked="checked"' : ''). ' /><label for="nyr">Not yet recruiting</label><br/>'
					.'<input type="checkbox" name="r" id="r" value="1" ' 
					.($_GET['r'] ? ' checked="checked"' : ''). ' /><label for="r">Recruiting</label><br/>'
					.'<input type="checkbox" name="ebi" id="ebi" value="1" ' 
					.($_GET['ebi'] ? ' checked="checked"' : ''). ' /><label for="ebi">Enrolling by invitation</label><br/>'
					.'<input type="checkbox" name="anr" id="anr" value="1"' 
					.($_GET['anr'] ? ' checked="checked"' : ''). '  /><label for="anr">Active, not recruiting</label><br/>'
					.'<input type="checkbox" name="a" id="a" value="1" ' 
					.($_GET['a'] ? ' checked="checked"' : ''). ' /><label for="a">Available</label><br/>';
					
$inactivestatus 	= '<input type="checkbox" name="wh" id="wh" value="1" ' 
					.($_GET['wh'] ? ' checked="checked"' : ''). ' /><label for="wh">Withheld</label><br/>'
					.'<input type="checkbox" name="afm" id="afm" value="1" ' 
					.($_GET['afm'] ? ' checked="checked"' : ''). ' /><label for="afm">Approved for marketing</label><br/>'
					.'<input type="checkbox" name="tna" id="tna" value="1" ' 
					.($_GET['tna'] ? ' checked="checked"' : ''). '/><label for="tna">Temporarily not available</label><br/>'
					.'<input type="checkbox" name="nla" id="nla" value="1" ' 
					.($_GET['nla'] ? ' checked="checked"' : ''). '/><label for="nla">No Longer Available</label><br/>'
					.'<input type="checkbox" name="wd" id="wd" value="1" ' 
					.($_GET['wd'] ? ' checked="checked"' : ''). '/><label for="wd">Withdrawn</label><br/>'
					.'<input type="checkbox" name="t" id="t" value="1" ' 
					.($_GET['t'] ? ' checked="checked"' : ''). '/><label for="t">Terminated</label><br/>'
					.'<input type="checkbox" name="s" id="s" value="1" ' 
					.($_GET['s'] ? ' checked="checked"' : ''). '/><label for="s">Suspended</label><br/>'
					.'<input type="checkbox" name="c" id="c" value="1" ' 
					.($_GET['c'] ? ' checked="checked"' : ''). '/><label for="c">Completed</label><br/>';
					
$allstatus = $activestatus . $inactivestatus;

//storeParams(array('params' => $params, 'time' => $time_machine));	//used for iframe result count
$pstart = ($page-1) * $db->set['results_per_page'] + 1;
$pend = $pstart + $db->set['results_per_page'] - 1;
$pages = ceil($count / $db->set['results_per_page']);
$last = ($page*$db->set['results_per_page']>$count) ? $count : $pend;

//echo('Results of ' . $name . '<br />run on ' . $gentime);

//displaying row label and column label
echo('<center style="font-size: 18px;"><u>Results for ' . $rowlabel . ' in ' . $columnlabel . '</u></center><br/><br/>');
//<br/>Results of report run on ' . $gentime
//$bomb = 'lb';

if($bomb != '') {
	$bomb_type_arr = array('sb'=>'small', 'lb'=>'large');
	$bomb_img_arr = array('sb'=>'sbomb.png', 'lb'=>'lbomb.png');
	
	echo '<div style="font-size:11px;">'
	. '<span><img src="./images/' . $bomb_img_arr[$bomb] . '" style="vertical-align:middle;" /></span>'
	. '&nbsp;This cell has a ' . $bomb_type_arr[$bomb] . ' <a href="./help/bomb.html">bomb</a></div>';
}

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
	
	if(isset($_GET['list'])) { 
		$sort .= '&amp;list='.$_GET['list']; 
	} else {
		$sort .= '&amp;list=active'; 
	}
	
	foreach($actfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
	foreach($inactfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
	foreach($allfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }

	/*echo('<h3>Found ' . $results . ' studies - Displaying '.$count. (($_GET["active"]) ? (' '.$_GET["active"]) : ' active' ) 
	.' results</h3>');*/
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
}/*else{
	echo('<h3>Displaying ' . $count . (($_GET["active"]) ? (' '.$_GET["active"]) : ' active' ) .' results</h3>');
}*/

echo('<br clear="all"/><br/>');
echo('<form method="get" action="intermediary.php">'
	.'<table border="0" width="50%" cellpadding="5" cellspacing="2" height="auto">'
    .'<tr><td width="5%">List</td><td width="25%" nowrap="nowrap">'
	. '<input type="radio" name="list" checked="checked" value="active" onchange="javascript: applyfilter(this.value);" />'
	. '&nbsp;<span style="color: #00B050;">'
	. $totactivecount.' Active Records </span>');
	if(!empty($activephase)) { 
		echo ' (Highest Phase: ' . ((count($activephase) > 1) ? max($activephase) : $activephase[0]) . ')';
	} 
	
echo ('<br/><input type="radio" name="list" value="inactive" ' .
	($_GET['list']=='inactive' ? ' checked="checked"' : '')
	. 'onchange="javascript: applyfilter(this.value);" />&nbsp;<span style="color: #FF0000;">'
	.$totinactivecount.' Inactive Records</span>');
	if(!empty($inactivephase)) { 
		echo ' (Highest Phase: ' . ((count($inactivephase) > 1) ? max($inactivephase) : $inactivephase[0]) . ')';
	}
	
echo ('<br/><input type="radio" name="list" value="all" ' .
	($_GET['list']=='all' ? ' checked="checked"' : '')
	. 'onchange="javascript: applyfilter(this.value);" />&nbsp;' . count($arr) . ' All Records ');	
	
echo ('</td><td width="5%" nowrap="nowrap">Sort by</td><td width="25%"><select name="sort">'
    .'<option'
	. ($_GET['sort']!='phase' && $_GET['sort']!='enrollment' && $_GET['sort']!='overall_status' ? ' selected="selected"' : '')
	. '>none</option>'
    . '<option value="phase"' . ($_GET['sort']=='phase' ? ' selected="selected"' : '') . '>phase</option>'
    . '<option value="enrollment"' . ($_GET['sort']=='enrollment' ? ' selected="selected"' : '') . '>enrollment</option>'
    . '<option value="overall_status"'.($_GET['sort']=='overall_status' ? ' selected="selected"' : '') . '>overall_status</option>'
    . '<option value="phased"' . ($_GET['sort']=='phased' ? ' selected="selected"' : '') . '>phase (Desc)</option>'
    . '<option value="enrollmentd"' . ($_GET['sort']=='enrollmentd' ? ' selected="selected"' : '') . '>enrollment (Desc)</option>'
    . '<option value="overall_statusd"' .($_GET['sort']=='overall_statusd' ? ' selected="selected"' : '') 
	. '>overall_status (Desc)</option>'
    . '</select></td>'
	. '<td width="5%">Filter</td><td nowrap="nowrap"  width="25%" id="filteropt" style="font-size: 11px;border: 1px solid black;">'
    . (isset($_GET["list"]) ? ${$_GET["list"].'status'} : $activestatus)
    . '</td></tr>'
	. '<tr><td colspan="6"><input type="submit" value="Show"/></td></tr>'
	. '</table>'
	. '<input type="hidden" name="params" value="' . $_GET['params'] . '"/>'
	. '<input type="hidden" name="leading" value="' . $_GET['leading'] . '"/>'
	. '</form>');
	
echo '<table width="100%" border="0 " cellpadding="5" cellspacing="0" class="manage">'
 . '<tr><th rowspan="2" width="30%">Title</th>'
 . '<th rowspan="2" width="12%">N</th><th rowspan="2" width="14%">Status</th>'
 . '<th rowspan="2" width="15%">Conditions</th><th rowspan="2" width="15%">Interventions</th>'
 . '<th rowspan="2" width="10%">Study Dates</th><th rowspan="2" width="4%">Ph</th>'
 . '<th width="30%" nowrap="nowrap" colspan="36">Projected Completion</th></tr>'
 . '<tr><th colspan="12">' . $current_yr . '</th>'
 . '<th colspan="12">' . $second_yr . '</th>'
 . '<th colspan="12">' . $third_yr . '</th></tr>';

	 
$relrank = 0;
if(count($$var) > 0) {
	
	$start = $pstart-1;
	$end = $last;
	for($i=$start;$i<$last;$i++) 
	{ 	
		${$var}[$i]['NCT/nct_id'] = padnct(${$var}[$i]['NCT/nct_id']);
		for($woo=0;$woo<2;$woo++)
			unset_nulls(${$var}[$i]);
		
		//end date is calculated by giving precedence than primary completion date to completion if it exists
		$end_date = getEndDate(${$var}[$i]["NCT/primary_completion_date"], ${$var}[$i]["NCT/completion_date"]);

		$phase_arr = array('N/A'=>'#bfbfbf','0'=>'#44cbf5','0/1'=>'#99CC00','1'=>'#99CC00','1/2'=>'#ffff00','2'=>'#ffff00',
				'2/3'=>'#ff9900','3'=>'#ff9900','3/4'=>'#ff0000','4'=>'#ff0000');
		$ph = str_replace('Phase ', '', ${$var}[$i]['NCT/phase']);
		
		$start_month = date('m',strtotime(${$var}[$i]['NCT/start_date']));
		$start_year = date('Y',strtotime(${$var}[$i]['NCT/start_date']));
		$end_month = date('m',strtotime($end_date));
		$end_year = date('Y',strtotime($end_date));

		//$diff = ceil((strtotime($end_date)-strtotime(${$var}[$i]['NCT/start_date']))/2628000);
	
		echo '<tr>'//<td>' . ($pstart + $relrank++) . '.</td>'
			. '<td class="title"><a href="http://clinicaltrials.gov/ct2/show/' 
			. ${$var}[$i]['NCT/nct_id'] . '">' . ${$var}[$i]['NCT/brief_title'];
			if(${$var}[$i]['NCT/acronym'] != '') {
				echo '&nbsp;('.${$var}[$i]['NCT/acronym'].')';
			}
		echo '</a></td>';
		
		foreach($displist as $dname => $fqname)
		{ 
			$val = ${$var}[$i][$fqname];
			
			if($fqname == "NCT/enrollment"){ 
			
				echo '<td nowrap="nowrap" style="background-color:#D8D3E0;text-align:center;">';
				
					if(${$var}[$i]["NCT/enrollment_type"] != '') {
					
						if(${$var}[$i]["NCT/enrollment_type"] == 'Anticipated') { 
							echo '<span style="color:gray;font-weight:bold;" title="' 
							. ${$var}[$i]["NCT/enrollment_type"] . '">'	. $val . '</span>';
						} else { 
							echo $val . ' ('.${$var}[$i]["NCT/enrollment_type"].')';
						}
					} else {
						echo $val;
					}
				
				echo '</td>';  
			
			
			}else if($fqname == "NCT/start_date") {
			
				echo '<td nowrap="nowrap" style="background-color:#EDEAFF;">' 
				. date('m/Y',strtotime(${$var}[$i]["NCT/start_date"]))
				. ($end_date != '' ? (' -- ' . date('m/Y',strtotime($end_date))) : '');
				
				/*$val = floor((strtotime(${$var}[$i]["NCT/primary_completion_date"]) - 
				strtotime(${$var}[$i]["NCT/start_date"])) / (30*60*60*24) );
				
				if(strtotime(${$var}[$i]["NCT/primary_completion_date"]) < strtotime(date('Y-m-d')) ) {
					echo '<br/>(Est. completion: <span> Complete</span>)';
				} else {
					
					if($val < 12) {
						echo '<br/>(Est. completion: <span style="color:#FF0000">' .$val. 'm</span>)';
					} else if($val > 36) {
						echo '<br/>(Est. completion: <span> >3 years</span>)';
					} else {
						echo '<br/>(Est. completion: <span>' .$val. 'm</span>)';
					}
				}*/
				
				echo '</td>';
			
			
			} else if($fqname == "NCT/overall_status") {
				echo '<td style="background-color:#D8D3E0;">' . $val . ' </td>';
			
			} else if($fqname == "NCT/condition" || $fqname == "NCT/intervention_name") {
				
				echo '<td style="background-color:#EDEAFF;">' . (is_array($val) ? implode(', ', $val) : $val) . '</td>';
				
			} else if($fqname == "NCT/phase") {
			
				
				$phase = (${$var}[$i]['NCT/phase']=='N/A') ? $ph : ('P' . $ph);
				echo '<td style="background-color:'.$phase_arr[$ph].'">' . $phase . '</td>';
			
			
			} else { 
				echo '<td style="background-color:#EDEAFF;">' . (is_array($val) ? implode(', ', $val) : $val) . '</td>';
			}
			
		}
		
		//getting the project completion chart
		echo $str = getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, 
		$phase_arr[$ph], ${$var}[$i]['NCT/start_date'], $end_date);
			//krumo($study);
		echo '</tr>';
	}
	
}else {
	echo '<tr><th colspan="45" style="text-align: left;"> No record found. </th></tr>';
}
echo('</table><br/>');
echo($pager);
echo('</body></html>');

//calculating the end-date of a trial from completion and primary completion date
function getEndDate($primary_date, $date) {

	if($primary_date != '' && $date != '') {
		return $date;
		
	} else if($primary_date != '') {
		return $primary_date;
		
	} else if($date != '') {
		return $date;
		
	} else {
		return '';
	}
}

//get difference between two dates in months
function getColspan($start_dt, $end_dt) {
	
	$diff = ceil((strtotime($end_dt)-strtotime($start_dt))/2628000);
	return $diff;

}

//calculating the project completion chart in which the year ranges from the current year and next-to-next year
function getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, $bg_color, $start_date, $end_date){

		if($start_year < $current_yr) {
			
			if($end_year < $current_yr) {
				$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>';
			
			} else if($end_year == $current_yr) { 
			
				if($end_month == 12) {
					$value = '<td style="background-color:' . $bg_color . '" colspan="' . $end_month . '">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>';
				} else {
					$value = '<td style="background-color:' . $bg_color . '" colspan="' . $end_month . '">&nbsp;</td>'
					. '<td colspan="' . (12-$end_month) . '">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>';
				}
			} else if($end_year == $second_yr) { 
			 
			 	if($end_month == 12) {
					$value = '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td>';
				} else {
					$value = '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="' . $end_month . '">&nbsp;</td>'
					. '<td colspan="' . (12-$end_month) . '">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td>';
				}
		
			} else if($end_year == $third_yr) { 
			
			 	if($end_month == 12) {
					$value = '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>';
				} else {
					$value = '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="' . $end_month . '">&nbsp;</td><td colspan="' 
					. (12-$end_month) . '">&nbsp;</td>';
				}
			 
			} else { 
				$value = '<td colspan="12" style="background-color:' . $bg_color . '">&nbsp;</td>'
						. '<td colspan="12" style="background-color:' . $bg_color . '">&nbsp;</td>'
						. '<td colspan="12" style="background-color:' . $bg_color . '">&nbsp;</td>';
			}		
		
		
		
		} else if($start_year == $current_yr) {
		
			$val = getColspan($start_date, $end_date);
			$st = $start_month-1;
			if($end_year == $current_yr) {
				
				$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
					. '<td style="background-color:' . $bg_color . '" colspan="' . $val . '">&nbsp;</td>'
					. (((12 - ($st+$val)) != 0) ? '<td colspan="' .(12 - ($st+$val)) . '">&nbsp;</td>' : '')
					. '<td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td>';
			
			} else if($end_year == $second_yr) { 
			 
				$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
					. '<td style="background-color:' . $bg_color . '" colspan="' . $val . '">&nbsp;</td>'
					. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '')
					. '<td colspan="12">&nbsp;</td>';
		
			} else if($end_year == $third_yr) {
					
				$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
					. '<td style="background-color:' . $bg_color . '" colspan="' . $val . '">&nbsp;</td>'
					. (((36 - ($val+$st)) != 0) ? '<td colspan="' .(36 - ($val+$st)) . '">&nbsp;</td>' : '');
		
			} else {
				$value = '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>';
			}
			
		} 
	return $value;
}

?>


