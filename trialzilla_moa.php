<?php
	require_once('db.php');
	require_once('product_tracker.php');
	$page = 1;
	if($_REQUEST['MoaId'] != NULL && $_REQUEST['MoaId'] != '' && isset($_REQUEST['MoaId']))
	{
		$MoaId = $_REQUEST['MoaId'];
		$query = 'SELECT `name`, `id`, `display_name` FROM `entities` WHERE `class` = "MOA" AND `id`=' . mysql_real_escape_string($MoaId);
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$MoaId = $header['id'];
		$MoaName = $header['name'];
		if($header['display_name'] != NULL && $header['display_name'] != '')
				$MoaName = $header['display_name'];	
				
		if(isset($_REQUEST['dwcount']))
			$dwcount = $_REQUEST['dwcount'];
		else
			$dwcount = 'total';					
	}
	
	if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
	{
		$page = mysql_real_escape_string($_REQUEST['page']);
	}
	
	$DiseaseId = NULL;
	if(isset($_REQUEST['DiseaseId']))
	{
		$DiseaseId = mysql_real_escape_string($_REQUEST['DiseaseId']);
	}
	
	$phase = NULL;
	if(isset($_REQUEST['phase']))
	{
		$phase = mysql_real_escape_string($_REQUEST['phase']);
	}

	$OptionArray = array('DiseaseId'=>$DiseaseId, 'Phase'=> $phase);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Larvol Trials</title>
<style type="text/css">
body
{
	font-family:Arial;
	font-size:14px;
	color:#000000;
}

a {color:#1122cc;}      /* unvisited link */
a:visited {color:#6600bc;}  /* visited link */
/*a:hover {color:#FF00FF;}  /* mouse over link */
/*a:active {color:#0000FF;}  /* selected link */

.ReportHeading
{
	color: rgb(83, 55, 130);
	font-size: xx-large;
	font-weight: bold;
}

.SearchBttn1
{
	width:100px;
	height:35px;
	background-color:#4f2683;
	font-weight:bold;
	color:#FFFFFF;
}

.FoundResultsTb
{
	background-color:#aa8ece;
	border:0;
	border-top:#4f2683 solid 2px;
}
</style>
<script src="scripts/jquery-1.7.1.min.js"></script>
<script src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
<script type="text/javascript" src="scripts/chrome.js"></script>
<script type="text/javascript" src="scripts/iepngfix_tilebg.js"></script>
</head>
<body>
<?php include "trialzilla_searchbox.php";?>
<!-- Number of Results -->
<br/>
<table width="100%" border="0" class="FoundResultsTb">
	<tr>
    	<td width="100%" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; font-size:23px;" align="left">
        	<table><tr>
        	 <?php 
				print '<td style="vertical-align:middle;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="trialzilla_moa.php?MoaId='.$MoaId.'">'.$MoaName.'</a>&nbsp;</td>';
				if(isset($DiseaseId) && $DiseaseId != NULL)
				{
					print '<td style="vertical-align:middle;"> >> </td><td><a style="color:#FFFFFF; display:inline;" href="trialzilla_moa.php?MoaId='.$MoaId. ((isset($phase) && $phase != NULL) ? '&phase='.$phase.'&TrackerType=MPT':'').'"><img src="images/delicon.gif" width="30" height="30" style="padding-top:2px;" /></a>&nbsp;</td>';
					print '<td style="vertical-align:middle;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="trialzilla_disease.php?DiseaseId='.$DiseaseId.'">'.GetEntityName($DiseaseId).'</a>&nbsp;</td>';
				}
				if(isset($phase) && $phase != NULL)
				{
					print '<td style="vertical-align:middle;"> >> </td><td><a style="color:#FFFFFF; display:inline;" href="trialzilla_moa.php?MoaId='.$MoaId . ((isset($DiseaseId) && $DiseaseId != NULL) ? '&DiseaseId='.$DiseaseId.'&TrackerType=DMPT':'').'"><img src="images/delicon.gif" width="30" height="30" style="padding-top:2px;" /></a>&nbsp;</td>';
					print '<td style="vertical-align:middle;"><a style="color:#FFFFFF; display:inline;" href="#">'.GetPhaseName($phase).'</a></td>';
				} 
			?>
            </tr></table>
        </td>
    </tr>
</table>

<!-- Displaying Records -->
<br/>
<table width="100%" border="0" style="">
<tr><td>
<?php 
	if(isset($_REQUEST['TrackerType']) && $_REQUEST['TrackerType'] == 'DMPT')
		print showProductTracker($MoaId, $dwcount, 'DMPT', $page, $OptionArray);	//DMPT= DISEASE MOA PRODUCT TRACKER 
	else
		print showProductTracker($MoaId, $dwcount, 'MPT', $page, $OptionArray);	//MPT= MOA PRODUCT TRACKER 
?>
</td></tr>
</table>
<br/><br/>
<?php include "trialzilla_footer.php" ?>

</body>
</html>