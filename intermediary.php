<?php 
//Below lines are lines should be kept on top of all like session start gives session error, sphinx also gives query related error
// - so incase of TZ we place in these pages itself
if(!isset($_REQUEST['DiseaseId']) && $_REQUEST['sourcepg'] != 'TZ')
{
	header('P3P: CP="CAO PSA OUR"');
	session_start();
	//connect to Sphinx
	if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
}
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('special_chars.php');
require_once('run_trial_tracker.php');
require('searchhandler.php');

if(isset($_POST['btnDownload'])) 
{	
	$resultIds = $_POST['resultIds'];
	$globalOptions = $_POST['globalOptions'];
	
	$globalOptions['dOption'] = $_POST['dOption'];
	
	switch($_POST['wFormat'])
	{
		case 'excel': 
			$fileType = 'excel';
			break;
		case 'pdf': 
			$fileType = 'pdf';
			break;
		case 'tsv': 
			$fileType = 'tsv';
			break;
	}
	
	$tt = new TrialTracker;

	$tt->generateTrialTracker($fileType, $resultIds, $globalOptions);
	exit;
}
if(!isset($_REQUEST['DiseaseId']) && $_REQUEST['sourcepg'] != 'TZ')
DisplayOTT();
function DisplayOTT()
{
	global $db;
	$maxEnrollLimit = 5000;

	/*******************************/
	if(isset($_REQUEST['p']) && !isset($_REQUEST['e1']))	$_REQUEST['e1']= $_REQUEST['p'];
	if(isset($_REQUEST['a']) && !isset($_REQUEST['e2']))	$_REQUEST['e2']= $_REQUEST['a'];
	/********************************/

	$tt = new TrialTracker;
	
	$globalOptions['type'] = 'activeTrials';
	$globalOptions['enroll'] = '0';
	$globalOptions['status'] = array();
	$globalOptions['itype'] = array();
	$globalOptions['region'] = array();
	$globalOptions['phase'] = array();
	
	if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
		$globalOptions['page'] = mysql_real_escape_string($_REQUEST['page']);
	else
		$globalOptions['page'] = 1;
	$globalOptions['onlyUpdates'] = "no";
	$globalOptions['LI'] = "0";
	$globalOptions['product'] = array();
	
	$globalOptions['startrange'] = "now";
	$globalOptions['endrange'] = "1 month";
	
	$globalOptions['pageLocation'] = "intermediary";
	if(isset($_REQUEST['DiseaseId']))
	{
		$globalOptions['DiseaseId'] = $_REQUEST['DiseaseId'];
		$globalOptions['pageLocation'] = "trialzilla_disease";
		$_REQUEST['e1'] = $globalOptions['DiseaseId'];
	}
	if(isset($_REQUEST['sourcepg']) && $_REQUEST['sourcepg'] == 'TZ')
	{
		$globalOptions['sourcepg'] = $_REQUEST['sourcepg'];
		$globalOptions['pageLocation'] = "trialzilla_ott";
	}
	
	if((isset($_REQUEST['sourcepg']) && $_REQUEST['sourcepg'] == 'TZ') || (isset($_REQUEST['DiseaseId'])))
	{
		if(!isset($_REQUEST['list']))	//set default view all trials in case of TZ related OTT.
		{
			$_REQUEST['list']=2;
		}
	}	
	//sphinx search option.
	$globalOptions['sphinxSearch'] = '';
	//$globalOptions['sphinx_s'] = '';
	
	$globalOptions['includeProductsWNoData'] = "off";
	$globalOptions['showTrialsSponsoredByProductOwner'] = "off";
	
	if(isset($_REQUEST['ipwnd']) && $_REQUEST['ipwnd'] == "on")
	{	
		$globalOptions['includeProductsWNoData'] = "on";
	}
	
	if(isset($_REQUEST['tspo']) && $_REQUEST['tspo'] == "on")
	{
		$globalOptions['showTrialsSponsoredByProductOwner'] = "on";
	}
	
	if(isset($_REQUEST['enroll']))
	{	
		$globalOptions['enroll'] = trim($_REQUEST['enroll']);
	}
	
	if(isset($_REQUEST['sr']))
	{	
		$globalOptions['startrange'] = $_REQUEST['sr'];
	}
	
	if(isset($_REQUEST['er']))
	{	
		$globalOptions['endrange'] = $_REQUEST['er'];
	}
	
	$globalOptions['resetLink'] = '';
	if(!(isset($_REQUEST['rflag'])))
	{
		$resetParams = array();
		parse_str($_SERVER['QUERY_STRING'], $resetParams);
		
		unset($resetParams['p']);
		unset($resetParams['a']);
		unset($resetParams['JSON_search']);
		
		unset($resetParams['e1']);
		unset($resetParams['e2']);
		
		if(!empty($resetParams))
		{
			foreach($resetParams as $rkey => $rvalue)
			{
				$globalOptions['resetLink'] .= ',' . $rkey . '=' . $rvalue;
				if(isset($globalOptions['DiseaseId']))
				$globalOptions['resetLink'] = ',DiseaseId='. $globalOptions['DiseaseId'] .',tab=DiseaseOTT';
			}
		}		
	}
	
	if(isset($_REQUEST['rlink']) && $_REQUEST['rlink'] != '')
	{
		$globalOptions['resetLink'] = $_REQUEST['rlink'];
	}
	
	$globalOptions['Highlight_Range'] = array('1 week', '2 weeks', '1 month', '1 quarter', '6 months', '1 year');
	
	//// Part added to switch start range and end range if they look reverse order
	if($globalOptions['startrange'] != '' && $globalOptions['endrange'] != '')
	{	
		global $now;
		/// Below part is keep to support old links with ago
		$globalOptions['startrange'] = str_replace('ago', '', $globalOptions['startrange']);
		$globalOptions['endrange'] = str_replace('ago', '', $globalOptions['endrange']);
		$st_limit = $globalOptions['startrange'];
		$st_limit = trim($st_limit);
		
		if(in_array($st_limit, $globalOptions['Highlight_Range']))
			$st_limit = '-' . (($st_limit == '1 quarter') ? '3 months' : $st_limit);
		$st_limit = date('Y-m-d', strtotime($st_limit, $now));
		
		$ed_limit = $globalOptions['endrange'];
		$ed_limit = trim($ed_limit);
		
		if(in_array($ed_limit, $globalOptions['Highlight_Range']))
			$ed_limit = '-' . (($ed_limit == '1 quarter') ? '3 months' : $ed_limit);
		$ed_limit = date('Y-m-d', strtotime($ed_limit, $now));
		
		if($st_limit < $ed_limit)	/// switch is start is less than end
		{
			$temp = $globalOptions['endrange'];
			$globalOptions['endrange'] = $globalOptions['startrange'];
			$globalOptions['startrange'] = $temp;
		}
	}
	
	switch($globalOptions['startrange'])
	{	
		case "now": $starttimerange = 0; break;
		case "1 week": $starttimerange = 1; break;
		case "2 weeks": $starttimerange = 2; break;
		case "1 month": $starttimerange = 3; break;
		case "1 quarter": $starttimerange = 4; break;
		case "6 months": $starttimerange = 5; break;
		case "1 year": $starttimerange = 6; break;
		default: $starttimerange = 0; break;
	}
	
	switch($globalOptions['endrange'])
	{
		case "now": $endtimerange = 0; break;
		case "1 week": $endtimerange = 1; break;
		case "2 weeks": $endtimerange = 2; break;
		case "1 month": $endtimerange = 3; break;
		case "1 quarter": $endtimerange = 4; break;
		case "6 months": $endtimerange = 5; break;
		case "1 year": $endtimerange = 6; break;
		default: $endtimerange = 3; break;
	}
	
	if(!$db->loggedIn()) 
	{
		$globalOptions['startrange'] = 'now';
	}
	
	$intermediaryCss = 'css/intermediary.css';
	$jueryUiCss 	= 'css/themes/cupertino/jquery-ui-1.8.17.custom.css';
	$dateInputCss 	= 'date/date_input.css';
	$jdPickerCss 	= 'scripts/date/jdpicker.css';
	$scrollBarCs	= 'css/jquery.mCustomScrollbar.css';
	
	$jqueryJs 		= 'scripts/jquery.js';
	$funcJs 		= 'scripts/func.js';
	$jqueryMinJs 	= 'scripts/jquery-1.7.1.min.js';
	$jqueryUiMinJs 	= 'scripts/jquery-ui-1.8.17.custom.min.js';
	$dateInputJs 	= 'date/jquery.date_input.js';
	$jdPickerJs 	= 'scripts/date/jquery.jdpicker.js';
	$initJs 		= 'date/init.js';
	$chromeJs 		= 'scripts/chrome.js';
	$hoverJs 		= 'scripts/jquery.hoverIntent.minified.js';
	$mouseWheelJs 	= 'scripts/jquery.mousewheel.min.js';
	$scrollBarJs 	= 'scripts/jquery.mCustomScrollbar.js';

if(!isset($globalOptions['DiseaseId']) && $globalOptions['sourcepg'] != 'TZ')
print	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta http-equiv="X-UA-Compatible" content="IE=edge" />
			<title>Online Trial Tracker</title>';
			
print	'	<link href="'. $intermediaryCss . '?t=' . filectime($intermediaryCss) .'" rel="stylesheet" type="text/css" media="all" />
			<link href="'. $jueryUiCss . '?t=' . filectime($jueryUiCss) .'" rel="stylesheet" type="text/css" media="all" />
			<link href="'. $dateInputCss . '?t=' . filectime($dateInputCss) .'" rel="stylesheet" type="text/css" media="all" />
			<link href="'. $jdPickerCss . '?t=' . filectime($jdPickerCss) .'" rel="stylesheet" type="text/css" media="screen" />
			<link href="'. $scrollBarCs . '?t=' . filectime($scrollBarCs) .'" rel="stylesheet" type="text/css" media="screen" />
			
			<script type="text/javascript" src="'. $jqueryJs . '?t=' . filectime($jqueryJs) .'" ></script>
			<script type="text/javascript" src="'. $funcJs . '?t=' . filectime($funcJs) .'"></script>	
			<script type="text/javascript" src="'. $jqueryMinJs . '?t=' . filectime($jqueryMinJs) .'"></script>
			<script type="text/javascript" src="'. $jqueryUiMinJs . '?t=' . filectime($jqueryUiMinJs) .'"></script>
			<script type="text/javascript" src="'. $dateInputJs . '?t=' . filectime($dateInputJs) .'"></script>
			<script type="text/javascript" src="'. $jdPickerJs . '?t=' . filectime($jdPickerJs) .'"></script>
			<script type="text/javascript" src="'. $initJs . '?t=' . filectime($initJs) .'"></script>
			<script type="text/javascript" src="'. $chromeJs . '?t=' . filectime($chromeJs) .'"></script>
			<script type="text/javascript" src="'. $hoverJs . '?t=' . filectime($hoverJs) .'"></script>
			
			<script type="text/javascript" src="'. $mouseWheelJs . '?t=' . filectime($mouseWheelJs) .'"></script>
			<script type="text/javascript" src="'. $scrollBarJs . '?t=' . filectime($scrollBarJs) .'"></script>
			
			<script type="text/javascript">
				var _gaq = _gaq || [];
				_gaq.push(["_setAccount", "UA-18240582-3"]);
				_gaq.push(["_trackPageview"]);
				
				(function() {
					var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true;
					ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";
					var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);
				})();
			</script>
			<script type="text/javascript"> 
			//<![CDATA[
			function showValues(value) 
			{	
				  if(value == "inactive") 
				  {	
					document.getElementById("statuscontainer").innerHTML = 
						 \'<input type="checkbox" class="status" value="0" />Withheld<br/>\'+
						 \'<input type="checkbox" class="status" value="1" />Approved for marketing<br/>\' +
						 \'<input type="checkbox" class="status" value="2" />Temporarily not available<br/>\' + 
						 \'<input type="checkbox" class="status" value="3" />No Longer Available<br/>\' + 
						 \'<input type="checkbox" class="status" value="4" />Withdrawn<br/>\' + 
						 \'<input type="checkbox" class="status" value="5" />Terminated<br/>\' +
						 \'<input type="checkbox" class="status" value="6" />Suspended<br/>\' +
						 \'<input type="checkbox" class="status" value="7" />Completed<br/>\';
				  } 
				  else if(value == "active") 
				  {	
					document.getElementById("statuscontainer").innerHTML = 
						\'<input type="checkbox" class="status" value="0" />Not yet recruiting<br/>\' +
						\'<input type="checkbox" class="status" value="1" />Recruiting<br/>\' + 
						\'<input type="checkbox" class="status" value="2" />Enrolling by invitation<br/>\' + 
						\'<input type="checkbox" class="status" value="3" />Active, not recruiting<br/>\' + 
						\'<input type="checkbox" class="status" value="4" />Available<br/>\' +
						\'<input type="checkbox" class="status" value="5" />No longer recruiting<br/>\';
				  } 
				  else 
				  { 
					document.getElementById("statuscontainer").innerHTML = 
						\'<input type="checkbox" class="status" value="0" />Not yet recruiting<br/>\' +
						\'<input type="checkbox" class="status" value="1" />Recruiting<br/>\' + 
						\'<input type="checkbox" class="status" value="2" />Enrolling by invitation<br/>\' + 
						\'<input type="checkbox" class="status" value="3" />Active, not recruiting<br/>\' + 
						\'<input type="checkbox" class="status" value="4" />Available<br/>\' +
						\'<input type="checkbox" class="status" value="5" />No longer recruiting<br/>\' +
						\'<input type="checkbox" class="status" value="6" />Withheld<br/>\'+
						\'<input type="checkbox" class="status" value="7" />Approved for marketing<br/>\' +
						\'<input type="checkbox" class="status" value="8" />Temporarily not available<br/>\' + 
						\'<input type="checkbox" class="status" value="9" />No Longer Available<br/>\' + 
						\'<input type="checkbox" class="status" value="10" />Withdrawn<br/>\' + 
						\'<input type="checkbox" class="status" value="11" />Terminated<br/>\' +
						\'<input type="checkbox" class="status" value="12" />Suspended<br/>\' +
						\'<input type="checkbox" class="status" value="13" />Completed<br/>\';
				  }
			  }
			//]]>
			
			$(function() 
			{
				$("#frmOtt").submit(function() 
				{	
					//set phase filters
					var phase = new Array();
					$("input.phase:checked").each(function(index) 
					{	
						phase.push($(this).val());
					});
					$("#phase").val(phase);
					
					//set region filters
					var region = new Array();
					$("input.region:checked").each(function(index) 
					{	
						region.push($(this).val());
					});
					$("#region").val(region);
					
					//set institution type filters
					var institution = new Array();
					$("input.institution:checked").each(function(index) 
					{	
						institution.push($(this).val());
					});
					$("#itype").val(institution);
					
					//set status filters
					var status = new Array();
					$("input.status:checked").each(function(index) 
					{	
						status.push($(this).val());
					});
					$("#status").val(status);
					
					//set product filters
					var product = new Array();
					$("input.product:checked").each(function(index) 
					{	
						product.push($(this).val());
					});
					$("#product").val(product);
					
					
					//$("#change").val($("#amount3").val());
					
				});
				
				//reset functionality
				$("#reset").click(function() 
				{	
					$("#status").val("");
					$("input.status").each(function(index) 
					{	
						 $(this).attr("checked", false);
					});
					
					$("#itype").val("");
					$("input.institution").each(function(index) 
					{	
						 $(this).attr("checked", false);
					});
					
					$("#region").val("");
					$("input.region").each(function(index) 
					{	
						 $(this).attr("checked", false);
					});
					
					$("#phase").val("");
					$("input.phase").each(function(index) 
					{	
						 $(this).attr("checked", false);
					});
		
					$("#showonlyupdated").attr("checked", false);
					
					$("#amount").val("0 - '. $globalOptions['maxEnroll'] .'");
					$( "#slider-range").slider( "option", "value", parseInt('. $globalOptions['maxEnroll'] .'));
					
					return true;
				});
				
				var config = {    
					 over: makeTall, // function = onMouseOver callback (REQUIRED)    
					 timeout: 500, // number = milliseconds delay before onMouseOut    
					 out: makeShort// function = onMouseOut callback (REQUIRED)   
				};
				
				//If javascript works override stylesheet  - as hover css and JS tries to execute same time at first instance
				$(".rowcollapse").css("height" , "16px");
				
				function makeTall()
				{  
					//JQuery animate function does not work correctly for 100% height, so first retrieve actual height by setting it to 100% then, give actual height to animate function
					var fullHeight = $(this).css("height" , "100%").height();	//get actual height
					$(this).css("height" , "16px"); //reset back height
					$(this).animate({height:fullHeight}, 500);	//give actual height
				}
		
				function makeShort(){ $(this).animate({"height":"16px"}, 500);}
		
				$(".rowcollapse").hoverIntent(config);
			});
			
			</script>
			
			<script type="text/javascript">
			//Count the Number of View of Records
			function INC_ViewCount(larvol_id)
			{
				 $.ajax({
							type: "GET",
							url:  "viewcount.php",
							data: "op=Inc_ViewCount&larvol_id="+larvol_id,
							success: function (html) {
								$("#ViewCount_"+larvol_id).html(html);
						   }
				});
				return;
				
			}
			</script>';

