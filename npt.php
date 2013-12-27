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
</style>
<?php
	$pageSize = 50;
	$pageNo = 0;
	
	$conditionArray = "";
	$resultArr = array();
	$AlphaData = array();
	$Char = 'A';
	$resultArr = getNonProductTrials($pageSize,$pageNo,$conditionArray);
	$resultCharArr = array_map(function($resultArray) {
		return strtoupper(substr($resultArray['name'],0,1));
	}, $resultArr);
	if(isset($_REQUEST['Alpha'])){
		$resultArr = filterArray( $resultArr, 'name', $_REQUEST['Alpha']);
	}
	for($c=0; $c < 26; $c++) {
		$AlphaData[$Char]['Char']=$Char;
		if(array_search($Char, $resultCharArr)){
			$AlphaData[$Char]['Active']=true;
		}else{
			$AlphaData[$Char]['Active']=false;
		}
		$AlphaData[$Char]['Data']=array();
		$Char++; 
	}
	$AlphaData['Other']['Char'] = 'Other';
	$checkSpecialCharacters = preg_grep('/[\'0-9^£$%&*()}{@#~?.,><>,|=_+¬-]/', $resultCharArr);
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
				print '<a href="npt.php?Alpha='.$key.'" style="background-color: #4f2683; color:#FFFFFF;">'.$key.'</a>'; 
			}else if($Alpha['Active']){ 
				print '<a href="npt.php?Alpha='.$key.'">'.$key.'</a>';
			}else{ 
				print '<span class="alphanormal">'.$key.'</span>';
			}
		}
		
		if(!isset($globalOptions['Alpha']) || trim($globalOptions['Alpha']) == '' || $globalOptions['Alpha'] == NULL)
		print '<a href="npt.php" style="background-color: #4f2683; color:#FFFFFF;">All</a>';
		else
		print '<a href="npt.php">All</a>';

		if('Disease' == 'NPT')
			print '&nbsp;&nbsp;<a href="index.php?class=Disease_Category">View Disease Categories</a></div></td>';
		elseif('Disease_Category' == 'NPT')
			print '&nbsp;&nbsp;<a href="index.php?class=Disease">View Individual Diseases</a></div></td>';
		else 
			print '			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div></td>';
		print '</tr>
		</table><br/>';
	}
	echo "<br/>";
	echo '<table border="0" cellspacing="0" cellpadding="0" style="background-color:#FFFFFF">';
	if(sizeof($resultArr) > 0){
		foreach($resultArr AS $DataArray){
			print ' 
				<tr>
					<td align="left"  width="100px" style="vertical-align:top;background-color:#FFFFFF;">
						<img src="images/NPTarrow.gif" style="padding:5px;width:100px;height:17px;"/>
					</td>
					<td style="padding:5px;background-color:#FFFFFF;" align="left"><a href="npt_tracker.php?tid='. trim($DataArray['id']) .'&nptname='.$DataArray['name'].'" title="Product" ><b>'.$DataArray['name'] . '</b></a>&nbsp;&nbsp;('.$DataArray['noOfTrials'].' Trials)</td>
				</tr>';
		}
	}
	echo "</table>";
	echo('</body></html>');
	/*[ Function-Start ](to filter result array by alphabet value )*/
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
	function getNonProductTrials($pageSize,$pageNo){
		$dataList = "SELECT intervention_name,larvol_id FROM data_trials WHERE larvol_id NOT IN (SELECT trial FROM entity_trials) AND intervention_name!='' LIMIT ".$pageSize*$pageNo.",".$pageSize."";
		//echo $dataList;die();
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
?>
