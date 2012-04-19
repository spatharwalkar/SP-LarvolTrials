<?php 
header('P3P: CP="CAO PSA OUR"');
session_start();
error_reporting(E_ALL ^ E_NOTICE);
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('special_chars.php');
require_once('run_trial_tracker.php');
require('searchhandler.php');


/********* If Report generation time is less than 1 Jan 2012, time machine is disabled **********/
if($_GET['time'] != NULL && $_GET['time'] != '')
{
if((date('Y-m-d H:i:s', $_GET['time'])) < date('Y-m-d H:i:s',strtotime('2012-01-01 00:00:00')))
$_GET['time'] = NULL;
}
else
{
$_GET['time'] = NULL;
}

$tt = new TrialTracker;
if(isset($_POST['btnDownload'])) 
{	
	$shownCnt = mysql_real_escape_string($_POST['shownCnt']);
	$ottType = $_POST['ottType'];
	$resultIds = $_POST['resultIds'];
	$timeMachine = (isset($_POST['timeMachine']) && $_POST['timeMachine'] != '') ? mysql_real_escape_string($_POST['timeMachine']) : NULL;
	$globalOptions = $_POST['globalOptions'];
	
	if($_POST['dOption'] == 'all')
	{
		$globalOptions['download'] = 'allTrialsforDownload';
	}
	else
	{
		$globalOptions['download'] = $globalOptions['type'];
	}
	
	switch($_POST['wFormat'])
	{
		case 'xml': 
			$fileType = 'xml';
			break;
		case 'excel': 
			$fileType = 'excel';
			break;
		case 'pdf': 
			$fileType = 'pdf';
			break;
	}
	
	if(($shownCnt != 0 && is_numeric($shownCnt)) || $_POST['dOption'] == 'all')
	{
		$tt->generateTrialTracker($fileType, $resultIds, $timeMachine, $ottType, $globalOptions);
		exit;
	}
}
$sortFields = array('phase' => 'pD', 'inactive_date' => 'iA', 'start_date' => 'sA', 'overall_status' => 'oA', 'enrollment' => 'eA');

$globalOptions['sortOrder'] = $sortFields;

$globalOptions['type'] = 'activeTrials';
$globalOptions['enroll'] = '0';
$globalOptions['status'] = array();
$globalOptions['itype'] = array();
$globalOptions['region'] = array();
$globalOptions['phase'] = array();

$globalOptions['version'] = rawurlencode(base64_encode('0'));

$globalOptions['page'] = 1;
$globalOptions['onlyUpdates'] = "no";
$globalOptions['encodeFormat'] = "old";
$globalOptions['LI'] = "0";
$globalOptions['minEnroll'] = "0";
$globalOptions['maxEnroll'] = "0";
$globalOptions['product'] = "";

$globalOptions['startrange'] = "now";
$globalOptions['endrange'] = "1 month ago";

if(isset($_GET['change']))
{
	$globalOptions['change'] = $_GET['change'];
}

if(isset($_GET['minenroll']) && isset($_GET['maxenroll']))
{
	$globalOptions['minEnroll'] = $_GET['minenroll'];
	$globalOptions['maxEnroll'] = $_GET['maxenroll'];
}

if(isset($_GET['enroll']) && $_GET['enroll'] != '0')
{	
	$globalOptions['enroll'] = $_GET['enroll'];
}

if(isset($_GET['sr']))
{	
	$globalOptions['startrange'] = $_GET['sr'];
}

if(isset($_GET['er']))
{	
	$globalOptions['endrange'] = $_GET['er'];
}

switch($globalOptions['startrange'])
{
	case "now": $starttimerange = 0; break;
	case "1 week ago": $starttimerange = 1; break;
	case "2 weeks ago": $starttimerange = 2; break;
	case "1 month ago": $starttimerange = 3; break;
	case "1 quarter ago": $starttimerange = 4; break;
	case "1 year ago": $starttimerange = 5; break;
	default: $starttimerange = 0; break;
}

switch($globalOptions['endrange'])
{
	case "now": $endtimerange = 0; break;
	case "1 week ago": $endtimerange = 1; break;
	case "2 weeks ago": $endtimerange = 2; break;
	case "1 month ago": $endtimerange = 3; break;
	case "1 quarter ago": $endtimerange = 4; break;
	case "1 year ago": $endtimerange = 5; break;
	default: $endtimerange = 3; break;
}
	
