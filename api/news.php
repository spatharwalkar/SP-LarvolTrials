<?php
chdir('..');
require_once('db.php');
$id = mysql_real_escape_string($_GET['id']);
$days = mysql_real_escape_string($_GET['days']);


function generateNewsIDs($days) {
	//$query = 'select CONCAT("[",GROUP_CONCAT(distinct id),"]") as id from news where (added >= DATE_SUB(current_date,interval '.$days.' day)) OR (COALESCE(last_changed_date,generation_date) >= DATE_SUB(current_date,interval '.$days.' day)) ';
	$query = 'select CONCAT("[",GROUP_CONCAT(distinct news.id),"]") as id from news 
				join entity_abstracts ea on (news.abstract_id=ea.abstract)
				join entities e1 on (ea.entity=e1.id and e1.class="product" and e1.is_active=1)
				join entity_abstracts ea2 on (news.abstract_id=ea2.abstract)
				join entities e2 on (ea2.entity=e2.id and e2.class in ("area","disease"))
				where 	(added >= DATE_SUB(current_date,interval '.$days.' day)) OR 
						(COALESCE(last_changed_date,generation_date) >= DATE_SUB(current_date,interval '.$days.' day)) ';

	$json = runNewsQuery($query);	
	echo($json);
	$json = runNewsQuery($query);	
	echo($json);
}

