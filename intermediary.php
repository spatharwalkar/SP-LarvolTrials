<?php
header('P3P: CP="CAO PSA OUR"');
session_start();
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
if(!isset($_GET['cparams']) && !isset($_GET['params']) && !isset($_GET['results'])) die('cell not set');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>Online Trial Tracker</title>
<link href="css/intermediary.css" rel="stylesheet" type="text/css" media="all" />
<script src="scripts/jquery.js" type="text/javascript"></script>	
<script src="scripts/func.js" type="text/javascript"></script>	
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
	private $displist 	= array('Enrollment' => 'NCT/enrollment','Region' => 'region', 'Status' => 'NCT/overall_status', 
								'Sponsor' => 'NCT/lead_sponsor', 'Conditions' => 'NCT/condition', 
								'Interventions' => 'NCT/intervention_name','Study Dates' => 'NCT/start_date', 
								'Phase' => 'NCT/phase');
								
	private $imgscale 	= array('style="width:14px;height:14px;"', 'style="width:12px;height:12px;"', 
								'style="width:10px;height:10px;"', 'style="width:8px;height:8px;"', 
								'style="width:6px;height:6px;"');
								
	private $actfilterarr 	= array('nyr'=>'Not yet recruiting', 'r'=>'Recruiting', 'ebi'=>'Enrolling by invitation', 
								'anr'=>'Active, not recruiting', 'a'=>'Available');
								
	private $inactfilterarr = array('wh'=>'Withheld', 'afm'=>'Approved for marketing',
								'tna'=>'Temporarily not available', 'nla'=>'No Longer Available', 'wd'=>'Withdrawn', 
								't'=>'Terminated','s'=>'Suspended', 'c'=>'Completed');
	private $phase_arr 		= array('N/A'=>'#bfbfbf','0'=>'#44cbf5','0/1'=>'#99CC00','1'=>'#99CC00','1/2'=>'#ffff00',
									'2'=>'#ffff00','2/3'=>'#ff9900','3'=>'#ff9900','3/4'=>'#ff0000','4'=>'#ff0000');
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
	
	public function __construct() {
	
		$db = new DatabaseManager();
		$this->results_per_page = $db->set['results_per_page'];
		
		$this->activestatus = '<input type="checkbox" name="nyr" value="1" ' 
			.(isset($_GET['nyr']) ? ' checked="checked"' : ''). ' />Not yet recruiting<br/>'
			.'<input type="checkbox" name="r" value="1" ' 
			.(isset($_GET['r']) ? ' checked="checked"' : ''). ' />Recruiting<br/>'
			.'<input type="checkbox" name="ebi" value="1" ' 
			.(isset($_GET['ebi']) ? ' checked="checked"' : ''). ' />Enrolling by invitation<br/>'
			.'<input type="checkbox" name="anr" value="1"' 
			.(isset($_GET['anr']) ? ' checked="checked"' : ''). '  />Active, not recruiting<br/>'
			.'<input type="checkbox" name="a" value="1" ' 
			.(isset($_GET['a']) ? ' checked="checked"' : ''). ' />Available<br/>';
							
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
		$this->fid['primary_completion_date'] 	= '_' . getFieldId('NCT', 'primary_completion_date');
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

		$this->type = (isset($_GET["list"])) ? ($_GET["list"].'array') : 'activearray' ;
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
	
	function commonControls($count, $act, $inact, $all, $actph, $inactph) {
	
		$enumvals = getEnumValues('clinical_study', 'institution_type');
	
		echo ('<div style="height:100px;width:1000px;"><div class="block"><div class="text">List</div>'
			. '<input type="radio" id="actlist" name="list" checked="checked" value="active" '
			. ' onchange="javascript: applyfilter(this.value);" />'
			. '&nbsp;<label for="actlist"><span style="color: #00B050;"> ' . $act
			. ' Active Records </span></label>');
				if(!empty($actph)) { 
					echo ' (Highest Phase: ' . ((count($actph) > 1) ? max($actph) : $actph[0]) . ')';
				}
		echo ('<br/><input type="radio" id="inactlist" name="list" value="inactive" ' 
			. ((isset($_GET['list']) && $_GET['list'] == 'inactive') ? ' checked="checked" ' : '')
			. ' onchange="javascript: applyfilter(this.value);" />&nbsp;<label for="inactlist">'
			. '<span style="color: #FF0000;"> ' . $inact
			. ' Inactive Records</span></label>');
				if(!empty($inactph)) { 
					echo ' (Highest Phase: ' . ((count($inactph) > 1) ? max($inactph) : $inactph[0]) . ')';
				}
		echo ('<br/><input type="radio" id="alllist" name="list" value="all"' 
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
			. '&nbsp;&nbsp;<div class="drop"><div class="text">Show Only</div>');
			
		foreach($enumvals as $k => $v){ 
			echo '<input type="checkbox" id="' . $v . '" name="institution[]" value="' . $v . '" '
			. ((isset($_GET['institution']) && in_array($v, $_GET['institution'])) ? 'checked="checked"' : '' ) . '/>&nbsp;' 
			. '<label for="'.$v.'">' .$v . '</label><br/>';
		}
		echo ('</div>'
			. '</div><br/><input type="submit" value="Show"/>&nbsp;');
			 if(strlen($count)) { echo $count . '&nbsp;Records'; }
			
	
	}
	
	function pagination($page, $count, $params, $leading, $tt_type, $stacktype) {
		
		$pager = '';
		$sort = '';
		
		if(isset($_GET['list'])) $sort .= '&amp;list='.$_GET['list']; else $sort .= '&amp;list=active'; 
		if(isset($_GET['sortorder']) && $_GET['sortorder'] != '') $sort .= '&amp;sortorder=' . rawurlencode($_GET['sortorder']);
		if(isset($_GET['instparams']) && $_GET['instparams'] != '') $sort .= '&amp;instparams=' 
		. rawurlencode($_GET['instparams']);
		if(isset($_GET['institution']) && $_GET['institution'] != '') { 
			foreach($_GET['institution'] as $k => $v)
			$sort .= '&amp;institution[]=' . $v;
		}
		if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] != '')
			$sort .= '&amp;chkOnlyUpdated=' . $_GET['chkOnlyUpdated'];
			
		if(isset($_GET['edited']) && $_GET['edited'] != '') 
			$sort .= '&amp;edited='.htmlspecialchars(trim($_GET['edited'])); 
		else 
			$sort .= '&amp;edited=oneweek';
		
		foreach($this->actfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
		foreach($this->inactfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
		foreach($this->allfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
		
		if($tt_type == 'stack') {
			
			$stack_url = '';
				
			if(isset($_GET['cparams'])) {
				foreach($_GET['params'] as $k => $v) {
					$stack_url .= '&leading['.$k.']=' . rawurlencode($_GET['leading'][$k]) . '&params['.$k.']=' . rawurlencode($_GET['params'][$k]);
					
					if($stacktype == 'row') 
						$stack_url .= '&rowupm['.$k.']=' . rawurlencode($_GET['rowupm'][$k]); 
					elseif($stacktype == 'col')
						$stack_url .= '&colupm['.$k.']=' . rawurlencode($_GET['colupm'][$k]); 
					
					if(isset($_GET['trunc']))
						$stack_url .= '&trunc=' . $_GET['trunc'];
				}
			}
			
			if($this->pstart > 1)
			{
				if(isset($_GET['results'])) {
				
					$pager .= '<a href="intermediary.php?results=' . rawurlencode($_GET['results']) . '&type=' . rawurlencode($_GET['type']) 
					. '&time=' . rawurlencode($_GET['time']) . '&amp;pg=' . ($page-1);
					if(isset($_GET['format']) && $_GET['format'] == 'new') 
						$pager .= '&format=' . $_GET['format'];
						
				} else if(isset($_GET['cparams'])) {
					$pager .= '<a href="intermediary.php?cparams=' . rawurlencode($_GET['cparams']) . $stack_url . '&amp;pg=' . ($page-1);
				}
				$pager .= $sort . '" style="float:left;">&lt;&lt; Previous Page (' . ($this->pstart - $this->results_per_page) . '-' . ($this->pstart-1) 
				. ')</a>&nbsp;&nbsp;&nbsp;';
			}
			$pager .= '<div style="float:left;margin:0px 10px;">Studies Shown (' . $this->pstart . '-' . $this->pend . ')&nbsp;&nbsp;&nbsp;</div>';
			if($this->pend < $count)
			{
				
				$nextlast = ($this->last+$this->results_per_page);
				if($nextlast > $count) $nextlast = $count;
				if(isset($_GET['results'])) {
				
					$pager .= '<a href="intermediary.php?results=' . rawurlencode($_GET['results']) . '&type=' . rawurlencode($_GET['type']) 
						. '&time=' . rawurlencode($_GET['time']) . '&amp;pg=' . ($page+1);
						
					if(isset($_GET['format']) && $_GET['format'] == 'new') 
					$pager .= '&format=' . $_GET['format'];	
					
				} else if(isset($_GET['cparams'])) {
					$pager .= '<a href="intermediary.php?cparams=' . rawurlencode($_GET['cparams']) . $stack_url . '&amp;pg=' . ($page+1);
				}
				$pager .= $sort . '" style="float:left;">Next Page (' . ($this->pstart+$this->results_per_page) . '-' . $nextlast . ') &gt;&gt;</a>';
			}
		
		} else {
		
			if($this->pstart > 1)
			{
				if(isset($_GET['results'])) {
					$pager .= '<a href="intermediary.php?results=' . rawurlencode($params) . '&amp;page=' . ($page-1) . '&amp;time=' . rawurlencode($leading);
				} else {
					$pager .= '<a href="intermediary.php?params=' . rawurlencode($params) . '&amp;page=' . ($page-1) . '&amp;leading=' . rawurlencode($leading);
				}
				$pager .= $sort . '" style="float:left;">&lt;&lt; Previous Page (' . ($this->pstart - $this->results_per_page) . '-' . ($this->pstart-1) 
				. ')</a>&nbsp;&nbsp;&nbsp;';
			}
			$pager .= '<div style="float:left;margin:0px 10px;">Studies Shown (' . $this->pstart . '-' . $this->pend . ')</div>';
			
			if($this->pend < $count)
			{
				$nextlast = ($this->last+$this->results_per_page);
				if($nextlast > $count) $nextlast = $count;
				if(isset($_GET['results'])) {
					$pager .= '<a href="intermediary.php?results=' . rawurlencode($params) . '&amp;page=' . ($page+1) . '&amp;time=' 
					. rawurlencode($leading);
				} else {
					$pager .= '<a href="intermediary.php?params=' . rawurlencode($params) . '&amp;page=' . ($page+1) . '&amp;leading=' 
					. rawurlencode($leading);
				}
				$pager .= $sort . '" style="float:left;">Next Page (' . ($this->pstart+$this->results_per_page) . '-' . $nextlast . ') &gt;&gt;</a>';
			}
		}
		echo $pager;

	}
	
	function processParams() {
		
		$return_param	= array();
		$return_param['fin_arr'] = array();
		$return_param['upmDetails'] = array();
		$ins_params		= array();
		$return_param['showRecordsCnt'] = 0;
		
		if(isset($_GET['results']) && isset($_GET['type'])) {
			
			$this->time_machine = $_GET['time'];
			echo ('<input type="hidden" name="results" value="' . $_GET['results'] . '"/>'
					. '<input type="hidden" name="type" value="' . $_GET['type'] . '"/>'
					. '<input type="hidden" name="time" value="' . $_GET['time'] . '"/>');
			if(isset($_GET['format']) && $_GET['format'] == 'new') {
			
				echo ('<input type="hidden" name="format" value="' . $_GET['format'] . '"/>');
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
						$output[] = substr($string, 0, -1);
						$three = 0;
						$lengthcounter = 0;
						$string = '';
					}
				}
				$return_param['c_params'] = $output;
			} else { 
				//no specific encoding method i.e. only implode used to encode data in the url
				$return_param['c_params'] = explode(',', gzinflate(base64_decode($_GET['results'])));
			}
			
			$vv = explode('.', $return_param['c_params'][0]);
			if($_GET['type'] == 'col') {
				$t = getLinkDetails('rpt_ott_header', 'header', 'id', $vv[1]);
			} else if($_GET['type'] == 'row') {
				$t = getLinkDetails('rpt_ott_header', 'header', 'id', $vv[0]);
			}
			$return_param['params_arr'] = $return_param['c_params'];
				
		} else {
		
				$return_param['c_params'] 	= unserialize(gzinflate(base64_decode($_GET['cparams'])));
				$stack_type = ($return_param['c_params']['type'] == 'col') ? 'rowlabel' : 'columnlabel';
				$t 	= ($return_param['c_params']['type'] == 'col') ? $return_param['c_params']['columnlabel'] : $return_param['c_params']['rowlabel'];
				
			echo ('<input type="hidden" name="cparams" value="' . $_GET['cparams'] . '"/>'
					. '<input type="hidden" name="trunc" value="' . $_GET['trunc'] . '"/>');
					
			foreach($_GET['params'] as $key => $value) {
				echo ('<input type="hidden" name="params['.$key.']" value="' . $_GET['params'][$key] . '"/>'
						. '<input type="hidden" name="leading['.$key.']" value="' . $_GET['leading'][$key] . '"/>');
				if($return_param['c_params']['type'] == 'row') {
					echo ('<input type="hidden" name="rowupm['.$key.']" value="' . $_GET['rowupm'][$key] . '"/>');
				} else if($return_param['c_params']['type'] == 'col'){
					echo ('<input type="hidden" name="colupm['.$key.']" value="' . $_GET['colupm'][$key] . '"/>');
				}		
			}
			
			//echo "<pre>";print_r($_GET);
			$return_param['params_arr'] = $_GET['params'];
		}
		
		if(isset($_GET['institution']) && $_GET['institution'] != '') {
				
			array_push($this->fid, 'institution_type');
			$sp = new SearchParam();
			$sp->field 	= 'institution_type';
			$sp->action = 'search';
			$sp->value 	= $_GET['institution'];
			$ins_params = array($sp);
		}
		
		echo ('</td><td class="result">Results for ' . htmlformat($t) . '</td>' . '</tr></table>');
		echo('<br clear="all"/>');
		foreach($return_param['params_arr'] as $pk => $pv) {
		
			$excel_params 	= array();
			$params = array();
			$arr 	= array();
			$arrr 	= array();
			$return_param['fin_arr'][$pk] = array();
			$totinactivecount = 0; 
			$totactivecount	 = 0; 
			
			//New Link Method
			if(isset($_GET['results'])) {
				$e 	= explode(".", $pv);
				
				//Retrieving headers
				if($_GET['type'] == 'row') {
					if($pk != 0) {
						$return_param['ltype'][$pk] 	= htmlentities(getLinkDetails('rpt_ott_header', 'header', 'id', $e[0]));
						$tt = $e[1];
					} else {
						$return_param['ltype'][$pk] 	= htmlentities(getLinkDetails('rpt_ott_header', 'header', 'id', $e[1]));
						$tt = $e[2];
					}	
				} else if($_GET['type'] == 'col') {
					if($pk != 0) {
						$return_param['ltype'][$pk] 	= htmlentities(getLinkDetails('rpt_ott_header', 'header', 'id', $e[0]));
						$tt = $e[1];
					} else {
						$return_param['ltype'][$pk] 	= htmlentities(getLinkDetails('rpt_ott_header', 'header', 'id', $e[0]));
						$tt = $e[2];
					}	
				}
				
				//Retrieving params
				$searchdata = substr($tt,0,3);
				if(chr($searchdata) == 's') {
				
					$search_data_content = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', substr($tt,3));
					$excel_params = unserialize(stripslashes(gzinflate(base64_decode($search_data_content))));
					
				} else {
					
					$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $tt);
					$sp = new SearchParam();
					$sp->field = 'larvol_id';
					$sp->action = 'search';
					$sp->value = str_replace(',', ' OR ', $res);
					$excel_params = array($sp);
				}
			} else {
			
				$excel_params = unserialize(gzinflate(base64_decode($pv)));
				$this->time_machine 	= $excel_params['time'];
				$return_param['ltype'][$pk]	= htmlentities($excel_params[$stack_type]);
			
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
			}
			
			$params = array_merge($this->params, $excel_params, $ins_params);
			$arrr = search($params,$this->fid,NULL,$this->time_machine);
			
			//Added to consolidate the data returned in an mutidimensional array format as opposed to earlier 
			//when it was not returned in an mutidimensional array format.
			foreach($arrr as $k => $v) {
				foreach($v as $kk => $vv) {
					if($kk != 'NCT/condition' && $kk != 'NCT/intervention_name' && 'NCT/lead_sponsor')
						$arr[$k][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;
					else
						$arr[$k][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;
				}
			}
			
			//Process to check for changes/updates in trials, matched & unmatched upms.
			foreach($arr as $key => $val) { 
				
				$nct = array();$trial_arr	= array();$allUpmDetails = array();
				
				//checking for updated and new trials
				$nct[$val['NCT/nct_id']] = getNCT($val['NCT/nct_id'], $val['larvol_id'], $this->time_machine, $this->edited);
				$trial_arr[] = $val['NCT/nct_id'] . ', ' . $val['larvol_id'];
				 
				//checking for updated and new unmatched upms.
				$allUpmDetails[$val['NCT/nct_id']] = getCorrespondingUPM($val['NCT/nct_id'], $this->time_machine, $this->edited);

				if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) {
			
					if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
						$return_param['fin_arr'][$pk][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
				
					foreach($allUpmDetails[$val['NCT/nct_id']] as $kk => $vv) {
						if(isset($vv['edited']) && !empty($vv['edited'])) {
							$return_param['upmDetails'][$pk][$val['NCT/nct_id']][] = $vv;
						}
					}
				} else {
					$return_param['fin_arr'][$pk][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
					$return_param['upmDetails'][$pk][$val['NCT/nct_id']] = $allUpmDetails[$val['NCT/nct_id']];
				}
				
				if(in_array($val['NCT/overall_status'],$this->actfilterarr)) {
					$totactivecount++;
					$return_param['activephase'][] = $val['NCT/phase'];
				} else {
					$totinactivecount++;
					$return_param['inactivephase'][] = $val['NCT/phase'];
				}
			}
			
			foreach($return_param['fin_arr'][$pk] as $key => $new_arr){
				
				if($this->inactflag == 1) { 
					
					if(in_array($new_arr['NCT/overall_status'], $this->inactfilterarr)) {
							
						if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
						|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) {
							
							$vall = implode(",",array_keys($this->inactfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_GET)) {
								$return_param['inactivearray'][$pk][] = $new_arr;	
							} 
						} else {
								$return_param['inactivearray'][$pk][] = $new_arr;
						}
					}
				
				} else if($this->allflag == 1) { 
				
					if(in_array($new_arr['NCT/overall_status'], $this->allfilterarr)) {
							
							if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
							|| isset($_GET['a']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
							|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
							|| isset($_GET['c'])) {	
							
							$vall = implode(",",array_keys($this->allfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_GET)) {
								$return_param['allarray'][$pk][] = $new_arr;
							} 
						} else {
							$return_param['allarray'][$pk][] = $new_arr;
						}
					}	
				
				} else {
				
					if(in_array($new_arr['NCT/overall_status'], $this->actfilterarr) ) {
						if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
						|| isset($_GET['a'])) {
							$vall = implode(",",array_keys($this->actfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_GET)) { 
								$return_param['activearray'][$pk][] = $new_arr;
							} 
						} else {
							$return_param['activearray'][$pk][] = $new_arr;
						}	
					}
				}
			}
			$return_param['eachCount'][$pk] = count($return_param[$this->type][$pk]);
			$return_param['showRecordsCnt'] += count($return_param[$this->type][$pk]);//exit;
			$return_param['stack_inactive_count'] 	= $return_param['stack_inactive_count'] + $totinactivecount;
			$return_param['stack_active_count']		= $return_param['stack_active_count'] + $totactivecount;
			$return_param['stack_total_count']		= $return_param['stack_total_count'] + ($totinactivecount + $totactivecount);
			
		}
		
		/*--------------------------------------------------------
		|Variables set for count when filtered by institution_type
		---------------------------------------------------------*/
		if(isset($_GET['instparams']) && $_GET['instparams'] != '') {
			$return_param['insparams'] = $_GET['instparams'];
		} else {
			$return_param['insparams']  = rawurlencode(base64_encode(gzdeflate(serialize(array('actphase' => $return_param['activephase'], 
												'inactphase' => $return_param['inactivephase'],'actcnt' => $return_param['stack_active_count'],
												'inactcnt' => $return_param['stack_inactive_count'])))));
		}
		
		return $return_param;
	}
	
	function chkType() {
	
		$process_params = array();
		echo('<form id="frmOtt" name="frmOtt" method="get" action="intermediary.php">');
		echo ('<table width="100%"><tr><td><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" />'
				. '</td><td nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;">Interface Work In Progress</span>');
		
		//Stacked Ott.	
		if(isset($_GET['cparams']) || (isset($_GET['results']) && isset($_GET['type']))) {
			
			//Process the get parameters and extract the information
			$process_params = $this->processParams();
			
			if(isset($_GET['institution']) && $_GET['institution'] != '') {
				$ins = unserialize(gzinflate(base64_decode(rawurldecode($process_params['insparams']))));
				$this->commonControls($process_params['showRecordsCnt'], $ins['actcnt'], $ins['inactcnt'], ($ins['actcnt'] + $ins['inactcnt']), $ins['actphase'], 
				$ins['inactphase']);
			} else {
				$this->commonControls($process_params['showRecordsCnt'], $process_params['stack_active_count'], $process_params['stack_inactive_count'], 
				$process_params['stack_total_count'],
				$process_params['activephase'], $process_params['inactivephase']);
			}
			echo('<br clear="all"/><br/>');
			echo ('<input type="hidden" name="instparams" value="' . $process_params['insparams']. '" />');	

			//Pagination
			$page = 1;
			$this->pstart 	= '';$this->last = '';$this->pend = '';$this->pages = '';
			if(isset($_GET['pg'])) $page = mysql_real_escape_string($_GET['pg']); 
			if(!is_numeric($page)) die('non-numeric page');
			$count = max($process_params['eachCount']);
			
			$this->pstart 	= ($page-1) * $this->results_per_page + 1;
			$this->pend 	= $this->pstart + $this->results_per_page - 1;
			$this->pages 	= ceil($count / $this->results_per_page);
			$this->last 	= ($page * $this->results_per_page > $count) ? $count : $this->pend;
			
			if($count > $this->results_per_page) {
				if(isset($_GET['results']) && isset($_GET['type'])) {
					$this->pagination($page, $count, NULL, NULL, 'stack', $_GET['type']);
				} else {
					$this->pagination($page, $count, NULL, NULL, 'stack', $process_params['c_params']['type']);
				}
			}
				
			$this->displayHeader();$index = 0;
			foreach($process_params['params_arr'] as $pk => $pv) {
				
				//Unmatched Upms for Row Stacked Upms.
				$row_upm_arr = array();$upm_string = '';$row_upm_flag = false;
				if(isset($_GET['results']) && $_GET['type'] == 'row') { 
					foreach($process_params['c_params'] as $k => $v) {
						$vv = explode('.', $v);
						if($k != 0) {
							if(isset($vv[2])) { $row_upm_arr[$k] = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[2]); }
						} else {
							if(isset($vv[3])) { $row_upm_arr[$k] = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[3]); }
						}
					}
					$row_upm_flag = true;
				} else if(isset($_GET['cparams'])) {
					if(isset($_GET['rowupm']) && $process_params['c_params']['type'] == 'row') {
						foreach($_GET['rowupm'] as $k => $v) {
							$val = unserialize(gzinflate(base64_decode($v)));
							if(isset($val) && $val != '' && !empty($val)) {
								foreach($val as $vv) { $row_upm_arr[$k] = $vv; }
							}
						}
						$row_upm_flag = true;
					}
				}
				
				if(isset($row_upm_arr) && !empty($row_upm_arr)) {
					$upm_string = $this->getNonAssocUpm($row_upm_arr, 'rowupm');
					if($upm_string != '' && $index == 0) {
						echo ('<tr class="trialtitles">'
								. '<td colspan="50" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
								. 'style="border-bottom:1px solid blue" onclick="sh(this,\'rowupm\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
								. '</td></tr>' . $upm_string);
					} 
				}
				
				//Displayiong header titles for Row Stacked Otts.
				if($row_upm_flag == true) { 
					if((isset($_GET['pg']) && $process_params['eachCount'][$pk] >= $this->pstart) || (!isset($_GET['pg']))) {
						echo ('<tr><td colspan="50" class="notopbottomborder leftrightborderblue sectiontitles">' 
						. trim($process_params['ltype'][$pk]) . '</td></tr>');
					}
				} 
				
				//Unmatched Upms for Column Stacked Upms.
				if(isset($_GET['results']) && $_GET['type'] == 'col') { 
					
					$upm_value = '';$upm_string = '';
					$c = explode('.', $process_params['c_params'][$pk]);
					if($pk != 0) {
						if(isset($c[2])) { $upm_value = $c[2]; }
					} else {
						if(isset($c[3])) { $upm_value = $c[3]; }
					}
					if(isset($upm_value) && $upm_value != '' && !empty($upm_value)) {
						$val = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $upm_value);
						$upm_string = $this->getNonAssocUpm(array($val), $pk);
					} 
					if($upm_string != '') {
						echo ('<tr class="trialtitles">'
								. '<td colspan="50" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
								. 'style="border-bottom:1px solid blue" onclick="sh(this,\'' . $pk . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
								. trim($process_params['ltype'][$pk]) . '</td></tr>' . $upm_string);
					} else {
						if((isset($_GET['pg']) && $process_params['eachCount'][$pk] >= $this->pstart) || (!isset($_GET['pg']))) {
							echo ('<tr><td colspan="50" class="notopbottomborder leftrightborderblue sectiontitles">' 
							. trim($process_params['ltype'][$pk]) . '</td></tr>');
						}
					}
				} else if(isset($_GET['cparams']) && $process_params['c_params']['type'] == 'col') { 
					
					$upm_string = '';
					$val = unserialize(gzinflate(base64_decode($_GET['colupm'][$pk])));
					if(isset($val) && $val != '' && !empty($val)) {
						$upm_string = $this->getNonAssocUpm($val, $pk);
					}
					if($upm_string != '') {
						echo ('<tr class="trialtitles">'
								. '<td colspan="50" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
								. 'style="border-bottom:1px solid blue" onclick="sh(this,\'' . $pk . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
								. trim($process_params['ltype'][$pk]) . '</td></tr>' . $upm_string);
					} else {
						if((isset($_GET['pg']) && $_GET['pg'] <= $process_params['eachCount'][$pk]) || (!isset($_GET['pg']))) {
							echo ('<tr><td colspan="50" class="notopbottomborder leftrightborderblue sectiontitles">' 
							. trim($process_params['ltype'][$pk]) . '</td></tr>');
						}
					}
				}
				
				if($process_params['eachCount'][$pk] > 0) {
					
					$last 	= ($page * $this->results_per_page > $process_params['eachCount'][$pk]) ? $process_params['eachCount'][$pk] : $this->pend;
					displayContent($this->displist, $process_params[$this->type][$pk], $this->edited, $this->time_machine, $this->pstart, $last, 
					$this->phase_arr, $process_params['fin_arr'][$pk], $this->actfilterarr, $this->current_yr, $this->second_yr, $this->third_yr,
					$process_params['upmDetails'][$pk]);
				} else  {
					if(!isset($_GET['pg'])) {
						echo ('<tr><th colspan="50" class="norecord" align="left">No record found.</th></tr>');
					}
				}
				$index++;
			}
			if(isset($_GET['trunc'])) {
				$t = unserialize(gzinflate(base64_decode($_GET['trunc'])));
				if($t == 'y') echo ('<span style="font-size:10px;color:red;">Note: all data could not be shown</span>');
			}
			echo('</table>');
			echo ('</form>');
			
		} else {
			
			$page = 1;
			if(isset($_GET['page'])) $page = mysql_real_escape_string($_GET['page']);
			if(!is_numeric($page)) die('non-numeric page');

			$totinactivecount = 0;
			$totactivecount = 0;
			
			$excel_params 	= array();
			$ins_params 	= array();
			$fin_arr 		= array();
			$activephase 	= array();
			$inactivephase 	= array();
			
			if(isset($_GET['results'])) {
			
				$excel_params 	= explode(".", $_GET['results']);
				
				$rowlabel = getLinkDetails('rpt_ott_header', 'header', 'id', $excel_params[0]);;
				$columnlabel = getLinkDetails('rpt_ott_header', 'header', 'id', $excel_params[1]);
				
				if(isset($excel_params[3])) {
					$non_assoc_upm_params	= array(getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $excel_params[3]));
				}
				
				if(strpos($excel_params[2], 's') !== FALSE) { 
					$res = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', substr($excel_params[2],1));
					$excel_params = unserialize(stripslashes(gzinflate(base64_decode($res))));
				} else {
					$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $excel_params[2]);
					$sp = new SearchParam();
					$sp->field = 'larvol_id';
					$sp->action = 'search';
					$sp->value = str_replace(',', ' OR ', $res);
					$excel_params = array($sp);
				}
				$bomb = (isset($_GET['bomb'])) ? $_GET['bomb'] : '';
				$this->time_machine = $_GET['time'];
				
			} else {
				$excel_params 	= unserialize(gzinflate(base64_decode($_GET['params'])));
				
				$rowlabel 		= $excel_params['rowlabel'];
				$columnlabel 	= $excel_params['columnlabel'];
				$bomb			= $excel_params['bomb'];  //added for bomb indication
				$this->time_machine = $excel_params['time'];
				$non_assoc_upm_params	= $excel_params['upm'];
				
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
			if(isset($_GET['institution']) && $_GET['institution'] != '') {
				array_push($this->fid, 'institution_type');



				$sp = new SearchParam();
				$sp->field 	= 'institution_type';
				$sp->action = 'search';
				$sp->value 	= $_GET['institution'];
				$ins_params = array($sp);
			}
			
			$params = array_merge($this->params, $excel_params, $ins_params);
			if($bomb != '') {
				echo ('<span><img src="./images/' . $this->bomb_img_arr[$bomb] . '" alt="Bomb"  /></span>'
				. '&nbsp;This cell has a ' . $this->bomb_type_arr[$bomb] . ' <a href="./help/bomb.html">bomb</a>');
			}
			echo ('</td><td class="result">Results for ' . htmlformat($rowlabel) . ' in ' . htmlformat($columnlabel) . '</td>' . '</tr></table>');
			echo('<br clear="all"/><br/>');		
			
			$arr = array();
			$nct = array();
			$trial_arr 		= array();
			$allUpmDetails	= array();
			$upmDetails	 	= array();
			
			$arrr = search($params,$this->fid,NULL,$this->time_machine);
			
			foreach($arrr as $k => $v) {
				foreach($v as $kk => $vv) {
					if($kk != 'NCT/condition' && $kk != 'NCT/intervention_name' && 'NCT/lead_sponsor')
						$arr[$v['NCT/nct_id']][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;//$arr[$k][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;
					else
						$arr[$v['NCT/nct_id']][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;//$arr[$k][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;
				}
			}
			
			foreach($arr as $key => $val) { 
			
				//checking for updated and new trials
				$nct[$val['NCT/nct_id']] = getNCT($val['NCT/nct_id'], $val['larvol_id'], $this->time_machine, $this->edited);
				
				if (!is_array($nct[$val['NCT/nct_id']])) { 
					$nct=array();
					$val['NCT/intervention_name'] = '(study not in database)';
				}
				$trial_arr[] = $val['NCT/nct_id'] . ', ' . $val['larvol_id']; 
				
				//checking for updated and new unmatched upms.
				$allUpmDetails[$val['NCT/nct_id']] = getCorrespondingUPM($val['NCT/nct_id'], $this->time_machine, $this->edited);
				if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) {
				
					if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
						$fin_arr[$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
						
					foreach($allUpmDetails[$val['NCT/nct_id']] as $kk => $vv) {
						if(isset($vv['edited']) && !empty($vv['edited'])) {
							$upmDetails[$val['NCT/nct_id']][] = $vv;
						}
					}	
				} else {
					$fin_arr[$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
					$upmDetails[$val['NCT/nct_id']] = $allUpmDetails[$val['NCT/nct_id']];
				}	
				if(in_array($val['NCT/overall_status'],$this->actfilterarr)) {
					$totactivecount++;
					$activephase[] = $val['NCT/phase'];
				} else {
					$totinactivecount++;
					$inactivephase[] = $val['NCT/phase'];
				}
			}
			
			/*--------------------------------------------------------
			|Variables set for count when filtered by institution_type
			---------------------------------------------------------*/
			if(isset($_GET['instparams']) && $_GET['instparams'] != '') {
			
				$insparams = $_GET['instparams'];
			
			} else {
			
				$insparams = rawurlencode(base64_encode(gzdeflate(serialize(array('actphase' => $activephase,
																	'inactphase' => $inactivephase,
																	'actcnt' => $totactivecount,
																	'inactcnt' => $totinactivecount)))));
			}
			
			foreach($fin_arr as $key => $new_arr) {
				if($this->inactflag == 1) { 
					
					if(in_array($new_arr['NCT/overall_status'], $this->inactfilterarr)) {
						
						if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
						|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) {
							
							$vall = implode(",",array_keys($this->inactfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_GET)) {
								$this->inactivearray[] = $new_arr;
								$this->inactivecount++;		
							} 
						} else {
								$this->inactivearray[] = $new_arr;
								$this->inactivecount++;	
						}
					}
				
				} else if($this->allflag == 1) {
					 
					if(in_array($new_arr['NCT/overall_status'], $this->allfilterarr)) {
						
						if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
						|| isset($_GET['a']) || isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) 
						|| isset($_GET['nla']) || isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) 
						|| isset($_GET['c'])) {	
						
						$vall = implode(",",array_keys($this->allfilterarr, $new_arr['NCT/overall_status']));
						if(array_key_exists($vall, $_GET)) {
						
							$this->allarray[] = $new_arr;
							$this->allcount++;	
						} 
					} else {
					
						$this->allarray[] = $new_arr;	
						$this->allcount++;
					}
				}	
			
				} else {
			
					if(in_array($new_arr['NCT/overall_status'], $this->actfilterarr) ) {
						if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
						|| isset($_GET['a'])) {
						
							$vall = implode(",",array_keys($this->actfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_GET)) { 
							
								$this->activearray[] = $new_arr;
								$this->activecount++;	
							} 
						} else {
						
							$this->activearray[] = $new_arr;	
							$this->activecount++;
						}	
					}
				}
			}
			
			$count = count($this->{$this->type});
			
			if(isset($_GET['institution']) && $_GET['institution'] != '') {
				$ins = unserialize(gzinflate(base64_decode(rawurldecode($insparams))));
				$this->commonControls($count, $ins['actcnt'], $ins['inactcnt'], ($ins['actcnt'] + $ins['inactcnt']), $ins['actphase'], $ins['inactphase']);
			} else {
				$this->commonControls($count, $totactivecount, $totinactivecount, ($totactivecount + $totinactivecount), $activephase, $inactivephase);
			}
			echo ('<br/><br clear="all" />');
			
			if(isset($_GET['results'])) {
				echo ('<input type="hidden" name="results" value="' . $_GET['results'] . '"/>'
						. '<input type="hidden" name="time" value="' . $_GET['time'] . '"/>');
				if(isset($_GET['bomb']))
					echo ('<input type="hidden" name="bomb" value="' . $_GET['bomb'] . '" />');
			} else {
				echo ('<input type="hidden" name="params" value="' . $_GET['params'] . '"/>'
						. '<input type="hidden" name="leading" value="' . $_GET['leading'] . '"/>');
			}
			echo ('<input type="hidden" name="instparams" value="' . $insparams . '" />');
			
			$this->pstart 	= '';$this->last = '';$this->pend = '';$this->pages = '';
			
			$this->pstart 	= ($page-1) * $this->results_per_page + 1;
			$this->pend 	= $this->pstart + $this->results_per_page - 1;
			$this->pages 	= ceil($count / $this->results_per_page);
			$this->last 	= ($page * $this->results_per_page > $count) ? $count : $this->pend;

			if($count > $this->results_per_page) {
				if(isset($_GET['results']))
					$this->pagination($page, $count, $_GET['results'], $_GET['time'], 'normal', NULL);
				else 
					$this->pagination($page, $count, $_GET['params'], $_GET['leading'], 'normal', NULL);
			}
			$this->displayHeader();
			
			if(isset($non_assoc_upm_params) && !empty($non_assoc_upm_params)) {
				$upm_string = $this->getNonAssocUpm($non_assoc_upm_params, 'ott');
				if($upm_string != '') {
					echo ('<tr class="trialtitles">'
							. '<td colspan="50" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
							. 'style="border-bottom:1px solid blue" onclick="sh(this,\'ott\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
							. '</td></tr>' . $upm_string);
				} 
			}
			if($count > 0) {
			
				displayContent($this->displist, $this->{$this->type}, $this->edited, $this->time_machine, 
				$this->pstart, $this->last, $this->phase_arr, $fin_arr, $this->actfilterarr, $this->current_yr, $this->second_yr, $this->third_yr, $upmDetails);
				
			} else {
			
				echo ('<tr><th colspan="50" class="norecord" align="left">No record found.</th></tr>');
			}
			echo('</table><br/><br/>');
			echo ('</form>');
		}
		
	}

	function displayHeader() {
	
		echo ('<table width="100%" border="0" cellpadding="4" cellspacing="0" class="manage">'
			 . '<tr><th rowspan="2" style="width:250px;">Title</th>'
			 . '<th style="width:28px;" title="gray values are anticipated and black values are actual">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'en\');">N</a></th>'
			 . '<th rowspan="2" style="width:45px;" title=\'"EU" = European Union\'>Region</th>'
			 . '<th style="width:55px;">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'os\');">Status</a></th>'
			 . '<th rowspan="2" style="width:110px;">Sponsor</th>'
			 . '<th rowspan="2" style="width:110px;">Conditions</th>'
			 . '<th rowspan="2" style="width:110px;">Interventions</th>'
			 . '<th style="width:25px;" title="MM/YY">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'sd\');">Start</a></th>'
			 . '<th style="width:25px;" title="MM/YY">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'ed\');">End</a></th>'
			 . '<th style="width:22px;">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'ph\');">Ph</a></th>'
			 . '<th rowspan="2" style="width:12px;padding:4px;"><div class="box_rotate">result</div></th>'
			 . '<th colspan="36" style="width:72px;">'
			 . '<div>&nbsp;</div></th>'
			 . '<th colspan="3" style="width:3px;" class="rightborder noborder"></th></tr>'
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
			 . '<th colspan="3" style="width:28px;" class="rightborder">+</th></tr>');

	}
	
	function getNonAssocUpm($non_assoc_upm_params, $trialheader) {
		
		global $now;

		$upm_arr = array();$record_arr = array();$unmatched_upm_arr = array();$upm_string = '';
		$upm_arr = getNonAssocUpmRecords($non_assoc_upm_params);
		$record_arr = getUnmatchedUpmChanges($upm_arr, $this->time_machine, $this->edited);
		
		foreach($record_arr as $key => $val) {
			
			if(isset($_GET['chkOnlyUpdated']) && $_GET['chkOnlyUpdated'] == 1) {
			
			 	if(!empty($val['edited']) && $val['new'] == 'n') {
					if( ($val['event_description'] == $val['edited']['event_description']) && ($val['event_link'] == $val['edited']['event_link']) && 
					($val['event_type'] == $val['edited']['event_type']) && ($val['start_date'] == $val['edited']['start_date']) && 
					($val['start_date_type'] == $val['edited']['start_date_type']) && ($val['end_date'] == $val['edited']['end_date']) && 

					($val['end_date_type'] == $val['edited']['end_date_type']) ){ 
						unset($record_arr[$key]);
					} 
				} else if(empty($val['edited']) && $val['new'] == 'n') {
					unset($record_arr[$key]);
				}
			} 		
		}
		
		if(!empty($record_arr)) {
		
			$cntr = 0;
			$upm_string .= '<tr class="upms ' . $trialheader . '">'
							. '<th colspan="3" nowrap="nowrap" class="titleupmodd">Upcoming Product Milestones not associated with a specific trial</th>'
							. '<th colspan="3" class="titleupmodd">Milestone Type</th>'
							. '<th colspan="2" class="titleupmodd">Status</th>'
							. '<th  class="titleupmodd" title="MM/YY">Start</th>'
							. '<th  class="titleupmodd" title="MM/YY" colspan="2">End</th>'
							. '<th colspan="12"  class="titleupmodd">' . $this->current_yr . '</th>'
							. '<th colspan="12" class="titleupmodd">' . $this->second_yr . '</th>'
							. '<th colspan="12" class="titleupmodd">' . $this->third_yr . '</th>'
							. '<th colspan="3" class="rightborder titleupmodd">+</th>'
							. '</tr>';
			
			foreach($record_arr as $key => $val) {
			
				$unassoc_upm_status = '';
				$title = '';$attr = '';
				$class = 'class = "upms ' . $trialheader . '" ';
				$title_link_color = 'color:#000;';
				$date_style = 'color:gray;';
				
				if($cntr%2 == 1) {
		
					$row_type_one = 'alttitle';
					$row_type_two = 'altrow';
					
				} else {
				
					$row_type_one = 'title';
					$row_type_two = 'row';
				}	
				
				//Highlighting the whole row in case of new trials
				if($val['new'] == 'y') {
					$class = 'class="upms newtrial ' . $trialheader . '" ';
				}
				
				$upm_string .= '<tr ' . $class . '>';
				
				if(!empty($val['edited']) && $val['edited']['event_description'] != $val['event_description']) {
				
					$title_link_color = 'color:#FF0000;';$attr = ' highlight'; 
					if($val['edited']['event_description'] != '' || $val['edited']['event_description'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['event_description'] . '" '; 
					else
						$title = ' title="No Previous value" ';
						
				} else if($val['new'] == 'y') {
					$title_link_color = 'color:#FF0000;';
					$title = ' title = "New record" ';
				}
				
				$upm_string .= '<td colspan="3" class="' . $row_type_one .  $attr . ' titleupm titleupmodd txtleft" ' . $title . '><div class="rowcollapse">';
				if($val['event_link'] != NULL && $val['event_link'] != '') {
					$upm_string .= '<a style="' . $title_link_color . '" href="' . $val['event_link'] . '">' . $val['event_description'] . '</a>';
				} else {
					$upm_string .= $val['event_description'];
				}
				$upm_string .= '</div></td>';
				
				$title = '';$attr = '';	
				if(!empty($val['edited']) && $val['edited']['event_type'] != $val['event_type']) {
				
					$attr = ' highlight'; 
					if($val['edited']['event_type'] != '' && $val['edited']['event_type'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['event_type'] . '" '; 
					else
						$title = ' title="No Previous value" ';
						
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
				}
				$upm_string .= '<td colspan="3" class="' . $row_type_two . $attr . ' titleupmodd" ' . $title . '><div class="rowcollapse">' 
						. $val['event_type'] . '</div></td>' . '<td colspan="2" class="' . $row_type_two . ' titleupmodd"><div class="rowcollapse">';
				
				if($val['start_date_type'] == 'anticipated' && ($val['start_date'] > date('Y-m-d', $now))) {
					$unassoc_upm_status .= 'Upcoming, ';
				}
				if($val['end_date_type'] == 'actual') {
					$unassoc_upm_status .= 'Occurred, ';
				}
				if(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') 
				&& ($val['end_date_previous_value'] != '' && $val['end_date_previous_value'] != NULL && $val['end_date_previous_value'] != '0000-00-00') 
				&& ($val['end_date'] > $val['end_date_previous_value']) && ($val['end_date'] > date('Y-m-d', $now))) {
					$unassoc_upm_status .= 'Delayed, ';
				}
				if(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') 
				&& ($val['end_date'] < date('Y-m-d', $now)) && ($val['result_link'] == '' || $val['result_link'] == NULL)) {
					$unassoc_upm_status .= 'Pending, ';
				}
				if(($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00')
				&& ($val['start_date_previous_value'] != '' && $val['start_date_previous_value'] != NULL && $val['start_date_previous_value'] != '0000-00-00')
				&& ($val['start_date'] < $val['start_date_previous_value'])) {
					$unassoc_upm_status .= 'Accelerated, ';
				}
				if(($val['start_date_previous_value'] != '' && $val['start_date_previous_value'] != NULL && $val['end_date_previous_value'] != '' && 
				$val['end_date_previous_value'] != NULL) && (($val['start_date'] == NULL || $val['start_date'] == '' || $val['start_date'] == '0000-00-00') && 
				($val['end_date'] == NULL || $val['end_date'] == '' || $val['end_date'] == '0000-00-00'))) {
					$unassoc_upm_status .= 'Cancelled, ';
				}
				
				$upm_string .= substr($unassoc_upm_status,0,-2);
				$upm_string .= '</div></td>';
				
				$title = '';$attr = '';	
				if(!empty($val['edited']) && $val['edited']['start_date'] != $val['start_date']){
				
					$attr = ' highlight';$date_style = 'color:#973535;'; 
					if($val['edited']['start_date'] != '' && $val['edited']['start_date'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['start_date'] . '" '; 
					else 
						$title = ' title="No Previous value" ';
						
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
					$date_style = 'color:#973535;'; 
				}
				if(!empty($val['edited']) && $val['edited']['start_date_type'] != $val['start_date_type']){
				
					$attr = ' highlight';$date_style = 'color:#973535;';
					if($val['edited']['start_date_type'] != '' && $val['edited']['start_date_type'] != NULL) {
						$title = ' title="Previous value: ' . 
						(($val['edited']['start_date'] != $val['start_date']) ? $val['edited']['start_date'] : '' ) 
						. ' ' .$val['edited']['start_date_type'] . '" '; 
					} else {
						$title = ' title="No Previous value" ';
					}
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
					$date_style = 'color:#973535;'; 
				}
								
				$upm_string .= '<td  class="' . $row_type_two . $attr . ' titleupmodd" ' . $title . '><div class="rowcollapse">';
				if($val['start_date_type'] == 'anticipated') {
				$upm_string .= '<span style="font-weight:bold;' . $date_style . '">' 
				. (($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00') ? date('m/y',strtotime($val['start_date'])) : '' )   
				. '</span>';
				} else {
					$upm_string .= 
					(($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00') ? date('m/y',strtotime($val['start_date'])) : '' );
				}
				
				$upm_string .= '</div></td>';
				
				$title = '';$attr = '';	
				if(!empty($val['edited']) && $val['edited']['end_date'] != $val['end_date']){
				
					$attr = ' highlight';$date_style = 'color:#973535;';
					if($val['edited']['end_date'] != '' && $val['edited']['end_date'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['end_date'] . '" '; 
					else 
						$title = ' title="No Previous value" ';
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
					$date_style = 'color:#973535;'; 
				}
				if(!empty($val['edited']) && $val['edited']['end_date_type'] != $val['end_date_type']){
				
					$attr = ' highlight';$date_style = 'color:#973535;'; 
					if($val['edited']['end_date_type'] != '' && $val['edited']['end_date_type'] != NULL) {
						$title = ' title="Previous value: ' . 
						(($val['edited']['end_date'] != $val['end_date']) ? $val['edited']['end_date'] : '' ) 
						. ' ' . $val['edited']['end_date_type'] . '" '; 
					} else {
						$title = ' title="No Previous value" ';

					}
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
					$date_style = 'color:#973535;'; 
				}
				
				$upm_string .= '<td colspan="2" class="' . $row_type_two . $attr . ' titleupmodd" ' . $title . '><div class="rowcollapse">';
				if($val['end_date_type'] == 'anticipated') {
					$upm_string .= '<span style="font-weight:bold;' . $date_style . '">' 
					. (($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') ? date('m/y',strtotime($val['end_date'])) : '' ) 
					. '</span>';
				} else {
					$upm_string .=  
					(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') ? date('m/y',strtotime($val['end_date'])) : '');
				}	
				
				$upm_string .= '</div></td>';
				
		$upm_string .= getCompletionChart(date('m',strtotime($val['start_date'])), date('Y',strtotime($val['start_date'])), 
		date('m',strtotime($val['end_date'])), date('Y',strtotime($val['end_date'])), $this->current_yr, $this->second_yr, 
		$this->third_yr, '#9966FF', $val['start_date'], $val['end_date']);
		
				$upm_string .= '</tr>';
				
				$cntr++;
			}
		} 
		
		return $upm_string;
	}
}

function displayContent($fieldlist, $type_arr, $edited, $gentime, $start, $last, $phase_arr, $fin_arr, $actfilterarr, $current_yr, $second_yr, $third_yr, $upmDetails) 
{
	$start = $start -1;
	
	for($i=$start;$i<$last;$i++) 
	{
		$rowspan = 1;
		$nctid =  $type_arr[$i]['NCT/nct_id'];
		
		$ph = str_replace('Phase ', '', trim($type_arr[$i]['NCT/phase']));
		
		$start_month = date('m',strtotime($type_arr[$i]['NCT/start_date']));
		$start_year = date('Y',strtotime($type_arr[$i]['NCT/start_date']));
		$end_month = date('m',strtotime($type_arr[$i]["inactive_date"]));
		$end_year = date('Y',strtotime($type_arr[$i]["inactive_date"]));
	
		$enroll_style = 'color:gray;';
		$title_link_color = '#000000;';
		
		$attr = ' ';
		if(isset($fin_arr[$nctid]['edited']) && in_array('NCT/brief_title',$fin_arr[$nctid]['edited'])) {
			$attr = ' highlight" title="' . $fin_arr[$nctid]['edited']['NCT/brief_title'];
			$title_link_color = '#FF0000;';
		} else if($fin_arr[$nctid]['new'] == 'y') {
			$attr = '" title="New record';
			$title_link_color = '#FF0000;';
		}
		
		if(isset($upmDetails[$nctid])) {
			
			$rowspan = count($upmDetails[$nctid])+1;
		}
		
		if($i%2 == 1) {
			$row_type_one = 'alttitle';
		} else {
			$row_type_one = 'title';
		}	
		
		echo '<tr ' . (($fin_arr[$nctid]['new'] == 'y') ? 'class="newtrial" ' : ''). ' >'
		. '<td rowspan="' . $rowspan . '" class="' . $row_type_one . ' ' . $attr . '">' 
		. '<div class="rowcollapse"><a style="color:' . $title_link_color 
		. '" href="http://clinicaltrials.gov/ct2/show/' . padnct($nctid) . '">';
		
		if(isset($type_arr[$i]['NCT/acronym']) && $type_arr[$i]['NCT/acronym'] != '') {
			echo '<b>' . htmlformat($type_arr[$i]['NCT/acronym']) . '</b>&nbsp;' . htmlformat($type_arr[$i]['NCT/brief_title']);
					
		} else {
			echo htmlformat($type_arr[$i]['NCT/brief_title']);
		}
				
		echo ('</a></div></td>');
		
		foreach($fieldlist as $k => $v) {
		
			$attr = ' ';
			$val = htmlformat($type_arr[$i][$v]);
			if($v == "NCT/enrollment"){
			
				if(isset($fin_arr[$nctid]['edited']) && in_array($v,$fin_arr[$nctid]['edited'])) {
				
					$attr = ' highlight" title="' . $fin_arr[$nctid]['edited'][$v];
					$enroll_style = 'color:#973535;';
					
				}	else if($fin_arr[$nctid]['new'] == 'y') {
				
					$attr = '" title="New record';
					$enroll_style = 'color:#973535;';
				}
				echo '<td nowrap="nowrap" rowspan="' . $rowspan . '" class="' . $row_type_one 
				. $attr . '"><div class="rowcollapse">';
				
					if($type_arr[$i]["NCT/enrollment_type"] != '') {
					
						if($type_arr[$i]["NCT/enrollment_type"] == 'Anticipated') { 
							echo '<span style="font-weight:bold;' . $enroll_style . '">' . $val . '</span>';
							
						} else if($type_arr[$i]["NCT/enrollment_type"] == 'Actual') {
							echo $val;
							
						} else { 
							echo $val . ' (' . $type_arr[$i]["NCT/enrollment_type"] . ')';
						}
					} else {
						echo $val;
					}
				echo '</div></td>';  
				
			} else if($v == "NCT/start_date") {
			
				if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])) {
					$attr = ' highlight" title="' . $fin_arr[$nctid]['edited'][$v] ;
				} else if($fin_arr[$nctid]['new'] == 'y') {
					$attr = '" title="New record';
				}
				
				echo '<td rowspan="' . $rowspan . '" class="' . $row_type_one . $attr . '" ><div class="rowcollapse">'; 
				if($type_arr[$i]["NCT/start_date"] != '' || $type_arr[$i]["NCT/start_date"] != NULL) {
					echo date('m/y',strtotime($type_arr[$i]["NCT/start_date"]));
				} else {
					echo '&nbsp;';
				}
				  
				echo '</div></td>';
				
				$attr = '';
				if($fin_arr[$nctid]['new'] == 'y') 
					$attr = ' title="New record" ';
					
				echo '<td rowspan="' . $rowspan . '" class="' . $row_type_one . '" ' . $attr . '><div class="rowcollapse">';
				if($type_arr[$i]["inactive_date"] != '' || $type_arr[$i]["inactive_date"] != NULL) {
					echo date('m/y',strtotime($type_arr[$i]["inactive_date"]));
				} else {
					echo '&nbsp;';
				}
				echo '</div></td>';
				
			} else if($v == "NCT/overall_status") {
		
				if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])) {
					$attr = 'class="highlight ' . $row_type_one . ' " title="' . $fin_arr[$nctid]['edited'][$v] . '" ';
				} else if($fin_arr[$nctid]['new'] == 'y') {
				 	$attr = 'title="New record" class="' . $row_type_one . '"' ;
				} else {
					$attr = 'class="' . $row_type_one . '"';
				}
					
				echo '<td ' . $attr . ' rowspan="' . $rowspan . '">'  
					. '<div class="rowcollapse">' . $val . '</div></td>';
			
			
			} else if($v == "NCT/condition") {
			
				if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])) {
					$attr = ' highlight" title="' . $fin_arr[$nctid]['edited'][$v];
				} else if($fin_arr[$nctid]['new'] == 'y') {
					$attr = '" title="New record';
				}
				
				echo '<td rowspan="' . $rowspan . '" class="' . $row_type_one . $attr . '">'
					. '<div class="rowcollapse">' . $val . '</div></td>';
					
			
			} else if($v == "NCT/intervention_name") {
			
				if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])){
					$attr = ' highlight" title="' . $fin_arr[$nctid]['edited'][$v];
				} else if($fin_arr[$nctid]['new'] == 'y') {
					$attr = '" title="New record';
				}
				
				echo '<td rowspan="' . $rowspan . '" class="' . $row_type_one . $attr . '">'
					. '<div class="rowcollapse">' . $val . '</div></td>';
				
			
			} else if($v == "NCT/phase") {
			
				if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])) {
					$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" ';
				} else if($fin_arr[$nctid]['new'] == 'y') {
					$attr = 'title="New record"';
				}
				
				if($ph != '' && $ph !== NULL) {
					$phase = (trim($type_arr[$i][$v]) == 'N/A') ? $ph : ('P' . $ph);
					$ph_color = $phase_arr[$ph];
				} else {
					$phase = 'N/A';
					$ph_color = $phase_arr['N/A'];
				}
				echo '<td rowspan="' . $rowspan . '" style="background-color:' . $ph_color . ';" ' . $attr . '>'
					. '<div class="rowcollapse">' . $phase . '</div></td>';
			
			
			} else if($v == "NCT/lead_sponsor") { 
			
			
				if(isset($fin_arr[$nctid]['edited']) && (in_array($v, $fin_arr[$nctid]['edited']) 
					|| in_array('NCT/collaborator', $fin_arr[$nctid]['edited']))) {
					
					$attr = ' highlight" title="';
					if(in_array($v, $fin_arr[$nctid]['edited']))
						$attr .= $fin_arr[$nctid]['edited'][$v] . ' ';
					
					if(in_array('NCT/collaborator', $fin_arr[$nctid]['edited'])) {
						$attr .= $fin_arr[$nctid]['edited']['NCT/collaborator'];
						$enroll_style = 'color:#973535;';
					}
					$attr .= '';
				} else if($fin_arr[$nctid]['new'] == 'y') {
					$attr = '" title="New record';

				}
				echo '<td rowspan="' . $rowspan . '" class="' . $row_type_one . $attr . '">'
					. '<div class="rowcollapse">' . $val . ' <span style="' . $enroll_style . '"> ' 
					. $type_arr[$i]["NCT/collaborator"] . ' </span></div></td>';
				
			} else if($v == 'region') {
			
				if($fin_arr[$nctid]['new'] == 'y') 
					$attr = 'title="New record"';
				
				echo '<td class="' . $row_type_one . '" rowspan="' . $rowspan . '" ' . $attr . '>'
				. '<div class="rowcollapse">' . $val . '</div></td>';
			} 
		}
		
		echo ('<td>&nbsp;</td>');
		
		//rendering project completion chart
		echo $str = getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, 
		$ph_color, $type_arr[$i]['NCT/start_date'], $type_arr[$i]['inactive_date']);
		
		echo '</tr>';
		
		if(isset($upmDetails[$nctid]) && !empty($upmDetails[$nctid])) {
			
			foreach($upmDetails[$nctid] as $k => $v) { 
			
				$str = '';$diamond = '';

				$st_month = date('m',strtotime($v[2]));
				$st_year = date('Y',strtotime($v[2]));
				$ed_month = date('m',strtotime($v[3]));
				$ed_year = date('Y',strtotime($v[3]));
				$upm_link = $v[1];
				$upm_result_link = $v[4];
				$upm_title = 'title="' . htmlformat($v[0]) . '"';
				
				echo ('<tr>');
				
				//rendering diamonds in case of end date is prior to the current year
				echo ('<td style="text-align:center;' . (($k < count($upmDetails[$nctid])-1) ? 'border-bottom:0;' : '' ) . '">');
				
				if(!empty($upmDetails[$nctid][$k]['edited']) && ($v[4] != $upmDetails[$nctid][$k]['edited'][3])) {
				
					if($upm_result_link != '' && $upm_result_link != NULL) {
						echo ('<div ' . $upm_title . '><a href="' 
						. $upm_result_link . '" style="color:#000;">'
						. '<img src="images/red-diamond.png" alt="diamond" style="padding-top: 3px;" border="0" /></a></div>');
					}
				} else {
				
					if($upm_result_link != '' && $upm_result_link != NULL) {
						echo ('<div ' . $upm_title . '><a href="' . $upm_result_link . '" style="color:#000;">'
						. '<img src="images/black-diamond.png" alt="diamond" style="padding-top: 3px;" border="0" /></a></div>');
					}
				}
				
				$date_updated = 'no';
				if((isset($upmDetails[$nctid][$k]['edited'][4]) && $v[2] != $upmDetails[$nctid][$k]['edited'][4]) || 
				(isset($upmDetails[$nctid][$k]['edited'][6]) && $v[3] != $upmDetails[$nctid][$k]['edited'][6])) {
					$date_updated = 'yes';
				} 
				
				if(($v[3] != '' && $v[3] != NULL && $v[3] != '0000-00-00') && ($v[3] < date('Y-m-d')) && ($upm_result_link == NULL || upm_result_link == '')){
					echo ('<div ' . $upm_title . '><img src="images/hourglass.png" alt="hourglass" border="0" /></div>');
				}
				echo ('</td>');
				
				//rendering upm (upcoming project completion) chart
				echo $str = getUPMChart($st_month, $st_year, $ed_month, $ed_year, $current_yr, $second_yr, $third_yr, $v[2], 
				$v[3], $upm_link, $upm_title, $date_updated);
				echo '</tr>';
			}
		}
	}
}

