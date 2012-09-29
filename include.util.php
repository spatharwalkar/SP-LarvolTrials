<?php
require_once('db.php');
/* Generates a 'fingerprint' from the current user.
	Is conceptually equivalent to using their IP address, but contains more information for increased security.
*/
function genPrint()
{
	$info = $_SERVER['REMOTE_ADDR']/* .','.		//User info from the web server. Should be reliable.
			$_SERVER['REMOTE_HOST'] .','.
			$_SERVER['HTTP_CLIENT_IP'] .','.	//HTTP headers. These are direct from the client and somewhat unreliable.
			$_SERVER['HTTP_X_FORWARDED_FOR'] .','.
			$_SERVER['HTTP_X_FORWARDED_HOST'] .','.
			$_SERVER['HTTP_X_FORWARDED_SERVER'] .','.
			$_SERVER['HTTP_FROM'] .','.
			$_SERVER['HTTP_USER_AGENT']*/;	//Useragent string. Also not too reliable, but plenty obscure.
	return hash(HASH_ALGO, $info);
}

function urlPath()  //gives the userland path to this file all the way from http://
{
	static $urlpath = '';
	$out = '';
	if(strlen($urlpath))
	{
		$out = $urlpath;
	}else if(file_exists('cache/urlpath.txt')){
		$out = file_get_contents('cache/urlpath.txt');
		$urlpath = $out;
	}else{
		$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
		$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
		$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
		$full = $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
		$beforeq = strpos($full,'?')===false?$full:substr($full,0,strpos($full,'?'));
		$out = substr($beforeq,0,strrpos($beforeq,'/')+1);
		$urlpath = $out;
		file_put_contents('cache/urlpath.txt', $out);
	}
	return $out;
}
function strleft($s1, $s2) //helper function for urlPath()
{
	return substr($s1, 0, strpos($s1, $s2));
}

function urlBase()  //gives the userland path to the siteroot all the way from http://
{
	static $urlpath = '';
	$out = '';
	if(strlen($urlpath))
	{
		$out = $urlpath;
	}else{
		$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
		$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
		$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
		$out = $protocol."://".$_SERVER['SERVER_NAME'].$port;
		$urlpath = $out;
	}
	return $out;
}

//used for generating random passwords
function generateCode($length=7)
{
	//character set excludes confusable symbols to make it easy on people.
	//also excludes vowels to avoid making bad words
	$chars = "bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ23456789";
	$code = "";
	$clen = strlen($chars) - 1;  //a variable with the fixed length of chars correct for the fence post issue
	while (strlen($code) < $length)
	{
		$code .= $chars[mt_rand(0,$clen)];  //mt_rand's range is inclusive - this is why we need 0 to n-1
	}
	return $code;
}

function getEnumValues($Table,$Column)
{
	$dbSQL = "SHOW COLUMNS FROM `".$Table."` LIKE '".$Column."'";
	$dbQuery = mysql_query($dbSQL);

	$dbRow = mysql_fetch_assoc($dbQuery);
	$EnumValues = $dbRow["Type"];

	$EnumValues = substr($EnumValues, 6, strlen($EnumValues)-8);
	return explode("','",$EnumValues);
}

//converts any value (objects, arrays) to XML.
function toXML($in, $indent = 0)
{
	$space = ' ';
	$spacer = '';
	for($i = 0; $i < $indent; ++$i) $spacer .= $space;
	if(is_array($in))
	{
		$xml = array();
		foreach($in as $key => $val)
		{
			if(is_object($val))
			{
				$xml[] = toXML($val, $indent+1);
			}else{
				if(is_numeric($key))
				{
					if(is_array($val))
					{
						$xml[] = $spacer . '<array>' . "\n" . toXML($val, $indent+1) . "\n" . $spacer . '</array>';
					}else{
						$xml[] = $spacer . '<value>' . "\n" . toXML($val, $indent+1) . "\n" . $spacer . '</value>';
					}
				}else{
					$xml[] = $spacer . '<' . $key . '>' . "\n" . toXML($val,$indent+1) . "\n" . $spacer . '</' . $key . '>';
				}
			}
		}
		return implode("\n",$xml);
	}else if(is_object($in)){
		$xml = $spacer . '<' . get_class($in) . '>' . "\n";
		foreach($in as $name => $val)
		{
			$xml .= $spacer . $space . '<' . $name . '>' . "\n"
					. toXML($val, $indent+2) . "\n"
					. $spacer . $space . '</' . $name . '>' . "\n";
		}
		$xml .= $spacer . '</' . get_class($in) . '>';
		return $xml;
	}else{
		return $spacer . $in;
	}
}

