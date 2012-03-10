<?php
require_once('db.php');
require_once('include.util.php');
//ini_set('error_reporting', E_ALL ^ E_NOTICE);
global $logger;
/*
if(!isset($_POST['PME_sys_operation']) and !isset($_GET['larvol_id'])) 
{
	$query = 'SELECT substring(source_id,5) as nctid from data_trials order by larvol_id limit 50';
	$res1 		= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query getting nctids from data_trials . Query=' . $query;
		$logger->fatal($log);
		echo $log;
		die($log);
	}
	$nctIds=array();
	while($x=mysql_fetch_assoc($res1))
	{
//		pr($x);
		$nctIds[0][]=$x['nctid'];
	}
//	pr($x);
//	pr($nctIds);
//	exit;
	?>
		<form name="formed" method="post" action="intermediary.php?id=0" >
	<?
		foreach ($nctIds as $nct)
		{
			foreach ($nct as $key => $value)
			{
				echo '<input type="hidden" name="nctids[0][' . $key . ']" value="'. $value .'">';
			}
		}
	?>	
		<input type="hidden" name="id" value="1">
		<input type="hidden" name="edittrials" value="1">
	
		 <script language="javascript" type="text/javascript">
		document.formed.submit();

		</script>
		<input type="submit" value="verify submit">
		</form>
     	<?
}
*/
?>


<?
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once 'include.page.php';
global $logger;
$table = 'products';
$script = 'edit_trials';
		
require('header.php');	
global $db;
?>
<script type="text/javascript">
<?
if(isset($_REQUEST['id']))	//load search from Saved Search
{
	$id = ($_REQUEST['id'])?$_REQUEST['id']:null;
	$searchDbData = getSearchData($table, 'searchdata', $id);
	//$show_value = 'loadQueryData("' . $data . '");';
	//$show_value = "loadQueryData('" . $searchDbData . "');";
	$show_value = "searchDbData = '" . $searchDbData . "';";
	echo($show_value);

}
else
{
	$show_value = "searchDbData = '';";
	echo($show_value);
}		

