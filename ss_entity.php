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
	$res = $cl->Query ( '"^'.$q.'$"', $index );
	if ( $res!==false )
	{
		if ( is_array($res["matches"]) )
		{
			$entity_ids=array();
			foreach ( $res["matches"] as $docinfo )
			{
				if($docinfo[weight]>1900)
				{
					$entity_ids[] = $docinfo[id];
				}
			}
		}
	}
	
	if(strlen($q)>=2) $q = '*'.$q.'*';
	$res = $cl->Query ( $q, $index );

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
			}
			$entity_ids=array_unique($entity_ids);
		}
	}
	return $entity_ids;
}

?>