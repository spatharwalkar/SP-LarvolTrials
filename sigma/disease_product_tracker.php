<?php
require_once('db.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','60');	//1min

function DataGenerator($id, $TrackerType, $page=1, $dwcount='')
{
	global $db;
	global $now;
	
	$rows = array();
	$productIds = array();
	
	//IMP DATA
	$data_matrix=array();
	
	$max_count = 0;

	$productIds = GetProductsFromDisease($id);
	
	foreach($productIds as $key=> $product)
	{
		
		$result =  mysql_fetch_assoc(mysql_query("SELECT name FROM `entities` WHERE `class`='Product' and id = '" . $product . "' "));
		$rows[$key] = $result['name'];
				
	}
	
	
	foreach($rows as $row => $rval)
	{
		/// Fill up all data in Data Matrix only, so we can sort all data at one place
		
		$data_matrix[$row]['productName'] = $rval;
		$data_matrix[$row]['product_CompanyName'] = $rowsCompanyName[$row];
		
		
		if(isset($productIds[$row]) && $productIds[$row] != NULL)
		{
			///// Initialize data
			$data_matrix[$row]['active']=0;
				
			$data_matrix[$row]['total']=0;
			
			$data_matrix[$row]['indlead']=0;
			
			$data_matrix[$row]['owner_sponsored']=0;
			
			$data_matrix[$row]['total_phase_na']=0;
			$data_matrix[$row]['active_phase_na']=0;
			$data_matrix[$row]['indlead_phase_na']=0;
			$data_matrix[$row]['total_phase_0']=0;
			$data_matrix[$row]['active_phase_0']=0;
			$data_matrix[$row]['indlead_phase_0']=0;
			$data_matrix[$row]['total_phase_1']=0;
			$data_matrix[$row]['active_phase_1']=0;
			$data_matrix[$row]['indlead_phase_1']=0;
			$data_matrix[$row]['total_phase_2']=0;
			$data_matrix[$row]['active_phase_2']=0;
			$data_matrix[$row]['indlead_phase_2']=0;
			$data_matrix[$row]['total_phase_3']=0;
			$data_matrix[$row]['active_phase_3']=0;
			$data_matrix[$row]['indlead_phase_3']=0;
			$data_matrix[$row]['total_phase_4']=0;
			$data_matrix[$row]['active_phase_4']=0;
			$data_matrix[$row]['indlead_phase_4']=0;
			
			$data_matrix[$row]['owner_sponsored_phase_na']=0;
			$data_matrix[$row]['owner_sponsored_phase_0']=0;
			$data_matrix[$row]['owner_sponsored_phase_1']=0;
			$data_matrix[$row]['owner_sponsored_phase_2']=0;
			$data_matrix[$row]['owner_sponsored_phase_3']=0;
			$data_matrix[$row]['owner_sponsored_phase_4']=0;
			
			//// To avoid multiple queries to database, we are quering only one time and retrieveing all data and seprating each type
			if($TrackerType == 'DPT')
			{
				$phase_query = "SELECT DISTINCT dt.`larvol_id`, dt.`is_active`, dt.`phase`, dt.`institution_type`,et.relation_type as relation_type  FROM data_trials dt JOIN entity_trials et ON (dt.`larvol_id` = et.`trial`) JOIN entity_trials et2 ON (dt.`larvol_id` = et2.`trial`) WHERE et.`entity`='" . $productIds[$row] ."' AND et2.`entity`='" .$id."'";
	
			}
			else
			{
				$phase_query = "SELECT dt.`is_active`, dt.`phase`, dt.`institution_type`,et.relation_type as relation_type  FROM data_trials dt JOIN entity_trials et ON (dt.`larvol_id` = et.`trial`) WHERE et.`entity`='" . $productIds[$row] ."'";
			}
			
			$phase_res = mysql_query($phase_query) or die($phase_query.' - '.mysql_error());
			while($phase_row=mysql_fetch_array($phase_res))
			{

				$data_matrix[$row]['total']++;
				if($phase_row['is_active'])
				{
					$data_matrix[$row]['active']++;
					if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead']++;
					if($phase_row['relation_type'] == 'ownersponsored')
						$data_matrix[$row]['owner_sponsored']++;
				}
					
				if($phase_row['phase'] == 'N/A' || $phase_row['phase'] == '' || $phase_row['phase'] === NULL)
				{
					
					$data_matrix[$row]['total_phase_na']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_na']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
							$data_matrix[$row]['indlead_phase_na']++;
						if($phase_row['relation_type'] == 'ownersponsored')
							$data_matrix[$row]['owner_sponsored_phase_na']++;
					}
				}
				else if($phase_row['phase'] == '0')
				{
					$data_matrix[$row]['total_phase_0']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_0']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
							$data_matrix[$row]['indlead_phase_0']++;
						if($phase_row['relation_type'] == 'ownersponsored')
							$data_matrix[$row]['owner_sponsored_phase_0']++;
					}
				}
				else if($phase_row['phase'] == '1' || $phase_row['phase'] == '0/1' || $phase_row['phase'] == '1a' 
				|| $phase_row['phase'] == '1b' || $phase_row['phase'] == '1a/1b' || $phase_row['phase'] == '1c')
				{
					
					$data_matrix[$row]['total_phase_1']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_1']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
							$data_matrix[$row]['indlead_phase_1']++;
						if($phase_row['relation_type'] == 'ownersponsored')
							$data_matrix[$row]['owner_sponsored_phase_1']++;
					}
				}
				else if($phase_row['phase'] == '2' || $phase_row['phase'] == '1/2' || $phase_row['phase'] == '1b/2' 
				|| $phase_row['phase'] == '1b/2a' || $phase_row['phase'] == '2a' || $phase_row['phase'] == '2a/2b' 
				|| $phase_row['phase'] == '2a/b' || $phase_row['phase'] == '2b' || $phase_row['phase'] == 2)
				{
					
					
					$data_matrix[$row]['total_phase_2']++;
					
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_2']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
							$data_matrix[$row]['indlead_phase_2']++;
						if($phase_row['relation_type'] == 'ownersponsored')
							$data_matrix[$row]['owner_sponsored_phase_2']++;
					}
				}
				else if($phase_row['phase'] == '3' || $phase_row['phase'] == '2/3' || $phase_row['phase'] == '2b/3' 
				|| $phase_row['phase'] == '3a' || $phase_row['phase'] == '3b')
				{
					
					$data_matrix[$row]['total_phase_3']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_3']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_3']++;
						if($phase_row['relation_type'] == 'ownersponsored')
						$data_matrix[$row]['owner_sponsored_phase_3']++;
					}
				}
				else if($phase_row['phase'] == '4' || $phase_row['phase'] == '3/4' || $phase_row['phase'] == '3b/4')
				{
					
					$data_matrix[$row]['total_phase_4']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_4']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_4']++;
						if($phase_row['relation_type'] == 'ownersponsored')
						$data_matrix[$row]['owner_sponsored_phase_4']++;
					}	
				}
			}	//// End of while
			if($data_matrix[$row]['total'] > $max_count)
			$max_count = $data_matrix[$row]['total'];
		}
		else
		{
			$data_matrix[$row]['active']=0;
			$data_matrix[$row]['total']=0;
			$data_matrix[$row]['indlead']=0;
			$data_matrix[$row]['owner_sponsored']=0;
			
			$data_matrix[$row]['total_phase_na']=0;
			$data_matrix[$row]['active_phase_na']=0;
			$data_matrix[$row]['indlead_phase_na']=0;
			$data_matrix[$row]['total_phase_0']=0;
			$data_matrix[$row]['active_phase_0']=0;
			$data_matrix[$row]['indlead_phase_0']=0;
			$data_matrix[$row]['total_phase_1']=0;
			$data_matrix[$row]['active_phase_1']=0;
			$data_matrix[$row]['indlead_phase_1']=0;
			$data_matrix[$row]['total_phase_2']=0;
			$data_matrix[$row]['active_phase_2']=0;
			$data_matrix[$row]['indlead_phase_2']=0;
			$data_matrix[$row]['total_phase_3']=0;
			$data_matrix[$row]['active_phase_3']=0;
			$data_matrix[$row]['indlead_phase_3']=0;
			$data_matrix[$row]['total_phase_4']=0;
			$data_matrix[$row]['active_phase_4']=0;
			$data_matrix[$row]['indlead_phase_4']=0;
			
			$data_matrix[$row]['owner_sponsored_phase_na']=0;
			$data_matrix[$row]['owner_sponsored_phase_0']=0;
			$data_matrix[$row]['owner_sponsored_phase_1']=0;
			$data_matrix[$row]['owner_sponsored_phase_2']=0;
			$data_matrix[$row]['owner_sponsored_phase_3']=0;
			$data_matrix[$row]['owner_sponsored_phase_4']=0;
			
			if($data_matrix[$row]['total'] < $max_count)
			$max_count = $data_matrix[$row]['total'];
		}
	}
	$data_matrix = sortTwoDimensionArrayByKey2($data_matrix, $dwcount);
	
	$TotalRecords = count($data_matrix);
		
	return  $TotalRecords;
}
///End of Process Report Tracker
//// End of Data Generator	

function sortTwoDimensionArrayByKey2($arr, $arrKey, $sortOrder=SORT_DESC)
{
	$key_arr = array();
	$res = array();

	if(is_array($arr) && count($arr) > 0)
	{
		foreach ($arr as $key => $row)
		{
			if($row[$arrKey] > 0)
			{
				$key_arr[$key] = $row[$arrKey];
				$res[$key] = $arr[$key];
			}
		}
		array_multisort($key_arr, $sortOrder, $res);
	}
	return $res;
}

//Get producrs froms disease
function GetProductsFromDisease($DiseaseID)
{
	global $db;
	global $now;
	$Products = array();
	$query = "SELECT DISTINCT e.`id` FROM `entities` e JOIN `entity_relations` er ON(e.`id` = er.`child`) WHERE e.`class`='Product' AND er.`parent`='" . mysql_real_escape_string($DiseaseID) . "' AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
	$res = mysql_query($query) or die('Bad SQL query getting products from Disease id in PT '.$query);

	if($res)
	{
		while($row = mysql_fetch_array($res))
		{
			$Products[] = $row['id'];
		}
	}
	return array_filter(array_unique($Products));
}

?>
