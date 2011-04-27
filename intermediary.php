<?php
header('P3P: CP="CAO PSA OUR"');
session_start();
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');

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
  
  //<![CDATA[
  function applyfilter(value) {
 	
	  if(value == 'inactive') {
	  
	  document.getElementById('filteropt').innerHTML = 
		  "<input type='checkbox' name='wh' value='1' />Withheld<br/>"+
		 "<input type='checkbox' name='afm' value='1' />Approved for marketing<br/>" +
		 "<input type='checkbox' name='tna' value='1' />Temporarily not available<br/>" + 
		 "<input type='checkbox' name='nla' value='1' />No Longer Available<br/>" + 
		 "<input type='checkbox' name='wd' value='1' />Withdrawn<br/>" + 
		 "<input type='checkbox' name='t' value='1' />Terminated<br/>" +
		 "<input type='checkbox' name='s' value='1' />Suspended<br/>" +
		 "<input type='checkbox' name='c' value='1' />Completed<br/>";
	  
	  } else if(value == 'active') {
	  
	  document.getElementById('filteropt').innerHTML = 
		  '<input type="checkbox" name="nyr" value="1" />Not yet recruiting<br/>' +
		  '<input type="checkbox" name="r" value="1" />Recruiting<br/>' + 
		  '<input type="checkbox" name="ebi" value="1" />Enrolling by invitation<br/>' + 
		  '<input type="checkbox" name="anr" value="1" />Active, not recruiting<br/>' + 
		  '<input type="checkbox" name="a" value="1" />Available<br/>' ;
	  
	  } else {
	  
	  document.getElementById('filteropt').innerHTML = 
		  '<input type="checkbox" name="wh" value="1" />Withheld<br/>'+
		  '<input type="checkbox" name="afm" value="1" />Approved for marketing<br/>' +
		  '<input type="checkbox" name="tna" value="1" />Temporarily not available<br/>' + 
		  '<input type="checkbox" name="nla" value="1" />No Longer Available<br/>' + 
		  '<input type="checkbox" name="wd" value="1" />Withdrawn<br/>' + 
		  '<input type="checkbox" name="t" value="1" />Terminated<br/>' +
		  '<input type="checkbox" name="s" value="1" />Suspended<br/>' +
		  '<input type="checkbox" name="c" value="1" />Completed<br/>' +
		  '<input type="checkbox" name="nyr" value="1" />Not yet recruiting<br/>' +
		  '<input type="checkbox" name="r" value="1" />Recruiting<br/>' + 
		  '<input type="checkbox" name="ebi" value="1" />Enrolling by invitation<br/>' + 
		  '<input type="checkbox" name="anr" value="1" />Active, not recruiting<br/>' + 
		  '<input type="checkbox" name="a" value="1" />Available<br/>' ;

	  }
  }
  //]]>

	//<![CDATA[
	function doSorting(type) {
	
		
		var value = document.getElementById(type).value;
		
		if(document.getElementById('sortorder').value == '')
			document.getElementById('sortorder').value = type[0];
		else
			document.getElementById('sortorder').value = document.getElementById('sortorder').value+'-'+type[0];
		if(value == "") {
		
			document.getElementById(type).value = "desc";
			
		} else if(value == "desc") {
		
			document.getElementById(type).value = "asc";
			
		} else {
		
			document.getElementById(type).value = "";
		}
				
		document.getElementById('frmOtt').submit();	
	}
	 //]]>
</script>
</head>
<body>
<?php
if(!isset($_GET['params'])) die('cell not set');

$excel_params 	= unserialize(gzinflate(base64_decode($_GET['params'])));
$gentime 		= $excel_params['rundate'];
$name 			= $excel_params['name'];
$time_machine 	= $excel_params['time'];
$results 		= $excel_params['count'];
$rowlabel 		= $excel_params['rowlabel'];
$columnlabel 	= $excel_params['columnlabel'];
$bomb			= $excel_params['bomb'];  //added for bomb indication

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

//getting the order of aort
$v = explode("-",$_GET['sortorder']);
if(count($v) <= 3) {

	if(count($v) == 3) {
		
		${$v[0]} = 'style="width:14px;height:14px;"';
		${$v[1]} = 'style="width:10px;height:10px;"';
		${$v[2]} = 'style="width:7px;height:7px;"';
	 }
	 if(count($v) == 2) {
		${$v[0]} = 'style="width:14px;height:14px;"';
		${$v[1]} = 'style="width:10px;height:10px;"';
	}
	if(count($v) == 1) 
		${$v[0]} = 'style="width:14px;height:14px;"';
	
	
} elseif(count($v) > 3) {

	${$v[0]} = 'style="width:14px;height:14px;"';
	${$v[1]} = 'style="width:10px;height:10px;"';
	${$v[2]} = 'style="width:7px;height:7px;"';

}


