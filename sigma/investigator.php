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
	require_once('disease_tracker.php');
	$page = 1;
	if($_REQUEST['TrackerType'] == 'INVESTDT' && $_REQUEST['DiseaseId'] && $_REQUEST['InvestigatorId'] )
	{
		include "searchbox.php";
		$dwcount=$_REQUEST['dwcount'];
		$page=$_REQUEST['page'];
		$OptionArray = array('InvestigatorId'=>$_REQUEST['InvestigtorId'], 'DiseaseId'=>$_REQUEST['DiseaseId'], 'Phase'=> $_REQUEST['phase']);
		print showProductTracker($_REQUEST['DiseaseId'], $dwcount, 'INVESTDT', $page, $OptionArray);	
		return;
	}
	
	if($_REQUEST['InvestigatorId'] != NULL && $_REQUEST['InvestigatorId'] != '' && isset($_REQUEST['InvestigatorId']))
	{
		$InvestigatorId   = $_REQUEST['InvestigatorId'];
		$query          = 'SELECT `name`, `id`, `display_name` FROM `entities` WHERE `class` = "Investigator" AND `id`=' . mysql_real_escape_string($InvestigatorId);
		$res            = mysql_query($query) or die($query.' '.mysql_error());
		$header         = mysql_fetch_array($res);
		$InvestigatorId   = $header['id'];
		$InvestigatorName = $header['name'];
		if($header['display_name'] != NULL && $header['display_name'] != '')
		     $InvestigatorName = $header['display_name'];	
				
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
	$tabCommonUrl		= 'investigator.php?InvestigatorId='.$InvestigatorId;
	
	$CompanyIds			= GetCompaniesFromInvestigator_CompanyTracker($InvestigatorId);
	$product = array();
	$product = DataGenerator($InvestigatorId, 'INVESTPT', $page, $OptionArray, $dwcount);
	$product = array();
	$product = DataGenerator($InvestigatorId, 'INVESTPT', $page, $OptionArray, $dwcount);
	$TabProductCount = $product['TotalRecords'];
	
	$MOAIds				= GetMOAsFromInvestigator($InvestigatorId);
	if(empty($MOAIds))
		$MOAIds = array();
	else
		$MOAIds = array_filter(array_unique($MOAIds));
	$TabMOACount = count($MOAIds);
	

	$disease = array();
	$disease = DataGeneratorForDiseaseTracker($InvestigatorId, 'INVESTDT', $page, $dwcount);
	$TabDiseaseCount = $disease['TotalRecords'];
	
	$CompanyIds = array_filter(array_unique($CompanyIds));
	
	$TabTrialCount   = GetTrialsCountFromInvestigator($InvestigatorId);

	$TabCompanyCount = count($CompanyIds);
	
	$meta_title = 'Larvol Sigma'; //default value
	$meta_title = isset($InvestigatorName) ? $InvestigatorName. ' - '.$meta_title : $meta_title;	
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

		#Investigator_tabs a
		{
			text-decoration:none;
			color:#000000;
			font-size:13px;
			font-family:Arial, Helvetica, sans-serif;
			display:block;
		}

		#InvestigatorTab_content
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
				<?php print $InvestigatorName; ?>
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
	
				<table cellpadding="0" cellspacing="0" id="Investigator_tabs">
					<tr>
						<?php 
						
						$CountExt = (($TabProductCount == 1) ? 'Product':'Products');
						$prodLinkName = '<a href="'.$tabCommonUrl.'&tab=Products" title="'.$TabProductCount.' '.$CountExt.'">&nbsp;'.$TabProductCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabDiseaseCount == 1) ? 'Disease':'Diseases');
						$diseaseLinkName = '<a href="'.$tabCommonUrl.'&tab=Diseases" title="'.$TabDiseaseCount.' '.$CountExt.'">&nbsp;'.$TabDiseaseCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabCompanyCount == 1) ? 'Company':'Companies');
						$compLinkName = '<a href="'.$tabCommonUrl.'&tab=Companies" title="'.$TabCompanyCount.' '.$CountExt.'">&nbsp;'.$TabCompanyCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabMOACount == 1) ? 'Mechanisms of Action':'Mechanisms of Action');
						$moaLinkName = '<a href="'.$tabCommonUrl.'&tab=MOAs" title="'.$TabMOACount.' '.$CountExt.'">&nbsp;'.$TabMOACount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$CountExt = (($TabTrialCount == 1) ? 'Trial':'Trials');
						$ottLinkName = '<a href="'.$tabCommonUrl.'&tab=InvestigatorOTT" title="'.$TabTrialCount.' '.$CountExt.'">&nbsp;'.$TabTrialCount.'&nbsp;'.$CountExt.'&nbsp;</a>';
						$ohmLinkName = '<a href="'.$tabCommonUrl.'&tab=InvestigatorOHM" title="Heatmap">&nbsp;Heatmap&nbsp;</a>';
						
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
								<img id="DiseasesImg" src="../images/afterTab.png" />
							</td>
							<td id="DiseasesTab" class="Tab"><?php print $diseaseLinkName; ?></td>
							
							<td>
								<img id="InvestigatorOTTImg" src="../images/afterTab.png" />
							</td>
							
							<td id="InvestigatorOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="InvestigatorOHMImg" src="../images/afterTab.png" /></td><td id="InvestigatorOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> --> <td><img id="lastImg" src="../images/lastTab.png" /></td> 
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
								<img id="DiseasesImg" src="../images/afterTab.png" />
							</td>
							<td id="DiseasesTab" class="Tab"><?php print $diseaseLinkName; ?></td>
							<td>
								<img id="InvestigatorOTTImg" src="../images/afterTab.png" />
							</td>
							
							<td id="InvestigatorOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="InvestigatorOHMImg" src="../images/afterTab.png" /></td><td id="InvestigatorOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> --> <td><img id="lastImg" src="../images/lastTab.png" /></td> 
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
								<img id="DiseasesImg" src="../images/selectTabConn.png" />
							</td>
							<td id="DiseasesTab" class="Tab"><?php print $diseaseLinkName; ?></td>
							<td>
								<img id="InvestigatorOTTImg" src="../images/afterTab.png" />
							</td>
							<td id="InvestigatorOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="InvestigatorOHMImg" src="../images/afterTab.png" /></td><td id="InvestigatorOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> --> <td><img id="lastImg" src="../images/lastTab.png" /></td> 
							<td></td>
							
						<?php } else if($tab == 'Diseases') { ?>
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
								<img id="DiseasesImg" src="../images/middleTab.png" />
							</td>
							<td id="DiseasesTab" class="selectTab"><?php print $diseaseLinkName; ?></td>
							<td>
								<img id="InvestigatorOTTImg" src="../images/selectTabConn.png" />
							</td>
							<td id="InvestigatorOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="InvestigatorOHMImg" src="../images/afterTab.png" /></td><td id="InvestigatorOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> --> <td><img id="lastImg" src="../images/lastTab.png" /></td> 
							<td></td>
							
						<?php } else if($tab == 'InvestigatorOTT') { ?>
					
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
								<img id="DiseasesImg" src="../images/afterTab.png" />
							</td>
							<td id="DiseasesTab" class="Tab"><?php print $diseaseLinkName; ?></td>
				
							
							<td><img id="InvestigatorOTTImg" src="../images/middleTab.png" /></td>															
							<td id="InvestigatorTab" class="selectTab"><?php print $ottLinkName; ?></td>
							';
							
							
							
							
							<!-- Temporarily disabled the auto HM tab becauase of performance issues (remove html and php comments below to enable it)-->
							<!-- <td><img id="InvestigatorOHMImg" src="../images/selectTabConn.png" /></td><td id="InvestigatorOHMTab" class="Tab"><?php //print $ohmLinkName; ?></td></td> --> <td><img id="lastImg" src="../images/selectLastTab.png" /></td><td></td>
						<?php } else if($tab == 'InvestigatorOHM') { ?>
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
								<img id="InvestigatorOTTImg" src="../images/afterTab.png" />
							</td>
							<td id="InvestigatorOTTTab" class="Tab"><?php print $ottLinkName; ?></td>
							<td>
								<img id="InvestigatorOHMImg" src="../images/middleTab.png" />
							</td>
							<td id="InvestigatorOHMTab" class="selectTab"><?php print $ohmLinkName; ?></td>
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
				<div id="InvestigatorTab_content" align="center">
					<?php 
					if($tab == 'Products')
					{
						chdir ("..");
						global $TabProductCount;
						print '<div id="Products" align="center">'.showProductTracker($InvestigatorId, $dwcount, 'INVESTPT', $page).'</div>';
						chdir ("$cwd");
					}
					if($tab == 'Companies')
					{
						//global $TabCompanyCount;
						print '<div id="Companies" align="center">'.showCompanyTracker($InvestigatorId, 'INVESTCT', $page).'</div>'; 
					}
					
					if($tab == 'MOAs')
						print '<div id="MOAs" align="center">'.showMOATracker($InvestigatorId, 'INVESTMT', $page).'</div>'; 
						
					if($tab == 'Diseases')
						print '<div id="Diseases" align="center">'.showDiseaseTracker($InvestigatorId, 'INVESTDT', $page).'</div>'; 
						
					if($tab == 'InvestigatorOTT')
					{
						chdir ("..");
						print '<div id="InvestigatorOTT" align="center">'; DisplayOTT(); print '</div>'; 
						chdir ("$cwd");
					}
						
					if($tab == 'InvestigatorOHM') 
					{
						
						print '<div id="InvestigatorOHM" align="center">'; DisplayOHM($arrInvestigatorIds, 'DOHM'); print '</div>'; 
					} ?>
				</div>
			</td>
		</tr>
	</table>
	<?php
	if($tab != 'InvestigatorOTT')
	print '<br/><br/>';
	include "footer.php";

	
	function getProductIdsFromInvestigator($inv_id)
	{
		global $db;
		global $now;
		$ProductsCount = 0;
		$query = "	SELECT er.parent from entity_relations er
					JOIN entities e ON (er.child = e.id and e.class='Institution')
					JOIN entity_trials et ON (er.parent = et.entity)
					JOIN entity_trials et2 ON (et.trial = et2.trial and et2.entity=". $inv_id .")";
					
		$res = mysql_query($query) or die('Bad SQL query for getting Investigators from investigator ID '.$query);

		if($res)
		{
			while($row = mysql_fetch_array($res))
				$arrInvestigatorIds[] = $row['parent'];
		}
		return $arrInvestigatorIds;
	}


	function GetTrialsCountFromInvestigator($InvestigatorId)
	{
		global $db;
		global $now;
		$TrialsCount = 0;
		$query = "
					SELECT count(et.trial) as trialCount 
					FROM `entity_trials` et 
					JOIN entities e ON (et.entity=e.id and e.class='Investigator' and et.entity=" . $InvestigatorId .")
				"
					
					
					
					;
		
		$res = mysql_query($query) or die('Bad SQL query getting trials count from Investigator id in TZ '.$query);

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