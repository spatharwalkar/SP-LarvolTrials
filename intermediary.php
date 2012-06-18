<?php 
header('P3P: CP="CAO PSA OUR"');
session_start();

require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('special_chars.php');
require_once('run_trial_tracker.php');
require('searchhandler.php');


/********* If Report generation time is less than 1 Jan 2012, time machine is disabled **********/
if($_REQUEST['time'] != NULL && $_REQUEST['time'] != '')
{
if((date('Y-m-d H:i:s', $_REQUEST['time'])) < date('Y-m-d H:i:s',strtotime('2012-01-01 00:00:00')))
$_REQUEST['time'] = NULL;
}
else
{
$_REQUEST['time'] = NULL;
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

$globalOptions['change'] = '1 week';
$globalOptions['startrange'] = "now";
$globalOptions['endrange'] = "1 week ago";

if(isset($_REQUEST['minenroll']) && isset($_REQUEST['maxenroll']))
{
	$globalOptions['minEnroll'] = $_REQUEST['minenroll'];
	$globalOptions['maxEnroll'] = $_REQUEST['maxenroll'];
}

if(isset($_REQUEST['enroll']) && $_REQUEST['enroll'] != '0')
{	
	$globalOptions['enroll'] = $_REQUEST['enroll'];
}

if(isset($_REQUEST['sr']))
{	
	$globalOptions['startrange'] = $_REQUEST['sr'];
}

if(isset($_REQUEST['er']))
{	
	$globalOptions['endrange'] = $_REQUEST['er'];
}


switch($globalOptions['startrange'])
{	
	case "now": $starttimerange = 0; break;
	case "1 week ago": $starttimerange = 1; break;
	case "2 weeks ago": $starttimerange = 2; break;
	case "1 month ago": $starttimerange = 3; break;
	case "1 quarter ago": $starttimerange = 4; break;
	case "6 months ago": $starttimerange = 5; break;
	case "1 year ago": $starttimerange = 6; break;
	default: $starttimerange = 0; break;
}

switch($globalOptions['endrange'])
{
	case "now": $endtimerange = 0; break;
	case "1 week ago": $endtimerange = 1; break;
	case "2 weeks ago": $endtimerange = 2; break;
	case "1 month ago": $endtimerange = 3; break;
	case "1 quarter ago": $endtimerange = 4; break;
	case "6 months ago": $endtimerange = 5; break;
	case "1 year ago": $endtimerange = 6; break;
	default: $endtimerange = 3; break;
}

if(isset($_REQUEST['change']) && $_REQUEST['change'] != '')
{
	$globalOptions['change'] = $_REQUEST['change'];
	$globalOptions['startrange'] = 'now';
	$globalOptions['endrange'] = $globalOptions['change'] . ' ago';
}

switch($globalOptions['change'])
{
	case "1 week": $change_value = 1; break;
	case "2 weeks": $change_value = 2; break;
	case "1 month": $change_value = 3; break;
	case "1 quarter": $change_value = 4; break;
	case "6 months": $change_value = 5; break;
	case "1 year": $change_value = 6; break;
}
	
$lastChangedTime = filectime("css/intermediary.css");
$maxEnrollLimit = 5000;
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
    <script type="text/javascript" src="scripts/chrome.js"></script>
    <script type="text/javascript" src="scripts/jquery.hoverIntent.minified.js"></script>
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
			
			return true;
		});
		
		$(".arrow ul li a").click(function() 
		{
			$("#pr").val(this.rel);
			var productTitle = $($("#menuwrapper ul li:first").not("ul").contents().get(0)).text();
			var selectedProduct = $(this).text();
			
			$(this).text(productTitle);
			$($("#menuwrapper ul li:first").not("ul").contents().get(0)).text(selectedProduct);

			return false;  
		}); 
		
		var config = {    
			 over: makeTall, // function = onMouseOver callback (REQUIRED)    
			 timeout: 500, // number = milliseconds delay before onMouseOut    
			 out: makeShort// function = onMouseOut callback (REQUIRED)   
		};
		
		function makeTall(){  $(this).animate({"height":'100%'}, 200);}
		function makeShort(){ $(this).animate({"height":'1.1em'}, 500);}

		$(".manage tr").hoverIntent(config)
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
if(isset($_REQUEST['region']) && $_REQUEST['region'] != '')
{
	$globalOptions['region'] = explode(',', $_REQUEST['region']);
}

