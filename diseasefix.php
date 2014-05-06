<?php
/*
* Story: 67949866
* Fix for Certain disease having 0 in company tab, even though attached products have attached companies
*/

ini_set('memory_limit','-1');
ini_set('max_execution_time','3600');

require_once('db.php');
require_once('calculate_hm_cells.php');

$Companies = array();
$parameters=array();

$query="SELECT id FROM `entities` WHERE `class`='disease' AND (`is_active` <> '0' OR `is_active` IS NULL)";
$res = mysql_query($query) or die('Bad SQL query getting disease id '.mysql_error()."<br />".$query);
if(mysql_num_rows($res) > 0)
{
	while($row = mysql_fetch_assoc($res))
	{
		$flag=0;
		$disease_id=$row["id"];
		
		$query="SELECT DISTINCT e.`id` FROM `entities` e JOIN `entity_relations` er ON(er.`child` = e.`id`) JOIN `entities` e2 ON(e2.`id` = er.`parent`) JOIN `entity_relations` er2 ON(er2.`child` = e2.`id`) WHERE e.`class` = 'Institution' AND e.`category`='Industry' AND e2.`class` = 'Product' AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL) AND er2.`parent`='" . $disease_id . "'";
		$ress = mysql_query($query) or die('Bad SQL query  . '.$query);
		if(mysql_num_rows($ress) > 0)
		{
			while($rows = mysql_fetch_array($ress))
			{
				$Companies[] = $rows['id'];
			}
			
			$CompanyIds = array_filter(array_unique($Companies));			

			$query="SELECT e2.`id` FROM `rpt_masterhm_cells` rpt JOIN `entities` e ON((rpt.`entity1`=e.`id` AND e.`class`='Product') OR (rpt.`entity2`=e.`id` AND e.`class`='Product')) JOIN `entity_relations` er ON(e.`id` = er.`parent`) JOIN `entities` e2 ON(e2.`id` = er.`child`) WHERE (rpt.`count_total` > 0) AND (rpt.`entity1` = '". $disease_id ."' OR rpt.`entity2` = '". $disease_id ."') AND e2.`id` IN ('" . implode("','",$CompanyIds) . "') AND e2.`class`='Institution' AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
			$ress = mysql_query($query) or die('Bad SQL query  . '.$query);
			if(mysql_num_rows($ress) == 0)
			{
				if(CheckProductsFromDisease($disease_id) === true)
				{	
					echo '<br>Fixing for disease id: '.$disease_id.'<br>';
					
					$parameters['entity1']=$disease_id; // for product
					//RECALCULATE HEATMAP CELLS
					echo '<br>RECALCULATING HEATMAP CELLS<br>';
					calc_cells($parameters);
					
					//TAB COUNT UPDATE
					echo '<br>UPDATING TAB COUNT<br>';
					updateDiseasesTabCount($disease_id);
					
					echo '<br>Done<br>';
				}
			}
		}
	}
}
echo '<br>All Done<br>';

