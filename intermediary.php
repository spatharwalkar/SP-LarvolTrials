<?php
header('P3P: CP="CAO PSA OUR"');
session_start();
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
if(!isset($_GET['cparams']) && !isset($_GET['params'])) die('cell not set');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Online Trial Tracker</title>
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
		
		var value = document.getElementById(type).value;
		
		if(value == "") {
		
			document.getElementById(type).value = "des";
			if(document.getElementById('sortorder').value != '') {
				var v = document.getElementById('sortorder').value;
				if(v.indexOf(type) == -1) {
					document.getElementById('sortorder').value = document.getElementById('sortorder').value+type+"##";
				}
			} else { 
				document.getElementById('sortorder').value = type+"##";
			}
			
		} else if(value == "des") {
		
			document.getElementById(type).value = "asc";
			if(document.getElementById('sortorder').value != '') {
				var v = document.getElementById('sortorder').value;
				if(v.indexOf(type) == -1) {
					document.getElementById('sortorder').value = document.getElementById('sortorder').value+type+"##";
				}
			} else { 
				document.getElementById('sortorder').value = type+"##";
			}
			
		} else {
			
			if(document.getElementById('sortorder').value != '') { 
				var str = document.getElementById('sortorder').value;
				if(str.indexOf(type+'##') != -1) { 
				
					var t = str.replace(type+'##','');
				}
			}
			document.getElementById('sortorder').value = t;
			document.getElementById(type).value = "";
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
$content->chkType();

class ContentManager 
{
	
	private $params 		= array();
	private $fid 		= array();
	private $sortorder 	= array();
	private $allfilterarr = array();
	private $displist 	= array('Enrollment' => 'NCT/enrollment', 'Status' => 'NCT/overall_status', 
								'Sponsor' => 'NCT/lead_sponsor', 'Conditions' => 'NCT/condition', 
								'Interventions' => 'NCT/intervention_name','Study Dates' => 'NCT/start_date', 
								'Phase' => 'NCT/phase');
								
	private $imgscale 		= array('style="width:14px;height:14px;"', 'style="width:12px;height:12px;"', 
								'style="width:10px;height:10px;"', 'style="width:8px;height:8px;"', 
								'style="width:6px;height:6px;"');
								
	private $actfilterarr 	= array('nyr'=>'Not yet recruiting', 'r'=>'Recruiting', 'ebi'=>'Enrolling by invitation', 
								'anr'=>'Active, not recruiting', 'a'=>'Available');
								
	private $inactfilterarr 	= array('wh'=>'Withheld', 'afm'=>'Approved for marketing',
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
		
		$this->current_yr	= date('Y');
		$this->second_yr	= date('Y')+1;
		$this->third_yr		= date('Y')+2;
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
	
		if((!isset($_GET['enrollment']) || $_GET['enrollment'] == '') && (!isset($_GET['status']) || $_GET['status'] == '') && 
		(!isset($_GET['startdate']) || $_GET['startdate'] == '') && (!isset($_GET['enddate'])  || $_GET['enddate'] == '')
		&& (!isset($_GET['phase']) || $_GET['phase'] == '')) {
		
			$sp = new SearchParam();
			$sp->field = '_' . getFieldId('NCT', 'phase');
			$sp->action = 'descending';
			$this->params[] = $sp;
		
			$sp = new SearchParam();
			$sp->field = '_' . getFieldId('NCT', 'completion_date');
			$sp->action = 'ascending';
			$this->params[] = $sp;
			
			$this->p_style = 'style="width:14px;height:14px;"';
			$this->e_style = 'style="width:12px;height:12px;"';
		}
		
		if(isset($_GET['sortorder']) && $_GET['sortorder'] != '') {
			$this->sortorder = explode("##", $_GET['sortorder']);
			$this->sortorder = array_filter($this->sortorder);
			
			foreach($this->sortorder as $v) {
		
				$fieldname = array('enrollment' => 'enrollment', 'phase' => 'phase', 'status' =>'overall_status', 
					'startdate' => 'start_date','enddate' => 'completion_date');
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', $fieldname[$v]);
				$sp->action = ($_GET[$v] == 'des' ) ? 'descending' : 'ascending';
				$this->params[] = $sp;
			}
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
	
	function commonControls() {
	
		echo ('<div style="height:100px;"><div class="block"><div class="text">List</div>'
			. '<input type="radio" id="actlist" name="list" checked="checked" value="active" '
			. 'onchange="javascript: applyfilter(this.value);" />'
			. '&nbsp;<label for="actlist"><span style="color: #00B050;">'
			. ' Active Records </span></label>'
			. '<br/><input type="radio" id="inactlist" name="list" value="inactive" ' 
			. ((isset($_GET['list']) && $_GET['list'] == 'inactive') ? ' checked="checked"' : '')
			. 'onchange="javascript: applyfilter(this.value);" />&nbsp;<label for="inactlist">'
			. '<span style="color: #FF0000;">&nbsp;Inactive Records</span></label>'
			. '<br/><input type="radio" id="alllist" name="list" value="all"' 
			. ((isset($_GET['list']) && $_GET['list'] == 'all') ? ' checked="checked"' : '')
			. 'onchange="javascript: applyfilter(this.value);" />&nbsp;<label for="alllist">'
			. ' All Records </label></div>'
			. '<input type="hidden" id="status" name="status" value="' . (isset($_GET['status']) ? $_GET['status'] : '') . '" />' 
			. '<input type="hidden" id="phase" name="phase" value="' . (isset($_GET['phase']) ? $_GET['phase'] : '') . '" />' 
			. '<input type="hidden" id="enrollment" name="enrollment" value="' 
			. (isset($_GET['enrollment']) ? $_GET['enrollment'] : '') . '" />'
			. '<input type="hidden" id="startdate" name="startdate" value="' 
			. (isset($_GET['startdate']) ? $_GET['startdate'] : '') . '" />'
			. '<input type="hidden" id="enddate" name="enddate" value="' 
			. (isset($_GET['enddate']) ? $_GET['enddate'] : '') . '" />'
			. '<input type="hidden" id="sortorder" name="sortorder" value="' 
			. (isset($_GET['sortorder']) ? $_GET['sortorder'] : '') . '" />'
			. '<div class="drop"><div class="text">Show Only</div>'
			. '<span id="filteropt">' . (isset($_GET["list"]) ? $this->{$_GET["list"].'status'} : $this->activestatus) 
			. '</span></div>'
			. '<div class="block"><div class="text">Find changes from: </div>'
			. '<input type="radio" id="oneweek" name="edited" value="oneweek" ' 
			. ((!isset($_GET['edited']) || $_GET['edited'] == 'oneweek') ? 'checked="checked"' : '' ) . ' />'
			. '<label for="oneweek">1 Week</label><br/>'
			. '<input type="radio" id="onemonth" name="edited" value="onemonth" ' 
			. ((isset($_GET['edited']) && $_GET['edited'] == 'onemonth') ? 'checked="checked"' : '' ) . ' />'
			. '<label for="onemonth">1 Month</label>'
			. '</div></div><br/><div><input type="submit" value="Show"/>'
			. '<br/><br clear="all" />');
	
	}
	
	function pagination($cntr = NULL, $page, $count, $params, $leading, $tt_type) {
		
		$pager = '';
		//if(isset($_GET['jump']) && isset($_GET['jumpno'])) $this->page = mysql_real_escape_string($_GET['jumpno']);
		//if(isset($_GET['back'])) --$page;
		//if(isset($_GET['next'])) ++$page;
			
		$sort = '';
		if(isset($_GET['list'])) $sort .= '&amp;list='.$_GET['list']; else $sort .= '&amp;list=active'; 
		if(isset($_GET['enrolment']) && $_GET['enrolment'] != '') $sort .= '&amp;enrolment='.$_GET['status'];
		if(isset($_GET['status']) && $_GET['status'] != '') $sort .= '&amp;status='.$_GET['status'];
		if(isset($_GET['phase']) && $_GET['phase'] != '') $sort .= '&amp;phase='.$_GET['phase'];
		if(isset($_GET['startdate']) && $_GET['startdate'] != '') $sort .= '&amp;startdate='.$_GET['startdate'];
		if(isset($_GET['enddate']) && $_GET['enddate'] != '') $sort .= '&amp;enddate='.$_GET['enddate'];
		if(isset($_GET['sortorder']) && $_GET['sortorder'] != '') $sort .= '&amp;sortorder=' . rawurlencode($_GET['sortorder']);
		if(isset($_GET['edited']) && $_GET['edited'] != '') $sort .= '&amp;edited='.htmlspecialchars(trim($_GET['edited'])); 
		else $sort .= '&amp;edited=oneweek';
		
		foreach($this->actfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
		foreach($this->inactfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
		foreach($this->allfilterarr as $k=>$v) { if(isset($_GET[$k])) $sort .= '&amp;'.$k.'=1'; }
		
		if($tt_type == 'stack') {
			
			$url = '';
			foreach($_GET['params'] as $k => $v) {
				$url .= '&leading['.$k.']=' . rawurlencode($_GET['leading'][$k]) . '&params['.$k.']=' 
				. rawurlencode($_GET['params'][$k]);
				
				if(isset($_GET['pg'][$k]) && $k != $cntr)
					$url .= '&pg['.$k.']='.$_GET['pg'][$k];
			}
			
			if($this->pstart > 1)
			{
				$pager .= '<a href="intermediary.php?cparams=' . rawurlencode($_GET['cparams']) . $url
						. '&amp;pg['.$cntr.']=' . ($page-1) 
						. $sort . '">&lt;&lt; Previous Page (' . ($this->pstart - 1) . '-' 
						. ($this->pstart-1) . ')</a>';
			}
			$pager .= ' &nbsp; &nbsp; &nbsp; Studies Shown (' . $this->pstart . '-' . $this->pend . ') &nbsp; &nbsp; &nbsp; ';
			if($this->pend < $count)
			{
				$nextlast = ($this->last+1);
				if($nextlast > $count) $nextlast = $count;
				$pager .= '<a href="intermediary.php?cparams=' . rawurlencode($_GET['cparams']) . $url
						. '&amp;pg['.$cntr.']=' . ($page+1) 
						. $sort . '">Next Page (' . ($this->pstart+1) . '-' . $nextlast . ') &gt;&gt;</a>';
			}
		
		} else {
			if($this->pstart > 1)
			{
				$pager .= '<a href="intermediary.php?params=' . rawurlencode($params)
					. '&amp;page=' . ($page-1) . '&amp;leading=' . rawurlencode($leading)
					. $sort . '">&lt;&lt; Previous Page (' . ($this->pstart - 1) . '-' 
					. ($this->pstart-1) . ')</a>';
			}
			$pager .= ' &nbsp; &nbsp; &nbsp; Studies Shown (' . $this->pstart . '-' . $this->pend . ') &nbsp; &nbsp; &nbsp; ';
			if($this->pend < $count)
			{
				$nextlast = ($this->last+1);
				if($nextlast > $count) $nextlast = $count;
				$pager .= '<a href="intermediary.php?params=' . rawurlencode($params)
					. '&amp;page=' . ($page+1) . '&amp;leading=' . rawurlencode($leading)
					. $sort . '">Next Page (' . ($this->pstart+1) . '-' . $nextlast . ') &gt;&gt;</a>';
			}
		}
		echo $pager;

	}
	
	function chkType() {
	
		echo ('<table width="100%"><tr><td>'
			. '<img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" />'
			. '</td><td nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;">Interface Work In Progress</span>');
		
		//displaying row label and column label
		if(isset($_GET['cparams']))	{
		
			$page = array();
			$c_params 	= unserialize(gzinflate(base64_decode($_GET['cparams'])));
			$t 			= ($c_params['type'] == 'col') ? $c_params['columnlabel'] : $c_params['rowlabel'];
			$stack_type = ($c_params['type'] == 'col') ? 'rowlabel' : 'columnlabel';
			$this->gentime 	= $c_params['rundate'];
			$this->name 	= $c_params['name'];
			
			echo ('</td><td class="result">Results for ' . htmlformat($t) . '</td>' . '</tr></table>');
			echo('<br clear="all"/>');		
			echo('<form id="frmOtt" name="frmOtt" method="get" action="intermediary.php">');
			$this->commonControls();
			echo ('<input type="hidden" name="cparams" value="' . $_GET['cparams'] . '"/>');
			
			
			foreach($_GET['params'] as $pk => $pv) {
				
				$page[$pk] = 1;
				if(isset($_GET['pg'][$pk])) $page[$pk] = mysql_real_escape_string($_GET['pg'][$pk]); 
				if(!is_numeric($page[$pk])) die('non-numeric page');

				$excel_params = array();$params = array();
				$arr = array();$fin_arr = array();
				$bomb = ''; $time_machine = '';
				$totinactivecount = 0;
				$totactivecount = 0;
				$activecount = 0;
				$allcount = 0;
				$inactivecount = 0;
				
				$this->inactivearray 	= array();
				$this->allarray			= array();
				$this->activearray		= array();
				
				$excel_params = unserialize(gzinflate(base64_decode($pv)));
				$time_machine = $excel_params['time'];
				$results	= $excel_params['count'];
				$ltype 		= $excel_params[$stack_type];
				$bomb	 	= $excel_params['bomb'];
				
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
				
				if($excel_params === false)
				{
					$results = count($leadingIDs);
				}
				
				$params = array_merge($this->params, $excel_params);
				
				echo ('<input type="hidden" name="params['.$pk.']" value="' . $_GET['params'][$pk] . '"/>'
						. '<input type="hidden" name="leading['.$pk.']" value="' . $_GET['leading'][$pk] . '"/>');
				
				$this->displayHeader();
				
				$arrr = search($params,$this->fid,NULL,$time_machine);

				foreach($arrr as $k => $v) {
			
					foreach($v as $kk => $vv) {
					
						if($kk != 'NCT/condition' && $kk != 'NCT/intervention_name' && 'NCT/lead_sponsor')
							$arr[$k][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;
						else
							$arr[$k][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;
					}
				}
				foreach($arr as $key => $val) { 
			
					$nct = getNCT($val['NCT/nct_id'], $val['larvol_id'], $gentime, $this->edited); 
					if (!is_array($nct)) { 
						$nct=array();
						$val['NCT/intervention_name'] = '(study not in database)';
					}
					$fin_arr[$val['NCT/nct_id']] = array_merge($nct, $val);
					
					if(in_array($val['NCT/overall_status'],$this->actfilterarr))
						$totactivecount++;
					else
						$totinactivecount++;
				}
				foreach($fin_arr as $key => $new_arr)	 {
					
					if($this->inactflag == 1) { 
						
						if(in_array($new_arr['NCT/overall_status'], $this->inactfilterarr)) {
								
							if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
							|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) {
								
								$vall = implode(",",array_keys($this->inactfilterarr, $new_arr['NCT/overall_status']));
								if(array_key_exists($vall, $_GET)) {
									$this->inactivearray[] = $new_arr;	
									$inactivecount++;
								} 
							} else {
									$this->inactivearray[] = $new_arr;
									$inactivecount++;	
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
									$allcount++;	
								} 
							} else {
								$this->allarray[] = $new_arr;
								$allcount++;	
							}
						}	
					
					} else {
					
						if(in_array($new_arr['NCT/overall_status'], $this->actfilterarr) ) {
							if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
							|| isset($_GET['a'])) {
								$vall = implode(",",array_keys($this->actfilterarr, $new_arr['NCT/overall_status']));
								if(array_key_exists($vall, $_GET)) { 
									$this->activearray[] = $new_arr;
									$activecount++;	
								} 
							} else {
								$this->activearray[] = $new_arr;
								$activecount++;	
							}	
						}
					}
				}
				
				$count = count($this->{$this->type});
				$this->pstart 	= '';$this->last = '';$this->pend = '';$this->pages = '';
				
				$this->pstart 	= ($page[$pk]-1) * $this->results_per_page + 1;
				$this->pend 	= $this->pstart + $this->results_per_page - 1;
				$this->pages 	= ceil($count / $this->results_per_page);
				$this->last 	= ($page[$pk] * $this->results_per_page > $count) ? $count : $this->pend;
				
				if($count > 1)
					$this->pagination($pk, $page[$pk], $count, $_GET['params'][$pk], $_GET['leading'][$pk], 'stack');

				if($bomb != '') {
					//$bomb = 'sb';
					echo('<br clear="all"/><br/>');	
					echo ('<span><img src="./images/' . $this->bomb_img_arr[$bomb] . '" alt="Bomb"  /></span>'
						. '&nbsp;This cell has a ' . $this->bomb_type_arr[$bomb] . ' <a href="./help/bomb.html">bomb</a>');
					
				}
				
				echo('<br clear="all"/><br/>');	
				echo ("<span style='color: #00B050;'>" . $totactivecount . " Active Records</span>&nbsp;&nbsp;&nbsp;"
						. "<span style='color: #FF0000;'>" . $totinactivecount . " Inactive Records</span>&nbsp;&nbsp;&nbsp;" 
						. ($totactivecount + $totinactivecount) . " All Records ");
				echo('<br clear="all"/><br/>');	
				echo $ltype;
				
				if($count > 0) {
			
					displayContent($params,$this->displist, $time_machine, $this->{$this->type}, $this->edited, $gentime, 
					$this->pstart, $this->last, $this->phase_arr, $fin_arr, $this->actfilterarr, $this->current_yr, 
					$this->second_yr, $this->third_yr);
				
				} else 
					echo ('<tr><th colspan="45" style="text-align: left;"> No record found. </th></tr>');
				
				echo('</table><br/><br/>');
			}
			
		} else {
		
			$page = 1;
			if(isset($_GET['page'])) $page = mysql_real_escape_string($_GET['page']);
			if(!is_numeric($page)) die('non-numeric page');

			$totinactivecount = 0;
			$totactivecount = 0;
			$activecount = 0;
			$allcount = 0;
			$inactivecount = 0;

			$excel_params 	= unserialize(gzinflate(base64_decode($_GET['params'])));
			$rowlabel 		= $excel_params['rowlabel'];
			$columnlabel 	= $excel_params['columnlabel'];
			$bomb			= $excel_params['bomb'];  //added for bomb indication
			
			$gentime 		= $excel_params['rundate'];
			$this->name 	= $excel_params['name'];
			$time_machine 	= $excel_params['time'];
			$results 		= $excel_params['count'];
			
			if($bomb != '') {
				//$bomb = 'sb';
				echo ('<span><img src="./images/' . $this->bomb_img_arr[$bomb] . '" alt="Bomb"  /></span>'
					. '&nbsp;This cell has a ' . $this->bomb_type_arr[$bomb] . ' <a href="./help/bomb.html">bomb</a>');
			}
			
			echo ('</td><td class="result">Results for ' . htmlformat($rowlabel) . ' in ' . htmlformat($columnlabel) . '</td>'
				. '</tr></table>');
				
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
			
			$params = array_merge($this->params, $excel_params);
		
			echo('<br clear="all"/><br/>');		
			echo('<form id="frmOtt" name="frmOtt" method="get" action="intermediary.php">');
			$this->commonControls();
			echo ('<input type="hidden" name="params" value="' . $_GET['params'] . '"/>'
					. '<input type="hidden" name="leading" value="' . $_GET['leading'] . '"/>');
			$this->displayHeader();
			
			$arr = array();
			//differentiating betwen active and inactive category of records.
			$arrr = search($params,$this->fid,NULL,$time_machine);

			foreach($arrr as $k => $v) {
				foreach($v as $kk => $vv) {
				
					if($kk != 'NCT/condition' && $kk != 'NCT/intervention_name' && 'NCT/lead_sponsor')
						$arr[$k][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;
					else
						$arr[$k][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;
				}
			}
			foreach($arr as $key=>$val) { 
			
				$nct = getNCT($val['NCT/nct_id'], $val['larvol_id'], $gentime, $edited); 
				if (!is_array($nct)) { 
					$nct=array();
					$val['NCT/intervention_name'] = '(study not in database)';
				}
				$fin_arr[$val['NCT/nct_id']] = array_merge($nct, $val);
					
				if(in_array($val['NCT/overall_status'],$this->actfilterarr))
					$totactivecount++;
				else
					$totinactivecount++;
			}
			foreach($fin_arr as $key => $new_arr) {
				if($this->inactflag == 1) { 
					
					if(in_array($new_arr['NCT/overall_status'], $this->inactfilterarr)) {
						
						if(isset($_GET['wh']) || isset($_GET['afm']) || isset($_GET['tna']) || isset($_GET['nla']) 
						|| isset($_GET['wd']) || isset($_GET['t']) || isset($_GET['s']) || isset($_GET['c'])) {
							
							$vall = implode(",",array_keys($this->inactfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_GET)) {
								$this->inactivearray[] = $new_arr;
								$inactivecount++;		
							} 
						} else {
								$this->inactivearray[] = $new_arr;
								$inactivecount++;	
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
							$allcount++;	
						} 
					} else {
						$this->allarray[] = $new_arr;	
						$allcount++;
					}
				}	
			
				} else {
			
					if(in_array($new_arr['NCT/overall_status'], $this->actfilterarr) ) {
						if(isset($_GET['nyr']) || isset($_GET['r']) || isset($_GET['ebi']) || isset($_GET['anr']) 
						|| isset($_GET['a'])) {
							$vall = implode(",",array_keys($this->actfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_GET)) { 
								$this->activearray[] = $new_arr;
								$activecount++;	
							} 
						} else {
							$this->activearray[] = $new_arr;	
							$activecount++;
						}	
					}
				}
			}
			$count = count($this->{$this->type});
			
			$this->pstart 	= '';$this->last = '';$this->pend = '';$this->pages = '';
			
			$this->pstart 	= ($page-1) * $this->results_per_page + 1;
			$this->pend 	= $this->pstart + $this->results_per_page - 1;
			$this->pages 	= ceil($count / $this->results_per_page);
			$this->last 	= ($page * $this->results_per_page > $count) ? $count : $this->pend;

			if($count > 1)
				$this->pagination(NULL, $page, $count, $_GET['params'], $_GET['leading'], 'normal');

			echo('<br clear="all"/><br/>');	
			echo ("<span style='color: #00B050;'>" . $totactivecount . " Active Records</span>&nbsp;&nbsp;&nbsp;" 
					. "<span style='color: #FF0000;'>" . $totinactivecount . " Inactive Records</span>&nbsp;&nbsp;&nbsp;" 
					. ($totactivecount + $totinactivecount) . " All Records ");
			echo('<br clear="all"/><br/>');	

			if($count > 0) {
			
			displayContent($params,$this->displist, $time_machine, $this->{$this->type}, $this->edited, $gentime, $this->pstart,
			$this->last, $this->phase_arr, $fin_arr, $this->actfilterarr, $this->current_yr, $this->second_yr, $this->third_yr);
				
			}else {
			
				echo ('<tr><th colspan="45" style="text-align: left;"> No record found. </th></tr>');
			}
		}
		
	}

	function displayHeader() {
	
		echo ('<table width="100%" border="0" cellpadding="4" cellspacing="0" class="manage">'
			 . '<tr><th rowspan="2" style="width:220px;">Title</th>'
			 . '<th style="width:28px;" title="gray values are anticipated and black values are actual">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'enrollment\');">N</a></th>'
			 . '<th style="width:55px;">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'status\');">Status</a></th>'
			 . '<th rowspan="2" style="width:130px;">Sponsor</th>'
			 . '<th rowspan="2" style="width:130px;">Conditions</th>'
			 . '<th rowspan="2" style="width:130px;">Interventions</th>'
			 . '<th style="width:29px;" title="MM/YY">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'startdate\');">Start</a></th>'
			 . '<th style="width:27px;" title="MM/YY">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'enddate\');">End</a></th>'
			 . '<th style="width:16px;">'
			 . '<a href="javascript: void(0);" onclick="javascript: doSorting(\'phase\');">Ph</a></th>'
			 . '<th colspan="36" style="width:72px;"><div style="white-space:nowrap;">'
			 . 'Projected<br/>Completion</div></th></tr>'
			 . '<tr><th>');
		
		if(isset($_GET['enrollment']) && $_GET['enrollment'] != '') {
			$img_style = array_search('enrollment', $this->sortorder);
			echo "<img src='images/".$_GET['enrollment'].".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		echo '</th><th>';
		if(isset($_GET['status']) && $_GET['status'] != '') {
			$img_style = array_search('status', $this->sortorder);
			echo "<img src='images/".$_GET['status'].".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		echo '</th><th>';
		if(isset($_GET['startdate']) && $_GET['startdate'] != '') {
			$img_style = array_search('startdate', $this->sortorder);
			echo "<img src='images/".$_GET['startdate'].".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		echo '</th><th>';
		if($this->e_style) {
			echo "<img src='images/asc.png' " . $this->e_style . " border='0' alt='Sort' />";
		}
		if(isset($_GET['enddate']) && $_GET['enddate'] != '') {
			$img_style = array_search('enddate', $this->sortorder);
			echo "<img src='images/".$_GET['enddate'].".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		echo '</th><th>';
		if($this->p_style) {
			echo "<img src='images/des.png' " . $this->p_style . " border='0' alt='Sort' />";
		}
		if(isset($_GET['phase']) && $_GET['phase'] != '') {
			$img_style = array_search('phase', $this->sortorder);
			echo "<img src='images/".$_GET['phase'].".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		echo ('</th><th colspan="12" style="width:26px;padding-left:0;padding-right:0;">' . $this->current_yr . '</th>'
			 . '<th colspan="12" style="width:26px;padding-left:0;padding-right:0;">' . $this->second_yr . '</th>'
			 . '<th colspan="12" style="width:26px;padding-left:0;padding-right:0;">' . $this->third_yr . '</th></tr>');

	}

}

function displayContent($params, $fieldlist, $time_machine, $type_arr, $edited, $gentime, $start, $last, $phase_arr, $fin_arr, $actfilterarr, $current_yr, $second_yr, $third_yr) {
	//echo "<br/><pre>==>";print_r($type_arr);
	//echo "<br/><pre>==>";print_r($fin_arr);
	//echo $start . $last;exit;
	//echo "<br/><pre>==>";print_r($fieldlist);
	$start = $start -1;
	for($i=$start;$i<$last;$i++) 
	{
	
		$nctid =  $type_arr[$i]['NCT/nct_id'];
		$pnctid =  padnct($type_arr[$i]['NCT/nct_id']);
		
		$end_date = getEndDate($type_arr[$i]["NCT/primary_completion_date"], $type_arr[$i]["NCT/completion_date"]);
		$ph = str_replace('Phase ', '', $type_arr[$i]['NCT/phase']);
		
		$start_month = date('m',strtotime($type_arr[$i]['NCT/start_date']));
		$start_year = date('Y',strtotime($type_arr[$i]['NCT/start_date']));
		$end_month = date('m',strtotime($end_date));
		$end_year = date('Y',strtotime($end_date));
	
		$attr_one = '';$attr_two = '';
		
		if(isset($fin_arr[$nctid]['edited']['NCT/brief_title'])) {
			$attr_one = ' highlight';
			$attr_two = 'title="' . $fin_arr[$nctid]['edited']['NCT/brief_title'] . '" ';
		}
		echo '<tr>'
			. '<td class="title' . $attr_one . '" ' . $attr_two . '>'
			. '<div class="rowcollapse"><a href="http://clinicaltrials.gov/ct2/show/' 
			. $pnctid . '">';
		
				if(isset($type_arr[$i]['NCT/acronym']) && $type_arr[$i]['NCT/acronym'] != '') {
					echo '<b>' . htmlformat($type_arr[$i]['NCT/acronym']) 
						. '</b>&nbsp;' . htmlformat($type_arr[$i]['NCT/brief_title']);
							
				} else {
					echo htmlformat($type_arr[$i]['NCT/brief_title']);
				}
				
		echo '</a></div></td>';
		foreach($fieldlist as $k => $v) {
		
			$attr = ' ';
			$val = htmlformat($type_arr[$i][$v]);
			if($v == "NCT/enrollment"){
			
				if(isset($fin_arr[$nctid]['edited']['NCT/enrollment']))
					$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" ';
					
				echo '<td nowrap="nowrap" style="background-color:#D8D3E0;text-align:center;" ' . $attr . ' >'
					. '<div class="rowcollapse">';
				
					if($type_arr[$i]["NCT/enrollment_type"] != '') {
					
						if($type_arr[$i]["NCT/enrollment_type"] == 'Anticipated') { 
							echo '<span style="color:gray;font-weight:bold;">'	. $val . '</span>';
							
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
				
				if(is_array($fin_arr[$nctid]['edited']['NCT/start_date']))
					$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" ';

				echo '<td style="background-color:#EDEAFF;" ' . $attr . ' >'
					. '<div class="rowcollapse">' . date('m/y',strtotime($type_arr[$i]["NCT/start_date"])) . '</div></td>';
				
				if(isset($fin_arr[$nctid]['edited']['NCT/completion_date']) || 
					isset($fin_arr[$nctid]['edited']['NCT/primary_completion_date'])) {
					$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" '; }
					
				echo '<td style="background-color:#EDEAFF;" ' . $attr . '>';
					if($end_date != '') {
						echo '<div class="rowcollapse">' . date('m/y',strtotime($end_date)) . '</div></td>';
					} else {
						echo '&nbsp;</td>';
					}
					
			} else if($v == "NCT/overall_status") {
		
				if(isset($fin_arr[$nctid]['edited']['NCT/overall_status']))  
					$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" ';
				
				if(in_array($val, $actfilterarr))
					$attr .= 'style="background-color:#D8D3E0"';
				else
					$attr .= 'style="background-color:#EDEAFF"';
					
				echo '<td ' . $attr . '>'  
					. '<div class="rowcollapse">' . $val . '</div></td>';
			
			} else if($v == "NCT/condition") {
			
				if(isset($fin_arr[$nctid]['edited']['NCT/condition']))
					$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" ';

				echo '<td style="background-color:#EDEAFF;" ' . $attr . '>'
					. '<div class="rowcollapse">' . $val . '</div></td>';
					
			} else if($v == "NCT/intervention_name") {
			
				if(isset($fin_arr[$nctid]['edited']['NCT/intervention_name']))
					$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" ';

				echo '<td style="background-color:#EDEAFF;" ' . $attr . '>'
					. '<div class="rowcollapse">' . $val . '</div></td>';
				
			} else if($v == "NCT/phase") {
			
				if(isset($fin_arr[$nctid]['edited']['NCT/phase']))
					$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" ';

				$phase = ($type_arr[$i][$v] == 'N/A') ? $ph : ('P' . $ph);
				echo '<td style="background-color:'.$phase_arr[$ph] . '"' . $attr . '>'
					. '<div class="rowcollapse">' . $phase . '</div></td>';
			
			} else if($v == "NCT/lead_sponsor") { 
			
				if(isset($fin_arr[$nctid]['edited']['NCT/lead_sponsor']) || 
				isset($fin_arr[$nctid]['edited']['NCT/collaborator'])) {
						$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" '; }
				
				echo '<td style="background-color:#EDEAFF;" ' . $attr . '>'
					. '<div class="rowcollapse">' . $val . ' <span style="color:gray;"> ' . $type_arr[$i]["NCT/collaborator"] 
					. ' </span></div></td>';
				
			}
		}
		
		//getting the project completion chart
		echo $str = getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, 
		$phase_arr[$ph], $type_arr[$i]['NCT/start_date'], $end_date);
			//krumo($study);
		echo '</tr>';

	}
}
	
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
		
			} else if($end_year > $third_yr){
			
				$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
					. '<td style="background-color:' . $bg_color . '" colspan="' . (12 - $st) . '">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>'
					. '<td style="background-color:' . $bg_color . '" colspan="12">&nbsp;</td>';
			}
			
		} 
	return $value;
}

//return NCT fields given an NCTID
function getNCT($nct_id,$larvol_id,$time,$edited)
{	
	$study = array();

	$fieldnames = array('nct_id', 'brief_title', 'enrollment', 'enrollment_type', 'acronym', 'start_date', 'completion_date',
	'primary_completion_date', 'overall_status', 'condition', 'intervention_name', 'phase', 'lead_sponsor', 'collaborator');

	$studycatData=mysql_fetch_assoc(mysql_query("SELECT `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_cats_in_study` `dc` ON (`dc`.`id`=`dv`.`studycat`) WHERE `dv`.`field`='1' AND `dv`.`val_int`='".$nct_id."' AND `dc`.`larvol_id`='"
	.$larvol_id."'"));
	
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
		
		$study['edited']['NCT/'.$row['fieldname']] = ($val != '') ? $val : 'No previous value';
		
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
?>