//returns html for an option-select control
function makeDropdown($name,$vals,$multi=false,$selected=NULL,$usekeys=false)
{
	if($selected === NULL)
	{
		$selected = array();
	}else if(!is_array($selected)){
		$selected = array($selected);
	}
	
	$out = '<select name="' . $name . ($multi ? ('[]" multiple="multiple" size="' . $multi . '"') : '"') . '>';
	foreach($vals as $key => $value)
	{
		if($usekeys == false) $key = $value;
		$out .= '<option value="' . $key . '"' . (in_array($key,$selected)?' selected="selected"':'') . '>' . $value . '</option>';
	}
	$out .= '</select>';
	return $out;
}

//returns the number of years represented by a english time description
// e.g. "2 weeks" = 20160
//returns null if the input reads 'N/A'
function strtoyears($text)
{
	if(strtoupper($text) == 'N/A') return 'NULL';
	$now = strtotime('now');
	return round( (strtotime('+'.$text,$now) - $now) / 60 / 60 / 24 / 365);
}

//mysql_escapes the text - if empty string is passed, returns 'NULL'
//intended for numeric values
function escrn($text)
{
	if(!strlen($text)) return 'NULL';
	return mysql_real_escape_string($text);
}

//returns 1 or 0 based on the input being "yes" or "no" -- returns NULL otherwise
function ynbool($yn)
{
	$yn = strtolower($yn);
	if($yn == 'yes' || $yn == 'y') return 1;
	if($yn == 'no' || $yn == 'n') return 0;
	return 'NULL';
}

// "null, or (escape and quote)"
function nrescnq($val)
{
	return ($val===NULL) ? 'NULL' : ('"'.mysql_real_escape_string($val).'"');
}

//turns a numeric NCTID into the full form including the string "NCT" and the leading zeroes
function padnct($id)
{
	if(isset($id) && substr($id,0,3) == 'NCT') return ($id);
	return 'NCT' . sprintf("%08s",$id);
}

function unpadnct($val)
{
	if(substr($val,0,3) == 'NCT') 
	{
		return ((int)right($val,8));
	}
	return $val;
}

function right($string,$chars)
{
    $vright = substr($string, strlen($string)-$chars,$chars);
    return $vright;
   
} 

function base64Decode($encoded)
{
	$decoded = "";
	for($i=0; $i < ceil(strlen($encoded)/256); $i++)
		$decoded .= base64_decode(substr($encoded,$i*256,256));
	return $decoded;
}

// unsets all null values in an arbitrarily complex data structure.
function unset_nulls(&$thing)
{
	$didanything = false;
	foreach($thing as $key => $value)
	{
		$didanything = true;
		$gotval = true;
		if(is_array($thing))
		{
			if(is_array($thing[$key]) || is_object($thing[$key])) $gotval = unset_nulls($thing[$key]);
			if($thing[$key] === NULL || count($thing[$key]) == 0 || !$gotval
				|| (is_string($thing[$key]) && strlen($thing[$key]) == 0))
			{
				unset($thing[$key]);
			}
		}
		if(is_object($thing))
		{
			if(is_array($thing->$key) || is_object($thing->$key)) $gotval = unset_nulls($thing->$key);
			if($thing->$key === NULL || count($thing->$key) == 0 || !$gotval
				|| (is_string($thing->$key) && strlen($thing->$key) == 0))
			{
				unset($thing->$key);
			}
		}
	}
	return $didanything;
}

function ref_mysql_escape(&$value, $field)
{
	$value = mysql_real_escape_string($value);
}

//starts with A=0
function num2char($num)
{
	$char = 'A';
	while($num-- > 0)
	{
		++$char;
	}
	return $char;
}

//changes minutes to years and rounds to the nearest
function minutes2years($min)
{
	return (int)round($min/60/24/365);
}

//like implode, but skips empties
function assemble($glue, $pieces)
{
	foreach($pieces as $key => $val) if(!strlen($val)) unset($pieces[$key]);
	return implode($glue,$pieces);
}

