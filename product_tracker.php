<?php
session_start();
//unset($_SESSION['OHM_array']);
require_once('db.php');
require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
global $db;
global $now;
if(!isset($_REQUEST['id'])) return;
$id = mysql_real_escape_string(htmlspecialchars($_REQUEST['id']));
if(!is_numeric($id)) return;

if($_POST['dwformat'])
{
	Download_reports();
	exit;
}

$query = 'SELECT `name`, `user`, `footnotes`, `description`, `category`, `shared`, `total`, `dtt`, `display_name` FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
$res = mysql_query($query) or die('Bad SQL query getting master heatmap report');
$res = mysql_fetch_array($res) or die('Report not found.');
$rptu = $res['user'];
$shared = $res['shared'];
$total_fld=$res['total'];
$name = $res['name'];
$dtt = $res['dtt'];
$Report_DisplayName=$res['display_name'];
$footnotes = htmlspecialchars($res['footnotes']);
$description = htmlspecialchars($res['description']);
$category = $res['category'];
	
$query = 'SELECT `num`,`type`,`type_id`, `display_name`, `category`, `tag` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'product\' ORDER BY num ASC';
$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers');

$rows = array();
$productIds = array();
$rowsDisplayName = array();

while($header = mysql_fetch_array($res))
{
	if($header['type_id'] != NULL)
	{
		$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description, company FROM `products` WHERE id = '" . $header['type_id'] . "' "));
		$rows[$header['num']] = $result['name'];
		if($result['company'] != NULL && trim($result['company']) != '')
		{
			$result['company']=str_replace(',',', ',$result['company']);
			$result['company']=str_replace(',  ',', ',$result['company']);
			$rowsCompanyName[$header['num']] = ' / '.$result['company'];
		} 
		$rowsDescription[$header['num']] = $result['description'];
	}
	else
	{
		$rows[$header['num']] = $header['type_id'];
	}
	$productIds[$header['num']] = $header['type_id'];
}

	// SELECT MAX ROW AND MAX COL
$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'product\'';
$res = mysql_query($query) or die(mysql_error());
$max_row = mysql_fetch_array($res);

	// SELECT MAX NUM of Area
$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'area\'';
$res = mysql_query($query) or die(mysql_error());
$header = mysql_fetch_array($res);
// Max Area Id
$query = 'SELECT `type_id` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'area\' AND `num`='.$header['num'];
$res = mysql_query($query) or die(mysql_error());
$header = mysql_fetch_array($res);

if($header['type_id'] != NULL)
{
	$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, display_name, description FROM `areas` WHERE id = '" . $header['type_id'] . "' "));
	$areaDisplayName = $header['display_name'];	///Display name from master hm header table
	$areaDescription = $result['description'];
}
$areaId = $header['type_id'];

$row_total=array();
$col_total=array();
$active_total=0;
$count_total=0;
$data_matrix=array();

///// No of columns in our graph
$columns = 10;
$column_width = 80;
$max_count = 0;

foreach($rows as $row => $rval)
{
	/// Fill up all data in Data Matrix only, so we can sort all data at one place
	$data_matrix[$row]['productName'] = $rval;
	$data_matrix[$row]['product_CompnayName'] = $rowsCompanyName[$row];
	$data_matrix[$row]['productIds'] = $productIds[$row];
	
	if(isset($areaId) && $areaId != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
	{
		///// Initialize data
		$data_matrix[$row]['active']=0;
			
		$data_matrix[$row]['total']=0;
		
		$data_matrix[$row]['indlead']=0;
		
		$data_matrix[$row]['total_phase_na']=0;
		$data_matrix[$row]['active_phase_na']=0;
		$data_matrix[$row]['indlead_phase_na']=0;
		$data_matrix[$row]['total_phase_0']=0;
		$data_matrix[$row]['active_phase_0']=0;
		$data_matrix[$row]['indlead_phase_0']=0;
		$data_matrix[$row]['total_phase_1']=0;
		$data_matrix[$row]['active_phase_1']=0;
		$data_matrix[$row]['indlead_phase_1']=0;
		$data_matrix[$row]['total_phase_2']=0;
		$data_matrix[$row]['active_phase_2']=0;
		$data_matrix[$row]['indlead_phase_2']=0;
		$data_matrix[$row]['total_phase_3']=0;
		$data_matrix[$row]['active_phase_3']=0;
		$data_matrix[$row]['indlead_phase_3']=0;
		$data_matrix[$row]['total_phase_4']=0;
		$data_matrix[$row]['active_phase_4']=0;
		$data_matrix[$row]['indlead_phase_4']=0;
		
		//// To avoid multiple queries to database, we are quering only one time and retrieveing all data and seprating each type
		$phase_query = 'SELECT dt.`is_active`, dt.`phase`, dt.`institution_type` FROM rpt_masterhm_cells rpt JOIN product_trials pt ON (rpt.`product` = pt.`product`) JOIN area_trials ar ON (rpt.`area` = ar.`area`) JOIN data_trials dt ON (dt.`larvol_id` = pt.`trial` && dt.`larvol_id` = ar.`trial`) WHERE pt.`product`=' . $productIds[$row] . ' AND ar.`area`='. $areaId;
		$phase_res = mysql_query($phase_query) or die(mysql_error());
		while($phase_row=mysql_fetch_array($phase_res))
		{
			$data_matrix[$row]['total']++;
			if($phase_row['is_active'])
			{
				$data_matrix[$row]['active']++;
				if($phase_row['institution_type'] == 'industry_lead_sponsor')
				$data_matrix[$row]['indlead']++;
			}
				
			if($phase_row['phase'] == 'N/A' || $phase_row['phase'] == '' || $phase_row['phase'] === NULL)
			{
				$data_matrix[$row]['total_phase_na']++;
				if($phase_row['is_active'])
				{
					$data_matrix[$row]['active_phase_na']++;
					if($phase_row['institution_type'] == 'industry_lead_sponsor')
					$data_matrix[$row]['indlead_phase_na']++;
				}
			}
			else if($phase_row['phase'] == '0')
			{
				$data_matrix[$row]['total_phase_0']++;
				if($phase_row['is_active'])
				{
					$data_matrix[$row]['active_phase_0']++;
					if($phase_row['institution_type'] == 'industry_lead_sponsor')
					$data_matrix[$row]['indlead_phase_0']++;
				}
			}
			else if($phase_row['phase'] == '1' || $phase_row['phase'] == '0/1' || $phase_row['phase'] == '1a' 
			|| $phase_row['phase'] == '1b' || $phase_row['phase'] == '1a/1b' || $phase_row['phase'] == '1c')
			{
				$data_matrix[$row]['total_phase_1']++;
				if($phase_row['is_active'])
				{
					$data_matrix[$row]['active_phase_1']++;
					if($phase_row['institution_type'] == 'industry_lead_sponsor')
					$data_matrix[$row]['indlead_phase_1']++;
				}
			}
			else if($phase_row['phase'] == '2' || $phase_row['phase'] == '1/2' || $phase_row['phase'] == '1b/2' 
			|| $phase_row['phase'] == '1b/2a' || $phase_row['phase'] == '2a' || $phase_row['phase'] == '2a/2b' 
			|| $phase_row['phase'] == '2a/b' || $phase_row['phase'] == '2b')
			{
				$data_matrix[$row]['total_phase_2']++;
				if($phase_row['is_active'])
				{
					$data_matrix[$row]['active_phase_2']++;
					if($phase_row['institution_type'] == 'industry_lead_sponsor')
					$data_matrix[$row]['indlead_phase_2']++;
				}
			}
			else if($phase_row['phase'] == '3' || $phase_row['phase'] == '2/3' || $phase_row['phase'] == '2b/3' 
			|| $phase_row['phase'] == '3a' || $phase_row['phase'] == '3b')
			{
				$data_matrix[$row]['total_phase_3']++;
				if($phase_row['is_active'])
				{
					$data_matrix[$row]['active_phase_3']++;
					if($phase_row['institution_type'] == 'industry_lead_sponsor')
					$data_matrix[$row]['indlead_phase_3']++;
				}
			}
			else if($phase_row['phase'] == '4' || $phase_row['phase'] == '3/4' || $phase_row['phase'] == '3b/4')
			{
				$data_matrix[$row]['total_phase_4']++;
				if($phase_row['is_active'])
				{
					$data_matrix[$row]['active_phase_4']++;
					if($phase_row['institution_type'] == 'industry_lead_sponsor')
					$data_matrix[$row]['indlead_phase_4']++;
				}	
			}
		}	//// End of while
		if($data_matrix[$row]['total'] > $max_count)
		$max_count = $data_matrix[$row]['total'];
		
	}
	else
	{
		$data_matrix[$row]['active']=0;
		$data_matrix[$row]['total']=0;
		$data_matrix[$row]['indlead']=0;
		
		$data_matrix[$row]['total_phase_na']=0;
		$data_matrix[$row]['active_phase_na']=0;
		$data_matrix[$row]['indlead_phase_na']=0;
		$data_matrix[$row]['total_phase_0']=0;
		$data_matrix[$row]['active_phase_0']=0;
		$data_matrix[$row]['indlead_phase_0']=0;
		$data_matrix[$row]['total_phase_1']=0;
		$data_matrix[$row]['active_phase_1']=0;
		$data_matrix[$row]['indlead_phase_1']=0;
		$data_matrix[$row]['total_phase_2']=0;
		$data_matrix[$row]['active_phase_2']=0;
		$data_matrix[$row]['indlead_phase_2']=0;
		$data_matrix[$row]['total_phase_3']=0;
		$data_matrix[$row]['active_phase_3']=0;
		$data_matrix[$row]['indlead_phase_3']=0;
		$data_matrix[$row]['total_phase_4']=0;
		$data_matrix[$row]['active_phase_4']=0;
		$data_matrix[$row]['indlead_phase_4']=0;
		
		if($data_matrix[$row]['total'] < $max_count)
		$max_count = $data_matrix[$row]['total'];
	}
}

/// This function willl Sort multidimensional array according to industry lead column
$data_matrix = sortTwoDimensionArrayByKey($data_matrix,'indlead');

///// No of inner columns
$original_max_count = $max_count;
$max_count = ceil(($max_count / $columns)) * $columns;
$column_interval = $max_count / $columns;
$inner_columns = 10;
$inner_width = $column_width  / $inner_columns;

if($max_count > 0)
$ratio = ($columns * $inner_columns) / $max_count;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Larvol Trials :: Product Tracker</title>
<script type="text/javascript" src="scripts/popup-window.js"></script>
<script src="scripts/jquery-1.7.1.min.js"></script>
<script src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
<script type="text/javascript" src="scripts/chrome.js"></script>
<script type="text/javascript" src="scripts/iepngfix_tilebg.js"></script>
<link href="css/popup_form.css" rel="stylesheet" type="text/css" media="all" />
<link href="css/themes/cupertino/jquery-ui-1.8.17.custom.css" rel="stylesheet" type="text/css" media="screen" />
<style type="text/css">

/* As in IE6 hover css does not works, below htc file is added which contains js script which will be executed only in IE, the script convert simple as well as complex hover css into compatible format for IE6 by replacing hover by class css - this file is used so that help tab as well as product selector will work in IE6 without any changes of code as well as css code and script can be also useful for making other css to work in IE6 like :hover and :active for IE6+, and additionally :focus for IE7 and IE8. */
ul, li, slideout { behavior:url("css/csshover3.htc"); }
img { behavior: url("css/iepngfix.htc"); }
body { font-family:Verdana; font-size: 13px;}
a, a:hover{ height:100%; display:block; text-decoration:none;}

.report_name {
	font-weight:bold;
	font-size:18px;
}

.controls td{
	border-bottom:1px solid #44F;
	border-right:1px solid #44F;
	padding: 10px 0 0 3px;
    vertical-align: top;
}
.controls th{
	font-weight:normal;
	border-bottom: 1px solid #4444FF;
    border-right: 1px solid #4444FF;
}
.right{
	border-right:0px !important;
}

.bottom{
	border-bottom:0px !important;
}
.controls input{
	margin:0.1em;
}

.tooltip {
	color: #000000; outline: none;
	cursor:default; text-decoration: none;
}
.tooltip span {
	border-radius: 5px 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; 
	box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1); -webkit-box-shadow: 5px 5px rgba(0, 0, 0, 0.1); -moz-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);
	font-family:Verdana; font-size: 12px;
	position: absolute; 
	margin-left: 0; width: 280px; display: none; z-index: 0;
}
.classic { padding: 0.8em 1em; }
.classic {background: #FFFFAA; border: 1px solid #FFAD33; }

#slideout {
	position: fixed;
	_position:absolute;
	top: 40px;
	right: 0;
	margin: 12px 0 0 0;
}

