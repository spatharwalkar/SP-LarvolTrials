<?php
//connect to Sphinx
if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
require_once('db.php');
require_once('include.util.php');

//ini_set('error_reporting', E_ALL ^ E_NOTICE);
global $logger;

?>
<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once 'include.page.php';
global $logger;
$table = 'news';
$script = 'edit_news';

// The table is not displayed properly in Chrome, but works fine in MSIE and FireFox.  Something to do with Doctype
// So a hack is used to fix the issue.
if(stripos($_SERVER['HTTP_USER_AGENT'],'chrome')) echo '<!DOCTYPE>';

require_once('header.php');
global $db;
if(isset($_GET['larvol_id']))
{
	$_GET['PME_sys_operation']='PME_op_View';
	$_GET['PME_sys_rec']=$_GET['larvol_id'];
}
if(isset($_GET['mode']) and $_GET['mode']=='edit' )
{
	//if($db->loggedIn() and ($db->user->userlevel=='admin'||$db->user->userlevel=='root'))
	if($db->loggedIn() )
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
//$adm=$db->loggedIn() and ($db->user->userlevel=='admin'||$db->user->userlevel=='root');
$adm=$db->loggedIn();//Manual trial entry and overriding should be allowed to all users, not just Admin

if(!$adm and isset($_POST['PME_sys_operation']) and ($_POST['PME_sys_operation']=='PME_op_Change' or $_POST['PME_sys_operation']=='Change'))
{
	$_POST['PME_sys_operation']='PME_op_View';
}
if(!$adm and isset($_GET['PME_sys_operation']) and ($_GET['PME_sys_operation']=='PME_op_Change' or $_GET['PME_sys_operation']=='Change'))
{
	$_GET['PME_sys_operation']='PME_op_View';
}

if(isset($_REQUEST['larvol_id']))	//load search from Saved Search
{
	$id = ($_REQUEST['larvol_id'])?$_REQUEST['larvol_id']:null;
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


?>
<script type="text/javascript">


function upmdelsure(){ return confirm("Are you sure you want to delete this news?"); }
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
hr.pme-hr {
	border: 0px solid;
	padding: 0px;
	margin: 0px;
	border-top-width: 1px;
	height: 5px;
}

table.pme-main {
	table-layout: fixed;
	border: #004d9c 1px solid;
	border-collapse: collapse;
	width: auto;
}

table.pme-navigation {
	table-layout: fixed;
	border: #004d9c 0px solid;
	border-collapse: collapse;
	width: auto;
}

td.pme-navigation-0, td.pme-navigation-1 {
	white-space: nowrap;
	table-layout: fixed;
	word-wrap: break-word;
}

th.pme-header {
	border: #004d9c 1px solid;
	padding: 4px;
	background: #add8e6;
}

td.pme-key-5, td.pme-value-0, td.pme-help-0, td.pme-navigation-0, td.pme-cell-0,
	td.pme-key-1, td.pme-value-1, td.pme-help-0, td.pme-navigation-1, td.pme-cell-1,
	td.pme-sortinfo, td.pme-filter {
	border: #0000ff 1px solid;
	padding: 1px;
	height: 50px;
	width: 50px overflow:hidden;
	word-wrap: break-word;
	white-space: wrap;
	padding-top: 0;
	margin: 0;
}

tr.pme-key-5, tr.pme-value-0, tr.pme-help-0, tr.pme-navigation-0, tr.pme-cell-0,
	tr.pme-key-1, tr.pme-value-1, tr.pme-help-0, tr.pme-navigation-1, tr.pme-cell-1,
	tr.pme-sortinfo, tr.pme-filter {
	border: #0000ff 1px solid;
	padding: 1px;
	table-layout: fixed;
	overflow: hidden;
	word-wrap: break-word;
	height: 10px;
	overflow: hidden;
	word-wrap: break-word;
	white-space: nowrap;
	padding-top: 0;
	margin: 0;
}

td.pme-buttons {
	text-align: left;
}

td.pme-message {
	text-align: center;
}

td.pme-stats {
	text-align: right;
}

td, th, label, form dd {
	table-layout: fixed;
	overflow: hidden;
	word-wrap: break-word;
	/*background-color:#DDF;*/
	background-color: #FFF;
}

table td {
	table-layout: fixed;
	overflow: hidden;
	word-wrap: break-word;
}
/* added for the id columns */
/*
	th.pme-header-id{ 
		background: #add8e6; 
		border:1px solid #004d9c; 
		color:white; 
		width: 7%; 
		padding: 4px; 
	}
*/
</style>
</head>
<?php
$opts['dbh'] = $db->db_link;
$opts['tb'] = 'news';
$opts['key'] = 'larvol_id';
$opts['key_type'] = 'int';
$opts['sort_field'] = array('larvol_id');
$opts['inc'] = 10;
$opts['options'] = 'LACVF';
$opts['multiple'] = '0';
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
		WHERE `TABLE_NAME`='news'
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


$field_exists = array_search('id',$cols) ;
if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['id']=
array
(
		'name'   => 'News ID',
		'select' => 'T',
		'maxlen' => 10,
		'sort'   => true,
		'options' => 'LAVCPDR',
		'size|ACP'   => 10,
		'URL' => '/api/news.php?id=$value',
		'URLtarget'	=>	'_blank'
);
$opts['fdd']['virt'] = array(
			'name'     => 'Redtag ID',
			'select'   => 'T',
			'input'   => 'V', // virtual
			'options'  => 'L', // list only
			'size|F' => 10,
			'values'   => Array('table' => 'news_redtag', 'column' => 'news', 'description' => 'redtag','join' => 'PMEtable0.id = PMEjoin1.news'),
			'sql'      => 'PMEjoin1.redtag',
			'sort'     => true
	);

	$field_exists = array_search('larvol_id',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  ) $opts['fdd']['larvol_id']
	= array(
	  'name'    => 'Larvol ID',
	  'select'  => 'T',
	  'options' => 'LAVCPDR',
	  'maxlen'  => 10,
	  'default' => '0',
	  'sort'    => true,
	'URL' => '/edit_trials.php?PME_sys_fl=0&PME_sys_fm=0&PME_sys_sfn[0]=0&PME_sys_operation=PME_op_Change&PME_sys_rec=$value',
	'URLtarget'	=>	'_blank'
	);
	$opts['fdd']['virt1'] = array(
		'name'     => 'Source ID',
		'select'   => 'T',
		'input'   => 'V', // virtual
		'options'  => 'L', // list only
		'size|F' => 10,
		'values'   => Array('table' => 'data_trials', 'column' => 'larvol_id', 'description' => 'source_id','join' => 'PMEtable0.larvol_id = PMEjoin3.larvol_id'),
		'sql'      => 'PMEjoin3.larvol_id',
		'sort'     => true
	);


	$field_exists = array_search('brief_title',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['brief_title'] = array(
			'name'     => 'Brief title',
			'select'   => 'T',
			'maxlen'   => 155,
			'sort'     => true
	);


	$field_exists = array_search('phase',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['phase'] = array(
			'name'     => 'Phase',
			'select'   => 'T',
			'maxlen'   => 155,
			'sort'     => true
	);

	$field_exists = array_search('enrollment',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['enrollment'] = array(
			'name'     => 'Enrollment',
			'select'   => 'T',
			'maxlen'   => 155,
			'sort'     => true
	);


	$field_exists = array_search('overall_status',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['overall_status'] = array(
			'name'     => 'Overall Status',
			'select'   => 'T',
			'maxlen'   => 155,
			'sort'     => true
	);


	$field_exists = array_search('sponsor',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['sponsor'] = array(
			'name'     => 'Sponsor',
			'select'   => 'T',
			'maxlen'   => 155,
			'sort'     => true
	);

	$field_exists = array_search('summary',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['summary'] = array(
			'name'     => 'Summary',
			'select'   => 'T',
			'maxlen'   => 155,
			'sort'     => true
	);

	$field_exists = array_search('added',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['added'] = array(
			'name'     => 'Added',
			'select'   => 'T',
			'maxlen'   => 15,
			'sort'     => true
	);

	$field_exists = array_search('period',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['period'] = array(
			'name'     => 'Period',
			'select'   => 'T',
			'maxlen'   => 10,
			'sort'     => true
	);


	$field_exists = array_search('score',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['score'] = array(
			'name'     => 'Score',
			'select'   => 'T',
			'maxlen'   => 5,
			'sort'     => true
	);

	$field_exists = array_search('abstract_id',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['abstract_id'] = array(
			'name'     => 'Abstract Id',
			'select'   => 'T',
			'maxlen'   => 5,
			'sort'     => true
	);

	$field_exists = array_search('generation_date',$cols) ;
	if ( isset($field_exists) and $field_exists > 0  )  $opts['fdd']['generation_date'] = array(
			'name'     => 'Generation Date',
			'select'   => 'T',
			'maxlen'   => 5,
			'sort'     => true
	);


require_once 'news/phpMyEdit.class.php';

new phpMyEdit($opts);
//pr($opts);
?>