//Takes an array of arrays, returns the result of array_intersect on all the arrays contained in the argument array
function array_split_intersect($arr)
{
	$names = array();
	foreach($arr as $key => $value) $names[] = '$arr[' . $key . ']';
	$terms = count($names);
	$names = implode(',', $names);
	if($terms == 0) return array();
	if($terms == 1) return $arr[0];
	$code = '$retval = array_intersect(' . $names . ');';
	eval($code);
	return $retval;
}

//returns an array with () around every item
function parenthesize($arr)
{
	if(!is_array($arr)) return '(' . $arr . ')';
	return array_map('parenthesize',$arr);
}

//throws an exception with the given message
function tex($msg)
{
	throw new Exception($msg);
}

function softDie($out)
{
	if(!mysql_query('ROLLBACK'))
	{
		$log = "Couldn't rollback changes";
		die($log);
	}
	echo($out);
	return false;
}

//Log all data errors while importing data from ct.gov
function logDataErr($out)
{
	if(!mysql_query('ROLLBACK'))
	{
		$log = "Couldn't rollback changes";
		die($log);
	}
	echo($out);
	return true;
}

//Add a URL to the internal URL shortening service
function addYourls($url,$title='',$keyword='')
{
	$format = 'xml';				// output format: 'json', 'xml' or 'simple'
	// Init the CURL session
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, YOURLS_URL);
	curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP'); 
	curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, array(     // Data to POST
			'url'		=> $url,
			'keyword'	=> $keyword,
			'title'		=> $title,
			'format'	=> $format,
			'action'	=> 'shorturl',
			'username'	=> YOURLS_USER,
			'password'	=> YOURLS_PASS
		));

	// Fetch and return content
	$data = curl_exec($ch);
	curl_close($ch);
	$pos = strpos($data, '<shorturl>');				/*find shorturl*/		if($pos === false) return false;
	$pos += strlen('<shorturl>');					/*seek to shorturl*/
	$endpos = strpos($data, '</shorturl>', $pos);	/*find end of shorturl*/if($endpos === false) return false;
	$length = $endpos - $pos;						/*calc length of shorturl*/
	$shorturl = substr($data, $pos, $length);		/*get shorturl*/		if($shorturl === false) return false;
	return $shorturl;
}

//array_merge that skips non-array args
function array_merge_s()
{
	$args = array();
	foreach(func_get_args() as $arr) if(is_array($arr)) $args[] = $arr;
	if(empty($args)) return array();
	$out = '$out=array_merge($args[' . implode('],$args[',array_keys($args)) . ']);';
	eval($out);
	return $out;
}

//opposite of empty().
//Used for operations that require callbacks where you can't just throw in a negate
function nonempty($f){return !empty($f);}

//Recursive version of array_filter.
function array_filter_recursive($input, $callback = NULL)
{
	foreach($input as &$value)
	{
		if(is_array($value))
		{
			$value = array_filter_recursive($value, $callback);
		}
	}
    return array_filter($input, $callback);
}

//ergonomic print_r for development cases
function pr($arr)
{
	echo '<pre>';
	print_r($arr);
	echo '</pre>';
}

//gets the ID of a field given the name (and category)
//returns false on failure.
function getFieldId($category,$name)
{
	$query = 'SELECT data_fields.id AS "id" '
		. 'FROM data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="' . $name . '" AND data_categories.name="' . $category . '" LIMIT 1';
	$res = mysql_query($query);
	
	if($res === false)
	{
		$log = 'Bad SQL query getting field ID of ' . $category . '/' . $name;
		
		return softDie($log);
	}
	$res = mysql_fetch_assoc($res);
	if($res === false)
	{
		$log = 'Field ' . $name . ' not found in category ' . $category . '!';
		return softDie($log);
	}
	return $res['id'];
}

//sql explicit nullifer
function sqlExplicitNullifier($val,$type=null)
{
	switch($type)
	{
		case 'date':
			if($val=='' || $val=='0000-00-00')
			return 'null';
			else
			return "'".$val."'";
			break;
		default:
			if($val=='')
			return 'null';
	}
	return "'".$val."'";
}

//back ticks every string passed into it
function backTicker($in)
{
	if($in)
	return '`'.$in.'`';
	else
	return null;
}

function searchHandlerBackTicker(&$item,$key,$userKey)
{
	if($key == $userKey)
	{
		$item = backTicker($item);
	}
}

