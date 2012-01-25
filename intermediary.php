<?php 
header('P3P: CP="CAO PSA OUR"');
session_start();

require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('special_chars.php');
require_once('run_trial_tracker.php');

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

if(isset($_GET['sort']))
{
	$sortOptions = explode(',', mysql_real_escape_string($_GET['sort']));
	foreach($sortOptions as $skey => $svalue)
    {
        $value = substr($svalue, 0, 1);
        switch($value)
        {
            case 'p':
                $key = 'phase';
                break;
            case 'o':
                $key = 'overall_status';
                break;
            case 's':
                $key = 'start_date';
                break;
            case 'i':
                $key = 'inactive_date';
                break;
            case 'e':
                $key = 'enrollment';
                break;
        }
        $sortFields[$key] = $svalue;
    }
}
else
{
	$sortFields = array('phase' => 'pD', 'inactive_date' => 'iA', 'start_date' => 'sA', 'overall_status' => 'oA', 'enrollment' => 'eA');
}

$sortFieldName = array('phase' => 'Phase', 'inactive_date' => 'End Date', 'start_date' => 'Start Date', 'overall_status' => 'Status', 'enrollment' => 'N');

$sortTab = '';
foreach($sortFields as $skey => $svalue)
{
	$sortImg = '';
	$sortType = substr($svalue, 1, 1);
	
	$sortImg = ($sortType == 'A') ? 'asc' : 'des';
	
	$sortTab .= "<li title='" . $svalue . "' class='ui-state-default' name='" . $svalue . "' id='" . $skey . "'>"
		. "<div align=\"left\" style=\"vertical-align:middle;\">" . $sortFieldName[$skey] 
		. "<img src='images/" . $sortImg . ".png' id='" . substr($svalue, 0, 1) . "'  alt='" . $svalue 
		. "' border='0' align='right' style='margin:0px 5px; width:14px; height:14px; padding-right:70px; cursor:pointer;' onclick='javascript:fnSort(this)' /></div></li>";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   <!-- <base target='_blank' />-->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Online Trial Tracker</title>
    <link href="css/intermediary.css" rel="stylesheet" type="text/css" media="all" />
    <script src="scripts/jquery.js" type="text/javascript"></script>
    <script type="text/javascript">
		//sort tab
		var sorttab = "<div class='slide-out-sortdiv'>"+
						"<table cellpadding='0' cellspacing='0'>"+
						"<tr><td align='center' valign='baseline'><a class='sorthandle' href='#sort'>Content</a></td>"+
						"<td style='border: 1px solid #000;background-color:#fff;'>"+
						"<ul id='sortable' class='demo ui-sortable'>"+<?php echo json_encode($sortTab); ?>+"</ul></td></tr></table></div>";
    </script>	
    <script src="scripts/slideout.js" type="text/javascript"></script>	
    <script src="scripts/func.js" type="text/javascript"></script>	
    <script src="scripts/jquery-ui-1.8.16.custom.min.js" type="text/javascript"></script>
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
          	document.getElementById('so1').innerHTML = 
				 "<input type='checkbox' name='wh' value='1' />Withheld<br/>"+
				 "<input type='checkbox' name='afm' value='1' />Approved for marketing<br/>" +
				 "<input type='checkbox' name='tna' value='1' />Temporarily not available<br/>" + 
				 "<input type='checkbox' name='nla' value='1' />No Longer Available<br/>" + 
				 "<input type='checkbox' name='wd' value='1' />Withdrawn<br/>" + 
				 "<input type='checkbox' name='t' value='1' />Terminated<br/>" +
				 "<input type='checkbox' name='s' value='1' />Suspended<br/>" +
				 "<input type='checkbox' name='c' value='1' />Completed<br/>";
          } 
		  else if(value == 'active') 
		  {
			document.getElementById('so1').innerHTML = 
				'<input type="checkbox" name="nyr" value="1" />Not yet recruiting<br/>' +
				'<input type="checkbox" name="r" value="1" />Recruiting<br/>' + 
				'<input type="checkbox" name="ebi" value="1" />Enrolling by invitation<br/>' + 
				'<input type="checkbox" name="anr" value="1" />Active, not recruiting<br/>' + 
				'<input type="checkbox" name="av" value="1" />Available<br/>' +
				'<input type="checkbox" name="nlr" value="1" />No longer recruiting<br/>';
          } 
		  else 
		  {
			document.getElementById('so1').innerHTML = 
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
				'<input type="checkbox" name="av" value="1" />Available<br/>' +
				'<input type="checkbox" name="nlr" value="1" />No longer recruiting<br/>';
          }
      }
    //]]>
	//code for drag drop tab
	$(document).ready(function()
	{
		$("#sortable").sortable({revert: true});
		$("#draggable").draggable({connectToSortable: "#sortable", helper: "clone", revert: "invalid"});
		$("ul, li").disableSelection();
		
		var sortInput = jQuery('#sort');
		var list = jQuery('#sortable');
		
		var fnSubmit = function() 
		{
			var sortOrder = [];
			list.children('li').each(function()
			{
				sortOrder.push($(this).data('id'));
			});
			sortInput.val(sortOrder.join(','));
		};
		
		list.children('li').each(function() 
		{
			var li = $(this);
			li.data('id',li.attr('title')).attr('title','');
		});
		
		/* sortables */
		list.sortable({
			opacity: 0.7,
			update: function() {
				fnSubmit();
			}
		});
		list.disableSelection();
	});
	
	function fnSort(th) 
	{	
		var src = $(th).attr('src');
		var imgId = $(th).attr('id');
		var newSrc = 'images/';
		var newOrder;
		
		if(src == 'images/des.png')
		{
			newSrc += 'asc.png';
			newOrder = 'A';
		}
		else
		{
			newSrc += 'des.png';
			newOrder = 'D';
		}
		$(th).attr('src',newSrc);
		
		var fld;
		var sortInput = $('#sort').val();
		var sortOrder = sortInput.split(',');
		
		for(var i=0; i<sortOrder.length; i++)
		{
			fld = sortOrder[i].substring(0, 1);
			if(fld == imgId)
			{
				sortOrder[i] = fld+newOrder;
			}
			else
			{
				sortOrder[i] = sortOrder[i];
			}
		}
		$('#sort').val(sortOrder.join(','));
	};
    </script>
    </head>
