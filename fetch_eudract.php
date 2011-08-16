<?php
require_once('db.php');
require_once('include.import.php');
echo '.<br>';
echo str_repeat ("   ", 3500);
$mapping = array(
    'EudraCT Number:' => 'eudract_number',
    'National Competent Authority:' => 'competent_authority',
    'Clinical Trial Type:' => 'clinical_trial_type',
    'Trial Status:' => 'trial_status',
    'Member State Concerned' => 'member_state',
    'Full title of the trial' => 'full_title',
    'Title of the trial for lay people, in easily understood, i.e. non-technical, language' => 'trial_title',
    'Name or abbreviated title of the trial where available' => 'brief_title',
    'Sponsor\'s protocol code number' => 'sponsor_code',
    'ISRCTN (International Standard Randomised Controlled Trial) Number' => 'isrctn',
    'US NCT (ClinicalTrials.gov registry) number' => 'usnct',
    'WHO Universal Trial Reference Number (UTRN)' => 'utrn',
    'Other Identifier - Name' => 'other_name',
    'Other Identifier - Identifier' => 'other_identifier',
    'Trial is part of a Paediatric Investigation Plan' => 'partof_pip',
    'EMA Decision number of Paediatric Investigation Plan' => 'decision_pip',
    'Name of Sponsor' => 'sponsor_name',
    'Status of the sponsor' => 'sponsor_status',
    'Name of organisation providing support' => 'support_org',
    'Name of organisation' => 'contact_name',
    'Functional name of contact point' => 'contact_function',
    'Street Address' => 'contact_address',
    'Town/ city' => 'contact_city',
    'Post code' => 'contact_postal',
    'Telephone number' => 'contact_telephone',
    'Fax number' => 'contact_fax',
    'E-mail' => 'contact_email',
    'IMP Role' => 'imp_role',
    'IMP to be used in the trial has a marketing authorisation' => 'imp_marketing',
    'Trade name' => 'imp_trade_name',
    'Name of the Marketing Authorisation holder' => 'imp_market_holder',
    'Country which granted the Marketing Authorisation' => 'imp_market_country',
    'The IMP has been designated in this indication as an orphan drug in the Community' => 'imp_orphan_com',
    'Orphan drug designation number' => 'imp_orphan_drug',
    'Product name' => 'imp_product_name',
    'Product code' => 'imp_product_code',
    'Pharmaceutical form' => 'imp_product_form',
    'Specific paediatric formulation' => 'imp_paed_form',
    'Routes of administration for this IMP' => 'imp_admin_route',
    'INN - Proposed INN' => 'imp_inn',
    'CAS number' => 'imp_cas',
    'Current sponsor code' => 'imp_sponsor',
    'Other descriptive name' => 'imp_name',
    'EV Substance Code' => 'imp_ev_code',
    'Concentration unit' => 'imp_conc_unit',
    'Concentration type' => 'imp_conc_type',
    'Concentration number' => 'imp_conc_number',
    'Active substance of chemical origin' => 'imp_chemical_orgin',
    'Active substance of biological/ biotechnological origin (other than Advanced Therapy IMP (ATIMP)' => 'imp_biol_orgin',
    'Advanced Therapy IMP (ATIMP)' => 'imp_atimp',
    'Somatic cell therapy medicinal product' => 'imp_somatic_cell',
    'Gene therapy medical product' => 'imp_gene_therapy',
    'Tissue Engineered Product' => 'imp_tissue_eng',
    'Combination ATIMP (i.e. one involving a medical device)' => 'imp_combo_atimp',
    'CAT classification and reference number' => 'imp_cat',
    'Combination product that includes a device, but does not involve an Advanced Therapy' => 'imp_therapy',
    'Radiopharmaceutical medicinal product' => 'imp_radio_mp',
    'Immunological medicinal product (such as vaccine, allergen, immune serum)' => 'imp_immuno_mp',
    'Plasma derived medicinal product' => 'imp_plasma_mp',
    'Extractive medicinal product' => 'imp_extract_mp',
    'Recombinant medicinal product' => 'imp_recomb_mp',
    'Medicinal product containing genetically modified organisms' => 'imp_organism_mp',
    'Herbal medicinal product' => 'imp_herbal_mp',
    'Homeopathic medicinal product' => 'imp_homeo_mp',
    'Another type of medicinal product' => 'imp_another_mp',
    'Other medicinal product type' => 'imp_other_mp',
    'Is a Placebo used in this Trial?' => 'placebo_trial',
    'Pharmaceutical form of the placebo' => 'placebo_form',
    'Route of administration of the placebo' => 'placebo_route',
    'Medical condition(s) being investigated' => 'trial_being_invest',
    'Medical condition in easily understood language' => 'trial_laymen',
    'Therapeutic area' => 'trial_therapy',
    'Medical condition in easily understood language' => 'trial_dra_condition',
    'Version' => 'trial_dra_version',
    'Level' => 'trial_dra_level',
    'Classification code' => 'trial_dra_code',
    'Term' => 'trial_dra_term',
    'System Organ Class' => 'trial_dra_class',
    'Condition being studied is a rare disease' => 'trial_rare',
    'Main objective of the trial' => 'trial_objective',
    'Secondary objectives of the trial' => 'trial_sec_objective',
    'Trial contains a sub-study' => 'trial_substudy',
    'Full title, date and version of each sub-study and their related objectives' => 'trial_full_title',
    'Principal inclusion criteria' => 'trial_inclusion',
    'Principal exclusion criteria' => 'trial_exclusion',
    'Primary end point(s)' => 'trial_primary_end',
    'Timepoint(s) of evaluation of this end point' => 'trial_primary_timepoint',
    'Secondary end point(s)' => 'trial_secondary_end',
    'Timepoint(s) of evaluation of this end point' => 'trial_secondary_timepoint',
    'Diagnosis' => 'trial_scope_diagnosis',
    'Prophylaxis' => 'trial_scope_prophylaxis',
    'Therapy' => 'trial_scope_therapy',
    'Safety' => 'trial_scope_safety',
    'Efficacy' => 'trial_scope_efficacy',
    'Pharmacokinetic' => 'trial_scope_pharmacokinectic',
    'Pharmacodynamic' => 'trial_scope_pharmacodynamic',
    'Bioequivalence' => 'trial_scope_bioe',
    'Dose response' => 'trial_scope_dose',
    'Pharmacogenetic' => 'trial_scope_pharmacogenetic',
    'Pharmacogenomic' => 'trial_scope_pharmacogenomic',
    'Pharmacoeconomic' => 'trial_scope_pharmacoeconomic',
    'Other scope of the trial description' => 'trial_scope_other_descr',
    'Human pharmacology (Phase I)' => 'trial_type_human_pharma',
    'First administration to humans' => 'trial_type_human_admin',
    'Bioequivalence study' => 'trial_type_bioequivalence',
    'Other trial type description' => 'trial_type_other_desc',
    'Therapeutic exploratory (Phase II)' => 'trial_type_thera_exploratory',
    'Therapeutic confirmatory (Phase III)' => 'trial_type_thera_confirm',
    'Therapeutic use (Phase IV)' => 'trial_type_thera_use',
    'Controlled' => 'trial_design_controlled',
    'Randomised' => 'trial_design_randomised',
    'Open' => 'trial_design_open',
    'Single blind' => 'trial_design_single',
    'Double blind' => 'trial_design_double',
    'Parallel group' => 'trial_design_parallel',
    'Cross over' => 'trial_design_cross',
    'Other trial design description' => 'trial_design_descrip',
    'Other medicinal product(s)' => 'trial_compar_mp',
    'Placebo' => 'trial_compar_placebo',
    'Comparator description' => 'trial_compar_descr',
    'Number of treatment arms in the trial' => 'trial_compar_numb_arms',
    'The trial involves single site in the Member State concerned' => 'trial_compar_single_site',
    'The trial involves multiple sites in the Member State concerned' => 'trial_compar_multiple_site',
    'Number of sites anticipated in Member State concerned' => 'trial_compar_numb_anticipated',
    'The trial involves multiple Member States' => 'trial_compar_member_states',
    'Number of sites anticipated in the EEA' => 'trial_compar_numb_anticipated_eea',
    'Trial being conducted both within and outside the EEA' => 'trial_outside_inside',
    'Trial being conducted completely outside of the EEA' => 'trial_outside_completely',
    'Trial has a data monitoring committee' => 'trial_datamonitor',
    'Definition of the end of the trial and justification where it is not the last visit of the last subject undergoing the trial' => 'trial_last_subject',
    'In the Member State concerned years' => 'trial_msc_years',
    'In the Member State concerned months' => 'trial_msc_months',
    'In the Member State concerned days' => 'trial_msc_days',
    'In all countries concerned by the trial years' => 'trial_iac_years',
    'In all countries concerned by the trial months' => 'trial_iac_months',
    'In all countries concerned by the trial days' => 'trial_iac_days',
    'Trial has subjects under 18' => 'populat_age_have_u18',
    'In Utero' => 'populat_age_have_utero',
    'Preterm newborn infants (up to gestational age < 37 weeks)' => 'populat_age_have_preterm',
    'Newborns (0-27 days)' => 'populat_age_have_newborn',
    'Infants and toddlers (28 days-23 months)' => 'populat_age_have_toddler',
    'Children (2-11years)' => 'populat_age_have_children',
    'Adolescents (12-17 years)' => 'populat_age_have_adolescent',
    'Adults (18-64 years)' => 'populat_age_have_adults',
    'Elderly (>=65 years)' => 'populat_age_have_elderly',
    'Male' => 'populat_gender_male',
    'Female' => 'populat_gender_female',
    'Healthy volunteers' => 'populat_group_healthy',
    'Patients' => 'populat_group_patients',
    'Specific vulnerable populations' => 'populat_group_vulnerable',
    'Women of childbearing potential not using contraception' => 'populat_group_women_noncontr',
    'Women of child-bearing potential using contraception' => 'populat_group_women_usecontr',
    'Pregnant women' => 'populat_group_pregnant',
    'Nursing women' => 'populat_group_nursing',
    'Emergency situation' => 'populat_group_emergency',
    'Subjects incapable of giving consent personally' => 'populat_group_incapable_consent',
    'Details of subjects incapable of giving consent' => 'populat_group_incapable_consent_details',
    'Details of other specific vulnerable populations' => 'populat_group_others_details',
    'In the member state' => 'populat_planned_memberstate',
    'In the EEA' => 'populat_planned_eea',
    'In the whole clinical trial' => 'populat_planned_wholetrial',
    'Plans for treatment or care after the subject has ended the participation in the trial (if it is different from the expected normal treatment of that condition)' => 'populat_planned_after',
    'Name of Organisation' => 'populat_investigate_name',
    'Network Country' => 'populat_investigate_country',
    'Third Country in which the trial was first authorised' => 'populat_committee_third',
    'First Authorised Third Country' => 'populat_committee_country',
    'Competent Authority Decision' => 'review_authority',
    'Date of Competent Authority Decision or Application withdrawal' => 'review_authority_decision',
    'Ethics Committee Opinion of the trial application' => 'review_ethics_opinion',
    'Ethics Committee Opinion Reason for unfavourable opinion/withdrawl' => 'review_ethics_reason',
    'Date of Ethics Committee Opinion or Application withdrawal' => 'review_ethics_date',
    'End of Trial Status' => 'end_status',
    'Date of the global end of the trial' => 'end_date'
);

