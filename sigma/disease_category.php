<?php
	header('P3P: CP="CAO PSA OUR"');
	session_start();
	//connect to Sphinx
	if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
	$cwd = getcwd();
	chdir ("..");
	require_once('db.php');
	require_once('intermediary.php');
	require_once('product_tracker.php');
	chdir ($cwd);
	require_once('company_tracker.php');
	require_once('moa_tracker.php');
	$page = 1;
	if($_REQUEST['DiseaseCatId'] != NULL && $_REQUEST['DiseaseCatId'] != '' && isset($_REQUEST['DiseaseCatId']))
	{
		$DiseaseCatId   = $_REQUEST['DiseaseCatId'];
		$query          = 'SELECT `name`, `id`, `display_name` FROM `entities` WHERE `class` = "Disease_Category" AND `id`=' . mysql_real_escape_string($DiseaseCatId);
		$res            = mysql_query($query) or die(mysql_error());
		$header         = mysql_fetch_array($res);
		$DiseaseCatId   = $header['id'];
		$DiseasecatName = $header['name'];
		if($header['display_name'] != NULL && $header['display_name'] != '')
		     $DiseasecatName = $header['display_name'];	
				
		if(isset($_REQUEST['dwcount']))
			$dwcount = $_REQUEST['dwcount'];
		else
			$dwcount = 'total';				
	}
	if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
	{
		$page = mysql_real_escape_string($_REQUEST['page']);
	}
	$tab = 'Companies';
	if(isset($_REQUEST['tab']))
	{
		$tab = mysql_real_escape_string($_REQUEST['tab']);
	}
	$tabCommonUrl    = 'disease_category.php?DiseaseCatId='.$DiseaseCatId;
	$arrDiseaseIds   = getDiseaseIdsFromDiseaseCat($DiseaseCatId);
	$productIds      = GetProductsFromDiseaseCat($arrDiseaseIds);
	$CompanyIds      = GetCompaniesFromDiseaseCat_CompanyTracker($arrDiseaseIds);
	$MOAData         = GetMOAsOrMOACatFromDiseaseCat_MOATracker($arrDiseaseIds);
	$TabProductCount = count($productIds);
	$TabCompanyCount = count($CompanyIds);
	$TabMOACount     = count($MOAData['all']);
	$TabTrialCount   = GetTrialsCountFromDiseaseCat($arrDiseaseIds);
	
	$meta_title = 'Larvol Sigma'; //default value
	$meta_title = isset($DiseasecatName) ? $DiseasecatName. ' - '.$meta_title : $meta_title;	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $meta_title; ?></title>
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