$lastChangedTime = filectime("css/intermediary.css");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Online Trial Tracker</title>
    <link href="css/intermediary.css?t=<?php echo $lastChangedTime;?>" rel="stylesheet" type="text/css" media="all" />
    <link href="css/themes/cupertino/jquery-ui-1.8.17.custom.css" rel="stylesheet" type="text/css" media="all" />
    <link href="date/date_input.css" rel="stylesheet" type="text/css" media="all" />
    <link href="scripts/date/jdpicker.css" rel="stylesheet" type="text/css" media="screen" />
    <script src="scripts/jquery.js" type="text/javascript"></script>
    <script src="scripts/func.js" type="text/javascript"></script>	
    <script src="scripts/jquery-1.7.1.min.js"></script>
	<script src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
	<!--<script type="text/javascript" src="date/jquery.js"></script>-->
    <script type="text/javascript" src="date/jquery.date_input.js"></script>
    <script type="text/javascript" src="scripts/date/jquery.jdpicker.js"></script>
    <script type="text/javascript" src="date/init.js"></script>
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
    <script type="text/javascript"> 
    //<![CDATA[
    function showValues(value) 
	{	
          if(value == 'inactive') 
		  {	
          	document.getElementById('statuscontainer').innerHTML = 
				 "<input type='checkbox' class='status' value='6' />Withheld<br/>"+
				 "<input type='checkbox' class='status' value='7' />Approved for marketing<br/>" +
				 "<input type='checkbox' class='status' value='8' />Temporarily not available<br/>" + 
				 "<input type='checkbox' class='status' value='9' />No Longer Available<br/>" + 
				 "<input type='checkbox' class='status' value='10' />Withdrawn<br/>" + 
				 "<input type='checkbox' class='status' value='11' />Terminated<br/>" +
				 "<input type='checkbox' class='status' value='12' />Suspended<br/>" +
				 "<input type='checkbox' class='status' value='13' />Completed<br/>";
          } 
		  else if(value == 'active') 
		  {	
			document.getElementById('statuscontainer').innerHTML = 
				'<input type="checkbox" class="status" value="0" />Not yet recruiting<br/>' +
				'<input type="checkbox" class="status" value="1" />Recruiting<br/>' + 
				'<input type="checkbox" class="status" value="2" />Enrolling by invitation<br/>' + 
				'<input type="checkbox" class="status" value="3" />Active, not recruiting<br/>' + 
				'<input type="checkbox" class="status" value="4" />Available<br/>' +
				'<input type="checkbox" class="status" value="5" />No longer recruiting<br/>';
          } 
		  else 
		  { 
			document.getElementById('statuscontainer').innerHTML = 
				'<input type="checkbox" class="status" value="0" />Not yet recruiting<br/>' +
				'<input type="checkbox" class="status" value="1" />Recruiting<br/>' + 
				'<input type="checkbox" class="status" value="2" />Enrolling by invitation<br/>' + 
				'<input type="checkbox" class="status" value="3" />Active, not recruiting<br/>' + 
				'<input type="checkbox" class="status" value="4" />Available<br/>' +
				'<input type="checkbox" class="status" value="5" />No longer recruiting<br/>' +
				'<input type="checkbox" class="status" value="6" />Withheld<br/>'+
				'<input type="checkbox" class="status" value="7" />Approved for marketing<br/>' +
				'<input type="checkbox" class="status" value="8" />Temporarily not available<br/>' + 
				'<input type="checkbox" class="status" value="9" />No Longer Available<br/>' + 
				'<input type="checkbox" class="status" value="10" />Withdrawn<br/>' + 
				'<input type="checkbox" class="status" value="11" />Terminated<br/>' +
				'<input type="checkbox" class="status" value="12" />Suspended<br/>' +
				'<input type="checkbox" class="status" value="13" />Completed<br/>';
          }
      }
    //]]>
	
	$(function() 
	{
		$("#frmOtt").submit(function() 
		{	
			//set phase filters
			var phase = new Array();
			$('input.phase:checked').each(function(index) 
			{	
				phase.push($(this).val());
			});
			$("#phase").val(phase);
			
			//set region filters
			var region = new Array();
			$('input.region:checked').each(function(index) 
			{	
				region.push($(this).val());
			});
			$("#region").val(region);
			
			//set institution type filters
			var institution = new Array();
			$('input.institution:checked').each(function(index) 
			{	
				institution.push($(this).val());
			});
			$("#itype").val(institution);
			
			//set status filters
			var status = new Array();
			$('input.status:checked').each(function(index) 
			{	
				status.push($(this).val());
			});
			$("#status").val(status);
			
			$("#change").val($("#amount3").val());
			
		});
		
		//reset functionality
		$("#reset").click(function() 
		{	
			$("#status").val("");
			$('input.status').each(function(index) 
			{	
				 $(this).attr("checked", false);
			});
			
			$("#itype").val("");
			$('input.institution').each(function(index) 
			{	
				 $(this).attr("checked", false);
			});
			
			$("#region").val("");
			$('input.region').each(function(index) 
			{	
				 $(this).attr("checked", false);
			});
			
			$("#phase").val("");
			$('input.phase').each(function(index) 
			{	
				 $(this).attr("checked", false);
			});

			$('#showonlyupdated').attr("checked", false);
			
			$("#amount").val("0 - <?php echo $globalOptions['maxEnroll'];?>");
			$( "#slider-range").slider( "option", "value", parseInt(<?php echo $globalOptions['maxEnroll'];?>));
			
			$("#amount3").val("1 month");
			$( "#slider-range-min").slider({ step: 2});
			
			//$("#frmOtt").submit();
			return true;
		});
	});
	
    </script>
    
    <script type="text/javascript">
	//Count the Number of View of Records
	function INC_ViewCount(larvol_id)
	{
		 $.ajax({
						type: 'GET',
						url:  'viewcount.php' + '?op=Inc_ViewCount&larvol_id=' + larvol_id,
						success: function (data) {
	        					//alert(data);
	        					$("#ViewCount_"+larvol_id).html(data);
	        		   }
				});
	        return;
	}
	</script>
    </head>