if(!isset($globalOptions['DiseaseId']) && $globalOptions['sourcepg'] != 'TZ')			
print	'
<style type="text/css">
html,body {
	height: 100%;
	margin:0px;
	padding: 0px;
}

body {
	font-family:Arial, Helvetica, sans-serif;
	font-size:13px;
	background-color:#ffffff;
	width: 100%;
}
</style>
</head><body>';

if(isset($_REQUEST['region']) && $_REQUEST['region'] != '')
{
	$globalOptions['region'] = explode(',', $_REQUEST['region']);
	$globalOptions['region'] = array_filter($globalOptions['region'], 'iszero');
}

if(isset($_REQUEST['phase']) && $_REQUEST['phase'] != '')
{
	$globalOptions['phase'] = explode(',', $_REQUEST['phase']);
	$globalOptions['phase'] = array_filter($globalOptions['phase'], 'iszero');
}

if(isset($_REQUEST['itype']) && $_REQUEST['itype'] != '')
{
	$globalOptions['itype'] = explode(',', $_REQUEST['itype']);
	$globalOptions['itype'] = array_filter($globalOptions['itype'], 'iszero');
}

if(isset($_REQUEST['status']) && $_REQUEST['status'] != '')
{
	$globalOptions['status'] = explode(',', $_REQUEST['status']);
	$globalOptions['status'] = array_filter($globalOptions['status'], 'iszero');
}

