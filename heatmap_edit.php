<?php
require_once('db.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
$HEADER_INCLUDES = '<link href="css/ohm/ohm.css" rel="stylesheet" type="text/css" />'
				. '<link href="css/themes/cupertino/jquery-ui-1.10.3.custom.min.css" rel="stylesheet" type="text/css" media="screen" />'
				. '<script type="text/javascript" src="css/getcssrule.js"></script>'
				. '<script type="text/javascript" src="css/ohm/ohm.js"></script>'
				. '<script type="text/javascript" src="scripts/jquery-ui-1.10.3.custom.min.js"></script>'
				. '<script type="text/javascript" src="scripts/autosuggest/jquery.autocomplete-min.js"></script>'
				. '<style type="text/css" media="all">'
				. 'th{white-space: nowrap;}'
				. 'body div.heatmap div.hmdata fieldset table tr td{text-align:left;}'
				. '.hmdata td img{cursor:pointer;}'
				. '</style>';
require_once('header.php');

/*todo:
- require javascript because the only non-js way to support only sending individual updates in the POST (as opposed to the whole HM) is to have a million separate submit buttons
- changing things will fill a change queue visible to the user where individual things can be canceled
- color code changes. use red for destructive edits
- before submitting, require confirmation if there are destructive edits
- report list search in a way that can accept GET searches or result sets from elsewhere
- ohm style sidebar:
	- Global items: list/search
	- This report: ohm, export(html, excel, pdf, word), input check, product tracker, recalculate cells
*/

$where="";
if($db->user->userlevel != 'root')
{
	$where = 'WHERE user IS NULL OR user=' . $db->user->id . ' OR shared=true ';
}
$query = 'SELECT rpt_masterhm.id AS id,rpt_masterhm.name AS name,users.username AS user,rpt_masterhm.category AS category '
			. 'FROM rpt_masterhm LEFT JOIN users ON rpt_masterhm.user=users.id ' . $where . 'ORDER BY category,name';
$res = mysql_query($query);
if($res === false)
{
	$sqlerr = 'Bad SQL query getting HM list in editor: ' . $query;
	$logger->error($sqlerr);
	die($sqlerr);
}
$heatmaps = array();
while($row = mysql_fetch_assoc($res)) $heatmaps[$row['id']] = $row;
$query = 'SELECT COUNT(*) AS cnt,report,type FROM rpt_masterhm_headers GROUP BY report,type';
$res = mysql_query($query);
if($res === false)
{
	$sqlerr = 'Bad SQL query getting HM counts in editor: ' . $query;
	$logger->error($sqlerr);
	die($sqlerr);
}
while($row = mysql_fetch_assoc($res))
{
	if(isset($heatmaps[$row['report']]))
		$heatmaps[$row['report']][$row['type'].'s'] = $row['cnt'];
}

$id = NULL;
if(isset($_GET['id']))
{
	$id = (int)$_GET['id'];
	if(!is_numeric($id))
	{
		die('Invalid HM id.');
	}
}


echo('
<div class="heatmap">
<div class="hmcontrol">

<div class="hmcbutton info" title="Help">
  <div class="hmcmenu" style="width:270px;height:130px;">
    <div class="hmchead">Help</div>
    <table class="hmchelp"><tr><td><img src="images/sbomb.png" alt="bomb"/></td><td colspan="6">Discontinued</td></tr><tr><td><img src="images/expanded_crop.gif" alt="filing"/></td><td colspan="6">Filing details</td></tr><tr><td class="ch" style="background-color:#EEE;">&nbsp;</td><td colspan="6">Red border: Record updated</td></tr>
    <tr><td>Phase</td><td class="pn">N/A</td><td class="p0">0</td><td class="p1">1</td><td class="p2">2</td><td class="p3">3</td><td class="p4">4</td></tr></table>
  </div>
</div>
<div class="hmcbutton view" title="View Report">
  <div class="hmcmenu" id="viewmenu" style="width:400px;height:400px;">
    <div class="hmchead">Report</div>
    <div style="overflow-y:scroll;height:98%;"><table><tr><th>Name</th><th>Owner</th><th>Rows</th><th>Columns</th></tr>');

$lastcat=-1;
$totalcats=0;
foreach($heatmaps as $hm)
{
	if($lastcat != $hm['category'])
	{
		++$totalcats;
		$rptcatclass = "rptcat" . $totalcats . 'i';
		$rptcatclassq = "'.rptcat" . $totalcats . "'";
		$rptcatclassq2 = "document.getElementById('rptcat" . $totalcats . "i')";
		$tog = $rptcatclassq2 . ".src.indexOf('images/up.png')!=-1?'images/down.png':'images/up.png'";
		echo('<tr><th colspan="4" class="rptcat" onclick="$(' . $rptcatclassq . ').toggle();' . $rptcatclassq2 . '.src=(' . $tog . ');">'
			. '<img src="images/down.png" id="' . $rptcatclass . '"/>'
			. (empty($hm['category']) ? 'Uncategorized' : $hm['category']) . '</th></tr>');
	}
	echo('<tr class="rptcat' . $totalcats . '"><td><a href="heatmap_edit.php?id=' . $hm['id'] . '">'
		  . (empty($hm['name']) ? ('(report '.$hm['id'].')') : $hm['name'])
		  . '</a></td><td>' . $hm['user'] . '</td><td>' . $hm['rows'] . '</td><td>' . $hm['columns'] . '</td></tr>');
	$lastcat = $hm['category'];
}

echo('</table>
    </div>
  </div>
</div>');

if($id !== NULL) echo('
<div class="hmcbutton export" title="Export">
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
<a href="online_heatmap.php?id=' . $id . '"><div class="hmcbutton ohmlink" title="OHM">&nbsp;</div></a>
<a href="masterhm_report_inputcheck.php?id=' . $id . '"><div class="hmcbutton icheck" title="Input Check">&nbsp;</div></a>
<a href="product_tracker.php?id=' . $id . '"><div class="hmcbutton ptracker" title="Product Tracker">&nbsp;</div></a>');

echo('</div><div class="hmdata">');
if($id !== NULL)
{
	$query = 'SELECT user,shared FROM rpt_masterhm WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query);
	if($res === false)
	{
		echo('Bad SQL query getting report status');
	}else{
		$row = mysql_fetch_assoc($res);
		if($row == false)
		{
			echo('Specified HM not found.');				
		}else{
			if($db->user->userlevel == 'root' || $row['user'] == NULL || $row['user'] == $db->user)
			{
				show_editor($id, true);
			}else{
				if($row['shared'] || $db->user->userlevel == 'admin')
				{
					show_editor($id, false);
				}else{
					echo('Private HM.');
				}
			}
		}
	}
}else{
	echo('Select a report using the search icon on the left.');
}
?>
</div></div>

</body></html>

<?php
function show_editor($id, $editable=true)
{
	global $db;
	$query = 'SELECT * FROM rpt_masterhm WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting report info:' . mysql_error() . "<br />" . $query);
	$row = mysql_fetch_assoc($res) or die('Report info not found');
	$sel = ' selected="selected"';
	$chk = ' checked="checked"';
	echo('<fieldset style="float:left;"><legend>Report Settings</legend>');
	echo('<label>Name: <input type="text" name="hmname" id="hmname" value="' . $row['name'] . '"/></label> 
<label>Display Name:<input type="text" name="hmdisplayname" id="hmdisplayname" ' . $row['display_name'] . '/></label>
<label>Category:<input type="text" name="hmcat" id="hmcat" ' . $row['category'] . '/></label>');
	echo('<br /><label>Ownership: <select name="hmowner" id="hmowner">
    <option value="g"' . ($row['user']===NULL ? $sel : '') . '>Global</option>
    <option value="ms"' . ($row['user']==$db->user->id && $row['shared'] ? $sel : '') . '>Mine (shared)</option>
    <option value="mp"' . ($row['user']==$db->user->id && !$row['shared'] ? $sel : '') . '>Mine (Private)</option>');
	if($row['user'] !== NULL && $row['user'] != $db->user->id)
	{
		$query = 'SELECT username FROM users WHERE id=' . $row['user'] . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting owner name');
		$res = mysql_fetch_assoc($res);
		if($res === false)
			$res = $row['user'];
		else
			$res = $res['username'];
		echo('<option value="o"' . $sel . '>' . $res . ($row['shared'] ? ' (Shared)' : ' (Private)') . '</option>');
	}
	echo('</select></label> ');
	echo('<label><input type="checkbox" name="hmdtt" id="hmdtt"' . ($row['dtt']?$chk:'') . '/>Last column is DTT</label> ');
	echo('<label><input type="checkbox" name="hmtotal" id="hmtotal"' . ($row['total']?$chk:'') . '/>Add auto-total column</label>');
	echo('</fieldset><fieldset style="float:left;"><legend>Actions</legend>');
	echo('<form id="hmactions" name="hmactions" method="post" action="heatmap_edit.php" style="margin:3px;">');
	echo('<input type="submit" name="hmclone" id="hmclone" value="Clone" /><br />');
	echo('<input type="submit" name="hmcalc" id="hmcalc" value="Recalculate cells" /></form></fieldset>');
	echo('<fieldset style="float:left;"><legend>Pending Changes</legend>');
	echo('<form name="hmedit" method="post" action="heatmap_edit.php"><input type="hidden" name="edits" id="edits"/><ol id="editlist">');
	//<li> tags with a non-editable form control for each edit will be added here by javascript
	echo('</ol><input type="submit" name="hmsubmit" id="hmsubmit" value="Submit" /></form></fieldset><br clear="all"/>');
	echo('<fieldset><legend>Heatmap ' . $id . '</legend>');
	
	//get cell data all at once, it's the only way to avoid a lot of redundant work by the database
	$fields = 'entity1,entity2,bomb,bomb_explain,phase4_override,phase_explain,filing,preclinical';
	$query = 'SELECT ' . $fields . ',rh1.type AS e1type FROM rpt_masterhm_cells '
		. 'LEFT JOIN rpt_masterhm_headers AS rh1 ON entity1=rh1.type_id '
		. 'LEFT JOIN rpt_masterhm_headers AS rh2 ON entity2=rh2.type_id '
		. 'WHERE rh1.report=' . $id . ' AND rh2.report=' . $id
		. ' AND ((rh1.type="row" AND rh2.type="column") OR (rh2.type="row" AND rh1.type="column"))';
	$res = mysql_query($query);
	$cells = array();
	while($row = mysql_fetch_assoc($res))
	{
		$e1type = $row['e1type'];
		$e1 = $row['entity1'];
		$e2 = $row['entity2'];
		unset($row['e1type']);
		
		if($e1type == 'row')
		{
			if(!isset($cells[$e1])) $cells[$e1] = array();
			$cells[$e1][$e2] = $row;
		}else{
			if(!isset($cells[$e2])) $cells[$e2] = array();
			$cells[$e2][$e1] = $row;
		}
	}
	
	//show table
	echo('<table><tr><th>&nbsp;</th>');
	//get columns
	$headerfields =  'rpt_masterhm_headers.type_id      AS type_id,'
					.'rpt_masterhm_headers.id           AS id,'
					.'rpt_masterhm_headers.num          AS num,'
					.'rpt_masterhm_headers.display_name AS display_name,'
					.'rpt_masterhm_headers.category     AS category,'
					.'rpt_masterhm_headers.tag          AS tag,'
					.'entities.name                     AS name';
	$table = 'rpt_masterhm_headers LEFT JOIN entities ON rpt_masterhm_headers.type_id=entities.id';
	$query = 'SELECT ' . $headerfields . ' FROM ' . $table
			. ' WHERE rpt_masterhm_headers.report=' . $id . ' AND rpt_masterhm_headers.type="column" GROUP BY rpt_masterhm_headers.num ASC';
	$res = mysql_query($query) or die('Bad SQL query getting heatmap columns: '.$query);
	$columnorder = array();
	$json_headers = array();
	while($row = mysql_fetch_assoc($res))
	{
		echo('<th>Column ' . $row['num'] . ' - (#' . $row['id'] . ')');
		echo('<br /><img title="Entity" src="images/file_square.png"/>');
		$hid = '_col_' . $row['num'] . '_' . $row['id'];
		echo('<input type="text" id="e' . $hid . '" value="' . $row[/*'type_id'*/'name'] . '"' . ' class="entity" />');
			//. ' onkeyup="javascript:autoComplete(\'e' . $hid . '\')" />');
		echo('<br /><img title="Display Name" src="images/display.gif"/>');
		echo('<input type="text" id="d' . $hid . '" value="' . $row['display_name'] . '"/>');
		echo('<br /><img title="Category" src="images/expanded.png"/>');
		echo('<input type="text" id="c' . $hid . '" value="' . $row['category'] . '"/>');
		echo('<br /><img title="Tag" src="images/tag.gif"/>');
		echo('<input type="text" id="t' . $hid . '" value="' . $row['tag'] . '"/><br />');
		echo('<img title="Drag to reorder" draggable="true" src="images/drag_horizontal.png" style="margin-left:30px;"/> - ');
		echo('<img title="Delete" src="images/delicon.gif" style="margin-left:50px;"/>');
		echo('</th>');
		$columnorder[] = $row['type_id'];
		$json_headers[$row['id']] = array('e' => $row['name'], 'd' => $row['display_name'], 'c' => $row['category'], 't' => $row['tag']);
	}
	echo('</tr>');
	//get rows
	$query = 'SELECT ' . $headerfields . ' FROM ' . $table
			. ' WHERE rpt_masterhm_headers.report=' . $id . ' AND rpt_masterhm_headers.type="row" GROUP BY rpt_masterhm_headers.num ASC';
	$res = mysql_query($query) or die('Bad SQL query getting heatmap rows');
	$json = array();
	while($row = mysql_fetch_assoc($res))
	{
		//show row header
		$hid = '_row_' . $row['num'] . '_' . $row['id'];
		echo('<tr><th>Row ' . $row['num'] . ' - (#' . $row['id'] . ')');
		echo('<br /><img title="Entity" src="images/file_square.png"/>');
		echo('<input type="text" id="e' . $hid . '" value="' . $row[/*'type_id'*/'name'] . '"' . ' class="entity" />');
			//. ' onkeyup="javascript:autoComplete(\'e' . $hid . '\')" />');
		echo('<br /><img title="Display Name" src="images/display.gif"/>');
		echo('<input type="text" id="d' . $hid . '" value="' . $row['display_name'] . '"/>');
		echo('<br /><img title="Category" src="images/expanded.png"/>');
		echo('<input type="text" id="c' . $hid . '" value="' . $row['category'] . '"/>');
		echo('<br /><img title="Tag" src="images/tag.gif"/>');
		echo('<input type="text" id="t' . $hid . '" value="' . $row['tag'] . '"/>');
		echo('<br /><img title="Drag to reorder" draggable="true" src="images/drag_horizontal.png" style="margin-left:30px;"/> - ');
		echo('<img title="Delete" src="images/delicon.gif" style="margin-left:50px;"/>');
		echo('</th>');
		$json[$row['type_id']] = array();
		$json_headers[$row['id']] = array('e' => $row['name'], 'd' => $row['display_name'], 'c' => $row['category'], 't' => $row['tag']);
		//show cells
		foreach($columnorder as $col)
		{
			$cell = $cells[$row['type_id']][$col];
			$name = 'flags_' . $row['type_id'] . '_' . $col;
			echo('<td id="' . $row['type_id'].'`'.$col . '">');
			$json[$row['type_id']][$col] = array();
			if($cell['bomb'] == 'large')
			{
				echo(' <img src="images/lbomb.png" alt="bomb" id="' . $cell['entity1'].'`'.$cell['entity2'] . '`bomb"/>');
				$json[$row['type_id']][$col]['bomb'] = 'large';
				$json[$row['type_id']][$col]['bomb_explain'] = $cell['bomb_explain'];
			}
			if($cell['bomb'] == 'small')
			{
				echo(' <img src="images/sbomb.png" alt="bomb" id="' . $cell['entity1'].'`'.$cell['entity2'] . '`bomb"/>');
				$json[$row['type_id']][$col]['bomb'] = 'small';
				$json[$row['type_id']][$col]['bomb_explain'] = $cell['bomb_explain'];
			}
			if($cell['phase_explain'] !== NULL)
			{
				echo(' <img src="images/phaseexp_small.png" alt="info" id="' . $cell['entity1'].'`'.$cell['entity2'] . '`info"/>');
				$json[$row['type_id']][$col]['info'] = $cell['phase_explain'];
			}
			if($cell['filing'] !== NULL)
			{
				echo(' <img src="images/filing.png" alt="filing" id="' . $cell['entity1'].'`'.$cell['entity2'] . '`filing"/>');
				$json[$row['type_id']][$col]['filing'] = $cell['filing'];
			}
			if($cell['phase4_override'])
			{
				echo(' <img src="images/phase4.png" alt="phase4" id="' . $cell['entity1'].'`'.$cell['entity2'] . '`phase4"/>');
				$json[$row['type_id']][$col]['phase4'] = true;
			}
			if($cell['preclinical'])
			{
				echo(' <img src="images/preclinical.png" alt="preclinical" id="' . $cell['entity1'].'`'.$cell['entity2'] . '`preclinical"/>');
				$json[$row['type_id']][$col]['preclinical'] = true;
			}
			
			echo('</td>');
		}
		
		echo('</tr>');
	}
	?>
	</table></fieldset>
<div id="cell-form" title="Edit items in cell" >
Row: <span id="cellrow"></span><br />Column: <span id="cellcol"></span><br />
<form>
<input type="checkbox" name="bomb" id="bomb" class="ui-widget-content ui-corner-all" /><img src="images/lbomb.png" alt="bomb"/> <select name="bomb-size" id="bomb-size"><option value="large" selected="selected">large</option><option value="small">small</option></select><br />
<input type="checkbox" name="filing" id="filing" class="ui-widget-content ui-corner-all" /><img src="images/filing.png" alt="filing"/><br />
<input type="checkbox" name="info" id="info" class="ui-widget-content ui-corner-all" /><img src="images/phaseexp_small.png" alt="info"/><br />
<input type="checkbox" name="phase4" id="phase4" class="ui-widget-content ui-corner-all" /><img src="images/phase4.png" alt="phase4"/><br />
<input type="checkbox" name="preclinical" id="preclinical" class="ui-widget-content ui-corner-all" /><img src="images/preclinical.png" alt="preclinical"/>
</form>
</div>
<div id="icon-form" title="Edit text of item" >
Row: <span id="iconrow"></span><br />Column: <span id="iconcol"></span><br />Type: <span id="icontype"></span><br />
<form>
<textarea name="icontext" id="icontext" cols="100" rows="10"></textarea>
</form>
</div>
	<script type="text/javascript">
	//<![CDATA[
	
	//setup data
	var hm = <?php echo(json_encode($json));?>;
	var headers = <?php echo(json_encode($json_headers));?>;
	var bomb = $( "#bomb" ),
	filing = $( "#filing" ),
	info = $( "#info" ),
	phase4 = $( "#phase4" ),
	preclinical = $( "#preclinical" ),
	cellFields = $( [] ).add( bomb ).add( filing ).add( info ).add( phase4 ).add( preclinical );
	
	var icontext = $("#icontext");
	var icomFields = $( [] ).add(icontext);
	
	//Click to edit an icon
	 $( "#icon-form" ).dialog({
		autoOpen: false,
		height: "auto",
		width: "auto",
		modal: true,
		buttons: {
			"Save": function() {
				cellFields.removeClass( "ui-state-error" );
				var rowEntity = document.getElementById("iconrow").innerHTML;
				var colEntity = document.getElementById("iconcol").innerHTML;
				var type = document.getElementById("icontype").innerHTML;
				var idstr = rowEntity+"`"+colEntity+"`"+type;
				var user = $("#icontext").val();

				if(user != hm[rowEntity][colEntity][type])
				{
					hm[rowEntity][colEntity][type] = user;
					addEdit(idstr+"`"+user);
				}
				
				
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			cellFields.val( "" ).removeClass( "ui-state-error" );
		}
	});
	function iconEdit(event)
	{
		var id = event.target.id;
		var source = id.split("`");
		var rowEntity = source[0];
		var colEntity = source[1];
		var type = source[2];
		if(type != "bomb" && type != "filing" && type != "info") return false;
		if(type == "bomb") type = "bomb_explain";
		var defaultVal = hm[rowEntity][colEntity][type];
		document.getElementById("iconrow").innerHTML = rowEntity;
		document.getElementById("iconcol").innerHTML = colEntity;
		document.getElementById("icontype").innerHTML = type;
		$("#icontext").val(defaultVal);
		$( "#icon-form" ).dialog( "open" );
		
		return false;
	}
	$("td img").click(iconEdit);
	
	//click the cell to change what icons are in it
	 $( "#cell-form" ).dialog({
		autoOpen: false,
		height: "auto",
		width: "auto",
		modal: true,
		buttons: {
			"Save": function() {
				cellFields.removeClass( "ui-state-error" );
				//determine which cell to change
				var rowEntity = document.getElementById("cellrow").innerHTML;
				var colEntity = document.getElementById("cellcol").innerHTML;
				//set state of icons in memory
				//set state of images (add edit listener if needed)
				//add changes to backend list
				//add changes to visible list
				var idstr = rowEntity+"`"+colEntity;
				var cell = document.getElementById(idstr);
				if($("#bomb").prop("checked") && !("bomb" in hm[rowEntity][colEntity]))
				{
					//add bomb
					hm[rowEntity][colEntity]["bomb"] = $("#bomb-size").val();
					hm[rowEntity][colEntity]["bomb_explain"] = "";
					cell.innerHTML += '<img alt="bomb" id="'+idstr+'`bomb" src="images/' + $("#bomb-size").val().charAt(0) + 'bomb.png"/>';
					var imgNode = document.getElementById(idstr+"`bomb");
					$(imgNode).click(iconEdit);
					addEdit(idstr+"`bomb`##+");
				}else if(!$("#bomb").prop("checked") && ("bomb" in hm[rowEntity][colEntity])){
					//remove bomb
					delete hm[rowEntity][colEntity]["bomb"];
					var bombImg = document.getElementById(idstr+"`bomb");
					bombImg.parentNode.removeChild(bombImg);
					addEdit(idstr+"`bomb`##-");
				}else if($("#bomb").prop("checked") && $("#bomb-size").val() != hm[rowEntity][colEntity]["bomb"]){
					//change size of existing bomb
					hm[rowEntity][colEntity]["bomb"] = $("#bomb-size").val();
					var bombImg = document.getElementById(idstr+"`bomb");
					bombImg.parentNode.removeChild(bombImg);
					var bombSizeLetter = $("#bomb-size").val().charAt(0);
					cell.innerHTML += '<img alt="bomb" id="'+idstr+'`bomb" src="images/' + bombSizeLetter + 'bomb.png"/>';
					bombImg = document.getElementById(idstr+"`bomb");
					$(bombImg).click(iconEdit);
					addEdit(idstr+"`bomb`##"+bombSizeLetter);
				}
				
				if($("#filing").prop("checked") && !("filing" in hm[rowEntity][colEntity]))
				{
					hm[rowEntity][colEntity]["filing"] = "";
					cell.innerHTML += '<img alt="filing" id="'+idstr+'`filing" src="images/filing.png"/>';
					var imgNode = document.getElementById(idstr+"`filing");
					$(imgNode).click(iconEdit);
					addEdit(idstr+"`filing`##+");
				}else if(!$("#filing").prop("checked") && ("filing" in hm[rowEntity][colEntity])){
					delete hm[rowEntity][colEntity]["filing"];
					var imgNode = document.getElementById(idstr+"`filing");
					imgNode.parentNode.removeChild(imgNode);
					addEdit(idstr+"`filing`##-");
				}
				
				if($("#info").prop("checked") && !("info" in hm[rowEntity][colEntity]))
				{
					hm[rowEntity][colEntity]["info"] = "";
					cell.innerHTML += '<img alt="info" id="'+idstr+'`info" src="images/phaseexp_small.png"/>';
					var imgNode = document.getElementById(idstr+"`info");
					$(imgNode).click(iconEdit);
					addEdit(idstr+"`info`##+");
				}else if(!$("#info").prop("checked") && ("info" in hm[rowEntity][colEntity])){
					delete hm[rowEntity][colEntity]["info"];
					var imgNode = document.getElementById(idstr+"`info");
					imgNode.parentNode.removeChild(imgNode);
					addEdit(idstr+"`info`##-");
				}
				
				if($("#phase4").prop("checked") && !("phase4" in hm[rowEntity][colEntity]))
				{
					hm[rowEntity][colEntity]["phase4"] = true;
					cell.innerHTML += '<img alt="phase4" id="'+idstr+'`phase4" src="images/phase4.png"/>';
					addEdit(idstr+"`phase4`##+");
				}else if(!$("#phase4").prop("checked") && ("phase4" in hm[rowEntity][colEntity])){
					delete hm[rowEntity][colEntity]["phase4"];
					var imgNode = document.getElementById(idstr+"`phase4");
					imgNode.parentNode.removeChild(imgNode);
					addEdit(idstr+"`phase4`##-");
				}
				
				if($("#preclinical").prop("checked") && !("preclinical" in hm[rowEntity][colEntity]))
				{
					hm[rowEntity][colEntity]["preclinical"] = true;
					cell.innerHTML += '<img alt="preclinical" id="'+idstr+'`preclinical" src="images/preclinical.png"/>';
					addEdit(idstr+"`preclinical`##+");
				}else if(!$("#preclinical").prop("checked") && ("preclinical" in hm[rowEntity][colEntity])){
					delete hm[rowEntity][colEntity]["preclinical"];
					var imgNode = document.getElementById(idstr+"`preclinical");
					imgNode.parentNode.removeChild(imgNode);
					addEdit(idstr+"`preclinical`##-");
				}
				
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			cellFields.val( "" ).removeClass( "ui-state-error" );
		}
	});
	
	function cellEdit(event)
	{
		var id = event.target.id;
		var source = id.split("`");
		var rowEntity = source[0];
		var colEntity = source[1];
		if("bomb" in hm[rowEntity][colEntity])
		{
			$("#bomb").prop("checked", true);
			$("#bomb-size").val(hm[rowEntity][colEntity]["bomb"]);
		}else{
			$("#bomb").prop("checked", false);
		}
		if("filing" in hm[rowEntity][colEntity])
		{
			$("#filing").prop("checked", true);
		}else{
			$("#filing").prop("checked", false);
		}
		if("info" in hm[rowEntity][colEntity])
		{
			$("#info").prop("checked", true);
		}else{
			$("#info").prop("checked", false);
		}
		if("phase4" in hm[rowEntity][colEntity])
		{
			$("#phase4").prop("checked", true);
		}else{
			$("#phase4").prop("checked", false);
		}
		if("preclinical" in hm[rowEntity][colEntity])
		{
			$("#preclinical").prop("checked", true);
		}else{
			$("#preclinical").prop("checked", false);
		}
		document.getElementById("cellrow").innerHTML = rowEntity;
		document.getElementById("cellcol").innerHTML = colEntity;
		$( "#cell-form" ).dialog( "open" );
	}
	$("td").click(cellEdit);
	
	//log changes in the queue
	var edits = new Array();
	function addEdit(edit)
	{
		var list = document.getElementById("editlist");
		if(edit.charAt(0) == "#") //header change
		{
			var source = edit.split('`');
			var input = source[0].substring(1).split('_');
			var fieldName = input[0];
			var rowCol = input[1];
			var index = input[2];
			var id = input[3];
			var value = source[1];
			
			switch(fieldName)
			{
				case 'e': fieldName = 'entity';			break;
				case 'd': fieldName = 'display name';	break;
				case 'c': fieldName = 'category';		break;
				case 't': fieldName = 'tag';			break;
			}
			if(rowCol == 'col') rowCol = "column";
			list.innerHTML += "<li>Changed " + fieldName + " of " + rowCol + " " + index + " (#" + id + ") to: " + value + "</li>";
		}else{ //cell change
			var source = edit.split("`");
			var rowEntity = source[0];
			var colEntity = source[1];
			var type = source[2];
			var value = source[3];
			if(value.substring(0,2) == "##") //changed which icons are in the cell
			{
				var command = value.charAt(2);
				switch(command)
				{
					case '+':
					list.innerHTML += "<li>Added " + type + " in cell at " + rowEntity + "," + colEntity + "</li>";
					break;
					
					case '-':
					list.innerHTML += "<li>Removed " + type + " from cell at " + rowEntity + "," + colEntity + "</li>";
					break;
					
					case 'l':
					list.innerHTML += "<li>Changed small bomb to large bomb in cell at " + rowEntity + "," + colEntity + "</li>";
					break;
					
					case 's':
					list.innerHTML += "<li>Changed large bomb to small bomb in cell at " + rowEntity + "," + colEntity + "</li>";
					break;
					
					default:
					list.innerHTML += "<li>Unrecognized command.</li>";
				}
			}else{ //changed the text of a single icon in the cell
				var value = $("<div/>").text(value).html();
				list.innerHTML += "<li>Changed " + type + " of cell at " + rowEntity + "," + colEntity + " to: " + value + "</li>";
			}
		}
		edits.push(edit);
		document.getElementById("edits").value = JSON.stringify(edits);
	}
	
	//entity name autocomplete
	function autoComplete(fieldID)
	{	
		$(function()
		{
			if($('#'+fieldID).length > 0)
			{	
				var a = $('#'+fieldID).autocomplete({
						serviceUrl:'autosuggest.php',
						params:{table:'masterhm', field:'name'},
						minChars:3,
						width:450
				});
			}
		});
	}
	$(".entity").keyup(	function(event){ autoComplete($(this).attr("id")); });
	$("th input").focusout(
		function(event)
		{
			var input = $(this).attr("id");
			var source = input.split('_');
			var fieldName = source[0];
			var id = source[3];
			var value = $('#'+input).val();
			if(value != headers[id][fieldName])
			{
				headers[id][fieldName] = value;
				var edit = '#' + input + '`' + value;
				addEdit(edit);
			}
		}
	);
	
	//]]>
	</script>
<?php
}
?>