<body>
<div id="loading">
  <img id="loading-image" src="images/loading.gif" alt="Loading..." />
</div>
<?php
if(isset($_GET['region']) && $_GET['region'] != '')
{
	$globalOptions['region'] = explode(',', $_GET['region']);
}

if(isset($_GET['phase']) && $_GET['phase'] != '')
{
	$globalOptions['phase'] = explode(',', $_GET['phase']);
}

if(isset($_GET['itype']) && $_GET['itype'] != '')
{
	$globalOptions['itype'] = explode(',', $_GET['itype']);
}

if(isset($_GET['status']) && $_GET['status'] != '')
{
	$globalOptions['status'] = explode(',', $_GET['status']);
}

if(isset($_GET['list']))
{
	if($_GET['list'] == 0)
	{
		$globalOptions['type'] = 'inactiveTrials';
	}
	elseif($_GET['list'] == 2)
	{
		$globalOptions['type'] = 'allTrials';
	}
}

if(isset($_GET['page']) && is_numeric($_GET['page']))
{
	$globalOptions['page'] = mysql_real_escape_string($_GET['page']);
}

if(isset($_GET['v']))
{
	$globalOptions['version'] = mysql_real_escape_string($_GET['v']);
}

if(isset($_GET['osu']) && $_GET['osu'] == 'on')
{
	$globalOptions['onlyUpdates'] = "yes";
}

if(isset($_GET['cd']))
{
	$globalOptions['countDetails'] = unserialize(gzinflate(base64_decode(rawurldecode($_GET['cd']))));
}


if(isset($_GET['format']) && $_GET['format'] == "new")
{
	$globalOptions['encodeFormat'] = "new";
}

if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') !== FALSE)
|| (isset($_GET['LI']) && $_GET['LI'] == 1))
{
	$globalOptions['LI'] = "1";
}

if(isset($_GET['pr']))
{	
	$globalOptions['product'] = $_GET['pr'];
}