if(isset($_REQUEST['list']))
{
	if($_REQUEST['list'] == 0)
	{
		$globalOptions['type'] = 'inactiveTrials';
	}
	elseif($_REQUEST['list'] == 2)
	{
		$globalOptions['type'] = 'allTrials';
	}
}

if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
{
	$globalOptions['page'] = mysql_real_escape_string($_REQUEST['page']);
}

if(isset($_REQUEST['osu']) && $_REQUEST['osu'] == 'on')
{
	$globalOptions['onlyUpdates'] = "yes";
}

if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') !== FALSE)
|| (isset($_REQUEST['LI']) && $_REQUEST['LI'] == 1))
{
	$globalOptions['LI'] = "1";
}

if(isset($_REQUEST['pr']) && $_REQUEST['pr'] != '')
{	
	$globalOptions['product'] =  explode(',', $_REQUEST['pr']);
	$globalOptions['product'] = array_filter($globalOptions['product'], 'iszero');
}

if(isset($_REQUEST['e1']) || isset($_REQUEST['e2'])|| isset($_REQUEST['hm']) || isset($globalOptions['DiseaseId']))
{
	$globalOptions['url'] = 'e1=' . $_REQUEST['e1'] . '&e2=' . $_REQUEST['e2'];	
	if(isset($_REQUEST['JSON_search']))
	{
		$globalOptions['url'] .= '&JSON_search=' . $_REQUEST['JSON_search'];
		$globalOptions['JSON_search'] = $_REQUEST['JSON_search'];
	}
	if(isset($_REQUEST['hm']) && trim($_REQUEST['hm']) != '' && $_REQUEST['hm'] != NULL)
	{
		$globalOptions['hm'] = $_REQUEST['hm'];
	
	}
	if(isset($_REQUEST['sphinx_s']))
	{
		$globalOptions['sphinx_s'] = $_REQUEST['sphinx_s'];
	}
	
	if(isset($_REQUEST['ss']) && $_REQUEST['ss'] != '')
	{
		$globalOptions['sphinxSearch'] = $_REQUEST['ss'];
	}
	$tt->generateTrialTracker('entities', array('e1' => $_REQUEST['e1'], 'e2' => $_REQUEST['e2']), $globalOptions);
}
else if(isset($_REQUEST['p']) || isset($_REQUEST['a']) || isset($_REQUEST['hm']))
{
	$globalOptions['url'] = 'p=' . $_REQUEST['p'] . '&a=' . $_REQUEST['a'];	
	
	if(isset($_REQUEST['JSON_search']))
	{
		$globalOptions['url'] = 'p=' . $_REQUEST['p'] . '&a=' . $_REQUEST['a'] . '&JSON_search=' . $_REQUEST['JSON_search'];
		$globalOptions['JSON_search'] = $_REQUEST['JSON_search'];
	}
	
	//pr($globalOptions['itype']);
	if(isset($_REQUEST['hm']) && trim($_REQUEST['hm']) != '' && $_REQUEST['hm'] != NULL)
	{
		$globalOptions['hm'] = $_REQUEST['hm'];
		
		if(!isset($_REQUEST['itype']))
		{
			$globalOptions['itype'][0] = 0;
		}
		
	}
	
	if(isset($_REQUEST['sphinx_s']))
	{
		$globalOptions['sphinx_s'] = $_REQUEST['sphinx_s'];
	}
	
	if(isset($_REQUEST['ss']) && $_REQUEST['ss'] != '')
	{
		$globalOptions['sphinxSearch'] = $_REQUEST['ss'];
	}
	
	
	$tt->generateTrialTracker('indexed', array('product' => $_REQUEST['p'], 'area' => $_REQUEST['a']), $globalOptions);
}
else
{
	die('cell not set');
}