function updateDiseasesTabCount($diseaseId) {
		
	global $db;
	global $now;
	
	// diseases company count
	$diseasesCompanyCount = 0;
	
	$sqlDiseaseCompany = "SELECT DISTINCT e.`id` FROM `entities` e 
						JOIN `entity_relations` er ON(er.`child` = e.`id`) 
						JOIN `entities` e2 ON(e2.`id` = er.`parent`) 
						JOIN `entity_relations` er2 ON(er2.`child` = e2.`id`) 
						WHERE e.`class` = 'Institution' 
						AND e.`category`='Industry' 
						AND e2.`class` = 'Product' 
						AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL) 
						AND er2.`parent`='$diseaseId'";
	
	$resDiseaseCompany = mysql_query($sqlDiseaseCompany) or die('Bad SQL query getting companies from diseases'.$sqlDiseaseCompany);
	$diseasesCompanies = array();
	if($resDiseaseCompany)
	{
		while($rowDiseaseCompany = mysql_fetch_array($resDiseaseCompany))
		{
			$diseasesCompanies[] = $rowDiseaseCompany['id'];
		}
	}
	if(count($diseasesCompanies) > 0)
	$diseasesCompanies = implode("','", $diseasesCompanies);
	else
	$diseasesCompanies = '';
	
	$sqlGetCompaniesForDisease = "SELECT e2.`id` FROM `rpt_masterhm_cells` rpt 
								JOIN `entities` e ON((rpt.`entity1`=e.`id` AND e.`class`='Product') OR (rpt.`entity2`=e.`id` AND e.`class`='Product')) 
								JOIN `entity_relations` er ON(e.`id` = er.`parent`) 
								JOIN `entities` e2 ON(e2.`id` = er.`child`) 
								WHERE (rpt.`count_total` > 0) AND (rpt.`entity1` = '$diseaseId' OR rpt.`entity2` = '$diseaseId') 
								AND e2.`id` IN ('$diseasesCompanies') 
								AND e2.`class`='Institution' 
								AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)
								group by e2.`id`";	//SELECTING DISTINCT PHASES SO WE WILL HAVE MIN ROWS TO PROCESS

	$resGetCompaniesForDisease = mysql_query($sqlGetCompaniesForDisease) or die(mysql_error());
	
	$diseasesCompanyCount = mysql_num_rows($resGetCompaniesForDisease);
	
	// diseases company count
	$diseasesProductCount = 0;
	
	$sqlGetProductFromDisease = "SELECT DISTINCT e.`id` FROM `entities` e 
								JOIN `entity_relations` er ON(e.`id` = er.`child`) 
								WHERE e.`class`='Product' 
								AND er.`parent`='$diseaseId' 
								AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
	
	$resGetProductFromDisease = mysql_query($sqlGetProductFromDisease) or die('Bad SQL query getting products from Disease id '.$sqlGetProductFromDisease);
	
	$diseasesProducts = array();
	if($resGetProductFromDisease)
	{
		while($rowGetProductFromDisease = mysql_fetch_array($resGetProductFromDisease))
		{
			$diseasesProducts[] = $rowGetProductFromDisease['id'];
		}
	}
	$diseasesProducts = array_filter(array_unique($diseasesProducts));
	
	if(count($diseasesProducts) > 0)
	$implodeProducts = implode("','", $diseasesProducts);
	else
	$implodeProducts = '';
	
	$sqlGetdiseasesProductsTrials = "SELECT DISTINCT dt.`larvol_id`, et.entity
					FROM data_trials dt 
					JOIN entity_trials et ON (dt.`larvol_id` = et.`trial`) 
					JOIN entity_trials et2 ON (dt.`larvol_id` = et2.`trial`) 
					WHERE et.`entity` IN ('$implodeProducts') 
					AND et2.`entity`='$diseaseId'";
	
	$resGetdiseasesProductsTrials = mysql_query($sqlGetdiseasesProductsTrials) or die($sqlGetdiseasesProductsTrials.'- Bad SQL query');
		
	if($resGetdiseasesProductsTrials)
	{
		while($rowGetdiseasesProductsTrials = mysql_fetch_array($resGetdiseasesProductsTrials)) {
			//$allTrials[$rowGetdiseasesProductsTrials['larvol_id']] = $rowGetdiseasesProductsTrials['larvol_id'];
			$entityTrials[$rowGetdiseasesProductsTrials['entity']] = $rowGetdiseasesProductsTrials['entity'];
		}
	}
	
	//$diseasesTrialCount = count($allTrials);
	$diseasesProductCount = count($entityTrials);
		
	// diseases MOA count
	$diseasesMoaCount = 0;
	
	$Products = array();
	$MOAOrMOACats = array();
	$onlymoas = array();
	$OnlyMOACatIds = array();
	$OnlyMOAIds = array();

	//Get MOA Categoryids from Product id
	$sqlGetMoaForDisease = "SELECT e1.`id` as id, e2.`id` AS moaid FROM `entities` e1 
			JOIN `entity_relations` er1 ON(er1.`parent` = e1.`id`) 
			JOIN `entities` e2 ON (er1.`child` = e2.`id`) 
			JOIN `entity_relations` er2 ON(er2.`child` = e2.`id`) 
			JOIN `entities` e3 ON(e3.`id` = er2.`parent`) 
			JOIN `entity_relations` er3 ON(er3.`child` = e3.`id`) 
			WHERE e1.`class` = 'MOA_Category' 
			AND e1.`name` <> 'Other' 
			AND e2.`class` = 'MOA' 
			AND e3.`class` = 'Product' 
			AND er3.`parent`='$diseaseId' 
			AND (e3.`is_active` <> '0' OR e3.`is_active` IS NULL)";
			
	$resGetMoaForDisease = mysql_query($sqlGetMoaForDisease) or die('Bad SQL query getting MOA Categories from disease '.$sqlGetMoaForDisease);
	
	if($resGetMoaForDisease)
	{
		while($row = mysql_fetch_array($resGetMoaForDisease))
		{
			if(!in_array($row['id'], $MOAOrMOACats))
				$MOAOrMOACats[] = $row['id'];
			if(!in_array($row['moaid'], $onlymoas))
				$onlymoas[] = $row['moaid'];
		}
	}
	$OnlyMOACatIds = $MOAOrMOACats;
	
		
	//Get MOA which dont have related category from product id
	$query = "SELECT DISTINCT e.`id` FROM `entities` e 
			JOIN `entity_relations` er ON (er.`child` = e.`id`) 
			JOIN `entities` e2 ON (e2.`id` = er.`parent`) 
			JOIN `entity_relations` er2 ON(er2.`child` = e2.`id`) 
			WHERE e.`class` = 'MOA' 
			AND e2.`class` = 'Product' 
			AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL) 
			AND er2.`parent`='$diseaseId' ".((count($onlymoas) > 0) ? "AND e.`id` NOT IN (" . implode(',',$onlymoas) . ")" : "");
	$res = mysql_query($query) or die('Bad SQL query getting MOAs from products ids in MT');

	if($res)
	{
		while($row = mysql_fetch_array($res))
		{
			$MOAOrMOACats[] = $row['id'];
			$OnlyMOAIds[] = $row['id'];
		}
	}
	$moaOrMoaCat['all'] = array_filter(array_unique($MOAOrMOACats));
	$moaOrMoaCat['moa'] = array_filter(array_unique($OnlyMOAIds));
	$moaOrMoaCat['moacat'] = array_filter(array_unique($OnlyMOACatIds));
	
	if ($moaOrMoaCat['all'] > 0) {
		
			$sqlGetMoa = "SELECT e2.`id` AS id FROM `rpt_masterhm_cells` rpt JOIN `entities` e ON((rpt.`entity1`=e.`id` AND e.`class`='Product') OR (rpt.`entity2`=e.`id` AND e.`class`='Product')) JOIN `entity_relations` er ON(e.`id` = er.`parent`) JOIN `entities` e2 ON(e2.`id` = er.`child`) WHERE (rpt.`count_total` > 0) AND (rpt.`entity1` = '". $diseaseId ."' OR rpt.`entity2` = '$diseaseId') AND e2.`class`='MOA' AND e2.`id` IN ('".implode("','",$moaOrMoaCat['moa'])."') AND (e.`is_active` <> '0' OR e.`is_active` IS NULL) GROUP BY  e2.`id`";
			
			$resGetMoa = mysql_query($sqlGetMoa) or die(mysql_error());
			
			$diseasesMoaCount = mysql_num_rows($resGetMoa);
			
			$sqlGetMoaCat = "SELECT e3.`id` AS id FROM `rpt_masterhm_cells` rpt JOIN `entities` e ON((rpt.`entity1`=e.`id` AND e.`class`='Product') OR (rpt.`entity2`=e.`id` AND e.`class`='Product')) JOIN `entity_relations` er ON(e.`id` = er.`parent`) JOIN `entities` e2 ON(e2.`id` = er.`child`) JOIN `entity_relations` er2 ON(er2.`child`=e2.`id`) JOIN `entities` e3 ON(e3.`id` = er2.`parent`) WHERE (rpt.`count_total` > 0) AND (rpt.`entity1` = '$diseaseId' OR rpt.`entity2` = '$diseaseId') AND e2.`class`='MOA' AND e3.`id` IN ('".implode("','",$moaOrMoaCat['moacat'])."') AND (e.`is_active` <> '0' OR e.`is_active` IS NULL) GROUP BY e3.`id`";
			
			$resGetMoaCat = mysql_query($sqlGetMoaCat) or die(mysql_error());
			
			$diseasesMoaCount += mysql_num_rows($resGetMoaCat);
			
	}
	
	// diseases investigator count
	$diseasesInvestigatorCount = 0;
	
	$sqlGetInvestigatorForDisease = "SELECT DISTINCT et.entity 
								FROM entity_trials et
								JOIN entities e on (et.entity=e.id and e.class ='Investigator' 
								AND et.trial IN (SELECT trial as t from entity_trials where  entity= '$diseaseId'))";
	$resGetInvestigatorForDisease = mysql_query($sqlGetInvestigatorForDisease) or die(mysql_error());
			
	$diseasesInvestigatorCount = mysql_num_rows($resGetInvestigatorForDisease);
	
	// diseases trial count
	$diseaseTrialsCount = 0;
	
	$sqlGetTrailsforDisease = "SELECT count(Distinct(dt.`larvol_id`)) as trialCount FROM `data_trials` dt 
			JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`)  
			WHERE et.`entity`='$diseaseId'";
	$resGetTrailsforDisease = mysql_query($sqlGetTrailsforDisease) or die('Bad SQL query getting trials count from Disease id in TZ');
	
	if($resGetTrailsforDisease) {
		while($rowGetTrailsforDisease = mysql_fetch_array($resGetTrailsforDisease))
		$diseaseTrialsCount = $rowGetTrailsforDisease['trialCount'];
	}
	
	// diseases news count
	$diseaseNewsCount = 0;
	$sqlGetNewsForDisease = "SELECT count(dt.`larvol_id`) as newsCount FROM `data_trials` dt 
			JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`) 
			JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) 
			WHERE et.`entity`='$diseaseId'";
	$resGetNewsForDisease = mysql_query($sqlGetNewsForDisease) or die('Bad SQL query getting news for disease'.$sqlGetNewsForDisease);

	if($resGetNewsForDisease) {
		while($rowGetNewsForDisease = mysql_fetch_array($resGetNewsForDisease))
			$diseaseNewsCount = $rowGetNewsForDisease['newsCount'];
	}
	if ($diseaseNewsCount > 50) $diseaseNewsCount = 50;
	
	// to check if tab for this enetity is already there in the tabs table
	$sqlCheckTabsTable = "SELECT entity_id FROM tabs 
					WHERE entity_id = '$diseaseId'
					AND table_name = 'entities'";
	
	$resCheckTabsTable =  mysql_query($sqlCheckTabsTable);
	
	if(mysql_num_rows($resCheckTabsTable) > 0) { // update tab count for this entity in tabs table 
		
		
		$sqlUpdateTabsTable = "UPDATE tabs set entity_id = '$diseaseId',
							table_name = 'entities',
							companies = '$diseasesCompanyCount',
							products = '$diseasesProductCount',
							moas = '$diseasesMoaCount',
							investigators = '$diseasesInvestigatorCount',
							news = '$diseaseNewsCount',
							trials = '$diseaseTrialsCount'
							WHERE entity_id = '$diseaseId'
							AND table_name = 'entities' LIMIT 1";
		
		$resUpdateTabsTable = mysql_query($sqlUpdateTabsTable) or die('Bad SQL query  . '.$sqlUpdateTabsTable);
		
	} else { // insert the tab counts for this entity in tabs table 
		
		$sqlInsertTabsTable = "INSERT INTO tabs set entity_id = '$diseaseId',
							table_name = 'entities',
							companies = '$diseasesCompanyCount',
							products = '$diseasesProductCount',
							moas = '$diseasesMoaCount',
							investigators = '$diseasesInvestigatorCount',
							news = '$diseaseNewsCount',
							trials = '$diseaseTrialsCount'";
		$resInsertTabsTable = mysql_query($sqlInsertTabsTable) or die('Bad SQL query . '.$sqlInsertTabsTable);;
	}
	
}

