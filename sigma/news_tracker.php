<?php
$cwd = getcwd();
chdir ("..");
require_once('db.php');
chdir ($cwd);

ini_set('memory_limit','-1');
ini_set('max_execution_time','60');	//1min

if(!isset($_REQUEST['id'])) return;
$id = mysql_real_escape_string(htmlspecialchars($_REQUEST['id']));

if(!is_numeric($id)) return;

$page = 1;	
if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
{
	$page = mysql_real_escape_string($_REQUEST['page']);
}


function showNewsTracker($id, $TrackerType, $page=1)
{//echo $TrackerType;exit;
	$uniqueId = uniqid();
	
	$data = DataGeneratorForNewsTracker($id, $TrackerType, $page);	
	
	///////////PAGING DATA
	$data_matrix = array();
	while($data1  = mysql_fetch_array($data)){
		$data_matrix[] = $data1;
	}
	
	$RecordsPerPage = 50;
	$TotalPages = 0;
	$TotalRecords = count($data_matrix);
	$MainPageURL = 'product.php';
	
	$MainPageURL = 'news_tracker.php';
	if($TrackerType == 'PNT')	//PIT=Product Investigator TRACKER
		$MainPageURL = 'product.php';
	else if($TrackerType == 'DNT')	//CIT=company investigator TRACKER
		$MainPageURL = 'disease.php';
	else if($TrackerType == 'CNT')	//CIT=company investigator TRACKER
		$MainPageURL = 'company.php';
	else if($TrackerType == 'MNT')	//MIT=MOA Investigator TRACKER
		$MainPageURL = 'moa.php';
	else if($TrackerType == 'MCNT')	//MCIT=MOA Category Investigator TRACKER
		$MainPageURL = 'moacategory.php';
	
	if(isset($_REQUEST['dwcount']))
		$CountType = $_REQUEST['dwcount'];
	else
		$CountType = 'total';
	$query = "SELECT `name`, `display_name`, `id`, `class` FROM `entities` WHERE id='" . $id ."'";
	
	$res = mysql_query($query) or die( $query . ' '.mysql_error());
	$header = mysql_fetch_array($res);
	$GobalEntityType = $header['class'];
	
	$page=!empty($_REQUEST['page'])?$_REQUEST['page']:1;
	if(!isset($_POST['download']))
	{
	
	
		//Get only those product Ids which we are planning to display on current page to avoid unnecessary queries
		
		if(!empty($data_matrix))
		{
			$TotalPages = ceil(count($data_matrix) / $RecordsPerPage);
			$StartSlice = ($page - 1) * $RecordsPerPage;
			$EndSlice = $StartSlice + $RecordsPerPage;
			$data_matrix_temp = array_slice($data_matrix, $StartSlice, $RecordsPerPage);
			$rowsTemp = array_slice($data_matrix, $StartSlice, $RecordsPerPage);
				
			if (($TotalPages > 0 ) && (count($data_matrix_temp) == 0)){
					
				$StartSlice = ($TotalPages - 1) * $RecordsPerPage;
				$EndSlice = $StartSlice + $RecordsPerPage;
				$data_matrix = array_slice($data_matrix, $StartSlice, $RecordsPerPage);
				$rows = array_slice($data_matrix, $StartSlice, $RecordsPerPage);
				$page=$TotalPages;
					
			} else {
				$data_matrix = $data_matrix_temp;
				$rows        = $rowsTemp ;
			}
				
		}
		else
		{
			$data_matrix=array();
			$rows = array();
		}
	}
	
	$HTMLContent = NewsTrackerCommonCSS($uniqueId, $TrackerType);
	
	if($TotalPages > 1)
	{
		$paginate = NewsTrackerpagination($TrackerType, $TotalPages, $id, $page, $MainPageURL, $GobalEntityType, $CountType);
		$HTMLContent .= '<br/><br/>'.$paginate[1];
	}
	$HTMLContent .= NewsTrackerHTMLContent($data_matrix);
	/////////PAGING DATA ENDS
	return $HTMLContent;
}

