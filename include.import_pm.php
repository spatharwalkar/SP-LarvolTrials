<?php
require_once('db.php');
$newrecord='NO';

function addRecord($in)
{

	return addPubmed($in);
	return false;
}

// Add or update a Pubmed record from a SimpleXML object.
function addPubmed($rec)
{
	global $db;
	global $now;
	global $logger;
	if($rec === false) return false;
	//pr($rec);
	$rec=$rec->PubmedArticle;
	$DTnow = date('Y-m-d H:i:s',$now);

	/*** MAPPING *************************/ 
	if($rec->MedlineCitation[0])
	foreach($rec->MedlineCitation[0]->attributes() as $a => $b) 
	{
		if($a=='Owner') $medlinecitation_owner=(string) $b;
		if($a=='Status') $medlinecitation_status=(string) $b;
	}	
	$source_id = (string) $rec->MedlineCitation->PMID;
	$creation_year= (string) $rec->MedlineCitation->DateCreated->Year;
	$creation_month= (string) $rec->MedlineCitation->DateCreated->Month;
	$creation_day= (string) $rec->MedlineCitation->DateCreated->Day;
	if($rec->MedlineCitation->Article[0])
	foreach($rec->MedlineCitation->Article[0]->attributes() as $a => $b) 
	{
		if($a=='PubModel') $article_pubmodel=(string) $b;
	}
	if($rec->MedlineCitation->Article->Journal->ISSN[0])
	foreach($rec->MedlineCitation->Article->Journal->ISSN[0]->attributes() as $a => $b) 
	{
		if($a=='IssnType') $journal_issntype=(string) $b;
	}
	
	$journal_issn=(string) $rec->MedlineCitation->Article->Journal->ISSN ;
	if($rec->MedlineCitation->Article->Journal->JournalIssue[0])
	foreach($rec->MedlineCitation->Article->Journal->JournalIssue[0]->attributes() as $a => $b) 
	{
		if($a=='CitedMedium') $journalissue_citedmedium=(string) $b;
	}
	$journalissue_volume = (string) $rec->MedlineCitation->Article->Journal->JournalIssue->Volume;
	$journalissue_issue =  (string) $rec->MedlineCitation->Article->Journal->JournalIssue->Issue;
	$journalissue_pubdate_medlinedate =  (string) $rec->MedlineCitation->Article->Journal->JournalIssue->PubDate->MedlineDate;
	$journal_title='';
	if($rec->MedlineCitation->Article->Journal->Title)
	{	
		foreach($rec->MedlineCitation->Article->Journal->Title as $abt)
		{
			$journal_title.= (!empty($journal_title) ? '`':'') . (string) $abt;
		}
	}
		
	$journal_isoabbreviation= (string) $rec->MedlineCitation->Article->Journal->ISOAbbreviation;
		$article_title='';
	if($rec->MedlineCitation->Article->ArticleTitle)
	{
		foreach($rec->MedlineCitation->Article->ArticleTitle as $abt)
		{
			$article_title.= (!empty($article_title) ? '`':'') . (string) $abt;
		}
	}

	$medline_pagination= (string) $rec->MedlineCitation->Article->Pagination->MedlinePgn;
	$abstract_text='';
	if($rec->MedlineCitation->Article->Abstract->AbstractText)
	{
		foreach($rec->MedlineCitation->Article->Abstract->AbstractText as $abt)
		{
			$abstract_text.= (!empty($abstract_text) ? '`':'') . (string) $abt;
		}
	}
	if($rec->MedlineCitation->Article->AuthorList[0])
	foreach($rec->MedlineCitation->Article->AuthorList[0]->attributes() as $a => $b) 
	{
		if($a=='CompleteYN') $authorlist_complete=(string) $b;
	}
	if($rec->MedlineCitation->Article->AuthorList->Author[0])
	foreach($rec->MedlineCitation->Article->AuthorList->Author[0]->attributes() as $a => $b) 
	{
		if($a=='ValidYN') $author_valid=(string) $b;
	}	
	
	$author_lastname= (string) $rec->MedlineCitation->Article->AuthorList->Author->LastName;
	$author_forename= (string) $rec->MedlineCitation->Article->AuthorList->Author->ForeName;
	$author_initials= (string) $rec->MedlineCitation->Article->AuthorList->Author->Initials;
	//$author_affiliation= (string) $rec->MedlineCitation->Article->AuthorList->Author->Affiliation;
	$author_affiliation='';
	foreach($rec->MedlineCitation->Article->AuthorList as $a => $b) 
	{	
		foreach($b->Author as $c => $d) 
		{
			$staf = (string) $d->Affiliation;
			if (stripos( $author_affiliation,$staf) === false)
			{
				if(!empty($author_affiliation)) 
				{
					$author_affiliation .= '`'.$staf;
				}
				else 
				{
					$author_affiliation .= $staf;
				}
			}
			else continue;

		}
	}
	
	$Dmesh_terms='';
	foreach($rec->MedlineCitation->MeshHeadingList->MeshHeading as $a => $b) 
	{	
		$mterm = (string) $b->DescriptorName;
		if (stripos( $Dmesh_terms,$mterm) === false)
		{
			if(!empty($Dmesh_terms)) 
			{
				if(!empty($mterm)) $Dmesh_terms .= '`'.$mterm;
			}
			else 
			{
				if(!empty($mterm)) $Dmesh_terms .= $mterm;
			}
		}
		foreach($b->QualifierName as $c => $d) 
		{
			$mterm = (string) $d;
			if (stripos( $Dmesh_terms,$mterm) === false)
			{
				if(!empty($Dmesh_terms)) 
				{
					if(!empty($mterm)) $Dmesh_terms .= '`'.$mterm;
				}
				else 
				{
					if(!empty($mterm)) $Dmesh_terms .= $mterm;
				}
			}
			else continue;
		}
	}
	
	$language= (string) $rec->MedlineCitation->Article->Language;
	$publicationtype= (string) $rec->MedlineCitation->Article->PublicationTypeList->PublicationType;
	$medlinejournal_country= (string) $rec->MedlineCitation->MedlineJournalInfo->Country;
	$medlinejournal_ta= (string) $rec->MedlineCitation->MedlineJournalInfo->MedlineTA;
	$medlinejournal_nlmuniqueid= (string) $rec->MedlineCitation->MedlineJournalInfo->NlmUniqueID;
	$medlinejournal_issn_linking= (string) $rec->MedlineCitation->MedlineJournalInfo->ISSNLinking;
	$citationsubset= (string) $rec->MedlineCitation->CitationSubset;
	if($rec->PubmedData->History->PubMedPubDate[0])
	foreach($rec->PubmedData->History->PubMedPubDate[0]->attributes() as $a => $b) 
	{
		if($a=='PubStatus') $pubmeddata_date_pubstatus=(string) $b;
	}
	$pubmeddata_date_year=$rec->PubmedData->History->PubMedPubDate->Year ;
	$pubmeddata_date_month=$rec->PubmedData->History->PubMedPubDate->Month ;
	$pubmeddata_date_day=$rec->PubmedData->History->PubMedPubDate->Day ;
	$pubmeddata_date_hour=$rec->PubmedData->History->PubMedPubDate->Hour ;
	$pubmeddata_date_minute= $rec->PubmedData->History->PubMedPubDate->Minute;
	$publication_status= $rec->PubmedData->PublicationStatus;
	if($rec->PubmedData->ArticleIdList->ArticleId[0])
	foreach($rec->PubmedData->ArticleIdList->ArticleId[0]->attributes() as $a => $b) 
	{
		if($a=='IdType') $articleid_type=(string) $b;
	}
	
	$articleid=$rec->PubmedData->ArticleIdList->ArticleId ;
	
	/******************
	pr(__line__.' = '.$medlinecitation_owner);
	pr(__line__.' = '.$medlinecitation_status);
	pr(__line__.' = '.$source_id);
	pr(__line__.' = '.$creation_year);
	pr(__line__.' = '.$creation_month);
	pr(__line__.' = '.$creation_day);
	pr(__line__.' = '.$article_pubmodel);
	pr(__line__.' = '.$journal_issntype);
	pr(__line__.' = '.$journal_issn);
	pr(__line__.' = '.$journalissue_citedmedium);
	pr(__line__.' = '.$journalissue_volume); 
	pr(__line__.' = '.$journalissue_issue); 
	pr(__line__.' *****= '.$journalissue_pubdate_medlinedate);
	pr(__line__.' = '.$journal_title);
	pr(__line__.' = '.$journal_isoabbreviation);
	pr(__line__.' = '.$article_title);
	pr(__line__.' = '.$medline_pagination);
	pr(__line__.' = '.$abstract_text);
	pr(__line__.' = '.$authorlist_complete);
	pr(__line__.' = '.$author_valid);
	pr(__line__.' ***= '.$author_lastname);
	pr(__line__.' ***= '.$author_forename);
	pr(__line__.' = '.$author_initials);
	pr(__line__.' *****= '.$author_affiliation);
	pr(__line__.' = '.$language);
	pr(__line__.' = '.$publicationtype);
	pr(__line__.' = '.$medlinejournal_country);
	pr(__line__.' = '.$medlinejournal_ta);
	pr(__line__.' = '.$medlinejournal_nlmuniqueid);
	pr(__line__.' = '.$medlinejournal_issn_linking);
	pr(__line__.' = '.$citationsubset);
	pr(__line__.' = '.$pubmeddata_date_pubstatus);
	pr(__line__.' = '.$pubmeddata_date_year);
	pr(__line__.' = '.$pubmeddata_date_month);
	pr(__line__.' = '.$pubmeddata_date_day);
	pr(__line__.' = '.$pubmeddata_date_hour);
	pr(__line__.' = '.$pubmeddata_date_minute);
	pr(__line__.' = '.$publication_status);
	pr(__line__.' = '.$articleid_type);
	pr(__line__.' = '.$articleid);
	return;
	/*****************/
	
	$query = 'SELECT `pm_id` FROM  pubmed_abstracts where `source_id` =  ' . $source_id . '  LIMIT 1';
	
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query.  Query = :'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$res = mysql_fetch_assoc($res);
	$exists = $res !== false;

	$oldrecord=$exists;
	$pm_id = NULL;
	if($exists)
	{
		/* update values */
		$pm_id = $res['pm_id'];
		$query='UPDATE pubmed_abstracts SET
		`medlinecitation_owner`=	'."'".mysql_real_escape_string($medlinecitation_owner)."'".',
		`medlinecitation_status`=	'."'".mysql_real_escape_string($medlinecitation_status)."'".',
		`creation_year`=	'."'".mysql_real_escape_string($creation_year)."'".',
		`creation_month`=	'."'".mysql_real_escape_string($creation_month)."'".',
		`creation_day`=	'."'".mysql_real_escape_string($creation_day)."'".',
		`article_pubmodel`=	'."'".mysql_real_escape_string($article_pubmodel)."'".',
		`journal_issntype`=	'."'".mysql_real_escape_string($journal_issntype)."'".',
		`journal_issn`=	'."'".mysql_real_escape_string($journal_issn)."'".',
		`journalissue_citedmedium`=	'."'".mysql_real_escape_string($journalissue_citedmedium)."'".',
		`journalissue_volume`=	'."'".mysql_real_escape_string($journalissue_volume)."'".',
		`journalissue_issue`=	'."'".mysql_real_escape_string($journalissue_issue)."'".',
		`journalissue_pubdate_medlinedate`=	'."'".mysql_real_escape_string($journalissue_pubdate_medlinedate)."'".',
		`journal_title`=	'."'".mysql_real_escape_string($journal_title)."'".',
		`journal_isoabbreviation`=	'."'".mysql_real_escape_string($journal_isoabbreviation)."'".',
		`article_title`=	'."'".mysql_real_escape_string($article_title)."'".',
		`medline_pagination`=	'."'".mysql_real_escape_string($medline_pagination)."'".',
		`abstract_text`=	'."'".mysql_real_escape_string($abstract_text)."'".',
		`authorlist_complete`=	'."'".mysql_real_escape_string($authorlist_complete)."'".',
		`author_valid`=	'."'".mysql_real_escape_string($author_valid)."'".',
		`author_lastname`=	'."'".mysql_real_escape_string($author_lastname)."'".',
		`author_forename`=	'."'".mysql_real_escape_string($author_forename)."'".',
		`author_initials`=	'."'".mysql_real_escape_string($author_initials)."'".',
		`author_affiliation`=	'."'".mysql_real_escape_string($author_affiliation)."'".',
		`mesh_terms`=	'."'".mysql_real_escape_string($Dmesh_terms)."'".',
		`language`=	'."'".mysql_real_escape_string($language)."'".',
		`publicationtype`=	'."'".mysql_real_escape_string($publicationtype)."'".',
		`medlinejournal_country`=	'."'".mysql_real_escape_string($medlinejournal_country)."'".',
		`medlinejournal_ta`=	'."'".mysql_real_escape_string($medlinejournal_ta)."'".',
		`medlinejournal_nlmuniqueid`=	'."'".mysql_real_escape_string($medlinejournal_nlmuniqueid)."'".',
		`medlinejournal_issn_linking`=	'."'".mysql_real_escape_string($medlinejournal_issn_linking)."'".',
		`citationsubset`=	'."'".mysql_real_escape_string($citationsubset)."'".',
		`pubmeddata_date_pubstatus`=	'."'".mysql_real_escape_string($pubmeddata_date_pubstatus)."'".',
		`pubmeddata_date_year`=	'."'".mysql_real_escape_string($pubmeddata_date_year)."'".',
		`pubmeddata_date_month`=	'."'".mysql_real_escape_string($pubmeddata_date_month)."'".',
		`pubmeddata_date_day`=	'."'".mysql_real_escape_string($pubmeddata_date_day)."'".',
		`pubmeddata_date_hour`=	'."'".mysql_real_escape_string($pubmeddata_date_hour)."'".',
		`pubmeddata_date_minute`=	'."'".mysql_real_escape_string($pubmeddata_date_minute)."'".',
		`publication_status`=	'."'".mysql_real_escape_string($publication_status)."'".',
		`articleid_type`=	'."'".mysql_real_escape_string($articleid_type)."'".',
		`articleid`=	'."'".mysql_real_escape_string($articleid)."'
		where 
		`source_id`=	"."'".mysql_real_escape_string($source_id)."'";


	}
	else
	{
		/*     insert values */
		$query ='INSERT INTO `pubmed_abstracts`
		(`medlinecitation_owner`,
		`medlinecitation_status`,
		`source_id`,
		`creation_year`,
		`creation_month`,
		`creation_day`,
		`article_pubmodel`,
		`journal_issntype`,
		`journal_issn`,
		`journalissue_citedmedium`,
		`journalissue_volume`,
		`journalissue_issue`,
		`journalissue_pubdate_medlinedate`,
		`journal_title`,
		`journal_isoabbreviation`,
		`article_title`,
		`medline_pagination`,
		`abstract_text`,
		`authorlist_complete`,
		`author_valid`,
		`author_lastname`,
		`author_forename`,
		`author_initials`,
		`author_affiliation`,
		`language`,
		`publicationtype`,
		`medlinejournal_country`,
		`medlinejournal_ta`,
		`medlinejournal_nlmuniqueid`,
		`medlinejournal_issn_linking`,
		`citationsubset`,
		`pubmeddata_date_pubstatus`,
		`pubmeddata_date_year`,
		`pubmeddata_date_month`,
		`pubmeddata_date_day`,
		`pubmeddata_date_hour`,
		`pubmeddata_date_minute`,
		`publication_status`,
		`articleid_type`,
		`articleid`,
		`mesh_terms`)
		VALUES
		('.

		"'".mysql_real_escape_string($medlinecitation_owner)."',".
		"'".mysql_real_escape_string($medlinecitation_status)."',".
		"'".mysql_real_escape_string($source_id)."',".
		"'".mysql_real_escape_string($creation_year)."',".
		"'".mysql_real_escape_string($creation_month)."',".
		"'".mysql_real_escape_string($creation_day)."',".
		"'".mysql_real_escape_string($article_pubmodel)."',".
		"'".mysql_real_escape_string($journal_issntype)."',".
		"'".mysql_real_escape_string($journal_issn)."',".
		"'".mysql_real_escape_string($journalissue_citedmedium)."',".
		"'".mysql_real_escape_string($journalissue_volume)."',".
		"'".mysql_real_escape_string($journalissue_issue)."',".
		"'".mysql_real_escape_string($journalissue_pubdate_medlinedate)."',".
		"'".mysql_real_escape_string($journal_title)."',".
		"'".mysql_real_escape_string($journal_isoabbreviation)."',".
		"'".mysql_real_escape_string($article_title)."',".
		"'".mysql_real_escape_string($medline_pagination)."',".
		"'".mysql_real_escape_string($abstract_text)."',".
		"'".mysql_real_escape_string($authorlist_complete)."',".
		"'".mysql_real_escape_string($author_valid)."',".
		"'".mysql_real_escape_string($author_lastname)."',".
		"'".mysql_real_escape_string($author_forename)."',".
		"'".mysql_real_escape_string($author_initials)."',".
		"'".mysql_real_escape_string($author_affiliation)."',".
		"'".mysql_real_escape_string($language)."',".
		"'".mysql_real_escape_string($publicationtype)."',".
		"'".mysql_real_escape_string($medlinejournal_country)."',".
		"'".mysql_real_escape_string($medlinejournal_ta)."',".
		"'".mysql_real_escape_string($medlinejournal_nlmuniqueid)."',".
		"'".mysql_real_escape_string($medlinejournal_issn_linking)."',".
		"'".mysql_real_escape_string($citationsubset)."',".
		"'".mysql_real_escape_string($pubmeddata_date_pubstatus)."',".
		"'".mysql_real_escape_string($pubmeddata_date_year)."',".
		"'".mysql_real_escape_string($pubmeddata_date_month)."',".
		"'".mysql_real_escape_string($pubmeddata_date_day)."',".
		"'".mysql_real_escape_string($pubmeddata_date_hour)."',".
		"'".mysql_real_escape_string($pubmeddata_date_minute)."',".
		"'".mysql_real_escape_string($publication_status)."',".
		"'".mysql_real_escape_string($articleid_type)."',".
		"'".mysql_real_escape_string($articleid)."',".
		"'".mysql_real_escape_string($Dmesh_terms)."'
		)";
	}
	
	if(!mysql_query($query))
	{
		$log='There seems to be a problem with the SQL  Query:'.$query.' Error:' . mysql_error();
		$logger->fatal($log);
		mysql_query('ROLLBACK');
		echo $log;
		exit;
	}

}

?>
