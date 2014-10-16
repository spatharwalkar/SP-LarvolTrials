<?php

require_once('db.php');
require_once('include.util.php');
$Sphinx_search=null;
#Added by Sivaprasad Sivarajan Storyid-67946088
$escapers = array("\'");
$replacements = array("'");

switch($_REQUEST['op']){

	case 'load':
		$dataS = listSearchProc();
		//var_dump($dataS);
		echo($dataS);
		//echo("hello world");
		break;
	case 'getsearchdata':
		get_SearchData();
		break;
	case 'saveexists':
		updateSearch();
		echo $_REQUEST['reportname']." saved....";
		break;
	case 'savenew':
		echo(insertSearch());
		break;
	case 'list':
		echo(listSearchForm());
		break;
	case 'runQuery':
		runQuery($_REQUEST['data']);
		break;
	case 'testQuery':
		if(isset($_REQUEST['jsonOp']) && $_REQUEST['jsonOp']==1)
		echo(testQuery($_REQUEST['jsonOp']));
		else
		echo(testQuery());
		break;
	case 'gridList':
		echo(listSearchesInGrid());
		break;
	case 'copySearch':
		echo(copySearch_Data());
		break;
}

function listSearchesInGrid()
{
	$page = $_REQUEST['page']; // get the requested page
	$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
	$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
	$sord = $_REQUEST['sord']; // get the direction
	if(!$sidx) $sidx =1;

	$result = mysql_query("SELECT COUNT(*) AS count FROM saved_searches");
	$row = mysql_fetch_array($result,MYSQL_ASSOC);
	$count = $row['count'];

	if( $count >0 ) {
		$total_pages = ceil($count/$limit);
	} else {
		$total_pages = 0;
	}
	global $logger;
	
	if ($page > $total_pages) $page=$total_pages;
	$start = $limit*$page - $limit; // do not put $limit*($page - 1)
	$SQL = "SELECT a.id, a.name, 'description' as description FROM saved_searches a ORDER BY $sidx $sord LIMIT $start , $limit";
	if(!$result = mysql_query($SQL))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	
//	$result = mysql_query( $SQL ) or die("Couldn t execute query.".mysql_error());

	$responce->page = $page;
	$responce->total = $total_pages;
	$responce->records = $count;
	$i=0;
	while($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
		$responce->rows[$i]['id']=$row[id];
		$responce->rows[$i]['cell']=array($row[id],$row[name],$row[description]);
		$i++;
	}
	echo json_encode($responce);

}