//get difference between two dates in months
function getColspan($start_dt, $end_dt) {
	
	$diff = round((strtotime($end_dt)-strtotime($start_dt))/2628000);
	return $diff;

}

//calculating the project completion chart in which the year ranges from the current year and next-to-next year
function getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, $bg_color, $start_date, $end_date){

	$attr_two = 'class="rightborder"';
	if(($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') && ($end_date == '' || $end_date == NULL || $end_date == '0000-00-00')) {
	
		$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	

	} else if($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') {
	
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
			$value = '<td colspan="12" style="background-color:' . $bg_color . ';">&nbsp;</td>'
			. '<td colspan="12" style="background-color:' . $bg_color . ';">&nbsp;</td>'
			. '<td colspan="12" style="background-color:' . $bg_color . ';">&nbsp;</td>'
			. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
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
			$value .= '<td colspan="' .(12 - $st) . '" style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. '<td colspan="12" style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. '<td colspan="12" style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
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
			$value .= '<td colspan="' .(12 - $st) . '" style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. '<td colspan="12" style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
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
			$value .= '<td colspan="' .(12 - $st) . '" style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
						
		}
			
	} else if($start_year > $third_yr) {
	
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';	
			
	} 
	return $value;
}

function getUPMChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, $start_date, $end_date, $upm_link, $upm_title, 
$date_updated)
{
	$attr = '';
	$attr_two = 'class="rightborder"';
	$background_color = 'background-color:#9966FF;';
	
	if($date_updated == 'yes') $attr = 'border:1px solid red;';
	
	if(($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') && ($end_date == '' || $end_date == NULL || $end_date == '0000-00-00')) {
	
		$value = '<td colspan="12"><div ' . $upm_title . '>' 
			.(( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
			. '<td colspan="12"><div ' . $upm_title . '>'
			. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
			. '<td colspan="12"><div ' . $upm_title . '>' . (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '')  
			. '</div></td>'
			. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>' 
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';	

	} else if($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') {
	
		$st = $end_month-1;
		if($end_year < $current_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';	
						
		} else if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';	
					
		} else if($end_year == $second_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>' 
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
					
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';	
				
		} else if($end_year > $third_yr){
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" style="' . $background_color . '' . $attr . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
		}
	} else if($end_date == '' || $end_date == NULL || $end_date == '0000-00-00') {
	
		$st = $start_month-1;
		if($start_year < $current_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';	
						
		} else if($start_year == $current_yr) { 
			
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';	
					
		} else if($start_year == $second_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
					
		} else if($start_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
			 	. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td style="' . $background_color . 'width:2px;' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';	
				
		} else if($start_year > $third_yr){
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" style="' . $background_color . '' . $attr . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
		}
			
	} else if($start_year < $current_yr) {

		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;

		if($end_year < $current_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
			. '<td colspan="12"><div ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
			. '<td colspan="12"><div ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
			. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
		  
		} else if($end_year == $current_yr) { 
		
			if($end_month == 12) {
			
				$value = '<td style="' . $background_color . '" colspan="' . $end_month . '">' 
				. '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
				
			} else { 
			
				$value = '<td style="' . $background_color . '' . $attr . '" colspan="' . $end_month . '">' . '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td style="width:'.(12-$end_month).'px;" colspan="' . (12-$end_month) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
				
			}
		} else if($end_year == $second_yr) { 
		 
			if($end_month == 12) {
			
				$value = '<td style="' . $background_color . '' . $attr . '" colspan="24">' . '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
				
			} else {
			
				$value = '<td style="' . $background_color . '' . $attr . '" colspan="' . (12+$end_month) . '">' . '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="' . (12-$end_month) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : ''). '</div></td>';
				
			}
	
		} else if($end_year == $third_yr) { 
			
			if($end_month == 12) {
			
				$value = '<td style="' . $background_color . '' . $attr . '" colspan="36">' . '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
				
			} else {
			
				$value = '<td style="' . $background_color . '' . $attr . '" colspan="' . (24+$end_month) . '" ' . $class . '>' 
				. '<div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="' . (12-$end_month) . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'

				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
			}
		 
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12" style="' . $background_color . '' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12" style="' . $background_color . '' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12" style="' . $background_color . '' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" style="' . $background_color . '' . $attr . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';		
		}	
	
	} else if($start_year == $current_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '" ><div ' . $upm_title . '>'
			. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>' : '');
			
			if($val != 0) {
				$value .= '<td style="' . $background_color . '' . $attr . '" colspan="' . $val . '">'
						. '<div ' . $upm_title . '>'
						. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
						. (((12 - ($st+$val)) != 0) ? '<td colspan="' .(12 - ($st+$val)) . '"  style="' . $lineheight . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '' . $attr . '">'
						. '<div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"  style="' . $lineheight . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');			
			}
			
			$value .= '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
					. '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
		
		} else if($end_year == $second_yr) { 
		 
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
			
			if($val != 0) {
				$value .= '<td style="' . $background_color . '' . $attr . '" colspan="' . $val . '">'
						. '<div ' . $upm_title .' >'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '' . $attr . '">' . '<div ' . $upm_title .' >'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');			
			}
			
			$value .= '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
	
		} else if($end_year == $third_yr) {
				
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
				
			if($val != 0) {
				$value .= '<td style="' . $background_color . '' . $attr . '" colspan="' . $val . '">' . '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((36 - ($val+$st)) != 0) ? '<td colspan="' .(36 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '') ;
			} else {
				$value .= '<td style="' . $background_color . '' . $attr . '">'
						. '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((36 - (1+$st)) != 0) ? '<td colspan="' .(36 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '') ;			
			}
			
			$value .= '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
	
		} else if($end_year > $third_yr){
		
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(12 - $st) . '" style="' . $background_color . '' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12" style="' . $background_color . '' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12" style="' . $background_color . '' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" style="' . $background_color . '' . $attr . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';		
		}
		
	} else if($start_year == $second_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $second_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>' 
					. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</div></td>'
					. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
					
			if($val != 0) {
				$value .= '<td style="' . $background_color . '' . $attr . '" colspan="' . $val . '">' . '<div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '' . $attr . '">' . '<div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
			}
			
			$value .= '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';		
		
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
					. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
					
			if($val != 0) {
				$value .= '<td style="' . $background_color . '' . $attr . '" colspan="' . $val . '">' . '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '' . $attr . '">' . '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');			
			}
			$value .= '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';

		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(12 - $st) . '" style="' . $background_color . '' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12" style="' . $background_color . '' . $attr . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" style="' . $background_color . '' . $attr . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';		
		}
		
	} else if($start_year == $third_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;	
		if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
				
			if($val != 0) {
				$value .= '<td style="' . $background_color . '' . $attr . '" colspan="' . $val . '">' . '<div ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '' . $attr . '">' . '<div ' . $upm_title .'>' 
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
						. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '');			
			}
			
			$value .= '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
		
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' 
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>' : '')
				. '<td colspan="' . (12 - $st) . '" style="' . $background_color . '' . $attr . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';
		
		}
			
	} else if($start_year > $third_yr) {
	
			$value = '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="12"><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>'
				. '<td colspan="3" style="' . $background_color . '' . $attr . '" ' . $attr_two . '><div ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '') . '</div></td>';	
				
	}
	return $value;	
}

