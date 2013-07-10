<?php
	$cwd = getcwd();
	chdir ("..");
	require_once('db.php');
	include('ss_entity.php');
	chdir ($cwd);
	$RecordsPerPage = 50;
	$SearchFlg = false;
	$DiseaseFlg = false;
	$globalOptions['page'] = 1;
	$DataArray = $CurrentPageResultArr = array();
	$totalPages = $FoundRecords = 0;
	$ResultArr = array();
	
	if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
	{
		$globalOptions['page'] = mysql_real_escape_string($_REQUEST['page']);
	}
	
	//print '<br/><br/>';
	if($_REQUEST['TzSearch'] != NULL && $_REQUEST['TzSearch'] != '' && isset($_REQUEST['TzSearch']))
	{
		$SearchFlg = true;
		$globalOptions['TzSearch'] = $_REQUEST['TzSearch'];
		
		$ResultArr = find_entity($globalOptions['TzSearch']);
		
		//To remove repeated MOAs / Non-Industry Institutions
		$MOAArray = array();
		$MOACatArray = array();
		$NonIndustryArray = array();
		$NonActiveProductArray = array();
		$NonMeshDiseaseArray = array();
		if(is_array($ResultArr))
		{
			$GetDataQuery = "SELECT `id`, `name`, `class`, `category`, `is_active`, `mesh_name` FROM `entities` WHERE id IN (" . implode(',',$ResultArr) . ") ";
			//print $GetDataQuery;
			$GetDataQueryResult = mysql_query($GetDataQuery);
			if($GetDataQueryResult)
			{
				while($GetDataResult =  mysql_fetch_assoc($GetDataQueryResult))
				{ 
					if($GetDataResult['class'] == 'MOA_Category')
					{
						$MOACatArray[] = $GetDataResult['id'];	
					}
					
					if($GetDataResult['class'] == 'Institution')
					{
						if($GetDataResult['category'] != 'Industry')	//Remove Non-Industry Institutions
						$NonIndustryArray[] = $GetDataResult['id'];	
					}
					
					if($GetDataResult['class'] == 'Product')
					{
						if($GetDataResult['is_active'] === '0')	//Remove Non Active Products
						$NonActiveProductArray[] = $GetDataResult['id'];	
					}
					
					if($GetDataResult['class'] == 'Disease')
					{
						if(trim($GetDataResult['mesh_name']) == '' && $GetDataResult['mesh_name'] == NULL)	//Remove Non Mesh Diseases
						$NonMeshDiseaseArray[] = $GetDataResult['id'];	
					}
				}
			}
			
			if(count($MOACatArray) > 0)
			{
				$MOAQuery = "SELECT `id`, `name` FROM `entities` e JOIN `entity_relations` er ON(e.`id`=er.`child`) WHERE e.`class`='MOA' AND er.`parent` IN ('" . implode("','",$MOACatArray) . "')";			
				$MOAResult = mysql_query($MOAQuery);
				if($MOAResult &&  mysql_num_rows($MOAResult) > 0)
				{
					while($SMOA = mysql_fetch_assoc($MOAResult))
					$MOAArray[] = $SMOA['id'];
				}	
			}			
			$ResultArr = array_diff($ResultArr, $MOAArray);	//Remove moas if its category present
			
			$ResultArr = array_diff($ResultArr, $NonIndustryArray);	//Remove Non-Industry Institutions	
			
			$ResultArr = array_diff($ResultArr, $NonActiveProductArray);	//Remove Non Active Products
			
			$ResultArr = array_diff($ResultArr, $NonMeshDiseaseArray);	//Remove Non Mesh Diseases
		}
		///End of remove repeated moas / Non-Industry Institutions
		
		if(is_array($ResultArr))
		$FoundRecords = count($ResultArr);
		
		if($FoundRecords > 0)
		{
			$totalPages = ceil(count($ResultArr) / $RecordsPerPage);
			
			//Get only those product Ids which we are planning to display on current page to avoid unnecessary queries
			$StartSlice = ($globalOptions['page'] - 1) * $RecordsPerPage;
			$EndSlice = $StartSlice + $RecordsPerPage;
			$CurrentPageResultArr = array_slice($ResultArr, $StartSlice, $RecordsPerPage);
			
			foreach($CurrentPageResultArr as $index=> $value)
			{
				$result =  mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `class`, `display_name` FROM `entities` WHERE id = '" . mysql_real_escape_string($value) . "' "));
				$DataArray[$index]['index'] = $index;
				$DataArray[$index]['name'] = $result['name'];
				$DataArray[$index]['id'] = $result['id'];
				$DataArray[$index]['type'] = $result['class'];
				if($result['display_name'] != NULL && $result['display_name'] != '' && $DataArray[$index]['type'] != 'Product')
					$DataArray[$index]['name'] = $result['display_name'];
			}
		}
	}
	else if($_REQUEST['class'] != NULL && $_REQUEST['class'] != '' && isset($_REQUEST['class']))
	{
		//Make Char code
		$AlphaData = array();
		$Char = 'A';
		for($c=0; $c < 26; $c++) { $AlphaData[$Char]['Char']=$Char; $AlphaData[$Char]['Active']=false; $AlphaData[$Char]['Data']=array(); $Char++; }
		$AlphaData['Other']['Char'] = 'Other'; $AlphaData['Other']['Active']=false; $AlphaData['Other']['Data']=array();
		//End Char code
		
		$globalOptions['class'] = $_REQUEST['class'];
		$ClassFlg = true;
		
		if($globalOptions['class'] == 'MOA')
		$ResultArrQuery = "SELECT DISTINCT(`id`), `name`, `class`, `display_name` FROM `entities` WHERE `class` = 'MOA_Category' AND `name` <> 'Other'";
		else if($globalOptions['class'] == 'Disease')
		$ResultArrQuery = "SELECT DISTINCT(e.`id`), e.`name`, e.`class`, e.`display_name` FROM `entities` e JOIN `entity_relations` er ON(er.`parent`=e.`id`) JOIN `entities` e2 ON(e2.`id`=er.`child`) WHERE e.`class` = 'Disease' AND e2.`class` = 'Product'  AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL) AND (e.`mesh_name` IS NOT NULL AND e.`mesh_name` <> '')";
		else if($globalOptions['class'] == 'Product')
		$ResultArrQuery = "SELECT DISTINCT(`id`), `name`, `class`, `display_name`, `category` FROM `entities` WHERE `class` = '".$globalOptions['class']."'  AND (`is_active` <> '0' OR `is_active` IS NULL)";
		else
		$ResultArrQuery = "SELECT DISTINCT(`id`), `name`, `class`, `display_name`, `category` FROM `entities` WHERE `class` = '".$globalOptions['class']."'";
		
		$QueryResult = mysql_query($ResultArrQuery);
		
		$i=0;
		while($result = mysql_fetch_assoc($QueryResult))
		{
			if($result['class'] != 'Institution' || ($result['category'] == 'Industry' && $result['class'] == 'Institution'))	//AVOID NON-INDUSTRY INSTITUTIONS
			{
				$i++;
				$DataArray[$i]['index'] = $i;
				$DataArray[$i]['name'] = $result['name'];
				$DataArray[$i]['id'] = $result['id'];
				$DataArray[$i]['type'] = $result['class'];
				if($result['display_name'] != NULL && $result['display_name'] != '' && $DataArray[$index]['type'] != 'Product')
					$DataArray[$i]['name'] = $result['display_name'];
				
				//DETECT FIRST CHAR AND ACTIVATE FALG FOR THAT CHAR AND SEPARATE OUT DATA OF FOR EACH CHAR
				$ch1 = strtoupper(substr(trim($DataArray[$i]['name']),0,1));
				if(array_key_exists($ch1,$AlphaData))	{ $AlphaData[$ch1]['Active'] = true; $AlphaData[$ch1]['Data'][] = $DataArray[$i]; }
				else { $AlphaData['Other']['Active'] = true; $AlphaData['Other']['Data'][] = $DataArray[$i]; }
				//END OF PART
			}		
		}
		
		/**
		 * Holds the information of entity URLs and entity names.
		 * @var $arrTZBarLink 
		*/
		
		$arrTZBarLink =array("Institution"=> array("url"=>"index.php?class=Institution","name"=>"Companies"),
							 "MOA"		  => array("url"=>"index.php?class=MOA","name"=>"Mechanisms of Action"),
							 "Disease"	  => array("url"=>"index.php?class=Disease","name"=>"Diseases"),
							 "Product"	  => array("url"=>"index.php?class=Product", "name"=>"Products")				
							);
		
		
		if($globalOptions['class'] == 'MOA')	//IN CASE OF MOA  - GET MOA ID AS WELL WHO DOES NOT HAVE CATEGORY OR has Other Category
		{
			$ResultArrQuery = "SELECT DISTINCT(e.`id`), e.`name`, e.`class`, e.`display_name` FROM `entities` e LEFT OUTER JOIN `entity_relations` er ON(e.`id`=er.`child`) LEFT OUTER JOIN `entities` e2 ON(e2.`id`=er.`parent`) WHERE e.`class`='MOA' AND ((e2.`name` = 'Other' AND e2.`class` = 'MOA_Category') OR er.`parent` IS NULL)";
		
			$QueryResult = mysql_query($ResultArrQuery);
		
			while($result = mysql_fetch_assoc($QueryResult))
			{
				$i++;
				$DataArray[$i]['index'] = $i;
				$DataArray[$i]['name'] = $result['name'];
				$DataArray[$i]['id'] = $result['id'];
				$DataArray[$i]['type'] = $result['class'];
				if($result['display_name'] != NULL && $result['display_name'] != '')
					$DataArray[$i]['name'] = $result['display_name'];
				
				//DETECT FIRST CHAR AND ACTIVATE FALG FOR THAT CHAR AND SEPARATE OUT DATA OF FOR EACH CHAR
				$ch1 = strtoupper(substr(trim($DataArray[$i]['name']),0,1));
				if(array_key_exists($ch1,$AlphaData))	{ $AlphaData[$ch1]['Active'] = true; $AlphaData[$ch1]['Data'][] = $DataArray[$i]; }
				else { $AlphaData['Other']['Active'] = true; $AlphaData['Other']['Data'][] = $DataArray[$i]; }
				//END OF PART	
			}
		}
		
		if(isset($_REQUEST['Alpha']))
		{
			//MAKE CURRENT DATASET EQUAL TO THAT CHAR DATA
			$globalOptions['Alpha'] = $_REQUEST['Alpha'];
			$DataArray = $AlphaData[$globalOptions['Alpha']]['Data'];
		}
		
		$FoundRecords = count($DataArray);
		$totalPages = ceil($FoundRecords / $RecordsPerPage);
		$StartSlice = ($globalOptions['page'] - 1) * $RecordsPerPage;
		$EndSlice = $StartSlice + $RecordsPerPage;
		
		$DataArray = sortTwoDimensionArrayByKey($DataArray, 'name');	//SORT ARRAY USING PHP AS SOME RECORDS MAY NOT HAVE DISPLAY NAME
			
		$CurrentPageResultArr = array_slice($DataArray, $StartSlice, $RecordsPerPage);
		$DataArray = $CurrentPageResultArr;
		
		if($globalOptions['class'] == 'Institution')
			$globalOptions['classType'] = 'Companies';
		else
			$globalOptions['classType'] = $globalOptions['class'].'s';
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

.SearchBox
{
	/*outline:none;*/
	height:27px;
	width:600px;
}
.SearchBox:focus
{
	box-shadow:inset 0px 1px 2px rgba(0,0,0,0.3);
	-moz-box-shadow:inset 0px 1px 2px rgba(0,0,0,0.3);
	-webkit-box-shadow:inset 0px 1px 2px rgba(0,0,0,0.3);
	-moz-border-radius:1px;
	-webkit-border-radius:1px;
	border-radius:1px;
	border:1px solid #4d90fe;
	outline:none;
	height:27px;
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

.pagination {
	line-height: 1.6em;
	width:100%;
	float:none;
	margin-right:10px;
	float: left; 
	padding-top:2px; 
	vertical-align:bottom;
	font-weight:bold;
	padding-bottom:25px;
	color:#4f2683;
}

.pagination a:hover {
	background-color: #aa8ece;
	color: #FFFFFF;
	font-weight:bold;
}

.pagination a {
	margin: 0 2px;
	border: 1px solid #CCC;
	background-color:#4f2683;
	font-weight: bold;
	padding: 2px 5px;
	text-align: center;
	color: #FFFFFF;
	text-decoration: none;
	display:inline;
}

.pagination span {
	padding: 2px 5px;
}

.alpharow {
	line-height: 1.6em;
	width:100%;
	float:none;
	margin-right:10px;
	float: left; 
	padding-top:4px; 
	vertical-align:bottom;
	font-weight:bold;
	padding-bottom:4px;
	color:#4f2683;
}

.alpharow a:hover {
	background-color: #aa8ece;
	color: #FFFFFF;
	font-weight:bold;
}

.alpharow a {
	margin: 0 2px;
	border: 1px solid #CCC;
	/*background-color:#4f2683;*/
	font-weight: bold;
	padding: 2px 5px;
	text-align: center;
	color: #4f2683;
	text-decoration: none;
	display:inline;
}

.alphanormal {
	font-weight:normal;
	color:#000000;
	font-size:13px;
}

.alpharow span {
	padding: 2px 5px;
}

.searchTypes
{
	font-weight:bold;
	font-size:12px;
}

.autocomplete-w1 
{ 
	background:url(../images/shadow.png) no-repeat bottom right; 
	position:absolute; 
	top:0px; 
	left:0px; 
	margin:8px 0 0 6px; 
	/* IE6 fix: */ _background:none; _margin:0; 
}
.autocomplete 
{ 
	border:1px solid #999; 
	background:#FFF; 
	cursor:default; 
	text-align:left; 
	max-height:350px; 
	overflow:auto; 
	margin:-6px 6px 6px -6px; 
	/* IE6 specific: */ 
	_height:350px;  
	_margin:0;
	_overflow-x:hidden;
}
.autocomplete .selected { 
	background:#F0F0F0; 
}
.autocomplete div { 
	padding:2px 5px; 
	white-space:wrap;
}
.autocomplete strong { 
	font-weight:normal; 
	color:#3399FF; 
}
	
</style>
<script type="text/javascript" src="scripts/jquery-1.7.2.min.js"></script>
</head>
<body>
<?php include "searchbox.php";?>
<!-- Number of Results -->
<br/>
<table width="100%" border="0" class="FoundResultsTb">
	<tr>
	   <?php if ( !empty($_REQUEST["class"]) && array_key_exists($_REQUEST["class"], $arrTZBarLink)) { ?>
    	<td width="50%" align="left" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; font-size:23px; vertical-align:middle;">
        	<table><tbody><tr>
        	<td style="vertical-align:top;"><a href="<?php print $arrTZBarLink[$_REQUEST["class"]]["url"];?>" style="color:#FFFFFF; display:inline; text-decoration:underline;"><?php print $arrTZBarLink[$_REQUEST["class"]]["name"]; ?></a>&nbsp;</td>            </tr></tbody></table>
        </td> <?php }    elseif ( !empty($_REQUEST["TzSearch"]) && ($FoundRecords > 0) ) { ?>
    	<td width="50%" align="left" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; font-size:23px; vertical-align:middle;">
        	<table><tbody><tr>
        	<td style="vertical-align:top;"><a href="<?php echo "index.php?TzSearch=".$_REQUEST["TzSearch"];?>" style="color:#FFFFFF; display:inline; text-decoration:underline;"><?php print $_REQUEST["TzSearch"]; ?></a>&nbsp;</td>            </tr></tbody></table>
        </td> <?php } else { ?>
        <td width="50%" align="left" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; font-size:23px; vertical-align:middle;">
        	<table><tbody><tr>
        	<td style="vertical-align:top;">&nbsp;</td>            </tr></tbody></table>
        </td>
        <?php  } ?>
        <td width="50%" style="border:0; font-weight:bold; padding-right:5px;" align="right">
        	<?php
            	if($FoundRecords > 0)
				{
					$showResult = 'Showing';
					if($totalPages == 1)
					{
						$showResult .= ' '. ($StartSlice + count($CurrentPageResultArr)) .' Result'.(($FoundRecords > 1) ? 's':'');	
					}
					else
					{
						$showResult .= ' Results '. ($StartSlice + 1) .' - '. ($StartSlice + count($CurrentPageResultArr));
						$showResult .= ' of about '. $FoundRecords;
					}
					
					
					if($globalOptions['Alpha'] != '')
					$showResult .= ' Starting with Letter '. (($globalOptions['Alpha'] == 'Other') ? 'Other than "A-Z"' : '"'.$globalOptions['Alpha'] .'"');
					
					print $showResult;
				}
			?>
        </td>
    </tr>
</table>
<?php
if($ClassFlg)
{
	print '<br/><table align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td style="border-top:#CCCCCC solid 1px; border-bottom:#CCCCCC solid 1px;"><div class="alpharow"><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;See '. $globalOptions['classType'] .' by First Letter&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
						
	foreach($AlphaData as $key=> $Alpha)
	{
		if($globalOptions['Alpha'] == $key)
		{ print '<a href="index.php?class='.$globalOptions['class'].'&Alpha='.$key.'" style="background-color: #4f2683; color:#FFFFFF;">'.$key.'</a>'; }
		else if($Alpha['Active'])
		{ print '<a href="index.php?class='.$globalOptions['class'].'&Alpha='.$key.'">'.$key.'</a>'; } 
		else
		{ print '<span class="alphanormal">'.$key.'</span>'; }
	}
	
	if(!isset($globalOptions['Alpha']) || trim($globalOptions['Alpha']) == '' || $globalOptions['Alpha'] == NULL)
	print '<a href="index.php?class='.$globalOptions['class'].'" style="background-color: #4f2683; color:#FFFFFF;">All</a>';
	else
	print '<a href="index.php?class='.$globalOptions['class'].'">All</a>';
	
	print '			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div></td>
				</tr>
			</table><br/>';
}
?>

<!-- Displaying Records -->
<br/>
<?php
	foreach($CurrentPageResultArr as $index=> $data)
	{
		if($DataArray[$index]['id'] != '' && $DataArray[$index]['id'] != '' && $FoundRecords > 0)
		{
			if($DataArray[$index]['type'] == 'Institution' || $DataArray[$index]['type'] == 'MOA' || $DataArray[$index]['type'] == 'Product' || $DataArray[$index]['type'] == 'Disease' || $DataArray[$index]['type'] == 'MOA_Category')	// avoid displying row for other types
			{
				print'<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
						<td align="left"  width="100px" style="vertical-align:top;">
								<img src="../images/'.$DataArray[$index]['type'].'arrow.gif" style="padding-bottom:5px;" width="100px" height="17px" />
						</td>
						<td style="padding-left:5px;" align="left">';
						
    			if($DataArray[$index]['type'] == 'Institution')
					print ' 		<a href="company.php?CompanyId='. trim($DataArray[$index]['id']) .'" title="Company" >'.$DataArray[$index]['name'].'</a>&nbsp;&nbsp;('.GetProductsCountFromCompany(trim($DataArray[$index]['id'])).' Products)';
				else if($DataArray[$index]['type'] == 'MOA')
				{
					print ' 		<a href="moa.php?MoaId='. trim($DataArray[$index]['id']) .'" title="MOA" >'.$DataArray[$index]['name'].'</a>&nbsp;&nbsp;('.GetProductsCountFromMOA(trim($DataArray[$index]['id'])).' Products)';
				}
				else if($DataArray[$index]['type'] == 'Product')
				{
					$ProdRelateCompany = GetCompanyNames($DataArray[$index]['id']);
					print ' 		<a href="product.php?e1='. trim($DataArray[$index]['id']) .'&sourcepg=TZ" title="Product" ><b>'.$DataArray[$index]['name'] . '</b>' . ((trim($ProdRelateCompany) != '') ? ' / '.$ProdRelateCompany:'') .'</a>&nbsp;&nbsp;('.GetTrialsCountFromProduct(trim($DataArray[$index]['id'])).' Trials)';
				}
				else if($DataArray[$index]['type'] == 'Disease')
						print ' 		<a href="disease.php?DiseaseId='. trim($DataArray[$index]['id']) .'" title="Disease" >'.$DataArray[$index]['name'] .'</a>&nbsp;&nbsp;('.GetProductsCountFromDisease(trim($DataArray[$index]['id'])).' Products)';
				else if($DataArray[$index]['type'] == 'MOA_Category')
				{
						print ' 	<a href="moacategory.php?MoaCatId='. trim($DataArray[$index]['id']) .'" title="MOA Category" ><b>'.$DataArray[$index]['name'] .'</b></a>&nbsp;&nbsp;('.GetProductsCountFromMOACat(trim($DataArray[$index]['id'])).' Products)';
				}
				
				if($DataArray[$index]['type'] != 'MOA_Category') print '<br /><br style="line-height:6px;" />';
    			print ' </td>
    				  </tr>
					  </table>';
				if($DataArray[$index]['type'] == 'MOA_Category')
					print MOAListing(trim($DataArray[$index]['id']));
			}
			//else print '<tr><td><img src="images/'.$DataArray[$index]['type'].'arrow.gif" style="padding-bottom:5px;" width="120px" height="17px" /></td><td>'.$DataArray[$index]['type'].'</td></tr>';					
		}
	}
?>
<?php
if($FoundRecords == 0 && (($globalOptions['TzSearch'] != '' && $globalOptions['TzSearch'] != NULL) || $ClassFlg))
{
?>
	<div id="norecord">
 	 	<div>
		    
            	<?php
					if($ClassFlg)
					{
						print '<p>No '. $globalOptions['classType'] .' found.</p>';
					}
                	else
					{
						print "<p>Your search - 
                				<strong>
									".$globalOptions['TzSearch']."
                			    </strong> - did not match any products or companies or MOAs or diseases.</p>";
				?>
               <p>Suggestions:</p>
			    <ul>
			      <li>Make sure all words are spelled correctly.</li>
			      <li>Try different keywords.</li>
			    </ul>
                <?php } ?>
		  </div>
	</div>
<?php
}
if((trim($globalOptions['TzSearch']) == '' || $globalOptions['TzSearch'] == NULL) && !$ClassFlg)
{
?>
<br/>
<div align="center" style="padding-top:25px; color:#4f2683; font-weight:normal; font-size:16px">Welcome to <b>Larvol Sigma</b>. To find information please use the search field above or click on the links under it for a full list of covered items.</div>
<?php
}
?>
<!-- Displaying Records -->
<br/>
<table width="100%" border="0" style="">
	<tr>
    	<td style="border:1; font-weight:bold; padding-left:5px;" align="center">
        	<?php
				if($totalPages > 1)
				{
        			$paginate = pagination($globalOptions, $totalPages, $CurrentSearchType);
					print '<br/>'.$paginate[1];
				}
			?>
        </td>
    </tr>
</table>

<?php include "footer.php" ?>

</body>
</html>
<?php

function MOAListing($MOACat)
{
	$htmlContent = '';
	$MOAQuery = "SELECT `id`, `name`, `display_name` FROM `entities` e JOIN `entity_relations` er ON(e.`id`=er.`child`) WHERE e.`class`='MOA' AND er.`parent` = '" . mysql_real_escape_string($MOACat) . "'";
			
	$MOAResult = mysql_query($MOAQuery);
	$i=0;
	$MOAResultCount = mysql_num_rows($MOAResult);
	if($MOAResult && $MOAResultCount > 0)
	{
		if($i)
		$htmlContent .= '<br /><br style="line-height:6px;" />'; $i++;
		$htmlContent .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding-left:18px;">';
		while($SMOA = mysql_fetch_assoc($MOAResult))
		{
			$htmlContent .='<tr>
								<td align="left"  width="100px">
									<img src="../images/MOAarrow.gif" style="padding-bottom:5px;" width="100px" height="17px" />
								</td>
								<td style="padding-left:5px;" align="left">';
			$htmlContent .= ' 		<a href="moa.php?MoaId='. trim($SMOA['id']) .'" title="MOA" >'.(($SMOA['display_name'] != NULL && $SMOA['display_name'] != '') ? $SMOA['display_name']:$SMOA['name']).'</a>&nbsp;&nbsp;('.GetProductsCountFromMOA(trim($SMOA['id'])).' Products)<br />';
			$htmlContent .= '	</td>
				   			</tr>';									
		}
		$htmlContent .='</table>';
	}
	return $htmlContent;
}

/* Function to get Product count from MOA id */
function GetProductsCountFromMOA($moaID)
{
	global $db;
	global $now;
	$ProductsCount = 0;
	$query = "SELECT count(Distinct(et.`id`)) as proCount FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`)  WHERE et.`class`='Product' and er.`child`='" . mysql_real_escape_string($moaID) . "'  AND (et.`is_active` <> '0' OR et.`is_active` IS NULL)";
	$res = mysql_query($query) or die('Bad SQL query getting products count from moa id in TZ');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		$ProductsCount = $row['proCount'];
	}
	return $ProductsCount;
}

/* Function to get Trials count from MOA id */
function GetTrialsCountFromProduct($productID)
{
	global $db;
	global $now;
	$TrialsCount = 0;
	$query = "SELECT count(Distinct(dt.`larvol_id`)) as trialCount FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`)  WHERE et.`entity`='" . mysql_real_escape_string($productID) . "'";
	$res = mysql_query($query) or die('Bad SQL query getting trials count from product id in TZ');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		$TrialsCount = $row['trialCount'];
	}
	return $TrialsCount;
}