// Counters for dups.
$sponsor_counter = -1;
$last_sponsor_process = 0;
$other_counter = 0;
$others_counter = 0;
$country_counter = 0;
$numbers_counter = 0;

// DW
ini_set('max_execution_time', '36000'); //10 hours
ob_implicit_flush(true);
ob_end_flush();

$record_count = 0;

//Globals
$days = 0;
$last_id = 0;
$id_field = 0;



/*
	$query = '  * FROM update_status_fullhistory where status="1" and trial_type="EUDRACT" order by update_id desc limit 1' ;
	$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
	$res = mysql_fetch_array($res) ;

	if ( isset($res['update_id']) )
{
	
	$pid = getmypid();
	$up_id= ((int)$res['update_id']);
	$cid = ((int)$res['update_items_progress']); 
	$maxid = ((int)$res['update_items_total']); 
	$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message=""  WHERE update_id = "' . $up_id .'" ;' ;
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
}
*/




if(isset($_GET['days']) or isset($days_to_fetch))
{
	
	if(isset($_GET['days']))
	{
		$days_to_fetch = (int)$_GET['days'];
	}
	if(isset($days_to_fetch))	//$days_to_fetch comes from cron.php normally
	{
		$days = (int)$days_to_fetch;
	}

	$cron_run = isset($update_id); 	// check if being run by cron.php
	if($cron_run)
	{
		$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", updated_days='.$days.' WHERE update_id="' . $update_id . '"';
		$res = mysql_query($query) or die('Unable to update running' . mysql_error());
	}

	// Get Dates from Dates passed in
	$bd = '-' . $days . ' days';
	$now = time();
	$begin_now = strtotime($bd);
	$end_date = date("Y-m-d", $now);
	$start_date = date("Y-m-d", $begin_now);

	echo("Starting Search From: " . $start_date . " to " . $end_date . "<br>");
}
else
{
	$end_date = "";
	$start_date = "";
	ini_set('max_execution_time', '360000'); //100 hours
//	ignore_user_abort(true);
	echo("Starting full refreshing of EudraCT") ;
}
$Url = "https://www.clinicaltrialsregister.eu/ctr-search/index.xhtml";

