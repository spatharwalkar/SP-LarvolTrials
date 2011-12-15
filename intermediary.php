<?php
header('P3P: CP="CAO PSA OUR"');
session_start();
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('special_chars.php');

/*********TKV - time machine being disabled in the new database as it created problems   3.30 */
unset($_GET['time']);

error_reporting(E_ALL ^ E_NOTICE);
if(!isset($_GET['cparams']) && !isset($_GET['params']) && !isset($_GET['results']) && (!isset($_GET['a']) && !isset($_GET['p']))) die('cell not set');
if(isset($_POST['btnDownload'])) 
{
	if(isset($_POST['wFormat']) && $_POST['wFormat'] == 'xml') 
	{
		// Build XML
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<results>' . "\n";
		if($_POST['dOption'] == 'shown') 
		{
			$xml .= toXML(unserialize($_POST['xmlShownContent']));
		} else if($_POST['dOption'] == 'all') 
		{
			$xml .= toXML(unserialize($_POST['xmlFullContent']));
		}
		$xml .= "\n" . '</results>';
		//Send download
		header("Content-Type: text/xml");
		header("Content-Disposition: attachment;filename=ott.xml");
		header("Content-Transfer-Encoding: binary ");
		echo($xml);
		exit;
	}
}
//**************
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<base target='_blank' />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>Online Trial Tracker</title>
<link href="css/intermediary.css" rel="stylesheet" type="text/css" media="all" />
<script src="scripts/jquery.js" type="text/javascript"></script>	
<script src="scripts/slideout.js" type="text/javascript"></script>	
<script src="scripts/func.js" type="text/javascript"></script>
<script type="text/javascript">
$("html").css("overflow-x", "hidden"); //for hiding help tab
function checkformat()
{
	if(document.getElementById("wFormat").value=="excel")
	{
		document.forms["frmDOptions"].action="gen_excel.php";
	}
	if(document.getElementById("wFormat").value=="pdf")
	{
		document.forms["frmDOptions"].action="gen_pdf.php";
	}
} 
</script>	
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
<script type="text/javascript" language="javascript"> 
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
		'<input type="checkbox" name="a" value="1" />Available<br/>' +
		'<input type="checkbox" name="nlr" value="1" />No longer recruiting<br/>';
	  
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
		  '<input type="checkbox" name="a" value="1" />Available<br/>' +
		  '<input type="checkbox" name="nlr" value="1" />No longer recruiting<br/>';

	  }
  }
//]]>

//<![CDATA[
function doSorting(type) {
		
		var sOrder = document.getElementById('sortorder').value;
		
		if(sOrder.indexOf(type) != -1) {
		
			var i = sOrder.indexOf(type);
			var vtext = sOrder.slice(i, i+6);
			var sType = vtext.split('-');
			
			if(sType[1] == 'des') { 
			
				var nText = sOrder.replace(sType[0]+'-des',sType[0]+'-asc');
				document.getElementById('sortorder').value = nText;
				
			} else if(sType[1] == 'asc') { 
			
				var nText = sOrder.replace(vtext+'##','');
				document.getElementById('sortorder').value = nText;
			}
			
		} else {
		
			var nText = type+'-des##';
			document.getElementById('sortorder').value = document.getElementById('sortorder').value + nText;
		}
		
		document.getElementById('frmOtt').submit();	
	}
//]]>
</script>
</head>
<body>
<?php 
$content = new ContentManager();
$content->setSortParams();
$content->getChangeRange();
$content->chkType();
class ContentManager 
{
	
	private $params 	= array();
	private $fid 		= array();
	private $allfilterarr 	= array();
	private $sortorder;
	private $sort_params 	= array();
	private $sortimg 	= array();
	private $displist 	= array('Enrollment' => 'NCT/enrollment','Region' => 'region', 'Interventions' => 'NCT/intervention_name', 
								'Sponsor' => 'NCT/lead_sponsor', 'Status' => 'NCT/overall_status', 'Conditions' => 'NCT/condition', 
								'Study Dates' => 'NCT/start_date', 'Phase' => 'NCT/phase');
								
	private $imgscale 	= array('style="width:14px;height:14px;"', 'style="width:12px;height:12px;"', 
								'style="width:10px;height:10px;"', 'style="width:8px;height:8px;"', 
								'style="width:6px;height:6px;"');
								
	private $actfilterarr 	= array('nyr'=>'Not yet recruiting', 'r'=>'Recruiting', 'ebi'=>'Enrolling by invitation', 
								'anr'=>'Active, not recruiting', 'av'=>'Available', 'nlr' =>'No longer recruiting');
								
	private $inactfilterarr = array('wh'=>'Withheld', 'afm'=>'Approved for marketing',
								'tna'=>'Temporarily not available', 'nla'=>'No Longer Available', 'wd'=>'Withdrawn', 
								't'=>'Terminated','s'=>'Suspended', 'c'=>'Completed', 'empt'=>'');
								
	private $phase_arr 		= array('N/A'=>'#BFBFBF', '0'=>'#00CCFF', '0/1'=>'#99CC00', '1'=>'#99CC00', '1a'=>'#99CC00', '1b'=>'#99CC00', '1a/1b'=>'#99CC00', 
					'1c'=>'#99CC00', '1/2'=>'#FFFF00', '1b/2'=>'#FFFF00', '1b/2a'=>'#FFFF00', '2'=>'#FFFF00', '2a'=>'#FFFF00', '2a/2b'=>'#FFFF00', 
					'2a/b'=>'#FFFF00', '2b'=>'#FFFF00', '2/3'=>'#FF9900', '2b/3'=>'#FF9900','3'=>'#FF9900', '3a'=>'#FF9900', '3b'=>'#FF9900', '3/4'=>'#FF0000', 
					'3b/4'=>'#FF0000', '4'=>'#FF0000');
					
	//$nodata = array('action' => array(), 'searchval' => array());
	private $bomb_type_arr = array('sb'=>'small', 'lb'=>'large');
	private $bomb_img_arr = array('sb'=>'sbomb.png', 'lb'=>'lbomb.png');

	private $edited;
	private $e_style;
	private $p_style;
	private $activestatus;
	private $inactivestatus;
	private $allstatus;
	private $actflag;
	private $inactflag;
	private $allflag;
	private $current_yr;
	private $second_yr;
	private $third_yr;
	private $type;
	private $page;
	private $results_per_page;
	private $dispcount;
	private $activecount;
	private $allcount;
	private $inactivecount;
	private $time_machine;
	private $loggedIn;
	
	private $inactivearray = array();
	private $activearray = array();
	private $allarray = array();
			
	public function __construct() {
	
		$db = new DatabaseManager();
		$this->results_per_page = 100;//$db->set['results_per_page'];
		$this->loggedIn	= $db->loggedIn();
		
		$this->activestatus = '<input type="checkbox" name="nyr" value="1" ' 
			.(isset($_GET['nyr']) ? ' checked="checked"' : ''). ' />Not yet recruiting<br/>'
			.'<input type="checkbox" name="r" value="1" ' 
			.(isset($_GET['r']) ? ' checked="checked"' : ''). ' />Recruiting<br/>'
			.'<input type="checkbox" name="ebi" value="1" ' 
			.(isset($_GET['ebi']) ? ' checked="checked"' : ''). ' />Enrolling by invitation<br/>'
			.'<input type="checkbox" name="anr" value="1"' 
			.(isset($_GET['anr']) ? ' checked="checked"' : ''). '  />Active, not recruiting<br/>'
			.'<input type="checkbox" name="av" value="1" ' 
			.(isset($_GET['av']) ? ' checked="checked"' : ''). ' />Available<br/>'
			.'<input type="checkbox" name="nlr" value="1" ' 
			.(isset($_GET['nlr']) ? ' checked="checked"' : ''). ' />No longer recruiting<br/>';
							
		$this->inactivestatus = '<input type="checkbox" name="wh" value="1" ' 
			.(isset($_GET['wh']) ? ' checked="checked"' : ''). ' />Withheld<br/>'
			.'<input type="checkbox" name="afm" value="1" ' 
			.(isset($_GET['afm']) ? ' checked="checked"' : ''). ' />Approved for marketing<br/>'
			.'<input type="checkbox" name="tna" value="1" ' 
			.(isset($_GET['tna']) ? ' checked="checked"' : ''). '/>Temporarily not available<br/>'
			.'<input type="checkbox" name="nla" value="1" ' 
			.(isset($_GET['nla']) ? ' checked="checked"' : ''). '/>No Longer Available<br/>'
			.'<input type="checkbox" name="wd" value="1" ' 
			.(isset($_GET['wd']) ? ' checked="checked"' : ''). '/>Withdrawn<br/>'
			.'<input type="checkbox" name="t" value="1" ' 
			.(isset($_GET['t']) ? ' checked="checked"' : ''). '/>Terminated<br/>'
			.'<input type="checkbox" name="s" value="1" ' 
			.(isset($_GET['s']) ? ' checked="checked"' : ''). '/>Suspended<br/>'
			.'<input type="checkbox" name="c" value="1" ' 
			.(isset($_GET['c']) ? ' checked="checked"' : ''). '/>Completed<br/>';
							
		$this->allstatus = $this->activestatus . $this->inactivestatus;

		$this->allfilterarr = array_merge($this->actfilterarr, $this->inactfilterarr);	
		
		$this->fid['nct_id'] 					= '_' . getFieldId('NCT', 'nct_id');
		$this->fid['overall_status'] 			= '_' . getFieldId('NCT', 'overall_status');
		$this->fid['brief_title'] 				= '_' . getFieldId('NCT', 'brief_title');
		$this->fid['sponsor'] 					= '_' . getFieldId('NCT', 'lead_sponsor');
		$this->fid['collaborator'] 				= '_' . getFieldId('NCT', 'collaborator');
		$this->fid['condition'] 				= '_' . getFieldId('NCT', 'condition');
		$this->fid['intervention_name'] 		= '_' . getFieldId('NCT', 'intervention_name');
		$this->fid['phase'] 					= '_' . getFieldId('NCT', 'phase');
		$this->fid['enrollment'] 				= '_' . getFieldId('NCT', 'enrollment');
		$this->fid['enrollment_type'] 			= '_' . getFieldId('NCT', 'enrollment_type');
		$this->fid['start_date'] 				= '_' . getFieldId('NCT', 'start_date');
		$this->fid['completion_date'] 			= '_' . getFieldId('NCT', 'completion_date');
		$this->fid['acronym'] 					= '_' . getFieldId('NCT', 'acronym');
		$this->fid['inactive_date']				= 'inactive_date';
		$this->fid['region']					= 'region';
		
		$this->current_yr	= date('Y');
		$this->second_yr	= date('Y')+1;
		$this->third_yr		= date('Y')+2;

		$this->activecount = 0;
		$this->allcount = 0;
		$this->inactivecount = 0;

		$this->type = (isset($_GET["list"])) ? ($_GET["list"].'Trials') : 'activeTrials' ;
		if(isset($_GET['list']) && $_GET['list'] == 'inactive') { 
			$this->inactflag = 1; 		// checking if any of the inactive filters are set
			
		} else if(isset($_GET['list']) && $_GET['list'] == 'all') { 
			$this->allflag = 1; 	 	// checking if any of the all filters are set
			
		} else { 
			$this->actflag = 1; 		// checking if any of the active filters are set
		}
		
	}
	
	function setSortParams() {
	
		$sortorder = array();
		if(!isset($_GET['sortorder'])) { 
			$this->sort_params = "ph-des##ed-asc##sd-asc##os-asc##en-asc##";
		} else {	
			$this->sort_params = $_GET['sortorder'];
		}
		$this->sortorder = array_filter(explode("##", $this->sort_params));
		
		foreach($this->sortorder as $k => $v)
			$this->sortimg[substr($v,0,2)] = substr($v,3);
		
		$fieldname = array('en' => 'enrollment', 'ph' => 'phase', 'os' =>'overall_status', 
				'sd' => 'start_date','ed' => 'completion_date');
							
		foreach($this->sortorder as $k => $v) {
				
			$typ = substr($v, (strpos($v, '-')+1));
			$v = substr($v, 0, strpos($v, '-'));
			$sp = new SearchParam();
			
			if($v == 'ed')
				$sp->field = 'inactive_date';
			else	
				$sp->field = '_' . getFieldId('NCT', $fieldname[$v]);
				
			$sp->action = ($typ == 'des') ? 'descending' : 'ascending';
			$this->params[] = $sp;
		}
		
	}
	
	function getChangeRange() {
	
		//added for highlighting changes
		if(isset($_GET['edited']) && $_GET['edited'] == 'oneweek') {
			$this->edited = ' -1 week ';
		} else if(isset($_GET['edited']) && $_GET['edited'] == 'onemonth') {
			$this->edited = ' -1 month ';
		} else {
			$this->edited = ' -1 week ';
		}

	}
	
	function commonControls($count, $act, $inact, $all) {
		
		$enumvals = getEnumValues('clinical_study', 'institution_type');
	
		echo ('<div style="height:100px;width:1000px;"><div class="block"><div class="text">List</div>'
			. '<input type="radio" id="actlist" name="list" checked="checked" value="active" '
			. ' onchange="javascript: applyfilter(this.value);" />'
			. '&nbsp;<label for="actlist"><span style="color: #009900;"> ' . $act
			. ' Active Records </span></label>'
			. '<br/><input type="radio" id="inactlist" name="list" value="inactive" ' 
			. ((isset($_GET['list']) && $_GET['list'] == 'inactive') ? ' checked="checked" ' : '')
			. ' onchange="javascript: applyfilter(this.value);" />&nbsp;<label for="inactlist">'
			. '<span style="color: #3333CC;"> ' . $inact
			. ' Inactive Records</span></label>'
			. '<br/><input type="radio" id="alllist" name="list" value="all"' 
			. ((isset($_GET['list']) && $_GET['list'] == 'all') ? ' checked="checked" ' : '')
			. ' onchange="javascript: applyfilter(this.value);" />&nbsp;<label for="alllist"> ' . $all
			. ' All Records </label></div>'
			. '<div class="block"><div class="text">Find changes from: </div>'
			. '<input type="radio" id="oneweek" name="edited" value="oneweek" ' 
			. ((!isset($_GET['edited']) || $_GET['edited'] == 'oneweek') ? 'checked="checked"' : '' ) . ' />'
			. '<label for="oneweek">1 Week</label><br/>'
			. '<input type="radio" id="onemonth" name="edited" value="onemonth" ' 
			. ((isset($_GET['edited']) && $_GET['edited'] == 'onemonth') ? 'checked="checked"' : '' ) . ' />'
			. '<label for="onemonth">1 Month</label>'
			. '<br/><input type="checkbox" id="chkOnlyUpdated" name="chkOnlyUpdated" value="1" ' 
			. ((isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) ? 'checked="checked"' : '') . ' />'
			. '<label for="chkOnlyUpdated">Only Show Updated</label>'
			. '</div>'
			. '<input type="hidden" id="sortorder" name="sortorder" value="' . $this->sort_params . '" />'
			. '&nbsp;<div class="drop"><div class="text">Show Only</div>'
			. '<span id="filteropt">' . (isset($_GET["list"]) ? $this->{$_GET["list"].'status'} : $this->activestatus) 
			. '</span></div>'
			. '&nbsp;&nbsp;<div class="drop" style="margin-left:215px;"><div class="text">Show Only</div>');
			
		foreach($enumvals as $k => $v){ 
			echo '<input type="checkbox" id="' . $v . '" name="institution[]" value="' . $v . '" '
			. ((isset($_GET['institution']) && in_array($v, $_GET['institution'])) ? 'checked="checked"' : '' ) . '/>&nbsp;' 
			. '<label for="'.$v.'">' .$v . '</label><br/>';
		}
		echo ('</div></div><br/><input type="submit" value="Show"/>&nbsp;');
		if(strlen($count)) { echo $count . '&nbsp;Records'; }
		echo '<span id="addtoright"></span>';	
	
	}
	