/* Function to get Product count from Institution id */
function GetProductsCountFromCompany($companyID)
{
	global $db;
	global $now;
	$ProductsCount = 0;
	/* COUNT QUERY THROUGH ENTITY_RELATIONS*/
	$query = "SELECT count(Distinct(et.`id`)) as proCount FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`) WHERE et.`class`='Product' AND er.`child`='" . mysql_real_escape_string($companyID) . "'  AND (et.`is_active` <> '0' OR et.`is_active` IS NULL)";
	/* COUNT QUERY THROUGH OLD RELATION TABLE*/
	//$query = "SELECT count(Distinct(et.`id`)) as proCount FROM `entities` et JOIN `products_institutions` pi ON(et.`id` = pi.`product`) WHERE et.`class`='Product' AND pi.`institution`='" . mysql_real_escape_string($companyID) . "'";
	$res = mysql_query($query) or die('Bad SQL query getting products count from company id in TZ');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		$ProductsCount = $row['proCount'];
	}
	return $ProductsCount;
}

/* Function to get Product count from MOA Category id */
function GetProductsCountFromMOACat($moaCatID)
{
	global $db;
	global $now;
	$ProductsCount = 0;
	$query = "SELECT count(Distinct(et.`id`)) as proCount FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`)  WHERE et.`class`='Product' and er.`child` IN (SELECT et2.`id` FROM `entities` et2 JOIN `entity_relations` er2 ON(et2.`id` = er2.`child`)  WHERE et2.`class`='MOA' AND er2.`parent`='". mysql_real_escape_string($moaCatID) ."')  AND (et.`is_active` <> '0' OR et.`is_active` IS NULL)";
	$res = mysql_query($query) or die('Bad SQL query getting products count from company id in TZ');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		$ProductsCount = $row['proCount'];
	}
	return $ProductsCount;
}

