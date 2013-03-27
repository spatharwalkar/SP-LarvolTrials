<?php
	require_once('db.php');
	require_once('intermediary.php');
	require_once('product_tracker.php');
	require_once('company_tracker.php');
	require_once('moa_tracker.php');
	$page = 1;
	if($_REQUEST['DiseaseId'] != NULL && $_REQUEST['DiseaseId'] != '' && isset($_REQUEST['DiseaseId']))
	{
		$DiseaseId = $_REQUEST['DiseaseId'];
		$query = 'SELECT `name`, `id`, `display_name` FROM `entities` WHERE `class` = "Disease" AND `id`=' . mysql_real_escape_string($DiseaseId);
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$DiseaseId = $header['id'];
		$DiseaseName = $header['name'];
		if($header['display_name'] != NULL && $header['display_name'] != '')
				$DiseaseName = $header['display_name'];	
				
		if(isset($_REQUEST['dwcount']))
			$dwcount = $_REQUEST['dwcount'];
		else
			$dwcount = 'total';				
	}
	if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
	{
		$page = mysql_real_escape_string($_REQUEST['page']);
	}
	$tab = 'Products';
	if(isset($_REQUEST['tab']))
	{
		$tab = mysql_real_escape_string($_REQUEST['tab']);
	}
	$tabCommonUrl = trim(urlPath()).'trialzilla_disease.php?DiseaseId='.$DiseaseId;
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
	background-image:url(images/selectTab.png); 
	background-repeat:repeat-x;
}

.Tab
{
	background-image:url(images/Tab.png); 
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

<body style="background-color:#FFFFFF;">
<!-- Name & Logo -->
<?php include "trialzilla_searchbox.php";?>
<!-- Number of Results -->
<br/>
<table width="100%" border="0" class="FoundResultsTb">
	<tr>
    	<td width="50%" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; font-size:28px;" align="left">
        	<?php print $DiseaseName; ?>
        </td>
    </tr>
</table>

<!-- Displaying Records -->
<br/>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td>
	
    <table cellpadding="0" cellspacing="0" id="disease_tabs">
		<tr>
		    <?php if($tab == 'Products') {  ?>
            <td><img id="ProductsImg" src="images/firstSelectTab.png" /></td><td id="ProductsTab" class="selectTab"><a href="<?php print $tabCommonUrl.'&tab=Products'; ?>" title="Products">&nbsp;Products&nbsp;</a></td><td><img id="CompaniesImg" src="images/selectTabConn.png" /></td><td id="CompaniesTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=Companies'; ?>" title="Companies">&nbsp;Companies&nbsp;</a></td><td><img id="MOAsImg" src="images/afterTab.png" /></td><td id="MOAsTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=MOAs'; ?>" title="MOAs">&nbsp;MOAs&nbsp;</a></td><td><img id="DiseaseOTTImg" src="images/afterTab.png" /></td><td id="DiseaseOTTTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=DiseaseOTT'; ?>" title="Trial Tracker">&nbsp;Trial Tracker&nbsp;</a></td><td><img id="lastImg" src="images/lastTab.png" /></td><td></td>
            <?php } else if($tab == 'Companies') {  ?>
            <td><img id="ProductsImg" src="images/firstTab.png" /></td><td id="ProductsTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=Products'; ?>" title="Products">&nbsp;Products&nbsp;</a></td><td><img id="CompaniesImg" src="images/middleTab.png" /></td><td id="CompaniesTab" class="selectTab"><a href="<?php print $tabCommonUrl.'&tab=Companies'; ?>" title="Companies">&nbsp;Companies&nbsp;</a></td><td><img id="MOAsImg" src="images/selectTabConn.png" /></td><td id="MOAsTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=MOAs'; ?>" title="MOAs">&nbsp;MOAs&nbsp;</a></td><td><img id="DiseaseOTTImg" src="images/afterTab.png" /></td><td id="DiseaseOTTTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=DiseaseOTT'; ?>" title="Trial Tracker">&nbsp;Trial Tracker&nbsp;</a></td><td><img id="lastImg" src="images/lastTab.png" /></td><td></td>
            <?php } else if($tab == 'MOAs') {  ?>
            <td><img id="ProductsImg" src="images/firstTab.png" /></td><td id="ProductsTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=Products'; ?>" title="Products">&nbsp;Products&nbsp;</a></td><td><img id="CompaniesImg" src="images/afterTab.png" /></td><td id="CompaniesTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=Companies'; ?>" title="Companies">&nbsp;Companies&nbsp;</a></td><td><img id="MOAsImg" src="images/middleTab.png" /></td><td id="MOAsTab" class="selectTab"><a href="<?php print $tabCommonUrl.'&tab=MOAs'; ?>" title="MOAs">&nbsp;MOAs&nbsp;</a></td><td><img id="DiseaseOTTImg" src="images/selectTabConn.png" /></td><td id="DiseaseOTTTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=DiseaseOTT'; ?>" title="Trial Tracker">&nbsp;Trial Tracker&nbsp;</a></td><td><img id="lastImg" src="images/lastTab.png" /></td><td></td>
            <?php } else if($tab == 'DiseaseOTT') {  ?>
            <td><img id="ProductsImg" src="images/firstTab.png" /></td><td id="ProductsTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=Products'; ?>" title="Products">&nbsp;Products&nbsp;</a></td><td><img id="CompaniesImg" src="images/afterTab.png" /></td><td id="CompaniesTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=Companies'; ?>" title="Companies">&nbsp;Companies&nbsp;</a></td><td><img id="MOAsImg" src="images/afterTab.png" /></td><td id="MOAsTab" class="Tab"><a href="<?php print $tabCommonUrl.'&tab=MOAs'; ?>" title="MOAs">&nbsp;MOAs&nbsp;</a></td><td><img id="DiseaseOTTImg" src="images/middleTab.png" /></td><td id="DiseaseOTTTab" class="selectTab"><a href="<?php print $tabCommonUrl.'&tab=DiseaseOTT'; ?>" title="Trial Tracker">&nbsp;Trial Tracker&nbsp;</a></td><td><img id="lastImg" src="images/selectLastTab.png" /></td><td></td>
            <?php } ?>
            
   		</tr>
	</table>

</td></tr>
<tr><td align="center">
<div id="diseaseTab_content" align="center">             
	<?php if($tab == 'Products') print '<div id="Products" align="center">'.showProductTracker($DiseaseId, $dwcount, 'DPT', $page).'</div>'; //DPT=DISEASE PRODUCT TRACKER ?>
    <?php if($tab == 'Companies') print '<div id="Companies" align="center">'.showCompanyTracker($DiseaseId, 'DCT', $page).'</div>'; //DCT=DISEASE COMPANY TRACKER ?>
    <?php if($tab == 'MOAs') print '<div id="MOAs" align="center">'.showMOATracker($DiseaseId, 'DMT', $page).'</div>'; //DMT=DISEASE MOA TRACKER ?>
    <?php if($tab == 'DiseaseOTT') { print '<div id="DiseaseOTT" align="center">'; DisplayOTT(); print '</div>'; } ?>
</div>
</td></tr>
</table>
<?php
if($tab != 'DiseaseOTT')
print '<br/><br/>';
include "trialzilla_footer.php" ?>

</body>
</html>