$page = 1;
if(isset($_GET['page'])) $page = mysql_real_escape_string($_GET['page']);
if(isset($_GET['jump']) && isset($_GET['jumpno'])) $page = mysql_real_escape_string($_GET['jumpno']);
if(!is_numeric($page)) die('non-numeric page');

if(isset($_GET['back'])) --$page;
if(isset($_GET['next'])) ++$page;

$nodata = array('action' => array(), 'searchval' => array());


$statusparams = array();$enrollparams = array();$phaseparams = array();
if(isset($_GET['status']) && $_GET['status'] != '') {
	$sp = new SearchParam();
	$realfieldname = 'overall_status';
	$sp->field = '_' . getFieldId('NCT', $realfieldname);
	$sp->action = ($_GET['status'] == 'desc') ? 'descending' : 'ascending';
	$statusparams[] = $sp;
	
} 

if(isset($_GET['enrollment']) && $_GET['enrollment'] != '') {
	$sp = new SearchParam();
	$realfieldname = 'enrollment';
	$sp->field = '_' . getFieldId('NCT', $realfieldname);
	$sp->action = ($_GET['enrollment'] == 'desc') ? 'descending' : 'ascending';
	$enrollparams[] = $sp;
	
} 

if(isset($_GET['phase']) && $_GET['phase'] != '') {
	$sp = new SearchParam();
	$realfieldname = 'phase';
	$sp->field = '_' . getFieldId('NCT', $realfieldname);
	$sp->action = ($_GET['phase'] == 'desc') ? 'descending' : 'ascending';
	$phaseparams[] = $sp;
	
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

$params = array_merge($statusparams, $enrollparams, $phaseparams, $excel_params);
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

//added for highlighting changes
if($_GET['edited'] == 'oneweek') {
	$edited = ' -1 week ';
} else if($_GET['edited'] == 'onemonth') {
	$edited = ' -1 month ';
} else {
	$edited = ' -1 week ';
}

if($_GET['list'] == 'inactive') { $inactflag = 1;  // checking if any of the inactive filters are set
} else if($_GET['list'] == 'all') { $allflag = 1; } // checking if any of the all filters are set
else { $actflag = 1;} // checking if any of the active filters are set

$current_yr	= date('Y');
$second_yr	= date('Y')+1;
$third_yr	= date('Y')+2;

//checking if these has been edited to highlight changes
$new_arr = array();

foreach($arr as $key=>$val) { 

	$nct = getNCT($val['NCT/nct_id'], $gentime, $edited); 
	if (!is_array($nct)) { 
		$nct=array();
		$val['NCT/intervention_name'] = '(study not in database)';
	}
	$id = 'NCT' . str_pad($val['NCT/nct_id'],8,0,STR_PAD_LEFT);
	$new_arr[$id] = array_merge($nct, $val);
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
					
					
$allstatus = $activestatus . $inactivestatus;

//storeParams(array('params' => $params, 'time' => $time_machine));	//used for iframe result count
$pstart = ($page-1) * $db->set['results_per_page'] + 1;
$pend = $pstart + $db->set['results_per_page'] - 1;
$pages = ceil($count / $db->set['results_per_page']);
$last = ($page*$db->set['results_per_page']>$count) ? $count : $pend;

echo ('<table width="100%"><tr><td><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" /></td>'
	. '<td nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;">Interface Work In Progress</span>');
	
	if($bomb != '') {
		$bomb_type_arr = array('sb'=>'small', 'lb'=>'large');
		$bomb_img_arr = array('sb'=>'sbomb.png', 'lb'=>'lbomb.png');
	
		echo ('<span><img src="./images/' . $bomb_img_arr[$bomb] . '" alt="Bomb"  /></span>'
			. '&nbsp;This cell has a ' . $bomb_type_arr[$bomb] . ' <a href="./help/bomb.html">bomb</a>');
	}

//displaying row label and column label
echo ('</td><td class="result">Results for ' . htmlformat($rowlabel) . ' in ' . htmlformat($columnlabel) . '</td>'
		. '</tr></table>');
	
$pager='';
if($count > $db->set['results_per_page'])
{
	$sort = '';
	if($_GET['enrolment'] == 'overall_status') $sort = '&amp;enrolment=asc';
	if($_GET['enrolment'] == 'phased') $sort = '&amp;enrolment=desc';
	if($_GET['status'] == 'asc') $sort = '&amp;status=asc';
	if($_GET['status'] == 'desc') $sort = '&amp;status=desc';
	if($_GET['phase'] == 'asc') $sort = '&amp;phase=asc';
	if($_GET['phase'] == 'desc') $sort = '&amp;phase=desc';
	
	
	$sort .= '&amp;edited='.htmlspecialchars(trim($edited));
	if(isset($_GET['list'])) { 
		$sort .= '&amp;list='.$_GET['list']; 
	} else {
		$sort .= '&amp;list=active'; 
	}
	
	foreach($actfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
	foreach($inactfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
	foreach($allfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
	
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
	echo ($pager);
}

$dispcnt = (isset($_GET["list"]) ? ($_GET["list"].'count') : 'activecount' );
echo('<br clear="all"/><br/>');
echo('<form id="frmOtt" name="frmOtt" method="get" action="intermediary.php"><div style="height:100px;">'
    .'<div class="block"><div class="text">List</div>'
	. '<input type="radio" id="actlist" name="list" checked="checked" value="active" '
	. 'onchange="javascript: applyfilter(this.value);" />'
	. '&nbsp;<label for="actlist"><span style="color: #00B050;">'
	. $totactivecount.' Active Records </span></label>');
	if(!empty($activephase)) { 
		echo ' (Highest Phase: ' . ((count($activephase) > 1) ? max($activephase) : $activephase[0]) . ')';
	} 
	
echo ('<br/><input type="radio" id="inactlist" name="list" value="inactive" ' 
	. ($_GET['list']=='inactive' ? ' checked="checked"' : '')
	. 'onchange="javascript: applyfilter(this.value);" />&nbsp;<label for="inactlist"><span style="color: #FF0000;">'
	. $totinactivecount.' Inactive Records</span></label>');
	if(!empty($inactivephase)) { 
		echo ' (Highest Phase: ' . ((count($inactivephase) > 1) ? max($inactivephase) : $inactivephase[0]) . ')';
	}
	
echo ('<br/><input type="radio" id="alllist" name="list" value="all" ' .
		($_GET['list']=='all' ? ' checked="checked"' : '')
		. 'onchange="javascript: applyfilter(this.value);" />&nbsp;<label for="alllist">' . count($arr) 
		. ' All Records </label></div>');	

echo '<input type="hidden" id="status" name="status" value="' . $_GET['status'] . '" />' .
		'<input type="hidden" id="phase" name="phase" value="' . $_GET['phase'] . '" />' .
		'<input type="hidden" id="enrollment" name="enrollment" value="' . $_GET['enrollment'] . '" />' .
		'<input type="hidden" id="sortorder" name="sortorder" value="' . $_GET['sortorder'] . '" />';	
		
		echo ('<div class="drop"><div class="text">Show Only</div>'
		. '<span id="filteropt">'
		. (isset($_GET["list"]) ? ${$_GET["list"].'status'} : $activestatus)
		. '</span></div>'
		. '<div class="block"><div class="text">Find changes from: </div>'
		. '<input type="radio" id="oneweek" name="edited" value="oneweek" ' 
		. ((!isset($_GET['edited']) || $_GET['edited'] == 'oneweek') ? 'checked="checked"' : '' ) . ' />'
		. '<label for="oneweek">1 Week</label><br/>'
		. '<input type="radio" id="onemonth" name="edited" value="onemonth" ' 
		. (($_GET['edited'] == 'onemonth') ? 'checked="checked"' : '' ) . ' />'
		. '<label for="onemonth">1 Month</label>'
		. '</div></div>'
		. '<br/><br/>'
		. '<div><input type="submit" value="Show"/>&nbsp;'
		. '<input type="hidden" name="params" value="' . $_GET['params'] . '"/>'
		. '<input type="hidden" name="leading" value="' . $_GET['leading'] . '"/>'
		.  $$dispcnt . '&nbsp;Records</div>'
		. '</form>');
	
	echo '<table width="100%" border="0" cellpadding="4" cellspacing="0" class="manage">'
		 . '<tr><th rowspan="2" style="width:280px;">Title</th>'
		 . '<th style="width:28px;" title="gray values are anticipated and black values are actual">'
		 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'enrollment\');">N</a></th>'
		 . '<th style="width:55px;">'
		 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'status\');">Status</a></th>'
		 . '<th rowspan="2" style="width:130px;">Conditions</th>'
		 . '<th rowspan="2" style="width:130px;">Interventions</th>'
		 . '<th rowspan="2" style="width:29px;" title="MM/YY">Start</th>'
		 . '<th rowspan="2" style="width:27px;" title="MM/YY">End</th>'
		 . '<th style="width:14px;">'
		 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'phase\');">Ph</a></th>'
		 . '<th colspan="36" style="width:135px;"><div style="white-space:nowrap;">Projected Completion</div></th></tr>'
		 . '<tr><th>';
		 
			 if($_GET['enrollment'] == 'desc') {
				echo '<img src="images/des.png" alt="desc" border="0" ' . $e . ' />';
			 } else if($_GET['enrollment'] == 'asc') {
				echo '<img src="./images/asc.png" alt="asc" border="0" ' . $e . ' />';
			 } else { 
				echo '';
			 }
		 echo '</th><th>';
		 
			 if($_GET['status'] == 'desc') {
				echo '<img src="images/des.png" alt="desc" border="0" ' . $s . ' />';
			 } else if($_GET['status'] == 'asc') {
				echo '<img src="./images/asc.png" alt="asc" border="0" ' . $s . ' />';
			 } else { 
				echo '';
			 }
		 echo '</th><th>';
		
		 	if($_GET['phase'] == 'desc') {
				echo '<img src="images/des.png" alt="desc" border="0" ' . $p . ' />';
			 } else if($_GET['phase'] == 'asc') {
				echo '<img src="./images/asc.png" alt="asc" border="0" ' . $p . ' />';
			 } else { 
				echo '';
			 }
		 echo '</th>'
			 . '<th colspan="12" style="width:40px;">' . $current_yr . '</th>'
			 . '<th colspan="12" style="width:40px;">' . $second_yr . '</th>'
			 . '<th colspan="12" style="width:40px;">' . $third_yr . '</th></tr>';


$relrank = 0;
if(count($$var) > 0) {
	
	$start = $pstart-1;
	$end = $last;
	for($i=$start;$i<$last;$i++) 
	{ 	
		$highlight_arr =  padnct(${$var}[$i]['NCT/nct_id']);
		for($woo=0;$woo<2;$woo++)
			unset_nulls(${$var}[$i]);
		
		//end date is calculated by giving precedence to completion date(if it exists) than primary completion date  
		$end_date = getEndDate(${$var}[$i]["NCT/primary_completion_date"], ${$var}[$i]["NCT/completion_date"]);/*'2013-01-01';*/

		$phase_arr = array('N/A'=>'#bfbfbf','0'=>'#44cbf5','0/1'=>'#99CC00','1'=>'#99CC00','1/2'=>'#ffff00','2'=>'#ffff00',
				'2/3'=>'#ff9900','3'=>'#ff9900','3/4'=>'#ff0000','4'=>'#ff0000');
		$ph = str_replace('Phase ', '', ${$var}[$i]['NCT/phase']);
		
		$start_month = date('m',strtotime(${$var}[$i]['NCT/start_date']));
		$start_year = date('Y',strtotime(${$var}[$i]['NCT/start_date']));
		$end_month = date('m',strtotime($end_date));
		$end_year = date('Y',strtotime($end_date));
	
		if(in_array('NCT/brief_title',$new_arr[$highlight_arr]['edited'])) {
			$attr_one = ' highlight';
			$attr_two = 'title="' . $new_arr[$highlight_arr]['edited']['NCT/brief_title'] . '" ';
		}
		echo '<tr>'//<td>' . ($pstart + $relrank++) . '.</td>'
			. '<td class="title' . $attr_one . '" ' . $attr_two . '>'
			. '<div class="rowcollapse"><a href="http://clinicaltrials.gov/ct2/show/' 
			. ${$var}[$i]['NCT/nct_id'] . '">';
		
				if(${$var}[$i]['NCT/acronym'] != '') {
					echo '<b>' . htmlformat(${$var}[$i]['NCT/acronym']) 
						. '</b>&nbsp;' . htmlformat(${$var}[$i]['NCT/brief_title']);
							
				} else {
					echo htmlformat(${$var}[$i]['NCT/brief_title']);
				}
				
		echo '</a></div></td>';
		
		foreach($displist as $dname => $fqname)
		{ 
			$attr = ' ';
			$val = ${$var}[$i][$fqname];
			
			if( is_array( $val ) )
				$val = htmlformat(implode(', ', $val));
			else
				$val = htmlformat($val); 
			
			if($fqname == "NCT/enrollment"){ 
				
				if(in_array('NCT/enrollment',$new_arr[$highlight_arr]['edited']))
					$attr = 'class="highlight" title="' . $new_arr[$highlight_arr]['edited'][$fqname] . '" ';
					
				echo '<td nowrap="nowrap" style="background-color:#D8D3E0;text-align:center;" ' . $attr . ' >'
					. '<div class="rowcollapse">';
				
					if(${$var}[$i]["NCT/enrollment_type"] != '') {
					
						if(${$var}[$i]["NCT/enrollment_type"] == 'Anticipated') { 
							echo '<span style="color:gray;font-weight:bold;">'	. $val . '</span>';
							
						} else if(${$var}[$i]["NCT/enrollment_type"] == 'Actual') {
							echo $val;
							
						} else { 
							echo $val . ' (' . ${$var}[$i]["NCT/enrollment_type"] . ')';
						}
					} else {
						echo $val;
					}
				
				echo '</div></td>';  
			
			} else if($fqname == "NCT/start_date") {
			
				if(in_array('NCT/start_date',$new_arr[$highlight_arr]['edited']))
					$attr = 'class="highlight" title="' . $new_arr[$highlight_arr]['edited'][$fqname] . '" ';

				echo '<td style="background-color:#EDEAFF;" ' . $attr . ' >'
					. '<div class="rowcollapse">' . date('m/y',strtotime(${$var}[$i]["NCT/start_date"])) . '</div></td>';
				
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
				
				if(in_array(end_date,$new_arr[$highlight_arr]['edited']))
					$attr = 'class="highlight" title="' . $new_arr[$highlight_arr]['edited'][$fqname] . '" ';
					
				echo '<td style="background-color:#EDEAFF;" ' . $attr . '>';
					if($end_date != '') {
						echo '<div class="rowcollapse">' . date('m/y',strtotime($end_date)) . '</div></td>';
					} else {
						echo '&nbsp;</td>';
					}
			
			} else if($fqname == "NCT/overall_status") {
		
				if(in_array('NCT/overall_status',$new_arr[$highlight_arr]['edited']))
					$attr = 'class="highlight" title="' . $new_arr[$highlight_arr]['edited'][$fqname] . '" ';

			
				echo '<td style="background-color:#D8D3E0;" ' . $attr . '>'  
					. '<div class="rowcollapse">' . $val . '</div></td>';
			
			} else if($fqname == "NCT/condition") {
			
				if(in_array('NCT/condition',$new_arr[$highlight_arr]['edited']))
					$attr = 'class="highlight" title="' . $new_arr[$highlight_arr]['edited'][$fqname] . '" ';

				echo '<td style="background-color:#EDEAFF;" ' . $attr . '>'
					. '<div class="rowcollapse">' . $val . '</div></td>';
				
			} else if($fqname == "NCT/intervention_name") {
			
				if(in_array('NCT/intervention_name',$new_arr[$highlight_arr]['edited']))
					$attr = 'class="highlight" title="' . $new_arr[$highlight_arr]['edited'][$fqname] . '" ';

				echo '<td style="background-color:#EDEAFF;" ' . $attr . '>'
					. '<div class="rowcollapse">' . $val . '</div></td>';
				
			} else if($fqname == "NCT/phase") {
			
				if(in_array('NCT/phase',$new_arr[$highlight_arr]['edited']))
					$attr = 'class="highlight" title="' . $new_arr[$highlight_arr]['edited'][$fqname] . '" ';

				$phase = (${$var}[$i][$fqname] == 'N/A') ? $ph : ('P' . $ph);
				echo '<td style="background-color:'.$phase_arr[$ph] . '"' . $attr . '>'
					. '<div class="rowcollapse">' . $phase . '</div></td>';
			
			} else { 
				echo '<td style="background-color:#EDEAFF;"><div class="rowcollapse">' . $val . '</div></td>';
			}
			
		}
		
		//getting the project completion chart
		echo $str = getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, 
		$phase_arr[$ph], ${$var}[$i]['NCT/start_date'], $end_date);
			//krumo($study);
		echo '</tr>';
	}
	
}else {
	echo '<tr><th colspan="44" style="text-align: left;"> No record found. </th></tr>';
}
echo('</table><br/>');
echo($pager);
echo('</body></html>');

//calculating the end-date of a trial by giving precedence to completion than primary completion date
function getEndDate($primary_date, $date) {

	if($primary_date != '' && $date != '') {
		return $date;
		
	} else if($date != '') {
		return $date;
		
	} else if($primary_date != '') {
		return $primary_date;
		
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
				
					$value = '<td style="background-color:' . $bg_color 
					. '" colspan="' . $end_month . '"><div>&nbsp;</div></td>'
					. '<td style="width:'.(12-$end_month).'px" colspan="' . (12-$end_month) . '">&nbsp;</td>'
					. '<td colspan="12"><div style="width:40px;">&nbsp;</div></td>'
					. '<td colspan="12"><div style="width:40px;">&nbsp;</div></td>';
					
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

//return NCT fields given an NCTID
function getNCT($nct_id,$time,$edited)
{	
	$param = new SearchParam();
	$param->field = fieldNameToPaddedId('nct_id');
	$param->action = 'search';
	$param->value = $nct_id;
	
	$fieldnames = array('nct_id', 'brief_title', 'enrollment', 'enrollment_type', 'acronym', 'start_date', 'completion_date',
	'primary_completion_date', 'overall_status', 'condition', 'intervention_name', 'phase');
	
	foreach($fieldnames as $name) { 
		$list[] = fieldNameToPaddedId($name);
	}
	$res = search(array($param),$list,NULL,strtotime($time));

	foreach($res as $stu) $study = $stu;

	$studycatData=mysql_fetch_assoc(mysql_query("SELECT `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_cats_in_study` `dc` ON (`dc`.`id`=`dv`.`studycat`) WHERE `dv`.`field`='1' AND `dv`.`val_int`='".$nct_id."' AND `dc`.`larvol_id`='"
	.$study['larvol_id']."'"));
	
	$sql="SELECT DISTINCT `df`.`name` AS `fieldname`, `df`.`id` AS `fieldid`, `df`.`type` AS `fieldtype`, `dv`.`studycat`, dv.* FROM `data_values` `dv` LEFT JOIN `data_fields` `df` ON (`df`.`id`=`dv`.`field`) WHERE `df`.`name` IN ('" . join("','",$fieldnames) . "') AND `studycat`='" . $studycatData['studycat'] 
	. "' AND (`dv`.`superceded`<'" . date('Y-m-d',strtotime($time)) . "' AND `dv`.`superceded`>= '" 
	. date('Y-m-d',strtotime($edited,strtotime($time))) . "')";

    $changedFields = mysql_query($sql);
	$study['edited'] = array();
	
	while ($row=mysql_fetch_assoc($changedFields)){ 
		$study['edited'][] = 'NCT/'.$row['fieldname'];
		
		
		//getting previous value for updated trials
		if($row['fieldtype'] == 'enum') {
		
			$result = mysql_query('SELECT value FROM data_enumvals WHERE `field`=' . $row['fieldid'] 
			. ' AND `id` = "' . mysql_real_escape_string($row['val_'.$row['fieldtype']]) . '" LIMIT 1');
			if($result === false) return softDie('Bad SQL query getting enumval value');
			$result = mysql_fetch_array($result);
			if($result === false) return softDie('Invalid enumval value for field');
			
			$val = $result['value'];
		} else {
			$val = $row['val_'.$row['fieldtype']];
		}
		$study['edited']['NCT/'.$row['fieldname']] = $val;
		
	}

	return $study;
}

//Get field IDs for names
// - for the $list argument, search() takes IDs prepended with a padding character (stripped by highPass())
// - didn't find the alternative, so I wrote this
function fieldNameToPaddedId($name)
{
	$query = 'SELECT data_fields.id AS data_field_id FROM '
		. 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="' . $name . '" AND data_categories.name="NCT" LIMIT 1';
	$res = mysql_query($query);
	if($res === false) tex('Bad SQL query getting field ID of ' . $name);
	$res = mysql_fetch_assoc($res);
	if($res === false) tex('NCT schema not found!');
	return '_' . $res['data_field_id'];
}

//Get html content by passing through htmlspecialchars
function htmlformat($str)
{
	return htmlspecialchars($str);
}

function getPrevValue() {
}
?>