//processes postdata for saving new searches
function insertSearch()
{
	$querytosave=stripslashes($_REQUEST['querytosave']);
	global $db,$escapers,$replacements;
	$querytosave = preg_replace_callback('~.*?columnvalue\":\"(.*?)\"}[,\]]+.*?~', function($querytosave) {
		return str_replace($querytosave[1], mysql_real_escape_string($querytosave[1]), $querytosave[0]);
	},$querytosave);	
	
	/***Part to Replace product/Area name by product/Area id when storing******/
	$jsonData = str_replace($escapers, $replacements, $querytosave);
	
	//$jsonData=$querytosave; 
	$filterData = json_decode($jsonData, true, 10);
	if(is_array($filterData["wheredata"]) && !empty($filterData["wheredata"]))
	{
		foreach($filterData["wheredata"] as $key=>$where_data)
		{
			if($where_data["columnname"] == 'product' || $where_data["columnname"] == 'area')
			{
				$ProdORAreaID=get_ProdORAreaID($where_data["columnvalue"], $where_data["columnname"]);
				if($ProdORAreaID)
				$where_data["columnvalue"]=$ProdORAreaID;
				$filterData["wheredata"][$key]=$where_data;
			}
		}
	}
	$querytosave=json_encode($filterData);
	/***End Part to Replace product/Area name by product/Area id when storing******/
	
	if(!isset($_REQUEST['reportname']) || !strlen($_REQUEST['reportname'])) return;
	$name = mysql_real_escape_string($_REQUEST['reportname']);
	$user = $db->user->id;
	$searchdata = $querytosave;
	
	if($_REQUEST['search_type'] == 'global')
	{
		$uclause = 'NULL'; $shared=0;
	}
	else if($_REQUEST['search_type'] == 'mine')
	{
		$uclause = $user; $shared=0;
	}
	else if($_REQUEST['search_type'] == 'shared')
	{
		$uclause = $user; $shared=1;
	}
	
	$query = 'INSERT INTO saved_searches SET user=' . $uclause . ',name="' . $name . '",shared="' . $shared . '",searchdata="'
	. base64_encode(serialize($searchdata)) . '"';
	
	$insert_res=mysql_query($query) or die('Bad SQL query adding saved search');
	$miid = mysql_insert_id();
	global $logger;
	if(!$insert_res)
		{
			$log='Bad SQL query adding saved search:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	
	if(!mysql_query('COMMIT'))
		{
			$log='Couldn\'t commit SQL query: "COMMIT", Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	return $miid;
}

//processes postdata for saving new searches
function updateSearch()
{
	$querytosave=stripslashes($_REQUEST['querytosave']);
	global $db,$escapers,$replacements;
	$querytosave = preg_replace_callback('~.*?columnvalue\":\"(.*?)\"}[,\]]+.*?~', function($querytosave) {
		return str_replace($querytosave[1], mysql_real_escape_string($querytosave[1]), $querytosave[0]);
	},$querytosave);
	$jsonData = str_replace($escapers, $replacements, $querytosave);
	/***Part to Replace product/Area name by product/Area id when storing******/
	//$jsonData=$querytosave; 
	$filterData = json_decode($jsonData, true, 10);
	if(is_array($filterData["wheredata"]) && !empty($filterData["wheredata"]))
	{
		foreach($filterData["wheredata"] as $key=>$where_data)
		{
			if($where_data["columnname"] == 'product' || $where_data["columnname"] == 'area')
			{
				$ProdORAreaID=get_ProdORAreaID($where_data["columnvalue"], $where_data["columnname"]);
				if($ProdORAreaID)
				$where_data["columnvalue"]=$ProdORAreaID;
				$filterData["wheredata"][$key]=$where_data;
			}
		}
	}
	$querytosave=json_encode($filterData);
	/***End Part to Replace product/Area name by product/Area id when storing******/
	
	if(!isset($_REQUEST['reportname']) || !strlen($_REQUEST['reportname'])) return;
	$name = mysql_real_escape_string($_REQUEST['reportname']);
	$searchId = mysql_real_escape_string($_REQUEST['searchId']);
	//$user = isset($_REQUEST['saveglobal']) ? NULL : $db->user->id;
	$user = $db->user->id;
	$searchdata = $querytosave;
	
	if($_REQUEST['search_type'] == 'global')
	{
		$uclause = 'NULL'; $shared=0;
	}
	else if($_REQUEST['search_type'] == 'mine')
	{
		echo $uclause = $user; $shared=0;
	}
	else if($_REQUEST['search_type'] == 'shared')
	{
		$uclause = $user; $shared=1;
	}
	
	//////Only Authorised User can Update Search////////
	$query = 'SELECT user FROM `saved_searches` WHERE id='.$searchId.' LIMIT 1';
	$get_search = mysql_query($query) or die('Bad SQL query getting Search for saved search id');
	$get_search = mysql_fetch_assoc($get_search);
	if($get_search === false) return;	
	if(count($get_search)==0){ die('Not found.'); }
	$rptu = $get_search['user'];
	if(($rptu === NULL && $db->user->userlevel == 'user') || ($rptu !== NULL && $rptu != $db->user->id)) return;
	//////Only Authorised User can Update Search////////
	
	$query = 'UPDATE saved_searches SET user=' . $uclause . ',name="' . $name . '",shared="' . $shared . '",searchdata="'
	. base64_encode(serialize($searchdata)) . '" where id=' . $searchId;
	global $logger;
	if(!mysql_query($query))
		{
			$log='Bad SQL query adding saved search:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
//	mysql_query($query) or die('Bad SQL query adding saved search');
	//$miid = mysql_insert_id();
	
	global $logger;
	if(!mysql_query('COMMIT'))
		{
			$log='Could not commit sql query Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}

}

function get_SearchData()
{
	global $db;
	
	if(!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']))	return;
	//load search from Saved Search

	$ssid = mysql_real_escape_string($_REQUEST['id']);
	//$query = 'SELECT * FROM saved_searches WHERE id=' . $ssid . ' AND (user=' . $db->user->id . ' or user IS NULL)' . ' LIMIT 1';
	$query = 'SELECT * FROM saved_searches WHERE id=' . $ssid . ' LIMIT 1';
	
	global $logger;
	if(!$res=mysql_query($query))
		{
			$log='Bad SQL query getting searchdata :'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	
	$row = mysql_fetch_array($res);
	//if($row === false) return;	//In this case, either the ID is invalid or it doesn't belong to the current user.

	//$show_value = 'showSearchData("' . $_REQUEST['id'] . '");';
	//echo($show_value);
	$data = unserialize(base64_decode($row['searchdata']));
	
	/***Part to Replace product/Area ID by product/Area Name when Displaying******/
	$jsonData=$data; 
	$filterData = json_decode($jsonData, true, 10);
	if(is_array($filterData["wheredata"]) && !empty($filterData["wheredata"]))
	{
		foreach($filterData["wheredata"] as $key=>$where_data)
		{
			if($where_data["columnname"] == 'product' || $where_data["columnname"] == 'area')
			{
				$ProdORAreaName=get_ProdORAreaName($where_data["columnvalue"], $where_data["columnname"]);
				if($ProdORAreaName)
				$where_data["columnvalue"]=$ProdORAreaName;
				$filterData["wheredata"][$key]=$where_data;
			}
		}
	}
	$data=json_encode($filterData);
	/***End Part to Replace product/Area ID by product/Area Name when Displaying******/
	
	//////Only Authorised User can View Search////////
	$query = 'SELECT user,searchdata,name,shared FROM `saved_searches` WHERE id='.$ssid.' LIMIT 1';
	$get_search = mysql_query($query) or die('Bad SQL query getting Search for saved search id');
	$get_search = mysql_fetch_assoc($get_search);
	if($get_search === false) return;	
	if(count($get_search)==0){ die('Not found.'); }
	$rptu = $get_search['user'];
	$shared = $get_search['shared'];
	if($rptu !== NULL && $rptu != $db->user->id && !$shared) return;
	//////Only Authorised User can View Search////////
	
	$shared=$row['shared'];
	if($shared)
	$owner_type="shared";
	else if($row['user'] !== NULL)
	$owner_type="mine";
	else if($row['user'] === NULL)
	$owner_type="global";

	if (!isset($res_ret))
		$res_ret = new stdClass();
		
	$res_ret->searchdata=$data;
	$res_ret->name= $row['name'];
	$res_ret->id= $row['id'];
	$res_ret->search_type= trim($owner_type);
	$res_ret->search_user= $row['user'];
	$res_ret->current_user= $db->user->id;
	$res_ret->user_level= $db->user->userlevel;
	echo json_encode($res_ret);


}

//returns HTML for saved searches controller
//not using any more since we have a search grid separately
function listSearchForm()
{
	global $db;

	$out = "<ul class='treeview' id='treeview_9000'>";
	$out .= "<li class='list'>" . "Load saved search";
	//$out .= "</li>";
	$out .= '<ul style="display:block;">';
	$query = 'SELECT id,name,user FROM saved_searches WHERE user=' . $db->user->id . ' OR user IS NULL OR shared=1 ORDER BY user';
	global $logger;
	if(!$res=mysql_query($query))
		{
			$log='Bad SQL query adding saved search list:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}

	while($ss = mysql_fetch_assoc($res))
	{
		$out .= "<li class='item'> <a href='#' onclick='showSearchData(\"" . $ss['id'] . "\",\"" . htmlspecialchars($ss['name']) . "\");return false;'>" . htmlspecialchars($ss['name']) . "</a></li>";
		//$out .= "<li class='item'> <a href='javascript:void();return false;'>" . htmlspecialchars($ss['name']) . "</a></li>";
		//$out .= "<li class='item'>"  . htmlspecialchars($ss['name']) . "</li>";
		//$out .= "<li>"  . htmlspecialchars($ss['name']) . "</li>";

	}
	$out .= "</ul>";
	$out .= "</li>";
	//$out .= "</ul>";
	return $out;
}

function listSearchProc()
{
	global $db;

	$ssid = mysql_real_escape_string($_REQUEST['searchId']);
	$query = 'SELECT searchdata FROM saved_searches WHERE id=' . $ssid . ' AND (user=' . $db->user->id . ' or user IS NULL OR shared=1)'
	. ' LIMIT 1';
	global $logger;
	if(!$res=mysql_query($query))
		{
			$log='Bad SQL query getting searchdata:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		
	$row = mysql_fetch_array($res);
	if($row === false) return;	//In this case, either the ID is invalid or it doesn't belong to the current user.
	unserialize(base64_decode($row['searchdata']));
	return $row;
}

function testQuery($jsonOp=0,$scriptCall=0,$data=null)
{
	$jsonData=$_REQUEST['data'];	
	
	if($scriptCall==1)
	{
		$jsonData = $data;
	}
	$actual_query= "";
	
	try {
		$actual_query= buildQuery($jsonData, false);
	}
	catch(Exception $e)
	{
		if($jsonOp==1)
		{
			return json_encode(array('status'=>0,'message'=>$e->getMessage()));
		}
		else
		{
		return $e->getMessage();
		}
	}
	$result = mysql_query($actual_query." LIMIT 0");
	if (mysql_errno()) {
		$error = "MySQL error ".mysql_errno().": ".mysql_error()."\n<br>When executing:<br>\n$actual_query\n<br>";
		if($jsonOp==1)
		{
			return json_encode(array('status'=>0,'message'=>$error));
		}
		else
		{
		return $error;
		}
	}
	else
	{
		$msg =  "Great! SQL Query has no syntax issues" ."\n<br>When executing:<br>\n$actual_query\n<br>";
		if($jsonOp==1)
		{
			return json_encode(array('status'=>1,'message'=>$msg));
		}
		else
		{
			return $msg;
		}		
	}

}

function runQuery($jsonData)
{
	
	global $db,$escapers,$replacements;
	global $Sphinx_search;
	//$jsonData=$_REQUEST['data'];
	$jsonData = preg_replace_callback('~.*?columnvalue\":\"(.*?)\"}[,\]]+.*?~', function($jsonData) {
		return str_replace($jsonData[1], mysql_real_escape_string($jsonData[1]), $jsonData[0]);
	},$jsonData);
	$jsonData = str_replace($escapers, $replacements, $jsonData);	
	$filterData = json_decode($jsonData, true, 10);
	///// Part To replace Product/Area name by id as its faster to search
	if(is_array($filterData["wheredata"]) && !empty($filterData["wheredata"]))
	{
		foreach($filterData["wheredata"] as $key=>$where_data)
		{
			if($where_data['columnname']=='All')
			{
				$Sphinx_search=$where_data['columnvalue'];
				unset($filterData["wheredata"][$key]);
				
			}
			elseif($where_data["columnname"] == 'product' || $where_data["columnname"] == 'area')
			{
				$ProdORAreaID=get_ProdORAreaID($where_data["columnvalue"], $where_data["columnname"]);
				if($ProdORAreaID)
				$where_data["columnvalue"]=$ProdORAreaID;
				$filterData["wheredata"][$key]=$where_data;
			}
		}
	}
	$jsonData=json_encode($filterData); ///Again Encode data which include ID instead of Names
	
	///// End of Part To replace Product/Area name by id as its faster to search
	
	$where_datas = $filterData["wheredata"];
	$select_columns=$filterData["columndata"];
	$override_vals = trim($filterData["override"]);
	$sort_datas = $filterData["sortdata"];
	$isOverride = !empty($override_vals);
	
	$prod=''; $area=''; $prod_flag=0; $area_flag=0; $OT_Exist_Flg=0; $OT_Flg=0; $link=''; $Prod_Flg=0; $Area_Flg=0;
	if(is_array($where_datas) && !empty($where_datas))
	{
		foreach($where_datas as $where_data)
		{
			if($where_data["columnname"] == 'product')
			{
				$prod.=$where_data["columnvalue"] .',';
			}
			elseif($where_data["columnname"] == 'area')
			{
				$area.=$where_data["columnvalue"] .',';
			}
			elseif($where_data["columnname"] != '' && $where_data["columnname"] != NULL)
			{
				$OT_Exist_Flg=1;
			}
		}
	}
	$prod = substr($prod, 0, -1); //strip last comma
	
	$area = substr($area, 0, -1); //strip last comma
	
	if(is_array($select_columns) && !empty($select_columns))
	{
		foreach($select_columns as $selectcolumn)
		{
			//if($selectcolumn["columnname"] != '' && $selectcolumn["columnname"] != NULL)
			//$OT_Exist_Flg=1;	//Currently we Dont need select columns so flag is commented
		}
	}
	
	if(is_array($sort_datas) && !empty($sort_datas) && (!$prod_flag || !$area_flag))
	{
		foreach($sort_datas as $sort_column)
		{
			if($sort_column['columnas']=='All')
			{
				unset($filterData["sortdata"][$ky]);
			}
			elseif($sort_column["columnas"] != '' && $sort_column["columnas"] != NULL)
			$OT_Exist_Flg=1;
		}
	}
	
	if($isOverride) 
	{
		$OT_Exist_Flg=1;
	}
	
	if(!isset($_REQUEST['forcePost']))
	{	
		$link=urlPath().'intermediary.php?p= '.$prod.'&a= '.$area;
		if(!empty($Sphinx_search)) $link.='&sphinx_s='.mysql_real_escape_string($Sphinx_search);
		if($OT_Exist_Flg and !empty($filterData["wheredata"]) )	//if OTT exists just send data as it is we will process it in run_trial_tracket
		{
			$link.='&JSON_search='.$jsonData;
		}
		else if($prod=='' && $area==''  && !empty($filterData["wheredata"]))
		$link.='&JSON_search='.$jsonData;
		
	
		header("Location: ".$link); 
	}
	elseif(isset($_REQUEST['forcePost']) && $_REQUEST['forcePost']==1  && !empty($filterData["wheredata"]))
	{
		$_REQUEST['JSON_search'] = $jsonData;
	}
}

function buildQuery($data, $isCount=false)
{
	global $escapers,$replacements;
	$actual_query = "";
	$data = preg_replace_callback('~.*?columnvalue\":\"(.*?)\"}[,\]]+.*?~', function($data) {
		return str_replace($data[1], mysql_real_escape_string($data[1]), $data[0]);
	},$data);	
	
	try {

		$jsonData = str_replace($escapers, $replacements, $data);	
		//$jsonData=$data;
		$filterData = json_decode($jsonData, true, 10);
		
		if(is_array($filterData["wheredata"])) {
			foreach($filterData["wheredata"] as $ky => &$vl)
			{
				$vl['columnvalue']=mysql_real_escape_string($vl['columnvalue']);
				if($vl['columnname']=='All')
				{
					$Sphinx_search=$vl['columnvalue'];
					unset($filterData["wheredata"][$ky]);
				}
			}
		}
		if(is_array($filterData["columndata"])) {
			foreach($filterData["columndata"] as $ky => $vl)
			{
				if($vl['columnname']=='All')
				{
					unset($filterData["columndata"][$ky]);
				}
			}
		}
		if(is_array($filterData["sortdata"])) {
			foreach($filterData["sortdata"] as $ky => $vl)
			{
				if($vl['columnname']=='All')
				{
					unset($filterData["sortdata"][$ky]);
				}
			}
		}
		if(is_array($filterData))
		array_walk_recursive($filterData, 'searchHandlerBackTicker','columnname');
		if(is_array($filterData))
		array_walk_recursive($filterData['columndata'], 'searchHandlerBackTicker','columnas');
		$alias= " dt"; //data_trial table alias
		$pd_alias= " pd"; //Products table alias
		$ar_alias= " ar"; //Areas table alias
		
		$where_datas = $filterData["wheredata"];
		$select_columns=$filterData["columndata"];
		$override_vals = trim($filterData["override"]);
		$sort_datas = $filterData["sortdata"];
		$isOverride = !empty($override_vals);
		
		$prod_flag=0; $area_flag=0; $prod_col=0; $area_col=0;
		if(is_array($where_datas) && !empty($where_datas))
		{
			foreach($where_datas as $where_data)
			{
				if($where_data["columnname"] == '`product`')
				$prod_flag=1;
				if($where_data["columnname"] == '`area`')
				$area_flag=1;
			}
		}
		
		if(is_array($select_columns) && !empty($select_columns))
		{
			foreach($select_columns as $selectcolumn)
			{
				if($selectcolumn["columnname"] == '`product`')
				{
					$prod_flag=1;
					$prod_col=1;	//This will need in overrriding Query
				}
				if($selectcolumn["columnname"] == '`area`')
				{
					$area_flag=1;
					$area_col=1;	//This will need in overrriding Query
				}
			}
		}
		
		if(is_array($sort_datas) && !empty($sort_datas) && (!$prod_flag || !$area_flag))
		{
			foreach($sort_datas as $ky => $sort_column)
			{
				if($sort_column['columnas']=='All')
				{
					unset($sort_datas[$ky]);
				}
				elseif($sort_column["columnas"] == '`product`')
					$prod_flag=1;
				elseif($sort_column["columnas"] == '`area`')
					$area_flag=1;
			}
		}
		$select_str = getSelectString($select_columns, $alias, $pd_alias, $ar_alias);
		$where_str = getWhereString($where_datas, $alias, $pd_alias, $ar_alias);
		$sort_str = getSortString($sort_datas, $alias, $pd_alias, $ar_alias);


		if($isOverride)
		{
			if($isCount)
			{
				 $actual_query .= "SELECT((";
			}
			else
			{
		  		$actual_query .= "(";
			}
		}

		$actual_query .= "SELECT ";

		if($isCount)
		{
	 		 $actual_query .= 	"COUNT(*) AS count";
		}
		else
		{
			$actual_query .= 	$select_str;
		}

		$actual_query .= " FROM data_trials " . $alias;
		
		if($prod_flag)
		$actual_query .= " JOIN product_trials pt ON (pt.`trial`=".$alias.".`larvol_id`) JOIN products ". $pd_alias ." ON (". $pd_alias .".`id`=pt.`product`)";
		
		if($area_flag)
		$actual_query .= " JOIN area_trials at ON (at.`trial`=".$alias.".`larvol_id`) JOIN areas ". $ar_alias ." ON (". $ar_alias .".`id`=at.`area`)";

		if(strlen(trim($where_str)) != 0)
		{
			$actual_query .= " WHERE " .$where_str;
		}

		if((!$isCount) && (strlen(trim($sort_str)) != 0))//Sort
		{
			$actual_query .= " ORDER BY " . $sort_str;
		}

		if($isOverride)//override string present
		{

	 		$override_str = getNCTOverrideString($override_vals, $alias, $pd_alias, $ar_alias, $select_str, $isCount, $prod_col, $area_col);
	  		if($isCount)
	  		{
	  			$actual_query .=  ") + (" . $override_str . ")) AS count";
	  		}
	  		else
	  		{
	  			$actual_query .= ") UNION (" . $override_str . ")";
	  		}
		}
	}
	catch(Exception $e)
	{
		throw $e;
	}
	return $actual_query;
}


function buildPubmedQuery($data, $pm_ids,$isCount=false)
{
	global $escapers,$replacements;
	if(isset($pm_ids) and !is_array($pm_ids)) 
	{
		$log = "pubmed ids need to passed as a array <br />\n";
		global $logger;
		$logger->fatal($log);
		return softDie($log);
	}
	
	$actual_query = "";
	try {
		$jsonData = str_replace($escapers, $replacements, $data);
		//$jsonData=$data;
		$filterData = json_decode($jsonData, true, 10);

		if(is_array($filterData))
			array_walk_recursive($filterData, 'searchHandlerBackTicker','columnname');
		if(is_array($filterData))
			array_walk_recursive($filterData['columndata'], 'searchHandlerBackTicker','columnas');
		$alias= " pm"; //pubmd_abstracts table alias
		$pd_alias= " pd"; //Products table alias
		$ar_alias= " ar"; //Areas table alias

		$where_datas = $filterData["wheredata"];
		$select_columns=$filterData["columndata"];
		//$override_vals = trim($filterData["override"]);
		$sort_datas = $filterData["sortdata"];
		//$isOverride = !empty($override_vals);

		$prod_flag=0; $area_flag=0; $prod_col=0; $area_col=0;
		if(is_array($where_datas) && !empty($where_datas))
		{
			foreach($where_datas as $where_data)
			{
				if($where_data["columnname"] == '`product`')
					$prod_flag=1;
				if($where_data["columnname"] == '`area`')
					$area_flag=1;
			}
		}

		if(is_array($select_columns) && !empty($select_columns))
		{
			foreach($select_columns as $selectcolumn)
			{
				if($selectcolumn["columnname"] == '`product`')
				{
					$prod_flag=1;
					$prod_col=1;	//This will need in overrriding Query
				}
				if($selectcolumn["columnname"] == '`area`')
				{
					$area_flag=1;
					$area_col=1;	//This will need in overrriding Query
				}
			}
		}

		if(is_array($sort_datas) && !empty($sort_datas) && (!$prod_flag || !$area_flag))
		{
			foreach($sort_datas as $ky => $sort_column)
			{
				if($sort_column["columnas"] == '`product`')
				$prod_flag=1;
				elseif($sort_column["columnas"] == '`area`')
				$area_flag=1;
			}
		}


		$select_str = getPubmedSelectString($select_columns, $alias, $pd_alias, $ar_alias);
		$where_str = getPubmedWhereString($where_datas, $alias, $pd_alias, $ar_alias,$pm_ids);
		$sort_str = getSortString($sort_datas, $alias, $pd_alias, $ar_alias);


		$actual_query .= "SELECT ";

		if($isCount)
		{
			$actual_query .= 	"COUNT(*) AS count";
		}
		else
		{
			$actual_query .= 	$select_str;
		}

		$actual_query .= " FROM pubmed_abstracts " . $alias;

		if($prod_flag)
			$actual_query .= " JOIN products ". $pd_alias ." ON (". $pd_alias .".`id`=".$alias.".`larvol_id`)";

		if($area_flag)
			$actual_query .= " JOIN areas ". $ar_alias ." ON (". $ar_alias .".`id`=".$alias.".`larvol_id`)";
			
			
		if(empty($pm_ids) and empty($_POST['index_all_abs']) )
			$actual_query .= " JOIN temp_table_1 tt ON (". $alias .".`source_id`=tt.`pubmed_id`) ";

		if(strlen(trim($where_str)) != 0)
		{
			$actual_query .= " WHERE " .$where_str;
		}

		if((!$isCount) && (strlen(trim($sort_str)) != 0))//Sort
		{
			$actual_query .= " ORDER BY " . $sort_str;
		}
	}
	catch(Exception $e)
	{
		throw $e;
	}
	return $actual_query;
}


function getNCTOverrideString($data, $alias, $pd_alias, $ar_alias, $select_str, $isCount, $prod_col, $area_col)
{
	$override_str = $data;
	$return = " SELECT ";
	if($isCount)
	{
		$return .= "COUNT(*) AS count ";
	}
	else
	{
		$return .= $select_str;
	}
	$override_str_arr = explode(',',$override_str);
	$override_str_arr = array_map(function($v){
		$v = trim($v);
		return "'".padnct($v)."' ";
	},$override_str_arr);
	$return .=  " FROM `data_trials` " . $alias . " ";
	
	if($prod_col)
	$return .= " JOIN product_trials pt ON (pt.`trial`=".$alias.".`larvol_id`) JOIN products " . $pd_alias . " ON (" . $pd_alias . ".`id`=pt.`product`)";
	
	if($area_col)
	$return .= " JOIN area_trials at ON (at.`trial`=".$alias.".`larvol_id`) JOIN areas " . $ar_alias . " ON (" . $ar_alias . ".`id`=at.`area`)";
	
	$return .=  " WHERE "
		
	."left(" . $alias . ".source_id,11) IN (".implode(',',$override_str_arr).")";
	return $return;


}

function getSelectString($data, $alias, $pd_alias, $ar_alias)
{
	$query = $alias . "." . "larvol_id, " . $alias . "." . "source_id, ";
	$select_columns = $data;
	if(!empty($select_columns))
	{
		foreach($select_columns as $selectcolumn)
		{
			if($selectcolumn['columnname']=='All')
			{
				continue;
			}
			elseif($selectcolumn["columnname"] == '`product`')
				$query .="" . $pd_alias . ".`name` AS " . $selectcolumn["columnas"] . ", ";
			elseif($selectcolumn["columnname"] == '`area`')
				$query .= "" . $ar_alias . ".`name` AS " . $selectcolumn["columnas"] . ", ";
			else
				$query .= $alias . "." . $selectcolumn["columnname"] . " AS " . $selectcolumn["columnas"] . ", ";
		}
	}
	$query = substr($query, 0, -2); //strip last comma
	return $query;

}

function getPubmedSelectString($data, $alias, $pd_alias, $ar_alias)
{
	$query = $alias . "." . "pm_id, " . $alias . "." . "articleid, ";
	$select_columns = $data;
	if(!empty($select_columns))
	{
		foreach($select_columns as $selectcolumn)
		{
			if($selectcolumn['columnname']=='All')
			{
				continue;
			}
			elseif($selectcolumn["columnname"] == '`product`')
			$query .="" . $pd_alias . ".`name` AS " . $selectcolumn["columnas"] . ", ";
			elseif($selectcolumn["columnname"] == '`area`')
			$query .= "" . $ar_alias . ".`name` AS " . $selectcolumn["columnas"] . ", ";
			else
				$query .= $alias . "." . article_title . " AS " . article_title . ", ";
		}
	}
	$query = substr($query, 0, -2); //strip last comma
	return $query;

}

function getSortString($data, $alias, $pd_alias, $ar_alias)
{
	$query = '';
	$sort_columns = $data;
	if(empty($sort_columns))
	{
		return $query;
	}
	foreach($sort_columns as $sort_column)
	{
		$sort_as = $sort_column["columnas"];
		$sorttype = $sort_as=="Ascending"? "asc" : "desc";
		if($sort_column['columnname']=='All')
		{
			continue;
		}
		if($sort_column["columnname"] == '`product`')
			$query .= $pd_alias.".`name` "  . $sorttype . ", ";
		elseif($sort_column["columnname"] == '`area`')
			$query .= $ar_alias.".`name` "  . $sorttype . ", ";
		else
			$query .= $alias . "." . $sort_column["columnname"] . " "  . $sorttype . ", ";
	}
	$query = substr($query, 0, -2); //strip last comma
	return $query;

}


function getWhereString($data, $alias, $pd_alias, $ar_alias)
{
	$wheredatas = $data;
    if(empty($wheredatas))
	{
	   return '';
	}
	$wheres = array();
	$wcount = 0;
	$prevchain = ' ';
	try {

		foreach($wheredatas as $where_data)
		{
			$op_name = $where_data["opname"];
			$column_name = $where_data["columnname"];
			$column_value = $where_data["columnvalue"];
			$chain_name = $where_data["chainname"];
			
			if($column_name == '`product`' || $column_name == '`area`')
				$column_name='`name`';
				
			$op_string = getOperator($op_name, $column_name, $column_value);
			$wstr = " " . $prevchain . " " . $op_string;
			
			if($where_data["columnname"] == '`product`')
				$wstr = str_replace('%f', $pd_alias . "." . $column_name,$wstr);
			elseif($where_data["columnname"] == '`area`')
				$wstr = str_replace('%f', $ar_alias . "." . $column_name,$wstr);
			else
				$wstr = str_replace('%f', $alias . "." . $column_name,$wstr);
			
			$pos = strpos($op_string,'%s1');

			if($pos === false) {
				$wstr = str_replace('%s', $column_value, $wstr);
			}
			else {
				$xx = explode('and;endl', $column_value);//and;endl
				$wstr = str_replace('%s1', $xx[0],$wstr);
				$wstr = str_replace('%s2', $xx[1],$wstr);
			}
			$prevchain = $chain_name;
			$wheres[$wcount++] = $wstr;
		}
		$wherestr = implode(' ', $wheres);
		$pos = strpos($prevchain,'.');
		if($pos === false)
		{
			//do nothing
		}
		else
		{
			$wherestr .= str_replace('.', '', $prevchain);//if . is present remove it and empty
		}
		//                if($pos == true)
		//                    $wherestr .= $prevchain;
	}
	catch(Exception $e)
	{
		throw $e;
	}
	return $wherestr;
	

}


function getPubmedWhereString($data, $alias, $pd_alias, $ar_alias, $pm_ids)
{	
	$wheredatas = $data;
	if(empty($wheredatas))
	{
		return '';
	}
	$wheres = array();
	$wcount = 0;
	try {
		
		//$wheres[$wcount++] = " ( ";
		$unique_searchwords = array();
		//$pubmed_fields = array('abstract_text','article_title','journal_title','author_affiliation');
		$pubmed_fields = array('CONCAT_WS(" ",pm.abstract_text,pm.author_affiliation,journal_title,article_title)') ;
		foreach ($pubmed_fields as $pm_field) {
			$prevchain = ' ';
						
			$numOpenParens   = substr_count(implode('',$wheres),'(');
			$numClosedParens = substr_count(implode('',$wheres),')');
			if($wcount > 0) {
				$wheres[$wcount++]=str_repeat(')', $numOpenParens - $numClosedParens);
				$wheres[$wcount++] = " OR ";
			}				
			foreach($wheredatas as $where_data)
			{
				$op_name = $where_data["opname"];
				$column_name = $where_data["columnname"];
				$column_value = $where_data["columnvalue"];
				$chain_name = $where_data["chainname"];
	
								
				if($column_name == '`product`' || $column_name == '`area`')
					$column_name='`name`';
	
				$op_string = getOperator($op_name, $column_name, $column_value);				
				$wstr = " " . $prevchain . " " . $op_string;				
				if($where_data["columnname"] == '`product`')
					$wstr = str_replace('%f', $pd_alias . "." . $column_name,$wstr);
				elseif($where_data["columnname"] == '`area`')
					$wstr = str_replace('%f', $ar_alias . "." . $column_name,$wstr);
				else {
					$wstr = str_replace('%f', $pm_field." ",$wstr);
				}
				$pos = strpos($op_string,'%s1');
	
				if($pos === false) {
					$wstr = str_replace('%s', $column_value, $wstr);
				}
				else {
					$xx = explode('and;endl', $column_value);//and;endl
					$wstr = str_replace('%s1', $xx[0],$wstr);
					$wstr = str_replace('%s2', $xx[1],$wstr);
				}
				$prevchain = $chain_name;
				/*
				if(array_key_exists($pm_field.$wstr,$unique_searchwords))
					continue;
				*/
				$unique_searchwords[$pm_field.$wstr]++;
				
				//echo $wcount ." " . $wstr . "\n";
				$wheres[$wcount++] = $wstr;
							
			}
		}
		//$wheres[$wcount++] = " ) ";
		
		if(empty($pm_ids) and empty($_POST['index_all_abs']) )
			$wherestr = implode(' ', $wheres) ;
		else
			$wherestr = implode(' ', $wheres);
		$pos = strpos($prevchain,'.');
		if($pos === false)
		{
			//do nothing
		}
		else
		{
			$wherestr .= str_replace('.', '', $prevchain);//if . is present remove it and empty
		}
		//                if($pos == true)
		//                    $wherestr .= $prevchain;
		
	
		if( !empty($pm_ids) and count($pm_ids) > 0)
			$wherestr = '( ' . $alias .'.source_id IN ('. implode(',',$pm_ids). ')) AND ('. $wherestr . ') ' ;
	}
	catch(Exception $e)
	{
		throw $e;
	}
	/*	
	$pos2 = stripos($wherestr, 'benralizumab');
	if ($pos2 !== false) {
		pr($wherestr);
	}
	*/
	return $wherestr;


}

function getOperator($opname, $column_name, $column_value)
{
	$val = '';
	try {
		switch($opname){
			case 'EqualTo':
				$val = "%f='%s'";
				break;
			case 'NotEqualTo':
				$val= "%f!='%s'";
				break;
			case 'StartsWith':
				$val ="%f LIKE '%s%'";
				break;
			case 'NotStartsWith':
				$val ="NOT(%f LIKE '%s%')";
				break;
			case 'Contains':
				$val ="%f LIKE '%%s%'";
				break;
			case 'NotContains':
				$val ="NOT(%f LIKE '%%s%')";
				break;
			case 'BiggerThan':
				$val ="%f>'%s'";
				break;
			case 'BiggerOrEqualTo':
				$val ="%f>='%s'";
				break;
			case 'SmallerThan':
				$val ="%f<'%s'";
				break;
			case 'SmallerOrEqualTo':
				$val ="%f<='%s'";
				break;
			case 'InBetween':
				$val ="%f BETWEEN '%s1' AND '%s2'";
				break;
			case 'NotInBetween':
				$val ="not(%f BETWEEN '%s1' AND '%s2')";
				break;

			case 'IsIn':
				$val ="%f IN (%s)";
				break;
			case 'IsNotIn':
				$val ="NOT(%f IN (%s))";
				break;
			case 'IsNull':
				$val ="%f IS NULL";
				break;
			case 'NotNull':
				$val ="%f IS NOT NULL";
				break;
			case 'Regex':
				$val = text_equal($column_name, $column_value);
				break;
			case 'NotRegex':
				$val = 'NOT (' . text_equal($column_name, $column_value) . ')';
				break;

		}
	}
	catch(Exception $e)
	{
		throw $e;
	}
	return $val;
}


//Outputs SQL expression to match text -- auto-detects use of regex and selects comparison method automatically
function text_equal($field,$value)
{
	//	$pcre = strlen($value) > 1
	//	&& $value[0] == '/'
	//	&& ($value[strlen($value)-1] == '/' || ($value[strlen($value)-2] == '/' && strlen($value) > 2));
	//	if($pcre)
	{
		//alexvp added exception
		$result=validateMask_PCRE($value);
		if(!$result)
		throw new Exception("Bad regex: $field = $value", 6);
		
		// return 'PREG_RLIKE("' . '%s' . '",' . '%f' . ')';
		// Put all regexes in unicode mode since there is no disadvantage!
		if(UNICODE_MODE_ENABLED)
			return 'PREG_RLIKE("' . '%s' . 'u' . '",' . '%f' . ')';
		else
			return 'PREG_RLIKE("' . '%s' . '",' . '%f' . ')';
	}
	//	else{
	//		return '%f' . '="' . '%s' . '"';
	//	}
}


function validateMask_PCRE($s)
{
	//logger variable in db.php
	global $logger;

	$s=addslashes($s);
	$query = "SELECT PREG_CHECK('$s')";

	$time_start = microtime(true);
	$res = mysql_query($query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:validateMask_PCRE';
	$logger->info($log);
	unset($log);
	if($res === false)
	{
		$log = 'Bad SQL query on search: ' . $query . "<br />\n" . mysql_error();
		global $logger;
		$logger->fatal($log);
		return softDie($log);
	}

	list($check)=mysql_fetch_row($res);
	return ($check==1); // pcre ok?
}

function get_ProdORAreaID($column_value, $column_type)
{
	$query = "select id from " . $column_type . "s where name='" . mysql_real_escape_string($column_value) . "' ";
	$row = mysql_fetch_assoc(mysql_query($query)) or die('Bad SQL Query getting '. $column_type .' ID instead of names ');
	
	return $row['id'];
}

function get_ProdORAreaName($column_value, $column_type)
{
	$query = "select name from " . $column_type . "s where id='" . mysql_real_escape_string($column_value) . "' ";
	$row = mysql_fetch_assoc(mysql_query($query)) or die('Bad SQL Query getting '. $column_type .' Name instead of names ');
	
	return $row['name'];
}

//processes postdata for saving new searches
function copySearch_Data()
{
	$reportid=$_REQUEST['reportid'];
	global $db;
	
	//////Only Authorised User can Copy Search////////
	$query = 'SELECT user,searchdata,name,shared FROM `saved_searches` WHERE id='.$reportid.' LIMIT 1';
	$get_search = mysql_query($query) or die('Bad SQL query getting Search for saved search id');
	$get_search = mysql_fetch_assoc($get_search);
	if($get_search === false) return;	
	if(count($get_search)==0){ die('Not found.'); }
	$rptu = $get_search['user'];
	$shared = $get_search['shared'];
	if($rptu !== NULL && $rptu != $db->user->id && !$shared) return;
	//////Only Authorised User can Copy Search////////
	
	$user = $db->user->id;
	$searchdata = $get_search['searchdata'];
	$name = mysql_real_escape_string('Copy Of '.$get_search['name']);
	
	$query = 'INSERT INTO saved_searches SET user=' . $user . ',name="' . $name . '",searchdata="'. $searchdata . '"';
	
	$insert_res=mysql_query($query) or die('Bad SQL query adding copy of search');
	$miid = mysql_insert_id();
	global $logger;
	if(!$insert_res)
		{
			$log='Bad SQL query adding copy of search:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	
	if(!mysql_query('COMMIT'))
		{
			$log='Couldn\'t commit SQL query: "COMMIT", Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	return $miid;
}

?>
