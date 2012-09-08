<?php
ob_start();
if( !isset($sphinx) or empty($sphinx) ) 
{
	$sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
}
require_once 'db.php';
$search = mysql_real_escape_string($_GET['query']);
$query="SELECT * FROM rtindex1 where MATCH('".$search."') limit 5000";
$resultset = mysql_query($query,$sphinx);
$cnt=0;
$idlist="";

if(!$resultset) 
{
return false;
}

while($row = mysql_fetch_assoc($resultset)) 
{
	if($cnt==0) $idlist.="'".$row['id']."'";
	else $idlist.=",'".$row['id']."'";
	$cnt++;
}
$qry="
	SELECT dt.`brief_title`,  dt.`condition`, dt.`overall_status`, 
	dt.`intervention_name`, dt.inclusion_criteria from data_trials dt 
	where dt.larvol_id in (" . $idlist . ") ";
$res = mysql_query($qry);
$data = array();
$json = array();
$suggestions = array();
$datas = array();
if(!$res) return false;

$searchstringLength=strlen($search);
$suggestions[]="";
$i=0;
while($row = mysql_fetch_assoc($res)) 
{
	if($i>1000) break;
	$added=false;
	$parts = preg_split( '/[\.\,\`!\:\"\/ ]/',$row['brief_title']);
if(stringContainsInput($parts,$search))
	{
		$added=addDataToArray($parts,$search,$row['larvol_id']);
	}
	
	$parts = preg_split( "/[\.\,\`!\:\/ ]/",$row['intervention_name']);
	if(stringContainsInput($parts,$search) )
	{
		$added=addDataToArray($parts,$search,$row['id']);
	}
	$parts = preg_split( "/[\.\,\`!\:\/ ]/",$row['condition']);
	if(stringContainsInput($parts,$search) )
	{
		$added=addDataToArray($parts,$search,$row['id']);
	}
	$parts = preg_split( "/[\.\,\`!\:\/ ]/",$row['inclusion_criteria']);
	if(stringContainsInput($parts,$search) )
	{
		$added=addDataToArray($parts,$search,$row['id']);
	}
	
	$i++;
	$suggestions=array_unique($suggestions);
	asort($suggestions);
	foreach ($suggestions as $key => $val)
	{
		if ($suggestions[$key] == '')
		{
			unset($suggestions[$key]);
		}
	}
	$suggestions = array_values($suggestions);


}
$json['query'] = $search;
$json['suggestions'] = $suggestions;
$json['data'] = $datas;
$data[] = $json;
ob_end_clean();
gzip_compression();
echo json_encode($json);

die;

function gzip_compression() {

    //If no encoding was given - then it must not be able to accept gzip pages
    if( empty($_SERVER['HTTP_ACCEPT_ENCODING']) ) { return false; }

    //If zlib is not ALREADY compressing the page - and ob_gzhandler is set
    if (( ini_get('zlib.output_compression') == 'On'
        OR ini_get('zlib.output_compression_level') > 0 )
        OR ini_get('output_handler') == 'ob_gzhandler' ) {
        return false;
    }

    //Else if zlib is loaded start the compression.
    if ( extension_loaded( 'zlib' ) AND (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) ) {
        ob_start('ob_gzhandler');
    }

}

function addDataToArray($parts,$search,$id)
{
	$exists=false;
	$replace = array(";","[","]","(",")","\\"," ");
	global $suggestions, $datas;
	foreach($parts as $part)
	{
		$part=str_replace($replace,"",$part);
		if( stripos('x'.$part, $search) )
		{
			$suggestions[]=$part;
			$exists=true;
		}
	}
	if($exits)
	{
		$datas[] = $id;
		return true;
	}
}
function stringContainsInput($parts,$search)
{
	foreach($parts as $part)
	{
		if( stripos('x'.trim($part), trim($search)) )
			return true;
	}
	return false;
}
?>