function NewsTrackerpagination($TrackerType, $totalPages, $id, $CurrentPage, $MainPageURL, $GobalEntityType, $CountType)
{

	$url = '';
	$stages = 5;
		
	$url = 'id=' . $id .'&amp;tab=newstrac';

	$url = 'id='.$id;
	if($TrackerType == 'PNT')	//PDT = PRODUCT News TRACKER
		$url = 'e1='.$id.'&amp;tab=newstrac';
	else if($TrackerType == 'DNT')	//DNT = Disease News TRACKER
		$url = 'DiseaseId='.$id.'&amp;tab=News';
	else if($TrackerType == 'CNT')	//CNT = COMPANY News TRACKER
		$url = 'CompanyId='.$id.'&amp;tab=newstrac';
	else if($TrackerType == 'MNT')	//MCDT = MOA News TRACKER
		$url = 'MoaId='.$id.'&amp;tab=newstrac';
	else if($TrackerType == 'MCNT')	//MCNT = MOA CATEGORY News TRACKER
		$url = 'MoaCatId='.$id.'&amp;tab=newstrac';

	if($GobalEntityType == 'Product')
		$url .= '&amp;dwcount=' . $CountType;

	$rootUrl = $MainPageURL.'?';
	$paginateStr = '<table align="center"><tr><td style="border:0px;"><span class="pagination">';

	if($CurrentPage != 1)
	{
		$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . ($CurrentPage-1) . '\'>&laquo;</a>';
	}

	$prelink = 	'<a href=\'' . $rootUrl . $url . '&page=1\'>1</a>'
			.'<a href=\'' . $rootUrl . $url . '&page=2\'>2</a>'
					.'<span>...</span>';
	$postlink = '<span>...</span>'
			.'<a href=\'' . $rootUrl . $url . '&page=' . ($totalPages-1) . '\'>' . ($totalPages-1) . '</a>'
					.'<a href=\'' . $rootUrl . $url . '&page=' . $totalPages . '\'>' . $totalPages . '</a>';
		
	if($totalPages > (($stages * 2) + 3))
	{
		if($CurrentPage >= ($stages+3)){
			$paginateStr .= $prelink;
			if($totalPages >= $CurrentPage + $stages + 2)
			{
				$paginateStr .= generateLink($CurrentPage - $stages,$CurrentPage + $stages,$CurrentPage,$rootUrl,$url);
				$paginateStr .= $postlink;
			}else{
				$paginateStr .= generateLink($totalPages - (($stages*2) + 2),$totalPages,$CurrentPage,$rootUrl,$url);
			}
		}else{
			$paginateStr .= generateLink(1,($stages*2) + 3,$CurrentPage,$rootUrl,$url);
			$paginateStr .= $postlink;
		}
	}else{
		$paginateStr .= generateLink(1,$totalPages,$CurrentPage,$rootUrl,$url);
	}

	if($CurrentPage != $totalPages)
	{
		$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . ($CurrentPage+1) . '\'>&raquo;</a>';
	}
	$paginateStr .= '</span></td></tr></table>';

	return array($url, $paginateStr);
}

function NewsTrackerHTMLContent($res) {
	$results=array();	
	
	$htmlContent  = '';//<table class="news">';

	foreach($res as $result)
	{
		//this assignment should not happen here, 
		//set summary in the database during news generation
		if($result['summary'] == 'NA')
			$result['summary'] = $result['name'];
		
		$formattedNews=formatNews($result);
		$htmlContent .= '<ul><li>' . $formattedNews .'</li></ul>';
	}
	$htmlContent .= '';//</table>';
	return $htmlContent;
}