//return NCT fields given an NCTID
function getNCT($nct_id,$larvol_id,$time,$edited)
{	
						
	$study = array('edited' => array(), 'new' => 'n');
	
	$fieldnames = array('nct_id', 'brief_title', 'enrollment', 'enrollment_type', 'acronym', 'start_date', 'overall_status',
	'condition', 'intervention_name', 'phase', 'lead_sponsor', 'collaborator');

	$studycatData = mysql_fetch_assoc(mysql_query("SELECT `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_cats_in_study` `dc` ON "
	. "(`dc`.`id`=`dv`.`studycat`) WHERE `dv`.`field`='1' AND `dv`.`val_int`='" . $nct_id . "' AND `dc`.`larvol_id`='" .$larvol_id . "'"));

	$res = mysql_query("SELECT DISTINCT `df`.`name` AS `fieldname`, `df`.`id` AS `fieldid`, `df`.`type` AS `fieldtype`, `dv`.`studycat` "
		. "FROM `data_values` `dv` LEFT JOIN `data_fields` `df` ON (`df`.`id`=`dv`.`field`) WHERE `df`.`name` IN ('" 
		. join("','",$fieldnames) . "') AND `studycat` = '" . $studycatData['studycat'] 
		. "' AND (`dv`.`superceded`<'" . date('Y-m-d',$time) . "' AND `dv`.`superceded`>= '" . date('Y-m-d',strtotime($edited,$time)) . "') ");

	while ($row = mysql_fetch_assoc($res)) {
	 	
		$study['edited'][] = 'NCT/'.$row['fieldname'];
		
		//getting previous value for updated trials
		$val = '';
		$result = mysql_fetch_assoc(mysql_query("SELECT `" . 'val_'.$row['fieldtype'] ."` AS value FROM `data_values` WHERE `studycat` = '" 
		. $studycatData['studycat'] . "' AND `field` =  '" . $row['fieldid'] . "' AND (`superceded`<'" . date('Y-m-d',$time) 
		. "' AND `superceded`>= '" . date('Y-m-d',strtotime($edited,$time)) . "') "));
		
		$val = $result['value'];
		
		//special case for enum fields
		if($row['fieldtype'] == 'enum') {
			$result = mysql_fetch_assoc(mysql_query("SELECT `value` FROM `data_enumvals` WHERE `field` = '" . $row['fieldid'] . "' AND `id` = '" . $val . "' "));
			$val 	= $result['value'];
		}
			
		if(isset($val) && $val != '')
			$study['edited']['NCT/'.$row['fieldname']] = 'Previous value: ' . $val;
		else 
			$study['edited']['NCT/'.$row['fieldname']] = 'No previous value';
	}
	
	$sql = "SELECT `clinical_study`.`larvol_id` FROM `clinical_study` WHERE `clinical_study`.`import_time` <= '" 
		. date('Y-m-d',$time) . "' AND `clinical_study`.`larvol_id` = '" .  $larvol_id
		. "' AND `clinical_study`.`import_time` >= '" . date('Y-m-d',strtotime($edited,$time)) . "' ";
		
	$result = mysql_query($sql);		

	if(mysql_num_rows($result) >= 1) {
		$study['new'] = 'y';
	} 
	
	return $study;
}