?>
function upmdelsure(){ return confirm("Are you sure you want to delete this product?"); }
$(document).ready(function(){
	var options, a,b;

	jQuery(function(){
	  options = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'name'} };
	  if($('#PME_data_intervention_name').length>=0)
	  a = $('#PME_data_intervention_name').autocomplete(options);
	  b = $('#name').autocomplete(options);
	});
	$(".ajax").colorbox({
		onComplete:function(){ loadQueryData($('#searchdata').val());},
		onClosed:function(){ newSearch(); },
		inline:true, 
		width:"100%",
		height:"100%"
			});
	$("#inline_outer").hide();
});
</script>
<style type="text/css">
	hr.pme-hr		     { border: 0px solid; padding: 0px; margin: 0px; border-top-width: 1px; height: 1px; }
	table.pme-main 	     { border: #004d9c 1px solid; border-collapse: collapse; border-spacing: 0px; width: 100%; }
	table.pme-navigation { border: #004d9c 0px solid; border-collapse: collapse; border-spacing: 0px; width: 100%; }
	td.pme-navigation-0, td.pme-navigation-1 { white-space: nowrap; }
	th.pme-header	     { border: #004d9c 1px solid; padding: 4px; background: #add8e6; }
	td.pme-key-5, td.pme-value-0, td.pme-help-0, td.pme-navigation-0, td.pme-cell-0,
	td.pme-key-1, td.pme-value-1, td.pme-help-0, td.pme-navigation-1, td.pme-cell-1,
	td.pme-sortinfo, td.pme-filter { border: #0000ff 1px solid; padding: 1px; 
	vertical-align:top;
	height:10px;
	overflow:hidden;
	white-space:nowrap
	padding-top:0;
	margin:0;
	}
	tr.pme-key-5, tr.pme-value-0, tr.pme-help-0, tr.pme-navigation-0, tr.pme-cell-0,
	tr.pme-key-1, tr.pme-value-1, tr.pme-help-0, tr.pme-navigation-1, tr.pme-cell-1,
	tr.pme-sortinfo, tr.pme-filter { border: #0000ff 1px solid; padding: 1px; 
	vertical-align:top;
	height:10px;
	overflow:hidden;
	white-space:nowrap
	padding-top:0;
	margin:0;
	}
	td.pme-buttons { text-align: left;   }
	td.pme-message { text-align: center; }
	td.pme-stats   { text-align: right;  }
	td,th,label,form dd{
	width:500px;
	/*background-color:#DDF;*/
	background-color:#FFF;
}
</style>
</head>
<?php

require_once('db.php');
global $db;
//pr($_POST);
//pr($_GET);
if(isset($_GET['larvol_id']))
{
$_GET['PME_sys_operation']='PME_op_View';
$_GET['PME_sys_rec']=$_GET['larvol_id'];

}

if(isset($_GET['mode']) and $_GET['mode']=='edit' )
{
	if($db->loggedIn() and ($db->user->userlevel=='admin'||$db->user->userlevel=='root'))
	{
		$_GET['PME_sys_operation']='PME_op_Change';
		$_GET['PME_sys_rec']=$_GET['larvol_id'];
	}
	else
	{
		$_GET['PME_sys_operation']='PME_op_View';
		$_GET['PME_sys_rec']=$_GET['larvol_id'];
	}
}

$adm=$db->loggedIn() and ($db->user->userlevel=='admin'||$db->user->userlevel=='root');

if(!$adm and isset($_POST['PME_sys_operation']) and ($_POST['PME_sys_operation']=='PME_op_Change' or $_POST['PME_sys_operation']=='Change'))
{
	$_POST['PME_sys_operation']='PME_op_View';
}
if(!$adm and isset($_GET['PME_sys_operation']) and ($_GET['PME_sys_operation']=='PME_op_Change' or $_GET['PME_sys_operation']=='Change'))
{
	$_GET['PME_sys_operation']='PME_op_View';
}





$opts['dbh'] = $db->db_link;
$opts['tb'] = 'data_trials';
$opts['key'] = 'larvol_id';
$opts['key_type'] = 'int';
$opts['sort_field'] = array('larvol_id');
$opts['inc'] = 20;
$opts['options'] = 'ACVFI';
$opts['multiple'] = '4';
$opts['navigation'] = 'G';
$opts['display'] = array(
	'form'  => true,
	'query' => false,
	'sort'  => false,
	'time'  => false,
	'tabs'  => true
);

$opts['js']['prefix']               = 'PME_js_';
$opts['dhtml']['prefix']            = 'PME_dhtml_';
$opts['cgi']['prefix']['operation'] = 'PME_op_';
$opts['cgi']['prefix']['sys']       = 'PME_sys_';
$opts['cgi']['prefix']['data']      = 'PME_data_';
$opts['language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'] . '-UTF8';

/********* check if fieldname exists */ 
$query = 	"
			SELECT `COLUMN_NAME` 
			FROM `INFORMATION_SCHEMA`.`COLUMNS` 
			WHERE `TABLE_NAME`='data_trials'
			";

	$res1 		= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query getting column names from data schema . Query=' . $query;
		$logger->fatal($log);
		echo $log;
		die($log);
	}
	$cols=array();
	$cols[]='dummy';
	while($x=mysql_fetch_assoc($res1)) $cols[]=$x['COLUMN_NAME'];
/****************/	
$field_exists = array_search('larvol_id',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['larvol_id'] 
= array(
  'name'    => 'Larvol ID',
  'select'  => 'T',
  'options' => 'LAVCPDR',
  'maxlen'  => 10,
  'default' => '0',
  'sort'    => true
);

$field_exists = array_search('source_id',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['source_id']=
array
(
  'name'   => 'Source ID',
  'options'=> 'LAVPD', 
  'select' => 'T',
  'maxlen' => 63,
  'sort'   => true
);


$field_exists = array_search('brief_title',$cols) ;
if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['brief_title'] = array(
  'name'     => 'Brief title',
  'select'   => 'T',
  'maxlen'   => 255,
  'sort'     => true
);
$field_exists = array_search('acronym',$cols) ;
if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['acronym'] = array(
  'name'     => 'Acronym',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD',
  'sort'     => false
);
$field_exists = array_search('official_title',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['official_title'] = array(
  'name'     => 'Official title',
  'select'   => 'T',
  'width'   => '10%',
  'maxlen'   => 65535,
//  'options'  => 'AVCPD',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'sort'     => false
);
$field_exists = array_search('lead_sponsor',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['lead_sponsor'] = array(
  'name'     => 'Lead sponsor',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD',
  'sort'     => false
);
$field_exists = array_search('collaborator',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['collaborator'] = array(
  'name'     => 'Collaborator',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD',
  'sort'     => false
);
$field_exists = array_search('institution_type',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['institution_type'] = array(
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
$field_exists = array_search('source',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['source'] = array(
  'name'     => 'Source',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('has_dmc',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['has_dmc'] = array(
  'name'     => 'Has dmc',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('brief_summary',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['brief_summary'] = array(
  'name'     => 'Brief summary',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('detailed_description',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['detailed_description'] = array(
  'name'     => 'Detailed description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('overall_status',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['overall_status'] = array(
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
$field_exists = array_search('is_active',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['is_active'] = array(
  'name'     => 'Is active',
  'select'   => 'T',
  'maxlen'   => 1,
  'default'  => '1',
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('why_stopped',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['why_stopped'] = array(
  'name'     => 'Why stopped',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('start_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['start_date'] = array(
  'name'     => 'Start date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('end_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['end_date'] = array(
  'name'     => 'End date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('study_type',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['study_type'] = array(
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
$field_exists = array_search('study_design',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['study_design'] = array(
  'name'     => 'Study design',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('number_of_arms',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['number_of_arms'] = array(
  'name'     => 'Number of arms',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('number_of_groups',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['number_of_groups'] = array(
  'name'     => 'Number of groups',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('enrollment',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['enrollment'] = array(
  'name'     => 'Enrollment',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('enrollment_type',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['enrollment_type'] = array(
  'name'     => 'Enrollment type',
  'select'   => 'T',
  'maxlen'   => 11,
  'values'   => array(
                  "Actual",
                  "Anticipated"),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('biospec_retention',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['biospec_retention'] = array(
  'name'     => 'Biospec retention',
  'select'   => 'T',
  'maxlen'   => 19,
  'values'   => array(
                  "None Retained",
                  "Samples With DNA",
                  "Samples Without DNA"),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('biospec_descr',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['biospec_descr'] = array(
  'name'     => 'Biospec descr',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('study_pop',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['study_pop'] = array(
  'name'     => 'Study pop',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('sampling_method',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['sampling_method'] = array(
  'name'     => 'Sampling method',
  'select'   => 'T',
  'maxlen'   => 22,
  'values'   => array(
                  "Probability Sample",
                  "Non-Probability Sample"),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('criteria',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['criteria'] = array(
  'name'     => 'Criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('inclusion_criteria',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['inclusion_criteria'] = array(
  'name'     => 'Inclusion criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'options'  => 'AVCPDR',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
	'sort'     => false
);
$field_exists = array_search('exclusion_criteria',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['exclusion_criteria'] = array(
  'name'     => 'Exclusion criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'options'  => 'AVCPDR',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'sort'     => false
);
$field_exists = array_search('gender',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['gender'] = array(
  'name'     => 'Gender',
  'select'   => 'T',
  'maxlen'   => 6,
  'values'   => array(
                  "Male",
                  "Female",
                  "Both"),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('minimum_age',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['minimum_age'] = array(
  'name'     => 'Minimum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('maximum_age',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['maximum_age'] = array(
  'name'     => 'Maximum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('healthy_volunteers',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['healthy_volunteers'] = array(
  'name'     => 'Healthy volunteers',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('verification_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['verification_date'] = array(
  'name'     => 'Verification date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('lastchanged_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['lastchanged_date'] = array(
  'name'     => 'Lastchanged date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('firstreceived_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['firstreceived_date'] = array(
  'name'     => 'Firstreceived date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('responsible_party_name_title',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['responsible_party_name_title'] = array(
  'name'     => 'Responsible party name title',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('responsible_party_organization',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['responsible_party_organization'] = array(
  'name'     => 'Responsible party organization',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('org_study_id',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['org_study_id'] = array(
  'name'     => 'Org study ID',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);

$field_exists = array_search('phase',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['phase'] = array(
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
$field_exists = array_search('condition',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['condition'] = array(
  'name'     => 'Condition',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('secondary_id',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['secondary_id'] = array(
  'name'     => 'Secondary ID',
  'select'   => 'T',
  'maxlen'   => 63,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('oversight_authority',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['oversight_authority'] = array(
  'name'     => 'Oversight authority',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);

$field_exists = array_search('arm_group_label',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['arm_group_label'] = array(
  'name'     => 'Arm group label',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('arm_group_type',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['arm_group_type'] = array(
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
$field_exists = array_search('arm_group_description',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['arm_group_description'] = array(
  'name'     => 'Arm group description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('intervention_type',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['intervention_type'] = array(
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
$field_exists = array_search('intervention_name',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['intervention_name'] = array(
  'name'     => 'Intervention name',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('intervention_other_name',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['intervention_other_name'] = array(
  'name'     => 'Intervention other name',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('intervention_description',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['intervention_description'] = array(
  'name'     => 'Intervention description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('primary_outcome_measure',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['primary_outcome_measure'] = array(
  'name'     => 'Primary outcome measure',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('primary_outcome_timeframe',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['primary_outcome_timeframe'] = array(
  'name'     => 'Primary outcome timeframe',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('primary_outcome_safety_issue',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['primary_outcome_safety_issue'] = array(
  'name'     => 'Primary outcome safety issue',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('secondary_outcome_measure',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['secondary_outcome_measure'] = array(
  'name'     => 'Secondary outcome measure',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('secondary_outcome_timeframe',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['secondary_outcome_timeframe'] = array(
  'name'     => 'Secondary outcome timeframe',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('secondary_outcome_safety_issue',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['secondary_outcome_safety_issue'] = array(
  'name'     => 'Secondary outcome safety issue',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('location_name',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_name'] = array(
  'name'     => 'Location name',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('location_city',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_city'] = array(
  'name'     => 'Location city',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('location_state',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_state'] = array(
  'name'     => 'Location state',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('location_zip',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_zip'] = array(
  'name'     => 'Location zip',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('location_country',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_country'] = array(
  'name'     => 'Location country',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);

$field_exists = array_search('region',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['region'] = array(
  'name'     => 'Region',
  'select'   => 'T',
  'maxlen'   => 255,
  'default'  => 'RestOfWorld',
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('location_status',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_status']=
array
(
'name'     => 'Location status',
'select'   => 'T',
'maxlen'   => 25,
'values'   => 
	array
	(
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
        "Temporarily Not Available"
	),
'options'  => 'AVCPD', 
'sort'     => false
);

$field_exists = array_search('investigator_name',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['investigator_name'] = array(
  'name'     => 'Investigator name',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('investigator_role',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['investigator_role'] = array(
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
$field_exists = array_search('overall_official_name',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['overall_official_name'] = array(
  'name'     => 'Overall official name',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('overall_official_role',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['overall_official_role']=
array
(
'name'     => 'Overall official role',
'select'   => 'T',
'maxlen'   => 22,
'values'   => 
	array(
         "Principal Investigator",
         "Sub-Investigator",
         "Study Chair",
         "Study Director"
		 ),
'options'  => 'AVCPD', 
'sort'     => false
);

$field_exists = array_search('overall_official_affiliation',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['overall_official_affiliation'] = array(
  'name'     => 'Overall official affiliation',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('keyword',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['keyword'] = array(
  'name'     => 'Keyword',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);

$field_exists = array_search('is_fda_regulated',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['is_fda_regulated'] = array(
  'name'     => 'Is fda regulated',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
$field_exists = array_search('is_section_801',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['is_section_801'] = array(
  'name'     => 'Is section 801',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
require_once 'phpMyEdit.class.php';
//require_once 'edit_trials_list.php';
new phpMyEdit($opts);

?>

