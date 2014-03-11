<?php
	define("SIGMA","1");
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
	require_once('investigator_tracker.php');
	
	require_once('company_tracker.php');
	require_once('moa_tracker.php');
	require_once('news_tracker.php');
	$page = 1;
	
	if($_REQUEST['DiseaseId'] != NULL && $_REQUEST['DiseaseId'] != '' && isset($_REQUEST['DiseaseId']))
	{
		$DiseaseId = $_REQUEST['DiseaseId'];
		$query = 'SELECT `name`, `id`, `display_name` FROM `entities` WHERE `class` = "Disease" AND `id`=' . mysql_real_escape_string($DiseaseId);
		
		$res = mysql_query($query) or die( $query . ' '.mysql_error());
		$header = mysql_fetch_array($res);
		$DiseaseId = $header['id'];
		$DiseaseName = $header['name'];
		if($header['display_name'] != NULL && $header['display_name'] != '')
				$DiseaseName = $header['display_name'];	
				
		if(isset($_REQUEST['dwcount']))
			$dwcount = $_REQUEST['dwcount'];
		else
			$dwcount = 'total';
		if(isset($_REQUEST['dwIcount']))
			$dwIcount = $_REQUEST['dwIcount'];
		else
			$dwIcount = 'total';
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
	$tabCommonUrl = 'disease.php?DiseaseId='.$DiseaseId;
	
	$sqlGetTabs = "SELECT * from tabs where entity_id = $DiseaseId AND  table_name = 'entities'";
	$resGetTabs = mysql_query($sqlGetTabs) or die($sqlGetTabs.'- Bad SQL query');
	$rowGetTabs = mysql_fetch_assoc($resGetTabs);
	$TabCompanyCount = $rowGetTabs['companies'];
	$TabProductCount = $rowGetTabs['products'];
	$TabMOACount = $rowGetTabs['moas'];
	$TabTrialCount = $rowGetTabs['trials'];
	$TabNewsCount = $rowGetTabs['news'];
	$TabInvCount = $rowGetTabs['investigators'];
	
	$meta_title = 'Larvol Sigma'; //default value
	$meta_title = isset($DiseaseName) ? $DiseaseName. ' - '.$meta_title : $meta_title;	
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
				<?php print $DiseaseName; ?>
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
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td>	
				<table cellpadding="0" cellspacing="0" id="disease_tabs">
					<tr>
						<?php
						
						$CountExt = (($TabCompanyCount == 1) ? 'Company':'Companies');
						$compLinkName = '<a href="'.$tabCommonUrl.'&tab=Companies" title="'.$TabCompanyCount.' '.$CountExt.'">&nbsp;'.$TabCompanyCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabProductCount == 1) ? 'Product':'Products');
						$prodLinkName = '<a href="'.$tabCommonUrl.'&tab=Products" title="'.$TabProductCount.' '.$CountExt.'">&nbsp;'.$TabProductCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabMOACount == 1) ? 'Mechanism of Action':'Mechanisms of Action');
						$moaLinkName = '<a href="'.$tabCommonUrl.'&tab=MOAs" title="'.$TabMOACount.' '.$CountExt.'">&nbsp;'.$TabMOACount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabTrialCount == 1) ? 'Trial':'Trials');
						$ottLinkName = '<a href="'.$tabCommonUrl.'&tab=DiseaseOTT" title="'.$TabTrialCount.' '.$CountExt.'">&nbsp;'.$TabTrialCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabInvCount == 1) ? 'Investigator':'Investigators');
						$InvLinkName = '<a href="'.$tabCommonUrl.'&tab=Investigators" title="'.$TabInvCount.' '.$CountExt.'">&nbsp;'.$TabInvCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$ohmLinkName = '<a href="'.$tabCommonUrl.'&tab=DiseaseOHM" title="Heatmap">&nbsp;Heatmap&nbsp;</a>';
						$CountExt = (($TabNewsCount == 1) ? 'News':'News');
						$newsLinkName = '<a href="'.$tabCommonUrl.'&tab=News" title="'.$TabNewsCount.' '.$CountExt.'">&nbsp;'.$TabNewsCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						
						if($tab == 'Companies') {  ?>
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
								<img id="InvestigatorsImg" src="../images/afterTab.png" />
							</td>
							<td id="InvestigatorsTab" class="Tab"><?php print $InvLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/afterTab.png" />
							</td>
							<td id="DiseaseOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="DiseaseOHMImg" src="../images/afterTab.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> -->
							<td>
								<img id="NewsImg" src="../images/afterTab.png" />
							</td>
							<td id="NewsTab" class="Tab"><?php print $newsLinkName; ?></td> 
							<td>
								<img id="lastImg" src="../images/lastTab.png" />
							</td>		
						<?php 
						} else if($tab == 'Products') {  ?>
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
								<img id="InvestigatorsImg" src="../images/afterTab.png" />
							</td>
							<td id="InvestigatorsTab" class="Tab"><?php print $InvLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/afterTab.png" />
							</td>
							<td id="DiseaseOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="DiseaseOHMImg" src="../images/afterTab.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> -->
							<td>
								<img id="NewsImg" src="../images/afterTab.png" />
							</td>
							<td id="NewsTab" class="Tab"> <?php print $newsLinkName; ?></td> 
							<td>
								<img id="lastImg" src="../images/lastTab.png" />
							</td> 
							<td></td>
						<?php } else if($tab == 'MOAs') {  ?>
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
									<img id="InvestigatorsImg" src="../images/selectTabConn.png" />
								</td>
								<td id="InvestigatorsTab" class="Tab"><?php print $InvLinkName; ?></td>
								<td>
									<img id="DiseaseOTTImg" src="../images/afterTab.png" />
								</td>
								<td id="DiseaseOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
								<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
								<!-- <td><img id="DiseaseOHMImg" src="../images/afterTab.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> -->
								<td>
								<img id="NewsImg" src="../images/afterTab.png" />
								</td>
								<td id="NewsTab" class="Tab"> <?php print $newsLinkName; ?> </td>
								<td>
									<img id="lastImg" src="../images/lastTab.png" />
								</td> 
								<td></td>
						<?php } else if($tab == 'Investigators') {  ?>
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
								<img id="InvestigatorsImg" src="../images/middleTab.png" />
							</td>
							<td id="InvestigatorsTab" class="selectTab"><?php print $InvLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/selectTabConn.png" />
							</td>
							<td id="DiseaseOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="DiseaseOHMImg" src="../images/selectTabConn.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> -->
							<td>
								<img id="NewsImg" src="../images/afterTab.png" />
							</td>
							<td id="NewsTab" class="Tab"><?php print $newsLinkName; ?></td>
							<td>
								<img id="lastImg" src="../images/lastTab.png" />
							</td>
							<td></td>
						<?php } else if($tab == 'DiseaseOTT') {  ?>
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
								<img id="InvestigatorsImg" src="../images/afterTab.png" />
							</td>
							<td id="InvestigatorsTab" class="Tab"><?php print $InvLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/middleTab.png" />
							</td>
							<td id="DiseaseOTTTab" class="selectTab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="DiseaseOHMImg" src="../images/selectTabConn.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> -->
							<td>
								<img id="lastImg" src="../images/selectTabConn.png" />
							</td>
							<td id="NewsTab" class="Tab"><?php print $newsLinkName; ?></td>
							<td>
								<img id="lastImg" src="../images/lastTab.png" />
							</td>
							<td></td>
						<?php } else if($tab == 'DiseaseOHM') {  ?>
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
								<img id="lastImg" src="../images/selectTabConn.png" />
							</td>
							<td id="NewsTab" class="Tab"><?php print $newsLinkName; ?></td>
							<td>
								<img id="lastImg" src="../images/lastTab.png" />
							</td>
							<td></td>
						<?php } else if($tab == 'News') {  ?>
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
								<img id="InvestigatorsImg" src="../images/afterTab.png" />
							</td>
							<td id="InvestigatorsTab" class="Tab"><?php print $InvLinkName; ?></td>
							<td>
								<img id="DiseaseOTTImg" src="../images/afterTab.png" />
							</td>
							<td id="DiseaseOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="DiseaseOHMImg" src="../images/selectTabConn.png" /></td><td id="DiseaseOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> -->
							<td>
								<img id="lastImg" src="../images/middleTab.png" />
							</td>
							<td id="NewsTab" class="selectTab"><?php print $newsLinkName; ?></td>
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
					
					//DPT=DISEASE PRODUCT TRACKER 
					if($tab == 'Products')
					{
						chdir ("..");
						print '<div id="Products" align="center">'.showProductTracker($DiseaseId, $dwcount, 'DPT', $page).'</div>';
						chdir ("$cwd");
					}
					
					//DIT=DISEASE INVESTIGATOR TRACKER
					if($tab == 'Investigators')
						print '<div id="Investigators" align="center">'.showInvestigatorTracker($DiseaseId, $dwIcount, 'DIT', $page).'</div>'; 
					
					//DCT=DISEASE COMPANY TRACKER
					if($tab == 'Companies') 
						print '<div id="Companies" align="center">'.showCompanyTracker($DiseaseId, 'DCT', $page).'</div>';  
					
					//DMT=DISEASE MOA TRACKER 
					if($tab == 'MOAs') 
						print '<div id="MOAs" align="center">'.showMOATracker($DiseaseId, 'DMT', $page).'</div>'; 
					
					if($tab == 'DiseaseOTT') { 
						chdir ("..");
						print '<div id="DiseaseOTT" align="center">'; DisplayOTT(); print '</div>'; 
						chdir ("$cwd");
					}
					//DCT=DISEASE NEWS TRACKER
					if($tab == 'News')
						print '<div id="News" align="left">'.showNewsTracker($DiseaseId, 'DNT', $page).'</div>';
					//DOHM=DISEASE ONLINE HEATMAP 
					if($tab == 'DiseaseOHM')
						//print '<div id="DiseaseOHM" align="center">'; DisplayOHM($DiseaseId, 'DOHM').'</div>';
					?>
					
				</div>
			</td>
		</tr>
	</table>
<?php
if($tab != 'DiseaseOTT')
print '<br/><br/>';
include "footer.php";

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
/* Function to get News count from Disease id */
function GetNewsCountFromDisease($DiseaseID)
{
	global $db;
	global $now;
	$NewsCount = 0;
	$query = "SELECT count(dt.`larvol_id`) as newsCount FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`) JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) WHERE et.`entity`='" . mysql_real_escape_string($DiseaseID) . "'";
	$res = mysql_query($query) or die('Bad SQL query getting trials count from Disease id in TZ');

	if($res)
	{
		while($row = mysql_fetch_array($res))
			$NewsCount = $row['newsCount'];
	}
	if ($NewsCount > 50) $NewsCount = 50;
	return $NewsCount;
}

?>
</body>
</html>