// Fulltext search using Sphinx
function sphinx_search($srch_string=null)
{
global $sphinx,$db;
if(!isset($sphinx)) return false;
if(!isset($srch_string)) return false;
$_POST['sphinx_s']=$srch_string;
$str=$srch_string;
$qry="SELECT * FROM rtindex1 where MATCH('".$str."') limit 15000";
$rs = mysql_query($qry,$sphinx);
$cnt=0;
$idlist="";
while($row = mysql_fetch_assoc($rs)) {
	if($cnt==0) $idlist.="'".$row['id']."'";
	else $idlist.=",'".$row['id']."'";
	$cnt++;
}
	

$qry="
	SELECT dt.`larvol_id`, dt.`source_id`, dt.`brief_title`, dt.`acronym`, dt.`lead_sponsor`, dt.`collaborator`, dt.`condition`, 
	dt.`overall_status`, dt.`is_active`, dt.`start_date`, dt.`end_date`, dt.`enrollment`, dt.`enrollment_type`, dt.`intervention_name`, 
	dt.`region`, dt.`lastchanged_date`, dt.`phase`, dt.`firstreceived_date`, dt.`viewcount`, dt.`source`, dm.`larvol_id` 
	AS manual_larvol_id, dm.`is_sourceless` AS manual_is_sourceless, dm.`brief_title` AS manual_brief_title, dm.`acronym` 
	AS manual_acronym, dm.`lead_sponsor` AS manual_lead_sponsor, dm.`collaborator` AS manual_collaborator, dm.`condition` 
	AS manual_condition, dm.`overall_status` AS manual_overall_status, dm.`region` AS manual_region, dm.`end_date` 
	AS manual_end_date, dm.`enrollment` AS manual_enrollment, dm.`enrollment_type` AS manual_enrollment_type, dm.`intervention_name` 
	AS manual_intervention_name, dm.`phase` AS manual_phase  FROM `data_trials` dt LEFT JOIN `data_manual` dm ON dt.`larvol_id` = dm.`larvol_id`
	where dt.larvol_id in (" . $idlist . ") ORDER BY  dt.`phase` DESC, dt.`end_date` ASC, dt.`start_date` ASC, dt.`overall_status` ASC, 
	dt.`enrollment` ASC ";
$res = mysql_query($qry);
return $res;
/*
while($row = mysql_fetch_assoc($res)) {
	pr($row);
	}
*/	
}

function get_sphinx_idlist($srch_string=null)
{
	global $sphinx,$db;
	if(!isset($sphinx)) return false;
	if(!isset($srch_string)) return false;
	$_POST['sphinx_s']=$srch_string;
	$str=$srch_string;
	$qry="SELECT id FROM rtindex1 where MATCH('".$str."') limit 10000 OPTION max_matches=10000" ;

	$rs = mysql_query($qry,$sphinx);
	$cnt=0;
	$idlist="";
	while($row = mysql_fetch_assoc($rs)) 
	{
		if($cnt==0) $idlist.="'".$row['id']."'";
		else $idlist.=",'".$row['id']."'";
		$cnt++;
	}
	return $idlist;
}

// Delete trial from Sphinx index
function delete_sphinx_index($l_id)
{
	global $sphinx;
	if(!isset($sphinx)) return false;
	if(!$l_id) return false;
	
	$query ="DELETE FROM rtindex1 WHERE id =" . $l_id  ;
	$result = mysql_query($query,$sphinx);
	if (!$result) 
	{
		echo mysql_error($sphinx);
		return false;
	}
	else return true;

}