if(isset($_REQUEST['phase']) && $_REQUEST['phase'] != '')
{
	$globalOptions['phase'] = explode(',', $_REQUEST['phase']);
}

if(isset($_REQUEST['itype']) && $_REQUEST['itype'] != '')
{
	$globalOptions['itype'] = explode(',', $_REQUEST['itype']);
}

if(isset($_REQUEST['status']) && $_REQUEST['status'] != '')
{
	$globalOptions['status'] = explode(',', $_REQUEST['status']);
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

if(isset($_REQUEST['v']))
{
	$globalOptions['version'] = mysql_real_escape_string($_REQUEST['v']);
}

if(isset($_REQUEST['osu']) && $_REQUEST['osu'] == 'on')
{
	$globalOptions['onlyUpdates'] = "yes";
}

if(isset($_REQUEST['cd']))
{
	$globalOptions['countDetails'] = unserialize(gzinflate(base64_decode(rawurldecode($_REQUEST['cd']))));
}


if(isset($_REQUEST['format']) && $_REQUEST['format'] == "new")
{
	$globalOptions['encodeFormat'] = "new";
}

if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') !== FALSE)
|| (isset($_REQUEST['LI']) && $_REQUEST['LI'] == 1))
{
	$globalOptions['LI'] = "1";
}

if(isset($_REQUEST['pr']))
{	
	$globalOptions['product'] = $_REQUEST['pr'];
}

if(isset($_REQUEST['id']))
{	
	$globalOptions['url'] = $_REQUEST['id'];	
	$tt->generateTrialTracker('webpage', $_REQUEST['id'], NULL, 'standalone', $globalOptions);
}
else if(isset($_REQUEST['results']) && isset($_REQUEST['type']))
{
	$tt_type = $_REQUEST['type'] . 'stacked';
	$globalOptions['url'] = rawurlencode($_REQUEST['results']).'&amp;type='.$_REQUEST['type'];	
	$tt->generateTrialTracker('webpage', $_REQUEST['results'], $_REQUEST['time'], $tt_type, $globalOptions);
}
else if(isset($_REQUEST['results']))
{	
	$globalOptions['url'] = $_REQUEST['results'];	
	$tt->generateTrialTracker('webpage', $_REQUEST['results'], $_REQUEST['time'], 'unstacked', $globalOptions);
}
else if(isset($_REQUEST['p']) && isset($_REQUEST['a']))
{
	if(count($_REQUEST['p']) > 1 && count($_REQUEST['a']) > 1)
	{
		$tt_type = 'totalindexed';
	}
	else if(count($_REQUEST['p']) > 1 || count($_REQUEST['a']) > 1)
	{
		$tt_type = 'stackedindexed';
	}
	else 
	{
		$tt_type = 'singleindexed';
	}
	$globalOptions['url'] = 'p=' . $_REQUEST['p'] . '&a=' . $_REQUEST['a'];	
	
	if(isset($_REQUEST['JSON_search']))
	{
		$globalOptions['url'] = 'p=' . $_REQUEST['p'] . '&a=' . $_REQUEST['a'] . '&JSON_search=' . $_REQUEST['JSON_search'];
		$globalOptions['JSON_search'] = $_REQUEST['JSON_search'];
	}
	if(isset($_REQUEST['hm']) && trim($_REQUEST['hm']) != '' && $_REQUEST['hm'] != NULL)
	{
		$globalOptions['url'] .= '&hm=' . $_REQUEST['hm'];
		$globalOptions['hm'] = $_REQUEST['hm'];
	}
	
	$tt->generateTrialTracker('indexed', array('product' => $_REQUEST['p'], 'area' => $_REQUEST['a']), $_REQUEST['time'], 'indexed', $globalOptions);
}
else if(isset($_REQUEST['cparams']) || (isset($_REQUEST['leading']) && isset($_REQUEST['params'])))
{
	if(isset($_REQUEST['cparams']))
	{
		$tt->generateTrialTracker('stackedoldlink', array('cparams' => $_REQUEST['cparams'], 'leading' => $_REQUEST['leading'], 'params' => $_REQUEST['params']), 
		NULL, 'stackedoldlink', $globalOptions);
	}
	else
	{
		$tt->generateTrialTracker('unstackedoldlink', array('leading' => $_REQUEST['leading'], 'params' => $_REQUEST['params']), NULL, 'unstackedoldlink',
		 $globalOptions);
	}
}
else
{
	die('cell not set');
}

