<?php

/** Generate an online heatmap
 $id -- the ID of the heatmap or disease (interperetation depends on $auto)
 $auto -- Generate an "autoheatmap", meaning $id will be a disease (otherwise, it is a static heatmap id)
 $fullpage -- Output an entire valid xhtml page (otherwise, just outputs the ohm code. make sure to inlcude the right css/js files)
 $direct -- echo directly to browser (recommended) (otherwise, return the html)
 $li -- enter Larvol Insight mode
 
 returns false on error
*/
function ohm($id, $auto = false, $fullpage = false, $direct = true, $li = false)
{
	global $now;
	$id = (int)$id;

	if(!$direct)
	{
		ob_clean();
		ob_start();
	}	
	
	$query = 'SELECT display_name,dtt FROM rpt_masterhm WHERE id=' . $id;
	$res = mysql_query($query);
	if($res === false) return ohm_die($direct,'Bad SQL query finding heatmap name');
	$hmidRow = mysql_fetch_assoc($res);
	if($hmidRow === false) return ohm_die($direct,'Invalid heatmap ID');
	$heatmapName = $hmidRow['display_name'];
	$DTT = $hmidRow['dtt'];
	
	if($fullpage)
	{
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Online Heatmap</title>
<base target="_blank"/>
<script type="text/javascript" src="scripts/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="scripts/jquery-migrate-1.2.1.js"></script>
<script type="text/javascript" src="scripts/jquery-ui-1.10.3.custom.min.js"></script>
<script type="text/javascript" src="scripts/date/jquery.jdpicker.js"></script>
<script type="text/javascript" src="date/init.js"></script>
<script type="text/javascript" src="css/getcssrule.js"></script>
<link href="date/date_input.css" rel="stylesheet" type="text/css" media="all" />
<link href="scripts/date/jdpicker.css" rel="stylesheet" type="text/css" media="screen" />
<link href="css/themes/cupertino/jquery-ui-1.10.3.custom.min.css" rel="stylesheet" type="text/css" media="screen" />
<link href="css/ohm/ohm.css" rel="stylesheet" type="text/css" />

<!--[if lte IE 9]><link href="css/ohm/ie9.css" rel="stylesheet" type="text/css" /><![endif]-->
<!--[if IE 8]><link href="css/ohm/ie8.css" rel="stylesheet" type="text/css" /><![endif]-->
<!--[if IE 7]><link href="css/ohm/ie7.css" rel="stylesheet" type="text/css" /><![endif]-->

<script type="text/javascript" src="css/ohm/ohm.js"></script>';
	}
	
	//Generate javascript
	//normalizeDuration converts durations into the past TO absolute dates based on today's date
	echo('<script type="text/javascript">' . "\n" . '//<![CDATA[' . "\n//this comment keeps the following line from breaking in ie7/8\n"
		. 'function normalizeDuration(timerange){switch(timerange){');
	echo('case "now": return new Date(' . date('Y',$now) . ',' . (date('n',$now)-1) . ',' . date('j',$now) . ');');
	$time = $now - (7*24*60*60);
	echo('case "1 week": return new Date(' . date('Y',$time) . ',' . (date('n',$time)-1) . ',' . date('j',$time) . ');');
	$time = $now - (2*7*24*60*60);
	echo('case "2 weeks": return new Date(' . date('Y',$time) . ',' . (date('n',$time)-1) . ',' . date('j',$time) . ');');
	$time = $now - (30*24*60*60);
	$defChange = $time;	//use this later to pre-populate default changes
	echo('case "1 month": return new Date(' . date('Y',$time) . ',' . (date('n',$time)-1) . ',' . date('j',$time) . ');');
	$time = $now - (3*30*24*60*60);
	echo('case "1 quarter": return new Date(' . date('Y',$time) . ',' . (date('n',$time)-1) . ',' . date('j',$time) . ');');
	$time = $now - (6*30*24*60*60);
	echo('case "6 months": return new Date(' . date('Y',$time) . ',' . (date('n',$time)-1) . ',' . date('j',$time) . ');');
	$time = $now - (360*24*60*60);
	echo('case "1 year": return new Date(' . date('Y',$time) . ',' . (date('n',$time)-1) . ',' . date('j',$time) . ');');
	echo("}var datearr = (''+timerange).split('-');return new Date(datearr[0],datearr[1]-1,datearr[2]);}");
	//Get most of the heatmap's data here
	$viewmodes = array('total','active','active_indlead','active_owner_sponsored');
	$defVM = 'active_indlead';
	$cells = array(); //contains numbers going in all cells for each view mode
	$cellMeta = array(); //contains most cell data other than counts
	$fields = array();
	foreach($viewmodes as $vm)
	{
		$cells[$vm] = array();
		$fields[] = 'count_' . $vm;
	}
	$fields = implode(',', $fields) . ',bomb,bomb_lastchanged,bomb_explain,if(phase4_override, 4, highest_phase) AS phase'
		. ',if(phase4_override AND highest_phase != 4,highest_phase,highest_phase_prev) AS phase_prev'
		. ',greatest(highest_phase_lastchanged,phase4_override_lastchanged) as phase_lastchanged,phase_explain,phase_explain_lastchanged,'
		. 'filing,filing_lastchanged,entity1,entity2';
	$rowcolFrom = ' FROM (rpt_masterhm_headers LEFT JOIN entities ON type_id=entities.id) ';
	$rcFields = 'rpt_masterhm_headers.display_name AS display_name,rpt_masterhm_headers.category AS category,tag AS tag,'
			. 'entities.display_name AS ent_dn,entities.name AS name,entities.class AS class, entities.id AS ent_id';
	$rows = 'SELECT type_id AS ent,' . $rcFields . $rowcolFrom
			. 'WHERE type="row" AND report=' . $id . ' ORDER BY num ASC';
	$cols = 'SELECT type_id AS ent,' . $rcFields . $rowcolFrom
			. 'WHERE type="column" AND report=' . $id . ' ORDER BY num ASC';
	$res = mysql_query($rows);
	if($res === false) return ohm_die($direct, 'Bad SQL query getting rows: ' . $rows);
	$rows = array();
	for($i=0;$set = mysql_fetch_assoc($res);++$i)
	{
		$rows[$i] = $set;
		if($rows[$i]['display_name'] == 'NULL') $rows[$i]['display_name'] = NULL; //workaround for some bad data created by old editing page
	}

	$res = mysql_query($cols);
	if($res === false) return ohm_die($direct, 'Bad SQL query getting columns: ' . $cols);
	$cols = array();
	for($i=0;$set = mysql_fetch_assoc($res);++$i) $cols[$i] = $set;
	if($DTT)//if last column is DTT, drop it here
	{
		$DTT = $cols[count($cols)-1]['ent_id'];
		unset($cols[count($cols)-1]);
	}
	
	$query = 'SELECT ' . $fields . ' FROM rpt_masterhm_cells '
		. 'LEFT JOIN rpt_masterhm_headers AS rh1 ON entity1=rh1.type_id '
		. 'LEFT JOIN rpt_masterhm_headers AS rh2 ON entity2=rh2.type_id '
		. 'WHERE rh1.report=' . $id . ' AND rh2.report=' . $id
		. ' AND ((rh1.type="row" AND rh2.type="column") OR (rh2.type="row" AND rh1.type="column"))';
	$res = mysql_query($query);
	if($res === false) return ohm_die($direct, 'Bad SQL query getting HM cells');
	while($set = mysql_fetch_assoc($res))
	{
		//numbers for each view mode
		foreach($viewmodes as $vm)
		{
			$cells[$vm][$set['entity1']][$set['entity2']] = $set['count_'.$vm];
		}
		//changes: [b]omb, [f]iling, [e]xplanation, [p]hasechange, [l]astphase
		$changes = array();
		if($set['bomb_lastchanged'] !== NULL && $set['bomb'] != 'none')
		{
			$ts = strtotime($set['bomb_lastchanged']);
			$changes[] = 'b:new Date(' . date('Y',$ts) . ',' . (date('n',$ts)-1) . ',' . date('j',$ts) . ')';
		}
		if($set['filing_lastchanged'] !== NULL && $set['filing'] != NULL)
		{
			$ts = strtotime($set['filing_lastchanged']);
			$changes[] = 'f:new Date(' . date('Y',$ts) . ',' . (date('n',$ts)-1) . ',' . date('j',$ts) . ')';
		}
		if($set['phase_explain_lastchanged'] !== NULL && $set['phase_explain'] !== NULL)
		{
			$ts = strtotime($set['phase_explain_lastchanged']);
			$changes[] = 'e:new Date(' . date('Y',$ts) . ',' . (date('n',$ts)-1) . ',' . date('j',$ts) . ')';
		}
		if($set['phase_lastchanged'] !== NULL && $set['phase_prev'] != NULL && $set['phase_prev'] != $set['phase'])
		{
			$ts = strtotime($set['phase_lastchanged']);
			$changes[] = 'p:new Date(' . date('Y',$ts) . ',' . (date('n',$ts)-1) . ',' . date('j',$ts) . '),l:"' . $set['phase_prev'] . '"';
		}
		//cell meta data
		$cellMeta[$set['entity1']][$set['entity2']]['changes'] = '{' . implode(',',$changes) . '}';
		$directFields = array('bomb','bomb_explain','bomb_lastchanged','phase','phase_prev','phase_lastchanged',
						'phase_explain','phase_explain_lastchanged','filing','filing_lastchanged');
		foreach($directFields as $df)
		{
			if(($df == 'phase_prev' || $df == 'phase_lastchanged') && $set['phase_prev'] == $set['phase'])
			{
				continue;
			}
			// fixed the issue coming in bumb description url for &sect in $section_id
			if($df=="bomb_explain"){
				$cellMeta[$set['entity1']][$set['entity2']][$df] = htmlentities($set[$df]);
			}else{
				$cellMeta[$set['entity1']][$set['entity2']][$df] = $set[$df];
			}
		}
	}
	
	//output javascript arrays containing the cell numbers for each view mode
	echo('var cells_all=[');
	$allnums = array();
	foreach($rows as $row)
	{
		$rownums = array();
		foreach($cols as $col)
		{
			$rownums[] = isset($cells['total'][$row['ent_id']][$col['ent_id']]) ? $cells['total'][$row['ent_id']][$col['ent_id']] : $cells['total'][$col['ent_id']][$row['ent_id']];
		}
		$allnums[] = '[' . implode(',', $rownums) . ']';
	}
	echo(implode(',',$allnums) . '];');

	echo('var cells_active=[');
	$allnums = array();
	foreach($rows as $row)
	{
		$rownums = array();
		foreach($cols as $col)
		{
			$rownums[] = isset($cells['active'][$row['ent_id']][$col['ent_id']]) ?
					$cells['active'][$row['ent_id']][$col['ent_id']] : $cells['active'][$col['ent_id']][$row['ent_id']];
		}
		$allnums[] = '[' . implode(',', $rownums) . ']';
	}
	echo(implode(',',$allnums) . '];');
	
	echo('var cells_active_industry=[');
	$allnums = array();
	foreach($rows as $row)
	{
		$rownums = array();
		foreach($cols as $col)
		{
			$rownums[] = isset($cells['active_indlead'][$row['ent_id']][$col['ent_id']]) ?
					$cells['active_indlead'][$row['ent_id']][$col['ent_id']] : $cells['active_indlead'][$col['ent_id']][$row['ent_id']];
		}
		$allnums[] = '[' . implode(',', $rownums) . ']';
	}
	echo(implode(',',$allnums) . '];');
	
	echo('var cells_active_os=[');
	$allnums = array();
	foreach($rows as $row)
	{
		$rownums = array();
		foreach($cols as $col)
		{
			$rownums[] = isset($cells['active_owner_sponsored'][$row['ent_id']][$col['ent_id']]) ?
					$cells['active_owner_sponsored'][$row['ent_id']][$col['ent_id']] : $cells['active_owner_sponsored'][$col['ent_id']][$row['ent_id']];
		}
		$allnums[] = '[' . implode(',', $rownums) . ']';
	}
	echo(implode(',',$allnums) . '];');
	
	//generate javascript array containing change data for each cell
	echo('var changes=[');
	$allChanges = array();
	foreach($rows as $row)
	{
		$rowChanges = array();
		foreach($cols as $col)
		{
			$rowChanges[] = isset($cellMeta[$row['ent_id']][$col['ent_id']]) ?
					$cellMeta[$row['ent_id']][$col['ent_id']]['changes'] : $cellMeta[$col['ent_id']][$row['ent_id']]['changes'];
		}
		$allChanges[] = '[' . implode(',', $rowChanges) . ']';
	}
	echo(implode(',',$allChanges) . ']');
	
	//done generating javascript, now add the boilerplate html between the JS and the actual heatmap, most of this is for the sidebar controls
	echo '
 //]]>
</script>

<!--[if lte IE 9]><script type="text/javascript" src="css/ohm/ie9.js"></script><![endif]-->
<!--[if IE 8]><script type="text/javascript" src="css/ohm/ie8.js"></script><![endif]-->
<!--[if lte IE 7]><script type="text/javascript" src="css/ohm/ie7.js"></script><![endif]-->
</head>

<body onload="window.onscroll();updateviewmode();updatechanges();">
<div class="heatmap">
<div class="hmcontrol">

<div class="hmcbutton info">
  <div class="hmcmenu" style="width:270px;height:130px;">
    <div class="hmchead">Help</div>
    <table class="hmchelp"><tr><td><img src="images/sbomb.png" alt="bomb"/></td><td colspan="6">Discontinued</td></tr><tr><td><img src="images/expanded_crop.gif" alt="filing"/></td><td colspan="6">Filing details</td></tr><tr><td class="ch" style="background-color:#EEE;">&nbsp;</td><td colspan="6">Red border: Record updated</td></tr>
    <tr><td>Phase</td><td class="pn">N/A</td><td class="p0">0</td><td class="p1">1</td><td class="p2">2</td><td class="p3">3</td><td class="p4">4</td></tr></table>
  </div>
</div>
<div class="hmcbutton view">
  <div class="hmcmenu" id="viewmenu" style="width:340px;height:200px;">
    <div class="hmchead">View</div>
    <select name="viewmode" size="1" onchange="updateviewmode();" id="viewmode">
		<option value="ai" selected="selected">Active industry trials</option>
		<option value="aos">Active owner-sponsored trials</option>
		<option value="act">Active trials</option>
		<option value="all">All trials</option>
	</select><br /><br />
    <div>Highlight updates:<span style="color:#f6931f;">
      <input name="sr" type="text" class="jdpicker hmdates" id="startrange" onchange="updatechanges();" value="now" readonly="readonly" /> - <input type="text" id="endrange" name="er" value="1 month" readonly="readonly" class="jdpicker hmdates" onchange="updatechanges();"/></span><br />
      <div id="slider-range-min" style="width:250px;margin-left:50px;margin-top:5px;"></div>
  </div>
  </div>
</div>
<div class="hmcbutton export">
  <div class="hmcmenu" style="width:42px;">
    <div class="hmchead">Export<br /><hr/>
    <form method="get" action="master_heatmap.php">
	<input type="hidden" name="view_type" id="view_type" value="indlead"/>
    <input type="hidden" name="id" id="id" value="' . $id . '"/>
    <input type="image" name="excel" id="excel" src="images/blank.gif" style="background-image:url(images/excel_30.png)" title="Excel"/>
    <input type="image" name="pdf" id="pdf" src="images/blank.gif" style="background-image:url(images/pdf_30.png)" title="PDF"/>
    </form>
    </div>
  </div>
</div>

</div>

<div class="hmdata">
<table width="200" border="0" cellspacing="1" cellpadding="0" id="mainhm">
<tr class="hrow colfloat">
<th class="spc" rowspan="2"><div>' . ($li ? '&nbsp;' : $heatmapName) . '</div></th>';
	//Determine column category ranges
	$cats = array();
	$cl = 0;
	foreach($cols as $col)
	{
		if($cl > 0 && $cats[$cl-1]['name'] == $col['category'])
		{
			++$cats[$cl-1]['count'];
		}else{
			$cats[] = array('name' => $col['category'], 'count' => 1);
			++$cl;
		}
	}
    //output column categories
    foreach($cats as $cat)
    {
        if(!empty($cat['name'])){
            echo('<th colspan="' . $cat['count'] . '" class="cat"><div>' . $cat['name'] . '</div></th>');
        } else{
            echo('<th colspan="' . $cat['count'] . '" class="cat"></th>');
        }
    }
	echo('</tr><tr class="colfloat">');
	//output column headers
	foreach($cols as $col)
	{
		$url = 'intermediary.php?e2=' . $col['ent_id'] . '&list=1&itype=1,0&osflt=&hm='.$id;
		$colHeader = $col['display_name'];
		if(empty($colHeader))
		{
			if(strlen($col['ent_dn']) > 0)
				$colHeader = $col['ent_dn'];
			else if($col['class'] == 'Product' && strlen($col['name']))
				$rowHeader = $col['name'];
			else
				$colHeader = $col['class'] . ' ' . $col['ent_id'];
		}
		if($col['class'] == 'Product')
		{
			//$query = 'SELECT name FROM entity_relations LEFT JOIN entities ON parent=id WHERE class="Institution" AND child=' . $col['ent_id'];
			$query = 'SELECT company AS name FROM entities WHERE id=' . $col['ent_id'];
			$res = mysql_query($query);
			if($res === false) return ohm_die($direct, 'Bad SQL query getting companies of product for column');
			$prodCompanies = array();
			while($companyRow = mysql_fetch_assoc($res)) $prodCompanies[] = $companyRow['name'];
			$colHeader = productFormatLI($colHeader, $prodCompanies, $col['tag']);
		}
		echo('<th class="col"><div><div><a href="' . htmlspecialchars($url) . '">' . $colHeader . '</a></div></div></th>');
	}
	echo('</tr>');
	//determine entity IDs of rows within each section
	$lastsect = '';
	$sectionIDs = array();
	foreach($rows as $row)
	{
		$sectionIDs[$row['category']][] = $row['ent_id'];
	}
	//output main body of heatmap
	$numcols = count($cols)+1;
	$numrows = count($rows)+1;
	$lastsect = '';
	$changetypes = array('bomb_lastchanged','phase_lastchanged','phase_explain_lastchanged','filing_lastchanged');
	foreach($rows as $rowIndex => $row)
	{
		if($row['category'] != $lastsect)	//add row category header if new row category encoutered
		{
			$lastsect = $row['category'];
			$url = 'intermediary.php?e1=' . implode(',',$sectionIDs[$row['category']]) . '&e2=' . $DTT . '&list=1&itype=1,0&osflt=&hm=' . $id;
			echo('<tr><td class="sect" colspan="' . $numcols . '"><div><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($row['category']) . '</a></div></td></tr>');
		}
		$rowHeader = $row['display_name'];
		if(empty($rowHeader))
		{
			if(strlen($row['ent_dn']) > 0)
				$rowHeader = $row['ent_dn'];
			else if($row['class'] == 'Product' && strlen($row['name']))
				$rowHeader = $row['name'];
			else
				$rowHeader = $row['class'] . ' ' . $row['ent_id'];
		}
		if($row['class'] == 'Product')
		{	//todo: use real name of related company instead of company text given in product itself
			//$query = 'SELECT name FROM entity_relations LEFT JOIN entities ON parent=id WHERE class="Institution" AND child=' . $row['ent_id'];
			$query = 'SELECT company AS name FROM entities WHERE id=' . $row['ent_id'];
			$res = mysql_query($query);
			if($res === false) return ohm_die($direct, 'Bad SQL query getting companies of product for row');
			$prodCompanies = array();
			while($companyRow = mysql_fetch_assoc($res)) $prodCompanies[] = $companyRow['name'];
			$rowHeader = productFormatLI($rowHeader, $prodCompanies, $row['tag']);
		}
		$url = 'intermediary.php?e1=' . $row['ent_id'] . '&list=1&itype=1,0&osflt=&hm=' . $id;
		echo('<tr><th class="row"><div><a href="' . htmlspecialchars($url) . '">' . $rowHeader . '</a></div></th>');
		foreach($cols as $columnIndex => $col)
		{
			$blank = true;
			$mouseover = '';
			$forward = true;
			if(isset($cellMeta[$col['ent_id']][$row['ent_id']])) $forward = false;
			foreach($viewmodes as $vm)
			{
				if(($forward ? $cells[$vm][$row['ent_id']][$col['ent_id']] : $cells[$vm][$col['ent_id']][$row['ent_id']]) != 0)
					$blank = false;
			}
			if($blank)
			{
				echo('<td>&nbsp;</td>');
				continue;
			}
			$cellInfo = $forward ? $cellMeta[$row['ent_id']][$col['ent_id']] : $cellMeta[$col['ent_id']][$row['ent_id']];
			$cellChanges = array();
			$cellClasses = array();
			foreach($changetypes as $cht)
			{
				if($cellInfo[$cht] > $defChange) $cellChanges[] = $cht;
			}
			if(count($cellChanges)) $cellClasses[] = 'ch';
			switch($cellInfo['phase'])
			{
				case '0':																		$cellClasses[] = 'p0'; break;
				case '1':case '0/1':case '1a':case '1b':case '1a/1b':case '1c':					$cellClasses[] = 'p1'; break;
				case '2':case '1/2':case '1b/2':case '1b/2a':case '2a':case '2a/2b':case '2b':	$cellClasses[] = 'p2'; break;
				case '3':case '2/3':case '2b/3':case '3a':case '3b':							$cellClasses[] = 'p3'; break;
				case '4':case '3/4':case '3b/4':												$cellClasses[] = 'p4'; break;
				case 'N/A':default:																$cellClasses[] = 'pn';
			}
			if(strlen($cellInfo['phase_prev']))
				$mouseover .= '<a class="pha' . (in_array('phase_lastchanged',$cellChanges)?' ch':'') . '">Highest phase updated from: Phase ' . $cellInfo['phase_prev'] . '</a>';
			/* For each freetext item in the mouseover:
			   If there are 2+ links don't change the content and just structure it with a wrapping span
			   If there is just one or 0 links then strip out all tags, and structure it with a new link tag using the given url
			    - this is very space efficient and has less chance of allowing invalid markup
			*/
			if($cellInfo['bomb'] != 'none')
			{
				$cellClasses[] = 'bom';
				if(substr_count($cellInfo['bomb_explain'],'http') >= 2)
				{
					$mouseover .= '<span class="bex' . (in_array('bomb_lastchanged',$cellChanges)?' ch':'') . '">' . $cellInfo['bomb_explain'] . '</span>';
				}else{
					//todo: use separate url
					$linkStart = strpos($cellInfo['bomb_explain'],'http://');
					$linkEnd = strpos($cellInfo['bomb_explain'],'"',$linkStart);
					$linkLength = $linkEnd - $linkStart;
					$url = substr($cellInfo['bomb_explain'],$linkStart,$linkLength);
					//end todo
					$url = htmlspecialchars($url); 
					$mouseover .= '<a href="' . $url . '" class="bex' . (in_array('bomb_lastchanged',$cellChanges)?' ch':'') . '">'
							. strip_tags($cellInfo['bomb_explain']) . '</a>';
				}
			}
			if(strlen($cellInfo['filing']))
			{
				$cellClasses[] = 'fil';
				if(substr_count($cellInfo['filing'],'http') >= 2)
				{
					$mouseover .= '<span class="fex' . (in_array('filing_lastchanged',$cellChanges)?' ch':'') . '">' . $cellInfo['filing'] . '</span>';
				}else{
					//todo: use separate url
					$linkStart = strpos($cellInfo['filing'],'http://');
					$linkEnd = strpos($cellInfo['filing'],'"',$linkStart);
					$linkLength = $linkEnd - $linkStart;
					$url = substr($cellInfo['filing'],$linkStart,$linkLength);
					//end todo
					$url = htmlspecialchars($url);
					$mouseover .= '<a href="' . $url . '" class="fex' . (in_array('filing_lastchanged',$cellChanges)?' ch':'') . '">'
							. strip_tags($cellInfo['filing']) . '</a>';
				}
			}
			if(strlen($cellInfo['phase_explain']))
			{
				if(substr_count($cellInfo['phase_explain'],'http') >= 2)
				{
					$mouseover .= '<span class="ex' . (in_array('phase_explain_lastchanged',$cellChanges)?' ch':'') . '">' . $cellInfo['phase_explain'] . '</span>';
				}else{
					//todo: use separate url
					$linkStart = strpos($cellInfo['phase_explain'],'http://');
					$linkEnd = strpos($cellInfo['phase_explain'],'"',$linkStart);
					$linkLength = $linkEnd - $linkStart;
					$url = substr($cellInfo['phase_explain'],$linkStart,$linkLength);
					//end todo
					$url = htmlspecialchars($url);
					$mouseover .= '<a href="' . $url . '" class="ex' . (in_array('phase_explain_lastchanged',$cellChanges)?' ch':'') . '">'
							. strip_tags($cellInfo['phase_explain']) . '</a>';
				}
			}
			$url = 'intermediary.php?e1=' . $row['ent_id'] . '&e2=' . $col['ent_id'] . '&list=1&itype=1,0&osflt=&hm=' . $id;
			$cellnum = $forward ? $cells[$defVM][$row['ent_id']][$col['ent_id']] : $cells[$defVM][$col['ent_id']][$row['ent_id']];
			if(strlen($mouseover))
			{
				$moClass = array();
				//apply css classes for 'edge' if the mouseover is close to the end of the heatmap
				//(unless the hm is too small for it to matter)
				if(($numcols >= 7) && ($numcols - $columnIndex < 7)) $moClass[] = 're';
				if(($numrows >= 8) && ($numrows - $rowIndex < 5)) $moClass[] = 'be';
				if(!empty($moClass))
					$moClass = ' class="' . implode(' ',$moClass) . '"';
				else
					$moClass='';
				$mouseover = '<div' . $moClass . '>' . $mouseover . '</div>';
			}
			echo('<td class="' . implode(' ', $cellClasses) . '"><a href="' . htmlspecialchars($url) . '">' . $cellnum . '</a>' . $mouseover . '</td>');
		}
		echo('</tr>');
	}
	echo('</table></div></div>');
	
	if($fullpage) echo('</body></html>');
	if(!$direct)
	{
		$out = ob_get_contents();
		ob_end_clean();
	}else{
		$out = true;
	}

	return $out;
}

function ohm_die($direct, $message)
{
	global $logger;
	$logger->error('OHM died. ' . $message);
	if($direct)
		echo($message);
	else
		ob_end_clean();
	return false;
}

?>