function DataGeneratorForNewsTracker($id, $TrackerType, $page) {

	global $db;
	$query = "SELECT n.*,p.name as product ,r.*,dt.phase,dt.enrollment,dt.source,dt.source_id FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`) JOIN products p ON(et.entity = p.id) JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) JOIN `news_redtag` nr ON(nr.news=n.id) JOIN `redtags` r ON(r.id=nr.redtag)";

	if($TrackerType == 'PNT'){
		$query .= "WHERE et.`entity`='" . mysql_real_escape_string($id) . "'";

	} elseif($TrackerType == 'DNT' ) {
	 	$query = "SELECT  n.*,en.name as product ,r.*,dt.phase,dt.enrollment,dt.source,dt.source_id FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`) JOIN entities en ON(et.entity = en.id) JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) JOIN `news_redtag` nr ON(nr.news=n.id) JOIN `redtags` r ON(r.id=nr.redtag) WHERE et.`entity`='" . mysql_real_escape_string($id) . "'";
	}
	else {
		switch($TrackerType) {
			case 'CNT':
				$productIds = GetProductsFromCompany($id, 'CPT', array());
				break;
			
			case 'MNT':
				$productIds = GetProductsFromMOA($id, 'MPT', array());
				break;
			case 'MCNT':
				$productIds = GetProductsFromMOACategory($id, 'MCPT', array());
				break;
		}
		$impArr = implode("','", $productIds);
		$query .= "WHERE et.`entity` in('" . $impArr . "')";
	}
	//$query .= " order by added desc limit 50";
	$query .= " order by added desc";
	//echo $query;exit;
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
	
	$nctid = $result['source_id'];
	$source_name = '';
	if(isset($nctid) && strpos($nctid, 'NCT') === FALSE)
	{
		$ctLink = 'https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . $nctid;
		$source_name = 'EUDRACT';
	}
	else if(isset($nctid) && strpos($nctid, 'NCT') !== FALSE)
	{
		$matches = array();
		if(preg_match('/(NCT[0-9]+).*?/', $nctid,$matches))
			$nctid = $matches[1];
			
		$ctLink = 'http://clinicaltrials.gov/ct2/show/' . $nctid;
		$source_name = 'clinicaltrials.gov';
	}
	else
	{
		$ctLink = 'javascript:void(0)';
	}
	$phase = $result['phase'];
	if($phase != 'N/A') { 
		$phase = 'P'. $phase;
	}else
		$phase = 'P='. $phase;
			
	$compName[] =$result['company_name'];	
	$returnStr = '';
	$returnStr .= '<span class="rUIS">'.str_repeat('|',$result['score']).'</span><span class="barBold">'.str_repeat('|',10-floor($result['score'])).'</span>&nbsp;&nbsp;</span><span class="product_name">'.productFormatLI($result['product'], $compName, $tag='').'</span><br>';
	$returnStr .= '<span class="redtag">'.$result['name'].':&nbsp;&nbsp;</span>'.'<a class="title" href="'.$ctLink.'" target="_blank">'.$result['brief_title'].'</a>&nbsp;('.$source_name.') -&nbsp;&nbsp;';
	$returnStr .= date('M j, Y', strtotime($result['added'])).'&nbsp;-&nbsp;&nbsp;<br>'.'<span class="phase_enroll">'.$phase.', &nbsp;N='.$result['enrollment'].',&nbsp;'.$result['overall_status'].',&nbsp;</span><span class="sponsor">&nbsp;Sponsor:&nbsp;'.$result['source'].'</span><br>';
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
			color:purple;
		font-weight: bold;
		}
		.barBold{
			color:#E5E4E2;
		font-weight: bold;
		}
		.redtag {
			color:red;
		}
		.phase_enroll {
			color:black;
		}		
		.sponsor {
			color:black;
		}		
		a.title, a.title:visited, a.title:hover, a.title:active {
			color:#000080;
		}		
		.summary {
			color:black;
		}
		.pagination {
						width:100%;
						float:none;
						float: left; 
						padding-top:0px; 
						vertical-align:top;
						font-weight:bold;
						padding-bottom:25px;
						color:#4f2683;
		}
					
		.pagination a:hover {
						background-color: #aa8ece;
						color: #FFFFFF;
						font-weight:bold;
						display:inline;
		}
					
		.pagination a {
						margin: 0 2px;
						border: 1px solid #CCC;
						background-color:#4f2683;
						font-weight: bold;
						padding: 2px 5px;
						text-align: center;
						color: #FFFFFF;
						text-decoration: none;
						display:inline;
		}
					
		.pagination span {
			padding: 2px 5px;
		}
					</style>';
	return $htmlContent;				
}
?>
