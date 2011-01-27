<?php
require_once('db.php');
if(!$db->loggedIn() || !isset($_GET['id']))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('include.search.php');

$id = mysql_real_escape_string($_GET['id']);
if(!is_numeric($id)) die('non-numeric id!');
$type = 'heatmap';
if($_GET['type'] == 'competitor') $type = 'competitor';

$doc = file_get_contents('templates/general.htm');

$query = 'SELECT name FROM rpt_' . ($type == 'heatmap' ? 'heatmap' : 'competitor') . ' WHERE id=' . $id;
$res = mysql_query($query) or die('Bad SQL query getting report name');
$name = mysql_fetch_assoc($res) or die('Report not found.');
$name = $name['name'];

$out = '';
$query = 'SELECT header,num,type,searchdata FROM rpt_' . ($type == 'heatmap' ? 'heatmap' : 'competitor') . '_headers WHERE report=' . $id;
$res = mysql_query($query) or die('Bad SQL Query getting data');
while($row = mysql_fetch_assoc($res))
{
	$params = unserialize(base64_decode($row['searchdata']));
	if(!is_array($params)) continue;
	$params = prepareParams($params);
	$paramstr = '';
	foreach($params as $p)
	{
		if($p->field[0] == '_')
		{
			$query = 'SELECT '
				. 'data_fields.name AS "name",data_categories.name AS "cat", data_fields.`type` AS "type"'
				. ' FROM (data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id)'
				. ' WHERE data_fields.id=' . ((int)substr($p->field,1)) . ' LIMIT 1';
			$res2 = mysql_query($query) or die('Bad SQL query getting field/cat name'.$query.mysql_error());
			$res2 = mysql_fetch_assoc($res2);
			if($res2 !== false)
			{
				$p->field = $res2['cat'] . '/' . $res2['name'];
				$p->type = $res2['type'];
			}
		}
		$paramstr .= $p->action . ' ' . $p->field;
		if($p->action == 'search')
		{
			$paramstr .= ' for';
			
			if($p->type == 'enum')
			{
				if(is_array($p->value))
				{
					foreach($p->value as $key => $value) $p->value[$key] = enumIdToName($value);
				}else{
					$p->value = enumIdToName($p->value);
				}
			}
			
			if(strlen($p->value))
			{
				if(is_array($p->value)) $p->value = implode(' OR ', $p->value);
				$paramstr .= ' <tt style="color:blue;">' . $p->value . '</tt>';
			}
			if(strlen($p->value) && strlen($p->negate)) $paramstr .= ' and';
			if(strlen($p->negate)) $paramstr .= ' not <tt style="color:blue;">' . $p->negate . '</tt>';
		}
		$paramstr .= '<br>';
	}
	$out .= '<b>' . $row['type'] . ' ' . $row['num'] . ': ' . $row['header'] . '</b><br>' . $paramstr . '<br>';
}
$doc = explode('#content#',$doc);
$doc = implode($out, $doc);

//Send headers for file download
header("Pragma: public");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header("Content-Type: application/download");
header("Content-Type: application/msword");
header("Content-Disposition: attachment;filename=inputcheck-" . substr($name,0,20) . ".doc");
header("Content-Transfer-Encoding: binary ");
echo($doc);
@flush();

function enumIdToName($id)
{
	$query = 'SELECT `value` FROM data_enumvals WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query);
	if($res === false) die("Bad SQL query getting enumval name ".$query.mysql_error());
	$res = mysql_fetch_array($res);
	if($res === false) die("Couldn't find enumval");
	return $res['value'];
}

?>