/* Function to get Product count from Disease id */
function GetProductsCountFromDisease($DiseaseID)
{
	global $db;
	global $now;
	$ProductsCount = 0;
	$query = "SELECT count(Distinct(e.`id`)) as proCount FROM `entities` e JOIN `entity_relations` er ON(e.`id` = er.`child`) WHERE e.`class`='Product' AND er.`parent`='" . mysql_real_escape_string($DiseaseID) . "' AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
	$res = mysql_query($query) or die('Bad SQL query getting products count from company id in TZ');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		$ProductsCount = $row['proCount'];
	}
	return $ProductsCount;
}

function sortTwoDimensionArrayByKey($arr, $arrKey)
{
	if(is_array($arr) && count($arr) > 0)
	{
		foreach ($arr as $key => $row)
		{
			$key_arr[$key] = $row[$arrKey];
		}
		$key_arr = array_map('strtolower', $key_arr);
		array_multisort($key_arr, SORT_ASC, SORT_STRING, $arr);
	}
	return $arr;
}

function pagination($globalOptions = array(), $totalPages)
{	
	$url = '';
	$stages = 1;
			
	if(isset($_REQUEST['TzSearch']))
	{
		$url .= '&amp;TzSearch=' . $_REQUEST['TzSearch'];
	}
	else if(!isset($_REQUEST['TzSearch']) && isset($globalOptions['TzSearch']))
	{
		$url .= '&amp;TzSearch=' . $globalOptions['TzSearch'];
	}
	else if(isset($_REQUEST['class']))
	{
		$url .= '&amp;class='.$_REQUEST['class'];
	}
	else if( !isset($_REQUEST['class']) && isset($globalOptions['class']))
	{
		$url .= '&amp;class='.$globalOptions['class'];
	}
	
	if(isset($_REQUEST['Alpha']))
	{
		$url .= '&amp;Alpha='.$_REQUEST['Alpha'];
	}
	else if( !isset($_REQUEST['Alpha']) && isset($globalOptions['Alpha']))
	{
		$url .= '&amp;Alpha='.$globalOptions['Alpha'];
	}
	
	$rootUrl = 'index.php?';
	$paginateStr = '<div class="pagination">';
	
	if($globalOptions['page'] != 1)
	{
		$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . ($globalOptions['page']-1) . '\'>&laquo;</a>';
	}
	
	if($totalPages < 7 + ($stages * 2))
	{	
		for($counter = 1; $counter <= $totalPages; $counter++)
		{
			if ($counter == $globalOptions['page'])
			{
				$paginateStr .= '<span>' . $counter . '</span>';
			}
			else
			{
				$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
			}
		}
	}
	elseif($totalPages > 5 + ($stages * 2))
	{
		if($globalOptions['page'] < 1 + ($stages * 2))
		{
			for($counter = 1; $counter < 4 + ($stages * 2); $counter++)
			{
				if ($counter == $globalOptions['page'])
				{
					$paginateStr .= '<span>' . $counter . '</span>';
				}
				else
				{
					$paginateStr .='<a href=\'' . $rootUrl . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
				}
			}
			$paginateStr.= '<span>...</span>';
			$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . ($totalPages-1) . '\'>' .  ($totalPages-1) . '</a>';
			$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . $totalPages . '\'>' . $totalPages . '</a>';
		}
		elseif($totalPages - ($stages * 2) > $globalOptions['page'] && $globalOptions['page'] > ($stages * 2))
		{
			$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=1\'>1</a>';
			$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=2\'>2</a>';
			$paginateStr.= '<span>...</span>';
			for($counter = $globalOptions['page'] - $stages; $counter <= $globalOptions['page'] + $stages; $counter++)
			{
				if ($counter == $globalOptions['page'])
				{
					$paginateStr.= '<span>' . $counter . '</span>';
				}
				else
				{
					$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
				}
			}
			$paginateStr.= '<span>...</span>';
			$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . ($totalPages-1) . '\'>' . ($totalPages-1) . '</a>';
			$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . $totalPages . '\'>' . $totalPages . '</a>';
		}
		else
		{
			$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=1\'>1</a>';
			$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=2\'>2</a>';
			$paginateStr .= "<span>...</span>";
			for($counter = $totalPages - (2 + ($stages * 2)); $counter <= $totalPages; $counter++)
			{
				if ($counter == $globalOptions['page'])
				{
					$paginateStr .= '<span>' . $counter . '</span>';
				}
				else
				{
					$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
				}
			}
		}
	}
	
	if($globalOptions['page'] != $totalPages)
	{
		$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . ($globalOptions['page']+1) . '\'>&raquo;</a>';
	}
	$paginateStr .= '</div>';
	
	return array($url, $paginateStr);
}
?>