global $db;
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
			$minEnroll = parseInt($("#minenroll").val());
			$maxEnroll = parseInt($("#maxenroll").val());
		}
		else
		{
			$enrollValues = $enrollValues.split(' - ');
			$minEnroll = parseInt($enrollValues[0]);
			$maxEnroll = parseInt($enrollValues[1]);
		}
		
		$("#slider-range").slider({
			range: true,
			min: $("#minenroll").val(),
			max: (($("#maxenroll").val() > <?php echo $maxEnrollLimit;?>) ? <?php echo $maxEnrollLimit;?> : $("#maxenroll").val() ),
			values: [ parseInt($minEnroll), parseInt($maxEnroll) ],
			slide: function( event, ui ) {
				if(ui.values[ 1 ] == <?php echo $maxEnrollLimit;?>)
				{
					$("#amount").val(ui.values[ 0 ] + " - " + <?php echo $maxEnrollLimit;?> + "+" );
				}
				else
				{
					$("#amount").val(ui.values[ 0 ] + " - " + ui.values[ 1 ] );
				}
			}
		});
		
		if($("#slider-range").slider("values", 1) == <?php echo $maxEnrollLimit;?>)
		{
			$("#amount").val( $("#slider-range").slider("values", 0 ) +
				" - " + <?php echo $maxEnrollLimit;?> + '+' );
		}
		else
		{
			$("#amount").val( $("#slider-range").slider("values", 0 ) +
				" - " + $("#slider-range").slider("values", 1 ));
		}
		
		<?php if($db->loggedIn()) { ?>
		//highlight changes slider
		$("#slider-range-min").slider({
			range: true,
			min: 0,
			max: 6,
			step: 1,
			values: [ <?php echo $starttimerange;?>, <?php echo $endtimerange;?> ],
			slide: function(event, ui) {
				$("#startrange").val(timeEnum(ui.values[0]));
				$("#endrange").val(timeEnum(ui.values[1]));
			}
		});
		<?php } else { ?>
		$("#slider-range-min").slider({
			range: "min",
			value: <?php echo $change_value;?>,
			min: 1,
			max: 6,
			step:1,
			slide: function( event, ui ) {
				$("#amount3").val(timeEnumforGuests(ui.value));
			}
		});
		$timerange = '<?php echo $globalOptions['change'];?>';
		$("#amount3").val($timerange);
		<?php } ?>
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
			case 5: $timerange = "6 months ago"; break;
			case 6: $timerange = "1 year ago"; break;
		}
		return $timerange;
	}
	
	function timeEnumforGuests($timerange)
	{
		switch($timerange)
		{
			case 1: $timerange = "1 week"; break;
			case 2: $timerange = "2 weeks"; break;
			case 3: $timerange = "1 month"; break;
			case 4: $timerange = "1 quarter"; break;
			case 5: $timerange = "6 months"; break;
			case 6: $timerange = "1 year"; break;
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