//*********
// FIRST CONNECT: GET ID
// ********
// 
$Html = curl_start($Url);

// Get ID
$linesHtml = preg_split('/\n/', $Html);
foreach ($linesHtml as $lineHtml) {
    if (strpos($lineHtml, 'id="javax.faces.ViewState" value="j_id') !== false) {
        $javax = substr($lineHtml, strpos($lineHtml, 'id="javax.faces.ViewState" value="j_id') + 38, 120);
    }
}

unset($linesHtml);
unset($lineHtml);
unset($Html);

//Parse Id
$i = strpos($javax, "\"");
$j = substr($javax, 0, $i);
$javax = $j;

echo("<br>retrieved javax=$javax\n");



//*********
// SECOND CONNECT: GET SEARCH
// ********
// 
// How we have javax id... Now submit search

$url = "https://www.clinicaltrialsregister.eu/ctr-search/index.xhtml?"
		. "ctrSearchForm=ctrSearchForm"
		. "&searchStrId="
		. "&advancedSearchPanel=true"
		. "&countryId="
		. "&populationAgeId="
		. "&trialPhaseId="
		. "&trialStatusId="
		. "&from_calInputDate=" . $start_date
		. "&from_calInputCurrentDate=05%2F2011"
		. "&to_calInputDate=" . $end_date
		. "&to_calInputCurrentDate=05%2F2011"
		. "&searchButtonId=searchButtonId"
		. "&javax.faces.ViewState=j_id" . $javax
		. "&AJAXREQUEST=_viewRoot&";


