<?php
require_once('db.php');
set_time_limit(0);
$cond = '';

//'Product','Area','Disease','Institution','MOA','Biomarker','MOA_Category','Therapeutic_Area','Disease_Category','Investigator'
if(isset($entityType) && trim($entityType) != '') {
	$cond = "AND class='$entityType'";
}

if(isset($entityId) && trim($entityId) != '' && $entityId > 0) {
	$cond = "AND id ='".mysql_real_escape_string($entityId)."'";
}


$getEntitySql = "SELECT id, class FROM entities WHERE (is_active <> '0' OR is_active IS NULL) ";
$getEntitySql .= $cond;

$resEntitiesSql = mysql_query($getEntitySql);

while($rowEntitiesSql = mysql_fetch_array($resEntitiesSql)) {
	
	$entityType = $rowEntitiesSql['class'];
	$entityId = $rowEntitiesSql['id'];
	
	switch($entityType) {
	
		case 'Institution': // for company tab
			updateCompanyTabCount($entityId);
		break;
		
		case 'Product': // for company tab
			updateProductTabCount($entityId);
		break;
		
		case 'MOA': // for company tab
			updateMOATabCount($entityId);
		break;
		
		case 'MOA_Category': // for company tab
			updateMoaCatTabCount($entityId);
		break;
		
		case 'Disease': // for company tab
			updateDiseasesTabCount($entityId);
		break;
		
		case 'Disease_Category': // for company tab
			updateDisCatTabCount($entityId);
		break;
		
		case 'Investigator': // for company tab
			updateInvestigatorTabCount($entityId);
		break;
	
	}

}


