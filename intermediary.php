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
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
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
	
		echo ('<div style="height:100px;"><div class="block"><div class="text">List</div>'
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
			. '</div></div><br/><input type="submit" value="Show"/>&nbsp;');
			 if(strlen($count)) { echo $count . '&nbsp;Records'; }
			
	
	}
	
	function pagination($cntr = NULL, $page, $count, $params, $leading, $tt_type, $stacktype) {
		
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
			
			$url = '';
			foreach($_GET['params'] as $k => $v) {
				$url .= '&leading['.$k.']=' . rawurlencode($_GET['leading'][$k]) . '&params['.$k.']=' 
				. rawurlencode($_GET['params'][$k]);
				
				if($stacktype == 'row') 
					$url .= '&rowupm['.$k.']=' . rawurlencode($_GET['rowupm'][$k]); 
				elseif($stacktype == 'col')
					$url .= '&colupm['.$k.']=' . rawurlencode($_GET['colupm'][$k]); 
					
				if(isset($_GET['pg'][$k]) && $k != $cntr)
					$url .= '&pg['.$k.']=' . $_GET['pg'][$k];
			}
			$url .= '&trunc=' . $_GET['trunc'];
			if($this->pstart > 1)
			{
				$pager .= '<a href="intermediary.php?cparams=' . rawurlencode($_GET['cparams']) . $url
						. '&amp;pg['.$cntr.']=' . ($page-1) 
						. $sort . '">&lt;&lt; Previous Page (' . ($this->pstart - 1) . '-' 
						. ($this->pstart-1) . ')</a>&nbsp;&nbsp;&nbsp;';
			}
			$pager .= 'Studies Shown (' . $this->pstart . '-' . $this->pend . ')&nbsp;&nbsp;&nbsp;';
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
			$pager .= 'Studies Shown (' . $this->pstart . '-' . $this->pend . ')&nbsp;&nbsp;&nbsp;';
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
		
		if(isset($_GET['cparams']))	{
		
			$page = array();$ins_params = array();
			$c_params 	= unserialize(gzinflate(base64_decode($_GET['cparams'])));
			$t 			= ($c_params['type'] == 'col') ? $c_params['columnlabel'] : $c_params['rowlabel'];
			$stack_type = ($c_params['type'] == 'col') ? 'rowlabel' : 'columnlabel';
			$this->gentime 	= $c_params['rundate'];
			$this->name 	= $c_params['name'];
			
			echo ('</td><td class="result">Results for ' . htmlformat($t) . '</td>' . '</tr></table>');
			echo('<br clear="all"/>');		
			echo('<form id="frmOtt" name="frmOtt" method="get" action="intermediary.php">');
			echo ('<input type="hidden" name="cparams" value="' . $_GET['cparams'] . '"/>'
					. '<input type="hidden" name="trunc" value="' . $_GET['trunc'] . '"/>');
			
			$this->commonControls(NULL, NULL, NULL, NULL, NULL, NULL);
			echo('<br clear="all"/><br/>');
			if(isset($_GET['institution']) && $_GET['institution'] != '') {
				
				array_push($this->fid, 'institution_type');
				
				$sp = new SearchParam();
				$sp->field 	= 'institution_type';
				$sp->action = 'search';
				$sp->value 	= $_GET['institution'];
				$ins_params = array($sp);
			}
			
			if($c_params['type'] == 'row') {
			
				$row_upm_arr = array();
				foreach($_GET['rowupm'] as $k => $v) {
				
					$val = unserialize(gzinflate(base64_decode($v)));
					if($val != '' && !empty($val)) {
						foreach($val as $vv)
							$row_upm_arr[$k] = $vv;
					}
				}
				if(isset($row_upm_arr) && !empty($row_upm_arr))
					$this->getNonAssocUpm($row_upm_arr);
			}
			
			foreach($_GET['params'] as $pk => $pv) {
				
				if($pk != 1)
					echo ('<hr/>');
				
				$page[$pk] = 1;
				if(isset($_GET['pg'][$pk])) $page[$pk] = mysql_real_escape_string($_GET['pg'][$pk]); 
				if(!is_numeric($page[$pk])) die('non-numeric page');

				$excel_params 	= array();$params = array();
				$arr 	= array();$fin_arr 		= array();
				$arrr 	= array();$trial_arr	= array();
				$upmDetails = array();
				
				$bomb = ''; $time_machine = '';
				$totinactivecount = 0;
				$totactivecount = 0;
				
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
				
				$params = array_merge($this->params, $excel_params, $ins_params);
				
				echo ('<input type="hidden" name="params['.$pk.']" value="' . $_GET['params'][$pk] . '"/>'
						. '<input type="hidden" name="leading['.$pk.']" value="' . $_GET['leading'][$pk] . '"/>');
				
				if($c_params['type'] == 'row') {
					echo ('<input type="hidden" name="rowupm['.$pk.']" value="' . $_GET['rowupm'][$pk] . '"/>');
				} else if($c_params['type'] == 'col'){
					echo ('<input type="hidden" name="colupm['.$pk.']" value="' . $_GET['colupm'][$pk] . '"/>');
				}
				
				$arrr = search($params,$this->fid,NULL,$time_machine);
				
				foreach($arrr as $k => $v) {
			
					foreach($v as $kk => $vv) {
					
						if($kk != 'NCT/condition' && $kk != 'NCT/intervention_name' && 'NCT/lead_sponsor')
							$arr[$k][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;
						else
							$arr[$k][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;
					}
				}
				
				$nct = array();
				foreach($arr as $key => $val) { 
					
					//checking for updated and new trials
					$nct[$val['NCT/nct_id']] = getNCT($val['NCT/nct_id'], $val['larvol_id'], $this->gentime, $this->edited);
					$trial_arr[] = $val['NCT/nct_id'] . ', ' . $val['larvol_id']; 
					
					//checking for updated and new unmatched upms.
					$allUpmDetails[$val['NCT/nct_id']] = getCorrespondingUPM($val['NCT/nct_id'], $this->gentime, $this->edited);
					
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
					
					if(in_array($val['NCT/overall_status'],$this->actfilterarr))
						$totactivecount++;
					else
						$totinactivecount++;
				}
				
				foreach($fin_arr as $key => $new_arr){
					
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
				$this->pstart 	= '';$this->last = '';$this->pend = '';$this->pages = '';
				
				$this->pstart 	= ($page[$pk]-1) * $this->results_per_page + 1;
				$this->pend 	= $this->pstart + $this->results_per_page - 1;
				$this->pages 	= ceil($count / $this->results_per_page);
				$this->last 	= ($page[$pk] * $this->results_per_page > $count) ? $count : $this->pend;
				
				echo $ltype;
				echo('<br clear="all"/>');
				if($c_params['type'] == 'col') { 
					
					$val = unserialize(gzinflate(base64_decode($_GET['colupm'][$pk])));
					if($val != '' && !empty($val)) 
						$this->getNonAssocUpm($val);
				}
				
				if($bomb != '') {
					//$bomb = 'lb';
					echo ('<span><img src="./images/' . $this->bomb_img_arr[$bomb] . '" alt="Bomb"  /></span>'
						. '&nbsp;This cell has a ' . $this->bomb_type_arr[$bomb] . ' <a href="./help/bomb.html">bomb</a>');
					echo('<br clear="all"/>');
					
				}
				
				echo ("<span style='color: #00B050;'>" . $totactivecount . " Active Records</span>&nbsp;&nbsp;&nbsp;"
						. "<span style='color: #FF0000;'>" . $totinactivecount . " Inactive Records</span>&nbsp;&nbsp;&nbsp;" 
						. ($totactivecount + $totinactivecount) . " All Records ");
						
				if($count > $this->results_per_page) {
					echo('<br clear="all"/>');
					$this->pagination($pk, $page[$pk], $count, $_GET['params'][$pk], $_GET['leading'][$pk], 'stack', 
					$c_params['type']);
				}	
				$this->displayHeader();
				if($count > 0) {
			
					displayContent($params,$this->displist, $time_machine, $this->{$this->type}, $this->edited, $this->gentime, 
					$this->pstart, $this->last, $this->phase_arr, $fin_arr, $this->actfilterarr, $this->current_yr, 
					$this->second_yr, $this->third_yr, $upmDetails);
				
				} else 
					echo ('<tr><th colspan="50" class="norecord" align="left">No record found.</th></tr>');
					
				
				echo('</table><br/>');
				
			}
			if(isset($_GET['trunc'])) {
				$t = unserialize(gzinflate(base64_decode($_GET['trunc'])));
				if($t == 'y') echo ('<span style="font-size:10px;color:red;">Note: all data could not be shown</span>');
			}
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
			
			$excel_params 	= unserialize(gzinflate(base64_decode($_GET['params'])));
			
			$rowlabel 		= $excel_params['rowlabel'];
			$columnlabel 	= $excel_params['columnlabel'];
			$bomb			= $excel_params['bomb'];  //added for bomb indication
			
			$this->gentime 	= $excel_params['rundate'];
			$this->name 	= $excel_params['name'];
			$time_machine 	= $excel_params['time'];
			$results 		= $excel_params['count'];
			$non_assoc_upm_params	= $excel_params['upm'];
			
			if($bomb != '') {
				
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
				
			} else {
				$excel_params = $excel_params['params'];
			}
			
			if($excel_params === false)
			{
				$results = count($leadingIDs);
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
			
			echo('<br clear="all"/><br/>');		
			echo('<form id="frmOtt" name="frmOtt" method="get" action="intermediary.php">');
			
			$arr = array();
			$nct = array();
			$trial_arr 		= array();
			$allUpmDetails	= array();
			$upmDetails	 	= array();
			
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
			
				//checking for updated and new trials
				$nct[$val['NCT/nct_id']] = getNCT($val['NCT/nct_id'], $val['larvol_id'], $this->gentime, $this->edited);
				
				if (!is_array($nct[$val['NCT/nct_id']])) { 
					$nct=array();
					$val['NCT/intervention_name'] = '(study not in database)';
				}
				$trial_arr[] = $val['NCT/nct_id'] . ', ' . $val['larvol_id']; 
				
				//checking for updated and new unmatched upms.
				$allUpmDetails[$val['NCT/nct_id']] = getCorrespondingUPM($val['NCT/nct_id'], $this->gentime, $this->edited);
				
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
				
				$this->commonControls($count, $ins['actcnt'], $ins['inactcnt'], 
				($ins['actcnt'] + $ins['inactcnt']), $ins['actphase'], $ins['inactphase']);

			} else {
				$this->commonControls($count, $totactivecount, $totinactivecount, ($totactivecount + $totinactivecount), 
				$activephase, $inactivephase);
			}
			echo ('<br/><br clear="all" />');
			echo ('<input type="hidden" name="params" value="' . $_GET['params'] . '"/>'
					. '<input type="hidden" name="leading" value="' . $_GET['leading'] . '"/>'
					. '<input type="hidden" name="instparams" value="' . $insparams . '" />');
			
			if(isset($non_assoc_upm_params) && !empty($non_assoc_upm_params)) {
				$this->getNonAssocUpm($non_assoc_upm_params);
			}

			$this->pstart 	= '';$this->last = '';$this->pend = '';$this->pages = '';
			
			$this->pstart 	= ($page-1) * $this->results_per_page + 1;
			$this->pend 	= $this->pstart + $this->results_per_page - 1;
			$this->pages 	= ceil($count / $this->results_per_page);
			$this->last 	= ($page * $this->results_per_page > $count) ? $count : $this->pend;

			if($count > $this->results_per_page)
				$this->pagination(NULL, $page, $count, $_GET['params'], $_GET['leading'], 'normal', NULL);

			$this->displayHeader();
			if($count > 0) {
			
				displayContent($params,$this->displist, $time_machine, $this->{$this->type}, $this->edited, $this->gentime, 
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
	
	function getNonAssocUpm($non_assoc_upm_params) {
		
		$upm_arr = array();$record_arr = array();$unmatched_upm_arr = array();
		$upm_arr = getNonAssocUpmRecords($non_assoc_upm_params);
		$record_arr = getUnmatchedUpmChanges($upm_arr, $this->gentime, $this->edited);
		
		if(!empty($record_arr)) {
		
			$cntr = 0;
			echo ('<table width="100%" border="0" cellpadding="4" cellspacing="0" class="manage">'
				. '<tr><th style="width:490px;" nowrap="nowrap">'
				. 'Upcoming Product Milestones not associated with a specific trial</th>'
				. '<th style="width:130px;">Milestone Type</th>'
				. '<th style="width:340px;">Status</th>'
				. '<th style="width:30px;" title="MM/YY">Start</th>'
				. '<th style="width:30px;" title="MM/YY">End</th>'
				. '<th colspan="12" style="width:24px;">' . $this->current_yr . '</th>'
				. '<th colspan="12" style="width:24px;">' . $this->second_yr . '</th>'
				. '<th colspan="12" style="width:24px;">' . $this->third_yr . '</th>'
				. '<th colspan="3" style="width:12px;padding:0;margin:0;" class="rightborder">+</th>'
				. '</tr>');
			
			foreach($record_arr as $key => $val) {
			
				$un_status = '';$class = '';$title = '';$attr = '';
				$title_link_color = 'color:#000;';$date_style = 'color:gray;';
				
				
				if($cntr%2 == 1) {
		
					$row_type_one = 'alttitle';
					$row_type_two = 'altrow';
					
				} else {
				
					$row_type_one = 'title';
					$row_type_two = 'row';
				}	
				
				if(empty($val['edited'])) {
					$class = 'class = "newtrial"';
					$title_link_color = 'color:#FF0000;';
					$title = ' title = "New record" ';
				}
				
				echo ('<tr ' . $class . '>');
				
				if(!empty($val['edited']) && $val['edited']['event_description'] != $val['event_description']) {
				
					$title_link_color = 'color:#FF0000;';$attr = ' highlight'; 
					
					if($val['edited']['event_description'] != '' || $val['edited']['event_description'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['event_description'] . '" '; 
					else
						$title = ' title="No Previous value" ';
				}
				echo ('<td class="' . $row_type_one .  $attr . '" ' . $title . '><div class="rowcollapse"><a style="' . $title_link_color 
						. '" href="' . $val['event_link'] . '">' . $val['event_description'] . '</a></div></td>');
				
				$title = '';$attr = '';	
				if(empty($val['edited'])) {
					$title = ' title = "New record" ';
				}
				if(!empty($val['edited']) && $val['edited']['event_type'] != $val['event_type']) {
				
					$attr = ' highlight'; 
					if($val['edited']['event_type'] != '' && $val['edited']['event_type'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['event_type'] . '" '; 
					else
						$title = ' title="No Previous value" ';
				}
				echo ('<td class="' . $row_type_two . $attr . '" ' . $title . '><div class="rowcollapse">' . $val['event_type'] . '</div></td>'
						. '<td class="' . $row_type_two . '"><div class="rowcollapse">');
				
				if($val['start_date_type'] == 'anticipated') {
					$un_status .= 'Upcoming, ';
				}
				if($val['end_date_type'] == 'actual') {
					$un_status .= 'Occurred, ';
				}
				
				if(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') 
				&& ($val['end_date_previous_value'] != '' && $val['end_date_previous_value'] != NULL && $val['end_date_previous_value'] != '0000-00-00') 
				&& ($val['end_date'] > $val['end_date_previous_value'])) {
					$un_status .= 'Delayed, ';
				}
				if(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') 
				&& ($val['end_date'] < date('Y-m-d')) 
				&& ($val['result_link'] == '' || $val['result_link'] == NULL)) {
					$un_status .= 'Pending, ';
				}
				if(($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00')
				&& ($val['start_date_previous_value'] != '' && $val['start_date_previous_value'] != NULL && $val['start_date_previous_value'] != '0000-00-00')
				&& ($val['start_date'] < $val['start_date_previous_value'])) {
					$un_status .= 'Accelerated, ';
				}
				if(($val['start_date_previous_value'] != '' && $val['start_date_previous_value'] != NULL && $val['end_date_previous_value'] != '' && 
				$val['end_date_previous_value'] != NULL) && (($val['start_date'] == NULL || $val['start_date'] == '' || $val['start_date'] == '0000-00-00') && 
				($val['end_date'] == NULL || $val['end_date'] == '' || $val['end_date'] == '0000-00-00'))) {
					$un_status .= 'Cancelled, ';
				}
				
				echo substr($un_status,0,-2);
				echo ('</div></td>');
				
				$title = '';$attr = '';	
				if(empty($val['edited'])) {
					$date_style = 'color:#973535;';
					$title = ' title = "New record" ';
				}
				if(!empty($val['edited']) && $val['edited']['start_date'] != $val['start_date']){
				
					$attr = ' highlight';$date_style = 'color:#973535;'; 
					if($val['edited']['start_date'] != '' && $val['edited']['start_date'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['start_date'] . '" '; 
					else 
						$title = ' title="No Previous value" ';
				}
				if(!empty($val['edited']) && $val['edited']['start_date_type'] != $val['start_date_type']){
				
					$attr = ' highlight';$date_style = 'color:#973535;';
					if($val['edited']['start_date_type'] != '' && $val['edited']['start_date_type'] != NULL) {
						$title = ' title="Previous value: ' . 
						(($val['edited']['start_date'] != $val['start_date']) ? $val['edited']['start_date'] : '' ) 
						. $val['edited']['start_date_type'] . '" '; 
					} else {
						$title = ' title="No Previous value" ';
					}
				}
								
				echo ('<td class="' . $row_type_two . $attr . '" ' . $title . '><div class="rowcollapse">');
				if($val['start_date_type'] == 'anticipated') {
					echo '<span style="font-weight:bold;' . $date_style . '">' . date('m/y',strtotime($val['start_date']))  . '</span>';
				} else {
					echo date('m/y',strtotime($val['start_date']));
				}
				
				echo ('</div></td>');
				
				$title = '';$attr = '';	
				if(empty($val['edited'])) {
					$date_style = 'color:#973535;';
					$title = ' title = "New record" ';
				}
				if(!empty($val['edited']) && $val['edited']['end_date'] != $val['end_date']){
				
					$attr = ' highlight';$date_style = 'color:#973535;';
					if($val['edited']['end_date'] != '' && $val['edited']['end_date'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['end_date'] . '" '; 
					else 
						$title = ' title="No Previous value" ';
				}
				if(!empty($val['edited']) && $val['edited']['end_date_type'] != $val['end_date_type']){
				
					$attr = ' highlight';$date_style = 'color:#973535;'; 
					if($val['edited']['end_date_type'] != '' && $val['edited']['end_date_type'] != NULL) {
						$title = ' title="Previous value: ' . 
						(($val['edited']['end_date'] != $val['end_date']) ? $val['edited']['end_date'] : '' ) 
						. $val['edited']['end_date_type'] . '" '; 
					} else {
						$title = ' title="No Previous value" ';
					}
				}
				
				echo ('<td class="' . $row_type_two . $attr . '" ' . $title . '><div class="rowcollapse">');
				if($val['end_date_type'] == 'anticipated') {
					echo '<span style="font-weight:bold;' . $date_style . '">' . date('m/y',strtotime($val['end_date']))  . '</span>';
				} else {
					echo date('m/y',strtotime($val['end_date']));
				}	
				
				echo ('</div></td>');
				
		echo $str = getCompletionChart(date('m',strtotime($val['start_date'])), date('Y',strtotime($val['start_date'])), 
		date('m',strtotime($val['end_date'])), date('Y',strtotime($val['end_date'])), $this->current_yr, $this->second_yr, 
		$this->third_yr, '#9966FF', $val['start_date'], $val['end_date']);
		
				echo ('</tr>');
				
				$cntr++;
			}
			
			echo ('</table><br/>');
		} 
	}
}

function displayContent($params, $fieldlist, $time_machine, $type_arr, $edited, $gentime, $start, $last, $phase_arr, $fin_arr, $actfilterarr, $current_yr, $second_yr, $third_yr, $upmDetails) {
	
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
			echo '<b>' . htmlformat($type_arr[$i]['NCT/acronym']) 
				. '</b>&nbsp;' . htmlformat($type_arr[$i]['NCT/brief_title']);
					
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
		
		//echo "<pre>";print_r($upmDetails);
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

function getUPMChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, $start_date, $end_date, $upm_link, $upm_title, 
$date_updated)
{
	$attr = '';
	$attr_two = 'class="rightborder"';
	
	if($date_updated == 'yes') $attr = 'border:1px solid red;';
	
	if(($start_date == '' || $start_date == NULL) && ($end_date == '' || $end_date == NULL)) {
	
		$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';	

	} else if($start_date == '' || $start_date == NULL) {
	
		$st = $end_month-1;
		if($end_year < $current_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';	
						
		} else if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td style="background-color:#9966FF;width:2px;' . $attr . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';	
					
		} else if($end_year == $second_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td style="background-color:#9966FF;width:2px;' . $attr . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
					
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td style="background-color:#9966FF;width:2px;' . $attr . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';	
				
		} else if($end_year > $third_yr){
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" style="background-color:#9966FF;' . $attr . '" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link 
				. '">&nbsp;</a></div></td>';		
		}
	} else if($end_date == '' || $end_date == NULL) {
	
		$st = $start_month-1;
		if($start_year < $current_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';	
						
		} else if($start_year == $current_yr) { 
			
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td style="background-color:#9966FF;width:2px;' . $attr . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';	
					
		} else if($start_year == $second_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td style="background-color:#9966FF;width:2px;' . $attr . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
					
		} else if($start_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			 	. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td style="background-color:#9966FF;width:2px;' . $attr . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '')
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';	
				
		} else if($start_year > $third_yr){
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="3" style="background-color:#9966FF;' . $attr . '" ' . $attr_two . '><div ' 
			. $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';		
		}
			
	} else if($start_year < $current_yr) {

		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;

		if($end_year < $current_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
		  
		} else if($end_year == $current_yr) { 
		
			if($end_month == 12) {
			
				$value = '<td style="background-color:#9966FF;" colspan="' . $end_month . '">' 
				. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
				
			} else { 
			
				$value = '<td style="background-color:#9966FF;' . $attr . '" colspan="' . $end_month . '">' 
				. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td style="width:'.(12-$end_month).'px;" colspan="' . (12-$end_month) 
				. '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
				
			}
		} else if($end_year == $second_yr) { 
		 
			if($end_month == 12) {
			
				$value = '<td style="background-color:#9966FF;' . $attr . '" colspan="24">'
				. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
				
			} else {
			
				$value = '<td style="background-color:#9966FF;' . $attr . '" colspan="' . (12+$end_month) . '">' 
				. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="' . (12-$end_month) . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</div></a></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
				
			}
	
		} else if($end_year == $third_yr) { 
			
			if($end_month == 12) {
			
				$value = '<td style="background-color:#9966FF;' . $attr . '" colspan="36">' 
				. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
				
			} else {
			
				$value = '<td style="background-color:#9966FF;' . $attr . '" colspan="' . (24+$end_month) . '" ' . $class . '>' 
				. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="' . (12-$end_month) . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
			}
		 
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td style="background-color:#9966FF;' . $attr . '" colspan="3" ' . $attr_two . '><div ' 
			. $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
		}	
	
	} else if($start_year == $current_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '" ><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '');
			
			if($val != 0) {
				$value .= '<td style="background-color:#9966FF;' . $attr . '" colspan="' . $val . '">'
						. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((12 - ($st+$val)) != 0) ? '<td colspan="' .(12 - ($st+$val)) . '"  style="' . $lineheight 
						. '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '');
			} else {
				$value .= '<td style="background-color:#9966FF;' . $attr . '">'
						. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"  style="' . $lineheight 
						. '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '');			
			}
			
			$value .= '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
		
		} else if($end_year == $second_yr) { 
		 
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '');
			
			if($val != 0) {
				$value .= '<td style="background-color:#9966FF;' . $attr . '" colspan="' . $val . '">'
						. '<div ' . $upm_title .' ><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link 
						. '">&nbsp;</a></div></td>' : '');
			} else {
				$value .= '<td style="background-color:#9966FF;' . $attr . '">'
						. '<div ' . $upm_title .' ><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link  
						. '">&nbsp;</a></div></td>' : '');			
			}
			
			$value .= '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
	
		} else if($end_year == $third_yr) {
				
			$value = (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title 
					. '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '');
				
			if($val != 0) {
				$value .= '<td style="background-color:#9966FF;' . $attr . '" colspan="' . $val . '">'
						. '<div ' . $upm_title .'><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((36 - ($val+$st)) != 0) ? '<td colspan="' .(36 - ($val+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link 
						. '">&nbsp;</a></div></td>' : '') ;
			} else {
				$value .= '<td style="background-color:#9966FF;' . $attr . '">'
						. '<div ' . $upm_title .'><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((36 - (1+$st)) != 0) ? '<td colspan="' .(36 - (1+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link 
						. '">&nbsp;</a></div></td>' : '') ;			
			}
			
			$value .= '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
	
		} else if($end_year > $third_yr){
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td style="background-color:#9966FF;' . $attr . '" colspan="3" ' . $attr_two . '><div '
					. $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
		}
		
	} else if($start_year == $second_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $second_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;/a></div><</td>' : '');
					
			if($val != 0) {
				$value .= '<td style="background-color:#9966FF;' . $attr . '" colspan="' . $val . '">'
						. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link 
						. '">&nbsp;</a></div></td>' : '');
			} else {
				$value .= '<td style="background-color:#9966FF;' . $attr . '">'
						. '<div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link 
						. '">&nbsp;</a></div></td>' : '');
			}
			
			$value .= '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';		
		
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '');
					
			if($val != 0) {
				$value .= '<td style="background-color:#9966FF;' . $attr . '" colspan="' . $val . '">'
						. '<div ' . $upm_title .'><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link 
						. '">&nbsp;</a></div></td>' : '');
			} else {
				$value .= '<td style="background-color:#9966FF;' . $attr . '">'
						. '<div ' . $upm_title .'><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link 
						. '">&nbsp;</a></div></td>' : '');			
			}
			$value .= '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';

		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
					. '<td style="background-color:#9966FF;' . $attr . '" colspan="3"><div ' 
					. $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
		}
		
	} else if($start_year == $third_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;	
		if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>' : '');
				
			if($val != 0) {
				$value .= '<td style="background-color:#9966FF;' . $attr . '" colspan="' . $val . '">'
						. '<div ' . $upm_title .'><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link 
						. '">&nbsp;</a></div></td>' : '');
			} else {
				$value .= '<td style="background-color:#9966FF;' . $attr . '">'
						. '<div ' . $upm_title .'><a href="' . $upm_link . '">&nbsp;</a></div></td>'
						. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><div ' . $upm_title . '><a href="' . $upm_link 
						. '">&nbsp;</a></div></td>' : '');			
			}
			
			$value .= '<td colspan="3" ' . $attr_two . '><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';
		
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
				. '<td style="background-color:#9966FF;' . $attr . '" colspan="3" ' . $attr_two . '><div ' 
				. $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';			
		}
			
	} else if($start_year > $third_yr) {
	
		$value = '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td colspan="12"><div ' . $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>'
			. '<td style="background-color:#9966FF;' . $attr . '" colspan="3" ' . $attr_two . '><div ' 
			. $upm_title . '><a href="' . $upm_link . '">&nbsp;</a></div></td>';	
	}
	return $value;	
}