$Html = curl_start($url);

//$Html = @mb_convert_encoding($Html, 'HTML-ENTITIES', 'utf-8');
//
// First Page get Amount of Pages
$linesHtml = preg_split('/\n/', $Html);
foreach ($linesHtml as $lineHtml) {

	if (strpos($lineHtml, 'Displaying page 1 of') !== false) {
		$pages = substr($lineHtml, strpos($lineHtml, 'Displaying page 1 of ') + 21, 120);
		$i = strpos($pages, ".");
		$pages = substr($pages, 0, $i);
		echo("<br>retrieved pages=$pages<br>");
	}
}

unset($linesHtml);
unset($lineHtml);


// Now Get Links And Process
$doc = new DOMDocument();
for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
	echo('.');
	$done = @$doc->loadHTML($Html);
}
unset($Html);

$tables = $doc->getElementsByTagName('span');
$datatable = NULL;
foreach ($tables as $table) {
	foreach ($table->attributes as $attr) {

		if ($attr->name == 'id' && $attr->value == 'results') {
			$right = true;
			break;
		}
	}
	if ($right == true) {
		$datatable = $table;
		break;
	}
}
if ($datatable == NULL) {
	echo('<br>No Span Results Found.' . "\n<br />");
	exit();
}
unset($tables);

$links = array();
//Now that we found the table, go through its TDs to find the ones with NCTIDs
$tds = $datatable->getElementsByTagName('a');
foreach ($tds as $td) {
	foreach ($td->attributes as $attr) {
		if ($attr->name == 'id') {
			$links[] = $attr->value;
		}
	}
}
unset($datatable);
// Open each Page:
$link_count = count($links);
$links_count=$link_count;
$i = 0;
if($cron_run)
{
	$query = 'UPDATE update_status SET update_items_total="' . $links_count . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
	$res = mysql_query($query) or die('Unable to update running' . mysql_error());
}