function updateCompanyTabCount($companyId) {
		
	global $db;
	global $now;
	$companyproductCount = 0;
	$companyTrialCount = 0;
	$companyNewsCount = 0;
	
	$products = array();
	$sqlProductForCompany = "SELECT  id  FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`) 
							WHERE et.`class`='Product' 
							AND er.`child`='" . mysql_real_escape_string($companyId) . "' AND (et.`is_active` <> '0' OR et.`is_active` IS NULL)";
	
	$resProductForCompany = mysql_query($sqlProductForCompany) or die($sqlProductForCompany.'- Bad SQL query');
	
	if($resProductForCompany)
	{
		while($rowProductForCompany = mysql_fetch_array($resProductForCompany)) {
			
			$products[$rowProductForCompany['id']] = $rowProductForCompany['id'];			
		}
	}
	
	$companyProducts = implode("','", $products);
	
	$sqlGetCompanyProductsTrials = "SELECT dt.`larvol_id`,et.`entity`
									FROM `data_trials` dt 
									JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`) 
									WHERE et.`entity` in ('".$companyProducts."')";
	
	$resGetCompanyProductsTrials = mysql_query($sqlGetCompanyProductsTrials) or die($sqlGetCompanyProductsTrials.'- Bad SQL query');
		
	if($resGetCompanyProductsTrials) {
		while($rowGetCompanyProductsTrials = mysql_fetch_array($resGetCompanyProductsTrials)) {
			$allTrials[$rowGetCompanyProductsTrials['larvol_id']] = $rowGetCompanyProductsTrials['larvol_id'];
			$entityTrials[$rowGetCompanyProductsTrials['entity']] = $rowGetCompanyProductsTrials['entity'];
		}
	}
	
	$companyTrialCount = count($allTrials);
	$companyproductCount = count($entityTrials);
	
	$sqlCompanyNews = "SELECT count(dt.`larvol_id`) as newsCount FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`) JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) WHERE et.`entity` in('" . $companyProducts . "')";
	$resCompanyNews = mysql_query($sqlCompanyNews) or die($sqlCompanyNews.'-> Bad SQL query ');
	
	if($resCompanyNews)
	{
		while($rowCompanyNews = mysql_fetch_array($resCompanyNews))
			$companyNewsCount = $rowCompanyNews['newsCount'];
	}
	if ($companyNewsCount > 50) $companyNewsCount = 50;
	
	// diseases count for company
	$companyDiseasesCount = 0;	
	$companyDiseases = array();
	
	$sqlGetDiseasesForCompany = "SELECT DISTINCT e.`id` FROM `entities` e JOIN `entity_relations` er ON(er.`parent` = e.`id`) JOIN `entities` e2 ON (er.`child`=e2.`id`) JOIN `entity_relations` er2 ON(er2.`parent` = e2.`id`) WHERE e.`class` = 'Disease' AND e2.`class` = 'Product' AND er2.`child`='$companyId' AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL) AND (e.`is_active` <> '0' OR e.`is_active` IS NULL) AND (e.`mesh_name` IS NOT NULL AND e.`mesh_name` <> '')";
	
	$resGetDiseasesForCompany = mysql_query($sqlGetDiseasesForCompany);	
	
	if($resGetDiseasesForCompany)
	{
		while($rowGetDiseasesForCompany = mysql_fetch_array($resGetDiseasesForCompany))
		{
			$companyDiseases[] = $rowGetDiseasesForCompany['id'];
		}
	}
	$companyDiseases = array_filter(array_unique($companyDiseases));
	if(count($companyDiseases) > 0) {
		$ImplodeDiseaseIds = implode("','", $companyDiseases);
	} else {
		$ImplodeDiseaseIds = '';
	}	
	
	$DiseaseQuery = "SELECT e2.`id` AS id, e2.`name` AS name, e2.`display_name` AS dispname,e.`id` AS ProdId, rpt.`highest_phase` AS phase, rpt.`entity1`, rpt.`entity2`, rpt.`count_total` FROM `rpt_masterhm_cells` rpt JOIN `entities` e ON((rpt.`entity1`=e.`id` AND e.`class`='Product') OR (rpt.`entity2`=e.`id` AND e.`class`='Product')) JOIN `entity_relations` er ON(e.`id` = er.`parent`) JOIN `entities` e2 ON((rpt.`entity1`=e2.`id` AND e2.`class`='Disease') ) WHERE (rpt.`count_total` > 0) AND er.`child` = '". $companyId ."' AND e2.`id` IN ('" . $ImplodeDiseaseIds . "') AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
				
	$DiseaseQuery2= "SELECT e2.`id` AS id, e2.`name` AS name, e2.`display_name` AS dispname,e.`id` AS ProdId, rpt.`highest_phase` AS phase, rpt.`entity1`, rpt.`entity2`, rpt.`count_total` FROM `rpt_masterhm_cells` rpt JOIN `entities` e ON((rpt.`entity1`=e.`id` AND e.`class`='Product') OR (rpt.`entity2`=e.`id` AND e.`class`='Product')) JOIN `entity_relations` er ON(e.`id` = er.`parent`) JOIN `entities` e2 ON((rpt.`entity2`=e2.`id` AND e2.`class`='Disease') ) WHERE (rpt.`count_total` > 0) AND er.`child` = '". $companyId ."' AND e2.`id` IN ('" . $ImplodeDiseaseIds . "') AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
	
	$resDiseaseQuery = mysql_query($DiseaseQuery) or die($DiseaseQuery.' '.mysql_error());
	$results=array();
	if($resDiseaseQuery) {
		while($rowDiseaseQuery = mysql_fetch_array($resDiseaseQuery)) {
			$results[] = $rowDiseaseQuery['id'];
		}
	}
		
	if($DiseaseQuery2) {
		$resDiseaseQuery2 = mysql_query($DiseaseQuery2) or die($DiseaseQuery2.' '. mysql_error());
	
		if($resDiseaseQuery2) {
			while($rowDiseaseQuery2 = mysql_fetch_array($resDiseaseQuery2)) {
				$results[] = $rowDiseaseQuery2['id'];
			}
		}
		
	}
	
	//diseases count for company
	$companyDiseasesCount = count(array_filter(array_unique($results)));
	
	//Diseases category count for company
	$companyDisCatCount = 0;
	
	$sqlGetDisCatForCompany = "SELECT DISTINCT e.`id` FROM `entities` e 
							JOIN `entity_relations` er ON(er.`parent` = e.`id`) 
							JOIN `entities` e2 ON (er.`child`=e2.`id`) 
							JOIN `entity_relations` er2 ON(er2.`parent` = e2.`id`) 
							WHERE e.`class` = 'Disease' 
							AND e2.`class` = 'Product' 
							AND er2.`child`='$companyId' 
							AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL)";
	$resGetDisCatForCompany = mysql_query($sqlGetDisCatForCompany) or die('Bad SQL query  . '.$sqlGetDisCatForCompany);
	
	$companyDiseasesCat = array();
	if($resGetDisCatForCompany) {
		while($rowGetDisCatForCompany = mysql_fetch_array($resGetDisCatForCompany)) {
			$companyDiseasesCat[] = $rowGetDisCatForCompany['id'];
		}
	}
	$companyDiseasesCat = array_filter(array_unique($companyDiseasesCat));
	if(count($companyDiseasesCat) > 0) {
		$ImplodeDiseaseCatIds = implode("','", $companyDiseasesCat);
	} else {
		$ImplodeDiseaseCatIds = '';
	}	
	
	$rowGetDiseaseCat = array();
	$sqlGetDiseaseCat = "SELECT DISTINCT e.`id` FROM `entities` e 
						JOIN `entity_relations` er ON(er.`parent` = e.`id`) 
						WHERE e.`class` = 'Disease_Category' 
						AND er.`child` IN ('$ImplodeDiseaseCatIds')  
						AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
						
	$resGetDiseaseCat = mysql_query($sqlGetDiseaseCat) or die($sqlGetDiseaseCat.' '.mysql_error());
	if($resGetDiseaseCat)
	$companyDisCatCount = mysql_num_rows($resGetDiseaseCat);
		
	//Investigator count for company
	$companyInvestigatorCount = 0;	
	$Investigators = array();
	
	$sqlGetInvestigatorForCompany = "SELECT DISTINCT et.entity from entity_trials et
									JOIN entity_trials et2 on (et.trial = et2.trial)
									JOIN entity_relations er on (et2.entity=er.parent and er.child= " . mysql_real_escape_string($companyId) . ")
									JOIN entities e2 on (er.parent = e2.id and e2.class='Product' and (e2.is_active<>0 or e2.is_active IS NULL) )
									JOIN entities e on (et.entity = e.id and e.class='Investigator')";
	
	$resGetInvestigatorForCompany = mysql_query($sqlGetInvestigatorForCompany) or die('Bad SQL query  . '.$sqlGetInvestigatorForCompany);
	
	if($resGetInvestigatorForCompany)
	{
		while($rowGetInvestigatorForCompany = mysql_fetch_array($resGetInvestigatorForCompany))
		{
			$Investigators[] = $rowGetInvestigatorForCompany['entity'];
		}
	}
	 $companyInvestigatorCount = count(array_filter(array_unique($Investigators)));	
	
	// to check if tab for this enetity is already there in the tabs table
	$sqlCheckTabsTable = "SELECT entity_id FROM tabs 
					WHERE entity_id = '$companyId'
					AND table_name = 'entities'";
	
	$resCheckTabsTable =  mysql_query($sqlCheckTabsTable);
	
	if(mysql_num_rows($resCheckTabsTable) > 0) { // update tab count for this entity in tabs table 
		
		
		$sqlUpdateTabsTable = "UPDATE tabs set entity_id = '$companyId',
							table_name = 'entities',
							products = '$companyproductCount',
							diseases = '$companyDiseasesCount',
							diseases_categories = '$companyDisCatCount',
							investigators = '$companyInvestigatorCount',
							news = '$companyNewsCount',
							trials = '$companyTrialCount'
							WHERE entity_id = '$companyId'
							AND table_name = 'entities' LIMIT 1";
		
		$resUpdateTabsTable = mysql_query($sqlUpdateTabsTable) or die('Bad SQL query  . '.$sqlUpdateTabsTable);
		
	} else { // insert the tab counts for this entity in tabs table 
		
		$sqlInsertTabsTable = "INSERT INTO tabs set entity_id = '$companyId',
							table_name = 'entities',
							products = '$companyproductCount',
							diseases = '$companyDiseasesCount',
							diseases_categories = '$companyDisCatCount',
							investigators = '$companyInvestigatorCount',
							news = '$companyNewsCount',
							trials = '$companyTrialCount'";
		$resInsertTabsTable = mysql_query($sqlInsertTabsTable) or die('Bad SQL query . '.$sqlInsertTabsTable);;
	}	
}


