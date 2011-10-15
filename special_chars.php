<?php 
//tkv

function fix_special_chars($in_txt)
{

	$in_txt1 = str_replace('Â®','®', $in_txt);
	if($in_txt1==$in_txt) $in_txt = str_replace('®','Â®', $in_txt);
	
	$in_txt1 = str_replace('Â©','©', $in_txt);
	if($in_txt1==$in_txt) $in_txt = str_replace('©','Â©', $in_txt);
	
	$in_txt1 = str_replace('â„¢','™', $in_txt);
	if($in_txt1==$in_txt) $in_txt = str_replace('™','â„¢', $in_txt);
	
	$in_txt1 = str_replace('Â£','£', $in_txt);
	if($in_txt1==$in_txt) $in_txt = str_replace('£','Â£', $in_txt);
	
	$in_txt = str_replace('“','"', $in_txt);
	$in_txt = str_replace('”','"', $in_txt);
	$in_txt = str_replace('`',",", $in_txt);
	$in_txt = str_replace('¼',"&#188;", $in_txt);
	$in_txt = str_replace('½',"&#189;", $in_txt);
	
	$in_txt = iconv("UTF-8","UTF-8//IGNORE",$in_txt); 
	
	return $in_txt;
	
}	

?>