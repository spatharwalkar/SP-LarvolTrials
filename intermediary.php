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

$globalOptions['change'] = '1 month';
$globalOptions['version'] = rawurlencode(base64_encode('0'));

$globalOptions['page'] = 1;
$globalOptions['onlyUpdates'] = "no";
$globalOptions['encodeFormat'] = "old";
$globalOptions['LI'] = "0";
$globalOptions['minEnroll'] = "0";
$globalOptions['maxEnroll'] = "0";

if(isset($_GET['change']))
{
	$globalOptions['change'] = $_GET['change'];
}

if(isset($_GET['minenroll']) && isset($_GET['maxenroll']))
{
	$globalOptions['minEnroll'] = $_GET['minenroll'];
	$globalOptions['maxEnroll'] = $_GET['maxenroll'];
}

if(isset($_GET['enroll']) && $_GET['enroll'] != '')
{	
	$globalOptions['enroll'] = $_GET['enroll'];
}

switch($globalOptions['change'])
{
	case "1 week": $change_value = 1; break;
	case "2 weeks": $change_value = 2; break;
	case "1 month": $change_value = 3; break;
	case "1 quarter": $change_value = 4; break;
	case "1 year": $change_value = 5; break;
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
    <script src="scripts/jquery.js" type="text/javascript"></script>
    <script src="scripts/func.js" type="text/javascript"></script>	
    <script src="scripts/jquery-1.7.1.min.js"></script>
	<script src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
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
	
	function timeEnum($timerange)
	{
		switch($timerange)
		{
			case 1: $timerange = "1 week"; break;
			case 2: $timerange = "2 weeks"; break;
			case 3: $timerange = "1 month"; break;
			case 4: $timerange = "1 quarter"; break;
			case 5: $timerange = "1 year"; break;
		}
		return $timerange;
	}
	
	$(function() 
	{
		$("#slider-range-min").slider({
			range: "min",
			value: <?php echo $change_value;?>,
			min: 1,
			max: 5,
			step:1,
			slide: function( event, ui ) {
				$("#amount3").val(timeEnum(ui.value));
			}
		});
		$timerange = '<?php echo $globalOptions['change'];?>';
		$("#amount3").val($timerange);
		
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
else if(isset($_GET['JSON_search']))
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
	$globalOptions['url'] = 'p=' . $_GET['p'] . '&a=' . $_GET['a'] . '&JSON_search=' . $_GET['JSON_search'];	
	
	if(isset($_GET['JSON_search']) && $_GET['JSON_search'] != '' && $_GET['JSON_search'] != NULL)
	$globalOptions['JSON_search'] = $_GET['JSON_search'];
	
	$tt->generateTrialTracker('indexed_search', array('product' => $_GET['p'], 'area' => $_GET['a']), $_GET['time'], 'indexed_search', $globalOptions);
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
			min: $("#minenroll").val(),
			max: $("#maxenroll").val(),
			values: [ $minEnroll, $maxEnroll ],
			slide: function( event, ui ) {
				$("#amount").val(ui.values[ 0 ] + " - " + ui.values[ 1 ] );
			}
		});
		$("#amount").val( $("#slider-range").slider("values", 0 ) +
			" - " + $("#slider-range").slider("values", 1 ) );
		
	});
	
	$(window).load(function() 
	{
		$('#loading').hide();
	});
</script>
</body>
</html>