global $db;

print      '<div id="slideout" '.((isset($globalOptions['DiseaseId']) || $globalOptions['sourcepg'] == 'TZ') ? 'style="top:200px;"':'').'>
            <img src="images/help.png" alt="Help" />
            <div class="slideout_inner">
                <table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" class="table-slide">
                <tr><td width="15%"><img src="images/black-diamond.png" alt="Data release" /></td><td>Click for data release</td></tr>
                <tr><td><img src="images/red-diamond.png" alt="Data release (new)" /></td><td>Click for data release (new)</td></tr>
                <tr><td><img src="images/hourglass.png" alt="Results pending" /></td><td>Results pending</td></tr>
                <tr><td><img src="images/black-checkmark.png" alt="Milestone result" /></td><td>Click for milestone result</td></tr>
                <tr><td><img src="images/red-checkmark.png" alt="Milestone result (new)" /></td><td>Click for milestone result (new)</td></tr>
                <tr><td><img src="images/purple-bar.png" alt="Milestone details" /></td><td>Click for milestone details</td></tr>
                <tr><td><img src="images/down.png" alt="Milestones" /></td><td>Display milestones</td></tr>
                <tr><td colspan="2" style="padding-right: 1px;">
                 <div style="float:left;padding-top:3px;">Phase&nbsp;</div>
                 <div class="gray">N/A</div>
                 <div class="blue">0</div>
                 <div class="green">1</div>
                 <div class="yellow">2</div>
                 <div class="orange">3</div>
                 <div class="red">4</div>
                 </td></tr>
                </table>
            </div>
        </div>
        <script type="text/javascript">
            $(function() 
            {
                var maxE = '. $maxEnrollLimit .';
                
                var minv = 0;
                var maxv = maxE;
                
                var enroll = \''. $globalOptions['enroll'] .'\';
                if(enroll != "0")
                {	
                    var e = enroll.split("-");
                    minv = e[0];
                    maxv = e[1];
                }
                
                $("#slider-range").slider({
                    range: true,
                    min: 0,
                    max: maxE,
                    values: [ minv, maxv ],
                    slide: function( event, ui ) {
                        var ev;
                        if(ui.values[ 1 ] == maxE)
                        {
                            $("#amount").val(ui.values[ 0 ] + " - " + maxE + "+" );
                            ev = ui.values[ 0 ] + "-" + maxE;
                        }
                        else
                        {
                            $("#amount").val(ui.values[ 0 ] + " - " + ui.values[ 1 ] );
                            ev = ui.values[ 0 ] + "-" + ui.values[ 1 ];
                        }
                        $("input[name=enroll]").val(ev);
                    }
                });
                
                if($("#slider-range").slider("values", 1) == maxE)
                {
                    $("#amount").val( $("#slider-range").slider("values", 0 ) +
                        " - " + maxE + "+" );
                }
                else
                {
                    $("#amount").val( $("#slider-range").slider("values", 0 ) +
                        " - " + $("#slider-range").slider("values", 1 ));
                }';
                
               if($db->loggedIn()) 
			   { 
      	          //highlight changes slider
	       			print '$("#slider-range-min").slider({
	                	    range: false,
	              	      min: 0,
	              	      max: 6,
	              	      step: 1,
	              	      values: [ '. $starttimerange .', '. $endtimerange .' ],
	              	      slide: function(event, ui) {
	              	          if(ui.values[0] > ui.values[1])	/// Switch highlight range when sliders cross each other
	              	          {
	              	              $("#startrange").val(timeEnum(ui.values[1]));
	              	              $("#endrange").val(timeEnum(ui.values[0]));
	              	          }
	              	          else
	              	          {
	              	              $("#startrange").val(timeEnum(ui.values[0]));
	              	              $("#endrange").val(timeEnum(ui.values[1]));
	              	          }
	              	      }
	              	  });';
               } 
			   else
			   { 
                	print '$("#slider-range-min").slider({
               			     range: "min",
               			     value: '. $endtimerange .',
              			     min: 0,
              			     max: 6,
              			     step:1,
             			      slide: function( event, ui ) {
             		          $("#endrange").val(timeEnumforGuests(ui.value));
            			        }
           				     });
            			    $timerange = "'. $globalOptions['endrange'] .'";
            			    $("#endrange").val($timerange);';
                } 
                
