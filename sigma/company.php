<?php
	$cwd = getcwd();
	chdir ("..");
	require_once('db.php');
	require_once('product_tracker.php');
	require_once('intermediary.php');
	chdir ($cwd);
	require_once('disease_tracker.php');
	require_once('investigators_tracker.php');
	require_once('news_tracker.php');
	$page = 1;
	if($_REQUEST['CompanyId'] != NULL && $_REQUEST['CompanyId'] != '' && isset($_REQUEST['CompanyId']))
	{
		$CompanyId = $_REQUEST['CompanyId'];
		$query = 'SELECT `name`, `id`, `display_name` FROM `entities` WHERE `class` = "Institution" AND `id`=' . mysql_real_escape_string($CompanyId);
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$CompanyId = $header['id'];
		$CompanyName = $header['name'];
		if($header['display_name'] != NULL && $header['display_name'] != '')
				$CompanyName = $header['display_name'];	
				
		if(isset($_REQUEST['dwcount']))
			$dwcount = $_REQUEST['dwcount'];
		else
			$dwcount = 'total';					
	} elseif($_REQUEST['e1'] != NULL && $_REQUEST['e1'] != '' && isset($_REQUEST['e1']))
	{
		$CompanyId = $_REQUEST['e1'];
		$query = 'SELECT `name`, `id`, `display_name` FROM `entities` WHERE `class` = "Institution" AND `id`=' . mysql_real_escape_string($CompanyId);
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$CompanyId = $header['id'];
		$CompanyName = $header['name'];
		if($header['display_name'] != NULL && $header['display_name'] != '')
			$CompanyName = $header['display_name'];
		
		if(isset($_REQUEST['dwcount']))
			$dwcount = $_REQUEST['dwcount'];
		else
			$dwcount = 'total';
	}
	
	if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
	{
		$page = mysql_real_escape_string($_REQUEST['page']);
	}
	
	
	$phase = NULL;
	if(isset($_REQUEST['phase']))
	{
		$phase = mysql_real_escape_string($_REQUEST['phase']);
	}

	$DiseaseId = NULL;
	if(isset($_REQUEST['DiseaseId']))
	{
		$DiseaseId = mysql_real_escape_string($_REQUEST['DiseaseId']);
		$OptionArray = array('DiseaseId'=>$DiseaseId, 'Phase'=> $phase);
	}
	
	if(isset($_REQUEST['DiseaseCatId']))
	{
		$DiseaseCatId = mysql_real_escape_string($_REQUEST['DiseaseCatId']);
		$OptionArray = array('DiseaseCatId'=>$DiseaseCatId, 'Phase'=> $phase);
	}
	if(!isset($_REQUEST['DiseaseCatId']) && !isset($_REQUEST['DiseaseId']) ){
		$OptionArray = array('DiseaseId'=>$DiseaseId, 'Phase'=> $phase);
	}
	$InvestigatorId = null;
	if(isset($_REQUEST['InvestigatorId']))
	{
		$InvestigatorId = mysql_real_escape_string($_REQUEST['InvestigatorId']);
		$OptionArray = array('InvestigatorId'=>$InvestigatorId, 'Phase'=> $phase);
	}
	
	
	$tab = 'company';
	if(isset($_REQUEST['tab']))
	{
		$tab = mysql_real_escape_string($_REQUEST['tab']);
	}
	
	$categoryFlag = (isset($_REQUEST['category']) ? $_REQUEST['category'] : 0);
	$tabCommonUrl = 'company.php?CompanyId='.$CompanyId;
	$tabOTTUrl    = 'company.php?e1='.$CompanyId;
	$disease = array();
	if($categoryFlag == 1){
		//$TabDiseaseCount = count(GetDiseasesCatFromEntity_DiseaseTracker($CompanyId, 'Institution' ));
		$disease = DataGeneratorForDiseaseTracker($CompanyId, 'CDT', $page, $dwcount, $categoryFlag);
		$TabDiseaseCount = $disease['TotalRecords'];
	}
	else{
		//$TabDiseaseCount = count(GetDiseasesFromEntity_DiseaseTracker($CompanyId, 'Institution'));
		$disease = DataGeneratorForDiseaseTracker($CompanyId, 'CDT', $page, $dwcount, $categoryFlag);
		$TabDiseaseCount = $disease['TotalRecords'];
	}	
	
	$product = array();
	$product = DataGenerator($CompanyId, 'CPT', $page, $OptionArray, $dwcount);
	$TabProductCount = $product['TotalRecords'];
	$productIds = $product['ProductIds'];
	$TabTrialCount = GetTrialsCountForCompany($productIds);	
	$TabInvestigatorCount = count(GetInvestigatorFromEntity_InvestigatorTracker($CompanyId, 'Institution'));
	$TabNewsCount = GetNewsCountForCompany($productIds);
	
	$meta_title = 'Larvol Sigma'; //default value
	$meta_title = isset($CompanyName) ? $CompanyName. ' - '.$meta_title : $meta_title;	
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

		#FoundResultsTb a {
		display:inline;
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
			<td style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; <?php ((isset($DiseaseId) || isset($phase)) ? print 'font-size:15px;' : print 'font-size:23px;'); ?> vertical-align:middle;" align="left">
			
				<table>
					<tr>
					<?php 
						if(isset($DiseaseId) && $DiseaseId != NULL)
						{
							print '<td><a style="color:#FFFFFF; display:inline;" href="company.php?CompanyId='.$CompanyId. ((isset($phase) && $phase != NULL) ? '&phase='.$phase.'&TrackerType=CPT':'').'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
							print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="disease.php?DiseaseId='.$DiseaseId.'">'.GetEntityName($DiseaseId).'</a>&nbsp;</td>';
						}
						if(isset($DiseaseId) && $DiseaseId != NULL)
						{
							print '<td style="vertical-align:top;"> >> </td><td><a style="color:#FFFFFF; display:inline;" href="disease.php?DiseaseId='.$DiseaseId.'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
						}
						if(isset($DiseaseCatId) && $DiseaseCatId != NULL)
						{
							print '<td><a style="color:#FFFFFF; display:inline;" href="company.php?CompanyId='.$CompanyId. ((isset($phase) && $phase != NULL) ? '&phase='.$phase.'&TrackerType=CPT':'').'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
							print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="disease_category.php?DiseaseCatId='.$DiseaseCatId.'">'.GetEntityName($DiseaseCatId).'</a>&nbsp;</td>';
						}
						if(isset($DiseaseCatId) && $DiseaseCatId != NULL)
						{
							print '<td style="vertical-align:top;"> >> </td><td><a style="color:#FFFFFF; display:inline;" href="disease_category.php?DiseaseCatId='.$DiseaseCatId.'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
						}
						if(isset($InvestigatorId) && $InvestigatorId != NULL)
						{
							print '<td><a style="color:#FFFFFF; display:inline;" href="company.php?CompanyId='.$CompanyId. ((isset($phase) && $phase != NULL) ? '&phase='.$phase.'&TrackerType=CPT':'').'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
							print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="investigator.php?InvestigatorId='.$InvestigatorId.'">'.GetEntityName($InvestigatorId).'</a>&nbsp;</td>';
						}
						if(isset($InvestigatorId) && $InvestigatorId != NULL)
						{
							print '<td style="vertical-align:top;"> >> </td><td><a style="color:#FFFFFF; display:inline;" href="investigator.php?InvestigatorId='.$InvestigatorId.'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
						}
						print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="company.php?CompanyId='.$CompanyId.'">'.$CompanyName.'</a>&nbsp;</td>';
						if(isset($phase) && $phase != NULL)
						{
							print '<td style="vertical-align:top;"> >> </td><td><a style="color:#FFFFFF; display:inline;" href="company.php?CompanyId='.$CompanyId . ((isset($DiseaseId) && $DiseaseId != NULL) ? '&DiseaseId='.$DiseaseId.'&TrackerType=DCPT':'').'"><img src="../images/delicon.gif" width="15" height="15" style="padding-top:2px;" /></a>&nbsp;</td>';
							print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline;" href="#">'.GetPhaseName($phase).'</a></td>';
						}
						
					?>
					</tr>
				</table>
			</td>
			<td width="50%" align="right" style="border:0; font-weight:bold; padding-right:5px;">			
			<?php
			if($db->loggedIn()) {
				echo('<div style="padding-left:10px;float:right;">Welcome, <a  style="display:inline;"  href="profile.php">'
					. htmlspecialchars($db->user->username) . '</a> :: <a  style="float:right;width:50px;"  href="login.php?logout=true">Logout</a> &nbsp; </div>');
			} else {
				echo ('<div style="padding-left:10px;float:right;font-size:18px;"><a href="login.php">login</a></div>');
			}
			?>
			
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
		<tr>
			<td>
			
				<table cellpadding="0" cellspacing="0" id="disease_tabs">
					<tr>
						'; 
						if($categoryFlag == 1)
						{
							if($tab == 'diseasetrac') $tmp=showDiseaseTracker($CompanyId, 'CDT', $page, $categoryFlag);	//to recalculate no. of DCs
							$CountExt = (($TabDiseaseCount == 1) ? 'Disease Category':'Disease Categories');
						}
						else
						{
							$CountExt = (($TabDiseaseCount == 1) ? 'Disease':'Diseases');
						}
						
						$diseaseLinkName = '<a href="'.$tabCommonUrl.'&tab=diseasetrac" title="'.$TabDiseaseCount.' '.$CountExt.'">&nbsp;'.$TabDiseaseCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabProductCount == 1) ? 'Product':'Products');
						$companyLinkName = '<a href="'.$tabCommonUrl.'&tab=company" title="'.$TabProductCount.' '.$CountExt.'">&nbsp;'.$TabProductCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabTrialCount == 1) ? 'Trial':'Trials');
						$ottLinkName = '<a href="'.$tabOTTUrl.'&tab=OTTtrac&sourcepg=TZC" title="'.$TabTrialCount.' '.$CountExt.'">&nbsp;'.$TabTrialCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabInvestigatorCount == 1) ? 'Investigator':'Investigators');
						$investigatorLinkName = '<a href="'.$tabCommonUrl.'&tab=investigatortrac" title="'.$TabInvestigatorCount.' '.$CountExt.'">&nbsp;'.$TabInvestigatorCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabNewsCount == 1) ? 'News':'News');
						$newsLinkName = '<a href="'.$tabCommonUrl.'&tab=newstrac" title="'.$TabNewsCount.' '.$CountExt.'">&nbsp;'.$TabNewsCount.'&nbsp;'.$CountExt.'&nbsp;</a>';

						if($tab == 'diseasetrac') {
							print '<td><img id="CompanyImg" src="../images/firstTab.png" /></td>
							<td id="CompanyTab" class="Tab">'. $companyLinkName .'</td>
							<td><img id="DiseaseImg" src="../images/middleTab.png" /></td>
							<td id="DiseaseTab" class="selectTab">' . $diseaseLinkName .'</td>
							<td><img id="lastImg" src="../images/selectTabConn.png" /></td>
							<td id="CompanyTab" class="Tab">'. $investigatorLinkName .'</td>
							<td><img id="InvestigatorsImg" src="../images/afterTab.png" /></td>
							<td id="CompanyOTTTab" class="Tab">'.$ottLinkName.'</td>
							<td><img id="CompanyImg" src="../images/afterTab.png" /></td>
							<td id="InvestigatorTab" class="Tab">'. $newsLinkName .'</td>
							<td><img id="lastImg" src="../images/lastTab.png" /></td>
							<td></td>';
						 } else if($tab == 'company') { 
							print '<td><img id="CompanyImg" src="../images/firstSelectTab.png" /></td>
							<td id="CompanyTab" class="selectTab">'. $companyLinkName .'</td>
							<td><img id="DiseaseImg" src="../images/selectTabConn.png" /></td>
							<td id="DiseaseTab" class="Tab">' . $diseaseLinkName .'</td>
							<td><img id="lastImg" src="../images/afterTab.png" /></td>
							<td id="CompanyTab" class="Tab">'. $investigatorLinkName .'</td>							
							<td><img id="InvestigatorsImg" src="../images/afterTab.png" /></td>							
							<td id="CompanyOTTTab" class="Tab">'.$ottLinkName.'</td>
							<td><img id="CompanyImg" src="../images/afterTab.png" /></td>
							<td id="InvestigatorTab" class="Tab">'. $newsLinkName .'</td>
							<td><img id="lastImg" src="../images/lastTab.png" /></td>
							<td></td>';
							// print '<td><img id="CompanyImg" src="../images//firstSelectTab.png" /></td><td id="CompanyTab" class="selectTab">'. $companyLinkName .'</td></td><td><img id="lastImg" src="../images//selectLastTab.png" /></td><td></td>';
						 } else if($tab == 'OTTtrac') {
								print '<td><img id="CompanyImg" src="../images/firstTab.png" /></td>
									<td id="CompanyTab" class="Tab">'. $companyLinkName .'</td>
									<td><img id="DiseaseImg" src="../images/afterTab.png" /></td>
									<td id="DiseaseTab" class="Tab">' . $diseaseLinkName .'</td>
									<td><img id="lastImg" src="../images/afterTab.png" /></td>
									<td id="CompanyTab" class="Tab">'. $investigatorLinkName .'</td>
									<td><img id="InvestigatorsImg" src="../images/middleTab.png" /></td>
									<td id="CompanyOTTTab" class="selectTab">'.$ottLinkName.'</td>
									<td><img id="lastImg" src="../images/selectTabConn.png" /></td>
									<td id="InvestigatorTab" class="Tab">'. $newsLinkName .'</td>
									<td><img id="lastImg" src="../images/lastTab.png" /></td>
									<td></td>';
						 } else if($tab == 'investigatortrac') {
							print '<td><img id="DiseaseImg" src="../images/firstTab.png" /></td>
									<td id="DiseaseTab" class="Tab">'. $companyLinkName .'</td>
									<td><img id="CompanyImg" src="../images/afterTab.png" /></td>
									<td id="CompanyTab" class="Tab">'. $diseaseLinkName .'</td>
									<td><img id="lastImg" src="../images/middleTab.png" /></td>
									<td id="CompanyTab" class="selectTab">'. $investigatorLinkName .'</td>
									<td><img id="InvestigatorsImg" src="../images/selectTabConn.png" /></td>
									<td id="CompanyOTTTab" class="Tab">'.$ottLinkName.'</td>
									<td><img id="CompanyImg" src="../images/afterTab.png" /></td>
									<td id="InvestigatorTab" class="Tab">'. $newsLinkName .'</td>
									<td><img id="lastImg" src="../images/lastTab.png" /></td>
									<td></td>';
						 } else if($tab == 'newstrac') {
											print '<td><img id="CompanyImg" src="../images/firstTab.png" /></td>
									<td id="CompanyTab" class="Tab">'. $companyLinkName .'</td>
									<td><img id="DiseaseImg" src="../images/afterTab.png" /></td>
									<td id="DiseaseTab" class="Tab">' . $diseaseLinkName .'</td>
									<td><img id="lastImg" src="../images/afterTab.png" /></td>
									<td id="CompanyTab" class="Tab">'. $investigatorLinkName .'</td>
									<td><img id="InvestigatorsImg" src="../images/afterTab.png" /></td>
									<td id="CompanyOTTTab" class="Tab">'.$ottLinkName.'</td>
									<td><img id="CompanyImg" src="../images/middleTab.png" /></td>															
									<td id="CompanyTab" class="selectTab">'. $newsLinkName .'</td></td>
									<td><img id="lastImg" src="../images/selectLastTab.png" /></td><td></td>';
						 	// print '<td><img id="CompanyImg" src="../images/firstSelectTab.png" /></td><td id="CompanyTab" class="selectTab">'. $companyLinkName .'</td></td><td><img id="lastImg" src="../images/selectLastTab.png" /></td><td></td>';
				 }
						print	'            
					</tr>
				</table>		
			</td>
		</tr>';
		}	
		?>
		<tr>
			<td align="center">
				<?php
				if((!isset($DiseaseId) || $DiseaseId == NULL) && (!isset($InvestigatorId) || $InvestigatorId == NULL) && (!isset($phase) || $phase == NULL))
				{
					print '<div id="diseaseTab_content" align="center">';
					if($tab == 'diseasetrac')
						print showDiseaseTracker($CompanyId, 'CDT', $page, $categoryFlag);//CDT= COMPANY DISEASE TRACKER
					else if($tab == 'investigatortrac')
						print showInvestigatorTracker($CompanyId, 'CIT', $page);		//CIT= COMPANY INVESTIGATOR TRACKER
					else if($tab == 'newstrac')
						print showNewsTracker($CompanyId, 'CNT', $page);		//CNT = COMPANY NEWS TRACKER  showNewsTracker
					else if($tab == 'OTTtrac'){
						chdir ("..");
						DisplayOTT(); //SHOW OTT
						chdir ("$cwd");
					}
					else
						print showProductTracker($CompanyId, $dwcount, 'CPT', $page, $OptionArray);	//CPT = COMPANY PRODUCT TRACKER 
					print '</div>';
				}
				else
				{	 
					if(isset($_REQUEST['TrackerType']) && $_REQUEST['TrackerType'] == 'DCPT')
						print showProductTracker($CompanyId, $dwcount, 'DCPT', $page, $OptionArray);	//DCPT - DISEASE COMPANY PRODUCT TRACKER
					elseif(isset($_REQUEST['TrackerType']) && $_REQUEST['TrackerType'] == 'DISCATCPT')
						print showProductTracker($CompanyId, $dwcount, 'DISCATCPT', $page, $OptionArray);	//DISCATCPT - DISEASE CATEGORY COMPANY PRODUCT TRACKER
					elseif(isset($_REQUEST['TrackerType']) && $_REQUEST['TrackerType'] == 'ICPT')
						print showProductTracker($CompanyId, $dwcount, 'ICPT', $page, $OptionArray);	//ICPT - COMPANY INVESTIGATOR PRODUCT TRACKER
					elseif(isset($_REQUEST['TrackerType']) && ($_REQUEST['TrackerType'] == 'INVESTCT'))
						print showProductTracker($CompanyId, $dwcount, 'INVESTCT', $page, $OptionArray);	
					
					else
						print showProductTracker($CompanyId, $dwcount, 'CPT', $page, $OptionArray);	//CPT = COMPANY PRODUCT TRACKER 
				}
				?>
			</td>
		</tr>
	</table>
	<br/><br/>
	<?php include "footer.php" ?>
