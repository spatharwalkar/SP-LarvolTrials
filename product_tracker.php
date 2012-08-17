<?php
session_start();
//unset($_SESSION['OHM_array']);
require_once('db.php');
ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
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

function phase_colors($phase_value)
{
	$phase_color = array();
	if($phase_value == 'N/A' || $phase_value == '' || $phase_value === NULL)
	{
		$phase_color[0]='background-color:#BFBFBF;';
		$phase_color[1]='BFBFBF';
	}
	else if($phase_value == '0')
	{
		$phase_color[0]='background-color:#00CCFF;';
		$phase_color[1]='00CCFF';
	}
	else if($phase_value == '1' || $phase_value == '0/1' || $phase_value == '1a' 
	|| $phase_value == '1b' || $phase_value == '1a/1b' || $phase_value == '1c')
	{
		$phase_color[0]='background-color:#99CC00;';
		$phase_color[1]='99CC00';
	}
	else if($phase_value == '2' || $phase_value == '1/2' || $phase_value == '1b/2' 
	|| $phase_value == '1b/2a' || $phase_value == '2a' || $phase_value == '2a/2b' 
	|| $phase_value == '2a/b' || $phase_value == '2b')
	{
		$phase_color[0]='background-color:#FFFF00;';
		$phase_color[1]='FFFF00';
	}
	else if($phase_value == '3' || $phase_value == '2/3' || $phase_value == '2b/3' 
	|| $phase_value == '3a' || $phase_value == '3b')
	{
		$phase_color[0]='background-color:#FF9900;';
		$phase_color[1]='FF9900';
	}
	else if($phase_value == '4' || $phase_value == '3/4' || $phase_value == '3b/4')
	{
		$phase_color[0]='background-color:#FF0000;';
		$phase_color[1]='FF0000';	
	}
	return $phase_color;
}

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
height:6px;
line-height:6px;
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
	var view_type = document.getElementById('view_type');
	
	var i=1;
	for(i=1;i<=limit;i++)
	{
		if(view_type.value == 'active')
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
		else if(view_type.value == 'total')
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

$online_HMCounter=0;

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
				. '<table width="264px" border="0" cellspacing="0" cellpadding="0" class="controls" align="center">'
				. '<tr><th align="center">View mode</th><th align="center" class="right">Actions</th></tr>'
				. '<tr>'
				. '<td class="bottom"><p style="margin-top:8px;margin-right:5px;"><select id="view_type" name="view_type" onchange="change_view();">'
				. '<option value="indlead" selected="selected">Active industry trials</option>'
				. '<option value="active">Active trials</option>'
				. '<option value="total">All trials</option></select></p></td>'
				. '<td class="bottom right">'
				. '<div style="float: left; margin-left: 15px; margin-top: 11px; vertical-align:bottom;" id="chromemenu"><a rel="dropmenu"><span style="padding:2px; padding-right:4px; border:1px solid; color:#000000; background-position:left center; background-repeat:no-repeat; background-image:url(\'./images/save.png\'); cursor:pointer; ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Export</b></span></a></div>'
				. '</td>'
				. '</tr>'
				. '</table>';
				
$htmlContent  .= '<div id="dropmenu" class="dropmenudiv" style="width: 310px;">'
				.'<form action="product_tracker.php" method="post">'
				.'<div style="height:150px; padding:6px;"><div class="downldbox"><div class="newtext">Download options</div>'
				. '<input type="hidden" name="id" id="id" value="' . $id . '" />'
				. '<ul><li><label>Which format: </label></li>'
				. '<li><select id="dwformat" name="dwformat" size="2" style="height:40px">'
				//. '<option value="exceldown" selected="selected">Excel</option>'
				//. '<option value="pdfdown">PDF</option>'
				. '</select></li>'
				. '<li><label>Counts display: </label></li>'
				. '<li><select id="dwcount" name="dwcount" size="3" style="height:54px">'
				. '<option value="indlead" selected="selected">Active industry trials</option>'
				. '<option value="active">Active trials</option>'
				. '<option value="total">All trials</option>'
				. '</select></li></ul>'
				. '<input type="submit" name="download" title="Download" value="Download file" style="margin-left:8px;"  />'
				. '</div></div>'
				. '</form>'
				. '</div><script type="text/javascript">cssdropdown.startchrome("chromemenu");</script>';
						
$htmlContent .= '<div align="center" style="padding-left:10px; padding-right:15px; padding-top:20px; padding-bottom:20px;"><table align="center" style="height:100%; vertical-align:top;" cellpadding="0" cellspacing="0">'
			    . '<thead>';

//$rows = array_reverse($rows, true);

$htmlContent .= '<tr class="side_tick_height"><th class="prod_col">&nbsp;</th><th class="last_tick_width">&nbsp;</th>';
for($j=0; $j < $columns; $j++)
{
	for($k=0; $k < $inner_columns; $k++)
	$htmlContent .= '<th width="'.$inner_width .'px" colspan="1">&nbsp;</th>';
}
$htmlContent .= '</tr>';

foreach($rows as $row => $rval)
{
	$htmlContent .= '<tr class="side_tick_height"><th class="prod_col side_tick_height">&nbsp;</th><th class="'. (($max_row['num'] == $row) ? 'graph_top':'' ) .' graph_right last_tick_width">&nbsp;</th>';
	for($j=0; $j < $columns; $j++)
	{
		$htmlContent .= '<th width="'.$column_width.'px" colspan="'.$inner_columns.'" class="graph_right">&nbsp;</th>';
	}
	$htmlContent .= '</tr>';
	
	////// Color Graph - Bar Starts
	
	//// Code for Indlead
	$htmlContent .= '<tr id="indlead_Graph_Row_'.$row.'"><th align="right" class="prod_col"><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&hm=' . $id . '" target="_blank" style="text-decoration:underline;">'.$rval.'</a></th><th class="graph_right last_tick_width">&nbsp;</th>';
	
	$total_cols = $inner_columns * $columns;
	
	$total_colspan = $original_max_count * $ratio;
	
	if($data_matrix[$row]['indlead_phase_4'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['indlead_phase_4']).'" class="side_tick_height graph_red" title="'.$data_matrix[$row]['indlead_phase_4'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=4&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['indlead_phase_3'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['indlead_phase_3']).'" class="side_tick_height graph_orange" title="'.$data_matrix[$row]['indlead_phase_3'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=3&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['indlead_phase_2'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['indlead_phase_2']).'" class="side_tick_height graph_yellow" title="'.$data_matrix[$row]['indlead_phase_2'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=2&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['indlead_phase_1'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['indlead_phase_1']).'" class="side_tick_height graph_green" title="'.$data_matrix[$row]['indlead_phase_1'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=1&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['indlead_phase_0'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['indlead_phase_0']).'" class="side_tick_height graph_blue" title="'.$data_matrix[$row]['indlead_phase_0'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=0&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['indlead_phase_na'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['indlead_phase_na']).'" class="side_tick_height graph_gray" title="'.$data_matrix[$row]['indlead_phase_na'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&itype=0&phase=na&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	$phase_space = (round($ratio * $data_matrix[$row]['indlead_phase_4']) + round($ratio * $data_matrix[$row]['indlead_phase_3']) + round($ratio * $data_matrix[$row]['indlead_phase_2']) + round($ratio * $data_matrix[$row]['indlead_phase_1']) + round($ratio * $data_matrix[$row]['indlead_phase_0']) + round($ratio * $data_matrix[$row]['indlead_phase_na']));
	$remain_span = $total_cols - $phase_space;
	
	if($remain_span > 0)
	{
		$aq_sp = 0;
		while($aq_sp < $remain_span)
		$aq_sp = $aq_sp + $inner_columns;
		
		$extra_sp = $aq_sp - $remain_span;
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
	$htmlContent .= '<tr style="display:none;" id="active_Graph_Row_'.$row.'"><th align="right" class="prod_col"><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&hm=' . $id . '" target="_blank" style="text-decoration:underline;">'.$rval.'</a></th><th class="graph_right last_tick_width">&nbsp;</th>';
	
	$total_cols = $inner_columns * $columns;
	
	$total_colspan = $original_max_count * $ratio;
	
	if($data_matrix[$row]['active_phase_4'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['active_phase_4']).'" class="side_tick_height graph_red" title="'.$data_matrix[$row]['active_phase_4'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=4&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['active_phase_3'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['active_phase_3']).'" class="side_tick_height graph_orange" title="'.$data_matrix[$row]['active_phase_3'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=3&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['active_phase_2'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['active_phase_2']).'" class="side_tick_height graph_yellow" title="'.$data_matrix[$row]['active_phase_2'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=2&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['active_phase_1'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['active_phase_1']).'" class="side_tick_height graph_green" title="'.$data_matrix[$row]['active_phase_1'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=1&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['active_phase_0'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['active_phase_0']).'" class="side_tick_height graph_blue" title="'.$data_matrix[$row]['active_phase_0'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=0&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['active_phase_na'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['active_phase_na']).'" class="side_tick_height graph_gray" title="'.$data_matrix[$row]['active_phase_na'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=1&phase=na&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	$phase_space = (round($ratio * $data_matrix[$row]['active_phase_4']) + round($ratio * $data_matrix[$row]['active_phase_3']) + round($ratio * $data_matrix[$row]['active_phase_2']) + round($ratio * $data_matrix[$row]['active_phase_1']) + round($ratio * $data_matrix[$row]['active_phase_0']) + round($ratio * $data_matrix[$row]['active_phase_na']));
	$remain_span = $total_cols - $phase_space;
	
	if($remain_span > 0)
	{
		$aq_sp = 0;
		while($aq_sp < $remain_span)
		$aq_sp = $aq_sp + $inner_columns;
		
		$extra_sp = $aq_sp - $remain_span;
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
	$htmlContent .= '<tr style="display:none;" id="total_Graph_Row_'.$row.'"><th align="right" class="prod_col"><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&hm=' . $id . '" target="_blank" style="text-decoration:underline;">'.$rval.'</a></th><th class="graph_right last_tick_width">&nbsp;</th>';
	
	$total_cols = $inner_columns * $columns;
	
	$total_colspan = $original_max_count * $ratio;
	
	if($data_matrix[$row]['total_phase_4'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['total_phase_4']).'" class="side_tick_height graph_red" title="'.$data_matrix[$row]['total_phase_4'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=4&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['total_phase_3'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['total_phase_3']).'" class="side_tick_height graph_orange" title="'.$data_matrix[$row]['total_phase_3'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=3&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['total_phase_2'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['total_phase_2']).'" class="side_tick_height graph_yellow" title="'.$data_matrix[$row]['total_phase_2'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=2&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['total_phase_1'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['total_phase_1']).'" class="side_tick_height graph_green" title="'.$data_matrix[$row]['total_phase_1'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=1&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['total_phase_0'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['total_phase_0']).'" class="side_tick_height graph_blue" title="'.$data_matrix[$row]['total_phase_0'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=0&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	if($data_matrix[$row]['total_phase_na'] > 0)
	$htmlContent .= '<th colspan="'.round($ratio * $data_matrix[$row]['total_phase_na']).'" class="side_tick_height graph_gray" title="'.$data_matrix[$row]['total_phase_na'].'" ><a href="'. trim(urlPath()) .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaId . '&list=2&phase=na&hm=' . $id . '" target="_blank">&nbsp;</a></th>';
	
	$phase_space = (round($ratio * $data_matrix[$row]['total_phase_4']) + round($ratio * $data_matrix[$row]['total_phase_3']) + round($ratio * $data_matrix[$row]['total_phase_2']) + round($ratio * $data_matrix[$row]['total_phase_1']) + round($ratio * $data_matrix[$row]['total_phase_0']) + round($ratio * $data_matrix[$row]['total_phase_na']));
	$remain_span = $total_cols - $phase_space;
	
	if($remain_span > 0)
	{
		$aq_sp = 0;
		while($aq_sp < $remain_span)
		$aq_sp = $aq_sp + $inner_columns;
		
		$extra_sp = $aq_sp - $remain_span;
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