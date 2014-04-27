<?php
	define("SIGMA","1");
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
	$CompanyId = NULL;
	
	if($_REQUEST['CompanyId'] != NULL && $_REQUEST['CompanyId'] != '' && isset($_REQUEST['CompanyId']))
	{
		$CompanyId = $_REQUEST['CompanyId'];
		$query = 'SELECT `name`, `id`, `display_name` FROM `entities` WHERE `id`=' . mysql_real_escape_string($CompanyId);
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

	$TabTrialCount = $TabNewsCount = $TabInvestigatorCount = $TabProductCount = 0;
	$productIds = array();	
	$categoryFlag = (isset($_REQUEST['category']) ? $_REQUEST['category'] : 0);
	$tabCommonUrl = 'company.php?CompanyId='.$CompanyId;
	$tabOTTUrl    = 'company.php?e1='.$CompanyId;
	if($CompanyId !=NULL)
	{	
		$sqlGetTabs = "SELECT * from tabs where entity_id = $CompanyId AND  table_name = 'entities'";
		$resGetTabs = mysql_query($sqlGetTabs) or die($sqlGetTabs.'- Bad SQL query');
		$rowGetTabs = mysql_fetch_assoc($resGetTabs);
		
		if($categoryFlag == 1){
			$TabDiseaseCount = $rowGetTabs['diseases_categories'];
		}
		else{
			$TabDiseaseCount = $rowGetTabs['diseases'];
		}
		$TabTrialCount = $rowGetTabs['trials'];
		$TabNewsCount = $rowGetTabs['news'];
		$TabInvestigatorCount = $rowGetTabs['investigators'];
		$TabProductCount = $rowGetTabs['products'];
		
		$companyProducts = getcompanyProducts($CompanyId);
		$productIds = array_keys($companyProducts);
	}
	
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
	if((!isset($DiseaseId) || $DiseaseId == NULL) && (!isset($InvestigatorId) || $InvestigatorId == NULL) && (!isset($phase) || $phase == NULL))
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
					if($tab == 'diseasetrac')
						print '<div id="diseaseTab_content" align="center">'.showDiseaseTracker($CompanyId, 'CDT', $page, $categoryFlag, $disease);//CDT= COMPANY DISEASE TRACKER
					else if($tab == 'investigatortrac')
						print '<div id="diseaseTab_content" align="center">'.showInvestigatorTracker($CompanyId, 'CIT', $page);		//CIT= COMPANY INVESTIGATOR TRACKER
					else if($tab == 'newstrac')
						print '<div id="diseaseTab_content" align="left">'.showNewsTracker($CompanyId, 'CNT', $page);		//CNT = COMPANY NEWS TRACKER  showNewsTracker
					else if($tab == 'OTTtrac'){
						chdir ("..");
						print '<div id="diseaseTab_content" align="center">'; DisplayOTT(); //SHOW OTT
						chdir ("$cwd");
					}
					else {
						//$data_matrix = dataGeneratorForCPT($CompanyId, $CompanyName, $dwcount, 'CPT', $page, $OptionArray);
						print '<div id="diseaseTab_content" align="center">'.showProductTracker($CompanyId, $dwcount, 'CPT', $page, $OptionArray);	//CPT = COMPANY PRODUCT TRACKER 
					}
						
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
function GetProductsAndTrialsForCompany($productIds)
{
	global $db;
	global $now;
	$impArr = implode("','", $productIds);
	$TrialsCount = 0;
	global $allTrials;
	global $entityTrials;
	$query = "SELECT dt.`larvol_id`,et.`entity`, dt.`is_active`, dt.`phase`, dt.`institution_type`,et.relation_type as relation_type FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`)  WHERE et.`entity` in ('".$impArr."')";
	
	//echo $query;
	
	$res = mysql_query($query) or die($query.'- Bad SQL query getting trials count for Products ids in Sigma Companys Page');
		
	if($res)
	{
		while($row = mysql_fetch_array($res)) {
			$allTrials[$row['larvol_id']] = $row['larvol_id'];
			$entityTrials[$row['entity']][$row['larvol_id']]['is_active'] = $row['is_active'];
			$entityTrials[$row['entity']][$row['larvol_id']]['phase'] = $row['phase'];
			$entityTrials[$row['entity']][$row['larvol_id']]['institution_type'] = $row['institution_type'];
			$entityTrials[$row['entity']][$row['larvol_id']]['relation_type'] = $row['relation_type'];
		}
	}
	
	$return[] = count($allTrials);
	$return[] = count($entityTrials);
	
	return $return;
}
/* Function to get Trials count from Products id */
function GetNewsCountForCompany($productIds)
{
	global $db;
	global $now;
	global $allTrials;
	$NewsCount = 0;
	if(count($allTrials)) {
		$allNewsTrials = implode("','", $allTrials);
		$query = "SELECT n.`larvol_id` FROM `news` n  WHERE n.larvol_id in('".$allNewsTrials."') LIMIT 0, 50";
		$res = mysql_query($query) or die($query.'-> Bad SQL query getting news count in Sigma Companys Page');

		if($res){
			$NewsCount = mysql_num_rows($res);
		}
		if ($NewsCount > 50) $NewsCount = 50;
	}
	return $NewsCount;
}


function dataGeneratorForCPT($CompanyId, $CompanyName, $dwcount, $TrackerType, $page, $OptionArray) {
	
	global $companyProducts;
	global $entityTrials;
	
	//IMP DATA
	$data_matrix=array();
	
	///// No of columns in our graph
	$columns = 10;
	$inner_columns = 10;
	$column_width = 80;
	$max_count = 0;
	
	$max_count = 0;
	
	$Report_DisplayName = $CompanyName;
	$id = $CompanyId;
	$ExtName = GetReportNameExtension($OptionArray);	
	$Report_DisplayName = $ExtName['ReportName1'] . $Report_DisplayName . $ExtName['ReportName2'];
	
	if($CompanyName != NULL && trim($CompanyName) != '')
	{
		$CompanyName = ' / '.$CompanyName;
	} 
	
	//print_r($companyProducts);
	$i = 0;
	if(count($entityTrials)) {
		foreach($entityTrials as $row => $rval) {
			
			$data_matrix[$i]['productName'] = $companyProducts[$row];
			$data_matrix[$i]['product_CompanyName'] = $CompanyName;
			$data_matrix[$i]['productIds'] = $row;
			$data_matrix[$i]['productTag'] = '';
			
			//print_r($rval['larvol_id']); 
			//exit;
			
			///// Initialize data
			$data_matrix[$i]['active']=0;
						  
			$data_matrix[$i]['total']=0;
						  
			$data_matrix[$i]['indlead']=0;
						 
			$data_matrix[$i]['owner_sponsored']=0;
						 
			$data_matrix[$i]['total_phase_na']=0;
			$data_matrix[$i]['active_phase_na']=0;
			$data_matrix[$i]['indlead_phase_na']=0;
			$data_matrix[$i]['total_phase_0']=0;
			$data_matrix[$i]['active_phase_0']=0;
			$data_matrix[$i]['indlead_phase_0']=0;
			$data_matrix[$i]['total_phase_1']=0;
			$data_matrix[$i]['active_phase_1']=0;
			$data_matrix[$i]['indlead_phase_1']=0;
			$data_matrix[$i]['total_phase_2']=0;
			$data_matrix[$i]['active_phase_2']=0;
			$data_matrix[$i]['indlead_phase_2']=0;
			$data_matrix[$i]['total_phase_3']=0;
			$data_matrix[$i]['active_phase_3']=0;
			$data_matrix[$i]['indlead_phase_3']=0;
			$data_matrix[$i]['total_phase_4']=0;
			$data_matrix[$i]['active_phase_4']=0;
			$data_matrix[$i]['indlead_phase_4']=0;
						 
			$data_matrix[$i]['owner_sponsored_phase_na']=0;
			$data_matrix[$i]['owner_sponsored_phase_0']=0;
			$data_matrix[$i]['owner_sponsored_phase_1']=0;
			$data_matrix[$i]['owner_sponsored_phase_2']=0;
			$data_matrix[$i]['owner_sponsored_phase_3']=0;
			$data_matrix[$i]['owner_sponsored_phase_4']=0;
			
			
			 foreach($rval as $phase_row) {	
			 
				$data_matrix[$i]['total']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$i]['active']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
							$data_matrix[$i]['indlead']++;
						if($phase_row['relation_type'] == 'ownersponsored')
							$data_matrix[$i]['owner_sponsored']++;
					}
						
					if($phase_row['phase'] == 'N/A' || $phase_row['phase'] == '' || $phase_row['phase'] === NULL)
					{
						
						$data_matrix[$i]['total_phase_na']++;
						if($phase_row['is_active'])
						{
							$data_matrix[$i]['active_phase_na']++;
							if($phase_row['institution_type'] == 'industry_lead_sponsor')
								$data_matrix[$i]['indlead_phase_na']++;
							if($phase_row['relation_type'] == 'ownersponsored')
								$data_matrix[$i]['owner_sponsored_phase_na']++;
						}
					}
					else if($phase_row['phase'] == '0')
					{
						$data_matrix[$i]['total_phase_0']++;
						if($phase_row['is_active'])
						{
							$data_matrix[$i]['active_phase_0']++;
							if($phase_row['institution_type'] == 'industry_lead_sponsor')
								$data_matrix[$i]['indlead_phase_0']++;
							if($phase_row['relation_type'] == 'ownersponsored')
								$data_matrix[$i]['owner_sponsored_phase_0']++;
						}
					}
					else if($phase_row['phase'] == '1' || $phase_row['phase'] == '0/1' || $phase_row['phase'] == '1a' 
					|| $phase_row['phase'] == '1b' || $phase_row['phase'] == '1a/1b' || $phase_row['phase'] == '1c')
					{
						
						$data_matrix[$i]['total_phase_1']++;
						if($phase_row['is_active'])
						{
							$data_matrix[$i]['active_phase_1']++;
							if($phase_row['institution_type'] == 'industry_lead_sponsor')
								$data_matrix[$i]['indlead_phase_1']++;
							if($phase_row['relation_type'] == 'ownersponsored')
								$data_matrix[$i]['owner_sponsored_phase_1']++;
						}
					}
					else if($phase_row['phase'] == '2' || $phase_row['phase'] == '1/2' || $phase_row['phase'] == '1b/2' 
					|| $phase_row['phase'] == '1b/2a' || $phase_row['phase'] == '2a' || $phase_row['phase'] == '2a/2b' 
					|| $phase_row['phase'] == '2a/b' || $phase_row['phase'] == '2b' || $phase_row['phase'] == 2)
					{
						
						
						$data_matrix[$i]['total_phase_2']++;
						
						if($phase_row['is_active'])
						{
							$data_matrix[$i]['active_phase_2']++;
							if($phase_row['institution_type'] == 'industry_lead_sponsor')
								$data_matrix[$i]['indlead_phase_2']++;
							if($phase_row['relation_type'] == 'ownersponsored')
								$data_matrix[$i]['owner_sponsored_phase_2']++;
						}
					}
					else if($phase_row['phase'] == '3' || $phase_row['phase'] == '2/3' || $phase_row['phase'] == '2b/3' 
					|| $phase_row['phase'] == '3a' || $phase_row['phase'] == '3b')
					{
						
						$data_matrix[$i]['total_phase_3']++;
						if($phase_row['is_active'])
						{
							$data_matrix[$i]['active_phase_3']++;
							if($phase_row['institution_type'] == 'industry_lead_sponsor')
							$data_matrix[$i]['indlead_phase_3']++;
							if($phase_row['relation_type'] == 'ownersponsored')
							$data_matrix[$i]['owner_sponsored_phase_3']++;
						}
					}
					else if($phase_row['phase'] == '4' || $phase_row['phase'] == '3/4' || $phase_row['phase'] == '3b/4')
					{
						
						$data_matrix[$i]['total_phase_4']++;
						if($phase_row['is_active'])
						{
							$data_matrix[$i]['active_phase_4']++;
							if($phase_row['institution_type'] == 'industry_lead_sponsor')
							$data_matrix[$i]['indlead_phase_4']++;
							if($phase_row['relation_type'] == 'ownersponsored')
							$data_matrix[$i]['owner_sponsored_phase_4']++;
						}	
					}
				}	//// End of while
				if($data_matrix[$i]['total'] > $max_count)
				$max_count = $data_matrix[$i]['total'];
				
				//var_dump($data_matrix);
				
				$i++;
				
		}
	 }
	 
	 $data_matrix = sortTwoDimensionArrayByKey($data_matrix, $dwcount);	//Sort according to default view as other than LI default view is total
	
	$RecordsPerPage = 50;
	$TotalPages = 0;
	$TotalRecords = count($data_matrix);
	if(!isset($_POST['download']))
	{
		$TotalPages = ceil(count($data_matrix) / $RecordsPerPage);
		
		//Get only those product Ids which we are planning to display on current page to avoid unnecessary queries
		$StartSlice = ($page - 1) * $RecordsPerPage;
		$EndSlice = $StartSlice + $RecordsPerPage;
		if(!empty($data_matrix))
		{
			$data_matrix = array_slice($data_matrix, $StartSlice, $RecordsPerPage);
			$rows = array_slice($data_matrix, $StartSlice, $RecordsPerPage);
		}
		else
		{
			$data_matrix=array();
			$rows = array();
		}
	}
	/////////PAGING DATA ENDS
	
	///// No of inner columns
	$original_max_count = $max_count;
	$max_count = ceil(($max_count / $columns)) * $columns;
	$column_interval = $max_count / $columns;
	$inner_columns = 10;
	$inner_width = $column_width  / $inner_columns;
	
	if($max_count > 0)
	$ratio = ($columns * $inner_columns) / $max_count;

	///All Data send
	$Return['matrix'] = $data_matrix;
	$Return['report_name'] = $Report_DisplayName;
	$Return['id'] = $id;
	$Return['rows'] = $data_matrix;
	$Return['columns'] = $columns;
	$Return['ProductIds'] = array_keys($companyProducts);
	$Return['inner_columns'] = $inner_columns;
	$Return['inner_width'] = $inner_width;
	$Return['column_width'] = $column_width;
	$Return['ratio'] = $ratio;
	$Return['entity2Id'] = $entity2Id;
	$Return['column_interval'] = $column_interval;
	$Return['TrackerType'] = $TrackerType;
	$Return['TotalPages'] = $TotalPages;
	$Return['TotalRecords'] = $TotalRecords;
		 
	//var_dump($Return);
	
	return $Return;
	
}

function getcompanyProducts($companyID) {

	$products = array();
	$query = "SELECT  id, name  FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`) 
			WHERE et.`class`='Product' 
			AND er.`child`='" . mysql_real_escape_string($companyID) . "' AND (et.`is_active` <> '0' OR et.`is_active` IS NULL)";
	
	$res = mysql_query($query) or die($query.'- Bad SQL query getting trials count for Products ids in Sigma Companys Page');
	
	if($res)
	{
		while($row = mysql_fetch_array($res)) {
			
			$products[$row['id']] = $row['name'];			
		}
	}
	
	return $products;
}











?>