if(isset($_GET['id']))
{	
	$globalOptions['url'] = $_GET['id'];	
	$tt->generateTrialTracker('webpage', $_GET['id'], NULL, 'standalone', $globalOptions);
}
else if(isset($_GET['results']) && isset($_GET['type']))
{
	$tt_type = $_GET['type'] . 'stacked';
	$globalOptions['url'] = rawurlencode($_GET['results']).'&amp;type='.$_GET['type'];	
	$tt->generateTrialTracker('webpage', $_GET['results'], $_GET['time'], $tt_type, $globalOptions);
}
else if(isset($_GET['results']))
{	
	$globalOptions['url'] = $_GET['results'];	
	$tt->generateTrialTracker('webpage', $_GET['results'], $_GET['time'], 'unstacked', $globalOptions);
}
else if(isset($_GET['p']) && isset($_GET['a']))
{
	if(count($_GET['p']) > 1 && count($_GET['a']) > 1)
	{
		$tt_type = 'totalindexed';
	}
	else if(count($_GET['p']) > 1 || count($_GET['a']) > 1)
	{
		$tt_type = 'stackedindexed';
	}
	else 
	{
		$tt_type = 'singleindexed';
	}
	$globalOptions['url'] = 'p=' . $_GET['p'] . '&a=' . $_GET['a'];	
	
	if(isset($_GET['JSON_search']))
	{
		$globalOptions['url'] = 'p=' . $_GET['p'] . '&a=' . $_GET['a'] . '&JSON_search=' . $_GET['JSON_search'];
		$globalOptions['JSON_search'] = $_GET['JSON_search'];
	}
	
	$tt->generateTrialTracker('indexed', array('product' => $_GET['p'], 'area' => $_GET['a']), $_GET['time'], 'indexed', $globalOptions);
}
else if(isset($_GET['cparams']) || (isset($_GET['leading']) && isset($_GET['params'])))
{
	if(isset($_GET['cparams']))
	{
		$tt->generateTrialTracker('stackedoldlink', array('cparams' => $_GET['cparams'], 'leading' => $_GET['leading'], 'params' => $_GET['params']), 
		NULL, 'stackedoldlink', $globalOptions);
	}
	else
	{
		$tt->generateTrialTracker('unstackedoldlink', array('leading' => $_GET['leading'], 'params' => $_GET['params']), NULL, 'unstackedoldlink',
		 $globalOptions);
	}
}
else
{
	die('cell not set');
}
?>
<div id="slideout">
    <img src="images/help.png" alt="Help" />
    <div class="slideout_inner">
        <table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" class="table-slide">
        <tr><td width="15%"><img src="images/black-diamond.png"/></td><td>Click for data release</td></tr>
        <tr><td><img src="images/red-diamond.png"/></td><td>Click for data release (new)</td></tr>
        <tr><td><img src="images/hourglass.png"/></td><td>Results pending</td></tr>
        <tr><td><img src="images/black-checkmark.png"/></td><td>Click for milestone result</td></tr>
        <tr><td><img src="images/red-checkmark.png"/></td><td>Click for milestone result (new)</td></tr>
        <tr><td><img src="images/purple-bar.png"/></td><td>Click for milestone details</td></tr>
        <tr><td><img src="images/down.png"/></td><td>Display milestones</td></tr>
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
		$enrollValues = '<?php echo $globalOptions['enroll'];?>';
		if($enrollValues == '0')
		{	
			$minEnroll = $("#minenroll").val();
			$maxEnroll = $("#maxenroll").val();
		}
		else
		{
			$enrollValues = $enrollValues.split(' - ');
			$minEnroll = $enrollValues[0];
			$maxEnroll = $enrollValues[1];
		}
		
		$("#slider-range").slider({
			range: true,
			min: parseInt($("#minenroll").val()),
			max: parseInt($("#maxenroll").val()),
			values: [ parseInt($minEnroll), parseInt($maxEnroll) ],
			slide: function( event, ui ) {
				$("#amount").val(ui.values[ 0 ] + " - " + ui.values[ 1 ] );
			}
		});
		$("#amount").val( $("#slider-range").slider("values", 0 ) +
			" - " + $("#slider-range").slider("values", 1 ) );
		
		//highlight changes slider
		$("#slider-range-min").slider({
			range: true,
			min: 0,
			max: 5,
			step: 1,
			values: [ <?php echo $starttimerange;?>, <?php echo $endtimerange;?> ],
			slide: function(event, ui) {
				$("#startrange").val(timeEnum(ui.values[0]));
				$("#endrange").val(timeEnum(ui.values[1]));
			}
		});
		
		//$("#startrange").val(timeEnum($("#slider-range-min").slider("values", 0)));
		//$("#endrange").val(timeEnum($("#slider-range-min").slider("values", 1)));
		
		if($('.arrow').width() > 150)
		{ 
			var width = $('.arrow').width();
			width = width + 15;
			width = parseInt(width);
			$('#nav ul').width(width+'px');
			$('#nav ul li').width(width+'px');
		}
	});

	function timeEnum($timerange)
	{
		switch($timerange)
		{
			case 0: $timerange = "now"; break;
			case 1: $timerange = "1 week ago"; break;
			case 2: $timerange = "2 weeks ago"; break;
			case 3: $timerange = "1 month ago"; break;
			case 4: $timerange = "1 quarter ago"; break;
			case 5: $timerange = "1 year ago"; break;
		}
		return $timerange;
	}
	
	$(window).load(function() 
	{
		$('#loading').hide();
	});
</script>
</body>
</html>