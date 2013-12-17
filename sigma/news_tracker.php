<?php
$cwd = getcwd();
chdir ("..");
require_once('db.php');
chdir ($cwd);

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours

if(!isset($_REQUEST['id'])) return;
$id = mysql_real_escape_string(htmlspecialchars($_REQUEST['id']));

if(!is_numeric($id)) return;

$page = 1;	
if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
{
	$page = mysql_real_escape_string($_REQUEST['page']);
}


function showNewsTracker($id, $TrackerType, $page=1)
{
	$uniqueId = uniqid();
	
	$data = DataGeneratorForNewsTracker($id, $TrackerType, $page);	
	$HTMLContent = NewsTrackerCommonCSS($uniqueId, $TrackerType);	
	$HTMLContent .= NewsTrackerHTMLContent($data); 

	return $HTMLContent;
}

function NewsTrackerHTMLContent($res) {
	$results=array();

	$htmlContent  = '<table class="news">';

	while($result = mysql_fetch_array($res))
	{
		//this assignment should not happen here, 
		//set summary in the database during news generation
		if($result['summary'] == 'NA')
			$result['summary'] = $result['name'];
		$formattedNews=formatNews($result);
		$htmlContent .= '<tr><td>' . $formattedNews .'</td></tr>';
	}
	$htmlContent .= '</table>';
	return $htmlContent;
}

function DataGeneratorForNewsTracker($id, $TrackerType, $page) {
	
	global $db;
	$query = "SELECT n.*,p.name as product ,r.*,dt.phase,dt.enrollment,dt.source FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`) JOIN products p ON(et.entity = p.id) JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) JOIN `redtags` r ON(r.id=n.redtag_id)";

	if($TrackerType == 'PNT')
		$query .= "WHERE et.`entity`='" . mysql_real_escape_string($id) . "'";
	else if($TrackerType == 'CNT' || $TrackerType == 'DNT') {
		$productIds      = ($TrackerType == 'CNT' ? GetProductsFromCompany($id, 'CPT', array()) : GetProductsFromDisease($id, 'DPT', array()));
		$impArr = implode("','", $productIds);
		$query .= "WHERE et.`entity` in('" . $impArr . "')";
	}
	
	if(!$res = mysql_query($query))
	{
		global $logger;
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		$logger->error($log);
		echo $log;
		return false;
	}
	return $res;
}

/*format based on LastZilla_Story_8*/
function formatNews($result) {
	$returnStr = '';
	$returnStr .= '<span class="rUIS">'.str_repeat('|',$result['rUIS']).'</span>'.str_repeat('|',10-$result['rUIS']).'&nbsp;&nbsp;</span><span class="product_name">'.$result['product'].'</span><br>';
	$returnStr .= '<span class="redtag">'.$result['name'].':&nbsp;&nbsp;</span><span class="phase_enroll">P'.$result['phase'].', &nbsp;N='.$result['enrollment'].',</span><span class="sponsor">&nbsp;Sponsor:&nbsp;'.$result['source'].'</span><br>';
	$returnStr .= '<span class="title">'.$result['brief_title'].'</span>&nbsp;-&nbsp;&nbsp;'.date('M d Y', strtotime('now')) .'<br>';
	$returnStr .= '<span class="summary">'.$result['summary'].'</span>';
	return $returnStr;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Larvol Sigma News</title>
<style type="text/css">
body { font-family:Verdana; font-size: 13px;}
.report_name {
	font-weight:bold;
	font-size:18px;
}

					
</style>
<?php
function NewsTrackerCommonCSS($uniqueId, $TrackerType)
{
	
	$htmlContent = '<style type="text/css">
		.news td {
			border: 2px solid red;
			padding: 5px 5px 10px 10px;			
		}		
		table.news {
			float:left;
			border-collapse: separate;
    		border-spacing: 0 0.5em;
		}
		.product_name {
			color:#151B54;
		}
		.rUIS {
			color:red;
		}
		.redtag {
			color:red;
		}
		.phase_enroll {
			color:brown;
		}		
		.sponsor {
			color:blue;
		}		
		.title {
			color:#000080;
		}		
		.summary {
			color:purple;
		}
					</style>';
	return $htmlContent;				
}
?>