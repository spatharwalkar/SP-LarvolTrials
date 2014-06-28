<?php
require_once 'include.derived.php';
error_reporting(E_WARNING);

function getIDs($days_passed=NULL) 
{
	if(!is_null($days_passed)) $days=$days_passed;
    else global $days;
    $ids = array();
    $url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term=&reldate='.$days.'&datetype=edat&retmax=50000000&usehistory=y';
	
	$xml = file_get_contents($url);
	$xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOWARNING | LIBXML_NOERROR);
	$source_id = $xml->IdList->Id;
	
    return $source_id ;
}

function ProcessNew($id) 
{

	global $parse_retry;
	global $logger;
    echo "<hr>Processing new Record " . $id . "<br/>";

    echo('Getting XML for pubmed ID ' . $id . '... - ');
    $xml = utf8_encode(file_get_contents('http://www.ncbi.nlm.nih.gov/pubmed/'.$id.'?report=xml&format=text'));
	$xml=trim($xml);
	$xml2=str_replace("<pre>", "", $xml);
	$xml2=str_replace("</pre>", "", $xml2);
	$xml2=html_entity_decode($xml2);
	$xml = simplexml_load_string($xml2, 'SimpleXMLElement');	
    if ($xml === false) 
	{
		if($parse_retry>=5)
		{
			$log="ERROR: Parsing failed for url: " . 'http://www.ncbi.nlm.nih.gov/pubmed/'.$id.'?report=xml&format=text' ;
			$logger->error($log);
			echo '<br>'. $log."<br>";
		}
		else
		{
			$log="WARNING: Parsing failed for url: " . 'http://www.ncbi.nlm.nih.gov/pubmed/'.$id.'?report=xml&format=text' ;
			$logger->warn($log);
			echo '<br>'. $log."<br>";
			$parse_retry ++;
			sleep((1)); 
			ProcessNew($id);
		}
		/*******************/
    } 
	else 
	{
		$parse_retry=0;
        echo('Importing... - ');
        if (addRecord($xml) === false) 
		{
			require_once('generateNews.php');
			echo '<br><b>Generating news...<br></b>';
			generatePubmedNewsUsingID($id);
            echo(' Import failed for this pubmed record.' . "\n<br />");
        } 
		else 
		{
			require_once('generateNews.php');
			echo '<br><b>Generating news...<br></b>';
			generatePubmedNewsUsingID($id);
            echo(' Pubmed record imported.' . "\n<br />");
        }
    }

    echo('Done Processing for pubmed ID: .' . $id . "\n<hr><br />");
}
?>