//get difference between two dates in months
function getColspan($start_dt, $end_dt) {
	
	$diff = round((strtotime($end_dt)-strtotime($start_dt))/2628000);
	return $diff;

}

//calculating the project completion chart in which the year ranges from the current year and next-to-next year
function getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, $bg_color, $start_date, $end_date){

	$attr_two = 'class="rightborder"';
	if(($start_date == '' || $start_date == NULL) && ($end_date == '' || $end_date == NULL)) {
	
		$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	

	} else if($start_date == '' || $start_date == NULL) {
	
		$st = $end_month-1;
		if($end_year < $current_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
						
		} else if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';width:2px;">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3" ' 
				. $attr_two . '>&nbsp;</td>';	
					
		} else if($end_year == $second_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>'
				. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';width:2px;">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
					
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				.(($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';width:2px;">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
				
		} else if($end_year > $third_yr){
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
			. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
		}
	} else if($end_date == '' || $end_date == NULL) {
	
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
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
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
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
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
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
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
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
			. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';
		}
			
	} else if($start_year > $third_yr) {
	
		$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
		. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';
	} 
	return $value;
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
		//echo "<br/>==>".
		$sql = "SELECT `id`, `event_type`, `event_description`, `event_link`, `result_link`, `start_date`, `start_date_type`, `end_date`, `end_date_type` "
				. " FROM `upm_history` WHERE `id` = '" . $row['id'] . "' AND (`superceded` < '" . date('Y-m-d',strtotime($time)) . "' AND `superceded` >= '" 
				. date('Y-m-d',strtotime($edited,strtotime($time))) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
		$res = mysql_query($sql);
		
		$upm[$i]['edited'] = array();
		while($arr = mysql_fetch_assoc($res)) {
			$upm[$i]['edited'] = array($arr['event_type'], $arr['event_description'], $arr['event_link'], $arr['result_link'], 
									$arr['start_date'], $arr['start_date_type'], $arr['end_date'], $arr['end_date_type'],);
		}
		$i++;
	}
	//echo "<pre>";print_r($upm);
	return $upm;
}

