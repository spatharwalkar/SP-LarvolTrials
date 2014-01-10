<?php
	require_once('db.php');
	if($_SERVER['QUERY_STRING'] == 'logout') $db->logout();
	require_once('header.php');
?>
<style>
.alpharow {
	background-color:#FFFFFF;
    color: #4F2683;
    float: left;
    font-weight: bold;
    line-height: 1.6em;
    margin-right: 10px;
    padding-bottom: 4px;
    padding-top: 4px;
    vertical-align: bottom;
    width: 100%;
}
.alpharow a:hover {
    background-color: #AA8ECE;
    color: #FFFFFF;
    font-weight: bold;
}
.alpharow a {
    border: 1px solid #CCCCCC;
    color: #4F2683;
    display: inline;
    font-weight: bold;
    margin: 0 2px;
    padding: 2px 5px;
    text-align: center;
    text-decoration: none;
}
.alphanormal {
    color: #000000;
    font-size: 13px;
    font-weight: normal;
}
.alpharow span {
    padding: 2px 5px;
}
div.pagination table{
	margin:0 auto;
}
div.pagination table tr td{
	background:none repeat scroll 0 0 rgba(0, 0, 0, 0);
}
div.pagination table tr td a{
    background-color: #4F2683;
    border: 1px solid #CCCCCC;
    color: #FFFFFF;
    display: inline;
    font-weight: bold;
    margin: 0 2px;
    padding: 2px 5px;
    text-align: center;
    text-decoration: none;
}
div.pagination{
    color: #4F2683;
    float: left;
    font-weight: bold;
    line-height: 1.6em;
    margin-right: 10px;
    padding-bottom: 25px;
    padding-top: 20px;
    vertical-align: bottom;
    width: 100%;
	font-family: arial;
}
div.pagination table tr td a:hover {
    background-color: #AA8ECE;
    color: #FFFFFF;
    font-weight: bold;
}
div.pagination table tr td a {
    background-color: #4F2683;
    border: 1px solid #CCCCCC;
    color: #FFFFFF;
    display: inline;
    font-weight: bold;
    margin: 0 2px;
    padding: 2px 5px;
    text-align: center;
    text-decoration: none;
}
div.pagination table tr td span {
    padding: 2px 5px;
}
#slideout {
    margin: 12px 0 0;
    position: fixed;
    right: 0;
    top: 40px;
    z-index: 999;
}
.slideout_inner {
    display: none;
    position: absolute;
    right: -255px;
    top: 40px;
}
.table-slide {
    border: 1px solid #000000;
}

.table-slide td {
    border-bottom: 1px solid #000000;
    border-right: 1px solid #000000;
    padding: 8px 20px 8px 8px;
}
</style>