function CheckProductsFromDisease($DiseaseID)
{
	$Products = array();
	$productIds = array();
	$rowsCompanyName=array();
	$rowsDescription=array();
	
	$query = "SELECT DISTINCT e.`id` FROM `entities` e JOIN `entity_relations` er ON(e.`id` = er.`child`) WHERE e.`class`='Product' AND er.`parent`='" . mysql_real_escape_string($DiseaseID) . "' AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
	$res = mysql_query($query) or die('Bad SQL query getting products from Disease id in PT '.$query);
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		{
			$Products[] = $row['id'];
		}
	}
	$productIds = array_filter(array_unique($Products));
	
	foreach($productIds as $key=> $product)
	{
		
		$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description, company FROM `entities` WHERE `class`='Product' and id = '" . $product . "' "));
		$result['company'] = GetCompanyNames($result['id']);
		if($result['company'] != NULL && trim($result['company']) != '')
		{
			$Pflag=1;
			break;
		} 		
	}
	
	if($Pflag==1)
	{
		$query = "SELECT DISTINCT dt.`larvol_id` FROM data_trials dt JOIN entity_trials et ON (dt.`larvol_id` = et.`trial`) JOIN entity_trials et2 ON (dt.`larvol_id` = et2.`trial`) WHERE et.`entity` IN ('".implode("','",$productIds)."') AND et2.`entity`='" . $DiseaseID ."'";
		$res = mysql_query($query) or die('Bad SQL query '.$query);	
		if(mysql_num_rows($res) > 0)
		{
			return true;
		}
	}
	return false;
}
?>