<body style="background-color:#FFFFFF;">
	<!-- Name & Logo -->
	<?php include "searchbox.php";?>
	<!-- Number of Results -->
	<br/>
	<table width="100%" border="0" class="FoundResultsTb">
		<tr>
			<td width="50%" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; font-size:28px;" align="left">
				<?php print $DiseasecatName; ?>
			</td>
		</tr>
	</table>

	<!-- Displaying Records -->
	<br/>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td>
	
				<table cellpadding="0" cellspacing="0" id="disease_tabs">
					<tr>
						<?php 
						
						$CountExt = (($TabProductCount == 1) ? 'Product':'Products');
						$prodLinkName = '<a href="'.$tabCommonUrl.'&tab=Products" title="'.$TabProductCount.' '.$CountExt.'">&nbsp;'.$TabProductCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabCompanyCount == 1) ? 'Company':'Companies');
						$compLinkName = '<a href="'.$tabCommonUrl.'&tab=Companies" title="'.$TabCompanyCount.' '.$CountExt.'">&nbsp;'.$TabCompanyCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabMOACount == 1) ? 'Mechanisms of Action':'Mechanisms of Action');
						$moaLinkName = '<a href="'.$tabCommonUrl.'&tab=MOAs" title="'.$TabMOACount.' '.$CountExt.'">&nbsp;'.$TabMOACount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabTrialCount == 1) ? 'Trial':'Trials');
						$ottLinkName = '<a href="'.$tabCommonUrl.'&tab=DiseaseOTT" title="'.$TabTrialCount.' '.$CountExt.'">&nbsp;'.$TabTrialCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$ohmLinkName = '<a href="'.$tabCommonUrl.'&tab=DiseaseOHM" title="Heatmap">&nbsp;Heatmap&nbsp;</a>';
						
						if($tab == 'Companies') { ?>							
							<td>
								<img id="CompaniesImg" src="../images/firstSelectTab.png" />
							</td>
							<td id="CompaniesTab" class="selectTab"><?php print $compLinkName; ?></td>
							<td>
								<img id="ProductsImg" src="../images/selectTabConn.png" />
							</td>
							<td id="ProductsTab" class="Tab"><?php print $prodLinkName; ?></td>
							<td>
								<img id="MOAsImg" src="../images/afterTab.png" />
							</td>
							<td id="MOAsTab" class="Tab"><?php print $moaLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/afterTab.png" />
							</td>
							<td id="DiseaseOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="DiseaseOHMImg" src="../images/afterTab.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> --> <td><img id="lastImg" src="../images/lastTab.png" /></td> 
							<td></td>
						<?php } else if($tab == 'Products') { ?>							
							<td>
								<img id="CompaniesImg" src="../images/firstTab.png" />
							</td>
							<td id="CompaniesTab" class="Tab"><?php print $compLinkName; ?></td>
							<td>
								<img id="ProductsImg" src="../images/middleTab.png" />
							</td>
							<td id="ProductsTab" class="selectTab"><?php print $prodLinkName; ?></td>
							<td>
								<img id="MOAsImg" src="../images/selectTabConn.png" />
							</td>
							<td id="MOAsTab" class="Tab"><?php print $moaLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/afterTab.png" />
							</td>
							<td id="DiseaseOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="DiseaseOHMImg" src="../images/afterTab.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> --> <td><img id="lastImg" src="../images/lastTab.png" /></td> 
							<td></td>
						<?php } else if($tab == 'MOAs') { ?>
							<td>
								<img id="CompaniesImg" src="../images/firstTab.png" />
							</td>
							<td id="CompaniesTab" class="Tab"><?php print $compLinkName; ?></td>
							<td>
								<img id="ProductsImg" src="../images/afterTab.png" />
							</td>
							<td id="ProductsTab" class="Tab"><?php print $prodLinkName; ?></td>
							<td>
								<img id="MOAsImg" src="../images/middleTab.png" />
							</td>
							<td id="MOAsTab" class="selectTab"><?php print $moaLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/selectTabConn.png" />
							</td>
							<td id="DiseaseOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="DiseaseOHMImg" src="../images/afterTab.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> --> <td><img id="lastImg" src="../images/lastTab.png" /></td> 
							<td></td>
						<?php } else if($tab == 'DiseaseOTT') { ?>
							<td>
								<img id="CompaniesImg" src="../images/firstTab.png" />
							</td>
							<td id="CompaniesTab" class="Tab"><?php print $compLinkName; ?></td>
							<td>
								<img id="ProductsImg" src="../images/afterTab.png" />
							</td>
							<td id="ProductsTab" class="Tab"><?php print $prodLinkName; ?></td>
							<td>
								<img id="MOAsImg" src="../images/afterTab.png" />
							</td>
							<td id="MOAsTab" class="Tab"><?php print $moaLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/middleTab.png" />
							</td>
							<td id="DiseaseOTTTab" class="selectTab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="DiseaseOHMImg" src="../images/selectTabConn.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> --> <td><img id="lastImg" src="../images/selectLastTab.png" /></td><td></td>
						<?php } else if($tab == 'DiseaseOHM') { ?>
							<td>
								<img id="ProductsImg" src="../images/firstTab.png" />
							</td>
							<td id="ProductsTab" class="Tab"><?php print $prodLinkName; ?></td>
							<td>
								<img id="CompaniesImg" src="../images/afterTab.png" />
							</td>
							<td id="CompaniesTab" class="Tab"><?php print $compLinkName; ?></td>
							<td>
								<img id="MOAsImg" src="../images/afterTab.png" />
							</td>
							<td id="MOAsTab" class="Tab"><?php print $moaLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/afterTab.png" />
							</td>
							<td id="DiseaseOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<td>
								<img id="DiseaseOHMImg" src="../images/middleTab.png" />
							</td>
							<td id="DiseaseOHMTab" class="selectTab"><?php print $ohmLinkName; ?></td>
							<td>
								<img id="lastImg" src="../images/selectLastTab.png" />
							</td>
							<td></td>
						<?php } ?>						
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td align="center">
				<div id="diseaseTab_content" align="center">
					<?php 
					if($tab == 'Products')
					{
						chdir ("..");
						//DISCATPT=DISEASE CATEGORY PRODUCT TRACKER
						print '<div id="Products" align="center">'.showProductTracker($DiseaseCatId, $dwcount, 'DISCATPT', $page).'</div>';
						chdir ("$cwd");
					}
					if($tab == 'Companies')
					{
					print '<div id="Companies" align="center">'.showCompanyTracker($DiseaseCatId, 'DISCATCT', $page).'</div>'; //DISCATCT=DISEASE Category COMPANY TRACKER 
					}
					if($tab == 'MOAs')
					print '<div id="MOAs" align="center">'.showMOATracker($DiseaseCatId, 'DISCATMT', $page).'</div>'; //DISCATMT=DISEASE Category MOA TRACKER 
					if($tab == 'DiseaseOTT')
					{
						chdir ("..");
						print '<div id="DiseaseOTT" align="center">'; DisplayOTT(); print '</div>'; 
						chdir ("$cwd");
					}
					if($tab == 'DiseaseOHM') 
					{
						//DOHM=DISEASE ONLINE HEATMAP
						print '<div id="DiseaseOHM" align="center">'; DisplayOHM($arrDiseaseIds, 'DOHM'); print '</div>'; 
					} ?>
				</div>
			</td>
		</tr>
	</table>
	<?php
	if($tab != 'DiseaseOTT')
	print '<br/><br/>';
	include "footer.php";

	/* Function to get Diseases count based on Disease_Category id */
	function getDiseaseIdsFromDiseaseCat($dcid)
	{
		global $db;
		global $now;
		$ProductsCount = 0;
		$arrDiseaseIds = array();
		
		if($dcid > 0) 
		{
			$query = "SELECT child FROM `entity_relations` WHERE parent = '$dcid'";
			$res = mysql_query($query) or die('Bad SQL query for counting diseases by a disease category ID ');

			if($res)
			{
				while($row = mysql_fetch_array($res))
					$arrDiseaseIds[] = $row['child'];
			}
		}
		return $arrDiseaseIds;
	}

	/* Function to get Trials count from Disease ids */
	function GetTrialsCountFromDiseaseCat($arrDiseaseIds)
	{
		global $db;
		global $now;
		$TrialsCount = 0;
		
		if(is_array($arrDiseaseIds) && count($arrDiseaseIds)) 
		{
			$arrImplode = implode(",", $arrDiseaseIds);
			
			//$query = 	"SELECT count(Distinct(dt.`larvol_id`)) as trialCount FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`)  WHERE et.`entity` in(" . mysql_real_escape_string($arrImplode) . ")";
			$query =	"SELECT count(Distinct(dt.`larvol_id`)) as trialCount 
						FROM `entity_trials` et 
						JOIN `data_trials` dt 
						ON(dt.`larvol_id` = et.`trial` and et.`entity` in(" . mysql_real_escape_string($arrImplode) . "))";
			$res = mysql_query($query) or die('Bad SQL query getting trials count from Disease id in TZ');

			if($res)
			{
				while($row = mysql_fetch_array($res))
					$TrialsCount = $row['trialCount'];
			}
		}
		
		return $TrialsCount;
	}

	/* Function to get Trials count from Disease id */
	function GetTrialsCountFromDisease($DiseaseID)
	{
		global $db;
		global $now;
		$TrialsCount = 0;
		$query = "SELECT count(Distinct(dt.`larvol_id`)) as trialCount FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`)  WHERE et.`entity`='" . mysql_real_escape_string($DiseaseID) . "'";
		$res = mysql_query($query) or die('Bad SQL query getting trials count from Disease id in TZ');
		
		if($res)
		{
			while($row = mysql_fetch_array($res))
			$TrialsCount = $row['trialCount'];
		}
		return $TrialsCount;
	}
	?>

</body>
</html>