</body>
</html>
<?php 
/*
function m_query($n,$q)
{
	global $logger;
	$time_start = microtime(true);
	$res = mysql_query($q);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'TIME:'.$time_taken.'  QUERY:'.$q.'  LINE# '.$n;
	$logger->debug($log);
	unset($log);
	return $res;
}*/

/* Function to get Trials count from Products id */
function GetTrialsCountForCompany($productIds)
{
	global $db;
	global $now;
	$impArr = implode("','", $productIds);	
	$TrialsCount = 0;
	$query = "SELECT count(Distinct(dt.`larvol_id`)) as trialCount FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`)  WHERE et.`entity` in('" . $impArr . "')";
	$res = mysql_query($query) or die($query.'- Bad SQL query getting trials count for Products ids in Sigma Companys Page');

	if($res)
	{
		while($row = mysql_fetch_array($res))
			$TrialsCount = $row['trialCount'];
	}
	return $TrialsCount;
}
/* Function to get Trials count from Products id */
function GetNewsCountForCompany($productIds)
{
	global $db;
	global $now;
	$impArr = implode("','", $productIds);
	$NewsCount = 0;
	$query = "SELECT count(Distinct(dt.`larvol_id`)) as newsCount FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`) JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) WHERE et.`entity` in('" . $impArr . "')";
	$res = mysql_query($query) or die($query.'-> Bad SQL query getting trials count for Products ids in Sigma Companys Page');

	if($res)
	{
		while($row = mysql_fetch_array($res))
			$NewsCount = $row['newsCount'];
	}
	if ($NewsCount > 50) $NewsCount = 50;
	return $NewsCount;
}
?>
