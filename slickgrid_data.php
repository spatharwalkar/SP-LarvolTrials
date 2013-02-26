<?php
function slickgrid_data(){
	global $db;
	$query = 'SELECT id,name,user,category FROM `rpt_masterhm` '.(($db->user->userlevel != 'root') ? 'WHERE user IS NULL OR user=' . $db->user->id . ' OR shared=1 ' :'').'ORDER BY user';
	$res = mysql_query($query) or die('Bad SQL query retrieving master heatmap report names');
	$categoryArr  = array('');
	$outArr = array();
	while($row = mysql_fetch_array($res))
	{
		if($row['category'])
			$categoryArr[$row['category']] = $row['category'];
		$outArr[] = $row;
	}
	sort($categoryArr);
	
	$Uncategorized_Flg=0;
	
	$dataArrComplete = array();
	$parent_count = -1;
	foreach($categoryArr as $category)
	{
		$parent_count ++;
		
		$dataArr = array();
		$tree_cookie_arr=array();
		if($category == NULL || $category == '')
			$category_name = 'Uncategorized';
		else
			$category_name = $category;
	
		$tree_cookie_arr=explode('****',$_COOKIE['tree_grid_cookie']);
		if(in_array(trim($category_name),$tree_cookie_arr) && isset($_COOKIE['tree_grid_cookie']) && $_COOKIE['tree_grid_cookie'] != '')
			$expand='false';
		else
			$expand='true';
		
		$dataArr['mhmcategory'] = ($category_name);
		$dataArr['owner'] = '';
		$dataArr['rows'] = '';
		$dataArr['cols'] = '';
		$dataArr['iconCls'] = 'task-folder';
		$dataArr['expanded'] = $expand;
		$dataArr['indent'] = 0;
		$dataArr['parent'] = 0;
		$dataArrComplete[] = $dataArr;
		
		foreach($outArr as $row)
		{
			$dataArr = array();
			$owner='';
			if($row['user'] != NULL && $row['user'] != '')
			{
				$owner_query = 'SELECT username FROM `users` WHERE id=' . $row['user'];
				$owner_res = mysql_query($owner_query) or die('Bad SQL query retrieving username in master heatmap report list');
				if(mysql_num_rows($owner_res) > 0)
				{
					while($owner_row = mysql_fetch_array($owner_res))
						$owner=$owner_row['username'];
				}
				else
				{
					$refresh_query = 'UPDATE rpt_masterhm SET user=NULL, shared=0 WHERE id=' . $row['id'];
					$refresh=mysql_query($refresh_query) or die('Bad SQL Query Updating data for invalid users');
					if($refresh)
						$owner='Global';
				}
			}
			else
			{
				$owner='Global';
			}
				
			$row_query = 'SELECT max(num) as row FROM `rpt_masterhm_headers` WHERE `report`=' . $row['id'] . ' AND `type`=\'product\'';
			$row_res = mysql_query($row_query) or die('Bad SQL query retrieving Number of Row in master heatmap report list');
			while($row_row = mysql_fetch_array($row_res))
				$rows=$row_row['row'];
			$col_query = 'SELECT max(num) as col FROM `rpt_masterhm_headers` WHERE `report`=' . $row['id'] . ' AND `type`=\'area\'';
			$col_res = mysql_query($col_query) or die('Bad SQL query retrieving Number of Columns in master heatmap report list');
			while($col_row = mysql_fetch_array($col_res))
				$cols=$col_row['col'];
				
				

			if($row['category']== $category)
			{
				$Uncategorized_Flg=1;
				$report_name = '<a style="text-decoration:none;color:#000000;" href="master_heatmap.php?id=' . $row['id'] . '">'.htmlspecialchars(strlen($row['name'])>0?$row['name']:('(report '.$row['id'].')')) . '</a>';
	
				$dataArr['mhmcategory'] = ($report_name);
				$dataArr['owner'] = $owner;
				$dataArr['rows'] = $rows;
				$dataArr['cols'] = $cols;
				$dataArr['iconCls'] = 'task';
				$dataArr['expanded'] = '';
				$dataArr['indent'] = 1;
				$dataArr['parent'] = $parent_count;
				$dataArrComplete[] = $dataArr;
			}
			
		}
		$outer_count++;
	}
	return $dataArrComplete;
}