function updateProductTabCount($productId) {
		
	global $db;
	global $now;
	
	$productDiseasesCount = 0;
	$sqlProductDiseasesCount = "SELECT dt.`larvol_id`
								FROM data_trials dt 
								JOIN entity_trials et ON (dt.`larvol_id` = et.`trial`) 
								JOIN entity_trials et2 ON (dt.`larvol_id` = et2.`trial`) 
								JOIN entities e ON (e.id = et.`entity` AND e.`class` = 'Disease') 
								JOIN `entity_relations` er ON(er.`parent` = e.`id` AND e.`class` = 'Disease' and er.child = '$productId' AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)
								AND (e.`mesh_name` IS NOT NULL AND e.`mesh_name` <> '')) 
								WHERE  et2.`entity`='$productId'
								GROUP BY e.`id`";

	$resProductDiseasesCount = mysql_query($sqlProductDiseasesCount) or die('Bad SQL query getting Diseases for product'.$sqlProductDiseasesCount);
	
	$productDiseasesCount = mysql_num_rows($resProductDiseasesCount);
	
	// product disease category count
	$productDisCatCount = 0;
	
	$sqlGetDisCatForProduct = "SELECT DISTINCT e.`id` 
								FROM `entities` e 
								JOIN `entity_relations` er ON(er.`parent` = e.`id` AND e.`class` = 'Disease_Category') 
								WHERE  er.`child` IN (SELECT DISTINCT e.`id` FROM `entities` e 
									JOIN `entity_relations` er ON(er.`parent` = e.`id`) 
									WHERE e.`class` = 'Disease' 
									AND er.`child`='$productId')  
								AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
	$resGetDisCatForProduct = mysql_query($sqlGetDisCatForProduct) or die('Bad SQL query  . '.$sqlGetDisCatForProduct);
	
	$productDisCatCount = mysql_num_rows($resGetDisCatForProduct);

	// product investigator count
	$productInvestigatorCount = 0;
		
	$query = "SELECT DISTINCT entity 
			FROM entity_trials et
			JOIN entities e on et.entity=e.id AND e.class='Investigator' 
			AND et.trial IN (SELECT DISTINCT trial FROM entity_trials WHERE  entity= '" . mysql_real_escape_string($productId) . "')";
	

	$res = mysql_query($query) or die('Bad SQL query getting investigators for product.');
	
	$productInvestigatorCount = mysql_num_rows($res);

	// product trials count
	$productTrialsCount = 0;
	
	$sqlProductTrialsCount = "SELECT count(Distinct(dt.`larvol_id`)) as trialCount 
							FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`)  
							WHERE et.`entity`='$productId)'";
	$resProductTrialsCount = mysql_query($sqlProductTrialsCount) or die('Bad SQL query getting trials count from Product '.$sqlProductTrialsCount);
	
	if($resProductTrialsCount) {
		while($rowProductTrialsCount = mysql_fetch_array($resProductTrialsCount))
		$productTrialsCount = $rowProductTrialsCount['trialCount'];
	}
	
	$prodcutNewsCount = 0;
	$sqlProductNewsCount = "SELECT count(dt.`larvol_id`) as newsCount 
			FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`)  JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) 
			WHERE et.`entity`='$productId'";
	$resProductNewsCount = mysql_query($sqlProductNewsCount) or die('Bad SQL query getting news count for product '.$sqlProductNewsCount);

	if($resProductNewsCount)
	{
		while($rowProductNewsCount = mysql_fetch_array($resProductNewsCount))
			$prodcutNewsCount = $rowProductNewsCount['newsCount'];
	}
	if ($prodcutNewsCount > 50) $prodcutNewsCount = 50;
	
	// to check if tab for this enetity is already there in the tabs table
	$sqlCheckTabsTable = "SELECT entity_id FROM tabs 
					WHERE entity_id = '$productId'
					AND table_name = 'entities'";
	
	$resCheckTabsTable =  mysql_query($sqlCheckTabsTable);
	
	if(mysql_num_rows($resCheckTabsTable) > 0) { // update tab count for this entity in tabs table 
		
		
		$sqlUpdateTabsTable = "UPDATE tabs set entity_id = '$productId',
							table_name = 'entities',
							diseases = '$productDiseasesCount',
							diseases_categories = '$productDisCatCount',
							investigators = '$productInvestigatorCount',
							news = '$prodcutNewsCount',
							trials = '$productTrialsCount'
							WHERE entity_id = '$productId'
							AND table_name = 'entities' LIMIT 1";
		
		$resUpdateTabsTable = mysql_query($sqlUpdateTabsTable) or die('Bad SQL query  . '.$sqlUpdateTabsTable);
		
	} else { // insert the tab counts for this entity in tabs table 
		
		$sqlInsertTabsTable = "INSERT INTO tabs set entity_id = '$productId',
							table_name = 'entities',
							diseases = '$productDiseasesCount',
							diseases_categories = '$productDisCatCount',
							investigators = '$productInvestigatorCount',
							news = '$prodcutNewsCount',
							trials = '$productTrialsCount'";
		$resInsertTabsTable = mysql_query($sqlInsertTabsTable) or die('Bad SQL query . '.$sqlInsertTabsTable);;
	}
	
	
}