//Get html content by passing through htmlspecialchars
function htmlformat($str)
{
	return htmlspecialchars($str);
}

//getting corresponding UPM details for each of the trials
function getCorrespondingUPM($trial_id, $time, $edited) {
	
	$upm = array();
	$values = array();
					
	$result = mysql_query("SELECT id, corresponding_trial, event_description, event_link, result_link, start_date, end_date 
					FROM upm WHERE corresponding_trial = '" . $trial_id . "' ");
	
	$i = 0;			
	while($row = mysql_fetch_assoc($result)) {
	
		$upm[$i] = array($row['event_description'], $row['event_link'], $row['start_date'], $row['end_date'], $row['result_link'],);
		
		//Query for checking updates for upms.
		$sql = "SELECT `id`, `event_type`, `event_description`, `event_link`, `result_link`, `start_date`, `start_date_type`, `end_date`, `end_date_type` "
				. " FROM `upm_history` WHERE `id` = '" . $row['id'] . "' AND (`superceded` < '" . date('Y-m-d',$time) . "' AND `superceded` >= '" 
				. date('Y-m-d',strtotime($edited,$time)) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
		$res = mysql_query($sql);
		
		$upm[$i]['edited'] = array();
		while($arr = mysql_fetch_assoc($res)) {
			$upm[$i]['edited'] = array($arr['event_type'], $arr['event_description'], $arr['event_link'], $arr['result_link'], 
									$arr['start_date'], $arr['start_date_type'], $arr['end_date'], $arr['end_date_type'],);
		}
		$i++;
	}
	return $upm;
}

//get records for non associated upms
function getNonAssocUpmRecords($non_assoc_upm_params) {
	
	$where = '';$upms = array();
	foreach($non_assoc_upm_params as $key => $val){
		$where .= textEqual('product',$val);
	}
	
	$sql = "SELECT `id`, `event_description`, `event_link`, `result_link`, `event_type`, `start_date`, `start_date_type`, `end_date`, `end_date_type`, "
	. "(SELECT `end_date` FROM `upm_history` WHERE `upm_history`.`id` = `upm`.`id` ORDER BY `added` ASC LIMIT 0,1) AS end_date_previous_value, "
	. "(SELECT `start_date` FROM `upm_history` WHERE`upm_history`.`id` = `upm`.`id` ORDER BY `added` ASC LIMIT 0,1) AS start_date_previous_value "
	. "FROM `upm` WHERE (`corresponding_trial` IS NULL) AND " . $where;
	 
	$res = mysql_query($sql)  or tex('Bad SQL query getting unmatched upms ' . $sql);
	
	$i = 0;
	if(mysql_num_rows($res) > 0){
		while($row = mysql_fetch_assoc($res)) { 
		
			$upms[$i]['id'] = $row['id'];
			$upms[$i]['event_description'] = htmlspecialchars($row['event_description']);
			$upms[$i]['event_link'] = $row['event_link'];
			$upms[$i]['event_type'] = $row['event_type'];
			$upms[$i]['start_date'] = $row['start_date'];
			$upms[$i]['start_date_type'] = $row['start_date_type'];
			$upms[$i]['end_date'] 	= $row['end_date'];
			$upms[$i]['end_date_type'] = $row['end_date_type'];
			$upms[$i]['end_date_previous_value'] = $row['end_date_previous_value'];
			$upms[$i]['start_date_previous_value'] = $row['start_date_previous_value'];
			
			$i++;
		}
	}
	return $upms;
}

function getUnmatchedUpmChanges($record_arr, $time, $edited) {

	foreach($record_arr as $key => $value) {
	
		//echo "<br/>==>".
		$sql = "SELECT `id`, `event_type`, `event_description`, `event_link`, `result_link`, `start_date`, `start_date_type`, `end_date`, `end_date_type` "
				. " FROM `upm_history` WHERE `id` = '" . $value['id'] . "' AND (`superceded` < '" . date('Y-m-d',$time) . "' AND `superceded` >= '" 
				. date('Y-m-d',strtotime($edited,$time)) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
		$res = mysql_query($sql);
		
		$record_arr[$key]['edited'] = array();
		$record_arr[$key]['new'] = 'n';
		
		while($row = mysql_fetch_assoc($res)) {
		
			$record_arr[$key]['edited']['id'] = $row['id'];
			$record_arr[$key]['edited']['event_description'] = htmlspecialchars($row['event_description']);
			$record_arr[$key]['edited']['event_link'] = $row['event_link'];
			$record_arr[$key]['edited']['event_type'] = $row['event_type'];
			$record_arr[$key]['edited']['start_date'] = $row['start_date'];
			$record_arr[$key]['edited']['start_date_type'] = $row['start_date_type'];
			$record_arr[$key]['edited']['end_date'] 	= $row['end_date'];
			$record_arr[$key]['edited']['end_date_type'] = $row['end_date_type'];
			
		}
		
		//echo "<br/>==>"."SELECT `upm_history`.`id` FROM `upm_history` WHERE `upm_history`.`id` = '" . $value['id'] . "' ";
		$result = mysql_query("SELECT `upm_history`.`id` FROM `upm_history` WHERE `upm_history`.`id` = '" . $value['id'] . "' ");
		if(mysql_num_rows($result) < 1)
			$record_arr[$key]['new'] = 'y';
	}
	return $record_arr;
}

function getLinkDetails($tablename, $fieldname, $parameters, $param_value) {

	$query = "SELECT `" . $fieldname . "` FROM " . $tablename . " WHERE " . $parameters . " = '" . mysql_real_escape_string($param_value) . "' ";
	$res = mysql_fetch_assoc(mysql_query($query));
	return $res[$fieldname];
}
?>
</body>
</html>