// !!!!!!! TESTING PURPOSES ONLY !!!!!!!!!/
//$link_count = 35;

$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="EUDRACT" order by update_id desc limit 1' ;
$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
$res = mysql_fetch_array($res) ;
$newrecord=true;	
if ( isset($res['process_id']) )
{
	$pid = getmypid();
	$pr_id = $pid;
	$up_id= ((int)$res['update_id']);
	$pagei = ((int)$res['update_items_progress']); 
	$maxid = ((int)$res['update_items_total']); 
	$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message="", process_id = "'.$pr_id.'"  WHERE update_id = "' . $up_id .'" ;' ;
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
	$newrecord=false;

}
elseif ( $_GET['pages'] ) $pagei=$_GET['pages'];
else 
{
	$newrecord=true;
	$pagei = 2;
	while ($i < $link_count) {
		$link = $links[$i];
		gotostudy($link);
		$i = $i + 1;
		while ($i < $link_count) {
			$link = $links[$i];
			gotostudy($link);
			if($cron_run)
			{
				$query = 'UPDATE update_status SET updated_time="' ;
				$query .= date('Y-m-d H:i:s', strtotime('now')) ;
				$query.= '",update_items_progress="' ;
				$query.= $i ;
				$query.= '",current_nctid="' ;
				$query.= $i ;
				$query .= '" WHERE update_id="' ;
				$query .= $update_id . '"';
				$res = mysql_query($query) or die('Unable to update running');
			}
			$i = $i + 1;
		}

	}
	unset($links);

	echo("<br>**** Page: 1: Results Links: " . $link_count . " **** <br>");
	echo str_repeat ("   ", 4500);
	

}

// Go to Additional Pages.
	// If Pages > 1 Then have to get the counts of the other pages so load those pages
	// Have to Click the search pages so JIDs will be populated.
	// !!!!!!! TESTING PURPOSES ONLY !!!!!!!!!/
//	$pages = 3;




while ($pagei <= $pages) {
	ini_set('max_execution_time', '36000'); //10 hours
    echo "<br>Starting Page: " . ($pagei) . "<br>";

    $url = "https://www.clinicaltrialsregister.eu/ctr-search/index.xhtml?"
            . "ctrSearchForm=ctrSearchForm"
            . "&searchStrId="
            . "&advancedSearchPanel=true"
            . "&countryId="
            . "&populationAgeId="
            . "&trialPhaseId="
            . "&trialStatusId="
            . "&from_calInputDate=" . $start_date
            . "&from_calInputCurrentDate=05%2F2011"
            . "&to_calInputDate=" . $end_date
            . "&to_calInputCurrentDate=05%2F2011"
            //           . "&searchButtonId=searchButtonId"
            . "&javax.faces.ViewState=j_id" . $javax
            . "&AJAXREQUEST=_viewRoot"
            . "&AJAX:EVENTS_COUNT=1"
            . "&ajaxSingle=resultsTable:scroller"
            . "&resultsTable:scroller=" . $pagei . "&";

    $Html = curl_start($url);

    unset($doc);

    // Now Get Links And Process
    $doc = new DOMDocument();
    for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
        echo('.');
        $done = @$doc->loadHTML($Html);
    }
    unset($Html);

    $links = array();
    /////Now that we found the table, go through its TDs to find the ones with NCTIDs
    $tds = $doc->getElementsByTagName('a');
    foreach ($tds as $td) {
        foreach ($td->attributes as $attr) {
            if ($attr->name == 'id') {
                $links[] = $attr->value;
            }
        }
    }

    unset($datatable);

    // Open each Page:
	$link_count = count($links);
	$links_count=$links_count+$link_count;
    $i = 0;
	if($cron_run)
	{
		$query = 'UPDATE update_status SET update_items_total="' . $links_count . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
		$res = mysql_query($query) or die('Unable to update running' . mysql_error());
	}


    // !!!!!!! TESTING PURPOSES ONLY !!!!!!!!!/
