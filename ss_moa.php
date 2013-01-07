<?php
require_once('include.util.php');
require_once("api/sphinxapi.php");

//function to SEARCH MOAs using Sphinx.
function find_moa($searchstring)
{
	if ( !isset($searchstring) )
	{
		return false;
	}
	$offset=0;
	$cl = new SphinxClient ();
	$sql = "";

	$host = "localhost";
	$port = 9312;
	$index = "moas"; // "*" for all
	$groupby = "";
	$groupsort = "@name";
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
	$cl->SetRankingMode ( $ranker );
	if(strlen($searchstring)>=2) $searchstring = '*'.$searchstring.'*';
	$res = $cl->Query ( $searchstring, $index );

	if ( $res===false )
	{
		return false;
	} 
	else
	{

		if ( is_array($res["matches"]) )
		{
			$n = 1;
			$moa_ids=array();
			foreach ( $res["matches"] as $docinfo )
			{
				if($docinfo[weight]>1900)
				{
					$moa_ids[] = $docinfo[id];
				}
				$n++;
			}
		}
	}
	return $moa_ids;
}

?>