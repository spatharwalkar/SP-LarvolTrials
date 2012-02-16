<?php 
ob_start();
require_once('db.php');
if(!$db->loggedIn() || !isset($_GET['id']))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('include.search.php');

$id = mysql_real_escape_string($_GET['id']);

$doc = file_get_contents('templates/general.htm');

global $db;

$query = 'SELECT name,user,footnotes,description,category FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
$res = mysql_query($query) or die('Bad SQL query getting master heatmap report');
$res = mysql_fetch_array($res) or die('Report not found.');
$rptu = $res['user'];
if($rptu !== NULL && $rptu != $db->user->id) return;	//prevent anyone from viewing others' private reports
$name = $res['name'];

$query = 'SELECT `num`,`type`,`type_id` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' ORDER BY num ASC';
$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers');
$line='<div align="center"><b>Master Heatmap Input Check for '.$name.'</b></div><br/><br/>';
while($row = mysql_fetch_array($res))
{
	$out = '';
	if($row['type'] == 'product')
	{
		$data_query = 'SELECT `searchdata`, `name` FROM `products` WHERE `id` =' . $row['type_id'];
		$data_res = mysql_query($data_query) or die('Bad SQL query getting master heatmap report');
		$data_res = mysql_fetch_array($data_res) or die('Report not found.');
		$type='Row'.$data_res['num'];
	}
	else
	{	
		$data_query = 'SELECT `searchdata`, `name` FROM `areas` WHERE `id` = ' . $row['type_id'];
		$data_res = mysql_query($data_query) or die('Bad SQL query getting master heatmap report');
		$data_res = mysql_fetch_array($data_res) or die('Report not found.');
		$type='Column'.$data_res['num'];
	}
	
	$json = $data_res['searchdata'];
	
	$searchdata=array();
	$searchdatas=json_decode($json, true);
	
	if($data_res['searchdata'] != NULL && $data_res['searchdata'] != '')
	{
		/*Check data
		$json = '{"reportid":"9000","override":"1, 2, 10, 15","columndata":["1dasd"," 2dasdsa"," 3"],"sortdata":["1dasd"," 2dasdsa"," 3"],"groupdata":["1dasd"," 2dasdsa"," 3"],"wheredata":[{"columnname":"larvol_id","opname":"NotInBetween","chainname":"AND","columnvalue":"1and;endl20"},{"columnname":"institution_type","opname":"EqualTo","chainname":"AND","columnvalue":"industry_collaborator"}]}';
	
		$ch=0;
		if(is_array($searchdatas['columndata']) && !empty($searchdatas['columndata']))
		{
			$out.= '<tt style="font-weight:bold; color:#000000;">fields</tt> ';
			foreach($searchdatas['columndata'] as $columndata)
			{
				$out.= '<tt style="font-weight:bold; color:#FF0000;">'.$columndata["columnname"].'</tt>';
				$ch++;
				$out.=', ';
			}
		}	*/
		$conn='';
		foreach($searchdatas['wheredata'] as $searchdata)
		{
			$out.='<tt style="font-weight:bold; color:#000000;"><u>'.$conn.'</u></tt> ';
			$out.=CreateLine($searchdata['columnname'], $searchdata['opname'], $searchdata['columnvalue']);
			$conn=$searchdata['chainname'];
		}
		/// Commented some condition so if we require them to add in future just uncomment it
		/*$ch=0;
		if(is_array($searchdatas['sortdata']) && !empty($searchdatas['sortdata']))
		{
			$out.= '<tt style="font-weight:bold; color:#000000;">All Sorted using fields</tt> ';
			foreach($searchdatas['sortdata'] as $sortdata)
			{
				$out.= '<tt style="color:#FF0000;">'.$sortdata["columnname"].' '.$sort_column["columnas"].'</tt>';
				$ch++;
				if($ch < count($searchdatas['sortdata']))
					$out.=', ';
				else 
					$out.=' ';
			}
		}	
		
		$ch=0;
		if(is_array($searchdatas['groupdata']) && !empty($searchdatas['groupdata']))
		{
			$out.= '<tt style="font-weight:bold; color:#000000;">All Grouped using fields</tt> ';
			foreach($searchdatas['groupdata'] as $groupdata)
			{
				$out.= '<tt style="color:#FF0000;">'.$groupdata.'</tt>';
				$ch++;
				if($ch < count($searchdatas['groupdata']))
					$out.=', ';
				else 
					$out.=' ';
			}
		}*/
		
		$ch=0;
		if(!empty($searchdatas['override']))
		{
			$out.= '<tt style="font-weight:bold; color:#000000;">With Overriding search by following NCTid\'s</tt> ';
			
			$out.= '<tt style="color:#FF0000;">'.$searchdatas['override'].'</tt>';
		}	

	$out= '<br/><b>' . $type . ' ' . $row['num'] . ': ' . $data_res['name'] . '</b><br><tt style="font-weight:bold; color:#000000;">Search For </tt>' . $out.'<br>' ;
	$line=$line.$out;
	}
}

function CreateLine($columnname, $opname, $columnvalue)
{
	$line = '<tt style="font-weight:bold; color:#FF0000">'.$columnname.'</tt> ';
	
	switch($opname)
	{
		case 'EqualTo':
			$op = "= ";
			break;
			case 'NotEqualTo':
			$op= "Not Equal To ";
			break;
		case 'StartsWith':
			$op ="Starts With ";
			break;
		case 'NotStartsWith':
				$op ="Not Starts With ";
			break;
		case 'Contains':
			$op ="Contains ";
			break;
		case 'NotContains':
			$op ="Not Contains";
			break;
		case 'BiggerThan':
			$op ="> ";
			break;
		case 'BiggerOrEqualTo':
				$op =">= ";
			break;
		case 'SmallerThan':
			$op ="< ";
			break;
		case 'SmallerOrEqualTo':
			$op ="<= ";
			break;
		case 'InBetween':
				$op ="BETWEEN ";
			break;
		case 'NotInBetween':
			$op ="Not In Between ";
			break;
		case 'IsIn':
				$op ="Is In";
			break;
		case 'IsNotIn':
			$op ="Is Not In";
			break;
			case 'IsNull':
			$op ="IS NULL ";
			break;
		case 'NotNull':
			$op ="IS NOT NULL";
			break;
		case 'Regex':
			$op = "Having Regex as";
				break;
		case 'NotRegex':
			$op = "Having Not Regex as";
			break;
		default:
			$opname1=preg_replace('/([A-Z]{1}[a-z]+)/','$0  ',$opname); //separating properly name by capital letter detection
			$op = $opname1." ";
			break;	

	}
		
		$line.= '<tt style="font-weight:bold; color:#666666">'. $op .'</tt> ';
		
		if(strpos($columnvalue,'and;endl'))
		{
			$val = explode('and;endl', $columnvalue);
			$line.= '<tt style="color:blue;">'.$val[0].' And '.$val[1].'</tt> ';
		}
		else
		{
			$line.='<tt style="color:blue;">'.$columnvalue.'</tt> ';
		}
	
	return $line;
}		

//print $line;

$doc = explode('#content#',$doc);
$doc = implode($line, $doc);

global $logger;
$log = null;
$log = ob_get_contents();
$log = str_replace("\n", '', $log);
if($log)
$logger->error($log);
ob_end_clean();

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
?>