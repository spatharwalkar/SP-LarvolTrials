<?php
	require_once('db.php');
	include('ss_entity.php');
	
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
		
		//To remove repeated MOAs
		$MOAArray = array();
		if(is_array($ResultArr))
		{
			$MOACatQuery = "SELECT `id`, `name`, `class` FROM `entities` WHERE id IN (" . implode(',',$ResultArr) . ") ";
			//print $MOACatQuery;
			$MOACatQueryResult = mysql_query($MOACatQuery);
			if($MOACatQueryResult)
			{
				while($MOAResult =  mysql_fetch_assoc($MOACatQueryResult))
				{ 
					if($MOAResult['class'] == 'MOA_Category')
					{
						$MOAQuery = "SELECT `id`, `name` FROM `entities` e JOIN `entity_relations` er ON(e.`id`=er.`child`) WHERE e.`class`='MOA' AND er.`parent` = '" . mysql_real_escape_string($MOAResult['id']) . "' ";			
						$MOAResult = mysql_query($MOAQuery);
						if($MOAResult &&  mysql_num_rows($MOAResult) > 0)
						{
							while($SMOA = mysql_fetch_assoc($MOAResult))
							$MOAArray[] = $SMOA['id'];
						}		
					}
				}
			}
			$ResultArr = array_diff($ResultArr, $MOAArray);	//Remove moas if its category present								
		}
		///End of remove repeated moas
		
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
				$result =  mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `class` FROM `entities` WHERE id = '" . mysql_real_escape_string($value) . "' "));
				$DataArray[$index]['index'] = $index;
				$DataArray[$index]['name'] = $result['name'];
				$DataArray[$index]['id'] = $result['id'];
				$DataArray[$index]['type'] = $result['class'];
			
			}
		}
	}
	else if($_REQUEST['Disease'] != NULL && $_REQUEST['Disease'] != '' && isset($_REQUEST['Disease']))
	{
		$ResultArrQuery = "SELECT `id`, `name`, `class` FROM `entities` WHERE `class` = 'Disease'";
		$QueryResult = mysql_query($ResultArrQuery);
		$FoundRecords = mysql_num_rows($QueryResult);
		
		$totalPages = ceil($FoundRecords / $RecordsPerPage);
		
		$StartSlice = ($globalOptions['page'] - 1) * $RecordsPerPage;
		$EndSlice = $StartSlice + $RecordsPerPage;
		$query = "SELECT `id`, `name`, `class` FROM `entities` WHERE `class` = 'Disease' LIMIT $StartSlice, $EndSlice";
		$QueryResult = mysql_query($query);
		$i=0;
		while($result = mysql_fetch_assoc($QueryResult))
		{
			$i++;
			$DataArray[$i]['index'] = $i;
			$DataArray[$i]['name'] = $result['name'];
			$DataArray[$i]['id'] = $result['id'];
			$DataArray[$i]['type'] = $result['class'];
		}
		$CurrentPageResultArr = $DataArray;
		$DiseaseFlg = true;
		$globalOptions['Disease'] = 'true';
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Larvol Trials :: Trialzilla</title>
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

.searchTypes
{
	font-weight:bold;
	font-size:12px;
}

.autocomplete-w1 
{ 
	background:url(./images/shadow.png) no-repeat bottom right; 
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
<?php include "trialzilla_searchbox.php";?>
<!-- Number of Results -->
<br/>
<table width="100%" border="0" class="FoundResultsTb">
	<tr>
    	<td width="50%" style="border:0; font-weight:bold; padding-left:5px;" align="left">&nbsp;
        	
        </td>
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
					
					if($DiseaseFlg)
					$showResult .= ' for disease';
					else
					$showResult .= ' for "'. $globalOptions['TzSearch'] .'"';
					print $showResult;
				}
			?>
        </td>
    </tr>
</table>

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
								<img src="images/'.$DataArray[$index]['type'].'arrow.gif" style="padding-bottom:5px;" width="100px" height="17px" />
						</td>
						<td style="padding-left:5px;" align="left">';
						
    			if($DataArray[$index]['type'] == 'Institution')
					print ' 		<a href="'. trim(urlPath()) .'trialzilla_company.php?CompanyId='. trim($DataArray[$index]['id']) .'" title="Company" target="_blank">'.$DataArray[$index]['name'].'</a>&nbsp;&nbsp;('.GetProductsCountFromCompany(trim($DataArray[$index]['id'])).' Products)';
				else if($DataArray[$index]['type'] == 'MOA')
				{
					print ' 		<a href="'. trim(urlPath()) .'trialzilla_moa.php?MoaId='. trim($DataArray[$index]['id']) .'" title="MOA" target="_blank">'.$DataArray[$index]['name'].'</a>&nbsp;&nbsp;('.GetProductsCountFromMOA(trim($DataArray[$index]['id'])).' Products)';
				}
				else if($DataArray[$index]['type'] == 'Product')
				{
					print ' 		<a href="'. trim(urlPath()) .'intermediary.php?p='. trim($DataArray[$index]['id']) .'" title="Product" target="_blank">'.formatBrandName($DataArray[$index]['name'], 'product') . $DataArray[$index]['company'] .'</a>&nbsp;&nbsp;('.GetTrialsCountFromProduct(trim($DataArray[$index]['id'])).' Trials)';
				}
				else if($DataArray[$index]['type'] == 'Disease')
						print ' 		<a href="'. trim(urlPath()) .'trialzilla_disease.php?DiseaseId='. trim($DataArray[$index]['id']) .'" title="Disease" target="_blank">'.$DataArray[$index]['name'] .'</a>';
				else if($DataArray[$index]['type'] == 'MOA_Category')
				{
						print ' 	<a href="'. trim(urlPath()) .'trialzilla_moacategory.php?MoaCatId='. trim($DataArray[$index]['id']) .'" title="MOA Category" target="_blank"><b>'.$DataArray[$index]['name'] .'</b></a>&nbsp;&nbsp;('.GetProductsCountFromMOACat(trim($DataArray[$index]['id'])).' Products)';
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
if($FoundRecords == 0 && (($globalOptions['TzSearch'] != '' && $globalOptions['TzSearch'] != NULL) || ($globalOptions['Disease'] != '' && $globalOptions['Disease'] != NULL)))
{
?>
	<div id="norecord">
 	 	<div>
		    
            	<?php
					if($globalOptions['Disease'] != '' && $globalOptions['Disease'] != NULL)
					{
						print '<p>No Disease found.</p>';
					}
                	else
					{
						print "<p>Your search - 
                				<strong>
									".$globalOptions['TzSearch']."
                			    </strong> - did not match any products or companies or MOAs.</p>";
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

<?php include "trialzilla_footer.php" ?>

</body>
</html>
<?php

function MOAListing($MOACat)
{
	$htmlContent = '';
	$MOAQuery = "SELECT `id`, `name` FROM `entities` e JOIN `entity_relations` er ON(e.`id`=er.`child`) WHERE e.`class`='MOA' AND er.`parent` = '" . mysql_real_escape_string($MOACat) . "' ";
			
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
									<img src="images/MOAarrow.gif" style="padding-bottom:5px;" width="100px" height="17px" />
								</td>
								<td style="padding-left:5px;" align="left">';
			$htmlContent .= ' 		<a href="'. trim(urlPath()) .'trialzilla_moa.php?MoaId='. trim($SMOA['id']) .'" title="MOA" target="_blank">'.$SMOA['name'].'</a>&nbsp;&nbsp;('.GetProductsCountFromMOA(trim($SMOA['id'])).' Products)<br />';
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
	$query = "SELECT count(Distinct(et.`id`)) as proCount FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`)  WHERE et.`class`='Product' and er.`child`='" . mysql_real_escape_string($moaID) . "'";
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
	/* COUNT QUERY THROUGH ENTITY_RELATIONS
	$query = "SELECT count(Distinct(et.`id`)) as proCount FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`) WHERE et.`class`='Product' AND er.`child`='" . mysql_real_escape_string($companyID) . "'";*/
	$query = "SELECT count(Distinct(et.`id`)) as proCount FROM `entities` et LEFT JOIN `products_institutions` pi ON(et.`id` = pi.`product`) WHERE et.`class`='Product' AND pi.`institution`='" . mysql_real_escape_string($companyID) . "'";
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
	$query = "SELECT count(Distinct(et.`id`)) as proCount FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`)  WHERE et.`class`='Product' and er.`child` IN (SELECT et2.`id` FROM `entities` et2 JOIN `entity_relations` er2 ON(et2.`id` = er2.`child`)  WHERE et2.`class`='MOA' AND er2.`parent`='". mysql_real_escape_string($moaCatID) ."')";
	$res = mysql_query($query) or die('Bad SQL query getting products count from company id in TZ');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		$ProductsCount = $row['proCount'];
	}
	return $ProductsCount;
}

function pagination($globalOptions = array(), $totalPages)
{	
	$url = '';
	$stages = 1;
			
	if(isset($_REQUEST['TzSearch']))
	{
		$url .= '&amp;TzSearch=' . $_REQUEST['TzSearch'];
	}
	else if( !isset($_REQUEST['TzSearch']) and isset($globalOptions['TzSearch']))
	{
		$url .= '&amp;TzSearch=' . $globalOptions['TzSearch'];
	}
	else if(isset($_REQUEST['Disease']))
	{
		$url .= '&amp;Disease=true';
	}
	else if( !isset($_REQUEST['Disease']) and isset($globalOptions['Disease']))
	{
		$url .= '&amp;Disease=true';
	}
	
	$rootUrl = 'trialzilla.php?';
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