//    $link_count = 35;

    while ($i < $link_count) {
        $link = $links[$i];
        gotostudy($link);
		if($cron_run)
		{
		  	$query = 'UPDATE update_status SET updated_time="' ;
			$query .= date('Y-m-d H:i:s', strtotime('now')) ;
			$query.= '",update_items_progress="' ;
			$query.= $record_count ;
			$query.= '",current_nctid="' ;
			$query.= $record_count ;
			$query .= '" WHERE update_id="' ;
			$query .= $update_id . '"';
	        $res = mysql_query($query) or die('Unable to update running');
		}
		$i = $i + 1;
    }

	unset($links);
	
	if($newrecord)
	{
		$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
		$res = mysql_query($query) or die('Bad SQL query finding highest update id');
		$res = mysql_fetch_array($res) ;
		$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
		$fid = getFieldId('EudraCT','eudract_id');
		$pid = getmypid();
	
		$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,max_nctid,trial_type) 
			  VALUES ("'.$up_id.'","'. $pid .'","'. 2 .'",
			  "' . $pages . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "'. $pages .'", "EUDRACT"  ) ;';
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
		$newrecord=false;
	}
	else
	{
		$query = ' UPDATE  update_status_fullhistory SET update_items_progress= "' . $pagei . '" , status="2", current_nctid="'. $page_i .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" ;' ;
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	}
	

    echo("<br>**** Page: " . $pagei . ": Results Links: " . $link_count . " ****<br>");
    $pagei = $pagei + 1;
}
if($cron_run)
	{
		$query = 'UPDATE update_status SET status="'.COMPLETED.'", updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_complete_time ="' . date("Y-m-d H:i:s", strtotime('now')) . '",   end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", update_items_total="' . $record_count . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
    	$res = mysql_query($query) or die('Unable to update running' . mysql_error());
	}
else
	{
		$query = ' UPDATE  update_status_fullhistory SET status="0",  updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" ;' ;
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	}

echo("<br>Total Processed Count=" . $record_count . "<br>");
echo("Done");

unset($linesHtml);
unset($lineHtml);

function gotostudy($link) {
    global $start_date;
    global $end_date;
    global $javax;
    global $record_count;

    $urlstring = $link;

    $urlCase = "https://www.clinicaltrialsregister.eu/ctr-search/index.xhtml?"
            . "AJAXREQUEST=_viewRoot"
            . "&ctrSearchForm=ctrSearchForm"
            . "&searchStrId="
            . "&advancedSearchPanel=true"
            . "&countryId="
            . "&populationAgeId="
            . "&trialPhaseId="
            . "&trialStatusId="
            . "&from_calInputDate=" . $start_date
            . "&from_calInputCurrentDate=05%2F2011"
            . "&to_calInputDate=" . $end_date
            . "&to_calInputCurrentDate=05%2F2011"
//            . "&searchButtonId=searchButtonId"
            . "&javax.faces.ViewState=j_id" . $javax
            . "&" . $urlstring . "=" . $urlstring . "&";

//    echo "Url-Case: " . $urlCase . "<br>";

    $Html_case = curl_start($urlCase);


    ProcessHtml($Html_case);

    unset($Html_case);
    unset($linesHtml_c);
    unset($lineHtml_c);
    unset($urlCase);
    unset($urlstring);

    $record_count = $record_count + 1;
}

function ProcessHtml($Html) {
    // Find Fields and Values.
    // Field Names are in <td class="cellGrey">
    // Field Values are in <td class="cellLighterGrey">
    // Create Dom
    $doc = new DOMDocument();
    for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
        echo('.');
        $done = @$doc->loadHTML($Html);
    }

    // Main One
    $study = array();

    // Look For FieldName and FieldValue values
    // Need to go tru doc and store FieldName and FieldValues Respectively
    $tds = $doc->getElementsByTagName('td');
    foreach ($tds as $td) {
        foreach ($td->attributes as $attr) {
            if ($attr->name == 'class' && $attr->value == 'cellGrey') {
                $fieldname = $td->nodeValue;
            }
            if ($attr->name == 'class' && $attr->value == 'cellLighterGrey') {
                $fieldvalue = $td->nodeValue;
                $fieldname = preg_replace('/\s+/', ' ', trim($fieldname));
                $dbfieldname = get_mapping($fieldname);
                set_field($study, $dbfieldname, $fieldvalue);
            }
        }
    }
    unset($tds);
    unset($doc);

    echo ("<br>......Storing in DB.....");

    addRecord($study, "eudract");

    $values = sizeof($study, 1);

    echo ("Finished Processing: " . $study['eudract_number'][0] . ", Country: " . $study['competent_authority'][0] . " - Values Parsed: " . $values . "<br>");
    unset($study);
}

