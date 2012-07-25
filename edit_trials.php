<?php
//connect to Sphinx
if(!isset($sphinx) or empty($sphinx)) $sphinx = mysql_connect("127.0.0.1:9306") or die ("Couldn't connect to Sphinx server.");
require_once('db.php');
require_once('include.util.php');
require_once('preindex_trial.php');

//ini_set('error_reporting', E_ALL ^ E_NOTICE);
global $logger;
if(isset($_GET['sourceless_only'])) $_POST['sourceless_only']=$_GET['sourceless_only'];
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
$table1 = 'data_trials';
$script = 'edit_trials';
		
require_once('header.php');	
global $db;
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

if(isset($_REQUEST['id']))	//load search from Saved Search
{
	$id = ($_REQUEST['id'])?$_REQUEST['id']:null;
	$searchDbData = getSearchData($table, 'searchdata', $id);
	//$show_value = 'loadQueryData("' . $data . '");';
	//$show_value = "loadQueryData('" . $searchDbData . "');";
	$show_value = "searchDbData = '" . $searchDbData . "';";
//	echo($show_value);

}
else
{
	$show_value = "searchDbData = '';";
//	echo($show_value);
}		
$change='No' ;
if(isset($_GET['PME_sys_operation'])) $change=$_GET['PME_sys_operation'];
if(isset($_POST['PME_sys_operation'])) $change=$_POST['PME_sys_operation'];


if( $db->loggedIn() and ( $db->user->userlevel=='admin' || $db->user->userlevel=='root' ) and ( $change=='Change' or $change=='PME_op_Change' ) )
{
	

	if(isset($_GET['PME_sys_rec'])) $lid=$_GET['PME_sys_rec']; 
	if(isset($_POST['PME_sys_rec'])) $lid=$_POST['PME_sys_rec']; 
	
	
	$query = "
			SELECT `source_id`,`is_sourceless` 
			FROM `data_manual` 
			WHERE `larvol_id` = $lid limit 1
			";
	$res1 		= mysql_query($query) ;
	
	
	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		die($log);
	}
	
	$hint=mysql_fetch_assoc($res1);
	if(isset($hint['source_id']) and trim($hint['source_id'])<>'') $hnt=",hint:'".$hint['source_id']."'";
	else $hnt='';
	
}


?>
<script type="text/javascript">