function updateMOATabCount($moaId) {
		
	global $db;
	global $now;
	
	$moaProductCount = 0;
	
	$sqlProductForMoa = "SELECT  id  FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`) 
							WHERE et.`class`='Product' 
							AND er.`child`='" . mysql_real_escape_string($moaId) . "' AND (et.`is_active` <> '0' OR et.`is_active` IS NULL)";

	$resProductForMoa = mysql_query($sqlProductForMoa) or die($sqlProductForMoa.'- Bad SQL query');
	
	$products = array();
	if($resProductForMoa) {
		while($rowProductForMoa = mysql_fetch_array($resProductForMoa)) {
			
			$products[$rowProductForMoa['id']] = $rowProductForMoa['id'];			
		}
	}
	
	if(count($products) > 0)
	$moaProducts = implode("','", $products);
	else
	$moaProducts = '';
	
	$sqlGetMoaProductsTrials = "SELECT dt.larvol_id,et.entity
									FROM data_trials dt 
									JOIN entity_trials et ON(dt.`larvol_id` = et.trial) 
									WHERE et.`entity` in ('".$moaProducts."')";
	
	$resGetMoaProductsTrials = mysql_query($sqlGetMoaProductsTrials) or die($sqlGetMoaProductsTrials.'- Bad SQL query');
	$entityTrials = array();	
	if($resGetMoaProductsTrials)
	{
		while($rowGetMoaProductsTrials = mysql_fetch_array($resGetMoaProductsTrials)) {
			//$allTrials[$rowGetMoaProductsTrials['larvol_id']] = $rowGetMoaProductsTrials['larvol_id'];
			$entityTrials[$rowGetMoaProductsTrials['entity']] = $rowGetMoaProductsTrials['entity'];
		}
	}
	
	//$moaTrialCount = count($allTrials);
	$moaProductCount = count($entityTrials);
	
	$moaDiseaseCount = 0;
	
	// diseases count for company	
	$moaDiseases = array();
	$sqlGetDiseasesForMoa = "SELECT DISTINCT e.`id` FROM `entities` e 
								JOIN `entity_relations` er ON(er.`parent` = e.`id`) 
								JOIN `entities` e2 ON (er.`child`=e2.`id`) 
								JOIN `entity_relations` er2 ON(er2.`parent` = e2.`id`) 
								WHERE e.`class` = 'Disease' 
								AND e2.`class` = 'Product' 
								AND er2.`child`='$moaId' 
								AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL) 
								AND (e.`is_active` <> '0' OR e.`is_active` IS NULL) 
								AND (e.`mesh_name` IS NOT NULL AND e.`mesh_name` <> '')";
	
	$resGetDiseasesForMoa = mysql_query($sqlGetDiseasesForMoa);	
	
	if($resGetDiseasesForMoa)
	{
		while($rowGetDiseasesForMoa = mysql_fetch_array($resGetDiseasesForMoa))
		{
			$moaDiseases[] = $rowGetDiseasesForMoa['id'];
		}
	}
	$moaDiseases = array_filter(array_unique($moaDiseases));
	
	if(count($moaDiseases) > 0) {
		$moaDiseases = implode("','", $moaDiseases);
	} else {
		$moaDiseases = '';
	}	
	
	$DiseaseQuery = "SELECT e2.`id` AS id, e2.`name` AS name, e2.`display_name` AS dispname,e.`id` AS ProdId, rpt.`highest_phase` AS phase, rpt.`entity1`, rpt.`entity2`, rpt.`count_total` FROM `rpt_masterhm_cells` rpt JOIN `entities` e ON((rpt.`entity1`=e.`id` AND e.`class`='Product') OR (rpt.`entity2`=e.`id` AND e.`class`='Product')) JOIN `entity_relations` er ON(e.`id` = er.`parent`) JOIN `entities` e2 ON((rpt.`entity1`=e2.`id` AND e2.`class`='Disease') ) WHERE (rpt.`count_total` > 0) AND er.`child` = '$moaId' AND e2.`id` IN ('$moaDiseases') AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
				
	$DiseaseQuery2= "SELECT e2.`id` AS id, e2.`name` AS name, e2.`display_name` AS dispname,e.`id` AS ProdId, rpt.`highest_phase` AS phase, rpt.`entity1`, rpt.`entity2`, rpt.`count_total` FROM `rpt_masterhm_cells` rpt JOIN `entities` e ON((rpt.`entity1`=e.`id` AND e.`class`='Product') OR (rpt.`entity2`=e.`id` AND e.`class`='Product')) JOIN `entity_relations` er ON(e.`id` = er.`parent`) JOIN `entities` e2 ON((rpt.`entity2`=e2.`id` AND e2.`class`='Disease') ) WHERE (rpt.`count_total` > 0) AND er.`child` = '$moaId' AND e2.`id` IN ('$moaDiseases') AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
					
	$DiseaseQueryResult = mysql_query($DiseaseQuery) or die($DiseaseQuery.' '.mysql_error());
	if($DiseaseQuery2)
		$DiseaseQueryResult2 = mysql_query($DiseaseQuery2) or die($DiseaseQuery2.' '. mysql_error());
	$results=array();
	while ($results[] = mysql_fetch_array($DiseaseQueryResult));
	if($DiseaseQuery2)
		while($results[] = mysql_fetch_array($DiseaseQueryResult2));
	
	$resDiseaseQuery = mysql_query($DiseaseQuery) or die($DiseaseQuery.' '.mysql_error());
	$results=array();
	if($resDiseaseQuery) {
		while($rowDiseaseQuery = mysql_fetch_array($resDiseaseQuery)) {
			$results[] = $rowDiseaseQuery['id'];
		}
	}
		
	if($DiseaseQuery2) {
		$resDiseaseQuery2 = mysql_query($DiseaseQuery2) or die($DiseaseQuery2.' '. mysql_error());
	
		if($resDiseaseQuery2) {
			while($rowDiseaseQuery2 = mysql_fetch_array($resDiseaseQuery2)) {
				$results[] = $rowDiseaseQuery2['id'];
			}
		}
		
	}
	
	//diseases count for moa
	$moaDiseaseCount = count(array_filter(array_unique($results)));	
	
	//Diseases category count for MOA
	$moaDisCatCount = 0;
	
	$sqlGetDisCatForMoa = "SELECT DISTINCT e.`id` FROM `entities` e 
							JOIN `entity_relations` er ON (er.`parent` = e.`id`) 
							JOIN `entities` e2 ON (er.`child`=e2.`id`) 
							JOIN `entity_relations` er2 ON(er2.`parent` = e2.`id`) 
							WHERE e.`class` = 'Disease' 
							AND e2.`class` = 'Product' 
							AND er2.`child`='$moaId' 
							AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL)";
	$resGetDisCatForMoa = mysql_query($sqlGetDisCatForMoa) or die('Bad SQL query  . '.$sqlGetDisCatForMoa);
	
	$moaDiseasesCat = array();
	if($resGetDisCatForMoa) {
		while($rowGetDisCatForMoa = mysql_fetch_array($resGetDisCatForMoa)) {
			$moaDiseasesCat[] = $rowGetDisCatForMoa['id'];
		}
	}
	$moaDiseasesCat = array_filter(array_unique($moaDiseasesCat));
	if(count($moaDiseasesCat) > 0) {
		$ImplodeDiseaseCatIds = implode("','", $moaDiseasesCat);
	} else {
		$ImplodeDiseaseCatIds = '';
	}
	
	$sqlGetDiseaseCat = "SELECT DISTINCT e.`id` FROM `entities` e 
						JOIN `entity_relations` er ON(er.`parent` = e.`id`) 
						WHERE e.`class` = 'Disease_Category' 
						AND er.`child` IN ('$ImplodeDiseaseCatIds')  
						AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
						
	$resGetDiseaseCat = mysql_query($sqlGetDiseaseCat) or die($sqlGetDiseaseCat.' '.mysql_error());
	if($resGetDiseaseCat)
	$moaDisCatCount = mysql_num_rows($resGetDiseaseCat);
		
	//Investigator count for MOA
	$moaInvestigatorCount = 0;	
	$Investigators = array();
	
	$sqlGetInvestigatorForMoa = "SELECT DISTINCT et.entity from entity_trials et
								JOIN entity_trials et2 on (et.trial = et2.trial)
								JOIN entity_relations er on (et2.entity=er.parent and er.child= " . mysql_real_escape_string($moaId) . ")
								JOIN entities e2 on (er.parent = e2.id and e2.class='Product' and (e2.is_active<>0 or e2.is_active IS NULL) )
								JOIN entities e on (et.entity = e.id and e.class='Investigator')";
	
	$resGetInvestigatorForMoa = mysql_query($sqlGetInvestigatorForMoa) or die('Bad SQL query  . '.$sqlGetInvestigatorForMoa);
	
	if($resGetInvestigatorForMoa) {
		while($rowGetInvestigatorForMoa = mysql_fetch_array($resGetInvestigatorForMoa)) {
			$Investigators[] = $rowGetInvestigatorForMoa['entity'];
		}
	}
	$moaInvestigatorCount = count(array_filter(array_unique($Investigators)));
	 
	$moaNewsCount = 0;
	$sqlGetMoaNews = "SELECT count(dt.`larvol_id`) as newsCount FROM `data_trials` dt 
					JOIN `entity_trials` et ON(dt.`larvol_id` =et.`trial`) 
					JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) 
					WHERE et.`entity` in('$moaProducts')";
	$resGetMoaNews = mysql_query($sqlGetMoaNews) or die($sqlGetMoaNews . ' Bad SQL query getting news count for moa');

	if($resGetMoaNews) {
		while($rowGetMoaNews = mysql_fetch_array($resGetMoaNews))
			$moaNewsCount = $rowGetMoaNews['newsCount'];
	}
	if ($moaNewsCount > 50) $moaNewsCount = 50;
	
	// to check if tab for this enetity is already there in the tabs table
	$sqlCheckTabsTable = "SELECT entity_id FROM tabs 
						WHERE entity_id = '$moaId'
						AND table_name = 'entities'";
	
	$resCheckTabsTable =  mysql_query($sqlCheckTabsTable);
	
	if(mysql_num_rows($resCheckTabsTable) > 0) { // update tab count for this entity in tabs table 		
		
		$sqlUpdateTabsTable = "UPDATE tabs set entity_id = '$moaId',
							table_name = 'entities',
							products = '$moaProductCount',
							diseases = '$moaDiseaseCount',
							diseases_categories = '$moaDisCatCount',
							investigators = '$moaInvestigatorCount',
							news = '$moaNewsCount'
							WHERE entity_id = '$moaId'
							AND table_name = 'entities' LIMIT 1";
		
		$resUpdateTabsTable = mysql_query($sqlUpdateTabsTable) or die('Bad SQL query  . '.$sqlUpdateTabsTable);
		
	} else { // insert the tab counts for this entity in tabs table 
		
		$sqlInsertTabsTable = "INSERT INTO tabs set entity_id = '$moaId',
							table_name = 'entities',
							products = '$moaProductCount',
							diseases = '$moaDiseaseCount',
							diseases_categories = '$moaDisCatCount',
							investigators = '$moaInvestigatorCount',
							news = '$moaNewsCount'";
		$resInsertTabsTable = mysql_query($sqlInsertTabsTable) or die('Bad SQL query . '.$sqlInsertTabsTable);;
	}
	
}


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


