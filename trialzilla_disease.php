<?php
	require_once('db.php');
	require_once('product_tracker.php');
	require_once('company_tracker.php');
	if($_REQUEST['DiseaseId'] != NULL && $_REQUEST['DiseaseId'] != '' && isset($_REQUEST['DiseaseId']))
	{
		$DiseaseId = $_REQUEST['DiseaseId'];
		$query = 'SELECT `name`, `id` FROM `entities` WHERE `id`=' . mysql_real_escape_string($DiseaseId);
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$DiseaseId = $header['id'];
		$DiseaseName = $header['name'];				
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
#disease_tabs
{
   overflow: hidden;
   width: 100%;
   margin: 0;
   padding: 0;
   list-style: none;
}

#disease_tabs li
{
    float: left;
	margin: 0 -15px 0 0;
}

#disease_tabs a
{
   float: left;
   position: relative;
   padding: 0 30px;
   height: 0;
   line-height: 30px;
   text-transform: uppercase;
   text-decoration: none;
   color: #fff;      
   border-right: 30px solid transparent;
   border-bottom: 30px solid #6a4f8d;
   border-bottom-color: #6a4f8d;
   opacity: .8;
   filter: alpha(opacity=80);
   display:inline;
   width:auto;       
}

#disease_tabs a:hover
{
   border-bottom-color: #6a4f8d;
   opacity: 1;
   filter: alpha(opacity=100);
}

#disease_tabs a:focus
{
    outline: 0;
}

#disease_tabs #current a
{
    z-index: 3;
	border-bottom-color: #4f2683;
	opacity: 1;
	filter: alpha(opacity=100);  
}

#diseaseTab_content
{
    background-color: #ffffff;
    /*background-color: #fff;
    background-image: -webkit-gradient(linear, left top, left bottom, from(#fff), to(#ddd));
    background-image: -webkit-linear-gradient(top, #fff, #ddd); 
    background-image:    -moz-linear-gradient(top, #fff, #ddd); 
    background-image:     -ms-linear-gradient(top, #fff, #ddd); 
    background-image:      -o-linear-gradient(top, #fff, #ddd); 
    background-image:         linear-gradient(top, #fff, #ddd);
	filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0, startColorstr='#ffffff', endColorstr='#ebebeb'); /* for IE */
	-moz-border-radius: 2px 2px 2px 2px;
    -webkit-border-radius: 2px 2px 2px 2px;
    border-radius: 2px 2px 2px 2px;
    -moz-box-shadow: 0 2px 2px #000, 0 -1px 0 #fff inset;
    -webkit-box-shadow: 0 2px 2px #000, 0 -1px 0 #fff inset;
    box-shadow: 0 2px 2px #000, 0 -1px 0 #fff inset;
    padding: 30px;
	border:#CCCCCC 1px solid;
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
        	Details for "<?php print $DiseaseName; ?>"
        </td>
    </tr>
</table>

<!-- Displaying Records -->
<br/>
<table width="100%" border="0" style="">
<tr><td>
<ul id="disease_tabs">
    <li><a href="#" title="Products">Products</a></li>
    <li><a href="#" title="Companies">Companies</a></li>
    <!-- <li><a href="#" title="MOAs">MOAs</a></li>
    <li><a href="#" title="Conferences">Conferences</a></li> -->   
</ul>

<div id="diseaseTab_content"> 
    <div id="Products">        
			<?php print showProductTracker($DiseaseId, 'DPT'); //DPT=DISEASE PRODUCT TRACKER ?>
    </div>
    <div id="Companies">
       		<?php print showCompanyTracker($DiseaseId, 'DCT'); //DCT=DISEASE COMPANY TRACKER ?>
    </div>
    <div id="MOAs">
        MOA Tracker
    </div>
    <div id="Conferences">
        Conferences
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
	//$("#diseaseTab_content div").hide(); // Initially hide all content
	///Hide all main divs
	$("#Products").hide(); // Initially hide all content
	$("#Companies").hide(); // Initially hide all content
	$("#MOAs").hide(); // Initially hide all content
	$("#Conferences").hide(); // Initially hide all content
	///end
	
	$("#disease_tabs li:first").attr("id","current"); // Activate first tab
	$("#diseaseTab_content div:first").fadeIn(); // Show first tab content
    
    $('#disease_tabs a').click(function(e) {
        e.preventDefault();        
        //$("#diseaseTab_content div").hide(); //Hide all content
		///Hide all main divs
		$("#Products").hide(); // Initially hide all content
		$("#Companies").hide(); // Initially hide all content
		$("#MOAs").hide(); // Initially hide all content
		$("#Conferences").hide(); // Initially hide all content
		///end
        
		$("#disease_tabs li").attr("id",""); //Reset id's
        $(this).parent().attr("id","current"); // Activate this
        $('#' + $(this).attr('title')).fadeIn(); // Show content for current tab
    });
});
</script>