//Update trial in Sphinx index
function update_sphinx_index($l_id)
{
	if(!$l_id) return false;
	$query = 'SELECT  
				larvol_id, source_id, brief_title, acronym, official_title, lead_sponsor, collaborator, institution_type, source, 
				brief_summary, detailed_description, overall_status, is_active, enrollment, criteria, inclusion_criteria, 
				exclusion_criteria, org_study_id, phase, `condition`, intervention_name, intervention_description, 
				primary_outcome_measure, primary_outcome_timeframe, region, keyword,lastchanged_date
				from data_trials where larvol_id = '. $l_id .' limit 1 ';
	

	$res = mysql_query($query) or die(mysql_error());
	if($res === false)
	{
		$log = 'Bad SQL query getting data from data_trials. Query='.$query.' , Error='.mysql_error();
		return softDie($log);
	}
	$res = mysql_fetch_assoc($res);
	if($res === false)
	{
		return false;
	}
    
	$qry2="REPLACE INTO rtindex1 
	(
	id, source_id, brief_title, acronym, official_title, lead_sponsor, collaborator, institution_type, 
	source,  brief_summary, detailed_description, overall_status, is_active,  enrollment, criteria, inclusion_criteria, 
	exclusion_criteria, org_study_id, phase, condition, intervention_name, intervention_description, 
	primary_outcome_measure, primary_outcome_timeframe, region, keyword,lastchanged_date 
	)";
	$qry="
	VALUES 
	(".
	(($res['larvol_id'])?"'".str_replace("'", "",$res['larvol_id'])."'":"''").",".
	(($res['source_id'])?"'".str_replace("'", "",$res['source_id'])."'":"''").",".
	(($res['brief_title'])?"'".str_replace("'", "",$res['brief_title'])."'":"''").",".
	(($res['acronym'])?"'".str_replace("'", "",$res['acronym'])."'":"''").",".
	(($res['official_title'])?"'".str_replace("'", "",$res['official_title'])."'":"''").",".
	(($res['lead_sponsor'])?"'".str_replace("'", "",$res['lead_sponsor'])."'":"''").",".
	(($res['collaborator'])?"'".str_replace("'", "",$res['collaborator'])."'":"''").",".
	(($res['institution_type'])?"'".str_replace("'", "",$res['institution_type'])."'":"''").",".
	(($res['source'])?"'".str_replace("'", "",$res['source'])."'":"''").",".
	(($res['brief_summary'])?"'".str_replace("'", "",$res['brief_summary'])."'":"''").",".
	(($res['detailed_description'])?"'".str_replace("'", "",$res['detailed_description'])."'":"''").",".
	(($res['overall_status'])?"'".str_replace("'", "",$res['overall_status'])."'":"''").",".
	(($res['is_active'])?"'".str_replace("'", "",$res['is_active'])."'":"''").",".
	(($res['enrollment'])?"'".str_replace("'", "",$res['enrollment'])."'":"''").",".
	(($res['criteria'])?"'".str_replace("'", "",$res['criteria'])."'":"''").",".
	(($res['inclusion_criteria'])?"'".str_replace("'", "",$res['inclusion_criteria'])."'":"''").",".
	(($res['exclusion_criteria'])?"'".str_replace("'", "",$res['exclusion_criteria'])."'":"''").",".
	(($res['org_study_id'])?"'".str_replace("'", "",$res['org_study_id'])."'":"''").",".
	(($res['phase'])?"'".str_replace("'", "",$res['phase'])."'":"''").",".
	(($res['condition'])?"'".str_replace("'", "",$res['condition'])."'":"''").",".
	(($res['intervention_name'])?"'".str_replace("'", "",$res['intervention_name'])."'":"''").",".
	(($res['intervention_description'])?"'".str_replace("'", "",$res['intervention_description'])."'":"''").",".
	(($res['primary_outcome_measure'])?"'".str_replace("'", "",$res['primary_outcome_measure'])."'":"''").",".
	(($res['primary_outcome_timeframe'])?"'".str_replace("'", "",$res['primary_outcome_timeframe'])."'":"''").",".
	(($res['region'])?"'".str_replace("'", "",$res['region'])."'":"''").",".
	(($res['keyword'])?"'".str_replace("'", "",$res['keyword'])."'":"''").",".
	(($res['lastchanged_date'])?"'".str_replace("'", "",$res['lastchanged_date'])."'":"''")
	.")";
	
	$qry=$qry2.$qry;
	global $sphinx;
	if(!isset($sphinx)) return false;
	$res = mysql_query($qry,$sphinx);
//	mysql_close($sphinx);
	if($res === false)
	{
		$log = '<br>Bad SQL query updating Sphinx index. Query:<br>'.$qry.'<br> ,<b>Error:</b><br>'.mysql_error($sphinx) ;
		return softDie($log);
	}
}

function formatBrandName($inputStr)
{	
	$outputStr = '';
	$a = array();
	$outputArr = array();
	
	preg_match('/^(.*)\s\((.*)\)\s\/\s(.*)$/', $inputStr, $outputArr);
	if(empty($outputArr))
	{
		$a = explode('/', $inputStr, 2);
		$outputStr = '<b>' . trim($a[0]) . '</b> /' . $a[1];
	}
	else
	{
		$outputStr = '<b>' . $outputArr[1] . '</b> (' . $outputArr[2] . ') / ' . $outputArr[3];
	}
	
	return $outputStr;
}
?>