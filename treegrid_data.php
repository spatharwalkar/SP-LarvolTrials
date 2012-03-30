<?php
	require_once("db.php");
	global $db;
	$query = 'SELECT id,name,user,category FROM `rpt_masterhm` WHERE user IS NULL OR user=' . $db->user->id . ' OR shared=1 ORDER BY user';
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
	$Json_Tree=" {\"text\":\".\",\"children\": [ ";
	foreach($categoryArr as $category)
	{
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
		
		$Prev_Tree=$Json_Tree;
		
		$Json_Tree.=" { mhmcategory:'<a ondblclick=\"tree_grid_cookie(\'".trim($category_name)."\');\">".$category_name."</a>',
        owner:'',
		rows:'',
		cols:'',
        iconCls:'task-folder',
        expanded: ".$expand.",
        children:[ ";
		foreach($outArr as $row)
		{
			if($row['user'] != NULL && $row['user'] != '')
			{
				$owner_query = 'SELECT username FROM `users` WHERE id=' . $row['user'];
				$owner_res = mysql_query($owner_query) or die('Bad SQL query retrieving username in master heatmap report list');
				while($owner_row = mysql_fetch_array($owner_res))
				$owner=$owner_row['username'];
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
				$Json_Tree.="{
                mhmcategory:'". addslashes($report_name) ."',
                owner:'". $owner ."',
				rows:'".$rows."',
				cols:'".$cols."',
                leaf:true,
                iconCls:'task'
            	}, ";
			}
		}
		$Json_Tree = substr($Json_Tree, 0, -1); //strip last comma
		$Json_Tree .=' ]}, ';
		
		if(!$Uncategorized_Flg && $category_name = 'Uncategorized')//If Uncategorized is empty
		$Json_Tree=$Prev_Tree;
	}
	$Json_Tree = substr($Json_Tree, 0, -1); //strip last comma
	$Json_Tree .=' ]} ';
	
	print $Json_Tree;			
?>