	function pagination($page, $start, $end, $last, $count, $params, $leading, $tt_type, $stacktype) {
		
		$pager = '';
		$sort = '';
		
		if(isset($_GET['list'])) $sort .= '&amp;list='.$_GET['list']; else $sort .= '&amp;list=active'; 
		if(isset($_GET['sortorder']) && $_GET['sortorder'] != '') $sort .= '&amp;sortorder=' . rawurlencode($_GET['sortorder']);
		if(isset($_GET['instparams']) && $_GET['instparams'] != '') $sort .= '&amp;instparams=' 
		. rawurlencode($_GET['instparams']);
		if(isset($_GET['institution']) && $_GET['institution'] != '') 
		{ 
			foreach($_GET['institution'] as $k => $v)
			$sort .= '&amp;institution[]=' . $v;
		}
		if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] != '')
			$sort .= '&amp;chkOnlyUpdated=' . $_GET['chkOnlyUpdated'];
			
		if(isset($_GET['edited']) && $_GET['edited'] != '') 
			$sort .= '&amp;edited='.htmlspecialchars(trim($_GET['edited'])); 
		else 
			$sort .= '&amp;edited=oneweek';
		
		if(isset($_GET['v']) && $_GET['v'] != '')
			$sort .= '&amp;v=' . $_GET['v'];
			
		foreach($this->actfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
		foreach($this->inactfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
		foreach($this->allfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
		
		if($tt_type == 'stack') 
		{
			$stack_url = '';
				
			if(isset($_GET['cparams'])) 
			{
				foreach($_GET['params'] as $k => $v) 
				{
					$stack_url .= '&amp;leading['.$k.']=' . rawurlencode($_GET['leading'][$k]) . '&amp;params['.$k.']=' . rawurlencode($_GET['params'][$k]);
					
					if($stacktype == 'row') 
						$stack_url .= '&amp;rowupm['.$k.']=' . rawurlencode($_GET['rowupm'][$k]); 
					elseif($stacktype == 'col')
						$stack_url .= '&amp;colupm['.$k.']=' . rawurlencode($_GET['colupm'][$k]); 
					
					if(isset($_GET['trunc']))
						$stack_url .= '&amp;trunc=' . $_GET['trunc'];
				}
			}
			
			if($start > 1)
			{
				if(isset($_GET['results'])) 
				{
					$pager .= '<a target="_self" href="intermediary.php?results=' . rawurlencode($_GET['results']) . '&amp;type=' . rawurlencode($_GET['type']) 
					. '&amp;time=' . rawurlencode($_GET['time']) . '&amp;pg=' . ($page-1);
					if(isset($_GET['format']) && $_GET['format'] == 'new') 
						$pager .= '&amp;format=' . $_GET['format'];
						
				} 
				else if(isset($_GET['cparams'])) 
				{
					$pager .= '<a target="_self" href="intermediary.php?cparams=' . rawurlencode($_GET['cparams']) . $stack_url . '&amp;pg=' . ($page-1);
				}
				$pager .= $sort . '" style="float:left;">&lt;&lt; Previous Page (' . ($start - $this->results_per_page) . '-' . ($start-1) 
				. ')</a>&nbsp;&nbsp;&nbsp;';
			}
			$pager .= '<div style="float:left;margin:0px 10px;">Studies Shown (' . $start . '-' . $last . ')&nbsp;&nbsp;&nbsp;</div>';
			if($end < $count)
			{
				$nextlast = ($last+$this->results_per_page);
				if($nextlast > $count) $nextlast = $count;
				if(isset($_GET['results'])) 
				{
					$pager .= '<a target="_self" href="intermediary.php?results=' . rawurlencode($_GET['results']) . '&amp;type=' . rawurlencode($_GET['type']) 
						. '&amp;time=' . rawurlencode($_GET['time']) . '&amp;pg=' . ($page+1);
						
					if(isset($_GET['format']) && $_GET['format'] == 'new') 
					$pager .= '&amp;format=' . $_GET['format'];	
					
				} 
				else if(isset($_GET['cparams'])) 
				{
					$pager .= '<a target="_self" href="intermediary.php?cparams=' . rawurlencode($_GET['cparams']) . $stack_url . '&amp;pg=' . ($page+1);
				}
				$pager .= $sort . '" style="float:left;">Next Page (' . ($start+$this->results_per_page) . '-' . $nextlast . ') &gt;&gt;</a>';
			}
		} 
		else if($tt_type == 'indexed')
		{
			if($start > 1)
			{
				$pager .= '<a target="_self" href="intermediary.php?p=' . rawurlencode($_GET['p']) . '&amp;a=' . rawurlencode($_GET['a']) . '&amp;page=' . ($page-1);
				$pager .= $sort . '" style="float:left;">&lt;&lt; Previous Page (' . ($start - $this->results_per_page) . '-' . ($start-1) 
				. ')</a>&nbsp;&nbsp;&nbsp;';
			}
			$pager .= '<div style="float:left;margin:0px 10px;">Studies Shown (' . $start . '-' . $last . ')</div>';
			if($end < $count)
			{
				$nextlast = ($last+$this->results_per_page);
				if($nextlast > $count) $nextlast = $count;
				
				$pager .= '<a target="_self" href="intermediary.php?p=' . rawurlencode($_GET['p']) . '&amp;a=' . rawurlencode($_GET['a']) . '&amp;page=' . ($page+1);
				$pager .= $sort . '" style="float:left;">Next Page (' . ($start+$this->results_per_page) . '-' . $nextlast . ') &gt;&gt;</a>';
			}
		}
		else 
		{
			if($start > 1)
			{
				if(isset($_GET['results'])) 
				{
					$pager .= '<a target="_self" href="intermediary.php?results=' . rawurlencode($params) . '&amp;page=' . ($page-1) . '&amp;time=' 
					. rawurlencode($leading);
				} 
				else 
				{
					$pager .= '<a target="_self" href="intermediary.php?params=' . rawurlencode($params) . '&amp;page=' . ($page-1) . '&amp;leading=' 
					. rawurlencode($leading);
				}
				$pager .= $sort . '" style="float:left;">&lt;&lt; Previous Page (' . ($start - $this->results_per_page) . '-' . ($start-1) 
				. ')</a>&nbsp;&nbsp;&nbsp;';
			}
			$pager .= '<div style="float:left;margin:0px 10px;">Studies Shown (' . $start . '-' . $last . ')</div>';

			if($end < $count)
			{
				$nextlast = ($last+$this->results_per_page);
				if($nextlast > $count) $nextlast = $count;
				if(isset($_GET['results'])) 
				{
					$pager .= '<a target="_self" href="intermediary.php?results=' . rawurlencode($params) . '&amp;page=' . ($page+1) . '&amp;time=' 
					. rawurlencode($leading);
				} 
				else 
				{
					$pager .= '<a target="_self" href="intermediary.php?params=' . rawurlencode($params) . '&amp;page=' . ($page+1) . '&amp;leading=' 
					. rawurlencode($leading);
				}
				$pager .= $sort . '" style="float:left;">Next Page (' . ($start+$this->results_per_page) . '-' . $nextlast . ') &gt;&gt;</a>';
			}
		}
		echo $pager;

	}
	
	function processParams() 
	{
		global $logger;
		$return_param	= array();
		$return_param['inactiveTrials'] = array();
		$return_param['activeTrials'] = array();
		$return_param['allTrials'] = array();

		$return_param['inactiveCount'] = 0;
		$return_param['activeCount'] = 0;
		$return_param['allCount'] = 0;
		$return_param['trialsInfo'] = array();
		
		$ins_params		= array();
		$return_param['showRecordsCnt'] = 0;
		
		if(isset($_GET['results']) && isset($_GET['type'])) 
		{
			$this->time_machine = $_GET['time'];
			echo ('<input type="hidden" name="results" value="' . $_GET['results'] . '" />'
					. '<input type="hidden" name="type" value="' . $_GET['type'] . '" />'
					. '<input type="hidden" name="time" value="' . $_GET['time'] . '" />');
			
			if(isset($_GET['v']))
				echo ('<input type="hidden" name="v" value="' . $_GET['v'] . '" />');
				
			if(isset($_GET['format']) && $_GET['format'] == 'new') 
			{
				echo ('<input type="hidden" name="format" value="' . $_GET['format'] . '" />');
				//pack encoding method used to encode data in the url
				$results = unpack("l*", gzinflate(base64_decode(rawurldecode($_GET['results']))));
				$three = 0;
				$lengthcounter = 0; 
				$string = '';
				foreach($results as $vals)
				{
					if($lengthcounter == 0)
					{
						$lengthcounter = $vals;
						continue;
					}
					$string .= $vals . '.';
					$three++;
					if($three == $lengthcounter)
					{
						$resultIds[] = substr($string, 0, -1);
						$three = 0;
						$lengthcounter = 0;
						$string = '';
					}
				}
				
				$ids = explode('.', $resultIds[0]);
				foreach($resultIds as $resk => &$resv)
				{	
					if($resk != 0)
					{
						$out = array();
						$out = explode('.', $resv);
						if($_GET['type'] == 'col')
						{
							array_splice($out,1,0,$ids[1]);
						}
						else
						{
							array_splice($out,0,0,$ids[0]);
						}
						$resv = implode('.', $out);
					}	
				}
				$return_param['params_arr'] = $resultIds;
			} 
			else 
			{ 	
				//no specific encoding method i.e. only implode used to encode data in the url
				$return_param['params_arr'] = explode(',', gzinflate(base64_decode($_GET['results'])));
				$ids = explode('.', $return_param['params_arr'][0]);
			}
			
			if($_GET['type'] == 'col') 
			{
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $ids[1]);
				$t = 'Area: ' . $res['header'];
				$link_expiry_date = $res['expiry'];
			} 
			else if($_GET['type'] == 'row') 
			{
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $ids[0]);
				$t = 'Product: ' . $res['header'];
				$link_expiry_date = $res['expiry'];
			}
		} 
		else 
		{
			$return_param['c_params'] 	= unserialize(gzinflate(base64_decode($_GET['cparams'])));
			$stack_type = ($return_param['c_params']['type'] == 'col') ? 'rowlabel' : 'columnlabel';
			if($return_param['c_params']['type'] == 'col') 
			{
				$t = 'Area: ' . $return_param['c_params']['columnlabel'];
			} 
			else 
			{
				$t = 'Product: ' . $return_param['c_params']['rowlabel'];
			}
				
			echo ('<input type="hidden" name="cparams" value="' . $_GET['cparams'] . '"/>'
					. '<input type="hidden" name="trunc" value="' . $_GET['trunc'] . '"/>');
					
			foreach($_GET['params'] as $key => $value) 
			{
				echo ('<input type="hidden" name="params['.$key.']" value="' . $_GET['params'][$key] . '"/>'
						. '<input type="hidden" name="leading['.$key.']" value="' . $_GET['leading'][$key] . '"/>');
				if($return_param['c_params']['type'] == 'row') 
				{
					echo ('<input type="hidden" name="rowupm['.$key.']" value="' . $_GET['rowupm'][$key] . '"/>');
				} 
				else if($return_param['c_params']['type'] == 'col')
				{
					echo ('<input type="hidden" name="colupm['.$key.']" value="' . $_GET['colupm'][$key] . '"/>');
				}		
			}
			
			$return_param['params_arr'] = $_GET['params'];
		}
		
		if(isset($_GET['institution']) && $_GET['institution'] != '') 
		{
			array_push($this->fid, 'institution_type');
			$sp = new SearchParam();
			$sp->field 	= 'institution_type';
			$sp->action = 'search';
			$sp->value 	= $_GET['institution'];
			$ins_params = array($sp);
		}
		
		echo ('</td><td class="result">' . htmlformat($t) . '</td>' . '</tr></table>');
		echo ('<br clear="all"/>');
		
		$return_param['sections'] = array();
		
		foreach($return_param['params_arr'] as $pk => $pv) 
		{
			$excel_params 	= array();
			$params = array();
			$arr 	= array();
			$arrr 	= array();

			$return_param['link_expiry_date'][$pk] = array();
			$totinactivecount = 0; 
			$totactivecount	 = 0; 
			$totalcount	= 0;
			$larvolIds = array();			
			
			//New Link Method
			if(isset($_GET['results'])) 
			{
				$ids = explode(".", $pv);
				
				if($link_expiry_date != NULL && $link_expiry_date != '')
				{
					$return_param['link_expiry_date'][$pk][] = $link_expiry_date;
				}	
				//Retrieving headers
				if($_GET['type'] == 'row') 
				{
					$res = getLinkDetails('rpt_ott_header', 'header', 'id', $ids[1]);
				} 
				else if($_GET['type'] == 'col') 
				{
					$res = getLinkDetails('rpt_ott_header', 'header', 'id', $ids[0]);
				}
				
				$sectionHeader = htmlentities($res['header']);
				$return_param['trialsInfo'][$pk]['sectionHeader'] = $sectionHeader;
				
				if($res['expiry'] != NULL && $res['expiry'] != '')
				{
					$return_param['link_expiry_date'][$pk][] = $res['expiry'];
				}	
				if($ids[2] == '-1' || $ids[2] == '-2') 
				{
					if($ids[2] == '-2') 
					{
						$res = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', $ds[3]);
						if($res['expiry'] != NULL && $res['expiry'] != '')
						{
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						}	
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($res['result_set']))));
					}
					else
					{
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $ids[3]);
						if($res['expiry'] != NULL && $res['expiry'] != '')
						{
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						}	
						if($res['result_set'] != '') 
						{
							$sp = new SearchParam();
							$sp->field = 'larvol_id';
							$sp->action = 'search';
							$sp->value = str_replace(',', ' OR ', $res['result_set']);
							$excel_params = array($sp);
						}
					}
					if(isset($ids[4]))
					{	
						$val = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $ids[4]);
						if(isset($_GET['v']) && $_GET['v'] == 1)
							$val['intervention_name'] = explode('\n',$val['intervention_name']);
						else
							$val['intervention_name'] = explode(',',$val['intervention_name']);
						
						$return_param['trialsInfo'][$pk]['naUpms'] = $this->getNonAssocUpm($val['intervention_name'], $sectionHeader);	
					}
				} 
				else 
				{
					$searchdata = substr($ids[2],0,3);
					if(dechex($searchdata) == '73' && chr($searchdata) == 's') 
					{
						$res = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', substr($ids[2],3));
						if($res['expiry'] != NULL && $res['expiry'] != '')
						{
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						}	
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($res['result_set']))));
					} 
					else 
					{
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $ids[2]);
						if($res['expiry'] != NULL && $res['expiry'] != '')
						{
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						}
						if($res['result_set'] != '') 
						{	
							$sp = new SearchParam();
							$sp->field = 'larvol_id';
							$sp->action = 'search';
							$sp->value = str_replace(',', ' OR ', $res['result_set']);
							$excel_params = array($sp);
						}
					}
					if(isset($ids[3])) 
					{ 
						$val = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $ids[3]);
						if(isset($_GET['v']) && $_GET['v'] == 1)
							$val['intervention_name'] = explode('\n',$val['intervention_name']);
						else
							$val['intervention_name'] = explode(',',$val['intervention_name']);
						
						$return_param['trialsInfo'][$pk]['naUpms'] = $this->getNonAssocUpm($val['intervention_name'], $sectionHeader);
					}
				}
				
			} else {
			
				$excel_params = unserialize(gzinflate(base64_decode($pv)));
				$this->time_machine = $excel_params['time'];
				
				$sectionHeader = htmlentities($excel_params[$stack_type]);
				$return_param['trialsInfo'][$pk]['sectionHeader'] = $sectionHeader;
				
				if($excel_params['params'] === NULL)
				{ 	
					$packedLeadingIDs = gzinflate(base64_decode($_GET['leading'][$pk]));
					$leadingIDs = unpack('l*', $packedLeadingIDs);
					if($packedLeadingIDs === false) $leadingIDs = array();
					
					$sp = new SearchParam();
					$sp->field = 'larvol_id';
					$sp->action = 'search';
					$sp->value = implode(' OR ', $leadingIDs);
					$excel_params = array($sp);

				} else {	
					$excel_params = $excel_params['params'];
				}
				
				if(isset($_GET['cparams'])) 
				{ 	
					if(isset($_GET['rowupm']) && $return_param['c_params']['type'] == 'row') 
					{
						$val = unserialize(gzinflate(base64_decode($_GET['rowupm'][$pk])));
						if(isset($val) && $val != '' && !empty($val)) 
						{	
							$return_param['trialsInfo'][$pk]['naUpms'] = $this->getNonAssocUpm($val, $sectionHeader);
						}
					} 
					else if($process_params['c_params']['type'] == 'col') 
					{
						$val = unserialize(gzinflate(base64_decode($_GET['colupm'][$pk])));
						if(isset($val) && $val != '' && !empty($val)) 
						{
							$return_param['trialsInfo'][$pk]['naUpms'] = $this->getNonAssocUpm($val, $sectionHeader);
						}
					}
				}
			}
			
			$params = array_merge($this->params, $excel_params, $ins_params);
			if(!empty($excel_params)) 
			{
				$arrr = search($params,$this->fid,NULL,$this->time_machine);
			} 
			
			//Added to consolidate the data returned in an mutidimensional array format as opposed to earlier 
			//when it was not returned in an mutidimensional array format.
			$indx = 0;
			foreach($arrr as $key2 => $value2) 
			{
				foreach($value2 as $key3 => $value3) 
				{
					if(is_array($value3))
					{
						if($key3 == 'NCT/condition' || $key3 == 'NCT/intervention_name' || $key3 == 'NCT/lead_sponsor')
						{
							$arr[$indx][$key3] = implode(', ', $value3);
						}
						elseif($key3 == 'NCT/start_date' || $key3 == 'inactive_date')
						{
							$arr[$indx][$key3] = $value3[0];
						}
						elseif($key3 == 'NCT/phase')
						{
							$arr[$indx][$key3] = end($value3);
						}
						else
						{
							$arr[$indx][$key3] = implode(' ', $value3);
						}
					}
					else
					{
						$arr[$indx][$key3] = $value3;
					}
				}
				++$indx;
			}
			
			//Process to check for changes/updates in trials, matched & unmatched upms.
			foreach($arr as $key => $value) 
			{ 
				$nctId = $value['NCT/nct_id'];
				$dataset['trials'] = array();
				$dataset['matchedupms'] = array();
				
				
				if(!in_array($pk, $return_param['sections']))
				{
					$return_param['sections'][$pk] = $pk;
				}
				
				//checking for updated and new trials
				$dataset['trials'] = getTrialUpdates($nctId, $value['larvol_id'], $this->time_machine, $this->edited);
				$dataset['trials'] = array_merge($dataset['trials'], array('section' => $pk));
				
				//checking for updated and new unmatched upms.
				$dataset['matchedupms'] = getCorrespondingUPM($nctId, $this->time_machine, $this->edited);

				if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) 
				{
					//unsetting value for field acroynm if it has a previous value and no current value
					if(isset($dataset['trials']['edited']['NCT/acronym']) && !isset($value['trials']['NCT/acronym'])) 
					{
						unset($dataset['trials']['edited']['NCT/acronym']);
					}
					
					//unsetting value for field enrollment if the change is less than 20 percent
					if(isset($dataset['trials']['edited']['NCT/enrollment']))
					{
						$prevValue = substr($dataset['trials']['edited']['NCT/enrollment'],16);
						
						if(!getDifference($prevValue, $value['NCT/enrollment'])) 
						{
							unset($dataset['trials']['edited']['NCT/enrollment']);
						}
					}
					//merge only if updates are found
					foreach($dataset['matchedupms'] as $k => $v) 
					{
						if(empty($v['edited']) || $v['new'] != 'y') 
						{
							unset($dataset['matchedupms'][$k]);
						}
					}
					//merge only if updates are found
					if(!empty($dataset['trials']['edited']) || $dataset['trials']['new'] == 'y')
					{	
						if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
						|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
						|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
						|| isset($_GET['c']) || isset($_GET['nlr'])) 
						{	
							$statusValues = implode(",", array_keys($this->allfilterarr, $value['NCT/overall_status']));	
							if(array_key_exists($statusValues, $_GET)) 
							{
								$return_param['allTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
						else
						{
							$return_param['allTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
						}
						if(in_array($value['NCT/overall_status'], $this->inactfilterarr)) 
						{
							if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
							|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
							{
								$statusValues = implode(",", array_keys($this->inactfilterarr, $value['NCT/overall_status']));
								if(array_key_exists($statusValues, $_GET)) 
								{
									$return_param['inactiveTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
								}
							}
							else
							{
								$return_param['inactiveTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
						else
						{
							if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
							|| isset($_GET['av']) || isset($_GET['nlr'])) 
							{	
								$statusValues = implode(",", array_keys($this->actfilterarr, $value['NCT/overall_status']));
								if(array_key_exists($statusValues, $_GET)) 
								{
									$return_param['activeTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
								}
							}
							else
							{
								$return_param['activeTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
					}
				} 
				else 
				{
					if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
					|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
					|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
					|| isset($_GET['c']) || isset($_GET['nlr'])) 
					{	
						$statusValues = implode(",", array_keys($this->allfilterarr, $value['NCT/overall_status']));	
						if(array_key_exists($statusValues, $_GET)) 
						{
							$return_param['allTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
						}
					}
					else
					{
						$return_param['allTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
					}
					if(in_array($value['NCT/overall_status'], $this->inactfilterarr)) 
					{ 
						if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
						|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
						{	
							$statusValues = implode(",", array_keys($this->inactfilterarr, $value['NCT/overall_status']));
							if(array_key_exists($statusValues, $_GET)) 
							{	
								$return_param['inactiveTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
						else
						{
							$return_param['inactiveTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
						}
					}
					else
					{
						if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
						|| isset($_GET['av']) || isset($_GET['nlr'])) 
						{	
							$statusValues = implode(",", array_keys($this->actfilterarr, $value['NCT/overall_status']));
							if(array_key_exists($statusValues, $_GET)) 
							{
								$return_param['activeTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
						else
						{
							$return_param['activeTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
						}
					}
				}
				
				if(!in_array($value['NCT/overall_status'],$this->actfilterarr) && !in_array($value['NCT/overall_status'],$this->inactfilterarr)) 
				{ 
					$log 	= 'WARN: A new value "' . $val['NCT/overall_status'] . '" (not listed in the existing rule), was encountered for field overall_status.';
					$logger->warn($log);
					unset($log);
				}
				
				//getting count of active trials from a common function used in run_heatmap.php and here
				$larvolIds[] = $value['larvol_id'];
				sort($larvolIds); 
				$totalcount = count($larvolIds);
				$totactivecount = getActiveCount($larvolIds, $this->time_machine);
				$totinactivecount = $totalcount - $totactivecount; 
			}
			
			$return_param['inactiveCount'] 	= $return_param['inactiveCount'] + $totinactivecount;
			$return_param['activeCount']	= $return_param['activeCount'] + $totactivecount;
			$return_param['allCount']		= $return_param['allCount'] + $totalcount;
		}
		
		/*--------------------------------------------------------
		|Variables set for count when filtered by institution_type
		---------------------------------------------------------*/
		if(isset($_GET['instparams']) && $_GET['instparams'] != '') 
		{

			$return_param['instparams'] = $_GET['instparams'];
		} 
		return $return_param;
	}
	
	function chkType() 
	{
		global $now;
		global $logger;
		
		$Trials = array();
		$Trials['allTrials'] = array();
		$Trials['inactiveTrials'] = array();
		$Trials['activeTrials'] = array();
		
		$process_params = array();
		$process_params['link_expiry_date'] = array();
		$naUpms = array();
		$naUpmsDisplayStyle = 'collapse';
		
		echo('<form id="frmOtt" name="frmOtt" method="get" target="_self" action="intermediary.php">');
		echo ('<table width="100%"><tr><td><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" />'
				. '</td><td nowrap="nowrap">'
				. '<span style="color:#ff0000;font-weight:normal;margin-left:40px;">Interface Work In Progress</span>'
				. '<br/><span style="font-weight:normal;">Send feedback to '
				. '<a style="display:inline" target="_self" href="mailto:larvoltrials@larvol.com">'
				. 'larvoltrials@larvol.com</a></span>');
		
		//Stacked Ott.	
		if(isset($_GET['cparams']) || (isset($_GET['results']) && isset($_GET['type']))) 
		{	
			//Process the get parameters and extract the information
			$process_params = $this->processParams();//echo '<pre>';print_r($process_params['sections']);
			$count = count($process_params[$this->type]);
			
			if(isset($_GET['institution']) && $_GET['institution'] != '') 
			{
				$ins = unserialize(gzinflate(base64_decode(rawurldecode($process_params['instparams']))));
				$foundcount = ($ins['actcnt'] + $ins['inactcnt']);
				
				$this->commonControls($count, $ins['actcnt'], $ins['inactcnt'], ($ins['actcnt'] + $ins['inactcnt']));
			} 
			else 
			{
				$process_params['instparams']  = rawurlencode(base64_encode(gzdeflate(serialize(array('actcnt' => $process_params['activeCount'],
													'inactcnt' => $process_params['inactiveCount'])))));
				$foundcount = $process_params['allCount'];
				
				$this->commonControls($count, $process_params['activeCount'], $process_params['inactiveCount'], $process_params['allCount']);
			}
			echo ('<br clear="all"/><br/>');
			echo ('<input type="hidden" name="instparams" value="' . $process_params['instparams']. '" />');	

			//Pagination
			$page = 1;
			$start 	= '';$last = '';$end = '';$pages = '';
			if(isset($_GET['pg'])) $page = mysql_real_escape_string($_GET['pg']); 
			if(!is_numeric($page)) die('non-numeric page');
			
			$start 	= ($page-1) * $this->results_per_page + 1;
			$end 	= $start + $this->results_per_page - 1;
			$pages 	= ceil($count / $this->results_per_page);
			$last 	= ($page * $this->results_per_page > $count) ? $count : $end;
			
			if($count > $this->results_per_page) 
			{
				if(isset($_GET['results']) && isset($_GET['type'])) 
				{
					$this->pagination($page,  $start, $end, $last, $count, NULL, NULL, 'stack', $_GET['type']);
				} 
				else 
				{
					$this->pagination($page,  $start, $end, $last, $count, NULL, NULL, 'stack', $process_params['c_params']['type']);
				}
			}
			
			$this->displayHeader();
			
			if((isset($_GET['type']) && $_GET['type'] == 'row') || (isset($_GET['cparams']) && isset($_GET['rowupm'])))
			{
				$trialsInfo = array();
				foreach($process_params['trialsInfo'] as $trkey => &$trvalue)
				{
					foreach($trvalue['naUpms'] as $ukey => $uvalue)
					{
						$trialsInfo[] = $uvalue;
					}
					unset($trvalue['naUpms']);
				}
				
				if(isset($trialsInfo) && !empty($trialsInfo))
				{
					$naUpmsDisplayStyle = 'expand';
					echo '<tr class="trialtitles">'
						. '<td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
						. 'style="border-bottom:1px solid blue;background-image: url(\'images/down.png\');'
						. 'background-repeat: no-repeat;background-position:left center;"'
						. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
						. displayNAUpms($trialsInfo, 'rowstacked' , $currentYear, $secondYear, $thirdYear);
					/*echo '<tr><td colspan="' . getColspanforNAUpm($this->loggedIn)  . '" class="notopbottomborder leftrightborderblue sectiontitles">'
						. 'rowstacked</td></tr>';*/
				}
			}
			
			if($count > 0) 
			{
				displayContent($process_params[$this->type], $this->edited, $this->time_machine, $start, $last, 
				$this->current_yr, $this->second_yr, $this->third_yr, $naUpmsDisplayStyle, $process_params['trialsInfo'], $process_params['sections']);
			}
			/*else if(!isset($_GET['chkOnlyUpdated']))
			{
				echo '<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="norecord" align="left">No trials found</td></tr>';
			}*/
			
			echo('</table>');
			if(isset($_GET['trunc'])) 
			{
				$t = unserialize(gzinflate(base64_decode($_GET['trunc'])));
				if($t == 'y') echo ('<span style="font-size:10px;color:red;">Note: all data could not be shown</span>');
			}
			echo ('<input type="hidden" id="upmstyle" name="upmstyle" value="'.$naUpmsDisplayStyle.'" />');
			echo ('</form><br/>');
			
			if($foundcount > 0) 
			{
				$this->downloadOptions($count, $foundcount, $process_params['allTrials']);
				echo ('<br/>');
			}
			
			$link_expiry_date = array();
			foreach($process_params['link_expiry_date'] as $key => $value) 
				foreach($value as $kkey => $vvalue) 
					$link_expiry_date[] = $vvalue;
				
			//Expiry feature for new link method
			if(!empty($link_expiry_date) && ($this->loggedIn)) {
				echo '<span style="font-size:10px;color:red;">Expires on: ' . $link_expiry_date[0]  . '</span>';
			}
		} 
		else if(isset($_GET['p']) && isset($_GET['a'])) 
		{
			$_GET['p'] = mysql_real_escape_string($_GET['p']);
			$_GET['a'] = mysql_real_escape_string($_GET['a']);
			
			$productIds = array();
			$areaIds = array();
			
			$products = array();
			$areas = array();
			
			$fieldIds = array();
			
			$totinactivecount = 0;
			$totactivecount = 0;
			$totalcount = 0;
			$count = 0;
			
			$productIds = explode(',', $_GET['p']);
			$areaIds = explode(',', $_GET['a']);
			
			//total column
			$productCnt = count($productIds);
			$areaCnt = count($areaIds);
			
			$sections = array();
			if($productCnt > 1 && $areaCnt > 1)
			{
				$page = 1;
				if(isset($_GET['page'])) $page = mysql_real_escape_string($_GET['page']); 
				if(!is_numeric($page)) die('non-numeric page');
				
				echo ('</td><td class="result">Area: Total</td>' . '</tr></table>');
				echo('<br clear="all"/><br/>');
				
				$TrialsInfo = array();
				$Ids = array();
				foreach($productIds as $pkey => $pvalue)
				{
					$res = mysql_query("SELECT `name`, `id` FROM `products` WHERE id = '" . $pvalue . "' ");
					if(mysql_num_rows($res) > 0)
					{
						while($row = mysql_fetch_assoc($res))
						{
							$TrialsInfo[$pkey]['sectionHeader'] = $row['name'];
						}
					}
				}
				
				foreach($productIds as $pkey => $pvalue)
				{
					foreach($areaIds as $akey => $avalue)
					{
						$Ids[$pkey][$akey] = $pvalue . "', '" . $avalue;
					}
				}
				
				$fields = array_map('highPass', $this->fid);
				
				$Trials = array();
				$Trials['inactiveTrials'] = array();
				$Trials['activeTrials'] = array();
				$Trials['allTrials'] = array();
				
				foreach($Ids as $ikey => $ivalue)
				{	
					$inactiveCount = 0;
					$activeCount = 0;
					
					$result = array();
					$larvolIds = array();
					
					$query = "SELECT dcs.larvol_id FROM `data_values` `dv` "
								. " LEFT JOIN `data_cats_in_study` `dcs` ON (`dcs`.`id` = `dv`.`studycat`) "
								. " LEFT JOIN `data_fields` `df` ON (`df`.`id` = `dv`.`field`) "
								. " LEFT JOIN `data_categories` `dc` ON (`dc`.`id` = `df`.`category`) "
								. " WHERE `dc`.`name` IN ('Products', 'Areas') "
								. " AND df.`name` IN ('" . implode("','", $ivalue) . "') "
								. " AND `dv`.`val_bool`= '1' AND dv.superceded IS NULL ";
					$res = mysql_query($query);
					while($row = mysql_fetch_assoc($res))
					{
						$larvolIds[] = $row['larvol_id'];
					}
					
					//echo "<br/>-->".
					$query = 'SELECT dv.val_int AS "int",dv.val_bool AS "bool",dv.val_varchar AS "varchar",dv.val_date AS "date",de.`value` AS "enum", '
					. ' dv.val_text AS "text",dcs.larvol_id AS "larvol_id",df.`type` AS "type",df.`name` AS "name",dc.`name` AS "category" '
					. ' FROM data_values dv '
					. ' LEFT JOIN data_cats_in_study dcs ON dv.studycat = dcs.id '
					. ' LEFT JOIN data_fields df ON dv.`field`= df.id '
					. ' LEFT JOIN data_enumvals de ON dv.val_enum = de.id '
					. ' LEFT JOIN data_categories dc ON df.category = dc.id '
					. ' WHERE dv.superceded IS NULL AND dv.`field` IN("' . implode('","', $fields) . '") AND larvol_id IN("' . implode('","', $larvolIds) . '" )'
					/*. ' ORDER BY '*/;
					
					$res = mysql_query($query);
					while($row = mysql_fetch_assoc($res))
					{
						$id = $row['larvol_id'];
						$place = $row['category'] . '/' . $row['name']; //fully qualified field name
						$val = $row[$row['type']];
						//check if we already have a value for this field and ID
						if(isset($result[$id][$place]))
						{
							//now we know the value will have to be an array
							//check if there are already multiple values here
							if($result[$id][$place] == 'NCT/start_date' || $result[$id][$place] == 'inactive_date')
							{
								$result[$id][$place] = $result[$id][$place];
							}
							else if($result[$id][$place] == 'NCT/condition' || $result[$id][$place] == 'NCT/intervention_name' 
							|| $result[$id][$place] == 'NCT/lead_sponsor')
							{
								$result[$id][$place] = $result[$id][$place] . ' ' . $val;
							}
							else if($result[$id][$place] == 'NCT/phase')
							{
								$result[$id][$place] = $val;
							}
							else
							{
									$result[$id][$place] = $result[$id][$place] . ', ' . $val;
							}
						}
						else
						{
							//No previous value, so this value goes in the slot by itself.
							$result[$id][$place] = $val;
						}
					}
						
					foreach($result as $rkey => $rvalue) 
					{ 
						$nctId = $rvalue['NCT/nct_id'];
						$dataset['trials'] = array();
						$dataset['matchedupms'] = array();
						
						if(!in_array($ikey, $sections))
						{
							$sections[$ikey] = $ikey;
						}
								//checking for updated and new trials
						$dataset['trials'] = getTrialUpdates($nctId, $rvalue['larvol_id'], NULL, '-1 week');
						$dataset['trials'] = array_merge($dataset['trials'], array('section' => $ikey));
						
						if(in_array($rvalue['NCT/overall_status'],$this->inactfilterarr)) 
						{
							$inactiveCount++;
						}
						else
						{
							$activeCount++;
						}
					
						if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) 
						{
							//unsetting value for field acroynm if it has a previous value and no current value
							if(isset($dataset['trials']['edited']['NCT/acronym']) && !isset($rvalue['NCT/acronym'])) 
							{
								unset($dataset['trials']['edited']['NCT/acronym']);
							}
							//unsetting value for field enrollment if the change is less than 20 percent
							if(isset($dataset['trials']['edited']['NCT/enrollment'])) 
							{ 
								$prevValue = substr($dataset['trials']['edited']['NCT/enrollment'],16);
								if(!getDifference($prevValue, $rvalue['NCT/enrollment'])) 
								{
									unset($dataset['trials']['edited']['NCT/enrollment']);
								}
							}
							//merge only if updates are found
							foreach($dataset['matchedupms'] as $k => $v) 
							{
								if(empty($v['edited']) || $v['new'] != 'y') 
								{
									unset($dataset['matchedupms'][$k]);
								}
							}
							if(!empty($dataset['trials']['edited']) || $dataset['trials']['new'] == 'y')
							{
								if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
								|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
								|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
								|| isset($_GET['c']) || isset($_GET['nlr'])) 
								{	
									$statusValues = implode(",", array_keys($this->allfilterarr, $rvalue['NCT/overall_status']));	
									if(array_key_exists($statusValues, $_GET)) 
									{
										$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{
									$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								if(in_array($rvalue['NCT/overall_status'], $this->inactfilterarr)) 
								{
									if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
									|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
									{
										$statusValues = implode(",", array_keys($this->actfilterarr, $rvalue['NCT/overall_status']));
										if(array_key_exists($statusValues, $_GET)) 
										{
											$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{
										$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{
									if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
									|| isset($_GET['av']) || isset($_GET['nlr'])) 
									{	
										$statusValues = implode(",", array_keys($this->inactfilterarr, $rvalue['NCT/overall_status']));
										if(array_key_exists($statusValues, $_GET)) 
										{
											$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{
										$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
							}
						} 
						else 
						{	
							if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
							|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
							|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
							|| isset($_GET['c']) || isset($_GET['nlr'])) 
							{	
								$statusValues = implode(",", array_keys($this->allfilterarr, $rvalue['NCT/overall_status']));	
								if(array_key_exists($statusValues, $_GET)) 
								{
									$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							else
							{	
								$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							if(in_array($rvalue['NCT/overall_status'], $this->inactfilterarr)) 
							{
								if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
								|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
								{ 	
									$statusValues = implode(",", array_keys($this->inactfilterarr, $rvalue['NCT/overall_status']));
									if(array_key_exists($statusValues, $_GET)) 
									{
										$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							else
							{	
								if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
								|| isset($_GET['av']) || isset($_GET['nlr'])) 
								{
									$statusValues = implode(",", array_keys($this->actfilterarr, $rvalue['NCT/overall_status']));
									if(array_key_exists($statusValues, $_GET)) 
									{
										$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{	
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}	
					
						if(!in_array($rvalue['NCT/overall_status'],$this->actfilterarr) && !in_array($rvalue['NCT/overall_status'],$this->inactfilterarr)) 
						{ 
							$log 	= 'WARN: A new value "' . $value['NCT/overall_status'] 
							. '" (not listed in the existing rule), was encountered for field overall_status.';
							$logger->warn($log);
							unset($log);
						}
					}
					
					$totinactivecount  = $inactiveCount + $totinactivecount;
					$totactivecount		= $activeCount + $totactivecount;
					$totalcount		= $totalcount + $inactiveCount + $activeCount; 
				}
				
				$count = count($Trials[$this->type]);
				$this->commonControls($count, $totactivecount, $totinactivecount, $totalcount);
				echo '<br/><br clear="all" />';
				echo '<input type="hidden" name="p" value="' . $_GET['p'] . '"/><input type="hidden" name="a" value="' . $_GET['a'] . '"/>';
				
				$start 	= '';$last = '';$end = '';$pages = '';
		
				$start 	= ($page-1) * $this->results_per_page + 1;
				$end 	= $start + $this->results_per_page - 1;
				$pages 	= ceil($count / $this->results_per_page);
				$last 	= ($page * $this->results_per_page > $count) ? $count : $end;
				
				if($count > $this->results_per_page) 
				{
					$this->pagination($page, $start, $end, $last, $count, $_GET['p'], $_GET['v'], 'indexed', NULL);
				}
		
				$this->displayHeader();
				if($count > 0) 
				{
					displayContent($Trials[$this->type], $this->edited, NULL, $start, $last, $this->current_yr, $this->second_yr, $this->third_yr, 'collapse',
					$TrialsInfo, $sections);
				}
				else
				{
					echo ('<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="norecord" align="left">No trials found.</td></tr>');
				}
				echo('</table><br/>');
				echo ('</form>');


			}
			else if($productCnt > 1 || $areaCnt > 1)
			{
				$page = 1;
				if(isset($_GET['page'])) $page = mysql_real_escape_string($_GET['page']); 
				if(!is_numeric($page)) die('non-numeric page');
				
				$Ids = array();
				
				if($productCnt > 1)
				{	
					$areaName = mysql_fetch_assoc(mysql_query("SELECT `name` FROM `areas` WHERE id = '" . implode("','", $areaIds) . "' "));
					$areaName = $areaName['name'];
					
					echo ('</td><td class="result">Area: ' . htmlformat($areaName) . '</td>' . '</tr></table>');
					echo('<br clear="all"/><br/>');
					
					$TrialsInfo = array();
					$Ids = array();
					
					foreach($productIds as $pkey => $pvalue)
					{
						$res = mysql_query("SELECT `name`, `id` FROM `products` WHERE id = '" . $pvalue . "' ");
						if(mysql_num_rows($res) > 0)
						{
							while($row = mysql_fetch_assoc($res))
							{
								$TrialsInfo[$pkey]['sectionHeader'] = $row['name'];
								$Ids[$pkey][0] = $areaIds[0];
								$Ids[$pkey][1] = $row['id'];
							}
						}
					}
					
					$fields = array_map('highPass', $this->fid);
					
					$Trials = array();
					$Trials['inactiveTrials'] = array();
					$Trials['activeTrials'] = array();
					$Trials['allTrials'] = array();
					
					foreach($Ids as $ikey => $ivalue)
					{
						$inactiveCount = 0;
						$activeCount = 0;
						
						$result = array();
						$larvolIds = array();
						
						$query = "SELECT dcs.larvol_id FROM `data_values` `dv` "
								. " LEFT JOIN `data_cats_in_study` `dcs` ON (`dcs`.`id` = `dv`.`studycat`) "
								. " LEFT JOIN `data_fields` `df` ON (`df`.`id` = `dv`.`field`) "
								. " LEFT JOIN `data_categories` `dc` ON (`dc`.`id` = `df`.`category`) "
								. " WHERE `dc`.`name` IN ('Products', 'Areas') "
								. " AND df.`name` IN ('" . implode("','", $ivalue) . "') "
								. " AND `dv`.`val_bool`= '1' AND dv.superceded IS NULL ";
						$res = mysql_query($query);
						while($row = mysql_fetch_assoc($res))
						{
							$larvolIds[] = $row['larvol_id'];
						}
						
						//echo "<br/>-->".
						$query = 'SELECT dv.val_int AS "int",dv.val_bool AS "bool",dv.val_varchar AS "varchar",dv.val_date AS "date",de.`value` AS "enum", '
						. ' dv.val_text AS "text",dcs.larvol_id AS "larvol_id",df.`type` AS "type",df.`name` AS "name",dc.`name` AS "category" '
						. ' FROM data_values dv '
						. ' LEFT JOIN data_cats_in_study dcs ON dv.studycat = dcs.id '
						. ' LEFT JOIN data_fields df ON dv.`field`= df.id '
						. ' LEFT JOIN data_enumvals de ON dv.val_enum = de.id '
						. ' LEFT JOIN data_categories dc ON df.category = dc.id '
						. ' WHERE dv.superceded IS NULL AND dv.`field` IN("' . implode('","', $fields) . '") AND larvol_id IN("' . implode('","', $larvolIds) . '" )';
						
						$res = mysql_query($query);
						while($row = mysql_fetch_assoc($res))
						{
							$id = $row['larvol_id'];
							$place = $row['category'] . '/' . $row['name']; //fully qualified field name
							$val = $row[$row['type']];
							//check if we already have a value for this field and ID

							if(isset($result[$id][$place]))
							{
								//now we know the value will have to be an array
								//check if there are already multiple values here
								if($result[$id][$place] == 'NCT/start_date' || $result[$id][$place] == 'inactive_date')
								{
									$result[$id][$place] = $result[$id][$place];
								}
								else if($result[$id][$place] == 'NCT/condition' || $result[$id][$place] == 'NCT/intervention_name' 
								|| $result[$id][$place] == 'NCT/lead_sponsor')
								{
									$result[$id][$place] = $result[$id][$place] . ' ' . $val;
								}
								else if($result[$id][$place] == 'NCT/phase')
								{
									$result[$id][$place] = $val;
								}
								else
								{
										$result[$id][$place] = $result[$id][$place] . ', ' . $val;
								}
							}
							else
							{
								//No previous value, so this value goes in the slot by itself.
								$result[$id][$place] = $val;
							}
						}
						
						foreach($result as $rkey => $rvalue) 
						{ 
							$nctId = $rvalue['NCT/nct_id'];
							$dataset['trials'] = array();
							$dataset['matchedupms'] = array();
							
							if(!in_array($ikey, $sections))
							{
								$sections[$ikey] = $ikey;
							}
							//checking for updated and new trials
							$dataset['trials'] = getTrialUpdates($nctId, $rvalue['larvol_id'], NULL, '-1 week');
							$dataset['trials'] = array_merge($dataset['trials'], array('section' => $ikey));
							
							if(in_array($rvalue['NCT/overall_status'],$this->inactfilterarr)) 
							{
								$inactiveCount++;
							}
							else
							{
								$activeCount++;
							}
						
							if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) 
							{
								//unsetting value for field acroynm if it has a previous value and no current value
								if(isset($dataset['trials']['edited']['NCT/acronym']) && !isset($rvalue['NCT/acronym'])) 
								{
									unset($dataset['trials']['edited']['NCT/acronym']);
								}
								//unsetting value for field enrollment if the change is less than 20 percent
								if(isset($dataset['trials']['edited']['NCT/enrollment'])) 
								{ 
									$prevValue = substr($dataset['trials']['edited']['NCT/enrollment'],16);
									if(!getDifference($prevValue, $rvalue['NCT/enrollment'])) 
									{
										unset($dataset['trials']['edited']['NCT/enrollment']);
									}
								}
								//merge only if updates are found
								foreach($dataset['matchedupms'] as $k => $v) 
								{
									if(empty($v['edited']) || $v['new'] != 'y') 
									{
										unset($dataset['matchedupms'][$k]);
									}
								}
								if(!empty($dataset['trials']['edited']) || $dataset['trials']['new'] == 'y')
								{
									if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
									|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
									|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
									|| isset($_GET['c']) || isset($_GET['nlr'])) 
									{	
										$statusValues = implode(",", array_keys($this->allfilterarr, $rvalue['NCT/overall_status']));	
										if(array_key_exists($statusValues, $_GET)) 
										{
											$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{
										$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
									if(in_array($rvalue['NCT/overall_status'], $this->inactfilterarr)) 
									{
										if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
										|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
										{
											$statusValues = implode(",", array_keys($this->actfilterarr, $rvalue['NCT/overall_status']));
											if(array_key_exists($statusValues, $_GET)) 
											{
												$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
											}
										}
										else

										{
											$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{
										if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
										|| isset($_GET['av']) || isset($_GET['nlr'])) 
										{	
											$statusValues = implode(",", array_keys($this->inactfilterarr, $rvalue['NCT/overall_status']));
											if(array_key_exists($statusValues, $_GET)) 
											{
												$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
											}
										}
										else
										{
											$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
								}
							} 
							else 
							{	
								if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
								|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
								|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
								|| isset($_GET['c']) || isset($_GET['nlr'])) 
								{	
									$statusValues = implode(",", array_keys($this->allfilterarr, $rvalue['NCT/overall_status']));	
									if(array_key_exists($statusValues, $_GET)) 
									{
										$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{	
									$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								if(in_array($rvalue['NCT/overall_status'], $this->inactfilterarr)) 
								{
									if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
									|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
									{ 	
										$statusValues = implode(",", array_keys($this->inactfilterarr, $rvalue['NCT/overall_status']));
										if(array_key_exists($statusValues, $_GET)) 
										{
											$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{
										$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{	
									if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
									|| isset($_GET['av']) || isset($_GET['nlr'])) 
									{
										$statusValues = implode(",", array_keys($this->actfilterarr, $rvalue['NCT/overall_status']));
										if(array_key_exists($statusValues, $_GET)) 
										{
											$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{	
										$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
							}	
						
							if(!in_array($rvalue['NCT/overall_status'],$this->actfilterarr) && !in_array($rvalue['NCT/overall_status'],$this->inactfilterarr)) 
							{ 
								$log 	= 'WARN: A new value "' . $rvalue['NCT/overall_status'] 
								. '" (not listed in the existing rule), was encountered for field overall_status.';
								$logger->warn($log);
								unset($log);
							}
						}
						
						$totinactivecount  = $inactiveCount + $totinactivecount;
						$totactivecount		= $activeCount + $totactivecount;
						$totalcount		= $totalcount + $inactiveCount + $activeCount; 
					}
				}
				else
				{	
					$productName = mysql_fetch_assoc(mysql_query("SELECT `name` FROM `products` WHERE id = '" . implode("','", $productIds) . "' "));
					$productName = $productName['name'];
					
					echo ('</td><td class="result">Product: ' . htmlformat($productName) . '</td>' . '</tr></table>');
					echo('<br clear="all"/><br/>');
					
					$TrialsInfo = array();
					$Ids = array();
					
					foreach($areaIds as $akey => $avalue)
					{
						$res = mysql_query("SELECT `name`, `id` FROM `areas` WHERE id = '" . $avalue . "' ");
						if(mysql_num_rows($res) > 0)
						{
							while($row = mysql_fetch_assoc($res))
							{
								$TrialsInfo[$akey]['sectionHeader'] = $row['name'];
								$Ids[$akey][0] = $productIds[0];
								$Ids[$akey][1] = $row['id'];
							}
						}
					}
					
					$fields = array_map('highPass', $this->fid);
					
					$Trials = array();
					$Trials['inactiveTrials'] = array();
					$Trials['activeTrials'] = array();
					$Trials['allTrials'] = array();
					
					foreach($Ids as $ikey => $ivalue)
					{	
						$inactiveCount = 0;
						$activeCount = 0;
						
						$result = array();
						$larvolIds = array();
						
						$query = "SELECT dcs.larvol_id FROM `data_values` `dv` "
								. " LEFT JOIN `data_cats_in_study` `dcs` ON (`dcs`.`id` = `dv`.`studycat`) "
								. " LEFT JOIN `data_fields` `df` ON (`df`.`id` = `dv`.`field`) "
								. " LEFT JOIN `data_categories` `dc` ON (`dc`.`id` = `df`.`category`) "
								. " WHERE `dc`.`name` IN ('Products', 'Areas') "
								. " AND df.`name` IN ('" . implode("','", $ivalue) . "') "
								. " AND `dv`.`val_bool`= '1' AND dv.superceded IS NULL ";
						$res = mysql_query($query);
						while($row = mysql_fetch_assoc($res))
						{
							$larvolIds[] = $row['larvol_id'];
						}
						//echo "<br/>-->".
						$query = 'SELECT dv.val_int AS "int",dv.val_bool AS "bool",dv.val_varchar AS "varchar",dv.val_date AS "date",de.`value` AS "enum", '
						. ' dv.val_text AS "text",dcs.larvol_id AS "larvol_id",df.`type` AS "type",df.`name` AS "name",dc.`name` AS "category" '
						. ' FROM data_values dv '
						. ' LEFT JOIN data_cats_in_study dcs ON dv.studycat = dcs.id '
						. ' LEFT JOIN data_fields df ON dv.`field`= df.id '
						. ' LEFT JOIN data_enumvals de ON dv.val_enum = de.id '
						. ' LEFT JOIN data_categories dc ON df.category = dc.id '
						. ' WHERE dv.superceded IS NULL AND dv.`field` IN("' . implode('","', $fields) . '") AND larvol_id IN("' . implode('","', $larvolIds) . '" )'
						/*. ' ORDER BY '*/;
						
						$res = mysql_query($query);
						while($row = mysql_fetch_assoc($res))
						{
							$id = $row['larvol_id'];
							$place = $row['category'] . '/' . $row['name']; //fully qualified field name
							$val = $row[$row['type']];
							//check if we already have a value for this field and ID
							if(isset($result[$id][$place]))
							{
								//now we know the value will have to be an array
								//check if there are already multiple values here
								if($result[$id][$place] == 'NCT/start_date' || $result[$id][$place] == 'inactive_date')
								{
									$result[$id][$place] = $result[$id][$place];
								}
								else if($result[$id][$place] == 'NCT/condition' || $result[$id][$place] == 'NCT/intervention_name' 
								|| $result[$id][$place] == 'NCT/lead_sponsor')
								{
									$result[$id][$place] = $result[$id][$place] . ' ' . $val;
								}
								else if($result[$id][$place] == 'NCT/phase')
								{
									$result[$id][$place] = $val;
								}
								else
								{
										$result[$id][$place] = $result[$id][$place] . ', ' . $val;
								}
							}
							else
							{
								//No previous value, so this value goes in the slot by itself.
								$result[$id][$place] = $val;
							}
						}
						
						foreach($result as $rkey => $rvalue) 
						{ 
							$nctId = $rvalue['NCT/nct_id'];
							$dataset['trials'] = array();
							$dataset['matchedupms'] = array();
							
							if(!in_array($ikey, $sections))
							{
								$sections[$ikey] = $ikey;
							}
							
							//checking for updated and new trials
							$dataset['trials'] = getTrialUpdates($nctId, $rvalue['larvol_id'], NULL, '-1 week');
							$dataset['trials'] = array_merge($dataset['trials'], array('section' => $ikey));
							
							if(in_array($rvalue['NCT/overall_status'],$this->inactfilterarr)) 
							{
								$inactiveCount++;
							}
							else
							{
								$activeCount++;
							}
						
							if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) 
							{
								//unsetting value for field acroynm if it has a previous value and no current value
								if(isset($dataset['trials']['edited']['NCT/acronym']) && !isset($rvalue['NCT/acronym'])) 
								{
									unset($dataset['trials']['edited']['NCT/acronym']);
								}
								//unsetting value for field enrollment if the change is less than 20 percent
								if(isset($dataset['trials']['edited']['NCT/enrollment'])) 
								{ 
									$prevValue = substr($dataset['trials']['edited']['NCT/enrollment'],16);
									if(!getDifference($prevValue, $rvalue['NCT/enrollment'])) 
									{
										unset($dataset['trials']['edited']['NCT/enrollment']);
									}
								}
								//merge only if updates are found
								foreach($dataset['matchedupms'] as $k => $v) 
								{
									if(empty($v['edited']) || $v['new'] != 'y') 
									{
										unset($dataset['matchedupms'][$k]);
									}
								}
								if(!empty($dataset['trials']['edited']) || $dataset['trials']['new'] == 'y')
								{
									if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
									|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
									|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
									|| isset($_GET['c']) || isset($_GET['nlr'])) 
									{	
										$statusValues = implode(",", array_keys($this->allfilterarr, $rvalue['NCT/overall_status']));	
										if(array_key_exists($statusValues, $_GET)) 
										{
											$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{
										$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
									if(in_array($rvalue['NCT/overall_status'], $this->inactfilterarr)) 
									{
										if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
										|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
										{
											$statusValues = implode(",", array_keys($this->actfilterarr, $rvalue['NCT/overall_status']));
											if(array_key_exists($statusValues, $_GET)) 
											{
												$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
											}
										}
										else
										{
											$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{
										if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
										|| isset($_GET['av']) || isset($_GET['nlr'])) 
										{	
											$statusValues = implode(",", array_keys($this->inactfilterarr, $rvalue['NCT/overall_status']));
											if(array_key_exists($statusValues, $_GET)) 
											{
												$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
											}
										}
										else
										{
											$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
								}
							} 
							else 
							{	
								if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
								|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
								|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
								|| isset($_GET['c']) || isset($_GET['nlr'])) 
								{	
									$statusValues = implode(",", array_keys($this->allfilterarr, $rvalue['NCT/overall_status']));	
									if(array_key_exists($statusValues, $_GET)) 
									{
										$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{	
									$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								if(in_array($rvalue['NCT/overall_status'], $this->inactfilterarr)) 
								{
									if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
									|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
									{ 	
										$statusValues = implode(",", array_keys($this->inactfilterarr, $rvalue['NCT/overall_status']));
										if(array_key_exists($statusValues, $_GET)) 
										{
											$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{
										$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{	
									if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
									|| isset($_GET['av']) || isset($_GET['nlr'])) 
									{
										$statusValues = implode(",", array_keys($this->actfilterarr, $rvalue['NCT/overall_status']));
										if(array_key_exists($statusValues, $_GET)) 
										{
											$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
										}
									}
									else
									{	
										$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
							}	
						
							if(!in_array($rvalue['NCT/overall_status'],$this->actfilterarr) && !in_array($rvalue['NCT/overall_status'],$this->inactfilterarr)) 
							{ 
								$log 	= 'WARN: A new value "' . $rvalue['NCT/overall_status'] 
								. '" (not listed in the existing rule), was encountered for field overall_status.';
								$logger->warn($log);
								unset($log);
							}
						}
						
						$totinactivecount  = $inactiveCount + $totinactivecount;
						$totactivecount	= $activeCount + $totactivecount;
						$totalcount		= $totalcount + $inactiveCount + $activeCount; 
					}
				}
				
				$count = count($Trials[$this->type]);
				$this->commonControls($count, $totactivecount, $totinactivecount, $totalcount);
				echo '<br/><br clear="all" />';
				echo '<input type="hidden" name="p" value="' . $_GET['p'] . '"/><input type="hidden" name="a" value="' . $_GET['a'] . '"/>';
				
				$start 	= '';$last = '';$end = '';$pages = '';
		
				$start 	= ($page-1) * $this->results_per_page + 1;
				$end 	= $start + $this->results_per_page - 1;
				$pages 	= ceil($count / $this->results_per_page);
				$last 	= ($page * $this->results_per_page > $count) ? $count : $end;
				
				if($count > $this->results_per_page) 
				{
					$this->pagination($page, $start, $end, $last, $count, $_GET['p'], $_GET['v'], 'indexed', NULL);
				}
		
				$this->displayHeader();
				if($count > 0) 
				{
					displayContent($Trials[$this->type], $this->edited, NULL, $start, $last, $this->current_yr, $this->second_yr, $this->third_yr, 'collapse',
					 $TrialsInfo, $sections);
				}
				else
				{
					echo ('<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="norecord" align="left">No trials found.</td></tr>');
				}
				echo('</table><br/>');
				echo ('</form>');
			}
			else
			{
				$page = 1;
				if(isset($_GET['page'])) $page = mysql_real_escape_string($_GET['page']);
				if(!is_numeric($page)) die('non-numeric page');
				
				$TrialsInfo = array();
				$Ids = array();
				
				$areaName = mysql_fetch_assoc(mysql_query("SELECT `name`, `id` FROM `areas` WHERE id = '" . implode("','", $areaIds) . "' "));
				$Ids[] = $areaName['id'];
				
				echo ('</td><td class="result">Area: ' . htmlformat($areaName['name']) . '</td>' . '</tr></table>');
				echo('<br clear="all"/><br/>');

				$productName = mysql_fetch_assoc(mysql_query("SELECT `name`, `id` FROM `products` WHERE id = '" . implode("','", $productIds) . "' "));
				$TrialsInfo[0]['sectionHeader'] = $productName['name'];
				$Ids[] = $productName['id'];

				
				$larvolIds = array();
				$result = array();
				$sections = array();
				
				$Trials = array();
				$Trials['inactiveTrials'] = array();
				$Trials['activeTrials'] = array();
				$Trials['allTrials'] = array();
				
				$query = "SELECT dcs.larvol_id FROM `data_values` `dv` "
						. " LEFT JOIN `data_cats_in_study` `dcs` ON (`dcs`.`id` = `dv`.`studycat`) "
						. " LEFT JOIN `data_fields` `df` ON (`df`.`id` = `dv`.`field`) "
						. " LEFT JOIN `data_categories` `dc` ON (`dc`.`id` = `df`.`category`) "
						. " WHERE `dc`.`name` IN ('Products', 'Areas') "
						. " AND df.`name` IN ('" . implode("','", $Ids) . "') "
						. " AND `dv`.`val_bool`= '1' AND dv.superceded IS NULL ";
				$res = mysql_query($query);
				while($row = mysql_fetch_assoc($res))
				{
					$larvolIds[] = $row['larvol_id'];
				}
				
				$fields = array_map('highPass', $this->fid);
				//echo "<br/>-->".
				$query = 'SELECT dv.val_int AS "int",dv.val_bool AS "bool",dv.val_varchar AS "varchar",dv.val_date AS "date",de.`value` AS "enum", '
				. ' dv.val_text AS "text",dcs.larvol_id AS "larvol_id",df.`type` AS "type",df.`name` AS "name",dc.`name` AS "category" '
				. ' FROM data_values dv '
				. ' LEFT JOIN data_cats_in_study dcs ON dv.studycat = dcs.id '
				. ' LEFT JOIN data_fields df ON dv.`field`= df.id '
				. ' LEFT JOIN data_enumvals de ON dv.val_enum = de.id '
				. ' LEFT JOIN data_categories dc ON df.category = dc.id '
				. ' WHERE dv.superceded IS NULL AND dv.`field` IN("' . implode('","', $fields) . '") AND '
				. ' larvol_id IN("' . implode('","', $larvolIds) . '" )';
				
				$res = mysql_query($query);
				while($row = mysql_fetch_assoc($res))
				{
					$id = $row['larvol_id'];
					$place = $row['category'] . '/' . $row['name']; //fully qualified field name
					$val = $row[$row['type']];
					//check if we already have a value for this field and ID
					if(isset($result[$id][$place]))
					{
						//now we know the value will have to be an array
						//check if there are already multiple values here
						if($result[$id][$place] == 'NCT/start_date' || $result[$id][$place] == 'inactive_date')
						{
							$result[$id][$place] = $result[$id][$place];
						}
						else if($result[$id][$place] == 'NCT/condition' || $result[$id][$place] == 'NCT/intervention_name' 
						|| $result[$id][$place] == 'NCT/lead_sponsor')
						{
							$result[$id][$place] = $result[$id][$place] . ' ' . $val;
						}
						else if($result[$id][$place] == 'NCT/phase')
						{
							$result[$id][$place] = $val;
						}
						else
						{
							$result[$id][$place] = $result[$id][$place] . ', ' . $val;
						}
					}
					else
					{
						//No previous value, so this value goes in the slot by itself.
						$result[$id][$place] = $val;
					}
				}
				
				$sections[0] = 0;
				
				foreach($result as $rkey => $rvalue) 
				{ 
					$nctId = $rvalue['NCT/nct_id'];
					$dataset['trials'] = array();
					$dataset['matchedupms'] = array();
					
					//checking for updated and new trials
					$dataset['trials'] = getTrialUpdates($nctId, $rvalue['larvol_id'], NULL, '-1 week');
					$dataset['trials'] = array_merge($dataset['trials'], array('section' => 0));
					
					if(in_array($rvalue['NCT/overall_status'],$this->inactfilterarr)) 
					{
						$totinactivecount++;
					}
					else
					{
						$totactivecount++;
					}
					
					if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) 
					{
						//unsetting value for field acroynm if it has a previous value and no current value
						if(isset($dataset['trials']['edited']['NCT/acronym']) && !isset($rvalue['NCT/acronym'])) 
						{
							unset($dataset['trials']['edited']['NCT/acronym']);
						}
						
						//unsetting value for field enrollment if the change is less than 20 percent
						if(isset($dataset['trials']['edited']['NCT/enrollment'])) 
						{ 
							$prevValue = substr($dataset['trials']['edited']['NCT/enrollment'],16);
							if(!getDifference($prevValue, $rvalue['NCT/enrollment'])) 
							{
								unset($dataset['trials']['edited']['NCT/enrollment']);
							}
						}
						
						//merge only if updates are found
						foreach($dataset['matchedupms'] as $k => $v) 
						{
							if(empty($v['edited']) || $v['new'] != 'y') 
							{
								unset($dataset['matchedupms'][$k]);
							}
						}
						if(!empty($dataset['trials']['edited']) || $dataset['trials']['new'] == 'y')
						{
							if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
							|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
							|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
							|| isset($_GET['c']) || isset($_GET['nlr'])) 
							{	
								$statusValues = implode(",", array_keys($this->allfilterarr, $rvalue['NCT/overall_status']));	
								if(array_key_exists($statusValues, $_GET)) 
								{
									$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							else

							{
								$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							if(in_array($rvalue['NCT/overall_status'], $this->inactfilterarr)) 
							{
								if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
								|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
								{
									$statusValues = implode(",", array_keys($this->actfilterarr, $rvalue['NCT/overall_status']));
									if(array_key_exists($statusValues, $_GET)) 
									{
										$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							else
							{
								if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
								|| isset($_GET['av']) || isset($_GET['nlr'])) 
								{	
									$statusValues = implode(",", array_keys($this->inactfilterarr, $rvalue['NCT/overall_status']));
									if(array_key_exists($statusValues, $_GET)) 
									{
										$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
									}
								}
								else
								{
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
					} 
					else 
					{	
						if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
						|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
						|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
						|| isset($_GET['c']) || isset($_GET['nlr'])) 
						{	
							$statusValues = implode(",", array_keys($this->allfilterarr, $rvalue['NCT/overall_status']));	
							if(array_key_exists($statusValues, $_GET)) 
							{
								$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						else
						{	
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
						if(in_array($rvalue['NCT/overall_status'], $this->inactfilterarr)) 
						{
							if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
							|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
							{ 	
								$statusValues = implode(",", array_keys($this->inactfilterarr, $rvalue['NCT/overall_status']));
								if(array_key_exists($statusValues, $_GET)) 
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							else
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						else
						{	
							if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
							|| isset($_GET['av']) || isset($_GET['nlr'])) 
							{
								$statusValues = implode(",", array_keys($this->actfilterarr, $rvalue['NCT/overall_status']));
								if(array_key_exists($statusValues, $_GET)) 
								{
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							else
							{	
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}	
					
					if(!in_array($rvalue['NCT/overall_status'],$this->actfilterarr) && !in_array($rvalue['NCT/overall_status'],$this->inactfilterarr)) 
					{ 
						$log 	= 'WARN: A new value "' . $value['NCT/overall_status'] 
						. '" (not listed in the existing rule), was encountered for field overall_status.';
						$logger->warn($log);
						unset($log);
					}
				}
				
				$totalcount = $totinactivecount + $totactivecount; 
				$count = count($Trials[$this->type]);
				
				$this->commonControls($count, $totactivecount, $totinactivecount, $totalcount);
				
				echo '<br/><br clear="all" />';
				echo '<input type="hidden" name="p" value="' . $_GET['p'] . '"/><input type="hidden" name="a" value="' . $_GET['a'] . '"/>';
				
				$start 	= '';$last = '';$end = '';$pages = '';
				
				$start 	= ($page-1) * $this->results_per_page + 1;
				$end 	= $start + $this->results_per_page - 1;
				$pages 	= ceil($count / $this->results_per_page);
				$last 	= ($page * $this->results_per_page > $count) ? $count : $end;
				
				if($count > $this->results_per_page) 
				{
					$this->pagination($page, $start, $end, $last, $count, $_GET['p'], $_GET['v'], 'indexed', NULL);
				}
				
				$this->displayHeader();
				if($count > 0) 
				{
					displayContent($Trials[$this->type], $this->edited, NULL, $start, $last, $this->current_yr, $this->second_yr, $this->third_yr, 'collapse', 
					$TrialsInfo, $sections);
				}
				else
				{
					echo '<tr>' . '<td colspan="' . getColspanforNAUpm($this->loggedIn)  . '" class="notopbottomborder leftrightborderblue sectiontitles">'
					. $productName['name'] . '</td></tr>';
					echo ('<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="norecord" align="left">No trials found.</td></tr>');
				}
				echo('</table><br/>');
				echo ('</form>');
			}
		} 
		else 
		{
			$page = 1;
			if(isset($_GET['page'])) $page = mysql_real_escape_string($_GET['page']);
			if(!is_numeric($page)) die('non-numeric page');

			$totinactivecount = 0;
			$totactivecount = 0;
			
			$excel_params 	= array();
			$results_params = array();
			$ins_params 	= array();
			$link_expiry_date	= array();
			$naUpms = array();
			$TrialsInfo = array();
			$sections = array();
			
			if(isset($_GET['results'])) 
			{
				$results_params = explode(".", $_GET['results']);
				
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $results_params[0]);
				$rowlabel = trim($res['header']);
				$link_expiry_date[] = $res['expiry'];
				
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $results_params[1]);
				$columnlabel = trim($res['header']);
				$link_expiry_date[] = $res['expiry'];
				
				$TrialsInfo[0]['sectionHeader'] = $rowlabel;
				if($results_params[2] == '-1' || $results_params[2] == '-2') 
				{ 
					if($results_params[2] == '-2') 
					{
						$res = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', $results_params[3]);
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($res['result_set']))));
						$link_expiry_date[] = $res['expiry'];
					} 
					else
					{ 
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $results_params[3]);
						$link_expiry_date[] = $res['expiry'];
						
						$sp = new SearchParam();
						$sp->field = 'larvol_id';
						$sp->action = 'search';
						$sp->value = str_replace(',', ' OR ', $res['result_set']);
						$excel_params = array($sp);
					}
					
					if(isset($results_params[4])) 
					{
						$link_expiry_date[]	  = $res['expiry'];
						$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $results_params[4]);
						if(isset($_GET['v']) && $_GET['v'] == 1)
							$naUpms = explode('\n', $res['intervention_name']);
						else
							$naUpms = explode(',', $res['intervention_name']);
					}
				} 
				else 
				{
					if(strpos($results_params[2],'s') !== FALSE) 
					{
						$res = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', substr($results_params[2],1));
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($res['result_set']))));
						$link_expiry_date[] = $res['expiry'];
					} 
					else 
					{
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $results_params[2]);
						$link_expiry_date[] = $res['expiry'];
						
						$sp = new SearchParam();
						$sp->field = 'larvol_id';
						$sp->action = 'search';
						$sp->value = str_replace(',', ' OR ', $res['result_set']);
						$excel_params = array($sp);
					}
					
					if(isset($results_params[3])) 
					{
						$link_expiry_date[]	  = $res['expiry'];
						$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $results_params[3]);
						if(isset($_GET['v']) && $_GET['v'] == 1)
							$naUpms = explode('\n', $res['intervention_name']);
						else
							$naUpms = explode(',', $res['intervention_name']);
					}
				}
				$bomb = (isset($_GET['bomb'])) ? $_GET['bomb'] : '';
				$this->time_machine = $_GET['time'];
			} 
			else 
			{
				$excel_params 	= unserialize(gzinflate(base64_decode($_GET['params'])));
				
				$rowlabel 		= $excel_params['rowlabel'];
				$columnlabel 	= $excel_params['columnlabel'];
				$bomb			= $excel_params['bomb'];  //added for bomb indication
				$this->time_machine = $excel_params['time'];
				$naUpms	= $excel_params['upm'];
				
				$trialsInfo[0]['sectionHeader'] = $rowlabel;
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
					
				} else {
					$excel_params = $excel_params['params'];
				}
			
				if($excel_params === false)
				{
					$results = count($leadingIDs);
				}
			}
			
			if(isset($_GET['institution']) && $_GET['institution'] != '') 
			{
				array_push($this->fid, 'institution_type');

				$sp = new SearchParam();
				$sp->field 	= 'institution_type';

				$sp->action = 'search';
				$sp->value 	= $_GET['institution'];
				$ins_params = array($sp);
			}
			$params = array_merge($this->params, $excel_params, $ins_params);
			if($bomb != '') 
			{
				echo ('<span><img src="./images/' . $this->bomb_img_arr[$bomb] . '" alt="Bomb"  /></span>'
				. '&nbsp;This cell has a ' . $this->bomb_type_arr[$bomb] . ' <a href="./help/bomb.html">bomb</a>');
			}
			echo ('</td><td class="result">Area: ' . htmlformat($columnlabel) . '</td>' . '</tr></table>');
			echo('<br clear="all"/><br/>');		
			
			$arr = array();
			$larvolIds = array();
			
			$arrr = search($params,$this->fid,NULL,$this->time_machine);
			foreach($arrr as $k => $v) 
			{
				$nctId = $v['NCT/nct_id'];
				foreach($v as $kk => $vv) 
				{
					if(is_array($vv))
					{
						if($kk == 'NCT/condition' || $kk == 'NCT/intervention_name' || $kk == 'NCT/lead_sponsor')
						{
							$arr[$nctId][$kk] = implode(', ', $vv);
						}
						elseif($kk == 'NCT/start_date' || $kk == 'inactive_date')
						{
							$arr[$nctId][$kk] = $vv[0];
						}
						elseif($kk == 'NCT/phase')
						{
							$arr[$nctId][$kk] = end($vv);
						}
						else
						{
							$arr[$nctId][$kk] = implode(' ', $vv);
						}
					}
					else
					{
						$arr[$nctId][$kk] = $vv;
					}
				}
			}
			
			$sections[0] = 0;
			foreach($arr as $key => $value) 
			{ 
				$nctId = $value['NCT/nct_id'];
				$dataset['trials'] = array();
				$dataset['matchedupms'] = array();
				
				//checking for updated and new unmatched upms.
				$dataset['matchedupms'] = getCorrespondingUPM($nctId, $this->time_machine, $this->edited);
				
				//checking for updated and new trials
				$dataset['trials'] = getTrialUpdates($nctId, $value['larvol_id'], $this->time_machine, $this->edited);
				$dataset['trials'] = array_merge($dataset['trials'], array('section' => 0));
				
				
				if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) 
				{
					//unsetting value for field acroynm if it has a previous value and no current value
					if(isset($dataset['trials']['edited']['NCT/acronym']) && !isset($value['NCT/acronym'])) 
					{
						unset($dataset['trials']['edited']['NCT/acronym']);
					}
					//unsetting value for field enrollment if the change is less than 20 percent
					if(isset($dataset['trials']['edited']['NCT/enrollment']))
					{ 
						$prevValue = substr($dataset['trials']['edited']['NCT/enrollment'],16);
						if(!getDifference($prevValue, $value['NCT/enrollment'])) 
						{
							unset($dataset['trials']['NCT/enrollment']);
						}
					}
					//merge only if updates are found
					foreach($dataset['matchedupms'] as $k => $v) 
					{
						if(empty($v['edited']) || $v['new'] != 'y') 
						{
							unset($dataset['matchedupms'][$k]);
						}
					}
					//merge only if updates are found
					if(!empty($dataset['trials']['edited']) || $dataset['trials']['new'] == 'y')
					{	
						if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
						|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
						|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
						|| isset($_GET['c']) || isset($_GET['nlr'])) 
						{	
							$statusValues = implode(",", array_keys($this->allfilterarr, $value['NCT/overall_status']));	
							if(array_key_exists($statusValues, $_GET)) 
							{
								$Trials['allTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
						else
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
						}
						if(in_array($value['NCT/overall_status'], $this->inactfilterarr)) 
						{
							if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
							|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
							{
								$statusValues = implode(",", array_keys($this->inactfilterarr, $value['NCT/overall_status']));
								if(array_key_exists($statusValues, $_GET)) 
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
								}
							}
							else
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
						else
						{
							if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
							|| isset($_GET['av']) || isset($_GET['nlr'])) 
							{	
								$statusValues = implode(",", array_keys($this->actfilterarr, $value['NCT/overall_status']));
								if(array_key_exists($statusValues, $_GET)) 
								{
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
								}
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
					}
				} 
				else 
				{
					if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
					|| isset($_GET['av']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 

					|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
					|| isset($_GET['c']) || isset($_GET['nlr'])) 
					{	
						$statusValues = implode(",", array_keys($this->allfilterarr, $value['NCT/overall_status']));	
						if(array_key_exists($statusValues, $_GET)) 
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
						}
					}
					else
					{
						$Trials['allTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
					}
					if(in_array($value['NCT/overall_status'], $this->inactfilterarr)) 
					{ 
						if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
						|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) 
						{	
							$statusValues = implode(",", array_keys($this->inactfilterarr, $value['NCT/overall_status']));
							if(array_key_exists($statusValues, $_GET)) 
							{	
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
						else
						{
							$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
						}
					}
					else
					{
						if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
						|| isset($_GET['av']) || isset($_GET['nlr'])) 
						{	
							$statusValues = implode(",", array_keys($this->actfilterarr, $value['NCT/overall_status']));
							if(array_key_exists($statusValues, $_GET)) 
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
							}
						}
						else
						{
							$Trials['activeTrials'][] = array_merge($dataset['trials'], $value, $dataset['matchedupms']);
						}
					}
				}
				
				if(!in_array($value['NCT/overall_status'],$this->actfilterarr) && !in_array($value['NCT/overall_status'],$this->inactfilterarr)) 
				{ 
					$log 	= 'WARN: A new value "' . $value['NCT/overall_status'] . '" (not listed in the existing rule), was encountered for field overall_status.';
					$logger->warn($log);
					unset($log);
				}
				$larvolIds[] = $value['larvol_id'];
			}
			
			$count = count($Trials[$this->type]);
			
			if(isset($_GET['institution']) && $_GET['institution'] != '') 
			{
				$ins = unserialize(gzinflate(base64_decode(rawurldecode($_GET['instparams']))));
				$foundcount = ($ins['actcnt'] + $ins['inactcnt']);
				$this->commonControls($count, $ins['actcnt'], $ins['inactcnt'], ($ins['actcnt'] + $ins['inactcnt']));
			} 
			else 
			{	
				sort($larvolIds); 
				$totalcount = count($larvolIds);
				
				//getting count of active trials from a common function used in run_heatmap.php and here
				$totactivecount = getActiveCount($larvolIds, $this->time_machine);
				$totinactivecount = $totalcount - $totactivecount; 
				
				$foundcount = $totalcount;
				$instparams = rawurlencode(base64_encode(gzdeflate(serialize(array('actcnt' => $totactivecount,'inactcnt' => $totinactivecount)))));
				
				$this->commonControls($count, $totactivecount, $totinactivecount, ($totactivecount + $totinactivecount));
			}
			echo ('<br/><br clear="all" />');
			
			/*--------------------------------------------------------
			|Variables set for count when filtered by institution_type
			---------------------------------------------------------*/
			if(isset($_GET['instparams']) && $_GET['instparams'] != '') 
			{
				$instparams = $_GET['instparams'];
			} 
			
			if(isset($_GET['results'])) 
			{
				echo '<input type="hidden" name="results" value="' . $_GET['results'] . '"/><input type="hidden" name="time" value="' . $_GET['time'] . '"/>';
				if(isset($_GET['bomb']))
				{
					echo ('<input type="hidden" name="bomb" value="' . $_GET['bomb'] . '" />');
				}
			}
			else 
			{
				echo ('<input type="hidden" name="params" value="' . $_GET['params'] . '"/>'
						. '<input type="hidden" name="leading" value="' . $_GET['leading'] . '"/>');
			}
			echo ('<input type="hidden" name="instparams" value="' . $instparams . '" />');
			
			if(isset($_GET['v']))
			{
				echo ('<input type="hidden" name="v" value="' . $_GET['v'] . '" />');
			}
				
			$start 	= '';$last = '';$end = '';$pages = '';
			
			$start 	= ($page-1) * $this->results_per_page + 1;
			$end 	= $start + $this->results_per_page - 1;
			$pages 	= ceil($count / $this->results_per_page);
			$last 	= ($page * $this->results_per_page > $count) ? $count : $end;

			if($count > $this->results_per_page) 
			{
				if(isset($_GET['results']))
					$this->pagination($page,  $start, $end, $last, $count, $_GET['results'], $_GET['time'], 'normal', NULL);
				else 
					$this->pagination($page,  $start, $end, $last, $count, $_GET['params'], $_GET['leading'], 'normal', NULL);
			}
			$this->displayHeader();
			
			if(isset($naUpms) && !empty($naUpms)) 
			{
				$TrialsInfo[0]['naUpms'] = $this->getNonAssocUpm($naUpms, $rowlabel);
				if(isset($TrialsInfo[0]['naUpms']) && !empty($TrialsInfo[0]['naUpms']))
				{
					$naUpmsDisplayStyle = 'expand';
					$naUpmIndex = preg_replace('/[^a-z]/i', '', $TrialsInfo[0]['sectionHeader']);
					$naUpmIndex = substr($naUpmIndex, 0, 7);
					
					echo '<tr class="trialtitles">'
						. '<td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
						. 'style="border-bottom:1px solid blue;background-image: url(\'images/down.png\');'
						. 'background-repeat: no-repeat;background-position:left center;"'
						. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
						. $TrialsInfo[0]['sectionHeader'] . '</td></tr>'
						. displayNAUpms($TrialsInfo[0]['naUpms'], $naUpmIndex , $currentYear, $secondYear, $thirdYear);
					
					$TrialsInfo = array();
					//unset($TrialsInfo);
				}
				else
				{
					echo '<tr><td colspan="' . getColspanforNAUpm($this->loggedIn)  . '" class="notopbottomborder leftrightborderblue sectiontitles">'
						. $TrialsInfo[0]['sectionHeader'] . '</td></tr>';
						//. '<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="norecord" align="left">No trials found</td></tr>';
					$TrialsInfo = array();
				}
			}
			
			if($count > 0) 
			{
				displayContent($Trials[$this->type], $this->edited, $this->time_machine, $start, $last, $this->current_yr, $this->second_yr, 
				$this->third_yr, $naUpmsDisplayStyle, $TrialsInfo, $sections);
			}
			elseif(!isset($_GET['chkOnlyUpdated']))
			{
				echo '<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="norecord" align="left">No trials found</td></tr>';
			}
			
			echo('</table><br/>');
			echo ('<input type="hidden" id="upmstyle" name="upmstyle" value="'.$naUpmsDisplayStyle.'" />');
			echo ('</form>');
			
			if($foundcount > 0) 
			{
				$this->downloadOptions($count, $foundcount, $Trials);
				echo ('<br/>');
			}
			
			//Expiry feature for new link method
			if(!empty($link_expiry_date)) 
			{
				$link_expiry_date = array_unique(array_filter($link_expiry_date));
				usort($link_expiry_date, "cmpdate");
				if(!empty($link_expiry_date)) 
				{
					if($this->loggedIn) 
					{
						echo '<span style="font-size:10px;color:red;">Expires on: ' . $link_expiry_date[0]  . '</span>';
					}
					$ids = explode(".", $_GET['results']);
					if(($link_expiry_date[0] < date('Y-m-d', $now)) || ($link_expiry_date[0] < date('Y-m-d',strtotime('+1 week',$now)))) 
					{
					
						$query = "UPDATE `rpt_ott_header` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $ids[0] . "' ";
						$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for row header' . "\n" . $query);
						
						$query = "UPDATE `rpt_ott_header` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $ids[1] . "' ";
						$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for col header' . "\n" . $query);
						
						if(strpos($ids[2],'s') !== FALSE) 
						{
							$query = "UPDATE `rpt_ott_searchdata` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" 
							. $ids[2] . "' ";
						} else {
							$query = "UPDATE `rpt_ott_trials` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" 
							. $ids[2] . "' ";
						}
						$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for trials result set' . "\n" . $query);
						
						if(isset($ids[3]) && $ids[3] != '') {
							$query = "UPDATE `rpt_ott_upm` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $ids[3] . "' ";
							$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for upms' . "\n" . $query);
						}
						
					}
				}
			}
		}
	}
	
	function displayHeader() 
	{
		echo ('<table width="100%" border="0" cellpadding="4" cellspacing="0" class="manage">'
			 . '<tr>'
			 . (($this->loggedIn) ? '<th rowspan="2" style="width:50px;">ID</th>' : '' )
			 . '<th rowspan="2" style="width:230px;">Title</th>'
			 . '<th style="width:28px;" title="Black: Actual&nbsp;&nbsp;Gray: Anticipated&nbsp;&nbsp;Red: Change greater than 20%">'
			 . '<a target="_self" href="javascript:void(0);" onclick="javascript:doSorting(\'en\');">N</a></th>'
			 . '<th rowspan="2" style="width:32px;" title="&quot;EU&quot; = European Union&nbsp;&quot;ROW&quot; = Rest of World">Region</th>'
			 . '<th rowspan="2" style="width:110px;">Interventions</th>'
			  . '<th rowspan="2" style="width:70px;">Sponsor</th>'
			 . '<th style="width:105px;">'
			 . '<a target="_self" href="javascript:void(0);" onclick="javascript:doSorting(\'os\');">Status</a></th>'
			 . '<th rowspan="2" style="width:110px;">Conditions</th>'
			 . '<th style="width:25px;" title="MM/YY">'
			 . '<a target="_self" href="javascript:void(0);" onclick="javascript:doSorting(\'sd\');">Start</a></th>'
			 . '<th style="width:25px;" title="MM/YY">'
			 . '<a target="_self" href="javascript:void(0);" onclick="javascript:doSorting(\'ed\');">End</a></th>'
			 . '<th style="width:22px;">'
			 . '<a target="_self" href="javascript:void(0);" onclick="javascript:doSorting(\'ph\');">Ph</a></th>'
			 . '<th rowspan="2" style="width:12px;padding:4px;"><div class="box_rotate">result</div></th>'
			 . '<th colspan="36" style="width:72px;"><div>&nbsp;</div></th>'
			 . '<th colspan="3" style="width:10px;padding:0px;border-left:0px;" class="rightborder"><div>&nbsp;</div></th></tr>'
			 . '<tr class="secondrow"><th>');
		
		if(array_key_exists('en', $this->sortimg)) {
		
			$img = $this->sortimg['en'];
			$img_style = array_search('en-' . $img, $this->sortorder);
			echo "<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		echo '</th><th>';
		
		if(array_key_exists('os', $this->sortimg)) {
		
			$img = $this->sortimg['os'];
			$img_style = array_search('os-' . $img, $this->sortorder);
			echo "<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		echo '</th><th>';
		
		if(array_key_exists('sd', $this->sortimg)) {
		
			$img = $this->sortimg['sd'];
			$img_style = array_search('sd-' . $img, $this->sortorder);
			echo "<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		echo '</th><th>';
		
		if(array_key_exists('ed', $this->sortimg)) {
		
			$img = $this->sortimg['ed'];
			$img_style = array_search('ed-' . $img, $this->sortorder);
			echo "<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		echo '</th><th>';
		
		if(array_key_exists('ph', $this->sortimg)) {
		

			$img = $this->sortimg['ph'];
			$img_style = array_search('ph-' . $img, $this->sortorder);
			echo "<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		
		echo ('</th><th colspan="12" style="width:24px;">' . $this->current_yr . '</th>'
			 . '<th colspan="12" style="width:24px;">' . $this->second_yr . '</th>'
			 . '<th colspan="12" style="width:24px;">' . $this->third_yr . '</th>'
			 . '<th colspan="3" style="width:10px;" class="rightborder">+</th></tr>');

	}
	
	function getNonAssocUpm($naUpms, $trialheader) 
	{
		global $now;

		$upm_arr = array();$record_arr = array();$unmatched_upm_arr = array();
		$record_arr = getNonAssocUpmRecords($naUpms, $this->time_machine, $this->edited);
		//$record_arr = getUnmatchedUpmChanges($upm_arr, $this->time_machine, $this->edited);
		
		foreach($record_arr as $key => $val) 
		{
			if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) 
			{
			 	if(!empty($val['edited']) && $val['new'] == 'n') 
				{
					if( ($val['event_description'] == $val['edited']['event_description']) && ($val['event_link'] == $val['edited']['event_link']) && 
					($val['event_type'] == $val['edited']['event_type']) && ($val['start_date'] == $val['edited']['start_date']) && 
					($val['start_date_type'] == $val['edited']['start_date_type']) && ($val['end_date'] == $val['edited']['end_date']) && 
					($val['end_date_type'] == $val['edited']['end_date_type']) )
					{ 
						unset($record_arr[$key]);
					} 
				}
				else if(empty($val['edited']) && $val['new'] == 'n') 
				{
					unset($record_arr[$key]);
				}
			} 		
		}
		
		return $record_arr;
	}
	
	function downloadOptions($showncount, $foundcount, $Trials) 
	{
		echo ('<div style="height:100px;"><div class="drop new" style="margin:0px"><div class="newtext">Download Options</div>'
			. '<form  id="frmDOptions" name="frmDOptions" method="post" target="_self" action="">'
			. '<input type="hidden" name="excelInput" id="excelInput" value="" />'
			. '<ul><li><label>Number of Studies: </label></li>'
			. '<li><select id="dOption" name="dOption">'
			. '<option value="shown" selected="selected">' . $showncount . ' Shown Studies</option>'
			. '<option value="all">' . $foundcount . ' Found Studies</option></select></li>'
			. '<li><label>Which Fields: </label></li>'
			. '<li><select id="wFields" name="wFields" disabled="disabled">'
			. '<option selected="selected">Shown Fields</option><option>All Fields</option></select></li>'
			. '<li><label>Which Format: </label></li><li><select id="wFormat" name="wFormat">'

			. '<option value="excel" selected="selected">Excel</option><option value="xml">XML</option><option value="pdf">PDF</option></select></li></ul>'
			. '<input type="submit" id="btnDownload" name="btnDownload" onclick="javascript:checkformat()" value="Download File" style="margin-left:8px;"  />');
		
			foreach($_GET as $ke => $va)
			{
				echo '<input type="hidden" name="' . $ke . '" value="' . $va . '" />';
			}
			echo ( '</form></div></div>');
	}
}

function getColspanforNAUpm($loggedIn) 
{
	return $colspan = (($loggedIn) ? 51 : 50 );
}

function displayNAUpms($record_arr = array(), $sectionHeader, $currentYear, $secondYear, $thirdYear)
{
	$db = new DatabaseManager();
	$upm_string = '';
	if(!empty($record_arr)) 
	{

		$cntr = 0;
		foreach($record_arr as $key => $val) 
		{
		
			$title = '';$attr = '';$result_image = '';
			$class = 'class = "upms ' . $sectionHeader . '" ';
			$title_link_color = 'color:#000;';
			$date_style = 'color:gray;';
			$upm_title = 'title="' . htmlformat($val['event_description']) . '"';
			
			if($cntr%2 == 1) 
			{
				$row_type_one = 'alttitle';
				$row_type_two = 'altrow';
			} 
			else 
			{
				$row_type_one = 'title';
				$row_type_two = 'row';
			}	
			
			//Highlighting the whole row in case of new trials
			if($val['new'] == 'y')
			{
				$class = 'class="upms newtrial ' . $sectionHeader . '" ';
			}
			
			$upm_string .= '<tr ' . $class . ' style="background-color:#000;">';
			
			if($db->loggedIn())
			{
				if($val['new'] == 'y') 
				{
					$title_link_color = 'color:#FF0000;';
					$title = ' title = "New record" ';
				}
				$upm_string .= '<td style="text-align:left;" ' . $title .
				 '><a style="' . $title_link_color . '" href="upm.php?search_id=' . $val['id'] . '">' . $val['id'] . '</a></td>';
			}
			
			//event description column
			if(!empty($val['edited']) && $val['edited']['event_description'] != $val['event_description']) 
			{
				$title_link_color = 'color:#FF0000;';$attr = ' highlight'; 
				if($val['edited']['event_description'] != '' || $val['edited']['event_description'] != NULL)
					$title = ' title="Previous value: '. $val['edited']['event_description'] . '" '; 
				else
					$title = ' title="No Previous value" ';
					
			} 
			else if($val['new'] == 'y') 
			{
				$title_link_color = 'color:#FF0000;';
				$title = ' title = "New record" ';
			}
			
			$upm_string .= '<td colspan="5" class="' . $row_type_one .  $attr . ' titleupm titleupmodd txtleft" ' . $title . '><div class="rowcollapse">';
			if($val['event_link'] != NULL && $val['event_link'] != '') 
			{
				$upm_string .= '<a style="' . $title_link_color . '" href="' . $val['event_link'] . '">' . $val['event_description'] . '</a>';
			} 
			else 
			{
				$upm_string .= $val['event_description'];
			}
			$upm_string .= '</div></td>';
			
			//status column
			$upm_string .= '<td class="' . $row_type_two . ' titleupmodd"><div class="rowcollapse">';
			if($val['result_link'] != NULL && $val['result_link'] != '') 
			{
				$upm_string .= 'Occurred';
			} 
			else 
			{
				if($val['end_date'] == NULL || $val['end_date'] == '' || $val['end_date'] == '0000-00-00') 
				{
					$upm_string .= 'Cancelled';
				} 
				else if($val['end_date'] < date('Y-m-d', $now)) 
				{
					$upm_string .= 'Pending';
				} 
				else if($val['end_date'] > date('Y-m-d', $now)) 
				{
					$upm_string .= 'Upcoming';
				}
			}
			$upm_string .= '</div></td>';
			
			//event type column
			$title = '';$attr = '';	
			if(!empty($val['edited']) && $val['edited']['event_type'] != $val['event_type'])
			{
				$attr = ' highlight'; 
				if($val['edited']['event_type'] != '' && $val['edited']['event_type'] != NULL)
					$title = ' title="Previous value: '. $val['edited']['event_type'] . '" '; 
				else
					$title = ' title="No Previous value" ';
					
			} 
			else if($val['new'] == 'y') 
			{
				$title = ' title = "New record" ';
			}
			$upm_string .= '<td class="' . $row_type_two . $attr . ' titleupmodd" ' . $title 
							. '><div class="rowcollapse">' . $val['event_type'] . ' Milestone</div></td>';
			
			
			//start date column
			$title = '';$attr = '';	
			if(!empty($val['edited']) && $val['edited']['start_date'] != $val['start_date'])
			{
				$attr = ' highlight';$date_style = 'color:#973535;'; 
				if($val['edited']['start_date'] != '' && $val['edited']['start_date'] != NULL)
					$title = ' title="Previous value: '. $val['edited']['start_date'] . '" '; 
				else 
					$title = ' title="No Previous value" ';
					
			} 
			else if($val['new'] == 'y')
			{
				$title = ' title = "New record" ';
				$date_style = 'color:#973535;'; 
			}
			if(!empty($val['edited']) && $val['edited']['start_date_type'] != $val['start_date_type'])
			{
				$attr = ' highlight';$date_style = 'color:#973535;';
				if($val['edited']['start_date_type'] != '' && $val['edited']['start_date_type'] != NULL) 
				{
					$title = ' title="Previous value: ' . 
					(($val['edited']['start_date'] != $val['start_date']) ? $val['edited']['start_date'] : '' ) 
					. ' ' .$val['edited']['start_date_type'] . '" '; 
				} 
				else 
				{
					$title = ' title="No Previous value" ';
				}
			} 
			else if($val['new'] == 'y') 
			{
				$title = ' title = "New record" ';
				$date_style = 'color:#973535;'; 
			}
							
			$upm_string .= '<td  class="' . $row_type_two . $attr . ' titleupmodd txtleft" ' . $title . '><div class="rowcollapse">';
			if($val['start_date_type'] == 'anticipated') 
			{
			$upm_string .= '<span style="font-weight:bold;' . $date_style . '">' 
			. (($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00') ? date('m/y',strtotime($val['start_date'])) : '' )   
			. '</span>';
			} 
			else 
			{
				$upm_string .= 
				(($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00') ? date('m/y',strtotime($val['start_date'])) : '' );
			}
			$upm_string .= '</div></td>';
			
			
			//end date column
			$title = '';$attr = '';	
			if(!empty($val['edited']) && $val['edited']['end_date'] != $val['end_date'])
			{
				$attr = ' highlight';$date_style = 'color:#973535;';
				if($val['edited']['end_date'] != '' && $val['edited']['end_date'] != NULL)
					$title = ' title="Previous value: '. $val['edited']['end_date'] . '" '; 
				else 
					$title = ' title="No Previous value" ';
			} 
			else if($val['new'] == 'y') 
			{
				$title = ' title = "New record" ';
				$date_style = 'color:#973535;'; 
			}
			if(!empty($val['edited']) && $val['edited']['end_date_type'] != $val['end_date_type'])
			{
				$attr = ' highlight';$date_style = 'color:#973535;'; 
				if($val['edited']['end_date_type'] != '' && $val['edited']['end_date_type'] != NULL) 
				{
					$title = ' title="Previous value: ' . 
					(($val['edited']['end_date'] != $val['end_date']) ? $val['edited']['end_date'] : '' ) 
					. ' ' . $val['edited']['end_date_type'] . '" '; 
				} 
				else 
				{
					$title = ' title="No Previous value" ';
				}
			} 
			else if($val['new'] == 'y') 
			{
				$title = ' title = "New record" ';
				$date_style = 'color:#973535;'; 
			}
			
			$upm_string .= '<td class="' . $row_type_two . $attr . ' titleupmodd txtleft" ' . $title . '><div class="rowcollapse">';
			if($val['end_date_type'] == 'anticipated') 
			{
				$upm_string .= '<span style="font-weight:bold;' . $date_style . '">' 
				. (($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') ? date('m/y',strtotime($val['end_date'])) : '' ) 
				. '</span>';
			} 
			else 
			{
				$upm_string .=  
				(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') ? date('m/y',strtotime($val['end_date'])) : '');
			}	
			
			$upm_string .= '</div></td><td class="titleupmodd"><div class="rowcollapse"></div></td>';
			
			
			//result column
			$upm_string .= '<td class="titleupmodd"><div class="rowcollapse">';
			if(!empty($val['edited']) && ($val['result_link'] != $val['edited']['result_link'])) 
			{
				if($val['result_link'] != '' && $val['result_link'] != NULL) 
				{
					$result_image = (($val['event_type'] == 'Clinical Data') ? 'diamond' : 'checkmark' );
					$upm_string .= '<div ' . $upm_title . '><a href="' . $val['result_link'] . '" style="color:#000;">'
					. '<img src="images/red-' . $result_image . '.png" alt="' . $result_image . '" style="padding-top: 3px;" border="0" /></a></div>';
				}
			} 
			else 
			{
				if($val['result_link'] != '' && $val['result_link'] != NULL) 
				{
					$result_image = (($val['event_type'] == 'Clinical Data') ? 'diamond' : 'checkmark' );
					$upm_string .= '<div ' . $upm_title . '><a href="' . $val['result_link'] . '" style="color:#000;">'
					. '<img src="images/black-' . $result_image . '.png" alt="' . $result_image . '" style="padding-top: 3px;" border="0" /></a></div>';
				}
			}
			
			if(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') && 
			($val['end_date'] < date('Y-m-d', $now)) && ($val['result_link'] == NULL || $val['result_link'] == ''))
			{
				$upm_string .= '<div ' . $upm_title . '><img src="images/hourglass.png" alt="hourglass" border="0" /></div>';
			}
			$upm_string .= '</div></td>';
			
			
			//gnatt chart
			$upm_string .= getUPMChart(date('m',strtotime($val['start_date'])), date('Y',strtotime($val['start_date'])), 
			date('m',strtotime($val['end_date'])), date('Y',strtotime($val['end_date'])), $currentYear, $secondYear, $thirdYear, 
			$val['start_date'], $val['end_date'], $val['event_link'], $upm_title);
	
	
			$upm_string .= '</tr>';
			
			$cntr++;
		}
	} 
	
	return $upm_string;
}
	
function displayContent($trials, $edited, $gentime, $start, $end, $currentYear, $secondYear, $thirdYear, $naUpmsDisplayStyle, $trialsInfo = array(), 
$sections = array()) 
{	
	$db = new DatabaseManager();
	$fieldList 	= array('Enrollment' => 'NCT/enrollment', 'Region' => 'region', 'Interventions' => 'NCT/intervention_name', 
						'Sponsor' => 'NCT/lead_sponsor', 'Status' => 'NCT/overall_status', 'Conditions' => 'NCT/condition', 
						'Study Dates' => 'NCT/start_date', 'Phase' => 'NCT/phase');
	$phaseValues = array('N/A'=>'#BFBFBF', '0'=>'#00CCFF', '0/1'=>'#99CC00', '1'=>'#99CC00', '1a'=>'#99CC00', '1b'=>'#99CC00', '1a/1b'=>'#99CC00', 
						'1c'=>'#99CC00', '1/2'=>'#FFFF00', '1b/2'=>'#FFFF00', '1b/2a'=>'#FFFF00', '2'=>'#FFFF00', '2a'=>'#FFFF00', '2a/2b'=>'#FFFF00', 
						'2a/b'=>'#FFFF00', '2b'=>'#FFFF00', '2/3'=>'#FF9900', '2b/3'=>'#FF9900','3'=>'#FF9900', '3a'=>'#FF9900', '3b'=>'#FF9900', 
						'3/4'=>'#FF0000', '3b/4'=>'#FF0000', '4'=>'#FF0000');	
	
	$start = $start - 1;
	$section = '';
	for($i=$start; $i<$end; $i++) 
	{ 
		if($i%2 == 1)  
			$rowOneType = 'alttitle';
		else
			$rowOneType = 'title';
		
		$rowspan = 1;
		$enrollStyle = 'color:gray;';
		$titleLinkColor = '#000000;';
	
		$sectionKey = $trials[$i]['section'];
		
		if(isset($trials[$i]['matchedupms']))  
			$rowspan = count($trials[$i]['matchedupms'])+1; 
		
		foreach($trialsInfo as $tkey => $tvalue)
		{
			if(isset($sections[$tkey]))
			{
				if($section !== $sectionKey && $sectionKey === $tkey)
				{
					if(
					isset($tvalue['naUpms']) 
					&& !empty($tvalue['naUpms']) 
					&& ((isset($_GET['type']) && $_GET['type'] == 'col') || (isset($_GET['cparams']) && isset($_GET['colupm'])))
					) 
					{	
						if($naUpmsDisplayStyle == 'expand')
							$image = 'down';
						else
							$image = 'up';
						
						$naUpmIndex = preg_replace('/[^a-z]/i', '', $tvalue['sectionHeader']);
						$naUpmIndex = substr($naUpmIndex, 0, 7);
						echo '<tr class="trialtitles">'
							. '<td colspan="' . getColspanforNAUpm($db->loggedIn()) . '" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
							. 'style="border-bottom:1px solid blue;background-image: url(\'images/' . $image 
							. '.png\');background-repeat: no-repeat;background-position:left center;"'
							. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
							. $tvalue['sectionHeader'] . '</td></tr>'
							. displayNAUpms($tvalue['naUpms'], $naUpmIndex , $currentYear, $secondYear, $thirdYear);
					}
					else
					{
						echo '<tr><td colspan="' . getColspanforNAUpm($db->loggedIn())  . '" class="notopbottomborder leftrightborderblue sectiontitles">'
								. $tvalue['sectionHeader'] . '</td></tr>';
					}
				}
			}
			else if($section !== $sectionKey && $tkey < $sectionKey && $tkey > $section)
			{
				if($section !== $sectionKey)
				{
					if(
					isset($tvalue['naUpms']) 
					&& !empty($tvalue['naUpms']) 
					&& ((isset($_GET['type']) && $_GET['type'] == 'col') || (isset($_GET['cparams']) && isset($_GET['colupm'])))
					) 
					{	
						if($naUpmsDisplayStyle == 'expand')
							$image = 'down';
						else
							$image = 'up';
						
						$naUpmIndex = preg_replace('/[^a-z]/i', '', $tvalue['sectionHeader']);
						$naUpmIndex = substr($naUpmIndex, 0, 7);
						echo '<tr class="trialtitles">'
							. '<td colspan="' . getColspanforNAUpm($db->loggedIn()) . '" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
							. 'style="border-bottom:1px solid blue;background-image: url(\'images/' . $image 
							. '.png\');background-repeat: no-repeat;background-position:left center;"'
							. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
							. $tvalue['sectionHeader'] . '</td></tr>'
							. displayNAUpms($tvalue['naUpms'], $naUpmIndex , $currentYear, $secondYear, $thirdYear);
						echo '<tr><td colspan="' . getColspanforNAUpm($db->loggedIn()) . '" class="norecord" align="left">No trials found</td></tr>';
					}
					/*else
					{
						echo '<tr><td colspan="' . getColspanforNAUpm($db->loggedIn())  . '" class="notopbottomborder leftrightborderblue sectiontitles">'
								. $tvalue['sectionHeader'] . '</td></tr>';
						echo '<tr><td colspan="' . getColspanforNAUpm($db->loggedIn()) . '" class="norecord" align="left">No trials found</td></tr>';
					}*/
				}
			}	
		}
			
		//row starts  
		echo '<tr ' . (($trials[$i]['new'] == 'y') ? 'class="newtrial" ' : ''). '>';  
		
			//nctid column
			if($db->loggedIn()) 
			{ 
				echo '<td class="' . $rowOneType . '" rowspan="' . $rowspan . '" ' . (($trials[$i]['new'] == 'y') ? 'title="New record"' : '') 
				. ' ><a style="color:' . $titleLinkColor . '" href="http://clinicaltrials.gov/ct2/show/' 
				. padnct($trials[$i]['NCT/nct_id']) . '">' . $trials[$i]['NCT/nct_id'] . '</a></td>';
			}

			//acroynm and title column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/brief_title', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/brief_title'];
				$titleLinkColor = '#FF0000;';
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
				$titleLinkColor = '#FF0000;';
			}				
			echo '<td rowspan="' . $rowspan . '" class="' . $rowOneType . ' ' . $attr . '"><div class="rowcollapse">'
					. '<a style="color:' . $titleLinkColor . '" href="http://clinicaltrials.gov/ct2/show/' . padnct($trials[$i]['NCT/nct_id']) . '">'; 
			if(isset($trials[$i]['NCT/acronym']) && $trials[$i]['NCT/acronym'] != '') 
			{
				echo '<b>' . htmlformat($trials[$i]['NCT/acronym']) . '</b>&nbsp;' . htmlformat($trials[$i]['NCT/brief_title']);
			} 
			else 
			{
				echo htmlformat($trials[$i]['NCT/brief_title']);
			}
			echo '</a></div></td>';
			
			//enrollment column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/enrollment',$trials[$i]['edited']) 
				&& (getDifference(substr($trials[$i]['edited']['NCT/enrollment'],16), $trials[$i]['NCT/enrollment']))) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/enrollment'];
				$enrollStyle = 'color:#973535;';
			}
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
				$enrollStyle = 'color:#973535;';
			}
			echo '<td nowrap="nowrap" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">';
			if($trials[$i]["NCT/enrollment_type"] != '') 
			{
			
				if($trials[$i]["NCT/enrollment_type"] == 'Anticipated') 
				{ 
					echo '<span style="font-weight:bold;' . $enrollStyle . '">' . $trials[$i]["NCT/enrollment"] . '</span>';
					
				}
				else if($trials[$i]["NCT/enrollment_type"] == 'Actual') 
				{
					echo $trials[$i]["NCT/enrollment"];
				} 
				else 
				{ 
					echo $trials[$i]["NCT/enrollment"] . ' (' . $trials[$i]["NCT/enrollment_type"] . ')';
				}
			} 
			else 
			{
				echo $trials[$i]["NCT/enrollment"];
			}
			echo '</div></td>';				


			//region column
			$attr = ' ';
			if($trials[$i]['new'] == 'y') 
				$attr = 'title="New record"';
			
			echo '<td class="' . $rowOneType . '" rowspan="' . $rowspan . '" ' . $attr . '>'
			. '<div class="rowcollapse">' . $trials[$i]['region'] . '</div></td>';

			
			//intervention name column
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/intervention_name', $trials[$i]['edited']))
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/intervention_name'];
			} 
			else if($trials[$i]['new'] == 'y')
			{
				$attr = '" title="New record';
			}
			echo '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
				. '<div class="rowcollapse">' . $trials[$i]['NCT/intervention_name'] . '</div></td>';


			//collaborator and sponsor column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && (array_key_exists('NCT/collaborator', $trials[$i]['edited']) 
			|| array_key_exists('NCT/lead_sponsor', $trials[$i]['edited']))) 
			{
				$attr = ' highlight" title="';
				if(array_key_exists('NCT/lead_sponsor', $trials[$i]['edited']))
				{
					$attr .= $trials[$i]['edited']['NCT/lead_sponsor'] . ' ';
				}
				if(array_key_exists('NCT/collaborator', $trials[$i]['edited'])) 
				{
					$attr .= $trials[$i]['edited']['NCT/collaborator'];
					$enrollStyle = 'color:#973535;';
				}
				$attr .= '';
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			echo '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
				. '<div class="rowcollapse">' . $trials[$i]['NCT/lead_sponsor'] . ' <span style="' . $enrollStyle . '"> ' 
				. $trials[$i]["NCT/collaborator"] . ' </span></div></td>';


			//overall status column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/overall_status', $trials[$i]['edited'])) 
			{
				$attr = 'class="highlight ' . $rowOneType . ' " title="' . $trials[$i]['edited']['NCT/overall_status'] . '" ';
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = 'title="New record" class="' . $rowOneType . '"' ;
			} 
			else 
			{
				$attr = 'class="' . $rowOneType . '"';
			}
			echo '<td ' . $attr . ' rowspan="' . $rowspan . '">'  
				.'<div class="rowcollapse">' . $trials[$i]['NCT/overall_status'] . '</div></td>';
			
			
			//condition column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/condition', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/condition'];
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			echo '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
				. '<div class="rowcollapse">' . $trials[$i]['NCT/condition'] . '</div></td>';
				
			
			//start date column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/start_date', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/start_date'] ;
			} 

			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			echo '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '" ><div class="rowcollapse">'; 
			if($trials[$i]["NCT/start_date"] != '' && $trials[$i]["NCT/start_date"] != NULL && $trials[$i]["start_date"] != '0000-00-00') 
			{
				echo date('m/y',strtotime($trials[$i]["NCT/start_date"]));
			} 
			else 
			{
				echo '&nbsp;';
			}
			echo '</div></td>';
			
			
			//end date column
			$attr = '';
			if($trials[$i]['new'] == 'y') 
			{
				$attr = ' title="New record" ';
			}	
			echo '<td rowspan="' . $rowspan . '" class="' . $rowOneType . '" ' . $attr . '><div class="rowcollapse">'; 
			if($trials[$i]["inactive_date"] != '' && $trials[$i]["inactive_date"] != NULL && $trials[$i]["inactive_date"] != '0000-00-00') 
			{
				echo date('m/y',strtotime($trials[$i]["inactive_date"]));
			} 
			else 
			{
				echo '&nbsp;';
			}
			echo '</div></td>';
				
										
			//phase column
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/phase', $trials[$i]['edited'])) 
			{
				$attr = 'class="highlight" title="' . $trials[$i]['edited']['NCT/phase'] . '" ';
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = 'title="New record"';
			}
			if($trials[$i]['NCT/phase'] == 'N/A' || $trials[$i]['NCT/phase'] == '' || $trials[$i]['NCT/phase'] === NULL)
			{
				$phase = 'N/A';
				$phaseColor = $phaseValues['N/A'];
			}
			else
			{
				$phase = str_replace('Phase ', '', trim($trials[$i]['NCT/phase']));
				$phaseColor = $phaseValues[$phase];
			}
			echo '<td rowspan="' . $rowspan . '" style="background-color:' . $phaseColor . ';" ' . $attr . '>' 
				. '<div class="rowcollapse">' . $phase . '</div></td>';				
			
			echo '<td>&nbsp;</td>';
			
			$startMonth = date('m',strtotime($trials[$i]['NCT/start_date']));
			$startYear = date('Y',strtotime($trials[$i]['NCT/start_date']));
			$endMonth = date('m',strtotime($trials[$i]['inactive_date']));
			$endYear = date('Y',strtotime($trials[$i]['inactive_date']));

			//rendering project completion gnatt chart
			echo $str = getCompletionChart($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $trials[$i]['NCT/start_date'],
			 $trials[$i]['inactive_date'], $phaseColor);
			
			echo '</tr>';
			//rendering matched upms
			if(isset($trials[$i]['matchedupms']) && !empty($trials[$i]['matchedupms'])) 
			{
				foreach($trials[$i]['matchedupms'] as $k => $v) 
				{ 
					$str = '';
					$diamond = '';
					$resultImage = '';
	
					$stMonth = date('m',strtotime($v['start_date']));
					$stYear = date('Y',strtotime($v['start_date']));
					$edMonth = date('m',strtotime($v['end_date']));
					$edYear = date('Y',strtotime($v['end_date']));
					$upmTitle = 'title="' . htmlformat($v['event_description']) . '"';
					
					echo '<tr>';
					
					//rendering diamonds in case of end date is prior to the current year
					echo '<td style="text-align:center;' . (($k < count($trials[$i]['matchedupms'])-1) ? 'border-bottom:0;' : '' ) . '">';
					
					if(!empty($trials[$i]['matchedupms'][$k]['edited']) && ($v['result_link'] != $trials[$i]['matchedupms'][$k]['edited']['result_link'])) 
					{
						if($v['result_link'] != '' && $v['result_link'] != NULL) 
						{
							$resultImage = (($v['event_type'] == 'Clinical Data') ? 'diamond' : 'checkmark' );
							echo '<div ' . $upmTitle . '><a href="' . $v['result_link'] . '" style="color:#000;">'
							. '<img src="images/red-' . $resultImage . '.png" alt="' . $resultImage . '" style="padding-top: 3px;" border="0" /></a></div>';
						}
					} 
					else if($upmDetails[$nctid][$k]['new'] == 'y') 
					{
						$resultImage = (($v['event_type'] == 'Clinical Data') ? 'diamond' : 'checkmark' );
						echo '<div ' . $upmTitle . '>';
						if($v['result_link'] != '' && $v['result_link'] != NULL) 
						{
							echo '<a href="' . $v['result_link'] . '" style="color:#000;">'
							. '<img src="images/red-' . $resultImage . '.png" alt="' . $resultImage . '" style="padding-top: 3px;" border="0" /></a>';
						} 
						else 
						{
							echo '<img src="images/red-' . $resultImage . '.png" alt="' . $resultImage . '" style="padding-top: 3px;" border="0" />';
						}
						echo '</div>';
					}
					else 
					{
						if($v['result_link'] != '' && $v['result_link'] != NULL) 
						{
							$resultImage = (($v['event_type'] == 'Clinical Data') ? 'diamond' : 'checkmark' );
							echo '<div ' . $upmTitle . '><a href="' . $v['result_link'] . '" style="color:#000;">'
							. '<img src="images/black-' . $resultImage . '.png" alt="' . $resultImage . '" style="padding-top: 3px;" border="0" /></a></div>';
						}
					}
					
					if(($v['end_date'] != '' && $v['end_date'] != NULL && $v['end_date'] != '0000-00-00') && 
					($v['end_date'] < date('Y-m-d')) && ($v['result_link'] == NULL || $v['result_link'] == ''))
					{
						echo '<div ' . $upmTitle . '><img src="images/hourglass.png" alt="hourglass" border="0" /></div>';
					}
					echo '</td>';
					
					//rendering upm (upcoming project completion) chart

					echo $str = getUPMChart($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $v['start_date'],
					$v['end_date'], $v['event_link'], $upmTitle);
					echo '</tr>';
				}
			}
			echo '</tr>';
			
		//section title
		$section = $trials[$i]['section'];
	}
}

//get difference between two dates in months
function getColspan($start_dt, $end_dt) {
	
	$diff = round((strtotime($end_dt)-strtotime($start_dt))/2628000);
	return $diff;

}

//calculating the project completion chart in which the year ranges from the current year and next-to-next year
function getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, $start_date, $end_date, $bg_color)
{
	$attr_two = 'class="rightborder"';
	if(($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') && ($end_date == '' || $end_date == NULL || $end_date == '0000-00-00')) 
	{
		$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
	} 
	else if($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') 
	{
		$st = $end_month-1;
		if($end_year < $current_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
						
		} else if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';width:2px;">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
					
		} else if($end_year == $second_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';width:2px;">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
					
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';width:2px;">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
				
		} else if($end_year > $third_yr){
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';
		}
	} else if($end_date == '' || $end_date == NULL || $end_date == '0000-00-00') {
	
		$st = $start_month-1;
		if($start_year < $current_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
						
		} else if($start_year == $current_yr) { 
			
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';width:2px;">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3" ' 
				. $attr_two . '>&nbsp;</td>';	
					

		} else if($start_year == $second_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>'
				. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';width:2px;">&nbsp;</td>'

				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
					
		} else if($start_year == $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';width:2px;">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
				
		} else if($start_year > $third_yr){
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';
		}
			
	} else if($end_date < $start_date) {
	
		$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
					
	} else if($start_year < $current_yr) {
		
		if($end_year < $current_yr) {
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
		  
		} else if($end_year == $current_yr) {
		 
			if($end_month == 12) {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="12">&nbsp;</td>' 
						. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
						. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			} else { 
				$value = '<td style="background-color:' . $bg_color . ';" colspan="' . $end_month . '">&nbsp;</td>'
						. '<td style="width:'.(12-$end_month).'px;" colspan="' . (12-$end_month) . '">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
						. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			}
		} else if($end_year == $second_yr) { 
		 
			if($end_month == 12) {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="24">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			} else {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="' . (12+$end_month) . '">&nbsp;</td>'
						. '<td colspan="' . (12-$end_month) . '">&nbsp;</td><td colspan="12">&nbsp;</td>'
						. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			}
	
		} else if($end_year == $third_yr) { 
		
			if($end_month == 12) {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="36">&nbsp;</td>'
					. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			} else {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="' . (24+$end_month) . '">&nbsp;</td>'
				. '<td colspan="' . (12-$end_month) . '">&nbsp;</td>'
				. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';

			}
		 
		} else if($end_year > $third_yr) { 
			$value = '<td colspan="39" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
		}	
	
	} else if($start_year == $current_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			
			if($val != 0) {
				$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
						. (((12 - ($st+$val)) != 0) ? '<td colspan="' .(12 - ($st+$val)) . '">&nbsp;</td>' : '');
			} else {
				$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '');
			}
			$value .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
		
		} else if($end_year == $second_yr) { 
		 
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
		
			if($val != 0) {
				$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
			} else {
				$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');			
			}
			$value .= '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
	
		} else if($end_year == $third_yr) {
				
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			
			if($val != 0) {
				$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
						. (((36 - ($val+$st)) != 0) ? '<td colspan="' .(36 - ($val+$st)) . '">&nbsp;</td>' : '');
			} else {
				$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. (((36 - (1+$st)) != 0) ? '<td colspan="' .(36 - (1+$st)) . '">&nbsp;</td>' : '');
			}
			$value .= '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
	
		} else if($end_year > $third_yr){
		
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(39 - $st) . '" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
		}
		
	} else if($start_year == $second_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $second_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">' . '&nbsp;</td>' : '');
				
				if($val != 0) { 
					$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
							. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
				} else { 
					$value .= '<td style="background-color:' . $bg_color . ';width:2px;"></td>'
							. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$value .= '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';		
		
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) {
					$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
							. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
				} else {
					$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
							. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$value .= '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';

		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(27 - $st) . '" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
		}
		
	} else if($start_year == $third_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;	
		if($end_year == $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>'
				. '<td colspan="12">&nbsp;</td>'
				. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				
			if($val != 0) {
				$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
			} else {
				$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
					. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
			}
			$value .= '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
		
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(15 - $st) . '" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
						
		}
			
	} else if($start_year > $third_yr) {
	
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';	
			
	} 
	return $value;
}

function getUPMChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, $start_date, $end_date, $upm_link, $upm_title)
{
	
	$attr_two = 'class="rightborder"';
	$background_color = 'background-color:#9966FF;';
	
	
	if(($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') && ($end_date == '' || $end_date == NULL || $end_date == '0000-00-00')) {
	
		$value = '<td colspan="12"><div ' . $upm_title . '>' 
			.(( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
			. '<td colspan="12"><div ' . $upm_title . '>'
			. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
			. '<td colspan="12"><div ' . $upm_title . '>' . (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;')  
			. '</div></td>'
			. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>' 
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';	

	} else if($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') {
	
		$st = $end_month-1;
		if($end_year < $current_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';	
						
		} else if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';	
					
		} else if($end_year == $second_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>' 
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
					
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';	
				
		} else if($end_year > $third_yr){
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" style="' . $background_color . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
		}
	} else if($end_date == '' || $end_date == NULL || $end_date == '0000-00-00') {
	
		$st = $start_month-1;
		if($start_year < $current_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';	
						
		} else if($start_year == $current_yr) { 
			
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';	
					
		} else if($start_year == $second_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
					
		} else if($start_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
			 	. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';	
				
		} else if($start_year > $third_yr){
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" style="' . $background_color . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
		}
			
	} else if($end_date < $start_date) {
	
		$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
					
	} else if($start_year < $current_yr) {

		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;

		if($end_year < $current_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
			. '<td colspan="12"><div ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
			. '<td colspan="12"><div ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
			. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
		  
		} else if($end_year == $current_yr) { 
		
			if($end_month == 12) {
			
				$value = '<td style="' . $background_color . '" colspan="' . $end_month . '">' 
				. '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
				
			} else { 
			
				$value = '<td style="' . $background_color . '" colspan="' . $end_month . '">' . '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td style="width:'.(12-$end_month).'px;" colspan="' . (12-$end_month) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
				
			}
		} else if($end_year == $second_yr) { 
		 
			if($end_month == 12) {
			
				$value = '<td style="' . $background_color . '" colspan="24">' . '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
				
			} else {
			
				$value = '<td style="' . $background_color . '" colspan="' . (12+$end_month) . '">' . '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="' . (12-$end_month) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;'). '</div></td>';
				
			}
	
		} else if($end_year == $third_yr) { 
			
			if($end_month == 12) {
			
				$value = '<td style="' . $background_color . '" colspan="36">' . '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
				
			} else {
			
				$value = '<td style="' . $background_color . '" colspan="' . (24+$end_month) . '" ' . $class . '>' 
				. '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="' . (12-$end_month) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'

				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
			}
		 
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="39" style="' . $background_color . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';		
		}	
	
	} else if($start_year == $current_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '" ><div ' . $upm_title . '>'
			. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
			
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">'
						. '<div ' . $upm_title . '>'
						. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((12 - ($st+$val)) != 0) ? '<td colspan="' .(12 - ($st+$val)) . '"  style="' . $lineheight . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">'
						. '<div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"  style="' . $lineheight . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');			
			}
			
			$value .= '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
					. '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
		
		} else if($end_year == $second_yr) { 
		 
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
			
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">'
						. '<div ' . $upm_title .' >'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">' . '<div ' . $upm_title .' >'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');			
			}
			
			$value .= '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
	
		} else if($end_year == $third_yr) {
				
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
				
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">' . '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((36 - ($val+$st)) != 0) ? '<td colspan="' .(36 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '') ;

			} else {
				$value .= '<td style="' . $background_color . '">'
						. '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((36 - (1+$st)) != 0) ? '<td colspan="' .(36 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '') ;			
			}
			
			$value .= '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
	
		} else if($end_year > $third_yr){
		
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(39 - $st) . '" style="' . $background_color . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';		
		}
		
	} else if($start_year == $second_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $second_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>' 
					. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
					. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
					
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">' . '<div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">' . '<div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
			}
			
			$value .= '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';		
		
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
					. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
					
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">' . '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">' . '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');			
			}
			$value .= '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';

		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(27 - $st) . '" style="' . $background_color . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';		
		}
		
	} else if($start_year == $third_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;	
		if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
				
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">' . '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">' . '<div ' . $upm_title .'>' 
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');			
			}
			
			$value .= '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';
		
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' 
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '')
				. '<td colspan="' . (15 - $st) . '" style="' . $background_color . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';;
		
		}
			
	} else if($start_year > $third_yr) {
	
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
				. '<td colspan="3" style="' . $background_color . '" ' . $attr_two . '><div ' . $upm_title . '>'

				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>';	
				
	}
	return $value;	
}

//return NCT fields given an NCTID
function getTrialUpdates($nctId, $larvolId, $time, $edited)
{	
	global $now;
	if($time === NULL) $time = $now;

	$updates = array('edited' => array(), 'new' => 'n');
	
	$fieldnames = array('nct_id', 'brief_title', 'enrollment', 'enrollment_type', 'acronym', 'start_date', 'overall_status',
	'condition', 'intervention_name', 'phase', 'lead_sponsor', 'collaborator');

	$studycatData = mysql_fetch_assoc(mysql_query("SELECT `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_cats_in_study` `dc` ON "
	. "(`dc`.`id`=`dv`.`studycat`) WHERE `dv`.`field`='1' AND `dv`.`val_int`='" . $nctId . "' AND `dc`.`larvol_id`='" .$larvolId . "'"));
	

	$res = mysql_query("SELECT DISTINCT `df`.`name` AS `fieldname`, `df`.`id` AS `fieldid`, `df`.`type` AS `fieldtype`, `dv`.`studycat` "
		. "FROM `data_values` `dv` LEFT JOIN `data_fields` `df` ON (`df`.`id`=`dv`.`field`) WHERE `df`.`name` IN ('" 
		. join("','",$fieldnames) . "') AND `studycat` = '" . $studycatData['studycat'] 
		. "' AND (`dv`.`superceded`<'" . date('Y-m-d',$time) . "' AND `dv`.`superceded`>= '" . date('Y-m-d',strtotime($edited,$time)) . "') ");

	while ($row = mysql_fetch_assoc($res)) 
	{
		//getting previous value for updated trials
		$result = mysql_fetch_assoc(mysql_query("SELECT `" . 'val_'.$row['fieldtype'] ."` AS value FROM `data_values` WHERE `studycat` = '" 
		. $studycatData['studycat'] . "' AND `field` =  '" . $row['fieldid'] . "' AND (`superceded`<'" . date('Y-m-d',$time) 
		. "' AND `superceded`>= '" . date('Y-m-d',strtotime($edited,$time)) . "') "));
		
		$val = $result['value'];
		
		//special case for enum fields
		if($row['fieldtype'] == 'enum') 
		{
			$result = mysql_fetch_assoc(mysql_query("SELECT `value` FROM `data_enumvals` WHERE `field` = '" . $row['fieldid'] . "' AND `id` = '" . $val . "' "));
			$val 	= $result['value'];
		}
			
		if(isset($val) && $val != '')
			$updates['edited']['NCT/'.$row['fieldname']] = 'Previous value: ' . $val;
		else 
			$updates['edited']['NCT/'.$row['fieldname']] = 'No previous value';
	}
	/*
	$sql = "SELECT `clinical_study`.`larvol_id` FROM `clinical_study` WHERE `clinical_study`.`import_time` <= '" 
		. date('Y-m-d',$time) . "' AND `clinical_study`.`larvol_id` = '" .  $larvolId . "' AND `clinical_study`.`import_time` >= '" 
		. date('Y-m-d',strtotime($edited,$time)) . "' ";
	*/

		$frd=getFieldId('NCT', 'firstreceived_date');
	
		$sql = "SELECT cs.larvol_id,dv.val_date 
		FROM clinical_study cs 
		LEFT JOIN data_cats_in_study dcis ON cs.larvol_id = dcis.larvol_id 
		LEFT JOIN data_values dv ON dcis.id = dv.studycat 
		WHERE dv.field='" . $frd . "' and dv.val_date <= '". date('Y-m-d',$time) . "' 
		AND cs.larvol_id = '" .  $larvolId . "' 
		AND dv.val_date >= '" . date('Y-m-d',strtotime($edited,$time)) . "' ";
		
	$result = mysql_query($sql);		

	if(mysql_num_rows($result) > 0) 
		$updates['new'] = 'y';
	
	return $updates;
}

//Get html content by passing through htmlspecialchars
function htmlformat($str)
{
	$str=fix_special_chars($str);
	return htmlspecialchars($str);
}

//getting corresponding UPM details for each of the trials
function getCorrespondingUPM($trial_id, $time, $edited) 
{
	$upm['matchedupms'] = array();
	$values = array();
					
	$result = mysql_query("SELECT id, event_type, corresponding_trial, event_description, event_link, result_link, start_date, end_date 
					FROM upm WHERE corresponding_trial = '" . $trial_id . "' ");
	
	$i = 0;			
	while($row = mysql_fetch_assoc($result)) 
	{
		$upm['matchedupms'][$i] = array('event_description' => $row['event_description'], 'event_link' => $row['event_link'], 
								'start_date' => $row['start_date'], 'end_date' => $row['end_date'], 
								'result_link' => $row['result_link'],'event_type' => $row['event_type'],);
		
		//Query for checking updates for upms.
		$sql = "SELECT `id`, `event_type`, `event_description`, `event_link`, `result_link`, `start_date`, `start_date_type`, `end_date`, `end_date_type` "
				. " FROM `upm_history` WHERE `id` = '" . $row['id'] . "' AND (`superceded` < '" . date('Y-m-d',$time) . "' AND `superceded` >= '" 
				. date('Y-m-d',strtotime($edited,$time)) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
		$res = mysql_query($sql);
		
		$upm['matchedupms'][$i]['edited'] = array();
		$upm['matchedupms'][$i]['new'] = 'n';
		while($arr = mysql_fetch_assoc($res)) 
		{
			$upm['matchedupms'][$i]['edited'] = array('event_description' => $arr['event_description'], 'event_link' =>$arr['event_link'], 
											'start_date' => $arr['start_date'], 'end_date' => $arr['end_date'],
											'result_link' => $arr['result_link'], 'event_type' => $arr['event_type'], 
											'start_date_type' => $arr['start_date_type'], 'end_date_type' => $arr['end_date_type'],);
		}
		
		$query = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $row['id'] . "' AND u.`last_update` < '" 
				. date('Y-m-d',$time) . "' AND u.`last_update` >=  '" . date('Y-m-d',strtotime($edited,$time)) . "' AND uh.`id` IS NULL ";
		
		$ress = mysql_query($query);
		if(mysql_num_rows($ress) != 0)
			$upm['matchedupms'][$i]['new'] = 'y';
			
		$i++;
	}
	return $upm;
}

//get records for non associated upms
function getNonAssocUpmRecords($naUpmsRegex, $timeMachine, $timeInterval) 
{
	$where = '';$upms = array();$i = 0;
	foreach($naUpmsRegex as $key => $val)
	{
		$where .= textEqual('`search_name`',$val) . ' OR ';
	}
	
	$result = mysql_query("SELECT `id` FROM `products` WHERE ( " . substr($where,0,-4) . " ) ");
	if(mysql_num_rows($result) > 0) 
	{
		while($rows = mysql_fetch_assoc($result)) 
		{
			$sql = "SELECT `id`, `event_description`, `event_link`, `result_link`, `event_type`, `start_date`,
					`start_date_type`, `end_date`, `end_date_type` FROM `upm` WHERE `corresponding_trial` IS NULL AND `product` = '" . $rows['id'] 
					. "' ORDER BY `end_date` ASC ";
			$res = mysql_query($sql)  or tex('Bad SQL query getting unmatched upms ' . $sql);
		
			if(mysql_num_rows($res) > 0) 
			{
				while($row = mysql_fetch_assoc($res)) 
				{ 
					$upms[$i]['id'] = $row['id'];
					$upms[$i]['event_description'] = htmlspecialchars($row['event_description']);
					$upms[$i]['event_link'] = $row['event_link'];
					$upms[$i]['result_link'] = $row['result_link'];
					$upms[$i]['event_type'] = $row['event_type'];
					$upms[$i]['start_date'] = $row['start_date'];
					$upms[$i]['start_date_type'] = $row['start_date_type'];
					$upms[$i]['end_date'] 	= $row['end_date'];
					$upms[$i]['end_date_type'] = $row['end_date_type'];
					$upms[$i]['new'] = 'n';
					$upms[$i]['edited'] = array();
					
					$sql = "SELECT `id`, `event_type`, `event_description`, `event_link`, `result_link`, `start_date`, "
						. " `start_date_type`, `end_date`, `end_date_type` "
						. " FROM `upm_history` WHERE `id` = '" . $row['id'] . "' AND (`superceded` < '" . date('Y-m-d',$timeMachine) 
						. "' AND `superceded` >= '" . date('Y-m-d',strtotime($timeInterval,$timeMachine)) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
					$ress = mysql_query($sql);
					if(mysql_num_rows($ress) > 0) 
					{
						while($roww = mysql_fetch_assoc($ress)) 
						{
							$upms[$i]['edited']['id'] = $roww['id'];
							$upms[$i]['edited']['event_description'] = htmlspecialchars($roww['event_description']);
							$upms[$i]['edited']['event_link'] = $roww['event_link'];
							$upms[$i]['edited']['result_link'] = $roww['result_link'];
							$upms[$i]['edited']['event_type'] = $roww['event_type'];
							$upms[$i]['edited']['start_date'] = $roww['start_date'];
							$upms[$i]['edited']['start_date_type'] = $roww['start_date_type'];
							$upms[$i]['edited']['end_date'] 	= $roww['end_date'];
							$upms[$i]['edited']['end_date_type'] = $roww['end_date_type'];
						}
					}
						
					$sql = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $value['id'] 
							. "' AND u.`last_update` < '" . date('Y-m-d',$timeMachine) . "' AND u.`last_update` >=  '" 
							. date('Y-m-d',strtotime($timeInterval,$timeMachine)) . "' AND uh.`id` IS NULL ";
					$reslt = mysql_query($sql);
					if(mysql_num_rows($reslt) > 0)
						$upms[$i]['new'] = 'y';	
					
					$i++;
				}
			}
		}
	}
	return $upms;
}

function getLinkDetails($tablename, $fieldname, $parameters, $param_value) 
{
	$query = "SELECT `" . $fieldname . "`, `expiry` FROM " . $tablename . " WHERE " . $parameters . " = '" . mysql_real_escape_string($param_value) . "' ";
	$res = mysql_fetch_assoc(mysql_query($query));
	
	return $res;
}

function getDifference($value_one, $value_two) 
{
	$diff = abs(($value_one - $value_two) / $value_one * 100);
	$diff = round($diff);

	if($diff > 20)
		return true;
	else
		return false;
}
?> 
</body>
</html>