function generateNewsEntities($id) {
	$query ='SELECT 
					CONCAT	(
								"[",GROUP_CONCAT(DISTINCT concat("{\"LI_id\":\"",p.LI_id),concat("\",\"name\":\"",REPLACE(p.name,\'"\',\'&quot;\')),concat("\",\"owner_sponsored\":\"",IF(pt.relation_type = \'ownersponsored\', 1, 0),"\"}")),"]"
							) 	
							as product,
					CONCAT	(
								"[",GROUP_CONCAT(DISTINCT concat("{\"LI_id\":\"",COALESCE(d.LI_id,"N/A")),concat("\",\"name\":\"",REPLACE(d.name,\'"\',\'&quot;\'),"\"}")),"]"
							) 	
							as disease,		
					CONCAT	(
								"[",GROUP_CONCAT(DISTINCT concat("{\"LI_id\":\"",COALESCE(i.LI_id,"N/A")),concat("\",\"name\":\"",REPLACE(i.name,\'"\',\'&quot;\'),"\"}")),"]"
							) 	
							as investigator,
							t.source_id,REPLACE(t.brief_title,\'"\',\'&quot;\') as brief_title,n.phase,n.score,
							CONCAT("[",( SELECT GROUP_CONCAT(CONCAT("{", \'"\' ,counter, \'"\', ":", \'"\', LI_id, \'"\', "}")) 
							FROM ( SELECT rt1.LI_id as LI_id, @counter := CASE WHEN @prev = @counter THEN @counter + 1 ELSE 0 END AS counter,
							@prev := @counter FROM news n1 JOIN news_redtag nr1 on nr1.news=n1.id JOIN redtags rt1 on rt1.id=nr1.redtag, (
							SELECT @counter:=1, @prev:=NULL) as vars WHERE n1.id='.$id.' ) as liid ),"]") as redtag_id,
							REPLACE(n.sponsor,\'"\',\'&quot;\') AS sponsor,IF(EXISTS(SELECT nw.id FROM news nw, entity_trials et WHERE nw.id='.$id.' 
							AND nw.larvol_id=et.trial AND relation_type=\'ownersponsored\'),1,0) as is_product_owner_sponsored_active,n.summary,
							n.enrollment,n.overall_status as status,n.added,DATE(COALESCE(n.last_changed_date,n.generation_date)) as generation_date 
					FROM news n 
					JOIN data_trials t using(larvol_id)
					LEFT JOIN entity_trials pt on n.larvol_id=pt.trial 
					LEFT JOIN entity_trials it on n.larvol_id=it.trial 
	            	LEFT JOIN entity_trials dt on n.larvol_id=dt.trial 
				    LEFT JOIN entities p on p.id=pt.entity and p.class = "Product" and p.is_active=1 				
					LEFT JOIN entities d on d.id=dt.entity and d.class="Disease"
					LEFT JOIN entities i on i.id=it.entity and i.class="Investigator" 
					JOIN news_redtag nr on nr.news=n.id 
					JOIN redtags rt on rt.id=nr.redtag 
					WHERE n.id=' . $id .
					' GROUP BY n.larvol_id,n.brief_title,n.phase,n.summary,n.enrollment,COALESCE(n.last_changed_date,n.generation_date)';
	
	$tmpq = 'SELECT `larvol_id` FROM news where `id`="' . $id . '" and larvol_id is not null  LIMIT 1';
	if(!$tres = mysql_query($tmpq))
	{
		$log='There seems to be a problem with the SQL Query:'.$tmpq.' Error:' . mysql_error();
		echo $log;
		return false;
	}
	$tres = mysql_fetch_assoc($tres);

	if(empty($tres['larvol_id'])) //if larvol_id is empty, then run the other query for pubmed
	{				
		$query = '
		SELECT 
		CONCAT	(
					"[",GROUP_CONCAT(DISTINCT concat("{\"LI_id\":\"",p.LI_id),concat("\",\"name\":\"",REPLACE(p.name,\'"\',\'"\')),concat("\",\"owner_sponsored\":\"",0,"\"}")),"]"
				) 	
				as product,
		CONCAT	(
					"[",GROUP_CONCAT(DISTINCT concat("{\"LI_id\":\"",COALESCE(d.LI_id,"N/A")),concat("\",\"name\":\"",REPLACE(d.name,\'"\',\'"\'),"\"}")),"]"
				) 	
				as disease,		
		NULL as investigator,
				pma.source_id as source_id,REPLACE(n.brief_title,\'"\',\'"\') as brief_title,n.phase,n.score,
				CONCAT("[",( SELECT GROUP_CONCAT(CONCAT("{", \'"\' ,counter, \'"\', ":", \'"\', LI_id, \'"\', "}")) 
					FROM ( SELECT rt1.LI_id as LI_id, @counter := CASE WHEN @prev = @counter THEN @counter + 1 ELSE 0 END AS counter,@prev := @counter FROM 
					news n1 JOIN news_redtag nr1 on nr1.news=n1.id JOIN redtags rt1 on rt1.id=nr1.redtag, 
					(SELECT @counter:=1, @prev:=NULL) as vars WHERE n1.id='.$id.' ) as liid ),"]") as redtag_id,REPLACE(n.sponsor,\'"\',\'"\') AS sponsor,
					IF(EXISTS(SELECT nw.id FROM news nw, entity_trials et WHERE nw.id='.$id.' AND nw.larvol_id=et.trial AND 
					relation_type=\'ownersponsored\'),1,0) as is_product_owner_sponsored_active,n.summary,n.enrollment,n.overall_status as status,n.added ,
					DATE(COALESCE(n.last_changed_date,n.generation_date)) as generation_date
				FROM news n 
				JOIN pubmed_abstracts pma on (n.abstract_id=pma.pm_id)
				LEFT JOIN entity_abstracts pt on n.abstract_id=pt.abstract
				LEFT JOIN entity_abstracts dt on n.abstract_id=dt.abstract
				LEFT JOIN entities p on p.id=pt.entity and p.class = "Product" 				
				LEFT JOIN entities d on d.id=dt.entity and d.class="Disease"
				JOIN news_redtag nr on nr.news=n.id 
				JOIN redtags rt on rt.id=nr.redtag 
				WHERE n.id=' . $id . 
				' GROUP BY n.abstract_id,n.brief_title,n.phase,n.summary,n.enrollment,COALESCE(n.last_changed_date,n.generation_date)';
	}

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

	/** In case of pubmed abstracts, show only the last two sentences of summary */
	if(!empty($res['source_id']))
	{
		$chunks = preg_split('#[\r\n]#', $res['summary'], -1, PREG_SPLIT_NO_EMPTY);

		foreach($chunks as $val)
		{
			//preg_match_all('#(?:\s[a-z]\.(?:[a-z]\.)?|.)+?[.?!]+#i', $val, $paragraph);
			preg_match_all('#(?:\s[a-z]\.(?:[a-z]\.)?|.)+?(?:\.\s|\?\s|!\s|$|\.`)+#i', $val, $paragraph);
			foreach($paragraph[0] as $val)
			{
				$sentences[] = ltrim($val);
			}
		}
		if(is_array($sentences))
		{
			$sentences = @array_slice($sentences,-2,2,false);  
			$res['summary']=$sentences[0].' '.$sentences[1];
		}
	}
	/**************/
	
	$json = json_encode($res, JSON_UNESCAPED_UNICODE);
	global $days;
	if( !empty($days) ) 
	{
		$json = str_replace('"[', '[', $json);
		$json = str_replace(']"', ']', $json);
	}
	else
	{
		$json = str_replace('"[{', '[{', $json);
		$json = str_replace('}]"', '}]', $json);
	}
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