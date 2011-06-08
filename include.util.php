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
	if(isset($val) && substr($val,0,3) == 'NCT') return ($id);
	return 'NCT' . sprintf("%08s",$id);
}

function unpadnct($val)
{
	if(substr($val,0,3) == 'NCT') return (int)substr($val,3);
	return $val;
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
	global $logger;
	$logger->error($log);
	throw new Exception($msg);
}

function softDie($out)
{
	global $logger;
	$logger->error($out);
	if(!mysql_query('ROLLBACK'))
	{
		$log = "Couldn't rollback changes";
		$logger->fatal($log);
		die($log);
	}
	echo($out);
	return false;
}

//Log all data errors while importing data from ct.gov
function logDataErr($out)
{
	global $logger;
	$logger->error($out);
	if(!mysql_query('ROLLBACK'))
	{
		$log = "Couldn't rollback changes";
		$logger->fatal($log);
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
?>