print           '$("ul #productbox li").click(function () {
                    var $checkbox = $(this).find(":checkbox");
                    $checkbox.prop("checked", !$checkbox.attr("checked"));
        
                });
                
                $("#productbox li input, #productbox li label").click(function(event){
                    event.stopPropagation();
                });
                
                $("body").keydown(function(e)
                {	
                    if (e.keyCode == 13) 
                    {
                      $("#frmOtt").submit();
                    } 
                });
                
                $(window).load(function(){
                    $("#outercontainer").mCustomScrollbar({
                        horizontalScroll:true,
                        scrollButtons:{
                            enable:false,
                            scrollType:"pixels",
                            scrollAmount:116
                        }
                    });
                });
                
                $("#togglefilters").toggle( function() {
                   $(".controls").css({ "display" : ""});
                }, function () {
                   $(".controls").css({ "display" : "none"});
                });
                
                divresize();
            });
        
            function timeEnum($timerange)
            {
                switch($timerange)
                {
                    case 0: $timerange = "now"; break;
                    case 1: $timerange = "1 week"; break;
                    case 2: $timerange = "2 weeks"; break;
                    case 3: $timerange = "1 month"; break;
                    case 4: $timerange = "1 quarter"; break;
                    case 5: $timerange = "6 months"; break;
                    case 6: $timerange = "1 year"; break;
                }
                return $timerange;
            }
            
            function timeEnumforGuests($timerange)
            {
                switch($timerange)
                {
                    case 0: $timerange = "now"; break;
                    case 1: $timerange = "1 week"; break;
                    case 2: $timerange = "2 weeks"; break;
                    case 3: $timerange = "1 month"; break;
                    case 4: $timerange = "1 quarter"; break;
                    case 5: $timerange = "6 months"; break;
                    case 6: $timerange = "1 year"; break;
                }
                return $timerange;
            }
            
            function divresize() 
            {  
                var windowidth = $(".manage").width();
                $("#parent").width(windowidth);
                
                var filterwidth = $("#togglefilters").width();
                var recordswidth = $(".records").width();
                var searchboxwidth = $("#fulltextsearchbox").width();
                var paginationwidth = $(".pagination").width();
                var buttonswidth = $("#buttons").width();
                var milestoneswidth = $(".milestones").width();
                var exportwidth = $(".export").width();
                
                var ocontrolswidth = (filterwidth+recordswidth+searchboxwidth+paginationwidth+buttonswidth+milestoneswidth+exportwidth+110);
                $("#outercontainer").width(windowidth - ocontrolswidth);
            } 
            
            $(window).resize(function() {
                divresize();
            }); 
        </script>';

	if($db->loggedIn() && (strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') == FALSE) && !isset($globalOptions['DiseaseId']) && $globalOptions['sourcepg'] != 'TZ')
	{
		$cpageURL = 'http://';
		$cpageURL .= $_SERVER["SERVER_NAME"].urldecode($_SERVER["REQUEST_URI"]);
		echo '<a href="li/larvolinsight.php?url='. $cpageURL .'"><span style="color:red;font-weight:bold;margin-left:10px;">LI view</span></a><br>';
	}
	
if(!isset($globalOptions['DiseaseId']) && $globalOptions['sourcepg'] != 'TZ')		
	print ' </body>
			</html>';
}
?>
