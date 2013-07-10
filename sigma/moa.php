<?php
	$cwd = getcwd();
	chdir ("..");
	require_once('db.php');
	require_once('product_tracker.php');
	chdir ($cwd);
	require_once('disease_tracker.php');
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
	
	$tab = 'moa';
	if(isset($_REQUEST['tab']))
	{
		$tab = mysql_real_escape_string($_REQUEST['tab']);
	}
	
	$tabCommonUrl = 'moa.php?MoaId='.$MoaId;
	
	$TabDiseaseCount = count(GetDiseasesFromEntity_DiseaseTracker($MoaId, 'MOA'));
	$TabProductCount = count(GetProductsFromMOA($MoaId, 'MPT', array()));
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
<!--tab css-->
<style>
.selectTab
{
	background-image:url(../images/selectTab.png); 
	background-repeat:repeat-x;
}

.Tab
{
	background-image:url(../images/Tab.png); 
	background-repeat:repeat-x;
}

#disease_tabs a
{
	text-decoration:none;
	color:#000000;
	font-size:13px;
	font-family:Arial, Helvetica, sans-serif;
	display:block;
}

#diseaseTab_content
{
    background-color: #ffffff;
    padding: 30px;
	border-top:#333333 solid 1px;
}
</style>
<script src="scripts/jquery-1.7.1.min.js"></script>
<script src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
<script type="text/javascript" src="scripts/chrome.js"></script>
<script type="text/javascript" src="scripts/iepngfix_tilebg.js"></script>
</head>
<body>
<?php include "searchbox.php";?>
<!-- Number of Results -->
<br/>
<table width="100%" border="0" class="FoundResultsTb">
	<tr>
    	<td width="100%" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; <?php ((isset($DiseaseId) || isset($phase)) ? print 'font-size:15px;' : print 'font-size:23px;'); ?> vertical-align:middle;" align="left">
        	<table><tr>
        	 <?php 
			 
				if(isset($DiseaseId) && $DiseaseId != NULL)
				{
					print '<td><a style="color:#FFFFFF; display:inline;" href="moa.php?MoaId='.$MoaId. ((isset($phase) && $phase != NULL) ? '&phase='.$phase.'&TrackerType=MPT':'').'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
					print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="disease.php?DiseaseId='.$DiseaseId.'">'.GetEntityName($DiseaseId).'</a>&nbsp;</td><td style="vertical-align:top;"> >> </td>';
				}
				if(isset($DiseaseId) && $DiseaseId != NULL)
				{
					print '<td><a style="color:#FFFFFF; display:inline;" href="disease.php?DiseaseId='.$DiseaseId.'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
				}
				print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="moa.php?MoaId='.$MoaId.'">'.$MoaName.'</a>&nbsp;</td>';
				if(isset($phase) && $phase != NULL)
				{
					print '<td style="vertical-align:top;"> >> </td><td><a style="color:#FFFFFF; display:inline;" href="moa.php?MoaId='.$MoaId . ((isset($DiseaseId) && $DiseaseId != NULL) ? '&DiseaseId='.$DiseaseId.'&TrackerType=DMPT':'').'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
					print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline;" href="#">'.GetPhaseName($phase).'</a></td>';
				} 
			?>
            </tr></table>
        </td>
    </tr>
</table>

<!-- Displaying Records -->
<br/>
<table width="100%" border="0" style="" cellpadding="0" cellspacing="0">
<?php
if((!isset($DiseaseId) || $DiseaseId == NULL) && (!isset($phase) || $phase == NULL))
{
	print '
	<tr><td>
		
		<table cellpadding="0" cellspacing="0" id="disease_tabs">
			<tr>
				'; 
				
				$CountExt = (($TabDiseaseCount == 1) ? 'Disease':'Diseases');
				$diseaseLinkName = '<a href="'.$tabCommonUrl.'&tab=diseasetrac" title="'.$TabDiseaseCount.' '.$CountExt.'">&nbsp;'.$TabDiseaseCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
				$CountExt = (($TabProductCount == 1) ? 'Product':'Products');
				$moaLinkName = '<a href="'.$tabCommonUrl.'&tab=moa" title="'.$TabProductCount.' '.$CountExt.'">&nbsp;'.$TabProductCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
				
				if($tab == 'diseasetrac') {  
				print '<td><img id="DiseaseImg" src="../images/firstSelectTab.png" /></td><td id="DiseaseTab" class="selectTab">' . $diseaseLinkName .'</td><td><img id="MoaImg" src="../images/selectTabConn.png" /></td><td id="moaTab" class="Tab">'. $moaLinkName .'</td><td><img id="lastImg" src="../images/lastTab.png" /></td> 
				<td></td>';
				 } else if($tab == 'moa') { 
				print '<td><img id="DiseaseImg" src="../images/firstTab.png" /></td><td id="DiseaseTab" class="Tab">'. $diseaseLinkName .'</td><td><img id="MoaImg" src="../images/middleTab.png" /></td><td id="moaTab" class="selectTab">'. $moaLinkName .'</td></td><td><img id="lastImg" src="../images/selectLastTab.png" /></td><td></td>';
				// print '<td><img id="MoaImg" src="images/firstSelectTab.png" /></td><td id="moaTab" class="selectTab">'. $moaLinkName .'</td></td><td><img id="lastImg" src="images/selectLastTab.png" /></td><td></td>';
				 } 
	print	'            
			</tr>
		</table>
	
	</td></tr>';
}	
?>
<tr><td align="center">
<?php
if((!isset($DiseaseId) || $DiseaseId == NULL) && (!isset($phase) || $phase == NULL))
{
	print '<div id="diseaseTab_content" align="center">';
	if($tab == 'diseasetrac')
		print showDiseaseTracker($MoaId, 'MDT', $page);		//MDT= MOA DISEASE TRACKER
	else
		print showProductTracker($MoaId, $dwcount, 'MPT', $page, $OptionArray);	//MPT= MOA PRODUCT TRACKER 	
	print '</div>';
}
else
{	 
	if(isset($_REQUEST['TrackerType']) && $_REQUEST['TrackerType'] == 'DMPT')
		print showProductTracker($MoaId, $dwcount, 'DMPT', $page, $OptionArray);	//DMPT= DISEASE MOA PRODUCT TRACKER 
	else
		print showProductTracker($MoaId, $dwcount, 'MPT', $page, $OptionArray);	//MPT= MOA PRODUCT TRACKER 	
}
?>
</td></tr>

</table>
<br/><br/>
<?php include "footer.php" ?>

</body>
</html>