function updateInvestigatorTabCount($investigatorId) {
		
	global $db;
	global $now;
	
	//investigator company count
	$investigatorCompanyCount = 0;
	
	$sqlGetCompaniesForInvestogator = "SELECT DISTINCT er.child as CompId from entity_trials et
				JOIN entity_trials et2 ON (et.trial = et2.trial and et.entity = " . $investigatorId . ")
				JOIN entities e ON (et2.entity = e.id and e.class='Product' AND (e.`is_active` <> '0' OR e.`is_active` IS NULL))
				JOIN entity_relations er ON (e.id = er.parent )
				JOIN entities e2 ON (er.child = e2.id and e2.class='Institution')";
				
	$resGetCompaniesForInvestogator = mysql_query($sqlGetCompaniesForInvestogator) or die('Bad SQL query getting companies from Investigator id'.$sqlGetCompaniesForInvestogator);
	$investigatorCompanyCount = mysql_num_rows($resGetCompaniesForInvestogator);
	
	//investigator product count
	$investigatorProductCount = 0;	
	
	$sqlGetInvestigatorProduct = "SELECT  DISTINCT et2.entity from entity_trials et
				JOIN entity_trials et2 ON (et.trial = et2.trial and et.entity = " . $investigatorId . ")
				JOIN entities e ON (et2.entity = e.id and e.class='Product' AND (e.`is_active` <> '0' OR e.`is_active` IS NULL))";
				
	$resGetInvestigatorProduct = mysql_query($sqlGetInvestigatorProduct) or die(' - Bad SQL query getting products from Investigator id in PT  '.$sqlGetInvestigatorProduct);
	$investigatorProductCount = mysql_num_rows($resGetInvestigatorProduct);
	
	//investigator MOA count
	$investigatorMoaCount = 0;	
	
	$sqlGetMoaForInvestigator = "SELECT er.child AS CompId
				FROM entity_relations er 
				JOIN entities e ON (er.child = e.id and e.class='MOA')
				JOIN entity_trials et ON(er.parent = et.entity) 
				JOIN entity_trials et2 ON(et.trial = et2.trial and et2.entity =" . $investigatorId . " ) 
				JOIN data_trials dt on (et2.trial = dt.larvol_id )
				group by CompId";
			  
	$resGetMoaForInvestigator = mysql_query($sqlGetMoaForInvestigator) or die('Bad SQL query getting MOAs '.$sqlGetMoaForInvestigator);
	$investigatorMoaCount = mysql_num_rows($resGetMoaForInvestigator);
	
	//investigator Disease count
	$investigatorDiseaseCount = 0;
	
	$sqlGetDiseaseForinvestigator = "SELECT DISTINCT e.`id` FROM `entity_trials` et 
					JOIN `entity_trials` et2 ON(et.`trial`=et2.trial and et.entity='$investigatorId') 
					JOIN entities e on (et2.entity = e.id and e.class='Disease')
					JOIN `entity_relations` er ON(e.id=er.parent) 
					JOIN `entity_trials` et1 ON(er.child = et1.entity and et.trial=et1.trial ) 
					JOIN entities e1 on (et1.entity = e1.id and e1.class='Product')
					where (e.`is_active` <> '0' OR e.`is_active` IS NULL) AND (e.`mesh_name` IS NOT NULL AND e.`mesh_name` <> '')";
					
	$resGetDiseaseForinvestigator = mysql_query($sqlGetDiseaseForinvestigator) or die('Bad SQL query getting Diseases from Investigator id'.$sqlGetDiseaseForinvestigator);
	$investigatorDiseaseCount = mysql_num_rows($resGetDiseaseForinvestigator);
	
	//investigator Disease count
	$investigatorTrialCount = 0;
	
	$sqlGetInvestigatorTrials = "SELECT count(et.trial) as trialCount 
								FROM `entity_trials` et 
								JOIN entities e ON (et.entity=e.id and e.class='Investigator' and et.entity='$investigatorId')";
	$resGetInvestigatorTrials = mysql_query($sqlGetInvestigatorTrials) or die('Bad SQL query getting trials count from Investigator id in TZ '.$sqlGetInvestigatorTrials);

	if($resGetInvestigatorTrials) {
		while($rowGetInvestigatorTrials = mysql_fetch_array($resGetInvestigatorTrials))
			$investigatorTrialCount = $rowGetInvestigatorTrials['trialCount'];
	}
		
	// to check if tab for this enetity is already there in the tabs table
	$sqlCheckTabsTable = "SELECT entity_id FROM tabs 
					WHERE entity_id = '$investigatorId'
					AND table_name = 'entities'";
	
	$resCheckTabsTable =  mysql_query($sqlCheckTabsTable);
	
	if(mysql_num_rows($resCheckTabsTable) > 0) { // update tab count for this entity in tabs table 
		
		
		$sqlUpdateTabsTable = "UPDATE tabs set entity_id = '$investigatorId',
							table_name = 'entities',
							companies = '$investigatorCompanyCount',
							products = '$investigatorProductCount',
							moas = '$investigatorMoaCount',
							diseases = '$investigatorDiseaseCount',
							trials = '$investigatorTrialCount'
							WHERE entity_id = '$investigatorId'
							AND table_name = 'entities' LIMIT 1";
		
		$resUpdateTabsTable = mysql_query($sqlUpdateTabsTable) or die('Bad SQL query  . '.$sqlUpdateTabsTable);
		
	} else { // insert the tab counts for this entity in tabs table 
		
		$sqlInsertTabsTable = "INSERT INTO tabs set entity_id = '$investigatorId',
							table_name = 'entities',
							companies = '$investigatorCompanyCount',
							products = '$investigatorProductCount',
							moas = '$investigatorMoaCount',
							diseases = '$investigatorDiseaseCount',
							trials = '$investigatorTrialCount'";
		$resInsertTabsTable = mysql_query($sqlInsertTabsTable) or die('Bad SQL query . '.$sqlInsertTabsTable);;
	}
	
	
}