.slideout_inner {
	position:absolute;
	top: 40px;
	right: -255px;
	display:none;
}

#slideout:hover .slideout_inner{
	display : block;
	position:absolute;
	top: 2px;
	right: 0px;
	width: 280px;
	z-index:10;
}

.table-slide{
	border:1px solid #000;
	height:100px;
	width:280px;
}
.table-slide td{
	border-right:1px solid #000;
	padding:8px;
	padding-right:20px;
	border-bottom:1px solid #000;
}


.gray {
	background-color:#CCCCCC;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.blue {
	background-color:#00ccff;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.green {
	background-color:#99cc00;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.yellow {
	background-color:#ffff00;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.orange {
	background-color:#ff9900;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.red {
	background-color:#ff0000;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.downldbox {
	height:auto;
	width:310px;
	font-weight:bold;
}

.downldbox ul{
	list-style:none;
	margin:5px;
	padding:0px;
}

.downldbox ul li{
	width: 130px;
	float:left;
	margin:2px;
}

.dropmenudiv{
	position:absolute;
	top: 0;
	border: 1px solid #DDDDDD; /*THEME CHANGE HERE*/
	/*border-bottom-width: 0;*/
	font:normal 12px Verdana;
	line-height:18px;
	z-index:100;
	background-color: white;
	width: 50px;
	visibility: hidden;
}

.break_words{
	word-wrap: break-word;
}

.tag {
color:#120f3c;
font-weight:bold;
}


.graph_bottom {
border-bottom:1px solid #CCCCCC;
}
th { font-weight:normal; }
.last_tick_height {
height:4px;
}
.last_tick_width {
width:4px;
}
.graph_top {
border-top:1px solid #CCCCCC;
}
.graph_right {
border-right:1px solid #CCCCCC;
}
.prod_col {
/*width:300px;
max-width:300px;*/
}
.side_tick_height {
height:2px;
line-height:2px;
}

.graph_gray {
	background-color:#CCCCCC;
}

.graph_blue {
	background-color:#00ccff;
}

.graph_green {
	background-color:#99cc00;
}

.graph_yellow {
	background-color:#ffff00;
}

.graph_orange {
	background-color:#ff9900;
}

.graph_red {
	background-color:#ff0000;
}

</style>
<script language="javascript" type="text/javascript">
function change_view()
{
	var limit = document.getElementById('Tot_rows').value;
	var dwcount = document.getElementById('dwcount');
	
	var i=0;
	for(i=0;i<limit;i++)
	{
		if(dwcount.value == 'active')
		{
			var row_type = document.getElementById('active_Graph_Row_'+i);
			if(row_type != null && row_type != '')
			{
				<?php if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') == FALSE) { ?>
				document.getElementById("active_Graph_Row_"+i).style.display = "table-row";
				<? } else { ?>
				document.getElementById("active_Graph_Row_"+i).style.display = "inline";
				<?php } ?>
				document.getElementById("total_Graph_Row_"+i).style.display = "none";
				document.getElementById("indlead_Graph_Row_"+i).style.display = "none";
			}
		}
		else if(dwcount.value == 'total')
		{
			var row_type = document.getElementById('total_Graph_Row_'+i);
			if(row_type != null && row_type != '')
			{
				document.getElementById("active_Graph_Row_"+i).style.display = "none";
				<?php if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') == FALSE) { /// IE does not support table-row and moziall does not support inline ?>
				document.getElementById("total_Graph_Row_"+i).style.display = "table-row";
				<? } else { ?>
				document.getElementById("total_Graph_Row_"+i).style.display = "inline";
				<?php } ?>
				document.getElementById("indlead_Graph_Row_"+i).style.display = "none";
			}
		}
		else
		{
			var row_type = document.getElementById('indlead_Graph_Row_'+i);
			if(row_type != null && row_type != '')
			{
				document.getElementById("active_Graph_Row_"+i).style.display = "none";
				document.getElementById("total_Graph_Row_"+i).style.display = "none";
				<?php if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') == FALSE) { ?>
				document.getElementById("indlead_Graph_Row_"+i).style.display = "table-row";
				<? } else { ?>
				document.getElementById("indlead_Graph_Row_"+i).style.display = "inline";
				<?php } ?>
			}
		}
			
	}	
}

function Set_Link_Height()
{
	var limit = document.getElementById('Tot_rows').value;
	var dwcount = document.getElementById('dwcount');
	//id="indelead_ProdCol_'.$row.'"
	var i=0;
	for(i=0;i<limit;i++)
	{
		 //// As links are not getting correct height in Larvol Insight page
		//// Before getting height of each cell, make all rows visible and then again hide it at end as per requirement.
		var row_type = document.getElementById('active_Graph_Row_'+i);
		if(row_type != null && row_type != '')
		{
			<?php if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') == FALSE) { ?>
			document.getElementById("active_Graph_Row_"+i).style.display = "table-row";
			document.getElementById("total_Graph_Row_"+i).style.display = "table-row";
			document.getElementById("indlead_Graph_Row_"+i).style.display = "table-row";
			<? } else { ?>
			document.getElementById("active_Graph_Row_"+i).style.display = "inline";
			document.getElementById("total_Graph_Row_"+i).style.display = "inline";
			document.getElementById("indlead_Graph_Row_"+i).style.display = "inline";
			<?php } ?>
		}
			
		var IS_Prod_Row = document.getElementById("ProdCol_"+i);
		if(IS_Prod_Row != null && IS_Prod_Row != '')
		{
			$(".Link_"+i).css('height', document.getElementById("ProdCol_"+i).offsetHeight+'px');
			$(".Link_"+i).css('min-height', document.getElementById("ProdCol_"+i).offsetHeight+'px');
			$(".Link_"+i).css('max-height', document.getElementById("ProdCol_"+i).offsetHeight+'px');
		}
	}
	change_view();
}
</script>
</head>

<div id="slideout">
    <img src="images/help.png" alt="Help" />
    <div class="slideout_inner">
        <table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" class="table-slide">
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

<body bgcolor="#FFFFFF" style="background-color:#FFFFFF;">
<?php 

$name = htmlspecialchars(strlen($name)>0?$name:('report '.$id.''));

$Report_Name = ((trim($Report_DisplayName) != '' && $Report_DisplayName != NULL)? trim($Report_DisplayName):'report '.$id.'');

if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') == FALSE) || !isset($_SERVER['HTTP_REFERER']))
{
	$htmlContent .= '<table cellspacing="0" cellpadding="0" width="100%" style="background-color:#FFFFFF;">'
				   . '<tr><td style="background-color:#FFFFFF;"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" /></td>'
				   . '<td style="background-color:#FFFFFF;" nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;margin-left:40px;">Interface work in progress</span>'
				   . '<br/><span style="font-weight:normal;">Send feedback to '
				   . '<a style="display:inline;color:#0000FF;" target="_self" href="mailto:larvoltrials@larvol.com">'
				   . 'larvoltrials@larvol.com</a></span></td>'
				   . '<td style="background-color:#FFFFFF;" class="report_name">Name: ' . htmlspecialchars($Report_Name) . '</td></tr></table><br/>';
}
				
$htmlContent .= '<br style="line-height:11px;"/>'
				.'<form action="product_tracker.php" method="post">'
				. '<table width="264px" border="0" cellspacing="0" cellpadding="0" class="controls" align="center">'
				//. '<tr><th align="center">View mode</th><th align="center" class="right">Actions</th></tr>'
				. '<tr>'
				. '<td class="bottom right"><p style="margin-top:8px;margin-right:5px;"><select id="dwcount" name="dwcount" onchange="change_view();">'
				. '<option value="indlead" selected="selected">Active industry trials</option>'
				. '<option value="active">Active trials</option>'
				. '<option value="total">All trials</option></select></p></td>'
				. '<td class="bottom right" style="vertical-align:middle;">'
				. '<div style="border:1px solid #000000; float:right; margin-top: 8px; padding:2px;" id="chromemenu"><a rel="dropmenu"><span style="padding:2px; padding-right:4px; background-position:left center; background-repeat:no-repeat; background-image:url(\'./images/save.png\'); cursor:pointer; ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Export</b></span></a></div>'
				. '</td>'
				. '</tr>'
				. '</table>';
				
$htmlContent  .= '<div id="dropmenu" class="dropmenudiv" style="width: 310px;">'
				.'<div style="height:110px; padding:6px;"><div class="downldbox"><div class="newtext">Download options</div>'
				. '<input type="hidden" name="id" id="id" value="' . $id . '" />'
				. '<ul><li><label>Which format: </label></li>'
				. '<li><select id="dwformat" name="dwformat" size="3" style="height:54px">'
				. '<option value="exceldown" selected="selected">Excel</option>'
				. '<option value="pdfdown">PDF</option>'
				. '<option value="tsvdown">TSV</option>'
				. '</select></li>'
				. '</ul>'
				. '<input type="submit" name="download" title="Download" value="Download file" style="margin-left:8px;"  />'
				. '</div></div>'
				. '</div><script type="text/javascript">cssdropdown.startchrome("chromemenu");</script>'
				. '</form>';
				
						