<body>
<div id="loading">
  <img id="loading-image" src="images/loading.gif" alt="Loading..." />
</div>
<?php
$globalOptions['sortOrder'] = $sortFields;
$globalOptions['type'] = 'activeTrials';
$globalOptions['findChangesFrom'] = rawurlencode(base64_encode(gzdeflate('week')));
$globalOptions['version'] = rawurlencode(base64_encode('0'));

$globalOptions['page'] = 1;
$globalOptions['onlyUpdates'] = "no";
$globalOptions['encodeFormat'] = "old";
$globalOptions['filtersOne'] = array();
$globalOptions['filtersTwo'] = array();

if(isset($_GET['fcf']))
{
	$globalOptions['findChangesFrom'] = $_GET['fcf'];
}

if(isset($_GET['list']))
{
	if(gzinflate(base64_decode(rawurldecode($_GET['list']))) == 'in')
	{
		$globalOptions['type'] = 'inactiveTrials';
	}
	elseif(gzinflate(base64_decode(rawurldecode($_GET['list']))) == 'al')
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

if(isset($_GET['osu']) && $_GET['osu'] == rawurlencode(base64_encode(gzdeflate('on'))))
{
	$globalOptions['onlyUpdates'] = "yes";
}

if(isset($_GET['so1']))
{
	$globalOptions['filtersOne'] = $_GET['so1'];
}

if(isset($_GET['so2']))
{
	foreach($_GET['so2'] as $key => $value)
	{
		$globalOptions['filtersTwo'][] = gzinflate(base64_decode(rawurldecode($value)));
	}
}

if(isset($_GET['cd']))
{
	$globalOptions['countDetails'] = unserialize(gzinflate(base64_decode(rawurldecode($_GET['cd']))));
}


if(isset($_GET['format']) && $_GET['format'] == "new")
{
	$globalOptions['encodeFormat'] = "new";
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
<script type="text/javascript">
	$(window).load(function() 
	{
		$('#loading').hide();
	});
</script>
</body>
</html>