<?php
	$pageSize = 50; // Default page size which is constant
	$pageNo = 1; // Initialize page number
	/* Page number requested and assigned to $pageNo for pagination */
	if(isset($_REQUEST['page']) && $_REQUEST['page'] != "" && is_numeric($_REQUEST['page'])){
		$pageNo = mysql_real_escape_string($_REQUEST['page']);  
	}
	$resultArr = array();
	$resultArr = getNonProductTrials();  /* Getting all the non product trails	*/
	$resultArr = sortTwoDimensionArrayByKey($resultArr, 'name');
	$AlphaData = array();
	$Char = 'A';
	$filterResultFirstCharacterArray = array_map(function($resultArray) {
		return strtoupper(substr($resultArray['name'],0,1));
	}, $resultArr);
	if(isset($_REQUEST['Alpha'])){
		$resultArr = filterArray( $resultArr, 'name', $_REQUEST['Alpha']);
	}
	for($c=0; $c < 26; $c++) {
		$AlphaData[$Char]['Char']=$Char;
		if(array_search($Char, $filterResultFirstCharacterArray)){
			$AlphaData[$Char]['Active']=true;
		}else{
			$AlphaData[$Char]['Active']=false;
		}
		$AlphaData[$Char]['Data']=array();
		$Char++; 
	}
	$AlphaData['Other']['Char'] = 'Other';
	$checkSpecialCharacters = preg_grep('/[\'0-9^£$%&*()}{@#~?.,><>,|=_+¬-]/', $filterResultFirstCharacterArray);
	if(sizeof($checkSpecialCharacters) > 0){
		$AlphaData['Other']['Active']=true;
	}else{
		$AlphaData['Other']['Active']=false;
	}
	$AlphaData['Other']['Data']=array();	
	
	$ClassFlg = true;
	$globalOptions['Alpha'] = "";
	if(isset($_REQUEST['Alpha'])){
		$globalOptions['Alpha'] = $_REQUEST['Alpha'];
	}
	if($ClassFlg)
	{
		print '<br/><table align="center" cellpadding="0" cellspacing="0" style="margin:0 auto;">
					<tr>
						<td style="border-top:#CCCCCC solid 1px; border-bottom:#CCCCCC solid 1px;"><div class="alpharow"><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;See Non Product Trials by First Letter&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
							
		foreach($AlphaData as $key=> $Alpha)
		{
			if($globalOptions['Alpha'] == $key){ 
				print '<a href="upt.php?Alpha='.$key.'" style="background-color: #4f2683; color:#FFFFFF;">'.$key.'</a>'; 
			}else if($Alpha['Active']){ 
				print '<a href="upt.php?Alpha='.$key.'">'.$key.'</a>';
			}else{ 
				print '<span class="alphanormal">'.$key.'</span>';
			}
		}
		
		if(!isset($globalOptions['Alpha']) || trim($globalOptions['Alpha']) == '' || $globalOptions['Alpha'] == NULL)
		print '<a href="upt.php" style="background-color: #4f2683; color:#FFFFFF;">All</a>';
		else
		print '<a href="upt.php">All</a>';

		if('Disease' == 'UPT')
			print '&nbsp;&nbsp;<a href="index.php?class=Disease_Category">View Disease Categories</a></div></td>';
		elseif('Disease_Category' == 'UPT')
			print '&nbsp;&nbsp;<a href="index.php?class=Disease">View Individual Diseases</a></div></td>';
		else 
			print '			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div></td>';
		print '</tr>
		</table><br/>';
	}
	echo "<br/>";
	echo '<table border="0" cellspacing="0" cellpadding="0" style="background-color:#FFFFFF">';
	$finalResultArrayWithPageLimit = array_slice($resultArr,($pageNo-1)*$pageSize,$pageSize);  /* Filtering data as per the page number and page size */
	if(sizeof($finalResultArrayWithPageLimit) > 0){ 			/*	Showing required data  */
		foreach($finalResultArrayWithPageLimit AS $DataArray){
			print ' 
				<tr>
					<td align="left"  width="100px" style="vertical-align:top;background-color:#FFFFFF;">
						<img src="images/NPTarrow.gif" style="padding:5px;width:100px;height:17px;"/>
					</td>
					<td style="padding:5px;background-color:#FFFFFF;" align="left"><a href="upt_tracker.php?tid='. trim($DataArray['id']) .'&nptname='.$DataArray['name'].'" title="Product" ><b>'.$DataArray['name'] . '</b></a>&nbsp;&nbsp;('.$DataArray['noOfTrials'].' Trials)</td>
				</tr>';
		}
	}
	echo "</table>";
	echo "<div class=pagination><table><tr><td>"; 	/* Pagination start if $resultArr[] size is more than 50. */
		if(sizeof($resultArr) > 50){
			$sizeofResultArray = sizeof($resultArr);
			$pageStart = 1;
			if($pageNo >=2){
				$prevPage = $pageNo-1;
				echo '<a href = "?page='.$prevPage.'"> &laquo; </a>';
			}
			while($sizeofResultArray >= 1){
				if($pageStart == $pageNo){
					echo "<span> ".$pageStart." </span>";
				}else{
					echo '<a href = "?page='.$pageStart.'"> '.$pageStart.' </a>';
				}
				$pageStart++;
				$sizeofResultArray = $sizeofResultArray - 50;
			}
			if($pageNo < $pageStart-1){
				$nextPage = $pageNo+1;
				echo '<a href = "?page='.$nextPage.'"> &raquo; </a>';
			}
		}
	echo "</td></tr></table></div>";
	echo('</body></html>');
	/*[ Start Function ](to filter result array by alphabet value )*/
	function filterArray($array, $index, $value){
		if(is_array($array) && count($array)>0) 
		{
			foreach(array_keys($array) as $key){
				$temp[$key] = $array[$key][$index];
				if($value == "Other"){
					if (!preg_match('/^[a-z]/i', substr($temp[$key],0,1))) { /*   "/i" means case independent */
						$newarray[$key] = $array[$key];
					}
				}else{
					if (substr($temp[$key],0,1) == $value){
						$newarray[$key] = $array[$key];
					}
				}
			}
		}
		return $newarray;
	} 
	/*[ End Function]*/
	/* get all the product name those dont have an entry in the entity trials table*/
	function getNonProductTrials(){
		$dataList = "SELECT intervention_name,larvol_id FROM data_trials WHERE larvol_id NOT IN (SELECT trial FROM entity_trials) AND intervention_name!='' LIMIT 0 , 200";
		$dataListRes = mysql_query($dataList);
		$interventionNameArr = array();
		while($dlr = mysql_fetch_array($dataListRes)){
			if($dlr['intervention_name'] !='' || $dlr['intervention_name'] !='NULL'){
				$interventionNames = explode("`",$dlr['intervention_name']);
				foreach($interventionNames as $in){
					$interventionNameArr[$in][] = $dlr['larvol_id'];
				}
			}
		}	
		$productListArr = array();
		$i = 0;
		foreach ($interventionNameArr as $interventionName=>$larvolIds) {
			if (is_array($larvolIds)){	
				$i++;		
				if($interventionName != ""){
					$larvolIdCount = count($larvolIds);
					$larvolIdList = implode(",",$larvolIds);
					$productListArr[$i]['name'] = $interventionName;
					$productListArr[$i]['id'] = $larvolIdList;
					$productListArr[$i]['type'] = "NPT";
					$productListArr[$i]['affiliation'] = "";
					$productListArr[$i]['index'] = $i;
					$productListArr[$i]['noOfTrials'] = $larvolIdCount;
				}
			}
		}
		return $productListArr;
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
?>