function getUnmatchedUpmChanges($record_arr, $time, $edited) {

	foreach($record_arr as $key => $value) {
	
		//echo "<br/>==>".
		$sql = "SELECT `id`, `event_type`, `event_description`, `event_link`, `result_link`, `start_date`, `start_date_type`, `end_date`, `end_date_type` "
				. " FROM `upm_history` WHERE `id` = '" . $value['id'] . "' AND (`superceded` < '" . date('Y-m-d',strtotime($time)) . "' AND `superceded` >= '" 
				. date('Y-m-d',strtotime($edited,strtotime($time))) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
		$res = mysql_query($sql);
		
		$record_arr[$key]['edited'] = array();
		while($row = mysql_fetch_assoc($res)) {
		
			$record_arr[$key]['edited']['id'] = $row['id'];
			$record_arr[$key]['edited']['event_description'] = $row['event_description'];
			$record_arr[$key]['edited']['event_link'] = $row['event_link'];
			$record_arr[$key]['edited']['event_type'] = $row['event_type'];
			$record_arr[$key]['edited']['start_date'] = $row['start_date'];
			$record_arr[$key]['edited']['start_date_type'] = $row['start_date_type'];
			$record_arr[$key]['edited']['end_date'] 	= $row['end_date'];
			$record_arr[$key]['edited']['end_date_type'] = $row['end_date_type'];
			
		}
	}
	
	return $record_arr;
}

