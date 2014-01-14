<?php
require_once("api/sphinxapi.php");
require_once('include.util.php');
//ini_set('error_reporting', E_ALL ^ E_NOTICE ^ E_WARNING );
function find_entity($q)
{
	if ( !isset($q) )
	{
		return false;
	}
	$offset=0;
	$cl = new SphinxClient ();
	$sql = "";

	$host = "localhost";
	$port = 9312;
	$index = "entities"; 
	$groupby = "";
	$groupsort = "@group desc";
	$filter = "group_id";
	$filtervals = array();
	$distinct = "";
	$sortby = "";
	$sortexpr = "";

	$limit = 100;
	$ranker = SPH_RANK_PROXIMITY_BM25;
	$select = "";
	$mode = SPH_MATCH_EXTENDED2;

	$cl->SetServer ( $host, $port );
	$cl->SetConnectTimeout ( 2 );
	$cl->SetArrayResult ( true );
	$cl->SetWeights ( array ( 100, 1 ) );
	$cl->SetMatchMode ( $mode );
	if ( count($filtervals) )	$cl->SetFilter ( $filter, $filtervals );
	if ( $groupby )				$cl->SetGroupBy ( $groupby, SPH_GROUPBY_ATTR, $groupsort );
	if ( $sortby )				$cl->SetSortMode ( SPH_SORT_EXTENDED, $sortby );
	if ( $sortexpr )			$cl->SetSortMode ( SPH_SORT_EXPR, $sortexpr );
	if ( $distinct )			$cl->SetGroupDistinct ( $distinct );
	if ( $select )				$cl->SetSelect ( $select );
	if ( $limit )				$cl->SetLimits ( $offset, $limit, ( $limit>1000 ) ? $limit : 1000 );
	$cl->SetSortMode(SPH_SORT_EXTENDED, 'class ASC,@relevance DESC');
	
	$cl->SetRankingMode ( $ranker );
	/*
	$res = $cl->Query ( '"^'.$q.'$"', $index );
	if ( $res!==false )
	{
		if ( is_array($res["matches"]) )
		{
			$entity_ids1=array();
			foreach ( $res["matches"] as $docinfo )
			{
				if($docinfo[weight]>1900)
				{
					$entity_ids1[] = $docinfo[id];
				}
			}
		}
	}
	*/
		$orig_q=$q;
		$pos = strpos($q, "-");
		if ($pos !== false) 
		{
			$q2=str_replace("-", "", $q);
			$q = '"'.$q.'"';
			if(strlen($q2)>=2) $q2 = '*'.$q2.'*';
		}
		if(strlen($q)>=2) $q = '*'.$q.'*';
		$res = $cl->Query ( $q, $index );
		if($q2) $res1 = $cl->Query ( $q2, $index );
//	pr($res);
		if ( $res===false )
		{
	//		print "Query failed: " . $cl->GetLastError() . ".\n";
			return false;
		} 
		else
		{

			if ( is_array($res["matches"]) )
			{
				$n = 1;
				if(!isset($entity_ids)) $entity_ids=array();
				foreach ( $res["matches"] as $docinfo )
				{
					if($docinfo[weight]>1900)
					{
						$entity_ids[] = $docinfo[id];
					}
					$n++;
					//pr($res["class"]);
				}
			}
			if ( is_array($res1["matches"]) )
			{
				foreach ( $res1["matches"] as $docinfo )
				{
					if($docinfo[weight]>1900)
					{
						$entity_ids[] = $docinfo[id];
					}
					$n++;
					//pr($res["class"]);
				}
				$entity_ids=array_unique($entity_ids);
			}
		}
	if($entity_ids && count($entity_ids)>0)
	{
		return $entity_ids;
	}
	else
	{
		$q=$orig_q;
		//  make it capable of searching for limited words 
		preg_match_all('# #', $q, $matches, PREG_OFFSET_CAPTURE);
		$words = array();
		foreach($matches[0] as $match)
		{
		   $words[] = $match[1];
		}
		if(count($words)>1)
		{
			$q2=substr($q,0,$words[1]);
			/******************/
			$pos = strpos($q2, "-");
			if ($pos !== false) 
			{
				$q2 = '"'.$q2.'"';
			}
			if(strlen($q2)>=2) $q2 = '*'.$q2.'*';
			$res = $cl->Query ( $q2, $index );
			if ( $res===false )
			{
				return false;
			} 
			else
			{
				if ( is_array($res["matches"]) )
				{
					$n = 1;
					if(!$entity_ids || !count($entity_ids)>0) $entity_ids=array();
					foreach ( $res["matches"] as $docinfo )
					{
						if($docinfo[weight]>1900)
						{
							$entity_ids[] = $docinfo[id];
						}
						$n++;
					}
					$entity_ids=array_unique($entity_ids);
				}
			}
			/******************/
		}
		if($entity_ids && count($entity_ids)>0)
		{
			return $entity_ids;
		}
		elseif (count($words)>0)
		{
			$q=$orig_q;
			$q1=substr($q,0,$words[0]);
			/******************/
			$pos = strpos($q1, "-");
			if ($pos !== false) 
			{
				$q1 = '"'.$q1.'"';
			}
			if(strlen($q1)>=2) $q1 = '*'.$q1.'*';
			$res = $cl->Query ( $q1, $index );
			if ( $res===false )
			{
				return false;
			} 
			else
			{
				if ( is_array($res["matches"]) )
				{
					$n = 1;
					if(!$entity_ids || !count($entity_ids)>0) $entity_ids=array();
					foreach ( $res["matches"] as $docinfo )
					{
						if($docinfo[weight]>1900)
						{
							$entity_ids[] = $docinfo[id];
						}
						$n++;
					}
				$entity_ids=array_unique($entity_ids);
				}
			}
			/******************/
		}
		return $entity_ids;
	}
	//

}

?>