function updateDisCatTabCount($disCatId) {
		
	global $db;
	global $now;
		
	$diseases = array();
	
	$sqlGetDiseases = "SELECT child FROM `entity_relations` WHERE parent = '$disCatId'";
	$resGetDiseases = mysql_query($sqlGetDiseases) or die($sqlGetDiseases.'Bad SQL query for counting diseases by a disease category ID ');
	
	if($resGetDiseases)
	{
		while($rowGetDiseases = mysql_fetch_array($resGetDiseases))
		{
			$diseases[] = $rowGetDiseases['child'];
		}
	}
	$diseases = array_filter(array_unique($diseases));
	
	if(count($diseases) > 0)
	$implodeDiseases = implode("','", $diseases);
	else
	$implodeDiseases = '';

	//Diaease company  count
	$disCatCompanyCount = 0;
	
	$disCatCompanies = array();
	$sqlGetCompaniesForDisCat = "SELECT DISTINCT e.`id` FROM `entities` e 
			JOIN `entity_relations` er ON(er.`child` = e.`id`) 
			JOIN `entities` e2 ON(e2.`id` = er.`parent`) 
			JOIN `entity_relations` er2 ON(er2.`child` = e2.`id`) 
			WHERE e.`class` = 'Institution' 
			AND e.`category`='Industry' 
			AND e2.`class` = 'Product' 
			AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL) AND er2.`parent` in('$implodeDiseases')";
	$resGetProductForDisCat = mysql_query($sqlGetCompaniesForDisCat) or die($sqlGetCompaniesForDisCat.'Bad SQL query getting companies from products ids in CT');
	if($resGetProductForDisCat) {
		while($rowGetProductForDisCat = mysql_fetch_array($resGetProductForDisCat)) {
			$disCatCompanies[] = $rowGetProductForDisCat['id'];
		}
	}
	
	$disCatCompanyCount = count(array_filter(array_unique($disCatCompanies)));
	
	//Diaease category product count
	$disCatProductsCount = 0;
	$disCatProducts = array();
	$sqlGetProductForDisCat = "SELECT DISTINCT e.`id` FROM `entity_relations` er 
							JOIN `entities` e ON(e.`id` = er.`child`) 
							WHERE e.`class`='Product' 
							AND er.`parent` in('$implodeDiseases') 
							AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
	$resGetProductForDisCat = mysql_query($sqlGetProductForDisCat) or die('Bad SQL query getting products from DiseaseCat id in PT '.$sqlGetProductForDisCat);
	
	if($resGetProductForDisCat) {
		while($rowGetProductForDisCat = mysql_fetch_array($resGetProductForDisCat)) {
			$disCatProducts[] = $rowGetProductForDisCat['id'];
		}
	}
	
	$disCatProductsCount = count(array_filter(array_unique($disCatProducts)));	
	
	//Diaease category MOA count
	$disCatMoaCount = 0;
	
	$MOAOrMOACats = array();
	$onlymoas = array();
	$OnlyMOACatIds = array();
	$OnlyMOAIds = array();
	
	//Get MOA Categoryids from Product id
	$sqlGetMoa = "SELECT e1.`id` as id, e2.`id` AS moaid FROM `entities` e1 
				JOIN `entity_relations` er1 ON(er1.`parent` = e1.`id`) 
				JOIN `entities` e2 ON (er1.`child` = e2.`id`) 
				JOIN `entity_relations` er2 ON(er2.`child` = e2.`id`) 
				JOIN `entities` e3 ON(e3.`id` = er2.`parent`) 
				JOIN `entity_relations` er3 ON(er3.`child` = e3.`id`) 
				WHERE e1.`class` = 'MOA_Category' AND e1.`name` <> 'Other' 
				AND e2.`class` = 'MOA' AND e3.`class` = 'Product' 
				AND er3.`parent` in('$implodeDiseases') AND (e3.`is_active` <> '0' OR e3.`is_active` IS NULL)";
	
	$resGetMoa = mysql_query($sqlGetMoa) or die($sqlGetMoa.'Bad SQL query getting MOA Categories from products ids in MT');
	if($resGetMoa) {
		while($rowGetMoa = mysql_fetch_array($resGetMoa)) {
			if(!in_array($rowGetMoa['id'], $MOAOrMOACats))
				$MOAOrMOACats[] = $rowGetMoa['id'];
			if(!in_array($rowGetMoa['moaid'], $onlymoas))
				$onlymoas[] = $rowGetMoa['moaid'];
		}
	}
	$OnlyMOACatIds = $MOAOrMOACats;
	
	
	if(count($onlymoas) > 0) 
		$qstr=" AND e.`id` NOT IN (" . implode(',',$onlymoas) . ")" ;
	else
		$qstr='';
	$sqlGetMoaCat = "SELECT DISTINCT e.`id` 
			FROM `entities` e JOIN `entity_relations` er ON (er.`child` = e.`id` and e.`class` = 'MOA' " . $qstr ." ) 
			JOIN `entities` e2 ON (e2.`id` = er.`parent` and e2.`class` = 'Product' AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL) ) 
			JOIN `entity_relations` er2 ON(er2.`child` = e2.`id` and er2.`parent` in('$implodeDiseases')) 
				" ;
	$resGetMoaCat = mysql_query($sqlGetMoaCat) or die($sqlGetMoaCat.'Bad SQL query getting MOAs from products ids in MT');
		if($resGetMoaCat) {
			while($rowGetMoaCat = mysql_fetch_array($resGetMoaCat)) {
				$MOAOrMOACats[] = $rowGetMoaCat['id'];
				$OnlyMOAIds[] = $rowGetMoaCat['id'];
			}
		}
		
	$disCatMoaCount = count(array_filter(array_unique($MOAOrMOACats)));
	
	//Diaease category MOA count
	$disCatTrialsCount = 0;
	
	$sqlGetTrials =	"SELECT count(Distinct(dt.`larvol_id`)) as trialCount 
					FROM `entity_trials` et 
					JOIN `data_trials` dt 
					ON(dt.`larvol_id` = et.`trial` and et.`entity` in('$implodeDiseases'))";
	$resGetTrials = mysql_query($sqlGetTrials) or die($sqlGetTrials.'Bad SQL query getting trials count from Disease id in TZ');

	if($resGetTrials) {
		while($rowGetTrials = mysql_fetch_array($resGetTrials))
			$disCatTrialsCount = $rowGetTrials['trialCount'];
	}
	
	// to check if tab for this enetity is already there in the tabs table
	$sqlCheckTabsTable = "SELECT entity_id FROM tabs 
					WHERE entity_id = '$disCatId'
					AND table_name = 'entities'";
	
	$resCheckTabsTable =  mysql_query($sqlCheckTabsTable);
	
	if(mysql_num_rows($resCheckTabsTable) > 0) { // update tab count for this entity in tabs table 
		
		
		$sqlUpdateTabsTable = "UPDATE tabs set entity_id = '$disCatId',
							table_name = 'entities',
							companies = '$disCatCompanyCount',
							products = '$disCatProductsCount',
							moas = '$disCatMoaCount',
							trials = '$disCatTrialsCount'
							WHERE entity_id = '$disCatId'
							AND table_name = 'entities' LIMIT 1";
		
		$resUpdateTabsTable = mysql_query($sqlUpdateTabsTable) or die('Bad SQL query  . '.$sqlUpdateTabsTable);
		
	} else { // insert the tab counts for this entity in tabs table 
		
		$sqlInsertTabsTable = "INSERT INTO tabs set entity_id = '$disCatId',
							table_name = 'entities',
							companies = '$disCatCompanyCount',
							products = '$disCatProductsCount',
							moas = '$disCatMoaCount',
							trials = '$disCatTrialsCount'";
		$resInsertTabsTable = mysql_query($sqlInsertTabsTable) or die('Bad SQL query . '.$sqlInsertTabsTable);;
	}
	
}



