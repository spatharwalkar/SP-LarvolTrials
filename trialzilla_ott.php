<?php
	header('P3P: CP="CAO PSA OUR"');
	session_start();
	//connect to Sphinx
	if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
	
	require_once('db.php');
	require_once('intermediary.php');
	require_once('product_tracker.php');
	
	$e1 = NULL; $e1 = NULL; $phase = NULL;
	if(isset($_REQUEST['e1'])) $e1 = $_REQUEST['e1'];
	if(isset($_REQUEST['e2'])) $e2 = $_REQUEST['e2'];
	if(isset($_REQUEST['phase'])) $phase = $_REQUEST['phase'];	
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

#FoundResultsTb a {
display:inline;
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
    	<td width="100%" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; <?php (((isset($e2) && $e2 != NULL) || (isset($phase) && $phase != NULL)) ? print 'font-size:15px;' : print 'font-size:23px;'); ?> vertical-align:middle;" align="left">
        	<table><tr>
        	<?php 
				if(isset($e2) && $e2 != NULL)
				{
					$LinkDetails = GetLinkNClass($e2);
					if($LinkDetails['class'] == 'Disease')
					{
						print '<td><a style="color:#FFFFFF; display:inline;" href="trialzilla_ott.php?e1='.$e1. ((isset($phase) && $phase != NULL) ? '&phase='.$phase:'').'&sourcepg=TZ"><img src="images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
						print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="'.$LinkDetails['link'].'">'.GetEntityName($e2).'</a>&nbsp;</td><td style="vertical-align:top;"> >> </td>';
					}
				}
				if(isset($e1) && $e1 != NULL)
				{
					$LinkDetails = GetLinkNClass($e1);
					
					if($LinkDetails['class'] == 'Product' && (!isset($e2) || $e2 == NULL || trim($e2) == ''))
					{
						$ProdRelateCompany = GetCompanyNames($e1);
						$e1name = '<b>'. GetEntityName($e1) .'</b>'. ((trim($ProdRelateCompany) != '') ? ' / '.$ProdRelateCompany:'');
					}	
					else
					$e1name = GetEntityName($e1);
					
					if(isset($e2) && $e2 != NULL)
					print '<td style="vertical-align:middle;"><a style="color:#FFFFFF; display:inline;" href="trialzilla_ott.php?e1='.$e2. ((isset($phase) && $phase != NULL) ? '&phase='.$phase:'').'&sourcepg=TZ"><img src="images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
					print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="'.$LinkDetails['link'].'">'.$e1name.'</a>&nbsp;</td>';
				}
				if(isset($e2) && $e2 != NULL)
				{
					$LinkDetails = GetLinkNClass($e2);
					if($LinkDetails['class'] != 'Disease')
					{
						print '<td style="vertical-align:top;"> >> </td><td><a style="color:#FFFFFF; display:inline;" href="trialzilla_ott.php?e1='.$e1. ((isset($phase) && $phase != NULL) ? '&phase='.$phase:'').'&sourcepg=TZ"><img src="images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
						print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="'.$LinkDetails['link'].'">'.GetEntityName($e2).'</a>&nbsp;</td>';
					}
				}
				if(isset($phase) && $phase != NULL)
				{
					print '<td style="vertical-align:top;"> >> </td><td><a style="color:#FFFFFF; display:inline;" href="trialzilla_ott.php?e1='.$e1 . ((isset($e2) && $e2 != NULL) ? '&e2='.$e2:'').'&sourcepg=TZ"><img src="images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
					print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline;" href="#">';
					$allPh = explode(',',$phase);
					$phnm = '';
					foreach($allPh as $p)	
						$phnm .= GetPhaseName($p).', ';
					print substr($phnm, 0, -2);
					print '</a></td>';
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
	DisplayOTT();
?>
</td></tr>
</table>
<br/><br/>
<?php include "trialzilla_footer.php" ?>

</body>
</html>
<?php

function GetLinkNClass($id)
{
	$LinkNClass = array();
	$query = 'SELECT `name`, `id`, `class` FROM `entities` WHERE `id`=' . mysql_real_escape_string($id);
	$res = mysql_query($query);
	$header = mysql_fetch_array($res);
	
	$LinkNClass['class'] = $header['class'];
	
	if($header['class'] == 'Disease')
		$LinkNClass['link'] = 'trialzilla_disease.php?DiseaseId='. $header['id'];
	else if($header['class'] == 'Institution')
		$LinkNClass['link'] = 'trialzilla_company.php?CompanyId='. $header['id'];
	else if($header['class'] == 'MOA')
		$LinkNClass['link'] = 'trialzilla_moa.php?MoaId='. $header['id'];
	else if($header['class'] == 'MOA_Category')
		$LinkNClass['link'] = 'trialzilla_moacategory.php?MoaCatId='. $header['id'];
	else
		$LinkNClass['link'] = 'trialzilla_ott.php?e1='. $header['id'].'&&sourcepg=TZ';
						
	return $LinkNClass;
}
 
?>