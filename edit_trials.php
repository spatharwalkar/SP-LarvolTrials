<style type="text/css">
	hr.pme-hr		     { border: 0px solid; padding: 0px; margin: 0px; border-top-width: 1px; height: 1px; }
	table.pme-main 	     { border: #004d9c 1px solid; border-collapse: collapse; border-spacing: 0px; width: 100%; }
	table.pme-navigation { border: #004d9c 0px solid; border-collapse: collapse; border-spacing: 0px; width: 100%; }
	td.pme-navigation-0, td.pme-navigation-1 { white-space: nowrap; }
	th.pme-header	     { border: #004d9c 1px solid; padding: 4px; background: #add8e6; }
	td.pme-key-0, td.pme-value-0, td.pme-help-0, td.pme-navigation-0, td.pme-cell-0,
	td.pme-key-1, td.pme-value-1, td.pme-help-0, td.pme-navigation-1, td.pme-cell-1,
	td.pme-sortinfo, td.pme-filter { border: #004d9c 1px solid; padding: 3px; }
	td.pme-buttons { text-align: left;   }
	td.pme-message { text-align: center; }
	td.pme-stats   { text-align: right;  }
</style><?php

require_once('db.php');
if(isset($_GET['larvol_id']))
{
$_GET['PME_sys_operation']='PME_op_View';
$_GET['PME_sys_rec']=$_GET['larvol_id'];
}

global $db;

$opts['dbh'] = $db->db_link;
$opts['tb'] = 'data_trials';
$opts['key'] = 'larvol_id';
$opts['key_type'] = 'int';
$opts['sort_field'] = array('larvol_id');
$opts['inc'] = 10;
$opts['options'] = 'ACVFI';
$opts['multiple'] = '4';
$opts['navigation'] = 'G';
$opts['display'] = array(
	'form'  => true,
	'query' => false,
	'sort'  => false,
	'time'  => false,
	'tabs'  => false
);

$opts['js']['prefix']               = 'PME_js_';
$opts['dhtml']['prefix']            = 'PME_dhtml_';
$opts['cgi']['prefix']['operation'] = 'PME_op_';
$opts['cgi']['prefix']['sys']       = 'PME_sys_';
$opts['cgi']['prefix']['data']      = 'PME_data_';
$opts['language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'] . '-UTF8';

$opts['fdd']['larvol_id'] = array(
  'name'     => 'Larvol ID',
  'select'   => 'T',
  'options'  => 'LAVCPDR', // auto increment
  'maxlen'   => 10,
  'default'  => '0',
  'sort'     => false
);
$opts['fdd']['source_id'] = array(
  'name'     => 'Source ID',
  'options'  => 'LAVPD', 
  'select'   => 'T',
  'maxlen'   => 63,
  'sort'     => false
);
$opts['fdd']['brief_title'] = array(
  'name'     => 'Brief title',
  'select'   => 'T',
  'maxlen'   => 255,
  'sort'     => false
);
$opts['fdd']['acronym'] = array(
  'name'     => 'Acronym',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD',
  'sort'     => false
);
$opts['fdd']['official_title'] = array(
  'name'     => 'Official title',
  'select'   => 'T',
  'maxlen'   => 65535,
  'options'  => 'AVCPD',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'sort'     => false
);
$opts['fdd']['lead_sponsor'] = array(
  'name'     => 'Lead sponsor',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD',
  'sort'     => false
);
$opts['fdd']['collaborator'] = array(
  'name'     => 'Collaborator',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD',
  'sort'     => false
);
$opts['fdd']['institution_type'] = array(
  'name'     => 'Institution type',
  'select'   => 'T',
  'maxlen'   => 21,
  'options'  => 'AVCPD',
  'values'   => array(
                  "industry_lead_sponsor",
                  "industry_collaborator",
                  "coop",
                  "other"),
  'default'  => 'other',
  'sort'     => false
);
$opts['fdd']['source'] = array(
  'name'     => 'Source',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['has_dmc'] = array(
  'name'     => 'Has dmc',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['brief_summary'] = array(
  'name'     => 'Brief summary',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['detailed_description'] = array(
  'name'     => 'Detailed description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['overall_status'] = array(
  'name'     => 'Overall status',
  'select'   => 'T',
  'maxlen'   => 25,
  'values'   => array(
                  "Not yet recruiting",
                  "Recruiting",
                  "Enrolling by invitation",
                  "Active, not recruiting",
                  "Completed",
                  "Suspended",
                  "Terminated",
                  "Withdrawn",
                  "Available",
                  "No Longer Available",
                  "Approved for marketing",
                  "No longer recruiting",
                  "Withheld",
                  "Temporarily Not Available"),
  'default'  => 'Not yet recruiting',
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['is_active'] = array(
  'name'     => 'Is active',
  'select'   => 'T',
  'maxlen'   => 1,
  'default'  => '1',
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['why_stopped'] = array(
  'name'     => 'Why stopped',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['start_date'] = array(
  'name'     => 'Start date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['end_date'] = array(
  'name'     => 'End date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['study_type'] = array(
  'name'     => 'Study type',
  'select'   => 'T',
  'maxlen'   => 15,
  'values'   => array(
                  "Interventional",
                  "Observational",
                  "Expanded Access",
                  "N/A"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['study_design'] = array(
  'name'     => 'Study design',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['number_of_arms'] = array(
  'name'     => 'Number of arms',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['number_of_groups'] = array(
  'name'     => 'Number of groups',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['enrollment'] = array(
  'name'     => 'Enrollment',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['enrollment_type'] = array(
  'name'     => 'Enrollment type',
  'select'   => 'T',
  'maxlen'   => 11,
  'values'   => array(
                  "Actual",
                  "Anticipated"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['biospec_retention'] = array(
  'name'     => 'Biospec retention',
  'select'   => 'T',
  'maxlen'   => 19,
  'values'   => array(
                  "None Retained",
                  "Samples With DNA",
                  "Samples Without DNA"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['biospec_descr'] = array(
  'name'     => 'Biospec descr',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['study_pop'] = array(
  'name'     => 'Study pop',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['sampling_method'] = array(
  'name'     => 'Sampling method',
  'select'   => 'T',
  'maxlen'   => 22,
  'values'   => array(
                  "Probability Sample",
                  "Non-Probability Sample"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['criteria'] = array(
  'name'     => 'Criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['inclusion_criteria'] = array(
  'name'     => 'Inclusion criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'options'  => 'AVCPDR',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
	'sort'     => false
);
$opts['fdd']['exclusion_criteria'] = array(
  'name'     => 'Exclusion criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'options'  => 'AVCPDR',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'sort'     => false
);
$opts['fdd']['gender'] = array(
  'name'     => 'Gender',
  'select'   => 'T',
  'maxlen'   => 6,
  'values'   => array(
                  "Male",
                  "Female",
                  "Both"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['minimum_age'] = array(
  'name'     => 'Minimum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['maximum_age'] = array(
  'name'     => 'Maximum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['healthy_volunteers'] = array(
  'name'     => 'Healthy volunteers',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['verification_date'] = array(
  'name'     => 'Verification date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['lastchanged_date'] = array(
  'name'     => 'Lastchanged date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['firstreceived_date'] = array(
  'name'     => 'Firstreceived date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['responsible_party_name_title'] = array(
  'name'     => 'Responsible party name title',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['responsible_party_organization'] = array(
  'name'     => 'Responsible party organization',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['org_study_id'] = array(
  'name'     => 'Org study ID',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['phase'] = array(
  'name'     => 'Phase',
  'select'   => 'T',
  'maxlen'   => 5,
  'values'   => array(
                  "N/A",
                  "0",
                  "0/1",
                  "1",
                  "1a",
                  "1b",
                  "1a/1b",
                  "1c",
                  "1/2",
                  "1b/2",
                  "1b/2a",
                  "2",
                  "2a",
                  "2a/2b",
                  "2b",
                  "2/3",
                  "2b/3",
                  "3",
                  "3a",
                  "3b",
                  "3/4",
                  "3b/4",
                  "4"),
  'default'  => 'N/A',
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['condition'] = array(
  'name'     => 'Condition',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['secondary_id'] = array(
  'name'     => 'Secondary ID',
  'select'   => 'T',
  'maxlen'   => 63,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['oversight_authority'] = array(
  'name'     => 'Oversight authority',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['arm_group_label'] = array(
  'name'     => 'Arm group label',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['arm_group_type'] = array(
  'name'     => 'Arm group type',
  'select'   => 'T',
  'maxlen'   => 20,
  'values'   => array(
                  "Experimental",
                  "Active Comparator",
                  "Placebo Comparator",
                  "Sham Comparator",
                  "No Intervention",
                  "Other",
                  "Case",
                  "Control",
                  "Treatment Comparison",
                  "Exposure Comparison"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['arm_group_description'] = array(
  'name'     => 'Arm group description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['intervention_type'] = array(
  'name'     => 'Intervention type',
  'select'   => 'T',
  'maxlen'   => 36,
  'values'   => array(
                  "Behavioral",
                  "Drug",
                  "Device",
                  "Biological",
                  "Biological/Vaccine",
                  "Vaccine",
                  "Genetic",
                  "Radiation",
                  "Procedure",
                  "Procedure/Surgery",
                  "Procedure/Surgery Dietary Supplement",
                  "Dietary Supplement",
                  "Gene Transfer",
                  "Therapy",
                  "Other"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['intervention_name'] = array(
  'name'     => 'Intervention name',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['intervention_other_name'] = array(
  'name'     => 'Intervention other name',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['intervention_description'] = array(
  'name'     => 'Intervention description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['primary_outcome_measure'] = array(
  'name'     => 'Primary outcome measure',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['primary_outcome_timeframe'] = array(
  'name'     => 'Primary outcome timeframe',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['primary_outcome_safety_issue'] = array(
  'name'     => 'Primary outcome safety issue',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['secondary_outcome_measure'] = array(
  'name'     => 'Secondary outcome measure',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['secondary_outcome_timeframe'] = array(
  'name'     => 'Secondary outcome timeframe',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['secondary_outcome_safety_issue'] = array(
  'name'     => 'Secondary outcome safety issue',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['location_name'] = array(
  'name'     => 'Location name',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['location_city'] = array(
  'name'     => 'Location city',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['location_state'] = array(
  'name'     => 'Location state',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['location_zip'] = array(
  'name'     => 'Location zip',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['location_country'] = array(
  'name'     => 'Location country',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['region'] = array(
  'name'     => 'Region',
  'select'   => 'T',
  'maxlen'   => 255,
  'default'  => 'RestOfWorld',
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['location_status'] = array(
  'name'     => 'Location status',
  'select'   => 'T',
  'maxlen'   => 25,
  'values'   => array(
                  "Not yet recruiting",
                  "Recruiting",
                  "Enrolling by invitation",
                  "Active, not recruiting",
                  "Completed",
                  "Suspended",
                  "Terminated",
                  "Withdrawn",
                  "Available",
                  "No Longer Available",
                  "Approved for marketing",
                  "No longer recruiting",
                  "Withheld",
                  "Temporarily Not Available"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['investigator_name'] = array(
  'name'     => 'Investigator name',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['investigator_role'] = array(
  'name'     => 'Investigator role',
  'select'   => 'T',
  'maxlen'   => 22,
  'values'   => array(
                  "Principal Investigator",
                  "Sub-Investigator",
                  "Study Chair",
                  "Study Director"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['overall_official_name'] = array(
  'name'     => 'Overall official name',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['overall_official_role'] = array(
  'name'     => 'Overall official role',
  'select'   => 'T',
  'maxlen'   => 22,
  'values'   => array(
                  "Principal Investigator",
                  "Sub-Investigator",
                  "Study Chair",
                  "Study Director"),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['overall_official_affiliation'] = array(
  'name'     => 'Overall official affiliation',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['keyword'] = array(
  'name'     => 'Keyword',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['is_fda_regulated'] = array(
  'name'     => 'Is fda regulated',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['is_section_801'] = array(
  'name'     => 'Is section 801',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
require_once 'phpMyEdit.class.php';
new phpMyEdit($opts);

?>

