<?php
	require_once('db.php');
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
    	<td width="50%" style="border:0; font-weight:bold; padding-left:5px;" align="left">
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
		    <td><img id="ProductsImg" src="images/firstSelectTab.png" /></td><td id="ProductsTab" class="selectTab"><a href="#" title="Products">&nbsp;Products&nbsp;</a></td><td><img id="CompaniesImg" src="images/selectTabConn.png" /></td><td id="CompaniesTab" class="Tab"><a href="#" title="Companies">&nbsp;Companies&nbsp;</a></td><td><img id="MOAsImg" src="images/afterTab.png" /></td><td id="MOAsTab" class="Tab"><a href="#" title="MOAs">&nbsp;MOAs&nbsp;</a></td><td><img id="lastImg" src="images/lastTab.png" /></td><td></td>
   		</tr>
	</table>

</td></tr>
<tr><td align="center">
<div id="diseaseTab_content" align="center"> 
    <div id="Products" align="center">        
			<?php print showProductTracker($DiseaseId, $dwcount, 'DPT', $page); //DPT=DISEASE PRODUCT TRACKER ?>
    </div>
    <div id="Companies" align="center" style="display:none;">
       		<?php print showCompanyTracker($DiseaseId, 'DCT'); //DCT=DISEASE COMPANY TRACKER ?>
    </div>
    <div id="MOAs" align="center" style="display:none;">
        	<?php print showMOATracker($DiseaseId, 'DMT'); //DMT=DISEASE MOA TRACKER ?>
    </div>
</div>
</td></tr>
</table>

<br/><br/>
<?php include "trialzilla_footer.php" ?>

</body>
</html>
<script>
$(document).ready(function() {

	var mytabs = new Array();
	mytabs[0] = "Products";
	mytabs[1] = "Companies";
	mytabs[2] = "MOAs";
	//mytabs[3] = "Conferences";
	
	for (var i=1; i<mytabs.length; i++)
	{
		$("#" + mytabs[i]).hide(); // Initially hide all content
	}
	
	$('#disease_tabs a').click(function(e) {
        e.preventDefault();        
        ///Hide all main divs
		for (var i=0; i<mytabs.length; i++)
		{
			$("#" + mytabs[i]).hide(); // Initially hide all content
		}
		///end
        $('#' + $(this).attr('title')).fadeIn(); // Show content for current tab
		
		if($(this).attr('title') == mytabs[0])
			$('#' + $(this).attr('title') + 'Img').attr('src', 'images/firstSelectTab.png');
		else if	($(this).attr('title') != mytabs[0])
			$('#' + mytabs[0] + 'Img').attr('src', 'images/firstTab.png');
			
		if($(this).attr('title') == mytabs[mytabs.length -1])
			$('#lastImg').attr('src', 'images/selectLastTab.png');
		else if	($(this).attr('title') != mytabs[mytabs.length -1])
			$('#lastImg').attr('src', 'images/lastTab.png');	
				
		for (var i=0; i<mytabs.length; i++)
		{
			if($(this).attr('title') == mytabs[i])
			{$('#' +  mytabs[i] + 'Tab').removeClass('Tab'); $('#' + mytabs[i] + 'Tab').removeClass('selectTab');	$('#' + mytabs[i] + 'Tab').addClass('selectTab');}
			else
			{$('#' + mytabs[i] + 'Tab').removeClass('Tab'); $('#' + mytabs[i] + 'Tab').removeClass('selectTab'); $('#' + mytabs[i] + 'Tab').addClass('Tab');}
			
			if(i < (mytabs.length -1) && $(this).attr('title') == mytabs[i])
				$('#' + mytabs[i+1] + 'Img').attr('src', 'images/selectTabConn.png');
			
			if(i < (mytabs.length -1) && $(this).attr('title') == mytabs[i])
				$('#' + mytabs[i+1] + 'Img').attr('src', 'images/selectTabConn.png');
			
			if(i < (mytabs.length -1) && $(this).attr('title') != mytabs[i] && $(this).attr('title')!= mytabs[i+1])
				$('#' + mytabs[i+1] + 'Img').attr('src', 'images/afterTab.png');	
			
			if(i < (mytabs.length -1) && $(this).attr('title') != mytabs[i] && $(this).attr('title') == mytabs[i+1])
				$('#' + mytabs[i+1] + 'Img').attr('src', 'images/middleTab.png');		
		}
	});
});
</script>