function set_field(&$array, $fieldname, $fieldvalue) {
    $fieldvalue = trim($fieldvalue);
    if (!is_array($array[$fieldname])) {
        // Create Array then pop.
        $array[$fieldname] = array();
    } else {
        //echo "Already an array for: " .$fieldname;
    }

    array_push($array[$fieldname], $fieldvalue);
}

function curl_start($url) {

    $cookieFilenameLogin = "/tmp/hypo_login.cookie";

    $headers[] = 'Host: www.clinicaltrialsregister.eu';
    $headers[] = 'User Agent: Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0';
    $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    $headers[] = 'Accept-Language: en-us;q=0.5';
    $headers[] = 'Accept-Encoding: gzip, deflate';
    $headers[] = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
    $headers[] = 'Keep-Alive: 115';
    $headers[] = 'Connection: keep-alive';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSLVERSION, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    //   curl_setopt($ch, CURLOPT_HEADER, true);
//    curl_setopt($ch, CURLOPT_POST, true);
//    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilenameLogin);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilenameLogin);
    curl_setopt($ch, CURLOPT_URL, $url);


    $Html = curl_exec($ch);
    $Html = @mb_convert_encoding($Html, 'HTML-ENTITIES', 'utf-8');

    curl_close($ch);
    unset($ch);

    return $Html;
}

function get_mapping($fieldname) {
    global $mapping;
    global $sponsor_counter;
    global $last_sponsor_process;
    global $other_counter;
    global $others_counter;
    global $country_counter;
    global $numbers_counter;

    $value = $mapping[$fieldname];

    if ($value == "sponsor_name") {
        $sponsor_counter = $sponsor_counter + 1;
    }

    if (!isset($value)) {

        // Then check special multiple cases to return correct fieldname
        // This is for country, other, others, and numbers

        if ($fieldname == "Other") {
            switch ($other_counter) {
                case 0:
                    $value = 'trial_type_other';
                    break;
                case 1:
                    $value = 'trial_design_other';
                    break;
                case 2:
                    $value = 'trial_compar_other';
                    break;
            }
            $other_counter = $other_counter + 1;
        } else if ($fieldname == "Others") {
            switch ($others_counter) {
                case 0:
                    $value = 'trial_scope_others';
                    break;
                case 1:
                    $value = 'populat_group_others';
                    break;
            }
            $others_counter = $others_counter + 1;
        } else if ($fieldname == "Country") {
            // Tricky One To DO

            if ($last_sponsor_process != $sponsor_counter) {
                $last_sponsor_process = $last_sponsor_process + 1;
                $country_counter = 0;
            }

            switch ($country_counter) {
                case 0:
                    $value = 'sponsor_country';
                    break;
                case 1:
                    $value = 'support_org_country';
                    break;
                case 2:
                    $value = 'contact_country';
                    break;
            }

            $country_counter = $country_counter + 1;
        } else if ($fieldname == "Number of subjects for this age range:") {
            switch ($numbers_counter) {
                case 0:
                    $value = 'populat_age_numb_u18';
                    break;
                case 1:
                    $value = 'populat_age_numb_utero';
                    break;
                case 2:
                    $value = 'populat_age_numb_preterm';
                    break;
                case 3:
                    $value = 'populat_age_numb_newborn';
                    break;
                case 4:
                    $value = 'populat_age_numb_toddler';
                    break;
                case 5:
                    $value = 'populat_age_numb_children';
                    break;
                case 6:
                    $value = 'populat_age_numb_adolescent';
                    break;
                case 7:
                    $value = 'populat_age_numb_adults';
                    break;
                case 8:
                    $value = 'populat_age_numb_elderly';
                    break;
            }
            $numbers_counter = $numbers_counter + 1;
        }
    }

    return $value;
}

?>  