function upmdelsure(){ return confirm("Are you sure you want to delete this product?"); }
$(document).ready(function(){
	var options,a,b;

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
	hr.pme-hr		     { border: 0px solid; padding: 0px; margin: 0px; border-top-width: 1px; height: 5px; }
	table.pme-main 	     { border: #004d9c 1px solid; border-collapse: collapse; width:100%; }
	table.pme-navigation { border: #004d9c 0px solid; border-collapse: collapse; width: 100%; }
	td.pme-navigation-0, td.pme-navigation-1 { white-space: nowrap; }
	th.pme-header	     { border: #004d9c 1px solid; padding: 4px; background: #add8e6; }
	td.pme-key-5, td.pme-value-0, td.pme-help-0, td.pme-navigation-0, td.pme-cell-0,
	td.pme-key-1, td.pme-value-1, td.pme-help-0, td.pme-navigation-1, td.pme-cell-1,
	td.pme-sortinfo, td.pme-filter { border: #0000ff 1px solid; padding: 1px; 
	height:50px;
	width:50px
	overflow:hidden;
	word-wrap:break-word;
	white-space:wrap;
	padding-top:0;
	margin:0;
	}
	tr.pme-key-5, tr.pme-value-0, tr.pme-help-0, tr.pme-navigation-0, tr.pme-cell-0,
	tr.pme-key-1, tr.pme-value-1, tr.pme-help-0, tr.pme-navigation-1, tr.pme-cell-1,
	tr.pme-sortinfo, tr.pme-filter { border: #0000ff 1px solid; padding: 1px; 
	
	height:10px;
	overflow:hidden;
	white-space:nowrap;
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
<?
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
	
	$viewmode='NO';
	if(isset($_GET['PME_sys_operation']) and $_GET['PME_sys_operation']=='PME_op_View' ) $viewmode='YES';
	elseif(isset($_POST['PME_sys_operation']) and $_POST['PME_sys_operation']=='PME_op_View' ) $viewmode='YES';
	
	
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

if($viewmode=='YES')
{
 $opts['fdd']['brief_title_prev'] = array(
  'name'     => 'Prev Brief title',
  'select'   => 'T',
  'maxlen'   => 255,
  'sort'     => true
);
 $opts['fdd']['brief_title_lastchanged'] = array(
  'name'     => 'last_changed Brief title',
  'select'   => 'T',
  'maxlen'   => 255,
  'sort'     => true
);

}


$field_exists = array_search('acronym',$cols) ;
if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['acronym'] = array(
  'name'     => 'Acronym',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD',
  'sort'     => false
);

if($viewmode=='YES')
{
 $opts['fdd']['acronym_prev'] = array(
  'name'     => 'Prev Acronym',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD',
  'sort'     => false
);
 $opts['fdd']['acronym_lastchanged'] = array(
  'name'     => 'last_changed Acronym',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD',
  'sort'     => false
);

}

$field_exists = array_search('official_title',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['official_title'] = array(
  'name'     => 'Official title',
  'select'   => 'T',
  'width'   => '10%',
  'maxlen'   => 65535,
  'options'  => 'AVCPD',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['official_title_prev'] = array(
  'name'     => 'Prev Official title',
  'select'   => 'T',
  'width'   => '10%',
  'maxlen'   => 65535,
  'options'  => 'AVCPD',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'sort'     => false
);
$opts['fdd']['official_title_lastchanged'] = array(
  'name'     => 'last_changed Official title',
  'select'   => 'T',
  'width'   => '10%',
  'maxlen'   => 65535,
  'options'  => 'AVCPD',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'sort'     => false
);
 }
$field_exists = array_search('lead_sponsor',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['lead_sponsor'] = array(
  'name'     => 'Lead sponsor',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD',
  'sort'     => false
);

if($viewmode=='YES')
{$opts['fdd']['lead_sponsor_prev'] = array(
  'name'     => 'Prev Lead sponsor',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD',
  'sort'     => false
); 

$opts['fdd']['lead_sponsor_lastchanged'] = array(
  'name'     => 'last_changed Lead sponsor',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD',
  'sort'     => false
);

}

$field_exists = array_search('collaborator',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['collaborator'] = array(
  'name'     => 'Collaborator',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD',
  'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['collaborator_prev'] = array(
  'name'     => 'Prev Collaborator',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD',
  'sort'     => false
);
$opts['fdd']['collaborator_lastchanged'] = array(
  'name'     => 'last_changed Collaborator',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD',
  'sort'     => false
);

 }

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
if($viewmode=='YES')
{$opts['fdd']['institution_type_prev'] = array(
  'name'     => 'Prev Institution type',
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
$opts['fdd']['institution_type_lastchanged'] = array(
  'name'     => 'last_changed Institution type',
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

 }


$field_exists = array_search('source',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['source'] = array(
  'name'     => 'Source',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['source_prev'] = array(
  'name'     => 'Prev Source',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['source_lastchanged'] = array(
  'name'     => 'last_changed Source',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);
 }
$field_exists = array_search('has_dmc',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['has_dmc'] = array(
  'name'     => 'Has dmc',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);

if($viewmode=='YES')
{$opts['fdd']['has_dmc_prev'] = array(
  'name'     => 'Prev Has dmc',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['has_dmc_lastchanged'] = array(
  'name'     => 'last_changed Has dmc',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);

}
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
if($viewmode=='YES')
{$opts['fdd']['brief_summary_prev'] = array(
  'name'     => 'Prev Brief summary',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['brief_summary_lastchanged'] = array(
  'name'     => 'last_changed Brief summary',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);

 }
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
if($viewmode=='YES')
{$opts['fdd']['detailed_description_prev'] = array(
  'name'     => 'Prev Detailed description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['detailed_description_lastchanged'] = array(
  'name'     => 'last_changed Detailed description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
 }
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
                  "Temporarily Not Available",
				  "Ongoing",
				  "Not Authorized",
				  "Prohibited"),
  'default'  => 'Not yet recruiting',
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['overall_status_prev'] = array(
  'name'     => 'Prev Overall status',
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
                  "Temporarily Not Available",
				  "Ongoing",
				  "Not Authorized",
				  "Prohibited"),
  'default'  => 'Not yet recruiting',
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['overall_status_lastchanged'] = array(
  'name'     => 'last_changed Overall status',
  'select'   => 'T',
  'maxlen'   => 25,
  'options'  => 'AVCPD', 'sort'     => false
);
}
$field_exists = array_search('is_active',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['is_active'] = array(
  'name'     => 'Is active',
  'select'   => 'T',
  'maxlen'   => 1,
  'default'  => '1',
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['is_active_prev'] = array(
  'name'     => 'Prev Is active',
  'select'   => 'T',
  'maxlen'   => 1,
  'default'  => '1',
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['is_active_lastchanged'] = array(
  'name'     => 'last_changed Is active',
  'select'   => 'T',
  'maxlen'   => 1,
  'default'  => '1',
  'options'  => 'AVCPD', 'sort'     => false
);
 }
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
if($viewmode=='YES')
{$opts['fdd']['why_stopped_prev'] = array(
  'name'     => 'Prev Why stopped',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['why_stopped_lastchanged'] = array(
  'name'     => 'last_changed Why stopped',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
 }
$field_exists = array_search('start_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['start_date'] = array(
  'name'     => 'Start date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['start_date_prev'] = array(
  'name'     => 'Prev Start date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['start_date_lastchanged'] = array(
  'name'     => 'last_changed Start date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);

 }
$field_exists = array_search('end_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['end_date'] = array(
  'name'     => 'End date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['end_date_prev'] = array(
  'name'     => 'Prev End date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['end_date_lastchanged'] = array(
  'name'     => 'last_changed End date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);

 }

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
if($viewmode=='YES')
{$opts['fdd']['study_type_prev'] = array(
  'name'     => 'Prev Study type',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['study_type_lastchanged'] = array(
  'name'     => 'last_changed Study type',
  'select'   => 'T',
  'maxlen'   => 15,
  'values'   => array(
                  "Interventional",
                  "Observational",
                  "Expanded Access",
                  "N/A"),
  'options'  => 'AVCPD', 'sort'     => false
);

}
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
if($viewmode=='YES')
{$opts['fdd']['study_design_prev'] = array(
  'name'     => 'Prev Study design',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['study_design_lastchanged'] = array(
  'name'     => 'last_changed Study design',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);

}
$field_exists = array_search('number_of_arms',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['number_of_arms'] = array(
  'name'     => 'Number of arms',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['number_of_arms_prev'] = array(
  'name'     => 'Prev Number of arms',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['number_of_arms_lastchanged'] = array(
  'name'     => 'last_changed Number of arms',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
}
$field_exists = array_search('number_of_groups',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['number_of_groups'] = array(
  'name'     => 'Number of groups',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['number_of_groups_prev'] = array(
  'name'     => 'Prev Number of groups',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['number_of_groups_lastchanged'] = array(
  'name'     => 'last_changed Number of groups',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);

}

$field_exists = array_search('enrollment',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['enrollment'] = array(
  'name'     => 'Enrollment',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['enrollment_prev'] = array(
  'name'     => 'Prev Enrollment',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);

$opts['fdd']['enrollment_lastchanged'] = array(
  'name'     => 'last_changed Enrollment',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);

 }
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
if($viewmode=='YES')
{$opts['fdd']['enrollment_type_prev'] = array(
  'name'     => 'Prev Enrollment type',
  'select'   => 'T',
  'maxlen'   => 11,
  'values'   => array(
                  "Actual",
                  "Anticipated"),
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['enrollment_type_lastchanged'] = array(
  'name'     => 'last_changed Enrollment type',
  'select'   => 'T',
  'maxlen'   => 11,
  'values'   => array(
                  "Actual",
                  "Anticipated"),
  'options'  => 'AVCPD', 'sort'     => false
);

}
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
if($viewmode=='YES')
{$opts['fdd']['study_pop_prev'] = array(
  'name'     => 'Prev Study pop',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['study_pop_lastchanged'] = array(
  'name'     => 'last_changed Study pop',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);

}
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
if($viewmode=='YES')
{$opts['fdd']['sampling_method_prev'] = array(
  'name'     => 'Prev Sampling method',
  'select'   => 'T',
  'maxlen'   => 22,
  'values'   => array(
                  "Probability Sample",
                  "Non-Probability Sample"),
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['sampling_method_lastchanged'] = array(
  'name'     => 'last_changed Sampling method',
  'select'   => 'T',
  'maxlen'   => 22,
  'values'   => array(
                  "Probability Sample",
                  "Non-Probability Sample"),
  'options'  => 'AVCPD', 'sort'     => false
);

}
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
if($viewmode=='YES')
{$opts['fdd']['criteria_prev'] = array(
  'name'     => 'Prev Criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['criteria_lastchanged'] = array(
  'name'     => 'last_changed Criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);

 }

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
if($viewmode=='YES')
{$opts['fdd']['inclusion_criteria_prev'] = array(
  'name'     => 'Prev Inclusion criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'options'  => 'AVCPDR',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
	'sort'     => false
); 
$opts['fdd']['inclusion_criteria_lastchanged'] = array(
  'name'     => 'last_changed Inclusion criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'options'  => 'AVCPDR',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
	'sort'     => false
);
}
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
if($viewmode=='YES')
{$opts['fdd']['exclusion_criteria_prev'] = array(
  'name'     => 'Prev Exclusion criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'options'  => 'AVCPDR',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'sort'     => false
);

$opts['fdd']['exclusion_criteria_lastchanged'] = array(
  'name'     => 'last_changed Exclusion criteria',
  'select'   => 'T',
  'maxlen'   => 65535,
  'options'  => 'AVCPDR',
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'sort'     => false
);
 }

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
if($viewmode=='YES')
{$opts['fdd']['gender_prev'] = array(
  'name'     => 'Prev Gender',
  'select'   => 'T',
  'maxlen'   => 6,
  'values'   => array(
                  "Male",
                  "Female",
                  "Both"),
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['gender_lastchanged'] = array(
  'name'     => 'last_changed Gender',
  'select'   => 'T',
  'maxlen'   => 6,
  'values'   => array(
                  "Male",
                  "Female",
                  "Both"),
  'options'  => 'AVCPD', 'sort'     => false
);
}
$field_exists = array_search('minimum_age',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['minimum_age'] = array(
  'name'     => 'Minimum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['minimum_age_prev'] = array(
  'name'     => 'Prev Minimum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['minimum_age_lastchanged'] = array(
  'name'     => 'last_changed Minimum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
);
 }
$field_exists = array_search('maximum_age',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['maximum_age'] = array(
  'name'     => 'Maximum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['maximum_age_prev'] = array(
  'name'     => 'Prev Maximum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['maximum_age_lastchanged'] = array(
  'name'     => 'last_changed Maximum age',
  'select'   => 'T',
  'maxlen'   => 15,
  'options'  => 'AVCPD', 'sort'     => false
);

}

$field_exists = array_search('healthy_volunteers',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['healthy_volunteers'] = array(
  'name'     => 'Healthy volunteers',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['healthy_volunteers_prev'] = array(
  'name'     => 'Prev Healthy volunteers',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);

$opts['fdd']['healthy_volunteers_lastchanged'] = array(
  'name'     => 'last_changed Healthy volunteers',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);

}
$field_exists = array_search('verification_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['verification_date'] = array(
  'name'     => 'Verification date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['verification_date_prev'] = array(
  'name'     => 'Prev Verification date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['verification_date_lastchanged'] = array(
  'name'     => 'last_changed Verification date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);

 }

$field_exists = array_search('lastchanged_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['lastchanged_date'] = array(
  'name'     => 'Lastchanged date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
)

;

if($viewmode=='YES')
{$opts['fdd']['lastchanged_date_prev'] = array(
  'name'     => 'Prev Lastchanged date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['lastchanged_date_lastchanged'] = array(
  'name'     => 'last_changed Lastchanged date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
}
$field_exists = array_search('firstreceived_date',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['firstreceived_date'] = array(
  'name'     => 'Firstreceived date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);

if($viewmode=='YES')
{$opts['fdd']['firstreceived_date_prev'] = array(
  'name'     => 'Prev Firstreceived date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['firstreceived_date_lastchanged'] = array(
  'name'     => 'last_changed Firstreceived date',
  'select'   => 'T',
  'maxlen'   => 10,
  'options'  => 'AVCPD', 'sort'     => false
);
}
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


if($viewmode=='YES')
{$opts['fdd']['org_study_id_prev'] = array(
  'name'     => 'Prev Org study ID',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['org_study_id_lastchanged'] = array(
  'name'     => 'last_changed Org study ID',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);

 }
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
if($viewmode=='YES')
{$opts['fdd']['phase_prev'] = array(
  'name'     => 'Prev Phase',
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
$opts['fdd']['phase_lastchanged'] = array(
  'name'     => 'last_changed Phase',
  'select'   => 'T',
  'maxlen'   => 5,
  'default'  => 'N/A',
  'options'  => 'AVCPD', 'sort'     => false
);
}

$field_exists = array_search('condition',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['condition'] = array(
  'name'     => 'Condition',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);

if($viewmode=='YES')
{$opts['fdd']['condition_prev'] = array(
  'name'     => 'Prev Condition',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['condition_lastchanged'] = array(
  'name'     => 'last_changed Condition',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
}

$field_exists = array_search('secondary_id',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['secondary_id'] = array(
  'name'     => 'Secondary ID',
  'select'   => 'T',
  'maxlen'   => 63,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['secondary_id_prev'] = array(
  'name'     => 'Prev Secondary ID',
  'select'   => 'T',
  'maxlen'   => 63,
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['secondary_id_lastchanged'] = array(
  'name'     => 'last_changed Secondary ID',
  'select'   => 'T',
  'maxlen'   => 63,
  'options'  => 'AVCPD', 'sort'     => false
);
}

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

if($viewmode=='YES')
{$opts['fdd']['arm_group_label_prev'] = array(
  'name'     => 'Prev Arm group label',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);

$opts['fdd']['arm_group_label_lastchanged'] = array(
  'name'     => 'last_changed Arm group label',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
 }

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
if($viewmode=='YES')
{$opts['fdd']['arm_group_type_prev'] = array(
  'name'     => 'Prev Arm group type',
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
$opts['fdd']['arm_group_type_lastchanged'] = array(
  'name'     => 'last_changed Arm group type',
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
}

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
if($viewmode=='YES')
{$opts['fdd']['arm_group_description_prev'] = array(
  'name'     => 'Prev Arm group description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['arm_group_description_lastchanged'] = array(
  'name'     => 'last_changed Arm group description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
 }

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
if($viewmode=='YES')
{$opts['fdd']['intervention_type_prev'] = array(
  'name'     => 'Prev Intervention type',
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
$opts['fdd']['intervention_type_lastchanged'] = array(
  'name'     => 'last_changed Intervention type',
  'select'   => 'T',
  'maxlen'   => 36,
  'options'  => 'AVCPD', 'sort'     => false
);

 }
$field_exists = array_search('intervention_name',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['intervention_name'] = array(
  'name'     => 'Intervention name',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['intervention_name_prev'] = array(
  'name'     => 'Prev Intervention name',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['intervention_name_lastchanged'] = array(
  'name'     => 'last_changed Intervention name',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);

}

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
if($viewmode=='YES')
{$opts['fdd']['intervention_description_prev'] = array(
  'name'     => 'Prev Intervention description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['intervention_description_lastchanged'] = array(
  'name'     => 'last_changed Intervention description',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
 }
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
if($viewmode=='YES')
{$opts['fdd']['primary_outcome_measure_prev'] = array(
  'name'     => 'Prev Primary outcome measure',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['primary_outcome_measure_lastchanged'] = array(
  'name'     => 'last_changed Primary outcome measure',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
}
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
if($viewmode=='YES')
{$opts['fdd']['primary_outcome_timeframe_prev'] = array(
  'name'     => 'Prev Primary outcome timeframe',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['primary_outcome_timeframe_lastchanged'] = array(
  'name'     => 'last_changed Primary outcome timeframe',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
}

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
if($viewmode=='YES')
{$opts['fdd']['primary_outcome_safety_issue_prev'] = array(
  'name'     => 'Prev Primary outcome safety issue',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['primary_outcome_safety_issue_lastchanged'] = array(
  'name'     => 'last_changed Primary outcome safety issue',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
}
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
if($viewmode=='YES')
{$opts['fdd']['secondary_outcome_measure_prev'] = array(
  'name'     => 'Prev Secondary outcome measure',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['secondary_outcome_measure_lastchanged'] = array(
  'name'     => 'last_changed Secondary outcome measure',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
 }
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
if($viewmode=='YES')
{$opts['fdd']['secondary_outcome_timeframe_prev'] = array(
  'name'     => 'Prev Secondary outcome timeframe',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['secondary_outcome_timeframe_lastchanged'] = array(
  'name'     => 'last_changed Secondary outcome timeframe',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
}
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
if($viewmode=='YES')
{$opts['fdd']['secondary_outcome_safety_issue_prev'] = array(
  'name'     => 'Prev Secondary outcome safety issue',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['secondary_outcome_safety_issue_lastchanged'] = array(
  'name'     => 'last_changed Secondary outcome safety issue',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
}
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
if($viewmode=='YES')
{$opts['fdd']['location_name_prev'] = array(
  'name'     => 'Prev Location name',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['location_name_lastchanged'] = array(
  'name'     => 'last_changed Location name',
  'select'   => 'T',
  'maxlen'   => 65535,
  'textarea' => array(
    'rows' => 5,
    'cols' => 50),
  'options'  => 'AVCPD', 'sort'     => false
);
 }
$field_exists = array_search('location_city',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_city'] = array(
  'name'     => 'Location city',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['location_city_prev'] = array(
  'name'     => 'Prev Location city',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['location_city_lastchanged'] = array(
  'name'     => 'last_changed Location city',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
 }
$field_exists = array_search('location_state',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_state'] = array(
  'name'     => 'Location state',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['location_state_prev'] = array(
  'name'     => 'Prev Location state',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['location_state_lastchanged'] = array(
  'name'     => 'last_changed Location state',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);

}
$field_exists = array_search('location_zip',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_zip'] = array(
  'name'     => 'Location zip',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['location_zip_prev'] = array(
  'name'     => 'Prev Location zip',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
); 
$opts['fdd']['location_zip_lastchanged'] = array(
  'name'     => 'last_changed Location zip',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
}
$field_exists = array_search('location_country',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['location_country'] = array(
  'name'     => 'Location country',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);

if($viewmode=='YES')
{$opts['fdd']['location_country_prev'] = array(
  'name'     => 'Prev Location country',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['location_country_lastchanged'] = array(
  'name'     => 'last_changed Location country',
  'select'   => 'T',
  'maxlen'   => 255,
  'options'  => 'AVCPD', 'sort'     => false
);
 }
$field_exists = array_search('region',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['region'] = array(
  'name'     => 'Region',
  'select'   => 'T',
  'maxlen'   => 255,
  'default'  => 'RestOfWorld',
  'options'  => 'AVCPD', 'sort'     => false
);

if($viewmode=='YES')
{$opts['fdd']['region_prev'] = array(
  'name'     => 'Prev Region',
  'select'   => 'T',
  'maxlen'   => 255,
  'default'  => 'RestOfWorld',
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['region_lastchanged'] = array(
  'name'     => 'last_changed Region',
  'select'   => 'T',
  'maxlen'   => 255,
  'default'  => 'RestOfWorld',
  'options'  => 'AVCPD', 'sort'     => false
);
}
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

if($viewmode=='YES')
{$opts['fdd']['keyword_prev'] = array(
  'name'     => 'Prev Keyword',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['keyword_lastchanged'] = array(
  'name'     => 'last_changed Keyword',
  'select'   => 'T',
  'maxlen'   => 127,
  'options'  => 'AVCPD', 'sort'     => false
);

 }

$field_exists = array_search('is_fda_regulated',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['is_fda_regulated'] = array(
  'name'     => 'Is fda regulated',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['is_fda_regulated_prev'] = array(
  'name'     => 'Prev Is fda regulated',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
); 

$opts['fdd']['is_fda_regulated_lastchanged'] = array(
  'name'     => 'last_changed Is fda regulated',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
}
$field_exists = array_search('is_section_801',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['is_section_801'] = array(
  'name'     => 'Is section 801',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
if($viewmode=='YES')
{$opts['fdd']['is_section_801_prev'] = array(
  'name'     => 'Prev Is section 801',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
$opts['fdd']['is_section_801_lastchanged'] = array(
  'name'     => 'last_changed Is section 801',
  'select'   => 'T',
  'maxlen'   => 1,
  'options'  => 'AVCPD', 'sort'     => false
);
}

require_once 'phpMyEdit.class.php';
//require_once 'edit_trials_list.php';
new phpMyEdit($opts);
//pr($opts);
?>

