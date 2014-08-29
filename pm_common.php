<?php
require_once 'db.php';
require_once 'include.derived.php';

error_reporting(E_WARNING);

function getIDs($days_passed=NULL) 
{
	if(!is_null($days_passed)) $days=$days_passed;
    else global $days;
    $ids = array();
    $url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term=&reldate='.$days.'&datetype=mdat&retmax=50000000&usehistory=y';

	if(PUBMED_API_URL_ARG)
	{
		$url =  $url.PUBMED_API_URL_ARG;
	}
		
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
	$xurl='http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id='.$id.'&rettype=xml&retmode=text';
	if(PUBMED_API_URL_ARG)
	{
		$xurl =  $xurl.PUBMED_API_URL_ARG;
	}
    $xml = utf8_encode(file_get_contents($xurl));
	$xml=trim($xml);
	$xml2=str_replace("<pre>", "", $xml);
	$xml2=str_replace("</pre>", "", $xml2);
	$xml2=html_entity_decode($xml2);
	$xml2=cleanupXML($xml) ;
	$xml = simplexml_load_string($xml2, 'SimpleXMLElement');	
    if ($xml === false) 
	{
		if($parse_retry>=2)
		{
			//$log="ERROR: Parsing failed for url: " . 'http://www.ncbi.nlm.nih.gov/pubmed/'.$id.'?report=xml&format=text' ;
			$log="ERROR: Parsing failed for url: " . $xurl ;
			$logger->error($log);
			echo '<br>'. $log."<br>";
		}
		else
		{
			//$log="WARNING: Parsing failed for url: " . 'http://www.ncbi.nlm.nih.gov/pubmed/'.$id.'?report=xml&format=text' ;
			$log="WARNING: Parsing failed for url: " . $xurl ;
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
			echo '<br><b>Generating news for '.$id.'...<br></b>';
			generatePubmedNewsUsingID($id);
            echo(' Import failed for this pubmed record.' . "\n<br />");
        } 
		else 
		{
			require_once('generateNews.php');
			echo '<br><b>Generating news... for '.$id.'<br></b>';
			$id = (int) $id;
			generatePubmedNewsUsingID($id);
            echo(' Pubmed record imported.' . "\n<br />");
        }
    }

    echo('Done Processing for pubmed ID: .' . $id . "\n<hr><br />");
}

function cleanupXML($xml) 
{
    $xmlOut = '';
    $inTag = false;
    $xmlLen = strlen($xml);
    for($i=0; $i < $xmlLen; ++$i) 
	{
        $char = $xml[$i];
        switch ($char) 
		{
			case '<':
			if (!$inTag) 
			{
				for($j = $i+1; $j < $xmlLen; ++$j) 
				{
					$nextChar = $xml[$j];
					switch($nextChar) 
					{
						case '<':  
						$char = htmlentities($char);
						break 2;
						case '>':  
						$inTag = true;
						break 2;
					}
				}
			} 
			else 
			{
				$char = htmlentities($char);
			}
			break;
			case '>':
			if (!$inTag) 
			{  
				$char = htmlentities($char);
			} 
			else 
			{
				$inTag = false;
			}
			break;
			default:
			if (!$inTag) 
			{
				$char = htmlentities($char);
			}
			break;
        }
        $xmlOut .= $char;
    }
    return $xmlOut;
}
function ok2runPMscraper()
{
	date_default_timezone_set('America/New_York');
	$current_time = strtotime('now');
	$day=date('l', time());
	if ( ($day=='Sunday' or $day=='Saturday') || ($current_time > strtotime($day.' this week 9:01pm') or $current_time < strtotime($day . ' this week 4:59am')) )
	{
		 return true;
	}
	else
	{
		return false;
	}
}

?>
