<?php
chdir('..');
require_once('db.php');
$id = mysql_real_escape_string($_GET['id']);
$days = mysql_real_escape_string($_GET['days']);


function generateNewsIDs($days) {
	$query = 'select CONCAT("[",GROUP_CONCAT(id),"]") as id from news where added >= DATE_SUB(current_date,interval '.$days.' day) ';
	$json = runNewsQuery($query);	
	echo($json);
}

function generateNewsEntities($id) {
	$query ='SELECT 
					CONCAT	(
								"[",GROUP_CONCAT(DISTINCT concat("{\"LI_id\":\"",p.LI_id),concat("\",\"name\":\"",REPLACE(p.name,\'"\',\'&quot\'),"\"}")),"]"
							) 	
							as product,
					CONCAT	(
								"[",GROUP_CONCAT(DISTINCT concat("{\"LI_id\":\"",COALESCE(d.LI_id,"N/A")),concat("\",\"name\":\"",REPLACE(d.name,\'"\',\'&quot\'),"\"}")),"]"
							) 	
							as disease,		
					CONCAT	(
								"[",GROUP_CONCAT(DISTINCT concat("{\"LI_id\":\"",COALESCE(i.LI_id,"N/A")),concat("\",\"name\":\"",REPLACE(i.name,\'"\',\'&quot\'),"\"}")),"]"
							) 	
							as investigator,
					t.source_id,n.larvol_id,REPLACE(n.brief_title,\'"\',\'&quot\') as brief_title,n.phase,n.score,rt.LI_id as redtag_id,
					REPLACE(n.sponsor,\'"\',\'&quot\') AS sponsor,n.summary,n.enrollment,n.overall_status as status,n.added 
					FROM news n 
					JOIN data_trials t using(larvol_id)
					LEFT JOIN entity_trials pt on n.larvol_id=pt.trial 
					LEFT JOIN entity_trials it on n.larvol_id=it.trial 
	            	LEFT JOIN entity_trials dt on n.larvol_id=dt.trial 
				    LEFT JOIN entities p on p.id=pt.entity and p.class = "Product" 				
					LEFT JOIN entities d on d.id=dt.entity and d.class="Disease"
					LEFT JOIN entities i on i.id=it.entity and i.class="Investigator" 
					LEFT JOIN redtags rt on rt.id=n.redtag_id 
					WHERE n.id=' . $id .
					' GROUP BY n.larvol_id,n.brief_title,n.phase,n.redtag_id,n.summary,n.enrollment,n.added';

	$json = runNewsQuery($query);
	$json = str_replace('\\',  '', $json);
	echo($json);
}

function runNewsQuery($query) {
	$res = mysql_query($query);
	if (!$res) {
		http_response_code(400);
		$msg = "Invalid query " . mysql_error();
		jsonMessg($msg);
	}
	if (!mysql_num_rows($res)) {
		http_response_code(404);
		$msg = "cannot find data with your input params";
		jsonMessg($msg);
	}
	$res = mysql_fetch_assoc($res) or die('cannot fetch with id=$id' . mysql_error());
	$json = json_encode($res, JSON_UNESCAPED_UNICODE);
	$json = str_replace('"[', '[', $json);
	$json = str_replace(']"', ']', $json);
	return $json;
}

function jsonMessg($msg) {
	echo '{"type":"exception","message":"'.$msg.'"}';
	exit;
}

if(empty($id)) {
	if(empty($days)) {
		$msg = 'news.php takes as input, a news id OR number of days to fetch news ids for';
		jsonMessg($msg);
	}
	generatenewsIDs($days);
	exit;
}
generateNewsEntities($id);
?>