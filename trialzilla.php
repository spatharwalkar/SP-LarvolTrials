<?php
	require_once('db.php');
	include('ss_product.php');
	
	$RecordsPerPage = 50;
	$SearchFlg = false;
	$globalOptions['page'] = 1;
	$DataArray = $CurrentPageResultArr = array();
	$totalPages = $FoundRecords = 0;
	//print '<br/><br/>';
	if($_REQUEST['TzSearch'] != NULL && $_REQUEST['TzSearch'] != '' && isset($_REQUEST['TzSearch']))
	{
		$SearchFlg = true;
		$globalOptions['TzSearch'] = $_REQUEST['TzSearch'];
		
		$ResultArr = find_product($globalOptions['TzSearch']);
		if(is_array($ResultArr))
		$FoundRecords = count($ResultArr);
		
		if($FoundRecords > 0)
		{
			if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
			{
				$globalOptions['page'] = mysql_real_escape_string($_REQUEST['page']);
			}
			
			$totalPages = ceil(count($ResultArr) / $RecordsPerPage);
			
			//Get only those product Ids which we are planning to display on current page to avoid unnecessary queries
			$StartSlice = ($globalOptions['page'] - 1) * $RecordsPerPage;
			$EndSlice = $StartSlice + $RecordsPerPage;
			$CurrentPageResultArr = array_filter(array_slice($ResultArr, $StartSlice, $RecordsPerPage));
			
			foreach($CurrentPageResultArr as $index=> $id)
			{
				$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description, company FROM `products` WHERE id = '" . $id . "' "));
				$DataArray[$index]['index'] = $index;
				$DataArray[$index]['name'] = $result['name'];
				$DataArray[$index]['id'] = $result['id'];
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

.TopBorder
{
	filter:alpha(opacity=100);
	position:absolute;
	top:0;
	width:100%;
	z-index:990;
	background-color:#4f2683;
	height:27px;
	left:0;
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

</style>
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
	}
</script>
</head>

<body>

<!-- Heading border -->
<table width="100%" border="0" class="TopBorder">
	<tr>
    	<td width="33%" style="font-weight:bold; color:#FFFFFF; padding-left:5px;">
        	Trialzilla
        </td>
        <td width="33%" align="center">
			<font style="color:#FFFFFF; font-size:11px; padding-top:0px; font-weight:bold;">
            	<!--Interface work in progress<br/>-->
            	Send feedback to 
            	<a style="display:inline;color:#CCCCCC;" target="_self" href="mailto:larvoltrials@larvol.com">larvoltrials@larvol.com</a>
            </font>
        </td>
        <td width="34%">&nbsp;
        	
        </td>
    </tr>
</table>
<!-- SearchBox & Logo -->
<form action="trialzilla.php" method="post" name="trialzillaFrm" id="trialzillaFrm">
	<table width="100%" border="0" style="padding-top:27px;">
		<tr>
	    	<td width="300px">
	        	<img src="images/Larvol-Trial-Logo-notag.png" width="300" height="47" />
	        </td>
	        <td width="600px" style="vertical-align:bottom; padding-left:20px;" align="left">
	        	<input class="SearchBox" type="text" value="<?php echo htmlspecialchars($globalOptions['TzSearch']); ?>" autocomplete="off" style="font-weight:bold;" name="TzSearch" id="TzSearch" />
	        </td>
	        <td style="vertical-align:bottom; padding-left:10px;" align="left">
	        	<input type="submit" name="Search" title="Search" value="Search" style="vertical-align:bottom;" class="SearchBttn1" />
	        </td>
	    </tr>
	</table>
</form>
<!-- Number of Results -->
<br/>
<table width="100%" border="0" class="FoundResultsTb">
	<tr>
    	<td width="50%" style="border:0; font-weight:bold; padding-left:5px;" align="left">
        	Search Products
        </td>
        <td width="50%" style="border:0; font-weight:bold; padding-right:5px;" align="right">
        	<?php
            	if(SearchFlg && $FoundRecords > 0)
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
					$showResult .= ' for '. $globalOptions['TzSearch'];
					print $showResult;
				}
			?>
        </td>
    </tr>
</table>

<!-- Displaying Records -->
<br/>
<table width="100%" border="0" style="">
<?php
	foreach($CurrentPageResultArr as $index=> $id)
	{
		if($DataArray[$index]['id'] != '' && $DataArray[$index]['id'] != '' && $FoundRecords > 0)
		{
			print'<tr>
    			  	<td style="border:0px; padding-left:5px;" align="left">
    		      		<a href="'. trim(urlPath()) .'intermediary.php?p='. trim($DataArray[$index]['id']) .'" title="Product" target="_blank">'.formatBrandName($DataArray[$index]['name'], 'product') . $DataArray[$index]['company'] .'</a>
    		        	<br /><br style="line-height:6px;" />
					</td>
    			  </tr>';
		}
	}
?>
</table>
<?php
if($FoundRecords == 0 && $globalOptions['TzSearch'] != '' && $globalOptions['TzSearch'] != NULL)
{
?>
	<div id="norecord">
 	 	<div>
		    <p>Your search - <strong><?php echo $globalOptions['TzSearch']; ?></strong> - did not match any products.</p>
			    <p>Suggestions:</p>
			    <ul>
			      <li>Make sure all words are spelled correctly.</li>
			      <li>Try different keywords.</li>
			    </ul>
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
        			$paginate = pagination($globalOptions, $totalPages);
					print '<br/>'.$paginate[1];
				}
			?>
        </td>
    </tr>
</table>

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
	if( !isset($_REQUEST['TzSearch']) and isset($globalOptions['TzSearch']))
	{
		$url .= '&amp;TzSearch=' . $globalOptions['TzSearch'];
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