<?php
	require_once('db.php');
	include('ss_product.php');
	include('ss_institution.php');
	include('ss_moa.php');
	
	$RecordsPerPage = 50;
	$SearchFlg = false;
	$DiseaseFlg = false;
	$globalOptions['page'] = 1;
	$DataArray = $CurrentPageResultArr = array();
	$totalPages = $FoundRecords = 0;
	$ProdFlg = false;
	$CompanyFlg = false;
	$MoaFlg = false;
	$ProdResultArr = array();
	$CompanyResultArr = array();
	$MoaResultArr = array();
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
		
		$ProdResultArr = find_product($globalOptions['TzSearch']);
		$CompanyResultArr = find_institution($globalOptions['TzSearch']);
		$MoaResultArr = find_moa($globalOptions['TzSearch']);
		
		if(count($CompanyResultArr) > 0 && is_array($CompanyResultArr)) $CompanyFlg = true;
		
		if(count($ProdResultArr) > 0 && is_array($ProdResultArr)) $ProdFlg = true;
		
		if(count($MoaResultArr) > 0 && is_array($MoaResultArr)) $MoaFlg = true;
		
		$i = 0;
		
		if($CompanyFlg)
		{
			foreach($CompanyResultArr as $val)
			{
				if($val != NULL && $val != '')
				{
					$ResultArr[$i]['id'] = $val;
					$ResultArr[$i]['type'] = 'Company';
					$i++;
				}
			}
		}
		
		if($ProdFlg)
		{
			foreach($ProdResultArr as $val)
			{
				if($val != NULL && $val != '')
				{
					$ResultArr[$i]['id'] = $val;
					$ResultArr[$i]['type'] = 'Product';
					$i++;
				}
			}
		}
		
		if($MoaFlg)
		{
			foreach($MoaResultArr as $val)
			{
				if($val != NULL && $val != '')
				{
					$ResultArr[$i]['id'] = $val;
					$ResultArr[$i]['type'] = 'Moa';
					$i++;
				}
			}
		}				
		
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
				if($CurrentPageResultArr[$index]['type'] == 'Company')
				{
					$result =  mysql_fetch_assoc(mysql_query("SELECT id, name FROM `institutions` WHERE id = '" . mysql_real_escape_string($CurrentPageResultArr[$index]['id']) . "' "));
					$DataArray[$index]['index'] = $index;
					$DataArray[$index]['name'] = $result['name'];
					$DataArray[$index]['id'] = $result['id'];
					$DataArray[$index]['type'] = $CurrentPageResultArr[$index]['type'];
				}
				else if($CurrentPageResultArr[$index]['type'] == 'Moa')
				{
					$result =  mysql_fetch_assoc(mysql_query("SELECT id, name FROM `moas` WHERE id = '" . mysql_real_escape_string($CurrentPageResultArr[$index]['id']) . "' "));
					$DataArray[$index]['index'] = $index;
					$DataArray[$index]['name'] = $result['name'];
					$DataArray[$index]['id'] = $result['id'];
					$DataArray[$index]['type'] = $CurrentPageResultArr[$index]['type'];
				}
				else
				{
					$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description, company FROM `products` WHERE id = '" . mysql_real_escape_string($CurrentPageResultArr[$index]['id']) . "' "));
					$DataArray[$index]['index'] = $index;
					$DataArray[$index]['name'] = $result['name'];
					$DataArray[$index]['id'] = $result['id'];
					$DataArray[$index]['type'] = $CurrentPageResultArr[$index]['type'];
					$DataArray[$index]['description'] = $result['description'];
					if($result['company'] != NULL && trim($result['company']) != '')
					{
						$result['company']=str_replace(',',', ',$result['company']);
						$result['company']=str_replace(',  ',', ',$result['company']);
						$DataArray[$index]['company'] = ' / '.$result['company'];
					}
				}
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
<script type="text/javascript" src="scripts/autosuggest/jquery.autocomplete-min.js"></script>
<script type="text/javascript">
	$(function() 
	{
		$('body').keydown(function(e)
		{	
			if (e.keyCode == 13) 
			{
			  $('#trialzillaFrm').submit();
			} 
		});
	}); 
	
</script>
<script type="text/javascript">
function autoComplete(fieldID)
{	
	$(function()
	{
		if($('#'+fieldID).length > 0)
		{	
			var a = $('#'+fieldID).autocomplete({
					serviceUrl:'autosuggest.php',
					params:{table:'trialzilla', field:'name'},
					minChars:3,
					width:600
			});
		}
	});
}
</script>
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
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<?php
	foreach($CurrentPageResultArr as $index=> $data)
	{
		if($DataArray[$index]['id'] != '' && $DataArray[$index]['id'] != '' && $FoundRecords > 0)
		{
			print'<tr>
					<td align="left"  width="80px">
							<img src="images/'.$DataArray[$index]['type'].'arrow.gif" style="padding-bottom:5px;" width="80px" height="17px" />
					</td>
					<td style="padding-left:5px;" align="left">';
					
    		if($DataArray[$index]['type'] == 'Company')
				print ' 		<a href="'. trim(urlPath()) .'trialzilla_company.php?CompanyId='. trim($DataArray[$index]['id']) .'" title="Company" target="_blank">'.$DataArray[$index]['name'].'</a>';
			else if($DataArray[$index]['type'] == 'Moa')
				print ' 		<a href="'. trim(urlPath()) .'trialzilla_moa.php?MoaId='. trim($DataArray[$index]['id']) .'" title="MOA" target="_blank">'.$DataArray[$index]['name'].'</a>';
				else if($DataArray[$index]['type'] == 'Product')
					print ' 		<a href="'. trim(urlPath()) .'intermediary.php?p='. trim($DataArray[$index]['id']) .'" title="Product" target="_blank">'.formatBrandName($DataArray[$index]['name'], 'product') . $DataArray[$index]['company'] .'</a>';
					else
						print ' 		<a href="'. trim(urlPath()) .'trialzilla_disease.php?DiseaseId='. trim($DataArray[$index]['id']) .'" title="Disease" target="_blank">'.$DataArray[$index]['name'] .'</a>';
			
    		print '      <br /><br style="line-height:6px;" />
					</td>
    			  </tr>';
		}
	}
?>
</table>
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
                	else if($ProdFlg == false && $CompanyFlg == false && $MoaFlg == false)
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