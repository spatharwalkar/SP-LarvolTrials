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
	if(isset($areaId) && $areaId != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
	{
		$cell_query = 'SELECT * FROM rpt_masterhm_cells WHERE `product`=' . $productIds[$row] . ' AND `area`='. $areaId .'';
		$cell_res = mysql_query($cell_query) or die(mysql_error());
		$cell_data = mysql_fetch_array($cell_res);
			
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
	
	var i=1;
	for(i=1;i<=limit;i++)
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
				. '<td class="bottom right">'
				. '<div style="float: left; margin-left: 15px; margin-top: 11px; vertical-align:bottom;" id="chromemenu"><a rel="dropmenu"><span style="padding:2px; padding-right:4px; border:1px solid; color:#000000; background-position:left center; background-repeat:no-repeat; background-image:url(\'./images/save.png\'); cursor:pointer; ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Export</b></span></a></div>'
				. '</td>'
				. '</tr>'
				. '</table>';
				
$htmlContent  .= '<div id="dropmenu" class="dropmenudiv" style="width: 310px;">'
				.'<div style="height:100px; padding:6px;"><div class="downldbox"><div class="newtext">Download options</div>'
				. '<input type="hidden" name="id" id="id" value="' . $id . '" />'
				. '<ul><li><label>Which format: </label></li>'
				. '<li><select id="dwformat" name="dwformat" size="2" style="height:40px">'
				. '<option value="exceldown" selected="selected">Excel</option>'
				//. '<option value="pdfdown">PDF</option>'
				. '<option value="tsvdown" selected="selected">TSV</option>'
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

foreach($rows as $row => $rval)
{
	$htmlContent .= '<tr class="side_tick_height"><th class="prod_col side_tick_height">&nbsp;</th><th class="'. (($row == 1) ? 'graph_top':'' ) .' graph_right last_tick_width">&nbsp;</th>';
	for($j=0; $j < $columns; $j++)
	{
		$htmlContent .= '<th width="'.$column_width.'px" colspan="'.$inner_columns.'" class="graph_right">&nbsp;</th>';
	}
	$htmlContent .= '</tr>';
	
	////// Color Graph - Bar Starts
	
	//// Code for Indlead
	$htmlContent .= '<tr id="indlead_Graph_Row_'.$row.'"><th align="right" class="prod_col"><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&hm=' . $id . '" target="_blank" style="text-decoration:underline;">'.$rval.$rowsCompanyName[$row].'</a></th><th class="graph_right last_tick_width">&nbsp;</th>';
	
	$total_cols = $inner_columns * $columns;
	
	if(ceil($ratio * $data_matrix[$row]['indlead_phase_4']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['indlead_phase_4']).'" class="side_tick_height graph_red" title="'.$data_matrix[$row]['indlead_phase_4'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=4&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['indlead_phase_3']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['indlead_phase_3']).'" class="side_tick_height graph_orange" title="'.$data_matrix[$row]['indlead_phase_3'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=3&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['indlead_phase_2']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['indlead_phase_2']).'" class="side_tick_height graph_yellow" title="'.$data_matrix[$row]['indlead_phase_2'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=2&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['indlead_phase_1']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['indlead_phase_1']).'" class="side_tick_height graph_green" title="'.$data_matrix[$row]['indlead_phase_1'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=1&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['indlead_phase_0']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['indlead_phase_0']).'" class="side_tick_height graph_blue" title="'.$data_matrix[$row]['indlead_phase_0'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=0&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['indlead_phase_na']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['indlead_phase_na']).'" class="side_tick_height graph_gray" title="'.$data_matrix[$row]['indlead_phase_na'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=na&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	$phase_space = (ceil($ratio * $data_matrix[$row]['indlead_phase_4']) + ceil($ratio * $data_matrix[$row]['indlead_phase_3']) + ceil($ratio * $data_matrix[$row]['indlead_phase_2']) + ceil($ratio * $data_matrix[$row]['indlead_phase_1']) + ceil($ratio * $data_matrix[$row]['indlead_phase_0']) + ceil($ratio * $data_matrix[$row]['indlead_phase_na']));
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
	$htmlContent .= '<tr style="display:none;" id="active_Graph_Row_'.$row.'"><th align="right" class="prod_col"><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&hm=' . $id . '" target="_blank" style="text-decoration:underline;">'.$rval.$rowsCompanyName[$row].'</a></th><th class="graph_right last_tick_width">&nbsp;</th>';
	
	$total_cols = $inner_columns * $columns;
	
	if(ceil($ratio * $data_matrix[$row]['active_phase_4']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['active_phase_4']).'" class="side_tick_height graph_red" title="'.$data_matrix[$row]['active_phase_4'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=4&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['active_phase_3']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['active_phase_3']).'" class="side_tick_height graph_orange" title="'.$data_matrix[$row]['active_phase_3'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=3&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['active_phase_2']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['active_phase_2']).'" class="side_tick_height graph_yellow" title="'.$data_matrix[$row]['active_phase_2'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=2&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['active_phase_1']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['active_phase_1']).'" class="side_tick_height graph_green" title="'.$data_matrix[$row]['active_phase_1'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=1&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['active_phase_0']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['active_phase_0']).'" class="side_tick_height graph_blue" title="'.$data_matrix[$row]['active_phase_0'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=0&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['active_phase_na']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['active_phase_na']).'" class="side_tick_height graph_gray" title="'.$data_matrix[$row]['active_phase_na'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=na&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	$phase_space = (ceil($ratio * $data_matrix[$row]['active_phase_4']) + ceil($ratio * $data_matrix[$row]['active_phase_3']) + ceil($ratio * $data_matrix[$row]['active_phase_2']) + ceil($ratio * $data_matrix[$row]['active_phase_1']) + ceil($ratio * $data_matrix[$row]['active_phase_0']) + ceil($ratio * $data_matrix[$row]['active_phase_na']));
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
	$htmlContent .= '<tr style="display:none;" id="total_Graph_Row_'.$row.'"><th align="right" class="prod_col"><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&hm=' . $id . '" target="_blank" style="text-decoration:underline;">'.$rval.$rowsCompanyName[$row].'</a></th><th class="graph_right last_tick_width">&nbsp;</th>';
	
	$total_cols = $inner_columns * $columns;
	
	if(ceil($ratio * $data_matrix[$row]['total_phase_4']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['total_phase_4']).'" class="side_tick_height graph_red" title="'.$data_matrix[$row]['total_phase_4'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=4&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['total_phase_3'])> 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['total_phase_3']).'" class="side_tick_height graph_orange" title="'.$data_matrix[$row]['total_phase_3'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=3&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['total_phase_2']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['total_phase_2']).'" class="side_tick_height graph_yellow" title="'.$data_matrix[$row]['total_phase_2'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=2&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['total_phase_1']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['total_phase_1']).'" class="side_tick_height graph_green" title="'.$data_matrix[$row]['total_phase_1'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=1&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['total_phase_0']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['total_phase_0']).'" class="side_tick_height graph_blue" title="'.$data_matrix[$row]['total_phase_0'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=0&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if(ceil($ratio * $data_matrix[$row]['total_phase_na']) > 0)
	$htmlContent .= '<th colspan="'.ceil($ratio * $data_matrix[$row]['total_phase_na']).'" class="side_tick_height graph_gray" title="'.$data_matrix[$row]['total_phase_na'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=na&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	$phase_space = (ceil($ratio * $data_matrix[$row]['total_phase_4']) + ceil($ratio * $data_matrix[$row]['total_phase_3']) + ceil($ratio * $data_matrix[$row]['total_phase_2']) + ceil($ratio * $data_matrix[$row]['total_phase_1']) + ceil($ratio * $data_matrix[$row]['total_phase_0']) + ceil($ratio * $data_matrix[$row]['total_phase_na']));
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
		if(isset($areaId) && $areaId != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
		{
			$cell_query = 'SELECT * FROM rpt_masterhm_cells WHERE `product`=' . $productIds[$row] . ' AND `area`='. $areaId .'';
			$cell_res = mysql_query($cell_query) or die(mysql_error());
			$cell_data = mysql_fetch_array($cell_res);
				
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
		
		foreach($rows as $row => $rval)
		{
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
			if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaId))
			{
				/// Product Column
				$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
				$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
				
				$cell = $Prod_Col . $Excel_HMCounter;
				$objPHPExcel->getActiveSheet()->SetCellValue($cell, $rval.$rowsCompanyName[$row]);
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId .$link_part); 
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
					if(ceil($ratio * $data_matrix[$row]['indlead_phase_4']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['indlead_phase_4']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('4'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=4' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_4']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['indlead_phase_3']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['indlead_phase_3']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('3'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=3' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_3']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['indlead_phase_2']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['indlead_phase_2']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('2'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=2' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_2']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['indlead_phase_1']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['indlead_phase_1']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('1'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=1' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_1']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['indlead_phase_0']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['indlead_phase_0']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('0'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=0' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_0']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['indlead_phase_na']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['indlead_phase_na']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('na'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=na' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['indlead_phase_na']);
						$from = $to;
						$from++;
					}
					
					$phase_space = (ceil($ratio * $data_matrix[$row]['indlead_phase_4']) + ceil($ratio * $data_matrix[$row]['indlead_phase_3']) + ceil($ratio * $data_matrix[$row]['indlead_phase_2']) + ceil($ratio * $data_matrix[$row]['indlead_phase_1']) + ceil($ratio * $data_matrix[$row]['indlead_phase_0']) + ceil($ratio * $data_matrix[$row]['indlead_phase_na']));
				}
				else if ($mode == 'active')
				{
					if(ceil($ratio * $data_matrix[$row]['active_phase_4']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['active_phase_4']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('4'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=4' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_4']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['active_phase_3']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['active_phase_3']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('3'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=3' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_3']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['active_phase_2']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['active_phase_2']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('2'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=2' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_2']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['active_phase_1']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['active_phase_1']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('1'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=1' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_1']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['active_phase_0']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['active_phase_0']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('0'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=0' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_0']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['active_phase_na']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['active_phase_na']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('na'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=na' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['active_phase_na']);
						$from = $to;
						$from++;
					}
					
					$phase_space = (ceil($ratio * $data_matrix[$row]['active_phase_4']) + ceil($ratio * $data_matrix[$row]['active_phase_3']) + ceil($ratio * $data_matrix[$row]['active_phase_2']) + ceil($ratio * $data_matrix[$row]['active_phase_1']) + ceil($ratio * $data_matrix[$row]['active_phase_0']) + ceil($ratio * $data_matrix[$row]['active_phase_na']));
				}
				else
				{
					if(ceil($ratio * $data_matrix[$row]['total_phase_4']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['total_phase_4']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('4'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=4' . $link_part);  
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_4']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['total_phase_3']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['total_phase_3']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('3'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=3' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_3']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['total_phase_2']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['total_phase_2']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('2'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=2' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_2']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['total_phase_1']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['total_phase_1']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('1'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=1' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_1']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['total_phase_0']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['total_phase_0']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('0'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=0' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_0']);
						$from = $to;
						$from++;
					}
					
					if(ceil($ratio * $data_matrix[$row]['total_phase_na']) > 0)
					{
						$to = getColspanforExcelExport($from, ceil($ratio * $data_matrix[$row]['total_phase_na']));
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport('na'));
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&phase=na' . $link_part); 
						$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($data_matrix[$row]['total_phase_na']);
						$from = $to;
						$from++;
					}
					
					$phase_space = (ceil($ratio * $data_matrix[$row]['total_phase_4']) + ceil($ratio * $data_matrix[$row]['total_phase_3']) + ceil($ratio * $data_matrix[$row]['total_phase_2']) + ceil($ratio * $data_matrix[$row]['total_phase_1']) + ceil($ratio * $data_matrix[$row]['total_phase_0']) + ceil($ratio * $data_matrix[$row]['total_phase_na']));	
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
			$to = getColspanforExcelExport($from, (($j==0)? $inner_columns+1 : $inner_columns));
			$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
			$objPHPExcel->getActiveSheet()->SetCellValue($from . $Excel_HMCounter, (($j+1) * $column_interval));
			$from = $to;
			$from++;
		}
		//$objPHPExcel->getActiveSheet()->getRowDimension($Excel_HMCounter)->setRowHeight(5);
			
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
		
		foreach($rows as $row => $rval)
		{
			if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaId))
			{
				$TSV_data .= $rval.$rowsCompanyName[$row] ." \t ";
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
		$pdf->SetKeywords('Larvol Trials Master Heatmap, Larvol Trials Master Heatmap PDF Export');
		$pdf->SetFont('verdana', '', 8);
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
		
		$Page_Width = 192;
		$product_Col_Width = 25;
		$Line_Height = 3.96;
		$Min_One_Liner = 4.5;
		
		foreach($rows as $row => $rval)
		{
			//Height calculation depending on product name
			$rowcount = 0;
 			//work out the number of lines required
			$rowcount = $pdf->getNumLines($rval.$rowsCompanyName[$row].((trim($rowsTagName[$row]) != '') ? ' ['.$rowsTagName[$row].']':''), $product_Col_Width);
			if($rowcount < 1) $rowcount = 1;
 			$startY = $pdf->GetY();
			$row_height = $rowcount * $Line_Height;
			$ln=1;
			$pdf->MultiCell($product_Col_Width, $row_height, '', $border=0, $align='C', $fill=1, $ln, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
		}
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
?>