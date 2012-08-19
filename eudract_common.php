<?php
require_once 'include.derived.php';
error_reporting(E_ERROR);
$tab = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
$parse_retry=0;
$ignore_fields = array(
	'last_data_entry',
	'last_follow_up',
	'last_follow_up_date_type'
	);

	$ignore_fields=array_flip($ignore_fields);
	$mapping = array(
    'EudraCT Number:' => 'eudract_id',
    'National Competent Authority:' => 'national_competent_authority',
    'Clinical Trial Type:' => 'trial_type',
    'Trial Status:' => 'trial_status',
    'Date on which this record was first entered in the EudraCT database:' => 'firstreceived_date',
    'Member State Concerned' => 'member_state_concerned',
    'Full title of the trial' => 'full_title',
    'Title of the trial for lay people, in easily understood, i.e. non-technical, language' => 'lay_title',
    'Name or abbreviated title of the trial where available' => 'abbr_title',
    'Sponsor\'s protocol code number' => 'sponsor_protocol_code',
    'ISRCTN (International Standard Randomised Controlled Trial) Number' => 'isrctn_id',
    'Trial is part of a Paediatric Investigation Plan' => 'is_pip',
    'EMA Decision number of Paediatric Investigation Plan' => 'pip_emad_number',
    'US NCT (ClinicalTrials.gov registry) number' => 'nct_id',
    'WHO Universal Trial Reference Number (UTRN)' => 'who_utrn',
    'Other Identifier - Name' => 'other_name',
    'Other Identifier - Identifier' => 'other_id',
    'Name of Sponsor' => 'sponsor_name',
	// get_mapping 'Country' => 'sponsor_country',
    'Status of the sponsor' => 'sponsor_status',
    'Name of organisation providing support' => 'support_org_name',
	//get_mapping 'Name of organisat' => 'support_org_country',
    'Name of organisation' => 'contact_org_name',
    'Functional name of contact point' => 'contact_point_func_name',
    'Street Address' => 'street_address',
    'Town/ city' => 'city',
    'Post code' => 'postcode',
    'Telephone number' => 'phone',
    'Fax number' => 'fax',
    'E-mail' => 'email',
    'IMP Role' => 'imp_role',
    'IMP to be used in the trial has a marketing authorisation' => 'imp_auth',
    'Trade name' => 'imp_trade_name',
    'Name of the Marketing Authorisation holder' => 'marketing_auth_holder',
    'Country which granted the Marketing Authorisation' => 'marketing_auth_country',
    'The IMP has been designated in this indication as an orphan drug in the Community' => 'imp_orphan',
    'Orphan drug designation number' => 'imp_orphan_number',
    'Product name' => 'product_name',
    'Product code' => 'product_code',
    'Pharmaceutical form' => 'product_pharm_form',
    'Specific paediatric formulation' => 'product_paediatric_form',
    'Routes of administration for this IMP' => 'product_route',
    'INN - Proposed INN' => 'inn',
    'CAS number' => 'cas',
    'Current sponsor code' => 'sponsor_code',
    'Other descriptive name' => 'other_desc_name',
    'EV Substance Code' => 'ev_code',
    'Concentration unit' => 'concentration_unit',
    'Concentration type' => 'concentration_type',
    'Concentration number' => 'concentration_number',
    'Active substance of chemical origin' => 'imp_active_chemical',
    'Active substance of biological/ biotechnological origin (other than Advanced Therapy IMP (ATIMP)' => 'imp_active_bio',
    'Advanced Therapy IMP (ATIMP)' => 'type_at',
    'Somatic cell therapy medicinal product' => 'type_somatic_cell',
    'Gene therapy medical product' => 'type_gene',
    'Tissue Engineered Product' => 'type_tissue',
    'Combination ATIMP (i.e. one involving a medical device)' => 'type_combo_at',
    'CAT classification and reference number' => 'type_cat_class',
    'CAT classification and reference number' => 'type_cat_number',
    'Combination product that includes a device, but does not involve an Advanced Therapy' => 'type_combo_device_not_at',
    'Radiopharmaceutical medicinal product' => 'type_radio',
    'Immunological medicinal product (such as vaccine, allergen, immune serum)' => 'type_immune',
    'Plasma derived medicinal product' => 'type_plasma',
    'Extractive medicinal product' => 'type_extract',
    'Recombinant medicinal product' => 'type_recombinant',
    'Medicinal product containing genetically modified organisms' => 'type_gmo',
    'Herbal medicinal product' => 'type_herbal',
    'Homeopathic medicinal product' => 'type_homeopathic',
    'Another type of medicinal product' => 'type_other',
    'Other medicinal product type' => 'type_other_name',
    'Is a Placebo used in this Trial?' => 'placebo_used',
    'Pharmaceutical form of the placebo' => 'placebo_form',
    'Route of administration of the placebo' => 'placebo_route',
    'Medical condition(s) being investigated' => 'condition',
    'Medical condition in easily understood language' => 'lay_condition',
    'Therapeutic area' => 'therapeutic_area',
    'Version' => 'dra_version',
    'Level' => 'dra_level',
    'Classification code' => 'dra_code',
	//'Term' => 'dra_term',
    'System Organ Class' => 'dra_class',
    'Condition being studied is a rare disease' => 'dra_rare',
    'Main objective of the trial' => 'main_objective',
    'Secondary objectives of the trial' => 'secondary_objective',
    'Trial contains a sub-study' => 'has_sub_study',
    'Full title, date and version of each sub-study and their related objectives' => 'sub_studies',
    'Principal inclusion criteria' => 'inclusion_criteria',
    'Principal exclusion criteria' => 'exclusion_criteria',
    'Primary end point(s)' => 'primary_endpoint',
    'Timepoint(s) of evaluation of this end point' => 'primary_endpoint_timeframe',
    'Secondary end point(s)' => 'secondary_endpoint',
    'Timepoint(s) of evaluation of this end point' => 'secondary_endpoint_timeframe',
    'Diagnosis' => 'scope_diagnosis',
    'Prophylaxis' => 'scope_prophylaxis',
    'Therapy' => 'scope_therapy',
    'Safety' => 'scope_safety',
    'Efficacy' => 'scope_efficacy',
    'Pharmacokinetic' => 'scope_pharmacokinectic',
    'Pharmacodynamic' => 'scope_pharmacodynamic',
    'Bioequivalence' => 'scope_bioequivalence',
    'Dose response' => 'scope_dose_response',
    'Pharmacogenetic' => 'scope_pharmacogenetic',
    'Pharmacogenomic' => 'scope_pharmacogenomic',
    'Pharmacoeconomic' => 'scope_pharmacoeconomic',
	//'Other scope of the trial description' => 'scope_other',
    'Other scope of the trial description' => 'scope_other_description',
    'Human pharmacology (Phase I)' => 'tp_phase1_human_pharmacology',
    'First administration to humans' => 'tp_first_administration_humans',
    'Bioequivalence study' => 'tp_bioequivalence_study',
	//get_mapping 'Other trial type description' => 'tp_other',
    'Other trial type description' => 'tp_other_description',
    'Therapeutic exploratory (Phase II)' => 'tp_phase2_explatory',
    'Therapeutic confirmatory (Phase III)' => 'tp_phase3_confirmatory',
    'Therapeutic use (Phase IV)' => 'tp_phase4_use',
    'Controlled' => 'design_controlled',
    'Randomised' => 'design_randomised',
    'Open' => 'design_open',
    'Single blind' => 'design_single_blind',
    'Double blind' => 'design_double_blind',
    'Parallel group' => 'design_parallel_group',
    'Cross over' => 'design_crossover',
	//get_mapping 'Other trial design description' => 'design_other',
    'Other trial design description' => 'design_other_description',
    'Other medicinal product(s)' => 'comp_other_products',
    'Placebo' => 'comp_placebo',
	//get_mapping 'Comparator description' => 'comp_other',
    'Comparator description' => 'comp_descr',
    'Number of treatment arms in the trial' => 'comp_number_arms',
    'The trial involves single site in the Member State concerned' => 'single_site',
    'The trial involves multiple sites in the Member State concerned' => 'multi_site',
    'Number of sites anticipated in Member State concerned' => 'number_of_sites',
    'The trial involves multiple Member States' => 'multiple_member_state',
    'Number of sites anticipated in the EEA' => 'number_sites_eea',
    'Trial being conducted both within and outside the EEA' => 'eea_both_inside_outside',
    'Trial being conducted completely outside of the EEA' => 'eea_outside_only',
    'If E.8.6.1 or E.8.6.2 are Yes, specify the regions in which trial sites are planned' => 'eea_inside_outside_regions',
    'Trial has a data monitoring committee' => 'has_data_mon_comm',
    'Definition of the end of the trial and justification where it is not the last visit of the last subject undergoing the trial' => 'definition_of_end',
    'In the Member State concerned years' => 'dur_est_member_years',
    'In the Member State concerned months' => 'dur_est_member_months',
    'In the Member State concerned days' => 'dur_est_member_days',
    'In all countries concerned by the trial years' => 'dur_est_all_years',
    'In all countries concerned by the trial months' => 'dur_est_all_months',
    'In all countries concerned by the trial days' => 'dur_est_all_days',
    'Trial has subjects under 18' => 'age_has_under18',
	//'Trial has subjects under 18' => 'age_number_under18'
    'In Utero' => 'age_has_in_utero',
	//'In Utero' => 'age_number_in_utero',
    'Preterm newborn infants (up to gestational age < 37 weeks)' => 'age_has_preterm_newborn',
	//'Preterm newborn infants (up to gestational age < 37 weeks)' => 'age_number_preterm_newborn',
    'Newborns (0-27 days)' => 'age_has_newborn',
	//'Newborns (0-27 days)' => 'age_number_newborn',
    'Infants and toddlers (28 days-23 months)' => 'age_has_infant_toddler',
	//Infants and toddlers (28 days-23 months)' => 'age_number_infant_toddler',
    'Children (2-11years)' => 'age_has_children',
	//'Children (2-11years)' => 'age_number_children',
    'Adolescents (12-17 years)' => 'age_has_adolescent',
	//Adolescents (12-17 years)' => 'age_number_adolescent',
    'Adults (18-64 years)' => 'age_has_adult',
	//'Adults (18-64 years)' => 'age_number_adult',
    'Elderly (>=65 years)' => 'age_has_elderly',
	//'Elderly (>=65 years)' => 'age_number_elderly',
    'Male' => 'gender_male',
    'Female' => 'gender_female',
    'Healthy volunteers' => 'subjects_healthy_volunteers',
    'Patients' => 'subjects_patients',
    'Specific vulnerable populations' => 'subjects_vulnerable',
    'Women of childbearing potential not using contraception' => 'subjects_childbearing_no_contraception',
    'Women of child-bearing potential using contraception' => 'subjects_childbearing_with_contraception',
    'Pregnant women' => 'subjects_pregnant',
    'Nursing women' => 'subjects_nursing',
    'Emergency situation' => 'subjects_emergency',
    'Subjects incapable of giving consent personally' => 'subjects_incapable_consent',
    'Details of subjects incapable of giving consent' => 'subjects_incapable_consent_details',
    'Details of other specific vulnerable populations' => 'subjects_other',
	//'Details of other specific vulnerable populations' => 'subjects_other_details',
    'In the member state' => 'enrollment_memberstate',
    'In the EEA' => 'enrollment_intl_eea',
    'In the whole clinical trial' => 'enrollment_intl_all',
    'Plans for treatment or care after the subject has ended the participation in the trial (if it is different from the expected normal treatment of that condition)' => 'aftercare',
    'Name of Organisation' => 'inv_network_org',
    'Network Country' => 'inv_network_country',
    'Third Country in which the trial was first authorised' => 'committee_third_first_auth',
    'First Authorised Third Country' => 'committee_first_auth_third',
    'Competent Authority Decision' => 'review_decision',
    'Date of Competent Authority Decision or Application withdrawal' => 'review_decision_date',
    'Ethics Committee Opinion of the trial application' => 'review_opinion',
    'Ethics Committee Opinion Reason for unfavourable opinion/withdrawl' => 'review_opinion_reason',
    'Date of Ethics Committee Opinion or Application withdrawal' => 'review_opinion_date',
    'End of Trial Status' => 'end_status',
    'Date of the global end of the trial' => 'end_date_global'
	);


	$level = array();
	//prefetch recurring derived fields' calculation data.
	$fieldIDArr = calculateDateFieldIds();
	$fieldITArr = calculateInstitutionTypeFieldIds();
	$fieldRArr = calculateRegionFieldIds();
	$fieldCRITArr = calculateCriteriaFieldIds();


	//returned array maps the IDs to lastchanged dates
	function unpadeudra($val)
	{
		$eudra_number = substr($val, strpos($val, 'EudraCT Number: ') + strlen('EudraCT Number: '));
		return $eudra_number;
	}
	function getEudraIDs($single_id = NULL) {
		global $days;
		$end_date = date("Y-m-d", strtotime('now'));
		$start_date = date("Y-m-d", strtotime('-' . $days . ' days'  ));
        
		global $eudract_last_updated_date;
		$eudract_last_updated_date = $end_date;

		$url = "https://www.clinicaltrialsregister.eu/ctr-search/search?query=" .
        "&dateFrom=" . $start_date . "&dateTo=" . $end_date ;
		
		if(!is_null($single_id))
		{
			$url = "https://www.clinicaltrialsregister.eu/ctr-search/search?query=" . $single_id;
        }

		$Html = curl_start($url);
		$linesHtml = preg_split('/\n/', $Html);
		$pages = 1;
		foreach ($linesHtml as $lineHtml) {

			if (strpos($lineHtml, 'Displaying page 1 of') !== false) {
				$pages = substr($lineHtml, strpos($lineHtml, 'Displaying page 1 of ') + 21, 120);
				$i = strpos($pages, ".");
				$pages = substr($pages, 0, $i);
				echo("<br>Retrieved pages=$pages<br>");
				break;
			}
		}

		unset($linesHtml);
		unset($lineHtml);

		$ids = array();


		for ($page = 1; $page <= $pages; ++$page) {
			$fake = mysql_query('SELECT larvol_id FROM clinical_study LIMIT 1'); //keep alive
			@mysql_fetch_array($fake);
			//load search page and see if it has results, or if we've reached the end of results for the search
			$url = "https://www.clinicaltrialsregister.eu/ctr-search/search?query=" .
        "&dateFrom=" . $start_date . "&dateTo=" . $end_date . "&page=" . $page ;
			
		if(!is_null($single_id))
		{
			$url = "https://www.clinicaltrialsregister.eu/ctr-search/search?query=" . $single_id;
        }

			$Html = curl_start($url);


			$doc = new DOMDocument();
			for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
				echo('.');
				$done = @$doc->loadHTML($Html);
			}
			unset($Html);
			$tables = $doc->getElementsByTagName('table');
			$datatable = NULL;
			$pageids = array();
			foreach ($tables as $table) {
				$right = false;
				foreach ($table->attributes as $attr) {
					if ($attr->name == 'class' && $attr->value == 'result') {
						$right = true;
						break;
					}
				}
				if ($right == true) {
					$datatable = $table;
				}
				else
				{
					continue;
				}
				 
				//Now that we found the table, go through its TDs to find the ones with NCTIDs
				$tds = $datatable->getElementsByTagName('td');

				//$countries = array();
				$eudra_number='';
				foreach ($tds as $td) {
					 
					$number_pos = strpos($td->nodeValue,'EudraCT Number:');
					$country_pos = strpos($td->nodeValue,'Country:');
					//start date
					$startdate = strpos($td->nodeValue,'Start Date');
					
					if($startdate === false) 
					{

					}
					else 
					{
						$pageids[$eudra_number]['start_date']=substr(trim($td->nodeValue),-10);
					}

					if($number_pos === false) {

					}
					else {
						$eudra_number=trim($td->nodeValue);
						$eudra_number = unpadeudra($eudra_number);
						//$eudra_number = substr($eudra_number, strpos($eudra_number, 'EudraCT Number: ') + strlen('EudraCT Number: '));
						$pageids[$eudra_number] = array();

					}
					 
					if($country_pos === false) {

					}
					else {
						$hrefs = $td->getElementsByTagName('a');
						foreach ($hrefs as $href) {
							$pageids[$eudra_number][] = trim($href->nodeValue);
						}
						 
					}
					
					 
				}
			}
			unset($tables);
			echo('Page ' . $page . ': ' . implode(', ', array_keys($pageids)) . "\n<br />");
			$ids = array_merge($ids, $pageids);
		}
		return $ids;
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
						$value = 'tp_other';
						break;
					case 1:
						$value = 'design_other';
						break;
					case 2:
						$value = 'comp_other';
						break;
				}
				$other_counter = $other_counter + 1;
			} else if ($fieldname == "Others") {
				switch ($others_counter) {
					case 0:
						$value = 'scope_other';
						break;
					case 1:
						$value = 'subjects_other';
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
						$value = 'age_number_under18';
						break;
					case 1:
						$value = 'age_number_in_utero';
						break;
					case 2:
						$value = 'age_number_preterm_newborn';
						break;
					case 3:
						$value = 'age_number_newborn';
						break;
					case 4:
						$value = 'age_number_infant_toddler';
						break;
					case 5:
						$value = 'age_number_children';
						break;
					case 6:
						$value = 'age_number_adolescent';
						break;
					case 7:
						$value = 'age_number_adult';
						break;
					case 8:
						$value = 'age_number_elderly';
						break;
				}
				$numbers_counter = $numbers_counter + 1;
			}
		}

		return $value;
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

	function ProcessHtml($Html,&$study) {
		// Find Fields and Values.
		// Field Names are in <td class="cellGrey">
		// Field Values are in <td class="cellLighterGrey">
		// Create Dom
		global $current_country;
		$country_fields=array();
		$doc = new DOMDocument();
		for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
			echo('.');
			$done = @$doc->loadHTML($Html);
		}

		

		// Look For FieldName and FieldValue values
		// Need to go tru doc and store FieldName and FieldValues Respectively
		$divs = $doc->getElementsByTagName('div');
		foreach ($divs as $div) 
		{
			$found = false;
			foreach ($div->attributes as $divattr) 
			{
				if ($divattr->name == 'class' && $divattr->value == 'detail') 
				{
					$found = true;
					break;
				}
			}
			if(!$found)
			{
				continue;
			}
			$tds = $div->getElementsByTagName('td');
			global $current_country;
			foreach ($tds as $td) 
			{
				foreach ($td->attributes as $attr) 
				{
					if ($attr->name == 'class' && $attr->value == 'cellGrey') 
					{
						$fieldname = $td->nodeValue;
					}
					if ($attr->name == 'class' && $attr->value == 'cellLighterGrey') 
					{
						$fieldvalue = $td->nodeValue;
						$fieldname = preg_replace('/\s+/', ' ', trim($fieldname));
						$dbfieldname = get_mapping($fieldname);
						set_field($study, $dbfieldname, $fieldvalue);
						$country_fields[$current_country][$dbfieldname]=1;
					}
					if ($attr->name == 'class' && $attr->value == 'second') 
					{
						$fieldname = $td->nodeValue;
					}
					if ($attr->name == 'class' && $attr->value == 'third') 
					{
						$fieldvalue = $td->nodeValue;
						$fieldname = preg_replace('/\s+/', ' ', trim($fieldname));
						$dbfieldname = get_mapping($fieldname);
						/** Filter out all non-English text from fields that usually contain them.  */
						$dbx=trim($dbfieldname);
						//fields that may contain non-English text.
						$field_regex='/lay_title|full_title|product_name|product_code|product_pharm_form|imp_trade_name|condition|lay_condition|therapeutic_area/i';
						preg_match_all($field_regex, $dbx, $matches);
						if(count($matches[0]) >0 and isset($dbx) and !empty($dbx) )
						{
							global $current_country;
							//regex of countries that produce only English text(can add add more to the list if required).
							$english_countries='/GB|LI/i';
							preg_match_all($english_countries, $current_country, $matches2);
						
							if(!isset($study[$dbx]) or empty($study[$dbx])) 
							{
								set_field($study, $dbfieldname, $fieldvalue);
								$country_fields[$current_country][$dbfieldname]=1;
							}
							elseif(count($matches2[0]) >0)
							{
								if(!isset($country_fields[$current_country][$dbfieldname]) or $country_fields[$current_country][$dbfieldname]<>1) 
								{
									unset($study[$dbx]);
								}
								set_field($study, $dbfieldname, $fieldvalue);
								$country_fields[$current_country][$dbfieldname]=1;
							}
							elseif(isset($country_fields[$current_country][$dbfieldname]) and $country_fields[$current_country][$dbfieldname]==1)
							{
								set_field($study, $dbfieldname, $fieldvalue);
								$country_fields[$current_country][$dbfieldname]=1;
							}
						}
						else
						{
							set_field($study, $dbfieldname, $fieldvalue);
						}
					}
				}
			}
		}
		unset($tds);
		unset($doc);

		$values = sizeof($study, 1);
        
		return $study;
	}
	
	function GetCountry($country)
	{
		if($country == 'Outside EU/EEA (PIP Studies)')
		{
			return '3rd';
		}
		return $country;
	}

	function ProcessNew($id, $countries,$startdate) {
		global $parse_retry;
		global $logger;
		echo "<hr>Processing new Record " . $id . "<br/>";

		//$combo = split(' - ', $id);

		//$eudract_number = unpadeudra($id);
		$eudract_number = $id;
		// Main One
		$study = array();
		foreach ($countries as $country)
		{
			$country_val = GetCountry($country);
			$url = "https://www.clinicaltrialsregister.eu/ctr-search/trial/" .
			$eudract_number . "/" . $country_val ;
			$Html = curl_start($url);
			global $current_country;
			$current_country=$country;
			ProcessHtml($Html, $study);
			echo ("Finished Processing: " . $study['eudract_id'][0] . ", Country: " . $country . "<br>");
			unset($Html);
		}


		echo ("<br>......Storing in DB.....");

		if (addRecord($study, "eudract",$startdate) === false) {
			echo('Import failed for this record.' . "\n<br />");
		} else {
			echo('Record imported.' . "\n<br />");
		}
		 
		echo ("Finished Processing: " . $study['eudract_id'][0] .  " for all countries <br>");
		unset($study);
       
	}

		
	?>