$htmlContent .= '<div align="center" style="padding-left:10px; padding-right:15px; padding-top:20px; padding-bottom:20px;"><table align="center" style="height:100%; vertical-align:top;" cellpadding="0" cellspacing="0">'
			    . '<thead>';


$htmlContent .= '<tr class="side_tick_height"><th class="prod_col">&nbsp;</th><th class="last_tick_width">&nbsp;</th>';
for($j=0; $j < $columns; $j++)
{
	for($k=0; $k < $inner_columns; $k++)
	$htmlContent .= '<th width="'.$inner_width .'px" colspan="1">&nbsp;</th>';
}
$htmlContent .= '</tr>';

for($incr=0; $incr < count($rows); $incr++)
{	$row = $incr;
	$htmlContent .= '<tr class="side_tick_height"><th class="prod_col side_tick_height">&nbsp;</th><th class="'. (($row == 0) ? 'graph_top':'' ) .' graph_right last_tick_width">&nbsp;</th>';
	for($j=0; $j < $columns; $j++)
	{
		$htmlContent .= '<th width="'.$column_width.'px" colspan="'.$inner_columns.'" class="graph_right">&nbsp;</th>';
	}
	$htmlContent .= '</tr>';
	
	////// Color Graph - Bar Starts
	
	//// Code for Indlead
	$Rounded = (($data_matrix[$row]['indlead_phase_4'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_4'])) + (($data_matrix[$row]['indlead_phase_3'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_3'])) + (($data_matrix[$row]['indlead_phase_2'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_2'])) + (($data_matrix[$row]['indlead_phase_1'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_1'])) + (($data_matrix[$row]['indlead_phase_0'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_0'])) + (($data_matrix[$row]['indlead_phase_na'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_na']));
	$Actual = ($ratio * $data_matrix[$row]['indlead_phase_4']) + ($ratio * $data_matrix[$row]['indlead_phase_3']) + ($ratio * $data_matrix[$row]['indlead_phase_2']) + ($ratio * $data_matrix[$row]['indlead_phase_1']) + ($ratio * $data_matrix[$row]['indlead_phase_0'])+ ($ratio * $data_matrix[$row]['indlead_phase_na']);
	$Err = floor($Rounded - $Actual);
	
	$Max_ValueKey = Max_ValueKey($data_matrix[$row]['indlead_phase_4'], $data_matrix[$row]['indlead_phase_3'], $data_matrix[$row]['indlead_phase_2'], $data_matrix[$row]['indlead_phase_1'], $data_matrix[$row]['indlead_phase_0'], $data_matrix[$row]['indlead_phase_na']);
					
	$htmlContent .= '<tr id="indlead_Graph_Row_'.$row.'"><th align="right" class="prod_col" id="ProdCol_'.$row.'"><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&itype=0&hm=' . $id . '" target="_blank" style="text-decoration:underline;">'.$data_matrix[$row]['productName'].$data_matrix[$row]['product_CompnayName'].'</a></th><th class="graph_right last_tick_width">&nbsp;</th>';
	
	$total_cols = $inner_columns * $columns;
	$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['indlead']);
	$phase_space = 0;
	
	if($data_matrix[$row]['indlead_phase_4'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['indlead_phase_4']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_4']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 0 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_red" title="'.$data_matrix[$row]['indlead_phase_4'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&itype=0&phase=4&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['indlead_phase_3'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['indlead_phase_3']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_3']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 1 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_orange" title="'.$data_matrix[$row]['indlead_phase_3'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&itype=0&phase=3&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['indlead_phase_2'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['indlead_phase_2']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_2']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 2 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_yellow" title="'.$data_matrix[$row]['indlead_phase_2'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&itype=0&phase=2&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['indlead_phase_1'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['indlead_phase_1']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_1']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 3 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_green" title="'.$data_matrix[$row]['indlead_phase_1'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&itype=0&phase=1&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['indlead_phase_0'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['indlead_phase_0']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_0']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 4 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_blue" title="'.$data_matrix[$row]['indlead_phase_0'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&itype=0&phase=0&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['indlead_phase_na'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['indlead_phase_na']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_na']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 5 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_gray" title="'.$data_matrix[$row]['indlead_phase_na'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&itype=0&phase=na&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	$remain_span = $total_cols - $phase_space;
	
	if($remain_span > 0)
	{
		$aq_sp = 0;
		while($aq_sp < $phase_space)
		$aq_sp = $aq_sp + $inner_columns;
		
		$extra_sp = $aq_sp - $phase_space;
		if($extra_sp > 0)
		$htmlContent .= '<th colspan="'.($extra_sp).'" class="graph_right side_tick_height">&nbsp;</th>';
	
		$remain_span = $remain_span - $extra_sp;
		while($remain_span > 0)
		{
			$htmlContent .= '<th colspan="'.($inner_columns).'" class="graph_right side_tick_height">&nbsp;</th>';
			$remain_span = $remain_span - $inner_columns;
		}
	}
	$htmlContent .= '</tr>';
	
	//// Code for Active
	
	$Rounded = (($data_matrix[$row]['active_phase_4'] > 0 && round($ratio * $data_matrix[$row]['active_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_4'])) + (($data_matrix[$row]['active_phase_3'] > 0 && round($ratio * $data_matrix[$row]['active_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_3'])) + (($data_matrix[$row]['active_phase_2'] > 0 && round($ratio * $data_matrix[$row]['active_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_2'])) + (($data_matrix[$row]['active_phase_1'] > 0 && round($ratio * $data_matrix[$row]['active_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_1'])) + (($data_matrix[$row]['active_phase_0'] > 0 && round($ratio * $data_matrix[$row]['active_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_0'])) + (($data_matrix[$row]['active_phase_na'] > 0 && round($ratio * $data_matrix[$row]['active_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_na']));
	$Actual = ($ratio * $data_matrix[$row]['active_phase_4']) + ($ratio * $data_matrix[$row]['active_phase_3']) + ($ratio * $data_matrix[$row]['active_phase_2']) + ($ratio * $data_matrix[$row]['active_phase_1']) + ($ratio * $data_matrix[$row]['active_phase_0'])+ ($ratio * $data_matrix[$row]['active_phase_na']);
	$Err = floor($Rounded - $Actual);
	$Max_ValueKey = Max_ValueKey($data_matrix[$row]['active_phase_4'], $data_matrix[$row]['active_phase_3'], $data_matrix[$row]['active_phase_2'], $data_matrix[$row]['active_phase_1'], $data_matrix[$row]['active_phase_0'], $data_matrix[$row]['active_phase_na']);
					
	$htmlContent .= '<tr style="display:none;" id="active_Graph_Row_'.$row.'"><th align="right" class="prod_col"><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&hm=' . $id . '" target="_blank" style="text-decoration:underline;">'.$data_matrix[$row]['productName'].$data_matrix[$row]['product_CompnayName'].'</a></th><th class="graph_right last_tick_width">&nbsp;</th>';
	
	$total_cols = $inner_columns * $columns;
	$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['active']);
	$phase_space = 0;
	
	if($data_matrix[$row]['active_phase_4'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['active_phase_4']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_4']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 0 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_red" title="'.$data_matrix[$row]['active_phase_4'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&phase=4&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['active_phase_3'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['active_phase_3']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_3']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 1 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_orange" title="'.$data_matrix[$row]['active_phase_3'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&phase=3&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['active_phase_2'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['active_phase_2']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_2']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 2 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_yellow" title="'.$data_matrix[$row]['active_phase_2'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&phase=2&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['active_phase_1'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['active_phase_1']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_1']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 3 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_green" title="'.$data_matrix[$row]['active_phase_1'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&phase=1&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['active_phase_0'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['active_phase_0']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_0']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 4 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_blue" title="'.$data_matrix[$row]['active_phase_0'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&phase=0&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['active_phase_na'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['active_phase_na']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_na']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 5 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_gray" title="'.$data_matrix[$row]['active_phase_na'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=1&phase=na&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	$remain_span = $total_cols - $phase_space;
	
	if($remain_span > 0)
	{
		$aq_sp = 0;
		while($aq_sp < $phase_space)
		$aq_sp = $aq_sp + $inner_columns;
		
		$extra_sp = $aq_sp - $phase_space;
		if($extra_sp > 0)
		$htmlContent .= '<th colspan="'.($extra_sp).'" class="graph_right side_tick_height">&nbsp;</th>';
	
		$remain_span = $remain_span - $extra_sp;
		while($remain_span > 0)
		{
			$htmlContent .= '<th colspan="'.($inner_columns).'" class="graph_right side_tick_height">&nbsp;</th>';
			$remain_span = $remain_span - $inner_columns;
		}
	}
	$htmlContent .= '</tr>';
	
	//// Code for Total
	
	$Rounded = (($data_matrix[$row]['total_phase_4'] > 0 && round($ratio * $data_matrix[$row]['total_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_4'])) + (($data_matrix[$row]['total_phase_3'] > 0 && round($ratio * $data_matrix[$row]['total_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_3'])) + (($data_matrix[$row]['total_phase_2'] > 0 && round($ratio * $data_matrix[$row]['total_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_2'])) + (($data_matrix[$row]['total_phase_1'] > 0 && round($ratio * $data_matrix[$row]['total_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_1'])) + (($data_matrix[$row]['total_phase_0'] > 0 && round($ratio * $data_matrix[$row]['total_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_0'])) + (($data_matrix[$row]['total_phase_na'] > 0 && round($ratio * $data_matrix[$row]['total_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_na']));
	$Actual = ($ratio * $data_matrix[$row]['total_phase_4']) + ($ratio * $data_matrix[$row]['total_phase_3']) + ($ratio * $data_matrix[$row]['total_phase_2']) + ($ratio * $data_matrix[$row]['total_phase_1']) + ($ratio * $data_matrix[$row]['total_phase_0'])+ ($ratio * $data_matrix[$row]['total_phase_na']);
	$Err = floor($Rounded - $Actual);
	$Max_ValueKey = Max_ValueKey($data_matrix[$row]['total_phase_4'], $data_matrix[$row]['total_phase_3'], $data_matrix[$row]['total_phase_2'], $data_matrix[$row]['total_phase_1'], $data_matrix[$row]['total_phase_0'], $data_matrix[$row]['total_phase_na']);
	
	
	$htmlContent .= '<tr style="display:none;" id="total_Graph_Row_'.$row.'"><th align="right" class="prod_col"><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=2&hm=' . $id . '" target="_blank" style="text-decoration:underline;">'.$data_matrix[$row]['productName'].$data_matrix[$row]['product_CompnayName'].'</a></th><th class="graph_right last_tick_width">&nbsp;</th>';
	
	$total_cols = $inner_columns * $columns;
	$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['total']);
	$phase_space = 0;
	
	if($data_matrix[$row]['total_phase_4'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['total_phase_4']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_4']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 0 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_red" title="'.$data_matrix[$row]['total_phase_4'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=2&phase=4&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['total_phase_3'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['total_phase_3']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_3']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 1 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
			
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_orange" title="'.$data_matrix[$row]['total_phase_3'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=2&phase=3&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['total_phase_2'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['total_phase_2']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_2']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 2 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
			
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_yellow" title="'.$data_matrix[$row]['total_phase_2'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=2&phase=2&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['total_phase_1'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['total_phase_1']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_1']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 3 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
			
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_green" title="'.$data_matrix[$row]['total_phase_1'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=2&phase=1&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['total_phase_0'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['total_phase_0']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_0']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 4 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_blue" title="'.$data_matrix[$row]['total_phase_0'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=2&phase=0&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	if($data_matrix[$row]['total_phase_na'] > 0)
	{
		if(round($ratio * $data_matrix[$row]['total_phase_na']) > 0)
			$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_na']);
		else
			$Mini_Bar_Width = 1;
		
		if($Max_ValueKey == 5 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
		$Mini_Bar_Width = $Mini_Bar_Width - $Err;
		
		if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
			$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
		else
			$Mini_Bar_Width = $Total_Bar_Width;
		
		$phase_space =  $phase_space + $Mini_Bar_Width;					
		$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="side_tick_height graph_gray" title="'.$data_matrix[$row]['total_phase_na'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&list=2&phase=na&hm=' . $id . '" target="_blank" class="Link_'.$row.'">&nbsp;</a></th>';
	}
	
	$remain_span = $total_cols - $phase_space;
	
	if($remain_span > 0)
	{
		$aq_sp = 0;
		while($aq_sp < $phase_space)
		$aq_sp = $aq_sp + $inner_columns;
		
		$extra_sp = $aq_sp - $phase_space;
		if($extra_sp > 0)
		$htmlContent .= '<th colspan="'.($extra_sp).'" class="graph_right side_tick_height">&nbsp;</th>';
	
		$remain_span = $remain_span - $extra_sp;
		while($remain_span > 0)
		{
			$htmlContent .= '<th colspan="'.($inner_columns).'" class="graph_right side_tick_height">&nbsp;</th>';
			$remain_span = $remain_span - $inner_columns;
		}
	}
	$htmlContent .= '</tr>';
	
	////// End Of - Color Graph - Bar Starts
	
	$htmlContent .= '<tr class="side_tick_height"><th class="prod_col side_tick_height">&nbsp;</th><th class="graph_bottom graph_right last_tick_width">&nbsp;</th>';
	for($j=0; $j < $columns; $j++)
	{
		$htmlContent .= '<th width="'.$column_width.'px" colspan="'.$inner_columns.'" class="graph_right">&nbsp;</th>';
	}
	$htmlContent .= '</tr>';
}			   
			   
$htmlContent .= '<tr class="last_tick_height"><th class="last_tick_height prod_col"><font style="line-height:4px;">&nbsp;</font></th><th class="graph_right last_tick_width"><font style="line-height:4px;">&nbsp;</font></th>';
for($j=0; $j < $columns; $j++)
$htmlContent .= '<th width="'.$column_width.'px" colspan="'.$inner_columns.'" class="graph_top graph_right"><font style="line-height:4px;">&nbsp;</font></th>';
$htmlContent .= '</tr>';

$htmlContent .= '<tr><th class="prod_col"></th><th width="2px" class=""></th>';
for($j=0; $j < $columns; $j++)
$htmlContent .= '<th align="right" width="'.$column_width.'px" colspan="'.(($j==0)? $inner_columns+1 : $inner_columns).'" class="">'.(($j+1) * $column_interval).'</th>';
$htmlContent .= '</tr>';
						
$htmlContent .= '</thead></table></div>';

//// Common Data
$htmlContent .= '<input type="hidden" value="'.count($rows).'" name="Tot_rows" id="Tot_rows" />';
////// End of Common Data

			
print $htmlContent;
?>

</body>
</html>
<script language="javascript" type="text/javascript">
change_view();
Set_Link_Height();
</script>
<?php
function Download_reports()
{
	ob_start();
	global $db;
	global $now;
	if(!isset($_REQUEST['id'])) return;
	$id = mysql_real_escape_string(htmlspecialchars($_REQUEST['id']));
	if(!is_numeric($id)) return;
	
	$query = 'SELECT `name`, `user`, `footnotes`, `description`, `category`, `shared`, `total`, `dtt`, `display_name` FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report');
	$res = mysql_fetch_array($res) or die('Report not found.');
	$rptu = $res['user'];
	$shared = $res['shared'];
	$total_fld=$res['total'];
	$name = $res['name'];
	$dtt = $res['dtt'];
	$Report_DisplayName=$res['display_name'];
	$footnotes = htmlspecialchars($res['footnotes']);
	$description = htmlspecialchars($res['description']);
	$category = $res['category'];
		
	$query = 'SELECT `num`,`type`,`type_id`, `display_name`, `category`, `tag` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'product\' ORDER BY num ASC';
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers');
	
	$rows = array();
	$productIds = array();
	$rowsDisplayName = array();
	
	while($header = mysql_fetch_array($res))
	{
		if($header['type_id'] != NULL)
		{
			$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description, company FROM `products` WHERE id = '" . $header['type_id'] . "' "));
			$rows[$header['num']] = $result['name'];
			if($result['company'] != NULL && trim($result['company']) != '')
			{
				$result['company']=str_replace(',',', ',$result['company']);
				$result['company']=str_replace(',  ',', ',$result['company']);
				$rowsCompanyName[$header['num']] = ' / '.$result['company'];
			} 
			$rowsDescription[$header['num']] = $result['description'];
		}
		else
		{
			$rows[$header['num']] = $header['type_id'];
		}
		$productIds[$header['num']] = $header['type_id'];
	}
	
	// SELECT MAX ROW AND MAX COL
	$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'product\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_row = mysql_fetch_array($res);
	
	// SELECT MAX NUM of Area
	$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'area\'';
	$res = mysql_query($query) or die(mysql_error());
	$header = mysql_fetch_array($res);
	// Max Area Id
	$query = 'SELECT `type_id` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'area\' AND `num`='.$header['num'];
	$res = mysql_query($query) or die(mysql_error());
	$header = mysql_fetch_array($res);
	
	if($header['type_id'] != NULL)
	{
		$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, display_name, description FROM `areas` WHERE id = '" . $header['type_id'] . "' "));
		$areaDisplayName = $header['display_name'];	///Display name from master hm header table
		$areaDescription = $result['description'];
	}
	$areaId = $header['type_id'];
	
	$row_total=array();
	$col_total=array();
	$active_total=0;
	$count_total=0;
	$data_matrix=array();
	
	///// No of columns in our graph
	$columns = 10;
	$column_width = 80;
	$max_count = 0;
	
	foreach($rows as $row => $rval)
	{
		/// Fill up all data in Data Matrix only, so we can sort all data at one place
		$data_matrix[$row]['productName'] = $rval;
		$data_matrix[$row]['product_CompnayName'] = $rowsCompanyName[$row];
		$data_matrix[$row]['productIds'] = $productIds[$row];
	
		if(isset($areaId) && $areaId != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
		{
			$data_matrix[$row]['active']=0;
				
			$data_matrix[$row]['total']=0;
			
			$data_matrix[$row]['indlead']=0;
			
			$data_matrix[$row]['total_phase_na']=0;
			$data_matrix[$row]['active_phase_na']=0;
			$data_matrix[$row]['indlead_phase_na']=0;
			$data_matrix[$row]['total_phase_0']=0;
			$data_matrix[$row]['active_phase_0']=0;
			$data_matrix[$row]['indlead_phase_0']=0;
			$data_matrix[$row]['total_phase_1']=0;
			$data_matrix[$row]['active_phase_1']=0;
			$data_matrix[$row]['indlead_phase_1']=0;
			$data_matrix[$row]['total_phase_2']=0;
			$data_matrix[$row]['active_phase_2']=0;
			$data_matrix[$row]['indlead_phase_2']=0;
			$data_matrix[$row]['total_phase_3']=0;
			$data_matrix[$row]['active_phase_3']=0;
			$data_matrix[$row]['indlead_phase_3']=0;
			$data_matrix[$row]['total_phase_4']=0;
			$data_matrix[$row]['active_phase_4']=0;
			$data_matrix[$row]['indlead_phase_4']=0;
		
			//// To avoid multiple queries to database, we are quering only one time and retrieveing all data and seprating each type
			$phase_query = 'SELECT dt.`is_active`, dt.`phase`, dt.`institution_type` FROM rpt_masterhm_cells rpt JOIN product_trials pt ON (rpt.`product` = pt.`product`) JOIN area_trials ar ON (rpt.`area` = ar.`area`) JOIN data_trials dt ON (dt.`larvol_id` = pt.`trial` && dt.`larvol_id` = ar.`trial`) WHERE pt.`product`=' . $productIds[$row] . ' AND ar.`area`='. $areaId;
			$phase_res = mysql_query($phase_query) or die(mysql_error());
			while($phase_row=mysql_fetch_array($phase_res))
			{
				$data_matrix[$row]['total']++;
				if($phase_row['is_active'])
				{
					$data_matrix[$row]['active']++;
					if($phase_row['institution_type'] == 'industry_lead_sponsor')
					$data_matrix[$row]['indlead']++;
				}
					
				if($phase_row['phase'] == 'N/A' || $phase_row['phase'] == '' || $phase_row['phase'] === NULL)
				{
					$data_matrix[$row]['total_phase_na']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_na']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_na']++;
					}
				}
				else if($phase_row['phase'] == '0')
				{
					$data_matrix[$row]['total_phase_0']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_0']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_0']++;
					}
				}
				else if($phase_row['phase'] == '1' || $phase_row['phase'] == '0/1' || $phase_row['phase'] == '1a' 
				|| $phase_row['phase'] == '1b' || $phase_row['phase'] == '1a/1b' || $phase_row['phase'] == '1c')
				{
					$data_matrix[$row]['total_phase_1']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_1']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_1']++;
					}
				}
				else if($phase_row['phase'] == '2' || $phase_row['phase'] == '1/2' || $phase_row['phase'] == '1b/2' 
				|| $phase_row['phase'] == '1b/2a' || $phase_row['phase'] == '2a' || $phase_row['phase'] == '2a/2b' 
				|| $phase_row['phase'] == '2a/b' || $phase_row['phase'] == '2b')
				{
					$data_matrix[$row]['total_phase_2']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_2']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_2']++;
					}
				}
				else if($phase_row['phase'] == '3' || $phase_row['phase'] == '2/3' || $phase_row['phase'] == '2b/3' 
				|| $phase_row['phase'] == '3a' || $phase_row['phase'] == '3b')
				{
					$data_matrix[$row]['total_phase_3']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_3']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_3']++;
					}
				}
				else if($phase_row['phase'] == '4' || $phase_row['phase'] == '3/4' || $phase_row['phase'] == '3b/4')
				{
					$data_matrix[$row]['total_phase_4']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_4']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_4']++;
					}	
				}
			}	//// End of while
			if($data_matrix[$row]['total'] > $max_count)
			$max_count = $data_matrix[$row]['total'];
			
		}
		else
		{
			$data_matrix[$row]['active']=0;
			$data_matrix[$row]['total']=0;
			$data_matrix[$row]['indlead']=0;
			
			$data_matrix[$row]['total_phase_na']=0;
			$data_matrix[$row]['active_phase_0']=0;
			$data_matrix[$row]['indlead_phase_0']=0;
			$data_matrix[$row]['total_phase_1']=0;
			$data_matrix[$row]['active_phase_1']=0;
			$data_matrix[$row]['indlead_phase_1']=0;
			$data_matrix[$row]['total_phase_2']=0;
			$data_matrix[$row]['active_phase_2']=0;
			$data_matrix[$row]['indlead_phase_2']=0;
			$data_matrix[$row]['total_phase_3']=0;
			$data_matrix[$row]['active_phase_3']=0;
			$data_matrix[$row]['indlead_phase_3']=0;
			$data_matrix[$row]['total_phase_4']=0;
			$data_matrix[$row]['active_phase_4']=0;
			$data_matrix[$row]['indlead_phase_4']=0;
			
			if($data_matrix[$row]['total'] < $max_count)
			$max_count = $data_matrix[$row]['total'];
		
			$data_matrix[$row]['color']='background-color:#DDF;';
			$data_matrix[$row]['color_code']='DDF';
		}
	}
	
	/// This function willl Sort multidimensional array according to industry lead column
	$data_matrix = sortTwoDimensionArrayByKey($data_matrix,'indlead');
	
	///// No of inner columns
	$original_max_count = $max_count;
	$max_count = ceil(($max_count / $columns)) * $columns;
	$column_interval = $max_count / $columns;
	$inner_columns = 10;
	$inner_width = $column_width  / $inner_columns;
	
	if($max_count > 0)
	$ratio = ($columns * $inner_columns) / $max_count;
	
	$total_cols = $inner_columns * $columns;
	
	$Report_Name = htmlspecialchars((trim($Report_DisplayName) != '' && $Report_DisplayName != NULL)? trim($Report_DisplayName):'report '.$id.'');
	
	if($_POST['dwcount']=='active')
	{
		$tooltip=$title="Active trials";
		$pdftitle="Active trials";
		$link_part = '&list=1&hm=' . $id;
		$mode = 'active';
	}
	elseif($_POST['dwcount']=='total')
	{
		$pdftitle=$tooltip=$title="All trials (Active + Inactive)";
		$link_part = '&list=2&hm=' . $id;
		$mode = 'total';
	}
	else
	{
		$tooltip=$title="Active industry lead sponsor trials";
		$pdftitle="Active industry lead sponsor trials";
		$link_part = '&list=1&itype=0&hm=' . $id;
		$mode = 'indlead';
	}
	
	
	if($_POST['dwformat']=='exceldown')
	{
	  	$name = htmlspecialchars(strlen($name)>0?$name:('report '.$id.''));
		
		$Prod_Col = 'A';
		$Start_Char = 'B';
		
		// Create excel file object
		$objPHPExcel = new PHPExcel();
	
		// Set properties
		$objPHPExcel->getProperties()->setCreator(SITE_NAME);
		$objPHPExcel->getProperties()->setLastModifiedBy(SITE_NAME);
		$objPHPExcel->getProperties()->setTitle(substr($name,0,20));
		$objPHPExcel->getProperties()->setSubject(substr($name,0,20));
		$objPHPExcel->getProperties()->setDescription(substr($name,0,20));
		
		$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);
		$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('Verdana'); 
	
		// Build sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle(substr($name,0,20));
		//$objPHPExcel->getActiveSheet()->getStyle('A1:AA2000')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(36);
		
		$Excel_HMCounter = 0;
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$Excel_HMCounter, 'Report name:');
		$objPHPExcel->getActiveSheet()->mergeCells('B' . $Excel_HMCounter . ':BH' . $Excel_HMCounter);
		$objPHPExcel->getActiveSheet()->getStyle('B' . $Excel_HMCounter)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => false));
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $Excel_HMCounter, substr($Report_Name,0,250));
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$Excel_HMCounter, 'Display Mode:');
		$objPHPExcel->getActiveSheet()->mergeCells('B' . $Excel_HMCounter . ':BH' . $Excel_HMCounter);
		$objPHPExcel->getActiveSheet()->getStyle('B' . $Excel_HMCounter)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => false));
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $Excel_HMCounter, $tooltip);
		
		/// Extra Row
		$Excel_HMCounter++;
		$from = $Start_Char;
		$from++;
		for($j=0; $j < $columns; $j++)
		{
			$to = getColspanforExcelExport($from, $inner_columns);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
			$from = $to;
			$from++;
		}
		
		/// Set Dimension
		$Char = $Start_Char;
		$objPHPExcel->getActiveSheet()->getColumnDimension($Char)->setWidth(1);
		$Char++;
		for($j=0; $j < ($columns+1); $j++)
		{
			for($k=0; $k < $inner_columns; $k++)
			{
				$objPHPExcel->getActiveSheet()->getColumnDimension($Char)->setWidth(1);
				$Char++;
			}
		}
		
		for($incr=0; $incr < count($rows); $incr++)
		{	
			$row = $incr;
			
			$Excel_HMCounter++;
			
			$from = $Start_Char;
			$from++;
			for($j=0; $j < $columns; $j++)
			{
				$to = getColspanforExcelExport($from, $inner_columns);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
				$from = $to;
				$from++;
			}
			$objPHPExcel->getActiveSheet()->getRowDimension($Excel_HMCounter)->setRowHeight(5);
			$Excel_HMCounter++;
	
			////// Color Graph - Bar Starts
				
			//// Code for Indlead
			if(isset($data_matrix[$row]['productIds']) && $data_matrix[$row]['productIds'] != NULL && !empty($areaId))
			{
				/// Product Column
				$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
				$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
				
				$cell = $Prod_Col . $Excel_HMCounter;
				$objPHPExcel->getActiveSheet()->SetCellValue($cell, $data_matrix[$row]['productName'].$data_matrix[$row]['product_CompnayName']);
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .$link_part); 
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip);
				if($rdesc)
 			    {
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->setAuthor('Description:');
 			    	$objCommentRichText = $objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun('Description:');
 			    	$objCommentRichText->getFont()->setBold(true);
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun("\r\n");
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun($rdesc);
 			    } 
		
				$from = $Start_Char;
				//// Limit product names so that they will not overlap other cells
				$white_font['font']['color']['rgb'] = 'FFFFFF';
				$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray($white_font);
				$objPHPExcel->getActiveSheet()->setCellValue($from . $Excel_HMCounter, '.');
						
				$from++;
				
				//// Graph starts
				if($mode == 'indlead')
				{
					$Rounded = (($data_matrix[$row]['indlead_phase_4'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_4'])) + (($data_matrix[$row]['indlead_phase_3'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_3'])) + (($data_matrix[$row]['indlead_phase_2'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_2'])) + (($data_matrix[$row]['indlead_phase_1'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_1'])) + (($data_matrix[$row]['indlead_phase_0'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_0'])) + (($data_matrix[$row]['indlead_phase_na'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_na']));
					$Actual = ($ratio * $data_matrix[$row]['indlead_phase_4']) + ($ratio * $data_matrix[$row]['indlead_phase_3']) + ($ratio * $data_matrix[$row]['indlead_phase_2']) + ($ratio * $data_matrix[$row]['indlead_phase_1']) + ($ratio * $data_matrix[$row]['indlead_phase_0'])+ ($ratio * $data_matrix[$row]['indlead_phase_na']);
					$Err = floor($Rounded - $Actual);
					$Max_ValueKey = Max_ValueKey($data_matrix[$row]['indlead_phase_4'], $data_matrix[$row]['indlead_phase_3'], $data_matrix[$row]['indlead_phase_2'], $data_matrix[$row]['indlead_phase_1'], $data_matrix[$row]['indlead_phase_0'], $data_matrix[$row]['indlead_phase_na']);
				
					$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['indlead']);
					$phase_space = 0;
					if($data_matrix[$row]['indlead_phase_4'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['indlead_phase_4']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_4']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 0 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('4'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=4' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_4']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['indlead_phase_3'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['indlead_phase_3']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_3']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 1 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('3'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=3' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_3']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['indlead_phase_2'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['indlead_phase_2']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_2']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 2 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('2'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=2' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_2']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['indlead_phase_1'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['indlead_phase_1']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_1']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 3 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('1'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=1' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_1']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['indlead_phase_0'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['indlead_phase_0']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_0']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 4 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('0'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=0' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_0']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['indlead_phase_na'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['indlead_phase_na']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_na']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 5 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('na'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=na' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_na']);
						$from = $to;
						$from++;
					}
				}
				else if ($mode == 'active')
				{
					$Rounded = (($data_matrix[$row]['active_phase_4'] > 0 && round($ratio * $data_matrix[$row]['active_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_4'])) + (($data_matrix[$row]['active_phase_3'] > 0 && round($ratio * $data_matrix[$row]['active_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_3'])) + (($data_matrix[$row]['active_phase_2'] > 0 && round($ratio * $data_matrix[$row]['active_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_2'])) + (($data_matrix[$row]['active_phase_1'] > 0 && round($ratio * $data_matrix[$row]['active_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_1'])) + (($data_matrix[$row]['active_phase_0'] > 0 && round($ratio * $data_matrix[$row]['active_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_0'])) + (($data_matrix[$row]['active_phase_na'] > 0 && round($ratio * $data_matrix[$row]['active_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_na']));
					$Actual = ($ratio * $data_matrix[$row]['active_phase_4']) + ($ratio * $data_matrix[$row]['active_phase_3']) + ($ratio * $data_matrix[$row]['active_phase_2']) + ($ratio * $data_matrix[$row]['active_phase_1']) + ($ratio * $data_matrix[$row]['active_phase_0'])+ ($ratio * $data_matrix[$row]['active_phase_na']);
					$Err = floor($Rounded - $Actual);
					$Max_ValueKey = Max_ValueKey($data_matrix[$row]['active_phase_4'], $data_matrix[$row]['active_phase_3'], $data_matrix[$row]['active_phase_2'], $data_matrix[$row]['active_phase_1'], $data_matrix[$row]['active_phase_0'], $data_matrix[$row]['active_phase_na']);
	
					$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['active']);
					$phase_space = 	0;
					if($data_matrix[$row]['active_phase_4'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['active_phase_4']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_4']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 0 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('4'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=4' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_4']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['active_phase_3'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['active_phase_3']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_3']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 1 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('3'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=3' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_3']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['active_phase_2'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['active_phase_2']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_2']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 2 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('2'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=2' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_2']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['active_phase_1'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['active_phase_1']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_1']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 3 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('1'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=1' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_1']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['active_phase_0'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['active_phase_0']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_0']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 4 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('0'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=0' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_0']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['active_phase_na'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['active_phase_na']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_na']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 5 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('na'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=na' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_na']);
						$from = $to;
						$from++;
					}
				}
				else
				{
					$Rounded = (($data_matrix[$row]['total_phase_4'] > 0 && round($ratio * $data_matrix[$row]['total_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_4'])) + (($data_matrix[$row]['total_phase_3'] > 0 && round($ratio * $data_matrix[$row]['total_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_3'])) + (($data_matrix[$row]['total_phase_2'] > 0 && round($ratio * $data_matrix[$row]['total_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_2'])) + (($data_matrix[$row]['total_phase_1'] > 0 && round($ratio * $data_matrix[$row]['total_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_1'])) + (($data_matrix[$row]['total_phase_0'] > 0 && round($ratio * $data_matrix[$row]['total_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_0'])) + (($data_matrix[$row]['total_phase_na'] > 0 && round($ratio * $data_matrix[$row]['total_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_na']));
					$Actual = ($ratio * $data_matrix[$row]['total_phase_4']) + ($ratio * $data_matrix[$row]['total_phase_3']) + ($ratio * $data_matrix[$row]['total_phase_2']) + ($ratio * $data_matrix[$row]['total_phase_1']) + ($ratio * $data_matrix[$row]['total_phase_0'])+ ($ratio * $data_matrix[$row]['total_phase_na']);
					$Err = floor($Rounded - $Actual);
					$Max_ValueKey = Max_ValueKey($data_matrix[$row]['total_phase_4'], $data_matrix[$row]['total_phase_3'], $data_matrix[$row]['total_phase_2'], $data_matrix[$row]['total_phase_1'], $data_matrix[$row]['total_phase_0'], $data_matrix[$row]['total_phase_na']);
				
					$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['total']);
					$phase_space = 	0;
					if($data_matrix[$row]['total_phase_4'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['total_phase_4']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_4']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 0 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('4'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=4' . $link_part);  
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_4']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['total_phase_3'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['total_phase_3']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_3']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 1 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('3'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=3' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_3']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['total_phase_2'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['total_phase_2']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_2']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 2 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('2'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=2' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_2']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['total_phase_1'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['total_phase_1']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_1']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 3 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('1'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=1' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_1']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['total_phase_0'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['total_phase_0']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_0']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 4 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('0'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=0' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_0']);
						$from = $to;
						$from++;
					}
					
					if($data_matrix[$row]['total_phase_na'] > 0)
					{
						if(round($ratio * $data_matrix[$row]['total_phase_na']) > 0)
							$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_na']);
						else
							$Mini_Bar_Width = 1;
						
						if($Max_ValueKey == 5 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
						$Mini_Bar_Width = $Mini_Bar_Width - $Err;
						
						if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
							$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
						else
							$Mini_Bar_Width = $Total_Bar_Width;
						$phase_space =  $phase_space + $Mini_Bar_Width;	
						$to = getColspanforExcelExport($from, $Mini_Bar_Width);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('na'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . '&phase=na' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_na']);
						$from = $to;
						$from++;
					}
				}
				
				$remain_span = $total_cols - $phase_space;
		
				if($remain_span > 0)
				{
					$aq_sp = 0;
					while($aq_sp < $phase_space)
					$aq_sp = $aq_sp + $inner_columns;
					
					$extra_sp = $aq_sp - $phase_space;
					if($extra_sp > 0)
					{
						$to = getColspanforExcelExport($from, $extra_sp);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$from = $to;
						$from++;
					}
					
					$remain_span = $remain_span - $extra_sp;
					while($remain_span > 0)
					{
						$to = getColspanforExcelExport($from, $inner_columns);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$from = $to;
						$from++;
						
						$remain_span = $remain_span - $inner_columns;
					}
				} // End of remain span
			}	/// End of data check
			////// End Of - Color Graph - Bar
		}	/// End of rows foreach		
		
		
		$Excel_HMCounter++;
		$from = $Start_Char;
		$from++;
		for($j=0; $j < $columns; $j++)
		{
			$to = getColspanforExcelExport($from, $inner_columns);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
			$from = $to;
			$from++;
		}
		$objPHPExcel->getActiveSheet()->getRowDimension($Excel_HMCounter)->setRowHeight(5);
			
		$Excel_HMCounter++;
		$from = $Start_Char;
		$from++;
		for($j=0; $j < $columns; $j++)
		{
			$to = getColspanforExcelExport($from, (($j==0)? $inner_columns+1 : $inner_columns));
			$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
			$objPHPExcel->getActiveSheet()->SetCellValue($from . $Excel_HMCounter, (($j+1) * $column_interval));
			$from = $to;
			$from++;
		}
		//$objPHPExcel->getActiveSheet()->getRowDimension($Excel_HMCounter)->setRowHeight(5);
		
		/// Extra Row
		$Excel_HMCounter++;
		
		
		/////Phase Legend
		$Excel_HMCounter++;
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . $Excel_HMCounter, 'Phase:');
		
		$phases = array('N/A', 'Phase 0', 'Phase 1', 'Phase 2', 'Phase 3', 'Phase 4');
		$phasenums = array(); foreach($phases as $k => $p)  $phasenums[$k] = str_ireplace(array('phase',' '),'',$p);
		$phase_legend_nums = array('N/A', '0', '1', '2', '3', '4');
		//$p_colors = array('DDDDDD', 'BBDDDD', 'AADDEE', '99DDFF', 'DDFF99', 'FFFF00', 'FFCC00', 'FF9900', 'FF7711', 'FF4422');
		$p_colors = array('BFBFBF', '00CCFF', '99CC00', 'FFFF00', 'FF9900', 'FF0000');
		$phase_legend_colors = array('BFBFBF', '00CCFF', '99CC00', 'FFFF00', 'FF9900', 'FF0000');
		
		$from = $Start_Char;
		$from++;
		foreach($p_colors as $key => $color)
		{
			$to = getColspanforExcelExport($from, $inner_columns);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
			$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getFill()->getStartColor()->setRGB($color);
			$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->setValueExplicit($phasenums[$key], PHPExcel_Cell_DataType::TYPE_STRING);
			$from = $to;
			$from++;
		}
			
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			
		//ob_end_clean(); 
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Larvol_' . substr($Report_Name,0,20) . '_Product_Analytic_Excel_' . date('Y-m-d_H.i.s') . '.xlsx"');
			
		header("Content-Transfer-Encoding: binary ");
		$objWriter->save('php://output');
		@flush();
	} //Excel Function Ends
	
	if($_POST['dwformat']=='tsvdown')
	{
		$TSV_data = "";
		
		$TSV_data = "Product Name \t Phase 4 \t Phase 3 \t Phase 2 \t Phase 1 \t Phase 0 \t Phase N/A \n";
		
		for($incr=0; $incr < count($rows); $incr++)
		{	
			$row = $incr;
			
			if(isset($data_matrix[$row]['productIds']) && $data_matrix[$row]['productIds'] != NULL && !empty($areaId))
			{
				$TSV_data .= $data_matrix[$row]['productName'].$data_matrix[$row]['product_CompnayName'] ." \t ";
				if($mode == 'indlead')
				{
					$TSV_data .= $data_matrix[$row]['indlead_phase_4'] ." \t ". $data_matrix[$row]['indlead_phase_3'] ." \t ". $data_matrix[$row]['indlead_phase_2'] ." \t ". $data_matrix[$row]['indlead_phase_1'] ." \t ". $data_matrix[$row]['indlead_phase_0'] ." \t ". $data_matrix[$row]['indlead_phase_na'] ." \n";
				}
				else if($mode == 'active')
				{
					$TSV_data .= $data_matrix[$row]['active_phase_4'] ." \t ". $data_matrix[$row]['active_phase_3'] ." \t ". $data_matrix[$row]['active_phase_2'] ." \t ". $data_matrix[$row]['active_phase_1'] ." \t ". $data_matrix[$row]['active_phase_0'] ." \t ". $data_matrix[$row]['active_phase_na'] ." \n";
				}
				else
				{
					$TSV_data .= $data_matrix[$row]['total_phase_4'] ." \t ". $data_matrix[$row]['total_phase_3'] ." \t ". $data_matrix[$row]['total_phase_2'] ." \t ". $data_matrix[$row]['total_phase_1'] ." \t ". $data_matrix[$row]['total_phase_0'] ." \t ". $data_matrix[$row]['total_phase_na'] ." \n";
				}
			}
		}
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-type: application/force-download"); 
		header("Content-Type: application/tsv");
		header('Content-Disposition: attachment;filename="Larvol_' . substr($Report_Name,0,20) . '_Product_Analytic_TSV_' . date('Y-m-d_H.i.s'). '.tsv"');
		header("Content-Transfer-Encoding: binary ");
		echo $TSV_data;
	}	/// TSV FUNCTION ENDS HERE
	
	if($_POST['dwformat']=='pdfdown')
	{
		require_once('tcpdf/tcpdf.php');  
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set document information
		//$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Larvol Trials');
		$pdf->SetTitle('Larvol Trials');
		$pdf->SetSubject('Larvol Trials');
		$pdf->SetKeywords('Larvol Trials Product Analytics, Larvol Trials Product Analytics PDF Export');
		$pdf->SetFont('verdana', '', 6);
		$pdf->setFontSubsetting(false);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
			
		// remove default header/footer
		$pdf->setPrintHeader(false);
		//set some language-dependent strings
		$pdf->setLanguageArray($l);
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->AddPage();
		
		$font_height = 6;
		$Page_Width = 192;
		$product_Col_Width = 50;
		$Line_Height = 3.6;
		$Min_One_Liner = 4.5;
		$Tic_dimension = 1;
		$subColumn_width = 1.4;
		
		$Repo_Heading = $Report_Name.', '.$pdftitle;
		$current_StringLength = $pdf->GetStringWidth($Repo_Heading, 'verdana', '', 6);
		$pdf->MultiCell('', '', '<h3>'.$Repo_Heading.'</h3>', $border=0, $align='C', $fill=0, $ln=1, ((($Page_Width/2) - $current_StringLength))-20, '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
		$pdf->Ln(5);
		
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->setCellMargins(0, 0, 0, 0);
		
		$Main_X = $pdf->GetX();
		$Main_Y = $pdf->GetY();
		
		for($incr=0; $incr < count($rows); $incr++)
		{	
			$row = $incr;
			
			$dimensions = $pdf->getPageDimensions();
			//Height calculation depending on product name
			$rowcount = 0;
 			//work out the number of lines required
			$rowcount = $pdf->getNumLines($data_matrix[$row]['productName'].$data_matrix[$row]['product_CompnayName'].((trim($rowsTagName[$row]) != '') ? ' ['.$rowsTagName[$row].']':''), $product_Col_Width);
			if($rowcount < 1) $rowcount = 1;
 			$startY = $pdf->GetY();
			$row_height = $rowcount * $Line_Height;
			
			if($rowcount <= 1)
			$Extra_Spacing = 0;
			else
			$Extra_Spacing = ($row_height - $Line_Height) / 2;
			/// Next Row Height + Last Tick Row Height
			$Total_Height = 0;
			$Total_Height = $Tic_dimension + $row_height + $Tic_dimension + $Tic_dimension + $font_height;
			
			if (($startY + $Total_Height) + $dimensions['bm'] > ($dimensions['hk']))
			{
				//this row will cause a page break, draw the bottom border on previous row and give this a top border
				CreateLastTickBorder($pdf, $product_Col_Width, $Tic_dimension, $columns, $inner_columns, $subColumn_width, $column_interval);
				$pdf->AddPage();
			}
			
			$ln=0;
			$Main_X = $pdf->GetX();
			$Main_Y = $pdf->GetY();
			/// Bypass product column
			$Place_X = $Main_X+$product_Col_Width;
			$Place_Y = $Main_Y;
			
			if($row==1)
				$border = array('mode' => 'ext', 'TR' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			else
				$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			$pdf->MultiCell($Tic_dimension, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
			$Place_X = $Place_X+$Tic_dimension;
			$Place_Y = $Place_Y;
			for($j=0; $j < $columns; $j++)
			{
				for($k=0; $k < $inner_columns; $k++)
				{
					if($k == $inner_columns-1 && $row!=1)
					$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
					else
					$border = 0;
					if($j == $columns-1 && $k == $inner_columns-1) 
					$ln=1;
					
					$pdf->MultiCell($subColumn_width, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
					
					if($j == $columns-1 && $k == $inner_columns-1) 
					$Place_Y = $Place_Y+$Tic_dimension;
					$Place_X = $Place_X+$subColumn_width;
					
				}
			}
			
			$pdf->SetX($Main_X);
			$pdf->SetY($Place_Y);
			
			$Place_X = $pdf->GetX();
			$Place_Y = $pdf->GetY();
		
			$ln=0;
			$pdfContent = '<div align="right" style="vertical-align:top; float:none;"><a style="color:#000000; text-decoration:none;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId . $link_part . '" target="_blank" title="'. $title .'">'.$data_matrix[$row]['productName'].$data_matrix[$row]['product_CompnayName'].'</a></div>';
			$border = array('mode' => 'ext', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			$pdf->MultiCell($product_Col_Width, $row_height, $pdfContent, $border=0, $align='R', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$row_height);
			$Place_X = $Place_X + $product_Col_Width;
			if($row==1)
				$border = array('mode' => 'ext', 'TB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)), 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,255)));
			else
				$border = array('mode' => 'ext', 'B' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)), 'LT' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,255)));
			$pdf->MultiCell($Tic_dimension, $Line_Height, '', $border=0, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$row_height);
			
			$Place_X = $Place_X + $Tic_dimension;
			$Middle_Place = $Place_X;
			
			///// Part added to divide extra space formed by multiple rows of product name
			if($Extra_Spacing > 0)
			{
				$ln=0;
				$Place_X = $Middle_Place;
				$Place_Y = $Place_Y;
				for($j=0; $j < $columns; $j++)
				{
					for($k=0; $k < $inner_columns; $k++)
					{
						if($k == $inner_columns-1)
						$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
						else if ($k == 0 && $j==0)
						$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
						else
						$border = 0;
						if($j == $columns-1 && $k == $inner_columns-1) 
						$ln=1;
						
						$pdf->MultiCell($subColumn_width, $Extra_Spacing, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Extra_Spacing);
					
						if($j == $columns-1 && $k == $inner_columns-1) 
						$Place_Y = $Place_Y+$Extra_Spacing;
						
						$Place_X = $Place_X+$subColumn_width;
						
					}
				}
			}
			///// End of Part added to divide extra space formed by multiple rows of product name
			
			$Place_X = $Middle_Place;
			//// Graph starts
			if($mode == 'indlead')
			{
				$Rounded = (($data_matrix[$row]['indlead_phase_4'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_4'])) + (($data_matrix[$row]['indlead_phase_3'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_3'])) + (($data_matrix[$row]['indlead_phase_2'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_2'])) + (($data_matrix[$row]['indlead_phase_1'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_1'])) + (($data_matrix[$row]['indlead_phase_0'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_0'])) + (($data_matrix[$row]['indlead_phase_na'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_na']));
				$Actual = ($ratio * $data_matrix[$row]['indlead_phase_4']) + ($ratio * $data_matrix[$row]['indlead_phase_3']) + ($ratio * $data_matrix[$row]['indlead_phase_2']) + ($ratio * $data_matrix[$row]['indlead_phase_1']) + ($ratio * $data_matrix[$row]['indlead_phase_0'])+ ($ratio * $data_matrix[$row]['indlead_phase_na']);
				$Err = floor($Rounded - $Actual);
				$Max_ValueKey = Max_ValueKey($data_matrix[$row]['indlead_phase_4'], $data_matrix[$row]['indlead_phase_3'], $data_matrix[$row]['indlead_phase_2'], $data_matrix[$row]['indlead_phase_1'], $data_matrix[$row]['indlead_phase_0'], $data_matrix[$row]['indlead_phase_na']);
					
				$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['indlead']);
				$phase_space = 0;
				if($data_matrix[$row]['indlead_phase_4'] > 0)
				{
					$border = setStyleforPDFExport('4', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['indlead_phase_4']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_4']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 0 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['indlead_phase_4'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#FF0000; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=4'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['indlead_phase_3'] > 0)
				{
					$border = setStyleforPDFExport('3', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['indlead_phase_3']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_3']);
					else
						$Mini_Bar_Width = 1;
						
					if($Max_ValueKey == 1 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['indlead_phase_3'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#FF9900; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=3'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['indlead_phase_2'] > 0)
				{
					$border = setStyleforPDFExport('2', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['indlead_phase_2']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_2']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 2 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['indlead_phase_2'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:middle; float:none;"><a style="color:#FFFF00; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=2'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['indlead_phase_1'] > 0)
				{
					$border = setStyleforPDFExport('1', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['indlead_phase_1']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_1']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 3 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['indlead_phase_1'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#99CC00; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=1'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['indlead_phase_0'] > 0)
				{
					$border = setStyleforPDFExport('0', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['indlead_phase_0']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_0']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 4 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['indlead_phase_0'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#00CCFF; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=0'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['indlead_phase_na'] > 0)
				{
					$border = setStyleforPDFExport('na', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['indlead_phase_na']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['indlead_phase_na']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 5 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['indlead_phase_na'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#BFBFBF; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=na'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
			} 
			else if($mode == 'active')
			{
				$Rounded = (($data_matrix[$row]['active_phase_4'] > 0 && round($ratio * $data_matrix[$row]['active_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_4'])) + (($data_matrix[$row]['active_phase_3'] > 0 && round($ratio * $data_matrix[$row]['active_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_3'])) + (($data_matrix[$row]['active_phase_2'] > 0 && round($ratio * $data_matrix[$row]['active_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_2'])) + (($data_matrix[$row]['active_phase_1'] > 0 && round($ratio * $data_matrix[$row]['active_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_1'])) + (($data_matrix[$row]['active_phase_0'] > 0 && round($ratio * $data_matrix[$row]['active_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_0'])) + (($data_matrix[$row]['active_phase_na'] > 0 && round($ratio * $data_matrix[$row]['active_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_na']));
				$Actual = ($ratio * $data_matrix[$row]['active_phase_4']) + ($ratio * $data_matrix[$row]['active_phase_3']) + ($ratio * $data_matrix[$row]['active_phase_2']) + ($ratio * $data_matrix[$row]['active_phase_1']) + ($ratio * $data_matrix[$row]['active_phase_0'])+ ($ratio * $data_matrix[$row]['active_phase_na']);
				$Err = floor($Rounded - $Actual);
				$Max_ValueKey = Max_ValueKey($data_matrix[$row]['active_phase_4'], $data_matrix[$row]['active_phase_3'], $data_matrix[$row]['active_phase_2'], $data_matrix[$row]['active_phase_1'], $data_matrix[$row]['active_phase_0'], $data_matrix[$row]['active_phase_na']);
					
				$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['active']);
				$phase_space = 0;
				if($data_matrix[$row]['active_phase_4'] > 0)
				{
					$border = setStyleforPDFExport('4', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['active_phase_4']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_4']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 0 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['active_phase_4'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#FF0000; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=4'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['active_phase_3'] > 0)
				{
					$border = setStyleforPDFExport('3', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['active_phase_3']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_3']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 1 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['active_phase_3'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#FF9900; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=3'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['active_phase_2'] > 0)
				{
					$border = setStyleforPDFExport('2', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['active_phase_2']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_2']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 2 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['active_phase_2'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:middle; float:none;"><a style="color:#FFFF00; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=2'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['active_phase_1'] > 0)
				{
					$border = setStyleforPDFExport('1', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['active_phase_1']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_1']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 3 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['active_phase_1'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#99CC00; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=1'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['active_phase_0'] > 0)
				{
					$border = setStyleforPDFExport('0', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['active_phase_0']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_0']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 4 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['active_phase_0'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#00CCFF; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=0'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['active_phase_na'] > 0)
				{
					$border = setStyleforPDFExport('na', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['active_phase_na']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['active_phase_na']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 5 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['active_phase_na'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#BFBFBF; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=na'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
			} 
			else if($mode == 'total')
			{
				$Rounded = (($data_matrix[$row]['total_phase_4'] > 0 && round($ratio * $data_matrix[$row]['total_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_4'])) + (($data_matrix[$row]['total_phase_3'] > 0 && round($ratio * $data_matrix[$row]['total_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_3'])) + (($data_matrix[$row]['total_phase_2'] > 0 && round($ratio * $data_matrix[$row]['total_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_2'])) + (($data_matrix[$row]['total_phase_1'] > 0 && round($ratio * $data_matrix[$row]['total_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_1'])) + (($data_matrix[$row]['total_phase_0'] > 0 && round($ratio * $data_matrix[$row]['total_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_0'])) + (($data_matrix[$row]['total_phase_na'] > 0 && round($ratio * $data_matrix[$row]['total_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_na']));
				$Actual = ($ratio * $data_matrix[$row]['total_phase_4']) + ($ratio * $data_matrix[$row]['total_phase_3']) + ($ratio * $data_matrix[$row]['total_phase_2']) + ($ratio * $data_matrix[$row]['total_phase_1']) + ($ratio * $data_matrix[$row]['total_phase_0'])+ ($ratio * $data_matrix[$row]['total_phase_na']);
				$Err = floor($Rounded - $Actual);
				$Max_ValueKey = Max_ValueKey($data_matrix[$row]['total_phase_4'], $data_matrix[$row]['total_phase_3'], $data_matrix[$row]['total_phase_2'], $data_matrix[$row]['total_phase_1'], $data_matrix[$row]['total_phase_0'], $data_matrix[$row]['total_phase_na']);
					
				$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['total']);
				$phase_space = 0;
				if($data_matrix[$row]['total_phase_4'] > 0)
				{
					$border = setStyleforPDFExport('4', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['total_phase_4']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_4']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 0 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['total_phase_4'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#FF0000; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=4'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['total_phase_3'] > 0)
				{
					$border = setStyleforPDFExport('3', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['total_phase_3']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_3']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 1 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['total_phase_3'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#FF9900; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=3'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['total_phase_2'] > 0)
				{
					$border = setStyleforPDFExport('2', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['total_phase_2']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_2']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 2 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['total_phase_2'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:middle; float:none;"><a style="color:#FFFF00; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=2'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['total_phase_1'] > 0)
				{
					$border = setStyleforPDFExport('1', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['total_phase_1']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_1']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 3 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['total_phase_1'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#99CC00; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=1'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['total_phase_0'] > 0)
				{
					$border = setStyleforPDFExport('0', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['total_phase_0']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_0']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 4 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['total_phase_0'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#00CCFF; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=0'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
				
				if($data_matrix[$row]['total_phase_na'] > 0)
				{
					$border = setStyleforPDFExport('na', $pdf);
					$Width = $subColumn_width;
					
					if(round($ratio * $data_matrix[$row]['total_phase_na']) > 0)
						$Mini_Bar_Width = round($ratio * $data_matrix[$row]['total_phase_na']);
					else
						$Mini_Bar_Width = 1;
					
					if($Max_ValueKey == 5 && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
					$Mini_Bar_Width = $Mini_Bar_Width - $Err;
					
					if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
						$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
					else
						$Mini_Bar_Width = $Total_Bar_Width;
					
					$phase_space =  $phase_space + $Mini_Bar_Width;
					
					$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['total_phase_na'], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
					$m=0;
					while($m < $Mini_Bar_Width)
					{
						$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#BFBFBF; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?p=' . $data_matrix[$row]['productIds'] . '&a=' . $areaId .'&phase=na'. $link_part . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
						$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
						$Place_X = $Place_X + $Width;
						$m++;
					}
				}
			}
			
			$total_cols = $inner_columns * $columns;
			$remain_span = $total_cols - $phase_space;
		
			if($remain_span > 0)
			{
				$aq_sp = 0;
				while($aq_sp < $phase_space)
				$aq_sp = $aq_sp + $inner_columns;
				
				$extra_sp = $aq_sp - $phase_space;
				if($extra_sp > 0)
				{
					$Width = $extra_sp * $subColumn_width;
					$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
					$pdf->MultiCell($Width, $Line_Height, '', $border=0, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
					$Place_X = $Place_X + $Width;
				}
				
				$remain_span = $remain_span - $extra_sp;
				while($remain_span > 0)
				{
					$Width = $inner_columns * $subColumn_width;
					$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
					$pdf->MultiCell($Width, $Line_Height, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
					$Place_X = $Place_X + $Width;
					$remain_span = $remain_span - $inner_columns;
				//	if($remain_span <= $inner_columns )
				//	$ln=1;
				}
			} // End of remain span
			
			///EXTRA CELL FOR MAKING LINEBREAK
			$ln=1;
			$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			$pdf->MultiCell(1, $Line_Height, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
			$Place_Y = $Place_Y + $Line_Height;
			///// Part added to divide extra space formed by multiple rows of product name
			if($Extra_Spacing > 0)
			{
				$ln=0;
				$Place_X = $Middle_Place;
				$Place_Y = $Place_Y;
				for($j=0; $j < $columns; $j++)
				{
					for($k=0; $k < $inner_columns; $k++)
					{
						if($k == $inner_columns-1)
						$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
						else if ($k == 0 && $j==0)
						$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
						else
						$border = 0;
						if($j == $columns-1 && $k == $inner_columns-1) 
						$ln=1;
						
						$pdf->MultiCell($subColumn_width, $Extra_Spacing, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Extra_Spacing);
					
						if($j == $columns-1 && $k == $inner_columns-1) 
						$Place_Y = $Place_Y+$Extra_Spacing;
						
						$Place_X = $Place_X+$subColumn_width;
						
					}
				}
			}
			///// End of Part added to divide extra space formed by multiple rows of product name
			
			$ln=0;
			$Place_X = $Main_X;
			$Place_Y = $Place_Y;
			/// Bypass product column
			$Place_X =$Place_X+$product_Col_Width;
			$Place_Y = $Place_Y;
			if($row)
			$border = array('mode' => 'ext', 'RB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			$pdf->MultiCell($Tic_dimension, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
			$Place_X = $Place_X+$Tic_dimension;
			$Place_Y = $Place_Y;
			for($j=0; $j < $columns; $j++)
			{
				for($k=0; $k < $inner_columns; $k++)
				{
					if($k == $inner_columns-1)
					$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
					else
					$border = 0;
					if($j == $columns-1 && $k == $inner_columns-1) 
					$ln=1;
					
					$pdf->MultiCell($subColumn_width, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
					
					if($j == $columns-1 && $k == $inner_columns-1) 
					$Place_Y = $Place_Y+$Tic_dimension;
					
					$Place_X = $Place_X+$subColumn_width;
					
				}
			}
			
			$pdf->SetX($Main_X);
			$pdf->SetY($Place_Y);
		}
		
		
		
		CreateLastTickBorder($pdf, $product_Col_Width, $Tic_dimension, $columns, $inner_columns, $subColumn_width, $column_interval);
			
		ob_end_clean();
		//Close and output PDF document
		$pdf->Output('Larvol_'. substr($Report_Name,0,20) .'_PDF_Report_'. date("Y-m-d_H.i.s") .'.pdf', 'D');
	}	/// End of PDF Function
	
}

function getColspanforExcelExport($cell, $inc)
{
	for($i = 1; $i < $inc; $i++)
	{
		$cell++;
	}
	return $cell;
}

function getBGColorforExcelExport($phase)
{
	if($phase == '0')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => '00CCFF'),
									'endcolor'   => array('rgb' => '00CCFF'))
						));
	}
	else if($phase == '1')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => '99CC00'),
									'endcolor'   => array('rgb' => '99CC00'))
						));
	}
	else if($phase == '2')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => 'FFFF00'),
									'endcolor'   => array('rgb' => 'FFFF00'))
						));
	}
	else if($phase == '3')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => 'FF9900'),
									'endcolor'   => array('rgb' => 'FF9900'))
						));
	}
	else if($phase == '4')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => 'FF0000'),
									'endcolor'   => array('rgb' => 'FF0000'))
						));
	}
	else if($phase == 'na')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => 'BFBFBF'),
									'endcolor'   => array('rgb' => 'BFBFBF'))
						));
	}
	else
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => 'BFBFBF'),
									'endcolor'   => array('rgb' => 'BFBFBF'))
						));
	}
	
	return $bgColor;
}

function  setStyleforPDFExport($phase, &$pdf)
{
	if($phase == '0')
	{
		$pdf->SetFillColor(0,204,255);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,204,255)));
	}
	else if($phase == '1')
	{
		$pdf->SetFillColor(153,204,0);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(153,204,0)));
	}
	else if($phase == '2')
	{
		$pdf->SetFillColor(255,255,0);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,0)));
	}
	else if($phase == '3')
	{
		$pdf->SetFillColor(255,153,0);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,153,0)));
	}
	else if($phase == '4')
	{
		$pdf->SetFillColor(255,0,0);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,0,0)));
	}
	else if($phase == 'na')
	{
		$pdf->SetFillColor(191,191,191);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(191,191,191)));
	}
	else
	{
		$pdf->SetFillColor(191,191,191);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(191,191,191)));
	}
	
	return $border;
}

function CreateLastTickBorder(&$pdf, $product_Col_Width, $Tic_dimension, $columns, $inner_columns, $subColumn_width, $column_interval)
{
	$ln=0;
	$Main_X = $pdf->GetX();
	$Main_Y = $pdf->GetY();
	/// Bypass product column
	$Place_X = $Main_X+$product_Col_Width;
	$Place_Y = $Main_Y;
	/// SET NOT REQUIRED BORDERS TO WHITE COLORS THAT WILL MAKE TABLE COMPACT OTHERWISE HEIGHT/WIDTH ISSUE HAPPENS
	$border = array('mode' => 'ext', 'RT' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
	$pdf->MultiCell($Tic_dimension, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=0);
	$Place_X = $Main_X+$Tic_dimension;
	$Place_Y = $Main_Y;
	for($j=0; $j < $columns; $j++)
	{
		$Width = $inner_columns * $subColumn_width;
		$border = array('mode' => 'ext', 'RT' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
		$pdf->MultiCell($Width, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, '', '', $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
		$Place_X = $Main_X+$Width;
		
		if($j == $columns-1) 
		$Place_Y = $Place_Y+$Tic_dimension;
	}
	$pdf->SetX($Main_X);
	$pdf->SetY($Place_Y);
	
	$ln=0;
	$Main_X = $pdf->GetX();
	$Main_Y = $pdf->GetY();
	/// Bypass product column
	$Place_X = $Main_X+$product_Col_Width;
	$Place_Y = $Main_Y;
	/// SET NOT REQUIRED BORDERS TO WHITE COLORS THAT WILL MAKE TABLE COMPACT OTHERWISE HEIGHT/WIDTH ISSUE HAPPENS
	$border = 0;
	$pdf->MultiCell($Tic_dimension, $Tic_dimension, '', $border, $align='R', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=0);
	$Place_X = $Main_X+$Tic_dimension;
	$Place_Y = $Main_Y;
	for($j=0; $j < $columns; $j++)
	{
		if($j==0)
		$Width = ($inner_columns * $subColumn_width) + $subColumn_width;
		else
		$Width = $inner_columns * $subColumn_width;
		$border = 0;
		$pdf->MultiCell($Width, $Tic_dimension, (($j+1) * $column_interval), $border, $align='R', $fill=0, $ln, '', '', $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=0);
		$Place_X = $Main_X+$Width;
			
		if($j == $columns-1) 
		$Place_Y = $Place_Y+$Tic_dimension;
	}
	$pdf->SetX($Main_X);
	$pdf->SetY($Place_Y);
}

function Max_ValueKey($val0, $val1, $val2, $val3, $val4, $val5)
{
$key = 0;
$max = $val[0];
$i = 1;
	if($max < $val1)
	{
		$max = $val1;
		$key = 1;
	}
	
	if($max < $val2)
	{
		$max = $val2;
		$key = 2;
	}
	
	if($max < $val3)
	{
		$max = $val3;
		$key = 3;
	}
	
	if($max < $val4)
	{
		$max = $val4;
		$key = 4;
	}
	
	if($max < $val5)
	{
		$max = $val5;
		$key = 5;
	}
	
	return $key;
}

function sortTwoDimensionArrayByKey($arr, $arrKey, $sortOrder=SORT_DESC)
{
	foreach ($arr as $key => $row)
	{
		$key_arr[$key] = $row[$arrKey];
	}
	array_multisort($key_arr, $sortOrder, $arr);
	return $arr;
}
?>