function updateMoaCatTabCount($moaCatId) {
		
	global $db;
	global $now;
	
	//MOA category product count
	$moaCatProductCount = 0;
	
	$products = array();
	$sqlGetProductForMoaCat = "SELECT et.`id` FROM `entities` et 
							JOIN `entity_relations` er ON(et.`id` = er.`parent`) 
							JOIN `entity_relations` er2 ON(er.`child` = er2.`child`) 
							JOIN `entities` et2 ON (et2.`id` = er2.`parent`) 
							WHERE et.`class`='Product' 
							AND (et.`is_active` <> '0' OR et.`is_active` IS NULL) 
							AND et2.`class` = 'MOA_Category' 
							AND et2.`id`='$moaCatId'";
	
	$resGetProductForMoaCat = mysql_query($sqlGetProductForMoaCat) or die($sqlGetProductForMoaCat.'Bad SQL query getting products from moa id in PT');
	
	if($resGetProductForMoaCat) {
		while($rowGetProductForMoaCat = mysql_fetch_array($resGetProductForMoaCat)) {
			$products[] = $rowGetProductForMoaCat['id'];
		}
	}
	$moaCatProductCount = count(array_filter(array_unique($products)));
	
	//MOA category diseases count
	$moaCatDiseaseCount = 0;
	
	$diseases = array();
	
	$sqlGetDiseasesForMoaCat = "SELECT DISTINCT e.`id` FROM `entities` e 
							JOIN `entity_relations` er ON(er.`parent` = e.`id`) 
							JOIN `entities` e2 ON (er.`child`=e2.`id`) 
							JOIN `entity_relations` er2 ON(er2.`parent` = e2.`id`) 
							JOIN `entities` e3 ON (er2.`child`=e3.`id`) 
							JOIN `entity_relations` er3 ON(er3.`child` = e3.`id`) 
							WHERE e.`class` = 'Disease' 
							AND e2.`class` = 'Product' 
							AND e3.`class` = 'MOA' 
							AND er3.`parent`='$moaCatId' 
							AND (e2.`is_active` <> '0' OR e2.`is_active` IS NULL) 
							AND (e.`is_active` <> '0' OR e.`is_active` IS NULL) 
							AND (e.`mesh_name` IS NOT NULL AND e.`mesh_name` <> '')";
	$resGetDiseasesForMoaCat = mysql_query($sqlGetDiseasesForMoaCat) or die($sqlGetDiseasesForMoaCat.'Bad SQL query getting Diseases from MOA category id');
	
	if($resGetDiseasesForMoaCat) {
		while($rowGetDiseasesForMoaCat = mysql_fetch_array($resGetDiseasesForMoaCat)) {
			$diseases[] = $rowGetDiseasesForMoaCat['id'];
		}
	}
	$diseases = array_filter(array_unique($diseases));
	$moaCatDiseaseCount = count($diseases);
	
	if(count($diseases) > 0){
		$implodeDiseasesIds = implode("','", $diseases);
	}else{
		$implodeDiseasesIds = '';
	}
	
	//MOA category diseases count
	$moaCatDiseaseCatCount = 0;
	
	$diseasesCatgories = array();
	$sqlGetDiaCatForMoaCat = "SELECT DISTINCT e.`id` FROM `entities` e 
							JOIN `entity_relations` er ON(er.`parent` = e.`id`) 
							WHERE e.`class` = 'Disease_Category' 
							AND er.`child` IN ('$implodeDiseasesIds')  
							AND (e.`is_active` <> '0' OR e.`is_active` IS NULL)";
	$resGetDiaCatForMoaCat = mysql_query($sqlGetDiaCatForMoaCat) or die($sqlGetDiaCatForMoaCat.'Bad SQL query getting Diseases from products ids in DT');
	if($resGetDiaCatForMoaCat) {
		while($rowGetDiaCatForMoaCat = mysql_fetch_array($resGetDiaCatForMoaCat)) {
			$diseasesCatgories[] = $rowGetDiaCatForMoaCat['id'];
		}
	}
	
	$moaCatDiseaseCatCount = count(array_filter(array_unique($diseasesCatgories)));
	
	//MOA category investigator count
	$moaCatInvCount = 0;
	
	$sqlGetMoaForMoaCat = "select child from entity_relations where parent= '$moaCatId'";
	$resGetMoaForMoaCat = mysql_query($sqlGetMoaForMoaCat) or die($sqlGetMoaForMoaCat.'Bad SQL query getting child records from Moa_Category');
	$moaIds = array();
	while($rowGetMoaForMoaCat = mysql_fetch_array($resGetMoaForMoaCat)) {
		$moaIds[] = $rowGetMoaForMoaCat['child'];
	}
	
	if(count($moaIds) > 0){
		$implodeInvIds = implode("','", $moaIds);
	}else{
		$implodeInvIds = '';
	}
	
	$sqlGetInvCount = "SELECT DISTINCT et.entity from entity_trials et
				JOIN entity_trials et2 on (et.trial = et2.trial)
				JOIN entity_relations er on (et2.entity=er.parent and er.child in ('$implodeInvIds'))
				JOIN entities e2 on (er.parent = e2.id and e2.class='Product' and (e2.is_active<>0 or e2.is_active IS NULL) )
				JOIN entities e on (et.entity = e.id and e.class='Investigator')";
		
	$resGetInvCount = mysql_query($sqlGetInvCount) or die('Bad SQL query getting investigators. '.$sqlGetInvCount);
	$investigators = array();
	if($resGetInvCount) {
		while($rowGetInvCount = mysql_fetch_array($resGetInvCount)) {
			$investigators[] = $rowGetInvCount['entity'];
		}
	}
	
	$moaCatInvCount = count(array_filter(array_unique($investigators)));
	
	$moaNewsCount = 0;
	
	if(count($products) > 0){
		$implodeProducts = implode("','", $products);
	}else{
		$implodeProducts = '';
	}
	
	$sqlGetNews = "SELECT count(dt.`larvol_id`) as newsCount FROM `data_trials` dt JOIN `entity_trials` et ON(dt.`larvol_id` = et.`trial`) JOIN `news` n ON(dt.`larvol_id` = n.`larvol_id`) WHERE et.`entity` in('$implodeProducts')";
	$resGetNews = mysql_query($sqlGetNews) or die($sqlGetNews.'Bad SQL query getting trials count for Products ids in Sigma Companys Page');

	if($resGetNews)
	{
		while($rowGetNews = mysql_fetch_array($resGetNews))
			$moaNewsCount = $rowGetNews['newsCount'];
	}
	if ($moaNewsCount > 50) $moaNewsCount = 50;
	
	
	// to check if tab for this enetity is already there in the tabs table
	$sqlCheckTabsTable = "SELECT entity_id FROM tabs 
					WHERE entity_id = '$moaCatId'
					AND table_name = 'entities'";
	
	$resCheckTabsTable =  mysql_query($sqlCheckTabsTable);
	
	if(mysql_num_rows($resCheckTabsTable) > 0) { // update tab count for this entity in tabs table 
		
		
		$sqlUpdateTabsTable = "UPDATE tabs set entity_id = '$moaCatId',
							table_name = 'entities',
							products = '$moaCatProductCount',
							diseases = '$moaCatDiseaseCount',
							diseases_categories = '$moaCatDiseaseCatCount',
							investigators = '$moaCatInvCount',
							news = '$moaNewsCount'
							WHERE entity_id = '$moaCatId'
							AND table_name = 'entities' LIMIT 1";
		
		$resUpdateTabsTable = mysql_query($sqlUpdateTabsTable) or die('Bad SQL query  . '.$sqlUpdateTabsTable);
		
	} else { // insert the tab counts for this entity in tabs table 
		
		$sqlInsertTabsTable = "INSERT INTO tabs set entity_id = '$moaCatId',
							table_name = 'entities',
							products = '$moaCatProductCount',
							diseases = '$moaCatDiseaseCount',
							diseases_categories = '$moaCatDiseaseCatCount',
							investigators = '$moaCatInvCount',
							news = '$moaNewsCount'";
		$resInsertTabsTable = mysql_query($sqlInsertTabsTable) or die('Bad SQL query . '.$sqlInsertTabsTable);;
	}
	
	
}