//return NCT fields given an NCTID
function getNCT($nct_id,$larvol_id,$time,$edited)
{	
	$study = array('edited' => array(), 'new' => 'n');
	
	$fieldnames = array('nct_id', 'brief_title', 'enrollment', 'enrollment_type', 'acronym', 'start_date', 'overall_status',
	'condition', 'intervention_name', 'phase', 'lead_sponsor', 'collaborator');

	$studycatData=mysql_fetch_assoc(mysql_query("SELECT `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_cats_in_study` `dc` ON (`dc`.`id`=`dv`.`studycat`) WHERE `dv`.`field`='1' AND `dv`.`val_int`='".$nct_id."' AND `dc`.`larvol_id`='"
	.$larvol_id."'"));
	
	$res = mysql_query("SELECT DISTINCT `df`.`name` AS `fieldname`, `df`.`id` AS `fieldid`, `df`.`type` AS `fieldtype`, `dv`.`studycat`, dv.* FROM `data_values` `dv` LEFT JOIN `data_fields` `df` ON (`df`.`id`=`dv`.`field`) WHERE `df`.`name` IN ('" 
	. join("','",$fieldnames) . "') AND `studycat`='" . $studycatData['studycat'] 
	. "' AND (`dv`.`superceded`<'" . date('Y-m-d',strtotime($time)) . "' AND `dv`.`superceded`>= '" 
	. date('Y-m-d',strtotime($edited,strtotime($time))) . "')");

	while ($row = mysql_fetch_assoc($res)) { 
	
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
		if(isset($val) && $val != '')
			$study['edited']['NCT/'.$row['fieldname']] = 'Previous value: ' . $val;
		else 
			$study['edited']['NCT/'.$row['fieldname']] = 'No previous value';
		
	}
	
	$sql = "SELECT `clinical_study`.`larvol_id` FROM `clinical_study` WHERE `clinical_study`.`import_time` <= '" 
		. date('Y-m-d',strtotime($time)) . "' AND `clinical_study`.`larvol_id` = '" .  $larvol_id
		. "' AND `clinical_study`.`import_time` >= '" 
		. date('Y-m-d',strtotime($edited,strtotime($time))) . "' ";
		
	$result = mysql_query($sql);		

	if(mysql_num_rows($result) >= 1) {
		$study['new'] = 'y';
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

//get records for non associated upms
function getNonAssocUpmRecords($non_assoc_upm_params) {

	$where = '';$upms = array();
	foreach($non_assoc_upm_params as $key => $val)
		$where .= ' (PREG_RLIKE("' . $val . '",product)) OR ';

	//echo "<br/>".
	$sql = "SELECT `id`, `event_description`, `event_link`, `result_link`, `event_type`, `start_date`, `start_date_type`, `end_date`, `end_date_type`, "
	. "(SELECT `end_date` FROM `upm_history` WHERE `upm_history`.`id` = `upm`.`id` ORDER BY `added` ASC LIMIT 0,1) AS end_date_previous_value, "
	. "(SELECT `start_date` FROM `upm_history` WHERE`upm_history`.`id` = `upm`.`id` ORDER BY `added` ASC LIMIT 0,1) AS start_date_previous_value "
	. "FROM `upm` WHERE `corresponding_trial` IS NULL AND " . substr($where,0,-4);
	
	$res = mysql_query($sql);
	
	$i = 0;
	if(mysql_num_rows($res) > 0){
		while($row = mysql_fetch_assoc($res)) { 
		
			$upms[$i]['id'] = $row['id'];
			$upms[$i]['event_description'] = $row['event_description'];
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
?>
</body>
</html>