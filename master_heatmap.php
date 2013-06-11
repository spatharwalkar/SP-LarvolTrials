<?php
require_once('db.php');

require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');
require_once('class.phpmailer.php');
require_once('krumo/class.krumo.php');

ini_set('error_reporting', E_ALL ^ E_NOTICE);
define('READY', 1);
define('RUNNING', 2);
define('ERROR', 3);
define('CANCELLED', 4);
define('COMPLETED', 0);

global $logger;


ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours

/***Recalculation of cells start*/
if(isset($_REQUEST['recalc']))
{
	require_once('calculate_hm_cells.php');
	
	$productz=get_products();	// get list of products from master heatmap
	
	$areaz=get_areas();	// get list of areas from master heatmap
	
//	$searchdata=get_search_data($productz);	// get the searchdata using the list of products 
	echo 'Recalculating all values of the Master HM<br>';
	foreach($areaz as $akey => $aval)
	{
		foreach($productz as $pkey => $pval)
		{
			recalc_values($aval,$pval);	// recalculate values using searchdata.
		}
	}
	$id = mysql_real_escape_string($_GET['id']);
	$query = '	select update_id,trial_type,status from update_status_fullhistory where 
					trial_type="RECALC=' . $id . '" and status="2" ' ;
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			global $logger;
			$logger->error($log);
			echo $log;
			return false;
		}
		$x=mysql_fetch_assoc($res);
		if(isset($x['update_id']))
		{
			$x=$x['update_id'];
			$query = 'UPDATE update_status_fullhistory SET status="0",end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" 
				  WHERE update_id="' . $x . '"';
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				global $logger;
				$logger->error($log);
				echo $log;
				return false;
			}

		}
	
	
	
	echo '<br>All done.<br>';
	return true;
}

function get_products() // get list of products 
{
	global $logger;
	$productz=array();
	$id = mysql_real_escape_string($_GET['id']);
	$query = '	SELECT `num`,`type`,`type_id`, `display_name`, `category` FROM `rpt_masterhm_headers` 
				WHERE report=' . $id . ' and type="row" ORDER BY num ASC';

	if(!$resu = mysql_query($query))
	{
	$log='Bad SQL query getting  details from rpt_masterhm_headers table.<br>Query=' . $query;
	$logger->fatal($log);
	echo $log;
	return false;
	}

	while($header = mysql_fetch_array($resu))
	{
		$productz[] = $header['type_id'];
	}
	return $productz;
}
function get_areas() // get list of areas 
{
	global $logger;
	$areaz=array();
	$id = mysql_real_escape_string(htmlspecialchars($_GET['id']));
	
	$query = '	SELECT `num`,`type`,`type_id` FROM `rpt_masterhm_headers` 
				WHERE report=' . $id . ' and type="column" ORDER BY num ASC';

	if(!$resu = mysql_query($query))
	{
	$log='Bad SQL query getting  details from rpt_masterhm_headers table.<br>Query=' . $query;
	$logger->fatal($log);
	echo $log;
	return false;
	}

	while($header = mysql_fetch_array($resu))
	{
		$areaz[] = $header['type_id'];
	}
	return $areaz;
}

function get_search_data($idz,$cat) // get the searchdata 
{
	global $logger;
	$idz = implode(",", $idz);
	$query = 'SELECT `id`,`name`,`searchdata` from '. $cat .' where searchdata IS NOT NULL and  `searchdata` <>""
	and id in (' . $idz . ')';

	if(!$resu = mysql_query($query))
	{
		$log='Bad SQL query getting  details from '. $cat .' table.<br>Query=' . $query;
		$logger->fatal($log);
		echo $log;
		return false;
	}
	$searchdata=array();
	while($searchdata[]=mysql_fetch_array($resu));
	return $searchdata;

}

function  recalc_values($aval,$pval) // recalculate values
{
	global $logger;
	$parameters=array();
	$parameters['entity1']=$aval;
	$parameters['entity2']=$pval;
	// recalculate only if both area and product are supplied.
	if( isset($parameters['entity1']) and isset($parameters['entity2']) )
		calc_cells($parameters);
	return true;
}

/***Recalculation of cells end*/


if($_POST['dwformat'] || isset($_GET['excel_x']) || isset($_GET['pdf_x']))
{
	if($_POST['dwformat']=='htmldown')
		header('Location: ' . urlPath() . 'online_heatmap.php?id='.$_POST['id']);
	else
		Download_reports();
}
else {
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}

require_once('report_common.php');
require_once('slickgrid_data.php');

$_GET['header']='<link href="css/status.css" rel="stylesheet" type="text/css" media="all" />';

require('header.php');
?>
<script type="text/javascript">
function autoComplete(fieldID)
{	
	$(function()
	{
		if($('#'+fieldID).length > 0)
		{	
			var a = $('#'+fieldID).autocomplete({
					serviceUrl:'autosuggest.php',
					params:{table:'masterhm', field:'name'},
					minChars:3,
					width:450
			});
		}
	});
}
</script>
<script type="text/javascript">
function update_icons(type, row, col, tot_rows, tot_cols, BG_color)
{
	if(type=='phase4')
	{
	  if(document.getElementById("phase4opt_"+row+"_"+col).checked == true)
	  {
	  	document.getElementById("phase4_val_"+row+"_"+col).value=1;
		document.getElementById("phase4_pos_"+row+"_"+col).innerHTML = '<img align="middle" id="phase4img_'+row+'_'+col+'" title="Red cell override" src="images/phase4.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Red cell override"/>&nbsp;';
	  }
	  else
	  {
	 	// if(!confirm("Do you really want to unset phase4_override")) {document.getElementById("phase4opt_"+row+"_"+col).checked = true; return true;}
		 document.getElementById("phase4_val_"+row+"_"+col).value=0;
		 document.getElementById("phase4_pos_"+row+"_"+col).innerHTML = '';
	  }
	}
	
	if(type=='preclinical')
	{
	  if(document.getElementById("preclinicalopt_"+row+"_"+col).checked == true)
	  {
	  	document.getElementById("preclinical_val_"+row+"_"+col).value=1;
		document.getElementById("preclinical_pos_"+row+"_"+col).innerHTML = '<img align="middle" id="preclinicalimg_'+row+'_'+col+'" title="Preclinical swicth" src="images/preclinical.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Preclinical switch"/>&nbsp;';
	  }
	  else
	  {
	 	// if(!confirm("Do you really want to unset preclinical")) {document.getElementById("preclinicalopt_"+row+"_"+col).checked = true; return true;}
		 document.getElementById("preclinical_val_"+row+"_"+col).value=0;
		 document.getElementById("preclinical_pos_"+row+"_"+col).innerHTML = '';
	  }
	}
	
	if(type=='bomb')
	{
	  if(document.getElementById("bombopt_"+row+"_"+col).checked == true)
	  {
	  	//document.getElementById("bombpopup_"+row+"_"+col).style.display = 'block';
		
		var bk_bomb = document.getElementById("bk_bombselect_"+row+"_"+col).value;
		
		var bk_bomb = bk_bomb.replace(/\s+/g, '') ;
		if(bk_bomb == 'small')
		{
			document.getElementById("bombselect_"+row+"_"+col).value='small';
			var bomb_src='new_sbomb.png';
		}
		else if(bk_bomb == "large")
		{
			document.getElementById("bombselect_"+row+"_"+col).value='large';
			var bomb_src='new_lbomb.png';
		}
		else if(bk_bomb == 'none')
		{
			document.getElementById("bombselect_"+row+"_"+col).value='large';
			var bomb_src='new_lbomb.png';
		}
		
		document.getElementById("bomb_pos_"+row+"_"+col).innerHTML = '<img align="middle" id="bombimg_'+row+'_'+col+'" title="Edit bomb details" src="images/'+bomb_src+'" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Bomb" onclick="popup_show(\'bomb\', '+tot_rows+','+tot_cols+',\'bombpopup_'+row+'_'+col+'\', \'bombpopup_drag_'+row+'_'+col+'\', \'bombpopup_exit_'+row+'_'+col+'\', \'mouse\', -10, -10);" />&nbsp;';
		
		document.getElementById("bomb_explain_"+row+"_"+col).value = document.getElementById("bk_bomb_explain_"+row+"_"+col).value;
		}
	  else
	  {
	 	 document.getElementById("bomb_pos_"+row+"_"+col).innerHTML = '';
		 document.getElementById("bombselect_"+row+"_"+col).value='none';
		 document.getElementById("bomb_explain_"+row+"_"+col).value='';
		 document.getElementById("bombpopup_"+row+"_"+col).style.display = 'none';
	  }
	}
	
	if(type=='filing')
	{
	  if(document.getElementById("filingopt_"+row+"_"+col).checked == true)
	  {
	  	document.getElementById("filing_pos_"+row+"_"+col).innerHTML = '<img align="middle" id="filingimg_'+row+'_'+col+'" title="Edit filing" src="images/new_file.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Edit filing" onclick="popup_show(\'filing\', '+tot_rows+','+tot_cols+',\'filingpopup_'+row+'_'+col+'\', \'filingpopup_drag_'+row+'_'+col+'\', \'filingpopup_exit_'+row+'_'+col+'\', \'mouse\', -10, -10);" />&nbsp;';
		document.getElementById("filing_"+row+"_"+col).value=document.getElementById("bk_filing_"+row+"_"+col).value;
		document.getElementById("filing_presence_"+row+"_"+col).value = 1;
		//document.getElementById("filingpopup_"+row+"_"+col).style.display = 'block';
	  }
	  else
	  {
	 	 document.getElementById("filing_pos_"+row+"_"+col).innerHTML = '';
		 document.getElementById("filing_"+row+"_"+col).value = '';
		 document.getElementById("filingpopup_"+row+"_"+col).style.display = 'none';
		 document.getElementById("filing_presence_"+row+"_"+col).value = 0;
	  }
	}
	
	if(type=='phaseexp')
	{
	  if(document.getElementById("phaseexpopt_"+row+"_"+col).checked == true)
	  {
	  	document.getElementById("phaseexp_pos_"+row+"_"+col).innerHTML = '<img id="Phase_Explain_'+row+'_'+col+'" src="images/phaseexp.png" title="Edit phase explain" style="width: 20px; height: 20px; vertical-align: bottom; cursor: pointer; " alt="Phase explain" onclick="popup_show(\'phaseexp\', '+tot_rows+','+tot_cols+',\'phaseexppopup_'+row+'_'+col+'\', \'phaseexppopup_drag_'+row+'_'+col+'\', \'phaseexppopup_exit_'+row+'_'+col+'\', \'mouse\', -10, -10);" />&nbsp;';
		document.getElementById("phase_explain_"+row+"_"+col).value=document.getElementById("bk_phase_explain_"+row+"_"+col).value;
		document.getElementById("phaseexp_presence_"+row+"_"+col).value = 1;
		//document.getElementById("phaseexppopup_"+row+"_"+col).style.display = 'block';
	  }
	  else
	  {
	 	 document.getElementById("phaseexp_pos_"+row+"_"+col).innerHTML = '';
		 document.getElementById("phase_explain_"+row+"_"+col).value = '';
		 document.getElementById("phaseexppopup_"+row+"_"+col).style.display = 'none';
		 document.getElementById("phaseexp_presence_"+row+"_"+col).value = 0;
	  }
	}
	
	refresher(row, col, tot_rows, tot_cols);
}

function bicon_change(option, bomb_id, row, col, tot_rows, tot_cols)
{
	var bomb = document.getElementById('bomb_id');

	if(option.value == 'small')
	{
		document.getElementById("bomb_pos_"+row+"_"+col).innerHTML = '<img align="middle" id="bombimg_'+row+'_'+col+'" title="Edit bomb details" src="images/new_sbomb.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Large Bomb" onclick="popup_show(\'bomb\', '+tot_rows+','+tot_cols+',\'bombpopup_'+row+'_'+col+'\', \'bombpopup_drag_'+row+'_'+col+'\', \'bombpopup_exit_'+row+'_'+col+'\', \'mouse\', -10, -10);" />&nbsp;';
	}
	else if(option.value == 'large')
	{
		document.getElementById("bomb_pos_"+row+"_"+col).innerHTML = '<img align="middle" id="bombimg_'+row+'_'+col+'" title="Edit bomb details" src="images/new_lbomb.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Large Bomb" onclick="popup_show(\'bomb\', '+tot_rows+','+tot_cols+',\'bombpopup_'+row+'_'+col+'\', \'bombpopup_drag_'+row+'_'+col+'\', \'bombpopup_exit_'+row+'_'+col+'\', \'mouse\', -10, -10);" />&nbsp;';
	}
	else
	{
		document.getElementById("bomb_pos_"+row+"_"+col).innerHTML = '';
		document.getElementById("bombopt_"+row+"_"+col).checked = false;
	}
	
	refresher(row, col, tot_rows, tot_cols);	
}

function getCookie_value(name) 
{
	var nameEQ = name + "=";
	var ca = document.cookie.split( ';');
	for( var i=0;i < ca.length;i++) 
	{
	var c = ca[i];
	while ( c.charAt( 0)==' ') c = c.substring( 1,c.length);
	if ( c.indexOf( nameEQ) == 0) return c.substring( nameEQ.length,c.length);
	}
	return null;
}

function tree_grid_cookie(category_name)	///Categories listed in cookies will only be collapsed
{
	var present_flg=0; var New_cookie='';
	var Cookie_value=getCookie_value('tree_grid_cookie');
	if(Cookie_value != null && Cookie_value != "")
	{
		var Cookie_value_Arr = Cookie_value.split('****');
			
		for(var i=0; i<Cookie_value_Arr.length; i++)
		{
			if(Cookie_value_Arr[i] != '' && Cookie_value_Arr[i] != null)
			{
				if(Cookie_value_Arr[i] == escape(category_name))	///Check if category already present in our cookie, if present escape it.
					present_flg=1;
				else
				{
					if(New_cookie=='')
					New_cookie = Cookie_value_Arr[i];
					else
					New_cookie = New_cookie+'****'+Cookie_value_Arr[i];
				}
			}
			
		}
		if(!present_flg) New_cookie = New_cookie+'****'+escape(category_name);	//If cookie doesn't have category add it
		Cookie_value=New_cookie;
	}
	else
	{
		Cookie_value=escape(category_name);
	}
			
	var today = new Date();
 	var expire = new Date();//Cookie_value="";
 	expire.setTime(today.getTime() + 60*60*24*365*1000);
 	document.cookie ="tree_grid_cookie="+Cookie_value+ ";expires="+expire.toGMTString();
}

function validate(rows, cols)
{
	flag=0; phase4_flag=0; preclinical_flag=0; bomb_flag=0; filing_flag=0; phaseexp_flag=0; data=''; ele='';
	for(pt1=1; pt1<=rows; pt1++)
	{
		for(pt2=1; pt2<=cols; pt2++)
		{
			var entity2_ele = document.getElementById('columns'+pt2);
			var entity1_ele = document.getElementById('rows'+pt2);
			
			if((entity2_ele != null && entity2_ele !='') && (entity1_ele != null && entity1_ele !=''))
			{
				var entity2 = entity2_ele.value; var entity1 = entity1_ele.value;
			}
			
			var element = document.getElementById('phase4_val_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_phase4_val_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if(element.value == 0 && bk_element.value==1)
			{
				flag=1; phase4_flag=1;
			}
			
			var element = document.getElementById('preclinical_val_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_preclinical_val_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if(element.value == 0 && bk_element.value==1)
			{
				flag=1; preclinical_flag=1;
			}
			
			var element = document.getElementById('bombselect_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_bombselect_'+pt1+'_'+pt2);
			var element_expl = document.getElementById('bomb_explain_'+pt1+'_'+pt2);
			var bk_element_expl = document.getElementById('bk_bomb_explain_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if((element.value.replace(/\s+/g, '') == 'none') && (bk_element.value.replace(/\s+/g, '') != 'none'))
			{
				flag=1; bomb_flag=1;
			}
			
			var element = document.getElementById('filing_presence_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_filing_presence_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if(element.value == 0 && bk_element.value==1)
			{
				flag=1; filing_flag=1;
			}
			
			var element = document.getElementById('phaseexp_presence_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_phaseexp_presence_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if(element.value == 0 && bk_element.value==1)
			{
				flag=1; phaseexp_flag=1;
			}
			if(phase4_flag) ele='red cell override';
			if(preclinical_flag) ele='Preclinical Switch';
			if(bomb_flag) { if(ele != '') ele=ele+', '; ele=ele+'bomb'};
			if(filing_flag) { if(ele != '') ele=ele+', '; ele=ele+'filing'};
			if(phaseexp_flag) { if(ele != '') ele=ele+', '; ele=ele+'phase explain'};
			if(phase4_flag || bomb_flag || filing_flag || phaseexp_flag || preclinical_flag)
			data=data+' <font style="color:red">'+ele+'</font> of <font style="color:blue">entity1 '+entity1 +' X '+ 'entity2 '+ entity2 +'</font>; '
			
			phase4_flag=0; preclinical_flag=0; bomb_flag=0; filing_flag=0; phaseexp_flag=0; ele='';
		}
	}
	
	var message='';
	if(document.getElementById("delrep").checked == true)
	{
		//if(!confirm('You are going to delete report, Are you sure?')) 
		message = '<img align="middle" title="Warning" src="images/warning.png" style="width:20px; height:20px; vertical-align:top; cursor:pointer;" alt="Warning"> You are going to delete report, <b><font style="color:red">are you sure?</font></b>';
		//return false;
		document.getElementById("dialog").innerHTML = '<p>'+ message + '</p>';
		Dialog();
		return false;
	}
	else if(flag)
	{
		message='<img align="middle" title="Warning" src="images/warning.png" style="width:20px; height:20px; vertical-align:top; cursor:pointer;" alt="Warning">You are going to delete</b> '+data+' from this report, <b><font style="color:red">are you sure?</font></b>';
		//return confirm(message);
		document.getElementById("dialog").innerHTML = '<p>'+ message + '</p>';
		Dialog();
		return false;
	}
	else
	return true;
	
}

function Dialog()
{
	$(function(){
		// Dialog
		$('#dialog').dialog({
			autoOpen: true,
			width: 700,
			buttons: {
				"Ok": function() {
					//alert("You click OK");
					$(this).dialog("close");
					document.getElementById("reportsave_flg").value = 1;
					document.forms["master_heatmap"].submit();
				},
				"Cancel": function() {
					//alert("You click cancel");
					document.getElementById("reportsave_flg").value = 0;
					$(this).dialog("close");
				}
			}
			
		});	
	});
}

///Function refreshes the report such that if multiple instaces present of same entity1 X entity2 combination then its data made same
function refresher(row, col, tot_rows, tot_cols)
{
	var entity1_ele=document.getElementById("cell_entity1_"+row+"_"+col);
	var entity2_ele=document.getElementById("cell_entity2_"+row+"_"+col);
	entity1=entity1_ele.value.replace(/\s+/g, '');
	entity2=entity2_ele.value.replace(/\s+/g, '');
	
	for(pt1=1; pt1<=tot_rows; pt1++)
	{
		for(pt2=1; pt2<=tot_cols; pt2++)
		{

			var current_entity1_ele=document.getElementById("cell_entity1_"+pt1+"_"+pt2);
			var current_entity2_ele=document.getElementById("cell_entity2_"+pt1+"_"+pt2);
			
			if((current_entity1_ele != null && current_entity1_ele != '') && (current_entity2_ele != '' && current_entity2_ele != null) && (row != pt1 || col != pt2))
			{
				current_entity1=current_entity1_ele.value.replace(/\s+/g, '');
				current_entity2=current_entity2_ele.value.replace(/\s+/g, '');
				
				if((current_entity1 == entity1 && current_entity2 == entity2) || (current_entity2 == entity1 && current_entity1 == entity2))
				{
					/////Phase4 settings
					document.getElementById("phase4_val_"+pt1+"_"+pt2).value=document.getElementById("phase4_val_"+row+"_"+col).value;
					if(document.getElementById("phase4_val_"+row+"_"+col).value == 1)
					{
						document.getElementById("phase4_pos_"+pt1+"_"+pt2).innerHTML = '<img align="middle" id="phase4img_'+pt1+'_'+pt2+'" title="Red cell override" src="images/phase4.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Phase4_Override"/>&nbsp;';
						document.getElementById("phase4opt_"+pt1+"_"+pt2).checked = true;
					}
					else
					{
						document.getElementById("phase4_pos_"+pt1+"_"+pt2).innerHTML = '';
						document.getElementById("phase4opt_"+pt1+"_"+pt2).checked = false;
					}
					
					/////Preclinical settings
					document.getElementById("preclinical_val_"+pt1+"_"+pt2).value=document.getElementById("preclinical_val_"+row+"_"+col).value;
					if(document.getElementById("preclinical_val_"+row+"_"+col).value == 1)
					{
						document.getElementById("preclinical_pos_"+pt1+"_"+pt2).innerHTML = '<img align="middle" id="preclinicalimg_'+pt1+'_'+pt2+'" title="Preclinical switch" src="images/preclinical.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Preclinical switch"/>&nbsp;';
						document.getElementById("preclinicalopt_"+pt1+"_"+pt2).checked = true;
					}
					else
					{
						document.getElementById("preclinical_pos_"+pt1+"_"+pt2).innerHTML = '';
						document.getElementById("preclinicalopt_"+pt1+"_"+pt2).checked = false;
					}
					
					///////bomb settings
					 document.getElementById("bomb_explain_"+pt1+"_"+pt2).value= document.getElementById("bomb_explain_"+row+"_"+col).value;
					 var or_bomb = document.getElementById("bombselect_"+row+"_"+col).value;
					 var or_bomb = or_bomb.replace(/\s+/g, '') ;
					 if(or_bomb == 'small')
					 {
					 	document.getElementById("bombselect_"+pt1+"_"+pt2).value='small';
					 	document.getElementById("bomb_pos_"+pt1+"_"+pt2).innerHTML = '<img align="middle" id="bombimg_'+pt1+'_'+pt2+'" title="Edit Bomb Details" src="images/new_sbomb.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Small bomb" onclick="popup_show(\'bomb\', '+tot_rows+','+tot_cols+',\'bombpopup_'+pt1+'_'+pt2+'\', \'bombpopup_drag_'+pt1+'_'+pt2+'\', \'bombpopup_exit_'+pt1+'_'+pt2+'\', \'mouse\', -10, -10);" />&nbsp;';
						document.getElementById("bombopt_"+pt1+"_"+pt2).checked = true;
					 }
					 else if(or_bomb == "large")
					 {
					 	document.getElementById("bombselect_"+pt1+"_"+pt2).value='large';
					 	document.getElementById("bomb_pos_"+pt1+"_"+pt2).innerHTML = '<img align="middle" id="bombimg_'+pt1+'_'+pt2+'" title="Edit Bomb Details" src="images/new_lbomb.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Large bomb" onclick="popup_show(\'bomb\', '+tot_rows+','+tot_cols+',\'bombpopup_'+pt1+'_'+pt2+'\', \'bombpopup_drag_'+pt1+'_'+pt2+'\', \'bombpopup_exit_'+pt1+'_'+pt2+'\', \'mouse\', -10, -10);" />&nbsp;';
						document.getElementById("bombopt_"+pt1+"_"+pt2).checked = true;
					 }
					 else if(or_bomb == 'none')
					 {
					  	document.getElementById("bombselect_"+pt1+"_"+pt2).value='none';
					 	document.getElementById("bomb_pos_"+pt1+"_"+pt2).innerHTML = '';
						document.getElementById("bombopt_"+pt1+"_"+pt2).checked = false;
					 }
					 
					 ////Filing settings
					 document.getElementById("filing_"+pt1+"_"+pt2).value=document.getElementById("filing_"+row+"_"+col).value;
					 document.getElementById("filing_presence_"+pt1+"_"+pt2).value = document.getElementById("filing_presence_"+row+"_"+col).value;
					 
					 if(document.getElementById("filing_presence_"+row+"_"+col).value == 1)
					 {
					 	document.getElementById("filing_pos_"+pt1+"_"+pt2).innerHTML = '<img align="middle" id="filingimg_'+pt1+'_'+pt2+'" title="Edit filing" src="images/new_file.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Edit filing" onclick="popup_show(\'filing\', '+tot_rows+','+tot_cols+',\'filingpopup_'+pt1+'_'+pt2+'\', \'filingpopup_drag_'+pt1+'_'+pt2+'\', \'filingpopup_exit_'+pt1+'_'+pt2+'\', \'mouse\', -10, -10);" />&nbsp;';
						document.getElementById("filingopt_"+pt1+"_"+pt2).checked = true;
					 }
					 else
					 {

					 	document.getElementById("filing_pos_"+pt1+"_"+pt2).innerHTML = '';
						document.getElementById("filingopt_"+pt1+"_"+pt2).checked = false;
					 }
					 
					 ////Phase Explain settings
					 document.getElementById("phase_explain_"+pt1+"_"+pt2).value=document.getElementById("phase_explain_"+row+"_"+col).value;
					 document.getElementById("phaseexp_presence_"+pt1+"_"+pt2).value = document.getElementById("phaseexp_presence_"+row+"_"+col).value;
					 
					 if(document.getElementById("phaseexp_presence_"+row+"_"+col).value == 1)
					 {
					 	document.getElementById("phaseexp_pos_"+pt1+"_"+pt2).innerHTML = '<img id="Phase_Explain_'+pt1+'_'+pt2+'" src="images/phaseexp.png" title="Edit phase explain" style="width: 20px; height: 20px; vertical-align: bottom; cursor: pointer; " alt="Phase explain" onclick="popup_show(\'phaseexp\', '+tot_rows+','+tot_cols+',\'phaseexppopup_'+pt1+'_'+pt2+'\', \'phaseexppopup_drag_'+pt1+'_'+pt2+'\', \'phaseexppopup_exit_'+pt1+'_'+pt2+'\', \'mouse\', -10, -10);" />&nbsp;';
						document.getElementById("phaseexpopt_"+pt1+"_"+pt2).checked = true;
					 }
					 else
					 {
					 	document.getElementById("phaseexp_pos_"+pt1+"_"+pt2).innerHTML = '';
						document.getElementById("phaseexpopt_"+pt1+"_"+pt2).checked = false;
					 }
				} //if check for same entity1 entity2 ends
			} //if check for entity1 or entity2 element existence ends
		} //for llop of columns
	} //for loop of rows
}

</script>
<style type="text/css">
img { behavior: url("css/iepngfix.htc"); }
td {
background-color:#FFFFFF;
}
.Insert {
background-color:#FFFFFF;
}
.Total_Col{
width:150px;
min-width:150px;
max-width:150px;
}
.Extra_Insert_Col {
width:12.5px;
min-width:12.5px;
max-width:12.5px;
}
.Extra_Insert_FCol {
width:28px;
min-width:28px;
max-width:28px;
}
.normal_cells {
width:180px;
max-width:180px;
min-width:180px;
}
</style>
<link href="css/popup_form.css" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="css/chromestyle2.css" />
<script type="text/javascript" src="scripts/iepngfix_tilebg.js"></script>
<script type="text/javascript" src="scripts/popup-window.js"></script>
<script type="text/javascript" src="scripts/chrome.js"></script>
<link type="text/css" href="css/confirm_box.css" rel="stylesheet" />
<script type="text/javascript" src="scripts/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="scripts/jquery-ui-1.8.20.custom.min.js"></script>
<script type="text/javascript" src="scripts/autosuggest/jquery.autocomplete-min.js"></script>
<script type="text/javascript" src="progressbar/jquery.progressbar.js"></script>
<script type="text/javascript">
//// Below function SET all Row as Well as Column inserters at proper position, irrespective of type of browser after page load, as normal css causes position issue in different browser
$(function () {

	
	var col_num = document.getElementById('Count_Columns');
	var row_num = document.getElementById('Count_Rows');
	if(col_num != null && col_num != '' && row_num != null && row_num != '')
	{
		
		var count = 0;
		while(count <= col_num.value+1)
		{
			var Insert_Column = document.getElementById('Insert_Column_'+count);
			if(Insert_Column != null && Insert_Column != '')
			{
				if(count == 0)
				{
					var entity2_Cell_1 = document.getElementById('entity2_Cell_0');
					Insert_Column.style.width = (entity2_Cell_1.offsetWidth + 12.5)+'px';
				}
				else
				{
					if(col_num.value <= 6) var adj = (4.5 / col_num.value * count); else var adj = 0;
					var entity2_Cell_Other = document.getElementById('entity2_Cell_'+count);
					Insert_Column.style.width = (entity2_Cell_Other.offsetWidth - adj)+'px';
				}
			}
			count++;
		}
		
		
		var count = 0;
		while(count <= row_num.value+1)
		{
			var Insert_Row = document.getElementById('Insert_Row_'+count);
			if(Insert_Row != null && Insert_Row != '')
			{
				if(count == 0)
				{
					var entity1_Cell_1 = document.getElementById('entity2_Cell_0');
					Insert_Row.style.height = (entity1_Cell_1.offsetHeight + 12.5)+'px';
				}
				else
				{
					var entity1_Cell_1 = document.getElementById('entity1_Cell_'+count);
					Insert_Row.style.height = (entity1_Cell_1.offsetHeight)+'px';
				}
				
			}
			count++;
		}
		
	
	}
});
</script>
<?php

postRL();
postEd();

echo(reportListCommon('rpt_master_heatmap'));

if(!isset($_POST['delrep']) && !is_array($_POST['delrep'])) ///Below Function Should be skipped after delete Otherwise we will get report not found error after delete
echo(editor());

echo('</body></html>');
}
//return html for report editor
function editor()
{
	global $db;
	
	if(!isset($_GET['id'])) return;
	
	$id = mysql_real_escape_string(htmlspecialchars($_GET['id']));
	if(!is_numeric($id)) return;
	$query = 'SELECT name,user,footnotes,description,category,shared,total, dtt FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	
	/******** RECALCULATION STATUS  */
	//Get Process IDs of all currently running updates to check crashes
	$query = 'SELECT `update_id`,`process_id` FROM update_status_fullhistory WHERE `status`='. RUNNING . ' and left(trial_type,6)="RECALC" ';
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$count_upids=0;
	
	while($row = mysql_fetch_assoc($res))
	{
		$update_ids[$count_upids] = $row['update_id'];
		$update_pids[$count_upids++] = $row['process_id'];
	}
if($count_upids<>0)
{
	$err=array();
	$cmd = "ps aux|grep calculate";
	exec($cmd, $output, $result);
	for($i=0;$i < count($output); $i++)
	{
		$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
		$exp_out=explode(" ",$output[$i]);
		$running_pids[$i]=$exp_out[1];
	}

	//Check if any update has terminated abruptly
	for($i=0;$i < $count_upids; $i++)
	{
		if(is_array($running_pids) && count($running_pids) > 0 )
		{
			if(!in_array($update_pids[$i],$running_pids))
			{
				$err[$i]='yes';
			}
			else
			{
				$err[$i]='no';
			}
		}	
	}
	
	for($i=0;$i < $count_upids; $i++)
	{
		if((is_array($running_pids) && count($running_pids) > 0 && !in_array($update_pids[$i],$running_pids) ) and $err[$i]=='yes')
		{
	/*		$query = 'UPDATE update_status_fullhistory SET `status`="'.ERROR.'",`process_id`="0" WHERE `update_id`="' . $update_ids[$i].'"';
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
	*/
		}
			
	}
	
	/**************************************/



$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where left(trial_type,6)="RECALC" order by update_id desc limit 1 ';
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$recalc_status = array();
	while($row = mysql_fetch_assoc($res))
	$recalc_status = $row;
	
	echo "<script type=\"text/javascript\">";

	echo "$(document).ready(function() {";
	if(count($recalc_status)!=0)
	{
		echo "$(\"#recalc_new\").progressBar();";
		echo "$(\"#recalc_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
	}
	
	echo "});";

	echo "</script>";
}	
/*** RECALCULATION STATUS. ****/
	
	$query = 'SELECT `name`, `user`, `footnotes`, `description`, `category`, `shared`, `total`, `dtt`, `display_name` FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting heatmap report'.$query);
	$res = mysql_fetch_array($res) or die('Report not found.');
	$repoUser = $res['user'];
	$shared = $res['shared'];
	$total_fld=$res['total'];
	$dtt_fld=$res['dtt'];
	$Report_DisplayName=$res['display_name'];
	if($repoUser !== NULL && $repoUser != $db->user->id && !$shared && $db->user->userlevel != 'root') 
	{
		echo '<br clear="all"/><br/><div style="padding:10px;">&nbsp;&nbsp;<fieldset class="floatl"><legend> <b>Message:</b> </legend> <font style="color:#FF0000;">You are not authorized to view report: '. (strlen($res['name'])>0?$res['name']:('(report '.$res['id'].')')) .'.</font></fieldset></div>';
		return;	//prevent anyone from viewing others' private reports
	}
	$name = $res['name'];
	$footnotes = htmlspecialchars($res['footnotes']);
	$description = htmlspecialchars($res['description']);
	$category = $res['category'];
	
	if($shared && $repoUser !== NULL)
	{
		$owner_type = "shared";
		if($repoUser == $db->user->id)
			$owner_selector = "shared";
		else
			$owner_selector = "shared_other";
	}
	else if($repoUser === NULL)
	{
		$owner_type = "global";
		$owner_selector = "global";
	}
	else if($repoUser !== NULL)
	{
		if($repoUser == $db->user->id)
		{
			$owner_type = "mine";
			$owner_selector = "mine";
		}
		else
			$owner_selector = "mine_other";
	}
	
	$query = 'SELECT `num`,`type`,`type_id`, `display_name`, `category`, `tag` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' ORDER BY num ASC';
	$res = mysql_query($query) or die('Bad SQL query getting heatmap report headers'.$query);
	$rows = array();
	$columns = array();
	$entity2Ids = array();
	$entity1Ids = array();
	while($header = mysql_fetch_array($res))
	{
		if($header['type'] == 'column')
		{
			if($header['type_id'] != NULL)
			{
				$result =  mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `class` FROM `entities` WHERE id = '" . $header['type_id'] . "' "));
				$columns[$header['num']] = $result['name'];
				$columnsEntityType[$header['num']] = $result['class'];				
				$columnsCategoryName[$header['num']] = $header['category'];
			}
			else
			{
				$columns[$header['num']] = $header['type_id'];
			}
			$entity2Ids[$header['num']] = $header['type_id'];
			$columnsDisplayName[$header['num']] = $header['display_name']; ///Display name from master hm header table
			$columnsTagName[$header['num']] = $header['tag'];
		}
		else
		{
			if($header['type_id'] != NULL)
			{
				$result =  mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `class` FROM `entities` WHERE id = '" . $header['type_id'] . "' "));
				$rows[$header['num']] = $result['name'];
				$rowsEntityType[$header['num']] = $result['class'];
				$rowsCategoryName[$header['num']] = $header['category'];
			}
			else
			{
				$rows[$header['num']] = $header['type_id'];
			}
			$entity1Ids[$header['num']] = $header['type_id'];
			$rowsDisplayName[$header['num']] = $header['display_name']; ///Display name from master hm header table
			$rowsTagName[$header['num']] = $header['tag'];
		}
	}
	
	$entity2IdsInEntity1Names = $entity2Ids;
	if($dtt_fld)
	array_pop($entity2IdsInEntity1Names);
	
	// SELECT MAX ROW AND MAX COL
	$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'row\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_row = mysql_fetch_array($res);
	
	$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'column\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_column = mysql_fetch_array($res);
	
	$row_total=array();
	$col_total=array();
	$data_matrix=array();
	$active_total=0;
	$count_total=0;
	foreach($rows as $row => $rval)
	{
		foreach($columns as $col => $cval)
		{
			if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL)
			{
				$cell_query = 'SELECT * FROM rpt_masterhm_cells WHERE (`entity1`=' . $entity1Ids[$row] . ' AND `entity2`='. $entity2Ids[$col] .') OR (`entity2`=' . $entity1Ids[$row] . ' AND `entity1`='. $entity2Ids[$col] .')';
				$cell_res = mysql_query($cell_query) or die(mysql_error());
				$cell_data = mysql_fetch_array($cell_res);
				
				$col_active_total[$col]=$cell_data['count_active']+$col_active_total[$col];
				$row_active_total[$row]=$cell_data['count_active']+$row_active_total[$row];
				$col_count_total[$col]=$cell_data['count_total']+$col_count_total[$col];
				$row_count_total[$row]=$cell_data['count_total']+$row_count_total[$row];
				$col_indlead_total[$col]=$cell_data['count_active_indlead']+$col_indlead_total[$col];
				$row_indlead_total[$row]=$cell_data['count_active_indlead']+$row_indlead_total[$row];
				$col_active_owner_sponsored_total[$col]=$cell_data['count_active_owner_sponsored']+$col_active_owner_sponsored_total[$col];
				$row_active_owner_sponsored_total[$row]=$cell_data['count_active_owner_sponsored']+$row_active_owner_sponsored_total[$row];
				
				$active_total=$cell_data['count_active']+$active_total;
				$count_total=$cell_data['count_total']+$count_total;
				$indlead_total=$cell_data['count_active_indlead']+$indlead_total;
				$active_owner_sponsored_total=$cell_data['count_active_owner_sponsored']+$active_owner_sponsored_total;
				
				if($cell_data['count_active'] != '' && $cell_data['count_active'] != NULL)
				$data_matrix[$row][$col]['active']=$cell_data['count_active'];
				else
				$data_matrix[$row][$col]['active']=0;
				
				if($cell_data['count_total'] != '' && $cell_data['count_total'] != NULL)
				$data_matrix[$row][$col]['total']=$cell_data['count_total'];
				else
				$data_matrix[$row][$col]['total']=0;
				
				if($cell_data['count_active_indlead'] != '' && $cell_data['count_active_indlead'] != NULL)
				$data_matrix[$row][$col]['indlead']=$cell_data['count_active_indlead'];
				else
				$data_matrix[$row][$col]['indlead']=0;
				
				if($cell_data['count_active_owner_sponsored'] != '' && $cell_data['count_active_owner_sponsored'] != NULL)
					$data_matrix[$row][$col]['active_owner_sponsored']=$cell_data['count_active_owner_sponsored'];
				else
					$data_matrix[$row][$col]['active_owner_sponsored']=0;
				
				$data_matrix[$row][$col]['phase_explain']=$cell_data['phase_explain'];

				$data_matrix[$row][$col]['bomb_explain']=trim($cell_data['bomb_explain']);
				
				$data_matrix[$row][$col]['phase4_override']=$cell_data['phase4_override'];
				
				$data_matrix[$row][$col]['preclinical']=$cell_data['preclinical'];
				
				if($cell_data['bomb_auto'] == 'small')
				{
					$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$row][$col]['bomb_auto']['src']='new_sbomb.png';
					$data_matrix[$row][$col]['bomb_auto']['alt']='Small Bomb';
					$data_matrix[$row][$col]['bomb_auto']['style']='width:9px; height:11px;';
					$data_matrix[$row][$col]['bomb_auto']['title']='Suggested';
				}
				elseif($cell_data['bomb_auto'] == 'large')
				{
					$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$row][$col]['bomb_auto']['src']='new_lbomb.png';
					$data_matrix[$row][$col]['bomb_auto']['alt']='Large Bomb';
					$data_matrix[$row][$col]['bomb_auto']['style']='width:18px; height:20px;';
					$data_matrix[$row][$col]['bomb_auto']['title']='Suggested';
				}
				else
				{
					$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$row][$col]['bomb_auto']['src']='trans.gif';
					$data_matrix[$row][$col]['bomb_auto']['alt']='None';
					$data_matrix[$row][$col]['bomb_auto']['style']='width:18px; height:11px;';
					$data_matrix[$row][$col]['bomb_auto']['title']='';
				}
				
				
				if($cell_data['bomb'] == 'small')
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='new_sbomb.png';
					$data_matrix[$row][$col]['bomb']['alt']='Small Bomb';
					$data_matrix[$row][$col]['bomb']['style']='width:20px; height:20px;';
					$data_matrix[$row][$col]['bomb']['title']='Edit Small Bomb Details';
				}
				elseif($cell_data['bomb'] == 'large')
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='new_lbomb.png';
					$data_matrix[$row][$col]['bomb']['alt']='Large Bomb';
					$data_matrix[$row][$col]['bomb']['style']='width:20px; height:20px;';
					$data_matrix[$row][$col]['bomb']['title']='Edit Large Bomb Details';
				}
				else
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='new_square.png';
					$data_matrix[$row][$col]['bomb']['alt']='None';
					$data_matrix[$row][$col]['bomb']['style']='width:20px; height:20px;';
					$data_matrix[$row][$col]['bomb']['title']='Edit Bomb';
				}
				
				$data_matrix[$row][$col]['filing']=$cell_data['filing'];
				
				
				if($cell_data['highest_phase'] == 'N/A' || $cell_data['highest_phase'] == '' || $cell_data['highest_phase'] === NULL)
				{
					$data_matrix[$row][$col]['color']='background-color:#BFBFBF;';
					$data_matrix[$row][$col]['color_code']='BFBFBF';
				}
				else if($cell_data['highest_phase'] == '0')
				{
					$data_matrix[$row][$col]['color']='background-color:#00CCFF;';
					$data_matrix[$row][$col]['color_code']='00CCFF';
				}
				else if($cell_data['highest_phase'] == '1' || $cell_data['highest_phase'] == '0/1' || $cell_data['highest_phase'] == '1a' 
				|| $cell_data['highest_phase'] == '1b' || $cell_data['highest_phase'] == '1a/1b' || $cell_data['highest_phase'] == '1c')
				{
					$data_matrix[$row][$col]['color']='background-color:#99CC00;';
					$data_matrix[$row][$col]['color_code']='99CC00';
				}
				else if($cell_data['highest_phase'] == '2' || $cell_data['highest_phase'] == '1/2' || $cell_data['highest_phase'] == '1b/2' 
				|| $cell_data['highest_phase'] == '1b/2a' || $cell_data['highest_phase'] == '2a' || $cell_data['highest_phase'] == '2a/2b' 
				|| $cell_data['highest_phase'] == '2a/b' || $cell_data['highest_phase'] == '2b')
				{
					$data_matrix[$row][$col]['color']='background-color:#FFFF00;';
					$data_matrix[$row][$col]['color_code']='FFFF00';
				}
				else if($cell_data['highest_phase'] == '3' || $cell_data['highest_phase'] == '2/3' || $cell_data['highest_phase'] == '2b/3' 
				|| $cell_data['highest_phase'] == '3a' || $cell_data['highest_phase'] == '3b')
				{
					$data_matrix[$row][$col]['color']='background-color:#FF9900;';
					$data_matrix[$row][$col]['color_code']='F9900';
				}
				else if($cell_data['highest_phase'] == '4' || $cell_data['highest_phase'] == '3/4' || $cell_data['highest_phase'] == '3b/4')
				{
					$data_matrix[$row][$col]['color']='background-color:#FF0000;';	
					$data_matrix[$row][$col]['color_code']='FF0000';
				}
				
				$data_matrix[$row][$col]['last_update']=$cell_data['last_update'];
				$data_matrix[$row][$col]['count_lastchanged']=$cell_data['count_lastchanged'];
				$data_matrix[$row][$col]['bomb_lastchanged']=$cell_data['bomb_lastchanged'];
				$data_matrix[$row][$col]['filing_lastchanged']=$cell_data['filing_lastchanged'];
				$data_matrix[$row][$col]['phase_explain_lastchanged']=$cell_data['phase_explain_lastchanged'];
				$data_matrix[$row][$col]['phase4_override_lastchanged']=$cell_data['phase4_override_lastchanged'];
				
				$data_matrix[$row][$col]['active_prev']=$cell_data['count_active_prev'];
				$data_matrix[$row][$col]['total_prev']=$cell_data['count_total_prev'];
				$data_matrix[$row][$col]['indlead_prev']=$cell_data['count_active_indlead_prev'];
				$data_matrix[$row][$col]['active_owner_sponsored_prev']=$cell_data['count_active_owner_sponsored_prev'];
				
			}
			else
			{
				$data_matrix[$rid][$cid]['active']=0;
				$data_matrix[$rid][$cid]['total']=0;
				$data_matrix[$rid][$cid]['indlead']=0;
				$data_matrix[$rid][$cid]['active_owner_sponsored']=0;
				
				$col_active_total[$col]=0+$col_active_total[$col];
				$row_active_total[$row]=0+$row_active_total[$row];
				$col_count_total[$col]=0+$col_count_total[$col];
				$row_count_total[$row]=0+$row_count_total[$row];
				$col_count_indlead[$col]=0+$col_count_indlead[$col];
				$row_count_indlead[$row]=0+$row_count_indlead[$row];
				$col_active_owner_sponsored[$col]=0+$col_active_owner_sponsored[$col];
				$row_active_owner_sponsored[$row]=0+$row_active_owner_sponsored[$row];
				
				$data_matrix[$row][$col]['bomb_auto']['src']='';
				$data_matrix[$row][$col]['bomb']['src']='';
				$data_matrix[$row][$col]['bomb_explain']='';
				$data_matrix[$row][$col]['filing']='';
				$data_matrix[$row][$col]['color']='background-color:#DDF;';
			}
		}
	}

	if($_GET['view_type']=='total')
	{
		$title="All trials (Active+Inactive)";
		$view_tp='total';
		$link_part = '&list=2&hm=' . $id;
	}
	else if($_GET['view_type']=='active')
	{
		$title="Active trials";
		$view_tp='active';
		$link_part = '&list=1&hm=' . $id;
	}
	else if($_GET['view_type']=='active_owner_sponsored')
	{
		$title="Active owner sponsored trials";
		$view_tp='active_owner_sponsored';
		$link_part = '&list=1&osflt=on&hm=' . $id;
	}
	else
	{
		$title="Active industry lead sponsor trials";
		$view_tp='indlead';
		$link_part = '&list=1&itype=0&hm=' . $id;
	}

	$out = '<br/>&nbsp;&nbsp;<b>View type: </b> <select id="view_type" name="view_type" onchange="window.location.href=\'master_heatmap.php?id='.$_GET['id'].'&view_type=\'+this.value+\'\'">'
			. '<option value="indlead"'.(($view_tp=='indlead')? "selected=\"selected\"":"").'>Active Industry trials</option>'
			. '<option value="active_owner_sponsored"'.(($view_tp=='active_owner_sponsored')? "selected=\"selected\"":"").'>Active owner-sponsored trials</option>'
			. '<option value="active" '.(($view_tp=='active')? "selected=\"selected\"":"").'>Active trials</option>'
			. '<option value="total" '.(($view_tp=='total')? "selected=\"selected\"":"").'>All trials</option></select><br/>';
			
	$out .= '<form action="master_heatmap.php" method="post">'
			. '<fieldset><legend>Download Option</legend>'
			. '<input type="hidden" name="id" value="' . $id . '" />'
			. '<input type="hidden" name="pageType" id="pageType" value="editPage" />';
	if($total_fld)
	{
		$out .='<input type="hidden" name="total_col" value="1" />';
	}
	$out .='<b>Which format: </b><select id="dwformat" name="dwformat"><option value="htmldown" selected="selected">HTML</option>'
		. '<option value="exceldown">Excel</option>'
		. '<option value="pdfdown">PDF</option>'
		. '</select><br/><br/>';
	$out .='<b>Counts display: </b><select id="dwcount" name="dwcount">'
		. '<option value="indlead" '.(($view_tp=='indlead')? "selected=\"selected\"":"").'>Active industry trials</option>'
		. '<option value="active_owner_sponsored"'.(($view_tp=='active_owner_sponsored')? "selected=\"selected\"":"").'>Active owner-sponsored trials</option>'
		. '<option value="active" '.(($view_tp=='active')? "selected=\"selected\"":"").'>Active trials</option>'
		. '<option value="total" '.(($view_tp=='total')? "selected=\"selected\"":"").'>All trials</option></select><br/><br/><input type="submit" name="download" value="Download" title="Download" />'
		. '</fieldset></form>';	
		
	/*$out .='<input type="image" name="htmldown[]" src="images/html.png" title="HTML Download" />&nbsp;&nbsp;'
		. '<input type="image" name="pdfdown[]" src="images/pdf.png" title="PDF Download" />&nbsp;&nbsp;'
		. '<input type="image" name="exceldown[]" src="images/excel_new.png" title="Excel Download" /></div></form>';		*/
	
	/**Recalculate button***/
	//check if the  HM is being recalculated
		$id = mysql_real_escape_string($_GET['id']);	 
		$query = 'SELECT `update_id`,`process_id`,`start_time`,`end_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where status="2" 
						 and trial_type="RECALC='. $id . '"  order by update_id desc limit 1 ';
				 
		if(!$res1 = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		$row1 = mysql_fetch_assoc($res1);
	//	$recalc_status = array();
	//	while($row = mysql_fetch_assoc($res))
		$recalc_status = $row1;
								
		
		if( isset($row1['update_id']) )
		{
		
					if($recalc_status['status']==COMPLETED)
						$recalc_update_progress=100;
					else
						$recalc_update_progress=number_format(($recalc_status['update_items_total']==0?0:(($recalc_status['update_items_progress'])*100/$recalc_status['update_items_total'])),2);

		
		
		
			$out .=  "<br clear=\"both\" />&nbsp;&nbsp;&nbsp;Recalculation status: <span class=\"progressBar\" id=\"recalc_update\">".$recalc_update_progress."</span>";
			
		}
		else 
		{
			$out .= '<br clear="both" />'.
			'
			<form action="master_heatmap.php?id=' . $id . '" name="rc" id="rd" method="post" />
			<input type="submit" name="recalc" id="recalc" value="Recalculate all values" onclick="this.form.target=\'_blank\';return true;">
			
			</form>
			';
		
		}
	
	
	/****/
	
	$disabled=0;
	if(($owner_type == 'shared' && $repoUser != $db->user->id) || ($owner_type == 'global' && $db->user->userlevel == 'user'))
	$disabled=1;
	if($db->user->userlevel == 'root') $disabled=0;
	
	$out .= '<br clear="both" /><form action="master_heatmap.php" name="master_heatmap" onsubmit="return validate('.count($rows).','.count($columns).');" method="post"><fieldset><legend>Edit report ' . $id . '</legend>'
		. '<input type="hidden" name="id" value="' . $id . '" />'
		. '<label>Name: <input type="text" '.(($disabled) ? ' readonly="readonly" ':'').' '
		. 'name="reportname" value="' . htmlspecialchars($name) . '"/></label>'
		. '<label>Display name: <input type="text" '.(($disabled) ? ' readonly="readonly" ':'').' '
		. 'name="report_displayname" value="' . htmlspecialchars($Report_DisplayName) . '"/></label>'
		. '<label>Category: <input type="text" '.(($disabled) ? ' readonly="readonly" ':'').' '
		. 'name="reportcategory" value="' . htmlspecialchars($category)
		. '"/></label>';		
	
	if($repoUser !== NULL)
	{
		$owner_name_query = 'SELECT `username`, `userlevel` FROM `users` WHERE id=' . $repoUser;
		$owner_res = mysql_query($owner_name_query) or die('Bad SQL query retrieving username in heatmap report');
		if(mysql_num_rows($owner_res) > 0)
		{
			while($owner_row = mysql_fetch_array($owner_res))
			{
				$owner_name=$owner_row['username'];
				$owner_level=$owner_row['userlevel'];
			}
		}
	}
	if($db->user->userlevel != 'user')
	{
		$out .= ' Ownership: '
			. '<label><input type="radio" name="own" value="global" '
			. ($owner_selector == 'global' ? 'checked="checked"' : '')
			. (($disabled) ? ' disabled="disabled" ':'')
			. '/>Global</label> '
			. '<label><input type="radio" name="own" value="shared" '
			. ($owner_selector == 'shared' ? 'checked="checked"' : '')
			. (($disabled) ? ' disabled="disabled" ':'')
			. '/>Mine (Shared)</label> '
			. '<label><input type="radio" name="own" value="mine" '
			. ($owner_selector == 'mine' ? 'checked="checked"' : '')
			. (($disabled) ? ' disabled="disabled" ':'')
			. '/>Mine (Private)</label> ';
			
		if(($db->user->userlevel == 'root' || $db->user->userlevel == 'admin') && $repoUser != $db->user->id && $repoUser !== NULL && $owner_level != 'user')
		$out .='<label><input type="radio" name="own" value="shared_other" '.($owner_selector == 'shared_other' ? 'checked="checked"' : '')
			. (($disabled) ? ' disabled="disabled" ':'')
			. '/>'.$owner_name.' (Shared)</label> ';
			
		if($db->user->userlevel == 'root' && $repoUser != $db->user->id && $repoUser !== NULL)	
		$out .='<label><input type="radio" name="own" value="mine_other" '
			. ($owner_selector == 'mine_other' ? 'checked="checked"' : '')
			. (($disabled) ? ' disabled="disabled" ':'')
			. '/>'.$owner_name.' (Private)</label>';
	}else{
		$out .= ' Ownership: '
			. ($owner_selector == 'global' ? 'Global <input type="hidden" name="own" value="global"/> ' : '')
			. ($owner_selector == 'shared' ? 'Mine (Shared) <input type="hidden" name="own" value="shared"/> ' : '')
			. ($owner_selector == 'mine' ? 'Mine (Private) <input type="hidden" name="own" value="mine"/> ' : '');
			
		$out .= ($owner_selector == 'shared_other' ? $owner_name.' (Shared) <input type="hidden" name="own" value="shared_other"/> ' : '');
		
		$out .= ($owner_selector == 'mine_other' ? $owner_name.' (Private) <input type="hidden" name="own" value="mine_other"/> ' : '');
	}
	
	//total column checkbox
	$out .= ' <label><input '.(($disabled) ? ' disabled="disabled" ':'').' type="checkbox" name="total"  value="1" ' . (($total_fld) ? 'checked="checked"' : '') . ' />Total</label>';
	$out .= ' <label><input '.(($disabled) ? ' disabled="disabled" ':'').' type="checkbox" name="dtt"  value="1" ' . (($dtt_fld) ? 'checked="checked"' : '') . ' />Last column is DTT</label>';
	
	$out .= '<br clear="all"/>';
	
	$out .= '<input type="submit" name="reportsave" value="Save edits" /><input type="hidden" id="reportsave_flg" name="reportsave_flg" value="0" /> | ';
	
	
	$out .= '<input type="submit" name="reportcopy" value="Copy into new" /> | '
			. '<a href="masterhm_report_inputcheck.php?id=' . $id . '">Input check</a> | '
			. '<a href="product_tracker.php?id=' . $id . '" target="_blank">Product Tracker</a>';
			
	$out .= '<br/><table style="background-color:#FFFFFF; table-layout:fixed;"><tr><th style="background-color:#FFFFFF;" class="Extra_Insert_FCol"></th><th class="Insert" id="Insert_Column_0">'.(($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $repoUser == $db->user->id) || $db->user->userlevel == 'root') ? '<input type="image" name="insert_column[0]" src="images/insert_column.png" title="Insert Column" align="right"/>':'').'</th>';
	foreach($columns as $col => $val)
	$out .= '<th class="Insert" id="Insert_Column_'.$col.'">'.(($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $repoUser == $db->user->id) || $db->user->userlevel == 'root') ? '<input type="image" name="insert_column[' . $col . ']" src="images/insert_column.png" title="Insert Column" align="right"/>':'').'</th>';
	$out .= '</tr></table>';
	
	$out .= '<table style="background-color:#FFFFFF;"><tr><td valign="top"  style="vertical-align:top;"><table style="background-color:#FFFFFF;"><tr><th class="Insert" id="Insert_Row_0" valign="bottom"  style="vertical-align:bottom;">'.(($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $repoUser == $db->user->id) || $db->user->userlevel == 'root') ? '<input type="image" name="insert_row[0]" src="images/insert_row.png" title="Insert Column" valign="bottom"  style="vertical-align:bottom;"/>':'').'</th></tr>';
	foreach($rows as $row => $rval)
	$out .= '<tr><th class="Insert" valign="bottom"  style="vertical-align:bottom;" id="Insert_Row_'.$row.'">'.(($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $repoUser == $db->user->id) || $db->user->userlevel == 'root') ? '<input type="image" name="insert_row[' . $row . ']" src="images/insert_row.png" title="Insert Column" valign="bottom"  style="vertical-align:bottom;"/>':'').'</th></tr>';
	
	$out .= '</table></td><td valign="top"  style="vertical-align:top;">';
	
	$out .= '<table class="reportcell" style="background-color:#FFFFFF;"><tr><th class="normal_cells" id="entity2_Cell_0"></th>';
			
	foreach($columns as $col => $val)
	{
		$out .= '<th valign="top" class="normal_cells" id="entity2_Cell_'.$col.'">Entity 2:<br/><input type="text" id="columns' . $col . '" name="columns[' . $col . ']" value="' . $val . '" autocomplete="off" '
				. ' onkeyup="javascript:autoComplete(\'columns'.$col.'\')" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
				
		$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '' && $columnsDisplayName[$col] != 'NULL')?$columnsDisplayName[$col]:'';
		$out .= 'Display name: <br/><input type="text" id="columns_display' . $col . '" name="columns_display[' . $col . ']" value="' . $val . '" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
		
		$cat = (isset($columnsCategoryName[$col]) && $columnsCategoryName[$col] != '' && $columnsCategoryName[$col] != 'NULL')?$columnsCategoryName[$col]:'';
		$out .= 'Category name: <br/><input type="text" id="category_column' . $col . '" name="category_column[' . $col . ']" value="' . $cat . '" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
		
		$tag = (isset($columnsTagName[$col]) && $columnsTagName[$col] != '' && $columnsTagName[$col] != 'NULL')?$columnsTagName[$col]:'';
		$out .= 'Tag: <br/><input type="text" id="tag_column' . $col . '" name="tag_column[' . $col . ']" value="' . $tag . '" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
		
		$type = (isset($columnsEntityType[$col]) && $columnsEntityType[$col] != '' && $columnsEntityType[$col] != 'NULL')?$columnsEntityType[$col]:'';
		$out .= '<input type="hidden" id="type_column' . $col . '" name="type_column[' . $col . ']" value="' . $type . '" '.(($disabled) ? ' readonly="readonly" ':'').' />';
				
		$out .= 'Column : '.$col.' ';
		
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared') || $db->user->userlevel == 'root')
		{
			// LEFT ARROW?
			if($col > 1) $out .= ' <input type="image" name="move_col_left[' . $col . ']" src="images/left.png" title="Left"/>';
			// RIGHT ARROW?
			if($col < $max_column['num']) $out .= ' <input type="image" name="move_col_right[' . $col . ']" src="images/right.png" title="Right" />';
				
			$out .='&nbsp;&nbsp;';	
		}
			if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $repoUser == $db->user->id) || $db->user->userlevel == 'root')
		{
			$out .= '<label class="lbldeln"><input type="checkbox" name="deletecol[' . $col . ']" title="Delete Column '.$col.'"/></label>';
		}
		$out .='<br/>';
		if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
		{
			if($view_tp=='active')
			{
				$count_val='<b>'.$col_active_total[$col].'</b>';
			}
			else if($view_tp=='total')
			{
				$count_val=$col_count_total[$col];
			}
			else if($view_tp =='indlead')
			{
				$count_val=$col_indlead_total[$col];
			}
			else if($view_tp =='active_owner_sponsored')
			{
				$count_val=$col_active_owner_sponsored_total[$col];
			}
			
			//In case of last column DTT just get hm id and view type
			$out .= '<a href="intermediary.php?' . (($dtt_fld && ($max_column['num'] == $col)) ? substr($link_part, 1) : 'e2='.$entity2Ids[$col] . $link_part) .'" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a>';
		
		
		}
		$out .='<br/>';
		$out .= '</th>';
	}
	//if total checkbox is selected
	if($total_fld)
	{
		$out .= '<th width="150px" class="Total_Col">';
		if(!empty($entity1Ids) && !empty($entity2Ids))
		{
			if($view_tp=='active')
			{
				$count_val='<b>'.$active_total.'</b>';
			}
			else if($view_tp=='total')
			{
				$count_val=$count_total;
			}
			else if($view_tp == 'indlead')
			{
				$count_val='<b>'.$indlead_total.'</b>';
			}
			else if($view_tp == 'active_owner_sponsored')
			{
				$count_val='<b>'.$active_owner_sponsored_total.'</b>';
			}
				
			$entity1Ids = array_filter($entity1Ids);
			$entity2Ids = array_filter($entity2Ids);
			$out .= '<a href="intermediary.php?e1=' . implode(',', $entity1Ids) . '&e2=' . implode(',', $entity2IdsInEntity1Names). $link_part . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a>';
		}
		$out .= '</th>';
	}
	
	// Extra column for proper arrangement of inserteres
	if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $repoUser == $db->user->id) || $db->user->userlevel == 'root')
	{
		$out .= '<th style="background-color:#FFFFFF;"><img src="images/insert_column.png" style="visibility:hidden"></th>';
	}
	
	$out .= '</tr>';
	foreach($rows as $row => $rval)
	{
		$out .= '<tr><th class="normal_cells" id="entity1_Cell_'.$row.'">Entity 1:<br/><input type="text" id="rows' . $row . '"  name="rows[' . $row . ']" value="' . $rval . '" autocomplete="off" '
				. ' onkeyup="javascript:autoComplete(\'rows'.$row.'\')" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
				
		$val = (isset($rowsDisplayName[$row]) && $rowsDisplayName[$row] != '' && $rowsDisplayName[$row] != 'NULL')?$rowsDisplayName[$row]:'';
		$out .= 'Display name: <br/><input type="text" id="rows_display' . $row . '" name="rows_display[' . $row . ']" value="' . $val . '" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
				
		$cat = (isset($rowsCategoryName[$row]) && $rowsCategoryName[$row] != '' && $rowsCategoryName[$row] != 'NULL')?$rowsCategoryName[$row]:'';
		$out .= 'Category name: <br/><input type="text" id="category_row' . $row . '" name="category_row[' . $row . ']" value="' . $cat . '" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
		
		$tag = (isset($rowsTagName[$row]) && $rowsTagName[$row] != '' && $rowsTagName[$row] != 'NULL')?$rowsTagName[$row]:'';
		$out .= 'Tag: <br/><input type="text" id="tag_row' . $row . '" name="tag_row[' . $row . ']" value="' . $tag . '" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
		
		$type = (isset($rowsEntityType[$row]) && $rowsEntityType[$row] != '' && $rowsEntityType[$row] != 'NULL')?$rowsEntityType[$row]:'';
		$out .= '<input type="hidden" id="type_row' . $row . '" name="type_row[' . $row . ']" value="' . $type . '" '.(($disabled) ? ' readonly="readonly" ':'').' />';
		
		$out .= 'Row : '.$row.' ';
		
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared') || $db->user->userlevel == 'root')
		{
			// UP ARROW?
			if($row > 1) $out .= ' <input type="image" name="move_row_up[' . $row . ']" src="images/asc.png" title="Up"/>';
			// DOWN ARROW?
			if($row < $max_row['num']) $out .= ' <input type="image" name="move_row_down[' . $row . ']" src="images/des.png" title="Down"/>';
			
			$out .='&nbsp;&nbsp;';	
		}
		
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $repoUser == $db->user->id) || $db->user->userlevel == 'root')
		{
			$out .= '<label class="lbldeln"><input type="checkbox" name="deleterow[' . $row . ']" title="Delete Column '.$row.'" /></label>';
		}
		$out .='<br/>';
		if(isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL && !empty($entity2Ids))
		{
			if($view_tp=='active')
			{
				$count_val='<b>'.$row_active_total[$row].'</b>';
			}
			else if($view_tp=='total')
			{
				$count_val=$row_count_total[$row];
			}
			else if($view_tp == 'indlead')
			{
				$count_val='<b>'.$row_indlead_total[$row].'</b>';
			}
			else if($view_tp == 'active_owner_sponsored')
			{
				$count_val='<b>'.$row_active_owner_sponsored_total[$row].'</b>';
			}
				
			$out .= '<a href="intermediary.php?e1=' . $entity1Ids[$row] . $link_part . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a>';
		}
		$out .='<br/>';
		$out .= '</th>';
		
		foreach($columns as $col => $cval)
		{
			$out .= '<td valign="middle" align="center" style="text-align:center;'.$data_matrix[$row][$col]['color'].'"><br>';
			
			if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL)
			{
				if($data_matrix[$row][$col]['bomb_auto']['src'] != '' && $data_matrix[$row][$col]['bomb_auto']['src'] != NULL)
				$out .= '<img title="'.$data_matrix[$row][$col]['bomb_auto']['title'].'" src="images/'.$data_matrix[$row][$col]['bomb_auto']['src'].'" style="'.$data_matrix[$row][$col]['bomb_auto']['style'].' cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb_auto']['alt'].'"  />';
				
				if($view_tp=='active')
				{
					$count_val='<b>'.$data_matrix[$row][$col]['active'].'</b>';
					$prev_count_val='<b>'.$data_matrix[$row][$col]['active'].'</b>';
				}
				else if($view_tp=='total')
				{
					$count_val=$data_matrix[$row][$col]['total'];
					$prev_count_val=$data_matrix[$row][$col]['total'];
				}
				else if($view_tp == 'indlead')
				{
					$count_val='<b>'.$data_matrix[$row][$col]['indlead'].'</b>';
					$prev_count_val='<b>'.$data_matrix[$row][$col]['indlead_prev'].'</b>';
				}
				else if($view_tp == 'active_owner_sponsored')
				{
					$count_val='<b>'.$data_matrix[$row][$col]['active_owner_sponsored'].'</b>';
					$prev_count_val='<b>'.$data_matrix[$row][$col]['active_owner_sponsored_prev'].'</b>';
				}
					
				$out .= '<a href="intermediary.php?e1=' . $entity1Ids[$row] . '&e2=' . $entity2Ids[$col] . $link_part . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a><br/><br/>';
				
				$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['phase4_override']) ? '1':'0') . ' " name="phase4_val['.$row.']['.$col.']" id="phase4_val_'.$row.'_'.$col.'" />';
				$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['phase4_override']) ? '1':'0') . ' " name="bk_phase4_val['.$row.']['.$col.']" id="bk_phase4_val_'.$row.'_'.$col.'" />';
				
				$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['preclinical']) ? '1':'0') . ' " name="preclinical_val['.$row.']['.$col.']" id="preclinical_val_'.$row.'_'.$col.'" />';
				$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['preclinical']) ? '1':'0') . ' " name="bk_preclinical_val['.$row.']['.$col.']" id="bk_preclinical_val_'.$row.'_'.$col.'" />';
				
				$out .= '<input type="hidden" id="cell_entity1_'.$row.'_'.$col.'" name="cell_entity1['.$row.']['.$col.']" value="'. $entity1Ids[$row] .'" />'
						.'<input type="hidden" id="cell_entity2_'.$row.'_'.$col.'" name="cell_entity2['.$row.']['.$col.']" value="' . $entity2Ids[$col] . '" />'
						.'<input type="hidden" name="filing_presence['.$row.']['.$col.']" id="filing_presence_'.$row.'_'.$col.'" value="' . (($data_matrix[$row][$col]['filing'] != NULL)? 1:0) . '" />'
						.'<input type="hidden" name="phaseexp_presence['.$row.']['.$col.']" id="phaseexp_presence_'.$row.'_'.$col.'" value="' . (($data_matrix[$row][$col]['phase_explain'] != NULL)? 1:0) . '" />'
						.'<input type="hidden" name="bk_filing_presence['.$row.']['.$col.']" id="bk_filing_presence_'.$row.'_'.$col.'" value="' . (($data_matrix[$row][$col]['filing'] != NULL)? 1:0) . '" />'
						.'<input type="hidden" name="bk_phaseexp_presence['.$row.']['.$col.']" id="bk_phaseexp_presence_'.$row.'_'.$col.'" value="' . (($data_matrix[$row][$col]['phase_explain'] != NULL)? 1:0) . '" />';
				
				
				$out .= '<div style="float:left;"><font id="phase4_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['phase4_override'])
				$out .= '<img align="middle" id="phase4img_'.$row.'_'.$col.'" title="Red cell override" src="images/phase4.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Phase4_Override"/>&nbsp;';
				$out .= '</font>';
				
				$out .= '<font id="preclinical_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['preclinical'])
				$out .= '<img align="middle" id="preclinicalimg_'.$row.'_'.$col.'" title="Preclinical switch" src="images/preclinical.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Preclinical switch"/>&nbsp;';
				$out .= '</font>';
				
				$out .= '<font id="bomb_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png')
				$out .= '<img align="middle" id="bombimg_'.$row.'_'.$col.'" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="images/'.$data_matrix[$row][$col]['bomb']['src'].'" style="'.$data_matrix[$row][$col]['bomb']['style'].' vertical-align:bottom; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb']['alt'].'"'
			.'onclick="popup_show(\'bomb\', '.count($rows).','.count($columns).',\'bombpopup_'.$row.'_'.$col.'\', \'bombpopup_drag_'.$row.'_'.$col.'\', \'bombpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />&nbsp;';
				$out .= '</font>';
			
				$out .= '<font id="filing_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['filing'] != NULL)
				$out .= '<img align="middle" id="filingimg_'.$row.'_'.$col.'" title="Edit filing" src="images/new_file.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Edit Filing" onclick="popup_show(\'filing\', '.count($rows).','.count($columns).',\'filingpopup_'.$row.'_'.$col.'\', \'filingpopup_drag_'.$row.'_'.$col.'\', \'filingpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />&nbsp;';
				$out .= '</font>';
				
				$out .= '<font id="phaseexp_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['phase_explain'] != NULL)
				$out .= '<img align="middle" id="Phase_Explain_'.$row.'_'.$col.'" src="images/phaseexp.png" title="Edit phase explain" style="width: 20px; height: 20px; vertical-align: bottom; cursor: pointer;" alt="Phase Explain" onclick="popup_show(\'phaseexp\', '.count($rows).','.count($columns).',\'phaseexppopup_'.$row.'_'.$col.'\', \'phaseexppopup_drag_'.$row.'_'.$col.'\', \'phaseexppopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />&nbsp;';
				$out .= '</font></div>';
				
				
				$out .= '<div align="right" style="height:25px; vertical-align: bottom; cursor:pointer; float:right;" class="chromestyle" id="chromemenu_'.$row.'_'.$col.'"><ul><li><a rel="dropmenu_'.$row.'_'.$col.'"><b>+<img src="images/down.gif" border="0" style="width:9px; height:5px;" /><b></a></li></ul></div>';
				
				
				
				$out .= '<div id="dropmenu_'.$row.'_'.$col.'" class="dropmenudiv" style="width: 180px;">'
					 .'<a style="vertical-align:bottom;"><input  id="bombopt_'.$row.'_'.$col.'"  name="bombopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png') ? 'checked="checked"':'') . ' onchange="update_icons(\'bomb\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" />Small/Large bomb&nbsp;<img align="right" src="images/new_lbomb.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="filingopt_'.$row.'_'.$col.'"  name="filingopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['filing'] != NULL) ? 'checked="checked"':'') . '  onchange="update_icons(\'filing\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" />Filing&nbsp;<img align="right" src="images/new_file.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="phase4opt_'.$row.'_'.$col.'"  name="phase4opt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['phase4_override']) ? 'checked="checked"':'') . '  onchange="update_icons(\'phase4\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" />Red cell override&nbsp;<img align="right" src="images/phase4.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="phaseexpopt_'.$row.'_'.$col.'"  name="phaseexpopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['phase_explain'] != NULL) ? 'checked="checked"':'') . '  onchange="update_icons(\'phaseexp\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" />Phase explain&nbsp;<img align="right" src="images/phaseexp.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="preclinicalopt_'.$row.'_'.$col.'"  name="preclinicalopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['preclinical']) ? 'checked="checked"':'') . '  onchange="update_icons(\'preclinical\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" />Preclinical switch&nbsp;<img align="right" src="images/preclinical.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'</div>';
					 
				$out .= '<script type="text/javascript">cssdropdown.startchrome("chromemenu_'.$row.'_'.$col.'");</script>';
				
				
				$out .= '<div class="popup_form" id="bombpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
						.'<div class="menu_form_header" id="bombpopup_drag_'.$row.'_'.$col.'" style="width:300px;">'
						.'<img class="menu_form_exit" align="right" id="bombpopup_exit_'.$row.'_'.$col.'" src="images/fancy_close.png" style="width:30px; height:30px; " '		
						.'alt="" />&nbsp;&nbsp;&nbsp;'
						.'</div>'
						.'<div class="menu_form_body" style="width:300px;">'
						.'<table style="background-color:#fff;">'
						.'<tr><td style="background-color:#fff;">'
						.'<font style="color:#206040; font-weight: 900;"><br/>&nbsp;Bomb value: </font> <font style="color:#000000; font-weight: 900;">';
						
						$out .='<select id="bombselect_'.$row.'_'.$col.'" onchange="bicon_change(bombselect_'.$row.'_'.$col.', bombimg_'.$row.'_'.$col.','.$row.','.$col.', '.count($rows).','.count($columns).')" class="field" name="bomb['.$row.']['.$col.']">';
						$out .= '<option value="none" '.(($data_matrix[$row][$col]['bomb']['value'] == 'none' || $data_matrix[$row][$col]['bomb']['value'] == '' || $data_matrix[$row][$col]['bomb']['value'] == NULL) ? ' selected="selected"' : '') .'>None</option>';
						$out .= '<option value="small" '.(($data_matrix[$row][$col]['bomb']['value'] == 'small') ? ' selected="selected"' : '') .'>Small Bomb</option>';
						$out .= '<option value="large" '.(($data_matrix[$row][$col]['bomb']['value'] == 'large') ? ' selected="selected"' : '') .'>Large Bomb</option>';
						$out .= '</select><br/><br/></font><font style="color:#206040; font-weight: 900;">&nbsp;Bomb details: <br/></font><textarea onkeyup="refresher('.$row.','.$col.', '.count($rows).','.count($columns).')" onkeypress="refresher('.$row.','.$col.', '.count($rows).','.count($columns).')" onchange="refresher('.$row.','.$col.', '.count($rows).','.count($columns).')" align="left" name="bomb_explain['.$row.']['.$col.']" id="bomb_explain_'.$row.'_'.$col.'"  rows="5" cols="20" style="overflow:scroll; width:280px; height:80px; padding-left:10px; ">'. $data_matrix[$row][$col]['bomb_explain'] .'</textarea>';
						
						$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['bomb']['value'] == 'none' || $data_matrix[$row][$col]['bomb']['value'] == '' || $data_matrix[$row][$col]['bomb']['value'] == NULL) ? 'none':$data_matrix[$row][$col]['bomb']['value']) . ' " name="bk_bomb['.$row.']['.$col.']" id="bk_bombselect_'.$row.'_'.$col.'" />'
						.'<textarea name="bk_bomb_explain['.$row.']['.$col.']" id="bk_bomb_explain_'.$row.'_'.$col.'" style="overflow:scroll; display:none;" rows="5" cols="20">'. $data_matrix[$row][$col]['bomb_explain'] .'</textarea>'
						.'</td></tr>'
						.'</table>'
						.'</div>'
						.'</div>';	//Pop-Up Form for Bomb Editing Ends Here
			
						
						$out .= '<div class="popup_form" id="filingpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
						.'<div class="menu_form_header" id="filingpopup_drag_'.$row.'_'.$col.'" style="width:300px;">'
						.'<img class="menu_form_exit" align="right" id="filingpopup_exit_'.$row.'_'.$col.'" src="images/fancy_close.png" style="width:30px; height:30px;" '		
						.'alt="" />&nbsp;&nbsp;&nbsp;'
						.'</div>'
						.'<div class="menu_form_body" style="width:300px;">'
						.'<table style="background-color:#fff;">';
						
						$out .= '<tr><td style="background-color:#fff;">'
						.'<font style="color:#206040; font-weight: 900;">&nbsp;Filing details: <br/></font><textarea onkeyup="refresher('.$row.','.$col.', '.count($rows).','.count($columns).')" onkeypress="refresher('.$row.','.$col.', '.count($rows).','.count($columns).')" onchange="refresher('.$row.','.$col.', '.count($rows).','.count($columns).')" align="left" id="filing_'.$row.'_'.$col.'" name="filing['.$row.']['.$col.']"  rows="5" cols="20" style="overflow:scroll; width:280px; height:60px; padding-left:10px; ">'. $data_matrix[$row][$col]['filing'] .'</textarea>'
						.'<textarea id="bk_filing_'.$row.'_'.$col.'" name="bk_filing['.$row.']['.$col.']" style="overflow:scroll; display:none;" rows="5" cols="20">'. $data_matrix[$row][$col]['filing'] .'</textarea>'
						.'</td></tr>'
						.'<tr><th style="background-color:#fff;">&nbsp;</th></tr>'
						.'</table>'
						.'</div>'
						.'</div>'; ///Pop-up Form for Filing Ends Here	
						
						
						$out .= '<div class="popup_form" id="phaseexppopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
							.'<div class="menu_form_header" id="phaseexppopup_drag_'.$row.'_'.$col.'" style="width:300px;">'
							.'<img class="menu_form_exit" align="right" id="phaseexppopup_exit_'.$row.'_'.$col.'" src="'. urlPath() .'images/fancy_close.png" style="width:30px; height:30px;" '		
							.'alt="" />&nbsp;&nbsp;&nbsp;'
							.'</div>'
							.'<div class="menu_form_body" style="width:300px;">'
							.'<table style="background-color:#fff;">';
							
						$out .= '<tr style="background-color:#fff; border:none;"><td style="background-color:#fff; border:none;">'
							.'<font style="color:#206040; font-weight: 900;">&nbsp;Phase explain: <br/></font><textarea onkeyup="refresher('.$row.','.$col.', '.count($rows).','.count($columns).')" onkeypress="refresher('.$row.','.$col.', '.count($rows).','.count($columns).')" onchange="refresher('.$row.','.$col.', '.count($rows).','.count($columns).')" align="left" id="phase_explain_'.$row.'_'.$col.'" name="phase_explain['.$row.']['.$col.']"  rows="5" cols="20" style="overflow:scroll; width:280px; height:60px; padding-left:10px; ">'. $data_matrix[$row][$col]['phase_explain'] .'</textarea>'
							.'<textarea id="bk_phase_explain_'.$row.'_'.$col.'" name="bk_phase_explain['.$row.']['.$col.']" style="overflow:scroll; display:none;" rows="5" cols="20">'. $data_matrix[$row][$col]['phase_explain'] .'</textarea>'
							//.'<div align="left" width="200px" style="overflow:scroll; width:200px; height:150px; padding-left:10px;" id="filing">'. $data_matrix[$row][$col]['phase_explain'] .'</div>'
							.'</td></tr>'
							.'<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">&nbsp;</th></tr>'
							.'</table>'
							.'</div>'
							.'</div>'; ///Pop-up Form for Phase Explain Ends Here		


			}else{
				$out .= '';
			}
			
			$out .= '</td>';
					
		}
		//if total checkbox is selected
		if($total_fld)
		{
			$out .= '<th width="150px">&nbsp;</th>';
		}
		
		// Extra column for proper arrangement of inserters
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $repoUser == $db->user->id) || $db->user->userlevel == 'root')
		{
			$out .= '<th style="background-color:#FFFFFF;" class="Extra_Insert_Col"></th>';
		}
		
		$out .= '</tr>';
	}
	$out .= '</table></td></tr></table>'
			. '<fieldset><legend>Footnotes</legend><textarea '.(($disabled) ? ' readonly="readonly" ':'').' name="footnotes" cols="45" rows="5">' 
			. $footnotes . '</textarea></fieldset>'
			. '<fieldset><legend>Description</legend><textarea '.(($disabled) ? ' readonly="readonly" ':'').' name="description" cols="45" rows="5">' . $description
			. '</textarea></fieldset>';
	$out .='<div id="dialog" title="Confirm"></div>'
			.'<input type="hidden" id="Count_Rows" name="Count_Rows" value="'. count($rows) .'" />'
			.'<input type="hidden" id="Count_Columns" name="Count_Columns" value="'. count($columns) .'" />';
			
	if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $repoUser == $db->user->id) || $db->user->userlevel == 'root')
	{
		$out .= '<br clear="all"/><div align="left" style="vertical-align:bottom; float:left;"><fieldset style="margin-top:50px; padding:8px;"><legend>Advanced</legend>'
				. '<label class="lbldeln"><input class="delrepe" type="checkbox" id="delrep" name="delrep['.$id.']" title="Delete" /></label>' 
				. '&nbsp;&nbsp;&nbsp;&nbsp;Delete this heatmap report</fieldset></div>';
	};
	$out .= '</form>';

	return $out;
}

function Download_reports()
{
	ob_start();
	global $db;
	global $now;
	if(!isset($_REQUEST['id'])) return;
	$id = mysql_real_escape_string(htmlspecialchars($_REQUEST['id']));
	if(!is_numeric($id)) return;
	
	if(isset($_REQUEST['ohmtype']))
	{
		$ohm = $_REQUEST['ohmtype'];
	}else{
		$ohm = 'SOHM';
	}
	
	if($ohm == 'SOHM')
	{
		$query = 'SELECT `name`, `user`, `footnotes`, `description`, `category`, `shared`, `total`, `dtt`, `display_name` FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting heatmap report');
		$res = mysql_fetch_array($res) or die('Report not found.');
		$total_fld=$res['total'];
		$name = $res['name'];
		$dtt = $res['dtt'];
		$ReportDisplayName=$res['display_name'];
		if($res['display_name'] == NULL && trim($res['display_name']) == '')
		$ReportDisplayName = 'report '.$id;
	}
	else
	{
		$query = 'SELECT `name`, `display_name` FROM `entities` WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting heatmap report');
		$res = mysql_fetch_array($res) or die('Report not found.');
		$total_fld = 0;
		$dtt = 0;
		if($res['display_name'] != NULL && trim($res['display_name']) != '')
			$ReportDisplayName=$res['display_name'];
		else
			$ReportDisplayName=$res['name'];
	}	
	
	$rows = array();
	$columns = array();
	$entity2Ids = array();
	$entity1Ids = array();
	
	$columnsDisplayName = array();
	$rowsDisplayName = array();
	$columnsCompanyName = array();
	$rowsCompanyName = array();
	$columnsCategoryName = array();
	$rowsCategoryName = array();
	$columnsDescription = array();
	$rowsDescription = array();
	$columnsTagName = array();
	$rowsTagName = array();
	
	$ColumnsSpan  = array();
	$rowsCategoryEntityIds1 = array();
	
	$prevEntity2Category='';
	$prevEntity1Category='';
	$prevEntity2='';
	$prevEntity1='';
	$prevEntity2Span=0;
	$prevEntity1Span=0;
	$last_cat_col = '';
	$entity2_Category_Presence = 0;
	
	if($ohm == 'SOHM')
	{
		$query = 'SELECT `num`,`type`,`type_id`, `display_name`, `category`, `tag` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' ORDER BY num ASC';
		$res = mysql_query($query) or die('Bad SQL query getting heatmap report headers');
		
		while($header = mysql_fetch_array($res))
		{
			if($header['type'] == 'column')
			{
				$columnsCompanyName[$header['num']] = '';
				$columnsTagName[$header['num']] = '';
				if($header['type_id'] != NULL)
				{
					$result =  mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `display_name`, `description`, `class`, `company` FROM `entities` WHERE id = '" . $header['type_id'] . "' "));
					$columns[$header['num']] = $result['id'];
					$columnsEntityType[$header['num']] = $result['class'];
					
					$type = ''; $type = $result['class']; if($type == 'Institution') $type = 'Company'; else if($type == 'MOA_Category') $type = 'MOA Category'; else $type = $result['class'];
					
					if($type == 'Product')
						$result['company'] = GetCompanyNames($result['id']);
					else 
						$result['company'] = '';
					if($result['company'] != NULL && trim($result['company']) != '')
					{
						$columnsCompanyName[$header['num']] = ' / '.$result['company'];
					} 
					
					//$columnsDisplayName[$header['num']] = $result['display_name'];
					if($type == 'Product')
						$columnsDisplayName[$header['num']] = $result['name'];
					else
					{
						if(trim($header['display_name']) != '' && $header['display_name'] != NULL && trim($header['display_name']) != 'NULL') //HM LEVEL Display name
							$columnsDisplayName[$header['num']] = $header['display_name'];
						else if(trim($result['display_name']) != '' && $result['display_name'] != NULL && trim($result['display_name']) != 'NULL') //Global Display name
							$columnsDisplayName[$header['num']] = $result['display_name'];
						else if($type == 'Area')
							$columnsDisplayName[$header['num']] = $type .' '.$result['id'] ;	//For area display class n id
						else
							$columnsDisplayName[$header['num']] = $result['name'] ;	//For for other than Area take actual name
					}
						
					$columnsDescription[$header['num']] = $result['description'];
					$header['category'] = trim($header['category']);
					if($header['category'] == NULL || trim($header['category']) == '')
					$header['category'] = 'Undefined';
				}
				else
				{
					$columns[$header['num']] = $header['type_id'];
					$header['category'] = 'Undefined';
				}
				$entity2Ids[$header['num']] = $header['type_id'];
				
				if($prevEntity2Category == $header['category'])
				{
					$ColumnsSpan[$prevEntity2] = $prevEntity2Span+1;
					$ColumnsSpan[$header['num']] = 0;
					$prevEntity2 = $prevEntity2;
					$prevEntity2Span = $prevEntity2Span+1;
					$last_cat_col = $last_cat_col;
				}
				else
				{
					$ColumnsSpan[$header['num']] = 1;
					$prevEntity2 = $header['num'];
					$prevEntity2Span = 1;
					$second_last_cat_col = $last_cat_col;
					$last_cat_col = $header['num'];
				}
				
				$prevEntity2Category = $header['category'];
				$columnsCategoryName[$header['num']] = $header['category'];
				if($header['tag'] != 'NULL')
				$columnsTagName[$header['num']] = $header['tag'];
				
				$last_category = $header['category'];
				$second_last_num = $last_num;
				$last_num = $header['num'];
				$LastEntity2 = $header['type_id'];
				
				if(!$entity2_Category_Presence && $header['category'] != 'Undefined')
					$entity2_Category_Presence = 1;
			}
			else
			{
				$rowsCompanyName[$header['num']] = '';
				$rowsTagName[$header['num']] = '';
				if($header['type_id'] != NULL)
				{
					$result =  mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `display_name`, `description`, `class`, `company` FROM `entities` WHERE id = '" . $header['type_id'] . "' "));
					$rows[$header['num']] = $result['id'];
					$rowsEntityType[$header['num']] = $result['class'];
					
					$type = ''; $type = $result['class']; if($type == 'Institution') $type = 'Company'; else if($type == 'MOA_Category') $type = 'MOA Category'; else $type = $result['class'];
					
					if($type == 'Product')
						$result['company'] = GetCompanyNames($result['id']);
					else 
						$result['company'] = '';
						
					if($result['company'] != NULL && trim($result['company']) != '')
					{
						$rowsCompanyName[$header['num']] = ' / '.$result['company'];
					}
					
					if($type == 'Product')
						$rowsDisplayName[$header['num']] = $result['name'];
					else 
					{
						if(trim($header['display_name']) != '' && $header['display_name'] != NULL && trim($header['display_name']) != 'NULL') //HM LEVEL Display name
							$rowsDisplayName[$header['num']] = $header['display_name'];
						else if(trim($result['display_name']) != '' && $result['display_name'] != NULL && trim($result['display_name']) != 'NULL') //Global Display name
							$rowsDisplayName[$header['num']] = $result['display_name'];
						else if($type == 'Area')											//For area display class n id
							$rowsDisplayName[$header['num']] = $type .' '.$result['id'] ;
						else																//For for other than Area take actual name
							$rowsDisplayName[$header['num']] = $result['name'] ;
					}
							
					$rowsDescription[$header['num']] = $result['description'];
					$header['category']=trim($header['category']);
					if($header['category'] == NULL || trim($header['category']) == '')
					$header['category'] = 'Undefined';
				}
				else
				{
					$rows[$header['num']] = $header['type_id'];
					$header['category'] = 'Undefined';
				}
				$entity1Ids[$header['num']] = $header['type_id'];
				
				if($prevEntity1Category == $header['category'])
				{
					$RowsSpan[$prevEntity1] = $prevEntity1Span+1;
					$RowsSpan[$header['num']] = 0;
					$prevEntity1 = $prevEntity1;
					$prevEntity1Span = $prevEntity1Span+1;
				}
				else
				{
					$RowsSpan[$header['num']] = 1;
					$prevEntity1 = $header['num'];
					$prevEntity1Span = 1;
				}
				
				$prevEntity1Category = $header['category'];
				$rowsCategoryName[$header['num']] = $header['category'];
				
				$rowsCategoryEntityIds1[$header['category']][] = $header['type_id'];
				if($header['tag'] != 'NULL')
				$rowsTagName[$header['num']] = $header['tag'];
			}
		}	//END OF WHILE
	}	//END OF SOHM
	else
	{
		$query = "SELECT DISTINCT(e.`id`), e.`name`, e.`description` FROM `entities` e JOIN `entity_relations` er ON (e.`id`=er.`child`) WHERE er.`parent`='" . $id . "' AND e.`class`='Product'";
		$res = mysql_query($query) or die('Bad SQL query getting products from disease heatmap report headers');
		
		$counter = 0;
		while($result = mysql_fetch_array($res))
		{
			$counter++;
			$rows[$counter] = $result['id'];
			$rowsEntityType[$counter] = $result['class'];
					
			$result['company'] = GetCompanyNames($result['id']);
			if($result['company'] != NULL && trim($result['company']) != '')
			$rowsCompanyName[$counter] = ' / '.$result['company'];
					
			$rowsDisplayName[$counter] = $result['name'];
			$rowsDescription[$counter] = $result['description'];
			$header['category'] = 'Undefined';
			
			$entity1Ids[$counter] = $result['id'];
				
			if($prevEntity1Category == $header['category'])
			{
				$RowsSpan[$prevEntity1] = $prevEntity1Span+1;
				$RowsSpan[$counter] = 0;
				$prevEntity1 = $prevEntity1;
				$prevEntity1Span = $prevEntity1Span+1;
			}
			else
			{
				$RowsSpan[$counter] = 1;
				$prevEntity1 = $counter;
				$prevEntity1Span = 1;
			}
			
			$prevEntity1Category = $header['category'];
			$rowsCategoryName[$counter] = $header['category'];
			
			$rowsCategoryEntityIds1[$header['category']][] = $result['id'];
			$rowsTagName[$counter] = '';
		}//END OF WHILE - ADDITION OF ROW DATA COMPLETES
		
		$meshFlg = false;
		$query = "SELECT `mesh_name` FROM `entities` WHERE `id`='" . mysql_real_escape_string($id) . "'";
		$res = mysql_query($query) or die('Bad SQL query getting disease mesh flag in OHM');
	
		if($res)
		{
			while($row = mysql_fetch_array($res))
			if($row['mesh_name'] != NULL && trim($row['mesh_name']) != '')
			$meshFlg = true;
		}
		
		$query = "SELECT DISTINCT(e.`id`), e.`name`, e.`display_name`, e.`description`, e.`mesh_name` FROM `entities` e JOIN `entity_relations` er ON (e.`id`=er.`parent`) WHERE er.`child` IN ('" . implode("','",$entity1Ids) . "') AND e.`class`='Disease' ". (($meshFlg) ? "AND e.`mesh_name` <> '' AND e.`mesh_name` IS NOT NULL":"") ."";
		$res = mysql_query($query) or die('Bad SQL query getting products from disease heatmap report headers');
		
		$counter = 0;
		while($result = mysql_fetch_array($res))
		{
			$counter++;
			$columns[$counter] = $result['id'];
			$columnsEntityType[$counter] = $result['class'];
			$columnsCompanyName[$counter] = '';
			
			if($meshFlg)
				$columnsDisplayName[$counter] = $result['mesh_name'];
			else if(trim($result['display_name']) != '' && $result['display_name'] != NULL && trim($result['display_name']) != 'NULL') //Global Display name
				$columnsDisplayName[$counter] = $result['display_name'];
			else
				$columnsDisplayName[$counter] = $result['name'] ;	//For for other than Area take actual name
						
			$columnsDescription[$counter] = $result['description'];
			$header['category'] = 'Undefined';
			$entity2Ids[$counter] = $result['id'];
				
			if($prevEntity2Category == $header['category'])
			{
				$ColumnsSpan[$prevEntity2] = $prevEntity2Span+1;
				$ColumnsSpan[$counter] = 0;
				$prevEntity2 = $prevEntity2;
				$prevEntity2Span = $prevEntity2Span+1;
				$last_cat_col = $last_cat_col;
			}
			else
			{
				$ColumnsSpan[$counter] = 1;
				$prevEntity2 = $counter;
				$prevEntity2Span = 1;
				$second_last_cat_col = $last_cat_col;
				$last_cat_col = $counter;
			}
				
			$prevEntity2Category = $header['category'];
			$columnsCategoryName[$counter] = $header['category'];
			$columnsTagName[$counter] = '';
				
			$last_category = $header['category'];
			$second_last_num = $last_num;
			$last_num = $counter;
			$LastEntity2 = $result['id'];
		}//END OF WHILE - ADDITION OF COLUMN DATA COMPLETES
	}//END OF ELSE FOR DISEASE OHM

	
	// SELECT MAX ROW AND MAX COL
	$max_row = count($entity1Ids);
	$max_column = count($entity2Ids);
	
	/////Remove last column at start only //////////
	$new_columns = array();
	foreach($columns as $col => $cval)
	{
		if($dtt && $last_num == $col)
		{
			array_pop($entity2Ids); //In case of DTT enable skip last column vaules
			$ColumnsSpan[$last_cat_col] = $ColumnsSpan[$last_cat_col] - 1;	/// Decrease last category column span
		}
		else
		{
			if($dtt && $second_last_num == $col && $rows_Span[$col] == 0)	//In case of DTT skipping last column can cause colspan problem of category
			$rows_Span[$col] = $rows_Span[$last_num];
			$new_columns[$col]=$cval;
		}
	}
	
	$columns=$new_columns;
	/////Rearrange Completes //////////
	
	if(isset($_REQUEST['sr']) && isset($_REQUEST['er']))
	{
		$sr = $_REQUEST['sr'];
		$er = $_REQUEST['er'];
		$start_range = trim(str_replace('ago', '', $_REQUEST['sr']));
		if($start_range == 'now')
			$start_range = 'now';
		else if($start_range == '1 week' || $start_range == '2 weeks' || $start_range == '1 month' || $start_range == '1 quarter' || $start_range == '6 months' || $start_range == '1 year')
			$start_range = '-' . (($start_range == '1 quarter') ? '3 months' : $start_range);
		
		$end_range = trim(str_replace('ago', '', $_REQUEST['er']));
		if($end_range == 'now')
			$end_range = 'now';
		else if($end_range == '1 week' || $end_range == '2 weeks' || $end_range == '1 month' || $end_range == '1 quarter' || $end_range == '6 months' || $end_range == '1 year')
			$end_range = '-' . (($end_range == '1 quarter') ? '3 months' : $end_range);
	}
	else
	{
		$start_range = 'now';
		$end_range = '-1 month';
		$sr = 'now';
		$er = '1 month';
	}

/* 	echo '<pre>';
	print_r($rows);
	print_r($columnsDisplayName);
	print_r($columnsDescription);
	print_r($rowsDisplayName);
	print_r($rowsDescription);
	die; */
	
	
	$row_total=array();
	$col_total=array();
	$active_total=0;
	$count_total=0;
	$data_matrix=array();
	
	$Line_Height = 3.96;	// Normal Line Height
	$Bold_Line_Height = 3.96;		// Bold Line Height
	$Min_One_Liner = 4.5;
	
	//// Declare Tidy Configuration
	$tidy_config = array(
	                     'clean' => true,
	                     'output-xhtml' => true,
	                     'show-body-only' => true,
	                     'wrap' => 0,
	                    
	                     );
	$tidy = new tidy(); /// Create Tidy Object
	
	
	foreach($rows as $row => $rid)
	{
		$PhaseRowMatrix[$row]['oldrow'] = $row;
		$PhaseRowMatrix[$row]['entity'] = $rid;
		foreach($columns as $col => $cid)
		{
			$PhaseColumnMatrix[$col]['oldcol'] = $col;
			$PhaseColumnMatrix[$col]['entity'] = $cid;
			
			if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL)
			{
				$cell_query = 'SELECT * FROM rpt_masterhm_cells WHERE (`entity1`=' . $entity1Ids[$row] . ' AND `entity2`='. $entity2Ids[$col] .') OR (`entity2`=' . $entity1Ids[$row] . ' AND `entity1`='. $entity2Ids[$col] .')';
				$cell_res = mysql_query($cell_query) or die(mysql_error());
				$cell_data = mysql_fetch_array($cell_res);
				
				$col_active_total[$col]=$cell_data['count_active']+$col_active_total[$col];
				$row_active_total[$row]=$cell_data['count_active']+$row_active_total[$row];
				$col_count_total[$col]=$cell_data['count_total']+$col_count_total[$col];
				$row_count_total[$row]=$cell_data['count_total']+$row_count_total[$row];
				$col_indlead_total[$col]=$cell_data['count_active_indlead']+$col_indlead_total[$col];
				$row_indlead_total[$row]=$cell_data['count_active_indlead']+$row_indlead_total[$row];
				$col_active_owner_sponsored_total[$col]=$cell_data['count_active_owner_sponsored']+$col_active_owner_sponsored_total[$col];
				$row_active_owner_sponsored_total[$row]=$cell_data['count_active_owner_sponsored']+$row_active_owner_sponsored_total[$row];
				
				$active_total=$cell_data['count_active']+$active_total;
				$count_total=$cell_data['count_total']+$count_total;
				$indlead_total=$cell_data['count_active_indlead']+$indlead_total;
				$active_owner_sponsored_total=$cell_data['count_active_owner_sponsored']+$active_owner_sponsored_total;
				
				if($cell_data['count_active'] != '' && $cell_data['count_active'] != NULL)
				$data_matrix[$rid][$cid]['active']=$cell_data['count_active'];
				else
				$data_matrix[$rid][$cid]['active']=0;
				
				if($cell_data['count_total'] != '' && $cell_data['count_total'] != NULL)
				$data_matrix[$rid][$cid]['total']=$cell_data['count_total'];
				else
				$data_matrix[$rid][$cid]['total']=0;
				
				if($cell_data['count_active_indlead'] != '' && $cell_data['count_active_indlead'] != NULL)
				$data_matrix[$rid][$cid]['indlead']=$cell_data['count_active_indlead'];
				else
				$data_matrix[$rid][$cid]['indlead']=0;
				
				if($cell_data['count_active_owner_sponsored'] != '' && $cell_data['count_active_owner_sponsored'] != NULL)
					$data_matrix[$rid][$cid]['active_owner_sponsored']=$cell_data['count_active_owner_sponsored'];
				else
					$data_matrix[$rid][$cid]['active_owner_sponsored']=0;	
				
				if($ohm == 'SOHM')
				{
					$data_matrix[$rid][$cid]['phase_explain']=trim($cell_data['phase_explain']);
					$data_matrix[$rid][$cid]['bomb_explain']=trim($cell_data['bomb_explain']);
					$data_matrix[$rid][$cid]['filing']=trim($cell_data['filing']);
					
					$data_matrix[$rid][$cid]['bomb_lastchanged']=$cell_data['bomb_lastchanged'];
					$data_matrix[$rid][$cid]['filing_lastchanged']=$cell_data['filing_lastchanged'];
					$data_matrix[$rid][$cid]['phase_explain_lastchanged']=$cell_data['phase_explain_lastchanged'];
				
					$data_matrix[$rid][$cid]['phase4_override']=$cell_data['phase4_override'];
					$data_matrix[$rid][$cid]['phase4_override_lastchanged']=$cell_data['phase4_override_lastchanged'];
					
					$data_matrix[$rid][$cid]['preclinical']=$cell_data['preclinical'];
				}
				else	//FOR OHM OTHER THAN NORMAL OHM MAKE CELL LEVEL DATA NULL
				{
					$data_matrix[$rid][$cid]['phase_explain']='';
					$data_matrix[$rid][$cid]['bomb_explain']='';
					$data_matrix[$rid][$cid]['filing']='';
					
					$data_matrix[$rid][$cid]['bomb_lastchanged']='';
					$data_matrix[$rid][$cid]['filing_lastchanged']='';
					$data_matrix[$rid][$cid]['phase_explain_lastchanged']='';
				
					$data_matrix[$rid][$cid]['phase4_override']='';
					$data_matrix[$rid][$cid]['phase4_override_lastchanged']='';
					
					$data_matrix[$rid][$cid]['preclinical']=0;
					$cell_data['phase4_override']=0;
					
					$cell_data['bomb']='';
					$cell_data['bomb_auto']='';
				}
				$data_matrix[$rid][$cid]['highest_phase_prev']=$cell_data['highest_phase_prev'];
				$data_matrix[$rid][$cid]['highest_phase_lastchanged']=$cell_data['highest_phase_lastchanged'];
				
				$data_matrix[$rid][$cid]['count_lastchanged']=$cell_data['count_lastchanged'];

				/// Clean HTML using Tidy
				$tidy = tidy_parse_string($data_matrix[$rid][$cid]['bomb_explain'], $tidy_config, 'UTF8');
				$tidy->cleanRepair(); 
				$data_matrix[$rid][$cid]['bomb_explain']=trim($tidy);
				
				/// Clean HTML using Tidy
				$tidy = tidy_parse_string($data_matrix[$rid][$cid]['filing'], $tidy_config, 'UTF8');
				$tidy->cleanRepair(); 
				$data_matrix[$rid][$cid]['filing']=trim($tidy);
				
				/// Clean HTML using Tidy
				$tidy = tidy_parse_string($data_matrix[$rid][$cid]['phase_explain'], $tidy_config, 'UTF8');
				$tidy->cleanRepair(); 
				$data_matrix[$rid][$cid]['phase_explain']=trim($tidy);
				
				$data_matrix[$rid][$cid]['preclinical']=$cell_data['preclinical'];
				
				$Width = 0;
				
				if($cell_data['bomb_auto'] == 'small')
				{
					$data_matrix[$rid][$cid]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$rid][$cid]['bomb_auto']['src']='sbomb.png';
					$data_matrix[$rid][$cid]['bomb_auto']['alt']='Small bomb';
					$data_matrix[$rid][$cid]['bomb_auto']['style']='width:10px; height:11px;';
					$data_matrix[$rid][$cid]['bomb_auto']['title']='Suggested';
				}
				elseif($cell_data['bomb_auto'] == 'large')
				{
					$data_matrix[$rid][$cid]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$rid][$cid]['bomb_auto']['src']='lbomb.png';
					$data_matrix[$rid][$cid]['bomb_auto']['alt']='Large bomb';
					$data_matrix[$rid][$cid]['bomb_auto']['style']='width:18px; height:20px;';
					$data_matrix[$rid][$cid]['bomb_auto']['title']='Suggested';
				}
				else
				{
					$data_matrix[$rid][$cid]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$rid][$cid]['bomb_auto']['src']='trans.gif';
					$data_matrix[$rid][$cid]['bomb_auto']['alt']='None';
					$data_matrix[$rid][$cid]['bomb_auto']['style']='width:10px; height:11px;';
					$data_matrix[$rid][$cid]['bomb_auto']['title']='';
				}
				
				
				$data_matrix[$rid][$cid]['last_update']=$cell_data['last_update'];
				
				$data_matrix[$rid][$cid]['active_prev']=$cell_data['count_active_prev'];
				$data_matrix[$rid][$cid]['total_prev']=$cell_data['count_total_prev'];
				$data_matrix[$rid][$cid]['indlead_prev']=$cell_data['count_active_indlead_prev'];
				$data_matrix[$rid][$cid]['active_owner_sponsored_prev']=$cell_data['count_active_owner_sponsored_prev'];
				
				$data_matrix[$rid][$cid]['update_flag'] = 0;
				
				
				if(date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['filing_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now)) && date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['filing_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now)))
				{
					$data_matrix[$rid][$cid]['filing_image']='images/newred_file.png';
					$data_matrix[$rid][$cid]['exec_filing_image']='images/newred_file'; //Excel file image
					$data_matrix[$rid][$cid]['update_flag'] = 1;
				}
				else
				{
					$data_matrix[$rid][$cid]['filing_image']='images/new_file.png';
					$data_matrix[$rid][$cid]['exec_filing_image']='images/new_file'; //Excel file image
				}
				
				
				if(date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['phase_explain_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now)) && date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['phase_explain_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now)))
				{
					$data_matrix[$rid][$cid]['phase_explain_image']='images/phaseexp_red.png';
					$data_matrix[$rid][$cid]['update_flag'] = 1;
				}
				else
				$data_matrix[$rid][$cid]['phase_explain_image']='images/phaseexp.png';
				
				if((date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['highest_phase_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now))) && (date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['highest_phase_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now))) && ($data_matrix[$rid][$cid]['highest_phase_prev'] != NULL && $data_matrix[$rid][$cid]['highest_phase_prev'] != ''))
				{
					$data_matrix[$rid][$cid]['highest_phase_lastchanged_value']=1;
					$data_matrix[$rid][$cid]['update_flag'] = 1;
				}
				
				if(trim($cell_data['bomb']) == 'small')
				{
					$data_matrix[$rid][$cid]['bomb']['value']=trim($cell_data['bomb']);
					$Width = $Width + 3.2 + 0.2;
					
					if(date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['bomb_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now)) && date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['bomb_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now)))
					{
						$data_matrix[$rid][$cid]['bomb']['src']='newred_sbomb.png';
						$data_matrix[$rid][$cid]['exec_bomb']['src']='newred_sbomb'; //Excel bomb image
						$data_matrix[$rid][$cid]['update_flag'] = 1;
					}
					else
					{
						$data_matrix[$rid][$cid]['bomb']['src']='new_sbomb.png';
						$data_matrix[$rid][$cid]['exec_bomb']['src']='new_sbomb'; //Excel bomb image
					}
					$data_matrix[$rid][$cid]['bomb']['alt']='Small bomb';
					$data_matrix[$rid][$cid]['bomb']['style']='width:11px; height:11px;';
					$data_matrix[$rid][$cid]['bomb']['title']='Bomb Details';
				}
				elseif(trim($cell_data['bomb']) == 'large')
				{
					$data_matrix[$rid][$cid]['bomb']['value']=trim($cell_data['bomb']);
					$Width = $Width + 3.2 + 0.2;
					
					if((date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['bomb_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now))) && (date('Y-m-d H:i:s', strtotime($data_matrix[$rid][$cid]['bomb_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now))))
					{
						$data_matrix[$rid][$cid]['bomb']['src']='newred_lbomb.png';
						$data_matrix[$rid][$cid]['exec_bomb']['src']='newred_lbomb';
						$data_matrix[$rid][$cid]['update_flag'] = 1;
					}
					else
					{
						$data_matrix[$rid][$cid]['bomb']['src']='new_lbomb.png';
						$data_matrix[$rid][$cid]['exec_bomb']['src']='new_lbomb';
					}
					$data_matrix[$rid][$cid]['bomb']['alt']='Large bomb';
					$data_matrix[$rid][$cid]['bomb']['style']='width:11px; height:11px;';
					$data_matrix[$rid][$cid]['bomb']['title']='Bomb Details';
				}
				else
				{
					$data_matrix[$rid][$cid]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$rid][$cid]['bomb']['src']='new_square.png';
					$data_matrix[$rid][$cid]['exec_bomb']['src']='new_square.png';
					$data_matrix[$rid][$cid]['bomb']['alt']='None';
					$data_matrix[$rid][$cid]['bomb']['style']='width:11px; height:11px;';
					$data_matrix[$rid][$cid]['bomb']['title']='Bomb details';
				}
				
				if($cell_data['highest_phase'] == 'N/A' || $cell_data['highest_phase'] == '' || $cell_data['highest_phase'] === NULL)
				{
					$data_matrix[$rid][$cid]['color']='background-color:#BFBFBF;';
					$data_matrix[$rid][$cid]['color_code']='BFBFBF';
					
					$PhaseRowMatrix[$row]['na'] = $PhaseRowMatrix[$row]['na'] + 1;
					$PhaseColumnMatrix[$col]['na'] = $PhaseColumnMatrix[$col]['na'] + 1;
				}
				else if($cell_data['highest_phase'] == '0')
				{
					$data_matrix[$rid][$cid]['color']='background-color:#00CCFF;';
					$data_matrix[$rid][$cid]['color_code']='00CCFF';
					
					$PhaseRowMatrix[$row]['0'] = $PhaseRowMatrix[$row]['0'] + 1;
					$PhaseColumnMatrix[$col]['0'] = $PhaseColumnMatrix[$col]['0'] + 1;
				}
				else if($cell_data['highest_phase'] == '1' || $cell_data['highest_phase'] == '0/1' || $cell_data['highest_phase'] == '1a' 
				|| $cell_data['highest_phase'] == '1b' || $cell_data['highest_phase'] == '1a/1b' || $cell_data['highest_phase'] == '1c')
				{
					$data_matrix[$rid][$cid]['color']='background-color:#99CC00;';
					$data_matrix[$rid][$cid]['color_code']='99CC00';
					
					$PhaseRowMatrix[$row]['1'] = $PhaseRowMatrix[$row]['1'] + 1;
					$PhaseColumnMatrix[$col]['1'] = $PhaseColumnMatrix[$col]['1'] + 1;
				}
				else if($cell_data['highest_phase'] == '2' || $cell_data['highest_phase'] == '1/2' || $cell_data['highest_phase'] == '1b/2' 
				|| $cell_data['highest_phase'] == '1b/2a' || $cell_data['highest_phase'] == '2a' || $cell_data['highest_phase'] == '2a/2b' 
				|| $cell_data['highest_phase'] == '2a/b' || $cell_data['highest_phase'] == '2b')
				{
					$data_matrix[$rid][$cid]['color']='background-color:#FFFF00;';
					$data_matrix[$rid][$cid]['color_code']='FFFF00';
					
					$PhaseRowMatrix[$row]['2'] = $PhaseRowMatrix[$row]['2'] + 1;
					$PhaseColumnMatrix[$col]['2'] = $PhaseColumnMatrix[$col]['2'] + 1;
				}
				else if($cell_data['highest_phase'] == '3' || $cell_data['highest_phase'] == '2/3' || $cell_data['highest_phase'] == '2b/3' 
				|| $cell_data['highest_phase'] == '3a' || $cell_data['highest_phase'] == '3b')
				{
					$data_matrix[$rid][$cid]['color']='background-color:#FF9900;';
					$data_matrix[$rid][$cid]['color_code']='FF9900';
					
					$PhaseRowMatrix[$row]['3'] = $PhaseRowMatrix[$row]['3'] + 1;
					$PhaseColumnMatrix[$col]['3'] = $PhaseColumnMatrix[$col]['3'] + 1;
				}
				else if($cell_data['highest_phase'] == '4' || $cell_data['highest_phase'] == '3/4' || $cell_data['highest_phase'] == '3b/4')
				{
					$data_matrix[$rid][$cid]['color']='background-color:#FF0000;';
					$data_matrix[$rid][$cid]['color_code']='FF0000';
					
					$PhaseRowMatrix[$row]['4'] = $PhaseRowMatrix[$row]['4'] + 1;	
					$PhaseColumnMatrix[$col]['4'] = $PhaseColumnMatrix[$col]['4'] + 1;	
				}
				
				if($cell_data['phase4_override'])
				{
					$data_matrix[$rid][$cid]['color']='background-color:#FF0000;';
					$data_matrix[$rid][$cid]['color_code']='FF0000';
				}
				
				$allTrialsStatusArray = array('not_yet_recruiting', 'recruiting', 'enrolling_by_invitation', 'active_not_recruiting', 'completed', 'suspended', 'terminated', 'withdrawn', 'available', 'no_longer_available', 'approved_for_marketing', 'no_longer_recruiting', 'withheld', 'temporarily_not_available', 'ongoing', 'not_authorized', 'prohibited');
				foreach($allTrialsStatusArray as $status)
				{
					$data_matrix[$rid][$cid][$status]=$cell_data[$status];
				}
				
				foreach($allTrialsStatusArray as $status)
				{
					$data_matrix[$rid][$cid][$status.'_active_indlead']=$cell_data[$status.'_active_indlead'];
				}
				
				foreach($allTrialsStatusArray as $status)
				{
					$data_matrix[$rid][$cid][$status.'_active']=$cell_data[$status.'_active'];
				}
				
				foreach($allTrialsStatusArray as $status)
				{
					$data_matrix[$rid][$cid][$status.'_active_owner_sponsored']=$cell_data[$status.'_active_owner_sponsored'];
				}
				
				$data_matrix[$rid][$cid]['new_trials']=$cell_data['new_trials'];
				
				////// Remaining Width calculation
				require_once('tcpdf/config/lang/eng.php');
				require_once('tcpdf/tcpdf.php');  
				$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', true, 'UTF-8', false);
		
				if($_POST['dwcount']=='active')
				{
					if($data_matrix[$rid][$cid]['active'] != NULL && $data_matrix[$rid][$cid]['active'] != '')
					{
						$Width = $Width + $pdf->GetStringWidth($data_matrix[$rid][$cid]['active'], 'freesansb', 'B', 8) + 0.6;
					}
				}
				elseif($_POST['dwcount']=='total')
				{
					if($data_matrix[$rid][$cid]['total'] != NULL && $data_matrix[$rid][$cid]['total'] != '')
					{
						$Width = $Width + $pdf->GetStringWidth($data_matrix[$rid][$cid]['total'], 'freesansb', 'B', 8) + 0.6;
					}
				}
				elseif($_POST['dwcount']=='active_owner_sponsored')
				{
					if($data_matrix[$rid][$cid]['active_owner_sponsored'] != NULL && $data_matrix[$rid][$cid]['active_owner_sponsored'] != '')
					{
						$Width = $Width + $pdf->GetStringWidth($data_matrix[$rid][$cid]['active_owner_sponsored'], 'freesansb', 'B', 8) + 0.6;
					}
				}
				else
				{
					if($data_matrix[$rid][$cid]['indlead'] != NULL && $data_matrix[$rid][$cid]['indlead'] != '')
					{
						$Width = $Width + $pdf->GetStringWidth($data_matrix[$rid][$cid]['indlead'], 'freesansb', 'B', 8) + 0.6;
					}
				}
				
				if(trim($data_matrix[$rid][$cid]['filing']) != '' && $data_matrix[$rid][$cid]['filing'] != NULL)
				$Width = $Width + 3.4 + 0.2;
				
				if($data_matrix[$rid][$cid]['update_flag'] == 1)	// As we produce white border
				$Width = $Width + 0.8;
				
				if($Width < $Min_One_Liner)
				$Width = $Min_One_Liner;
					
				if($Width_matrix[$col]['width'] < ($Width) || $Width_matrix[$col]['width'] == '' || $Width_matrix[$col]['width'] == 0)
				{
					$Width_matrix[$col]['width'] = $Width;
				}
			}
			else
			{
				$data_matrix[$rid][$cid]['active']=0;
				$data_matrix[$rid][$cid]['total']=0;
				$data_matrix[$rid][$cid]['indlead']=0;
				$data_matrix[$rid][$cid]['active_owner_sponsored']=0;
				
				$col_active_total[$col]=0+$col_active_total[$col];
				$row_active_total[$row]=0+$row_active_total[$row];
				$col_count_total[$col]=0+$col_count_total[$col];
				$row_count_total[$row]=0+$row_count_total[$row];
				$col_count_indlead[$col]=0+$col_count_indlead[$col];
				$row_count_indlead[$row]=0+$row_count_indlead[$row];
				$col_active_owner_sponsored[$col]=0+$col_active_owner_sponsored[$col];
				$row_active_owner_sponsored[$row]=0+$row_active_owner_sponsored[$row];
				
				$data_matrix[$rid][$cid]['bomb_auto']['src']='';
				$data_matrix[$rid][$cid]['bomb']['src']='';
				$data_matrix[$rid][$cid]['bomb_explain']='';
				$data_matrix[$rid][$cid]['filing']='';
				$data_matrix[$rid][$cid]['color']='background-color:#DDF;';
				$data_matrix[$rid][$cid]['color_code']='DDF';
				$data_matrix[$rid][$cid]['update_flag'] = 0;
				$Width = $Bold_Line_Height;
				//if($Width_matrix[$col]['width'] < $Width || $Width_matrix[$col]['width'] == '' || $Width_matrix[$col]['width'] == 0)
				//$Width_matrix[$col]['width']=$Width+2;
				
				if($Width_matrix[$col]['width'] < $Min_One_Liner)
				$Width_matrix[$col]['width'] = $Min_One_Liner;
				
				$PhaseRowMatrix[$row]['blank'] = $PhaseRowMatrix[$row]['blank'] + 1;
				$PhaseColumnMatrix[$col]['blank'] = $PhaseColumnMatrix[$col]['blank'] + 1;
			}
		}
	}
	
	if($ohm != 'SOHM') //IF NOT NORMAL OHM THEN SORT IT BY PHASE COUNT
	{
		// SORT ROWS AND REARRANGE ALL ROW RELATED DATA
		foreach ($PhaseRowMatrix as $key => $p) {
			$phna[$key]  = $p['na'];
			$ph0[$key] = $p['0'];
			$ph1[$key] = $p['1'];
			$ph2[$key] = $p['2'];
			$ph3[$key] = $p['3'];
			$ph4[$key] = $p['4'];
			$phblank[$key] = $p['blank'];
		}
		array_multisort($ph4, SORT_DESC, $ph3, SORT_DESC, $ph2, SORT_DESC, $ph1, SORT_DESC, $ph0, SORT_DESC,  $phna, SORT_DESC,  $phblank, SORT_DESC, $PhaseRowMatrix);
		
		$row_active_totalCopy=$row_active_total;
		$row_count_totalCopy=$row_count_total;
		$row_indlead_totalCopy=$row_indlead_total;
		$row_active_owner_sponsored_totalCopy=$row_active_owner_sponsored_total;
		$rowsCopy = $rows;
		$rowsCompanyNameCopy = $rowsCompanyName;
		$rowsDisplayNameCopy = $rowsDisplayName;
		$rowsDescriptionCopy = $rowsDescription;
		$entity1IdsCopy = $entity1Ids;
			
		foreach($PhaseRowMatrix as $k=>$r)
		{
			$row_active_total[$k+1]=$row_active_totalCopy[$r['oldrow']];
			$row_count_total[$k+1]=$row_count_totalCopy[$r['oldrow']];
			$row_indlead_total[$k+1]=$row_indlead_totalCopy[$r['oldrow']];
			$row_active_owner_sponsored_total[$k+1]=$row_active_owner_sponsored_totalCopy[$r['oldrow']];
			$rows[$k+1] = $rowsCopy[$r['oldrow']];
			$rowsCompanyName[$k+1] = $rowsCompanyNameCopy[$r['oldrow']];
			$rowsDisplayName[$k+1] = $rowsDisplayNameCopy[$r['oldrow']];
			$rowsDescription[$k+1] = $rowsDescriptionCopy[$r['oldrow']];
			$entity1Ids[$k+1] = $entity1IdsCopy[$r['oldrow']];
		}
		// END OF - SORT ROWS AND REARRANGE ALL ROW RELATED DATA
		
		// SORT COLUMNS AND REARRANGE ALL COLUMN RELATED DATA
		foreach ($PhaseColumnMatrix as $key => $p) {
			$rphna[$key]  = $p['na'];
			$rph0[$key] = $p['0'];
			$rph1[$key] = $p['1'];
			$rph2[$key] = $p['2'];
			$rph3[$key] = $p['3'];
			$rph4[$key] = $p['4'];
			$rphblank[$key] = $p['blank'];
		}
		array_multisort($rph4, SORT_DESC, $rph3, SORT_DESC, $rph2, SORT_DESC, $rph1, SORT_DESC, $rph0, SORT_DESC,  $rphna, SORT_DESC,  $rphblank, SORT_DESC, $PhaseColumnMatrix);
		
		$counter = 1;
		$NewPhaseColumnMatrix = array();
		foreach($PhaseColumnMatrix as $k=>$r)
		{
			if($id == $r['entity'])
			{
				$NewPhaseColumnMatrix[0] = $r;
			}
			else
			{
				$NewPhaseColumnMatrix[$counter] = $r;
				$counter++;
			}
		}
		$PhaseColumnMatrix = $NewPhaseColumnMatrix;
		
		$col_active_totalCopy=$col_active_total;
		$col_count_totalCopy=$col_count_total;
		$col_indlead_totalCopy=$col_indlead_total;
		$col_active_owner_sponsored_totalCopy=$col_active_owner_sponsored_total;
		$columnsCopy = $columns;
		$columnsCompanyNameCopy = $columnsCompanyName;
		$columnsDisplayNameCopy = $columnsDisplayName;
		$columnsDescriptionCopy = $columnsDescription;
		$entity2IdsCopy = $entity2Ids;
			
		foreach($PhaseColumnMatrix as $k=>$r)
		{
			$col_active_total[$k+1]=$col_active_totalCopy[$r['oldcol']];
			$col_count_total[$k+1]=$col_count_totalCopy[$r['oldcol']];
			$col_indlead_total[$k+1]=$col_indlead_totalCopy[$r['oldcol']];
			$col_active_owner_sponsored_total[$k+1]=$col_active_owner_sponsored_totalCopy[$r['oldcol']];
			$columns[$k+1] = $columnsCopy[$r['oldcol']];
			$columnsCompanyName[$k+1] = $columnsCompanyNameCopy[$r['oldcol']];
			$columnsDisplayName[$k+1] = $columnsDisplayNameCopy[$r['oldcol']];
			$columnsDescription[$k+1] = $columnsDescriptionCopy[$r['oldcol']];
			$entity2Ids[$k+1] = $entity2IdsCopy[$r['oldcol']];
		}
		$last_num = count($entity2Ids);
		$second_last_num = $last_num-1;
		$LastEntity2 = $entity2Ids[count($entity2Ids)];
		// END OF - SORT COLS AND REARRANGE ALL ROW RELATED DATA
	}//END OF SORT IF
	
	$count_fillbomb=0;	
	if($_POST['dwcount']=='active' || $_GET['view_type'] == 'active')
	{
		$tooltip=$title="Active trials";
		$pdftitle="Active trials";
		$link_part = '&list=1';
		$mode = 'active';
	}
	elseif($_POST['dwcount']=='total' || $_GET['view_type'] == 'total')
	{
		$pdftitle=$tooltip=$title="All trials (Active + Inactive)";
		$link_part = '&list=2';
		$mode = 'total';
	}
	elseif($_POST['dwcount']=='active_owner_sponsored' || $_GET['view_type'] == 'active_owner_sponsored')
	{
		$pdftitle=$tooltip=$title="Active owner sponsored trials";
		$link_part = '&list=1&osflt=on';
		$mode = 'active_owner_sponsored';
	}
	else
	{
		$tooltip=$title="Active industry lead sponsor trials";
		$pdftitle="Active industry lead sponsor trials";
		$link_part = '&list=1&itype=0';
		$mode = 'indlead';
	}
	//if slider has default range dont add these parameters in links as OTT has same default range
	if($sr != 'now' || $er != '1 month')
	{
		$link_part .= '&sr='.$sr.'&er='.$er;
	}
	if($ohm == 'SOHM')
	$link_part .= '&hm=' . $id;
	
	if($ohm == 'SOHM' || $ohm == 'EOHMH')
		$CommonLinkForAll = urlPath() .'intermediary.php?';
	else
		$CommonLinkForAll = urlPath() .'trialzilla_ott.php?sourcepg=TZ&';	
	
	$link_part=str_replace(' ','+',$link_part);	
	
	$Report_Name = $ReportDisplayName;
	
	if($_POST['dwformat']=='pdfdown' || isset($_GET['pdf_x']))
	{
	
		require_once('tcpdf/config/lang/eng.php');
		require_once('tcpdf/tcpdf.php');  
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', true, 'UTF-8', false);
		// set document information
		//$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Larvol Trials');
		$pdf->SetTitle('Larvol Trials');
		$pdf->SetSubject('Larvol Trials');
		$pdf->SetKeywords('Larvol Trials Heatmap, Larvol Trials Heatmap PDF Export');
		$pdf->SetFont('freesans', ' ', 8, '', false); // Normal Font
		$pdf->setFontSubsetting(false);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
			
		// remove default header/footer
		$pdf->setPrintHeader(false);
	
		if($_POST['pageType']== 'editPage')
		$pdf->setPrintFooter(false);
		
		$marginBottom = 10;
		if($_POST['pageType']== 'editPage')
		{
			$pdf->setPrintFooter(false);
			$CustomFooter = true;
			$CustomFooterSize = 8;
		}
		else
		{
			$CustomFooter =  false;
			$CustomFooterSize = 0;
		}
		
		$PageNum = 0;
		
		//set some language-dependent strings
		$pdf->setLanguageArray($l);
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, $marginBottom);
		$pdf->AddPage();
		$PageNum = 1;
		
		//ini_set('pcre.backtrack_limit',strlen($pdfContent));	
		$name = $ReportDisplayName;
		
		////40% width formula - Now entity1 column will expand maximum upto 40% of the page width not more than that at initial stage only
		$Entity1_Width40 = 200.66 * 40 / 100;	/// give entity1 column upto 40% width of normal page orienation
		//if($Avail_Entity1_Col_width > $Entity1_Width40)
		$Avail_Entity1_Col_width = $Entity1_Width40;
		$entity1_Col_Width = 56;
		$Current_entity1_Col_Width = $entity1_Col_Width;
		if($Avail_Entity1_Col_width > $entity1_Col_Width)
		{
			foreach($rows as $row => $rid)
			{
				if(isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL && !empty($entity2Ids))
				{
					$Min_entity1NumLines=0;
					while($Min_entity1NumLines != 1)	///Check while we we dont get mimimum lines to display entity1 name
					{
						$current_NumLines=$pdf->getNumLines($rowsDisplayName[$row].$rowsCompanyName[$row].'  '.((trim($rowsTagName[$row]) != '') ? ' ['.$rowsTagName[$row].']':''), $Current_entity1_Col_Width);	//get number of lines
						if($current_NumLines == 1)	//if 1 line then stop processing, take next entity1
						$Min_entity1NumLines = $current_NumLines;
						else if($current_NumLines >= 1)	/// if more lines required to display text
						{
							if($Current_entity1_Col_Width < $Avail_Entity1_Col_width)	/// if possible to increase width then increase it
							$Current_entity1_Col_Width++;
							if($Current_entity1_Col_Width >= $Avail_Entity1_Col_width)	///if NOT possible to increase then stop execution take next entity1
							$Min_entity1NumLines = 1;
						}else if($current_NumLines < 1) $Min_entity1NumLines = 1;	/// if required line below range then stop and take next entity1
					}
				}
			}
			$entity1_Col_Width = $Current_entity1_Col_Width;	///new width
		}
		
		$entity2_Col_Width=23;
		
		$HColumn_Width = (((count($columns))+(($total_fld)? 1:0)) * ($entity2_Col_Width+0.5));
		
		if($total_fld) 
		{ 
			if($mode=='active')
			{
				$count_val=$active_total;
			}
			elseif($mode=='total')
			{
				$count_val=$count_total;
			}
			elseif($mode=='active_owner_sponsored')
			{
				$count_val=$active_owner_sponsored_total;
			}
			else
			{
				$count_val=$indlead_total;
			}
			$Total_Col_width = ($pdf->GetStringWidth($count_val, 'freesansb', 'B', 8) + 0.6);
			if($Total_Col_width < $Min_One_Liner)
			$Total_Col_width = $Min_One_Liner;
		}
		
		/// Calculate Max Column width and set it to all columns
		$maxEntity2_ConsistentWidth = 0;
		foreach($columns as $col => $val)
		{
			if($maxEntity2_ConsistentWidth < $Width_matrix[$col]['width'])
			$maxEntity2_ConsistentWidth = $Width_matrix[$col]['width'];
		}
		
		if($total_fld)
		{	
			if($maxEntity2_ConsistentWidth < $Total_Col_width)
			$maxEntity2_ConsistentWidth = $Total_Col_width;
		}
		
		$ConsistentRColumn_Width = (((count($columns))+(($total_fld)? 1:0)) * ($maxEntity2_ConsistentWidth+0.5));
		$ConsistentFlg = false;
		/// end of consistent width
			
		$RColumn_Width = 0; 
		foreach($columns as $col => $val)
		{
			$RColumn_Width = $RColumn_Width + $Width_matrix[$col]['width'] + 0.5;
		}
		
		if($total_fld) 
		{ 
			$RColumn_Width = $RColumn_Width + $Total_Col_width + 0.5; 
		}
		
		if(($HColumn_Width + $entity1_Col_Width + 0.5) < 200.66)
		{
			//// Potrait page orientation
			$pdf->setPageOrientation('p');
			$Page_Width = 200.66;
			$Rotation_Flg = 0;
			$All_Column_Width = $HColumn_Width;
		}
		else if(($RColumn_Width + $entity1_Col_Width + 0.5) < 200.66)
		{
			//// Potrait page orientation
			$pdf->setPageOrientation('p');
			$Page_Width = 200.66;
			$Rotation_Flg = 1;
			if(($ConsistentRColumn_Width + $entity1_Col_Width + 0.5) < 200.66)	//Check whether after applying consistency rule OHM fits
			{
				$All_Column_Width = $ConsistentRColumn_Width;
				$ConsistentFlg = true;
			}
			else	// If not go for inconsistent model
			{
				$All_Column_Width = $RColumn_Width;
			}
		}
		else if(($HColumn_Width + $entity1_Col_Width + 0.5) < 264.16)
		{
			//// Landscape page orientation
			$pdf->setPageOrientation('l');
			$Page_Width = 264.16;
			$Rotation_Flg = 0;
			$All_Column_Width = $HColumn_Width;
		}
		else
		{
			//// Landscape page orientation
			$pdf->setPageOrientation('l');
			$Page_Width = 264.16;
			$Rotation_Flg = 1;
			if(($ConsistentRColumn_Width + $entity1_Col_Width + 0.5) < 264.16)	//Check whether after applying consistency rule OHM fits
			{
				$All_Column_Width = $ConsistentRColumn_Width;
				$ConsistentFlg =true;
			}
			else	// If not go for inconsistent model
			{
				$All_Column_Width = $RColumn_Width;
			}
		}
		//$pdf->setPageOrientation('p');
		//$Page_Width = 192;
		//$Rotation_Flg = 1;
		//$All_Column_Width = $RColumn_Width;
		if($Rotation_Flg == 0)	//// Using this we dont have to use different code for specifying entity2 column width
		{
			foreach($columns as $col => $val)
			{
				$Width_matrix[$col]['width'] = $entity2_Col_Width;
			}
			$Total_Col_width = $entity2_Col_Width;
		}
		
		if($ConsistentFlg)	//// Apply consistent width to all columns
		{
			foreach($columns as $col => $val)
			{
				$Width_matrix[$col]['width'] = $maxEntity2_ConsistentWidth;
			}
			$Total_Col_width = $maxEntity2_ConsistentWidth;
		}
		
		//New Part to give required width to entity1 column from extra width
		$Avail_Entity1_Col_width = $Page_Width - $All_Column_Width - $entity1_Col_Width;
		$Current_entity1_Col_Width = $entity1_Col_Width;
		if($Avail_Entity1_Col_width > 0)
		{
			foreach($rows as $row => $rid)
			{
				if(isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL && !empty($entity2Ids))
				{
					$Min_entity1NumLines=0;
					while($Min_entity1NumLines != 1)	///Check while we we dont get mimimum lines to display entity1 name
					{
						$current_NumLines=$pdf->getNumLines($rowsDisplayName[$row].$rowsCompanyName[$row].'  '.((trim($rowsTagName[$row]) != '') ? ' ['.$rowsTagName[$row].'] ':''), $Current_entity1_Col_Width);	//get number of lines
						if($current_NumLines == 1)	//if 1 line then stop processing, take next entity1
						$Min_entity1NumLines = $current_NumLines;
						else if($current_NumLines >= 1)	/// if more lines required to display text
						{
							if($Avail_Entity1_Col_width > 0)	/// if possible to increase width then increase it
							{
								if($Avail_Entity1_Col_width < 1)
								{
									$Current_entity1_Col_Width = $Current_entity1_Col_Width + $Avail_Entity1_Col_width;
									$Avail_Entity1_Col_width = 0;
								}
								else
								{
									$Current_entity1_Col_Width++;
									$Avail_Entity1_Col_width--;
								}
							}
							if($Avail_Entity1_Col_width <= 0)	///if NOT possible to increase then stop execution take next entity1
							$Min_entity1NumLines = 1;
						}else if($current_NumLines < 1) $Min_entity1NumLines = 1;	/// if required line below range then stop and take next entity1
					}
				}
			}
			$entity1_Col_Width = $Current_entity1_Col_Width;	///new width
		}
		//End of - New Part to give required width to entity1 column from extra width
		
		///// Extra width addition part for entity2 columns
		//- If after rotation and after giving max width to entity1 column, extra width remains, distribute it equally to all columns, to achieve fitting
		$Avail_Entity2_Col_width = $Page_Width - $entity1_Col_Width - $All_Column_Width;
		if($Avail_Entity2_Col_width > 0)
		{
			$extra_width = $Avail_Entity2_Col_width / ((count($columns))+(($total_fld)? 1:0));
			foreach($columns as $col => $val)
			{
				$Width_matrix[$col]['width'] = $Width_matrix[$col]['width'] + $extra_width;
				$All_Column_Width = $All_Column_Width + $extra_width;
			}
			if($total_fld) 
			{ 
				$Total_Col_width = $Total_Col_width + $extra_width; 
				$All_Column_Width = $All_Column_Width + $extra_width;
			}
		}
		////////////////// End of Extra width addition part for entity2 columns
		
		///Perform all line number/height calculation at start only as after padding/margin value returned may be wrong
		
		///Calculate height for category entity2 row
		$pdf->SetFont('freesans', ' ', 8, '', false); // Bold Font
		$Max_Cat_entity2NumLines=0;
		foreach($columns as $col => $val)
		{
			if($ColumnsSpan[$col] > 0 && $columnsCategoryName[$col] != 'Undefined')
			{
				$i = 1; $width = 0; $col_id = $col;
				while($i <= $ColumnsSpan[$col])
				{
					$width = $width + $Width_matrix[$col_id]['width'];
					$i++; $col_id++;
				}
				$current_NumLines=$pdf->getNumLines($columnsCategoryName[$col], $width);
				if($Max_Cat_entity2NumLines < $current_NumLines)
				$Max_Cat_entity2NumLines = $current_NumLines;
			}
		}
		if($Max_Cat_entity2NumLines < 2)
		$Cat_Entity2_Row_height = $Line_Height;
		else
		$Cat_Entity2_Row_height = $Max_Cat_entity2NumLines * $Line_Height;
		
		///Calculate height for entity2 row
		$pdf->SetFont('freesansb', 'B ', 8); // Bold Font
		if($Rotation_Flg == 0)
		{
			$Max_entity2NumLines=0;
			foreach($columns as $col => $val)
			{
				$val = $columnsDisplayName[$col].$columnsCompanyName[$col].(($columnsTagName[$col] != NULL && $columnsTagName[$col] != '')? ' ['.$columnsTagName[$col].'] ':'');
				if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
				$current_NumLines=$pdf->getNumLines($val, $entity2_Col_Width);
				else $current_NumLines = 1;
				if($Max_entity2NumLines < $current_NumLines)
				$Max_entity2NumLines = $current_NumLines;
			}
			$Entity2_Row_height = $Max_entity2NumLines * $Bold_Line_Height;
		}
		else
		{
			$Max_entity2StringLength=0;
			foreach($columns as $col => $val)
			{
				$val = $columnsDisplayName[$col].$columnsCompanyName[$col].(($columnsTagName[$col] != NULL && $columnsTagName[$col] != '')? ' ['.$columnsTagName[$col].'] ':'');
				if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
				$current_StringLength = $pdf->GetStringWidth($val, 'freesansb', 'B', 8)+2;
				else $current_StringLength = 5;
				if($Max_entity2StringLength < $current_StringLength)
				$Max_entity2StringLength = $current_StringLength;
			}
			$Entity2_Row_height = $Max_entity2StringLength + 2;
		}
		$pdf->SetFont('freesans', ' ', 8, '', false); // Normal Font
		
		///Calculate height for entity1 row
		foreach($rows as $row => $rid)
		{
			$data = trim($rowsCompanyName[$row]).((trim($rowsTagName[$row]) != '') ? ' ['.$rowsTagName[$row].']':'');
			$RownHeight = getNumLinesPDFExport($rowsDisplayName[$row], $data, $entity1_Col_Width, $Bold_Line_Height, $Line_Height, $pdf);
			$entity1_row_height_calc[$row] = $RownHeight[1];
		}
		
		//////End of all heights calculation
		
		////Set new margins so heatmap table will be centered aligned
		$dimensions = $pdf->getPageDimensions();
		$newMarginWidth = (($dimensions['wk'] - ($entity1_Col_Width + $All_Column_Width))/2);
		$pdf->SetRightMargin($newMarginWidth);
		$pdf->SetLeftMargin($newMarginWidth);
		
		$pdf->SetTextColor(255);
		$pdf->Ln(0);
		$BorderStart_X = $pdf->GetX();
		$BorderStart_Y = $pdf->GetY();
		$pdf->SetFillColor(0, 0, 128);
		$pdf->SetFont('freesansb', 'B ', 8); // Bold Font
		$current_StringLength = $pdf->GetStringWidth($Report_Name.' Heatmap', 'freesansb', 'B', 8) + 5;
		$newMarginWidth = (($dimensions['wk'] - ($current_StringLength))/2);
		$pdf->MultiCell(($entity1_Col_Width + $All_Column_Width), '', $Report_Name.' Heatmap', $border=0, $align='C', $fill=1, $ln=1, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
		$current_StringLength = $pdf->GetStringWidth($pdftitle, 'freesansb', 'B', 8) + 5;
		$newMarginWidth = (($dimensions['wk'] - ($current_StringLength))/2);
		$pdf->MultiCell(($entity1_Col_Width + $All_Column_Width), '', $pdftitle, $border=0, $align='C', $fill=1, $ln=1, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
		$pdf->SetFont('freesans', ' ', 8, '', false); // Normal Font
		$pdf->Ln(3);
		
		$pdf->SetFillColor(221, 221, 255);
		
		$pdf->SetTextColor(0);
		
		$Main_X = $pdf->GetX();
		$Main_Y = $pdf->GetY();
		
		$Place_X = $Main_X;
		$Place_Y = $Main_Y;
		$pdf->SetFont('freesans', ' ', 8, '', false); // Normal Font
		/////////// Print Category Row
		$CatPresenceFlg = 0;
		$pdf->SetFillColor(255, 255, 255);
		$pdf->MultiCell($entity1_Col_Width, $Cat_Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Cat_Entity2_Row_height);
		$Place_X = $Place_X + $entity1_Col_Width + 0.5;
		
		foreach($columns as $col => $val)
		{
			//$pdf->setCellMargins(0, 0, 0, 0);
			
			if($dtt)
			{
				if($second_last_cat_col==$col && $second_last_category != $last_category) $ln=1; else if($last_cat_col==$col) $ln=1; else $ln=0;
			}
			else
			{
				if($last_cat_col==$col) $ln=1; else $ln=0;
			}
			
			if($ColumnsSpan[$col] > 0)
			{
				if($columnsCategoryName[$col] != 'Undefined')
				{
					$CatPresenceFlg = 1;
					$border = array('mode' => 'int', 'LTR' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,0,0)));
					
					$i = 1; $width = 0; $col_id = $col;
					while($i <= $ColumnsSpan[$col])
					{
						$width = $width + $Width_matrix[$col_id]['width'];
						$i++; $col_id++;
					}
					$Cat_Entity2_Row_width = $width +((($ColumnsSpan[$col] == 1) ? 0:0.5) * ($ColumnsSpan[$col]-1));
					$pdfContent = '<div align="center" style="vertical-align:middle; float:none;">';
					$current_NumLines=$pdf->getNumLines($columnsCategoryName[$col], $Cat_Entity2_Row_width);
					if($Max_Cat_entity2NumLines > $current_NumLines)
					{
						$extra_space = ($Max_Cat_entity2NumLines - $current_NumLines) * $Line_Height;
						//$pdfContent .= '<br style="line-height:'.((($extra_space* 72 / 96)/2)+1).'px;" />';
						$pdf->setCellPaddings(0, ($extra_space/2), 0, 0);
					}
					$pdfContent .= $columnsCategoryName[$col].'</div>';
					$pdf->MultiCell($Cat_Entity2_Row_width, $Cat_Entity2_Row_height, $pdfContent, $border, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Cat_Entity2_Row_height);
					$pdf->setCellPaddings(0, 0, 0, 0);
				}
				else
				{
					$i = 1; $width = 0; $col_id = $col;
					while($i <= $ColumnsSpan[$col])
					{
						$width = $width + $Width_matrix[$col_id]['width'];
						$i++; $col_id++;
					}
					$Cat_Entity2_Row_width = $width +((($ColumnsSpan[$col] == 1) ? 0:0.5) * ($ColumnsSpan[$col]-1));
					$pdf->MultiCell($Cat_Entity2_Row_width, $Cat_Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Cat_Entity2_Row_height);
				}
				$Place_X = $Place_X + $Cat_Entity2_Row_width + 0.5;
			}
		}
		if($CatPresenceFlg == 1)
		$Place_Y = $Place_Y + $Cat_Entity2_Row_height + 0.5;
		$Place_X = $Main_X;
		
		if($Rotation_Flg == 1)
		$Entity2_Row_height = $Entity2_Row_height + 0.5;	//padding adjustment in calculated height of entity2 cell
		
		$pdf->SetFont('freesansb', 'B ', 8); // Bold Font
		$pdf->SetFillColor(255, 255, 255);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));
		
		$pdf->MultiCell($entity1_Col_Width, $Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Entity2_Row_height);
		
		$Place_X = $Place_X + $entity1_Col_Width + 0.5;
		$Place_Y_Bk = $Place_Y;
		
		foreach($columns as $col => $val)
		{
			$pdf->SetFillColor(221, 221, 255);
			$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));
			$val = $columnsDisplayName[$col].$columnsCompanyName[$col].(($columnsTagName[$col] != NULL && $columnsTagName[$col] != '')? ' ['.$columnsTagName[$col].'] ':'');
			$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
			$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
			
			if(isset($total_fld) && $total_fld == "1")
			$ln=0;
			else if($dtt && $second_last_num == $col && $total_fld != "1")
			$ln=1;
			else if($max_column['num'] == $col && $total_fld != "1")
			$ln=1;
			else $ln=0;
				
			if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
			{
				if($mode=='active')
				{
					$count_val=$col_active_total[$col];
				}
				elseif($mode=='total')
				{
					$count_val=$col_count_total[$col];
				}
				elseif($mode=='active_owner_sponsored')
				{
					$count_val=$col_active_owner_sponsored_total[$col];
				}
				else
				{
					$count_val=$col_indlead_total[$col];
				}
				
				if($Rotation_Flg == 1)
					$pdfContent = '<div align="left" style="vertical-align:middle; float:none;">';
				else
					$pdfContent = '<div align="center" style="vertical-align:middle; float:none;">';
				
				if($Rotation_Flg == 1)
				{
					$extra_space = $Width_matrix[$col]['width'] - $Bold_Line_Height;
					//$pdfContent .= '<br style="line-height:'.((($extra_space* 72 / 96)/2)).'px;" />';
					$pdf->setCellPaddings(0.5, ($extra_space/2), 0, 0);
				}
				else
				{
					$current_NumLines=$pdf->getNumLines($val, $Width_matrix[$col]['width']);
					if($Max_entity2NumLines > $current_NumLines)
					{
						$extra_space = ($Max_entity2NumLines - $current_NumLines) * $Bold_Line_Height;
						//$pdfContent .= '<br style="line-height:'.((($extra_space* 72 / 96)/2)).'px;" />';
						$pdf->setCellPaddings(0, ($extra_space/2), 0, 0);
					}
				}
				
				//// This part added to add padding on both side of entity2 names as smaller entity2 name links get misplaced in PDF
				if($Rotation_Flg == 1)
				{
					while($pdf->getNumLines($val, ($Entity2_Row_height - 0.5)) == 1)
					{
						if($pdf->getNumLines($val.'.', ($Entity2_Row_height - 0.5)) == 1)
							$val=$val.'.';
						else
							break;
					}
					$val = str_replace('.','<font style="color:#ddddff">.</font>',$val);
					$val = '<font style="color:#000000">'.$val.'</font>';
				}
				////////End of part added for padding
				
				$pdfContent .= '<a style="color:#000000; text-decoration:none;" href="'. $CommonLinkForAll .'e2=' . $entity2Ids[$col]. $link_part . '" target="_blank" title="'. $caltTitle .'">'.$val.'</a></div>';
				
				if($Rotation_Flg == 1)
				{
					$pdf->StartTransform(); 
					$Place_Y = $Place_Y_Bk + $Entity2_Row_height;
					$pdf->Rotate(90,$Place_X, $Place_Y);
					$pdf->MultiCell($Entity2_Row_height, $Width_matrix[$col]['width'], $pdfContent, $border=0, $align='L', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Width_matrix[$col]['width']);
					$pdf->StopTransform();
					$Place_X = $Place_X + $Width_matrix[$col]['width'] + 0.5;
				}
				else
				{
					$pdf->MultiCell($Width_matrix[$col]['width'], $Entity2_Row_height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Entity2_Row_height);
					$Place_X = $Place_X + $Width_matrix[$col]['width'] + 0.5;
				}
				
				$pdf->setCellPaddings(0, 0, 0, 0);
			}
			else
			{
				if($Rotation_Flg == 1)
				{
					$pdf->StartTransform(); 
					$Place_Y = $Place_Y_Bk + $Entity2_Row_height;
					$pdf->Rotate(90,$Place_X, $Place_Y);
					$pdf->MultiCell($Entity2_Row_height, $Width_matrix[$col]['width'], '', $border=0, $align='L', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Width_matrix[$col]['width']);
					$pdf->StopTransform();
					$Place_X = $Place_X + $Width_matrix[$col]['width'] + 0.5;
				}
				else
				{
					$pdf->MultiCell($Width_matrix[$col]['width'], $Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Entity2_Row_height);
					$Place_X = $Place_X + $Width_matrix[$col]['width'] + 0.5;
				}
			}
		}
		//if total checkbox is selected
		if(isset($total_fld) && $total_fld == "1")
		{
			$pdf->getCellPaddings();
			$pdf->SetFillColor(221, 221, 255);
			if(!empty($entity1Ids) && !empty($entity2Ids))
			{
				if($mode=='active')
				{
					$count_val=$active_total;
				}
				elseif($mode=='total')
				{
					$count_val=$count_total;
				}
				elseif($mode=='active_owner_sponsored')
				{
					$count_val=$active_owner_sponsored_total;
				}
				else
				{
					$count_val=$indlead_total;
				}
				$entity1Ids = array_filter($entity1Ids);
				$entity2Ids = array_filter($entity2Ids);
				
				if($Rotation_Flg == 1)
					$pdfContent = '<div align="left" style="vertical-align:middle; float:none;">';
				else
					$pdfContent = '<div align="center" style="vertical-align:middle; float:none;">';
				
				if($Rotation_Flg == 1)
				{
					$extra_space = $Total_Col_width  - $Bold_Line_Height;
				}
				else
				{
					$extra_space = ($Max_entity2NumLines - 1) * $Bold_Line_Height;
				}
				
				//// This part added to add padding on both side of entity2 names as smaller entity2 name links get misplaced in PDF
				if($Rotation_Flg == 1)
				{
					while($pdf->getNumLines($count_val, ($Entity2_Row_height-0.5)) == 1)
					{
						if($pdf->getNumLines($count_val.'.', ($Entity2_Row_height-0.5)) == 1)
							$count_val=$count_val.'.';
						else
							break;
					}
					$count_val = str_replace('.','<font style="color:#ddddff">.</font>',$count_val);
					$count_val = '<font style="color:#000000">'.$count_val.'</font>';
				}
				////////End of part added for padding
				
				//$pdfContent .= '<br style="line-height:'.((($extra_space* 72 / 96)/2)).'px;" />';
				$pdf->setCellPaddings(0, ($extra_space/2), 0, 0);
				
				$pdfContent .= '<a style="color:#000000; text-decoration:none;" href="'. $CommonLinkForAll .'e1=' . implode(',', $entity1Ids) . '&e2=' . implode(',', $entity2Ids). $link_part . '" target="_blank" title="'. $title .'">'.$count_val.'</a></div>';
				
				if($Rotation_Flg == 1)
				{
					$pdf->StartTransform(); 
					$Place_Y = $Place_Y_Bk + $Entity2_Row_height;
					$pdf->Rotate(90,$Place_X, $Place_Y);
					$pdf->MultiCell($Entity2_Row_height, $Total_Col_width, $pdfContent, $border=0, $align='L', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Total_Col_width);
					$pdf->StopTransform();
					$Place_X = $Place_X + $Total_Col_width + 0.5;
				}
				else
				{
					$pdf->MultiCell($Total_Col_width, $Entity2_Row_height, $pdfContent, $border=0, $align='C', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Entity2_Row_height);
					$Place_X = $Place_X + $Total_Col_width + 0.5;
				}
				
				$pdf->setCellPaddings(0, 0, 0, 0);
			}
			else
			{
				if($Rotation_Flg == 1)
				{
					$pdf->StartTransform(); 
					$Place_Y = $Place_Y_Bk + $Entity2_Row_height;
					$pdf->Rotate(90,$Place_X, $Place_Y);
					$pdf->MultiCell($Entity2_Row_height, $Total_Col_width, '', $border=0, $align='L', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Total_Col_width);
					$pdf->StopTransform();
					$Place_X = $Place_X + $Total_Col_width + 0.5;
				}
				else
				{
					$pdf->MultiCell($Total_Col_width, $Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Entity2_Row_height);
					$Place_X = $Place_X + $Total_Col_width + 0.5;
				}
			}
		}
		$pdf->SetFont('freesans', ' ', 8, '', false); // Normal Font
		
		$pdf->SetX($Main_X);
		$pdf->SetY(($Place_Y_Bk + $Entity2_Row_height + 0.5));
		
		
		foreach($rows as $row => $rid)
		{
			$dimensions = $pdf->getPageDimensions();
			$startY = $pdf->GetY();
			$pdf->SetFont('freesans', ' ', 8, '', false); // Reset Font
			
			$entity1_row_height = $entity1_row_height_calc[$row];
			
			if($entity1_row_height == $Line_Height)
			$entity1_row_height = $entity1_row_height +0.5;
			
			$Main_X = $pdf->GetX();
			$Main_Y = $pdf->GetY();
		
			$Place_X = $Main_X;
			$Place_Y = $Main_Y;
			
			$checkHeight = $entity1_row_height;
			
			$cat = (isset($rowsCategoryName[$row]) && $rowsCategoryName[$row] != '')? $rowsCategoryName[$row]:'Undefined';
			$rowsCategoryNamePrint = false;
			$pdf->SetFont('freesansb', 'B ', 8); // Bold Font
			if($RowsSpan[$row] > 0 && $cat != 'Undefined')
			{
				$Entity1_Rowcat_width = ($entity1_Col_Width + $All_Column_Width);
				$rowcount = $pdf->getNumLines($cat, $Entity1_Rowcat_width);
 				$Entity1_Rowcat_height = ($rowcount * $Bold_Line_Height);
				if (($startY + $Entity1_Rowcat_height + 1) + $CustomFooterSize + $dimensions['bm'] < ($dimensions['hk']))
				{	
					PrintEntity1CategoryforPDFExport($dtt, $rowsCategoryEntityIds1[$cat], $last_entity2, $link_part, $cat, $Entity1_Rowcat_width, $Entity1_Rowcat_height, $Place_X, $Place_Y, $pdf);								
					$Place_Y = $Place_Y + $Entity1_Rowcat_height + 0.5;
					$startY = $Place_Y;
					$pdf->SetY($Place_Y);
					$rowsCategoryNamePrint = true;
				}
				else
				{
					$checkHeight = $Entity1_Rowcat_height;
				}				
			}
			$pdf->SetFont('freesans', ' ', 8, '', false); // Normal Font
			
			if (($startY + $checkHeight + 1) + $CustomFooterSize + $dimensions['bm'] > ($dimensions['hk']))
			{
				//this row will cause a page break, draw the bottom border on previous row and give this a top border
				
				$BorderStop_X = $pdf->GetX();
				$BorderStop_Y = $pdf->GetY();
				
				/// Create Border Around Heatmap before going to new page
				$pdf->SetFillColor(0, 0, 128);
				$border = array('mode' => 'ext', 'LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,0,128)));
				$pdf->MultiCell(($entity1_Col_Width + $All_Column_Width + 0.1), ($BorderStop_Y - $BorderStart_Y + 0.5), '', $border, $align='C', $fill=0, $ln=0, $BorderStart_X, $BorderStart_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=false, $maxh=($BorderStop_Y - $BorderStart_Y + 0.5), 'T');
				
				if($CustomFooter)
				PrintFooterPDFExport($pdf, $BorderStart_X, ($dimensions['hk'] - $dimensions['bm'] - $CustomFooterSize + 0.5), ($CustomFooterSize-1), ($entity1_Col_Width + $All_Column_Width + 0.1), $PageNum);

		
				//we could force a page break and rewrite grid headings here
				$pdf->AddPage();
				$PageNum = $PageNum + 1;
				
				$BorderStart_X = $pdf->GetX();
				$BorderStart_Y = $pdf->GetY();
				$pdf->Ln(1);
				
				$Main_X = $pdf->GetX();
				$Main_Y = $pdf->GetY();
		
				$Place_X = $Main_X;
				$Place_Y = $Main_Y;
		
				////Add category row again
				/////////// Print Category Row
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($entity1_Col_Width, $Cat_Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Cat_Entity2_Row_height);
				$Place_X = $Place_X + $entity1_Col_Width + 0.5;
		
				foreach($columns as $col => $val)
				{
					if($dtt)
					{
						if($second_last_cat_col==$col && $second_last_category != $last_category) $ln=1; else if($last_cat_col==$col) $ln=1; else $ln=0;
					}
					else
					{
						if($last_cat_col==$col) $ln=1; else $ln=0;
					}
					
					if($ColumnsSpan[$col] > 0)
					{
						if($columnsCategoryName[$col] != 'Undefined')
						{
							$border = array('mode' => 'int', 'LTR' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,0,0)));
					
							$i = 1; $width = 0; $col_id = $col;
							while($i <= $ColumnsSpan[$col])
							{
								$width = $width + $Width_matrix[$col_id]['width'];
								$i++; $col_id++;
							}
							$Cat_Entity2_Row_width = $width +((($ColumnsSpan[$col] == 1) ? 0:0.5) * ($ColumnsSpan[$col]-1));
							$pdfContent = '<div align="center" style="vertical-align:middle; float:none;">';
							$current_NumLines=$pdf->getNumLines($columnsCategoryName[$col], $Cat_Entity2_Row_width);
							if($Max_Cat_entity2NumLines > $current_NumLines)
							{
								$extra_space = ($Max_Cat_entity2NumLines - $current_NumLines) * $Line_Height;
								$pdfContent .= '<br style="line-height:'.((($extra_space* 72 / 96)/2)+1).'px;" />';
							}
							$pdfContent .= $columnsCategoryName[$col].'</div>';
							$pdf->MultiCell($Cat_Entity2_Row_width, $Cat_Entity2_Row_height, $pdfContent, $border, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Cat_Entity2_Row_height);
							$Place_X = $Cat_Entity2_Row_width + $Place_X + 0.5;
						}
						else
						{
							$i = 1; $width = 0; $col_id = $col;
							while($i <= $ColumnsSpan[$col])
							{
								$width = $width + $Width_matrix[$col_id]['width'];
								$i++; $col_id++;
							}
							$Cat_Entity2_Row_width = $width +((($ColumnsSpan[$col] == 1) ? 0:0.5) * ($ColumnsSpan[$col]-1));
							$pdf->MultiCell($Cat_Entity2_Row_width, $Cat_Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Cat_Entity2_Row_height);
							$Place_X = $Cat_Entity2_Row_width + $Place_X + 0.5;
						}
					}
				}
				if($CatPresenceFlg == 1)
				$Place_Y = $Place_Y + $Cat_Entity2_Row_height + 0.5;
				$Place_X = $Main_X;
				
				
				///Add the header row again at new page
				$pdf->SetFillColor(255, 255, 255);
				$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));
				
				$pdf->MultiCell($entity1_Col_Width, $Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
				
				$pdf->SetFillColor(221, 221, 255);
				$Place_X = $Place_X + $entity1_Col_Width + 0.5;
				$Place_Y_Bk = $Place_Y;
				$pdf->SetFont('freesansb', 'B ', 8); // Bold Font
				foreach($columns as $col => $val)
				{
					$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));
					$val = $columnsDisplayName[$col].$columnsCompanyName[$col].(($columnsTagName[$col] != NULL && $columnsTagName[$col] != '')? ' ['.$columnsTagName[$col].'] ':'');
					$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
					$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
					
					if(isset($total_fld) && $total_fld == "1")
					$ln=0;
					else if($dtt && $second_last_num == $col && $total_fld != "1")
					$ln=1;
					else if($max_column['num'] == $col && $total_fld != "1")
					$ln=1;
					else $ln=0;
						
					if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
					{
						if($mode=='active')
						{
							$count_val=$col_active_total[$col];
						}
						elseif($mode=='total')
						{
							$count_val=$col_count_total[$col];
						}
						elseif($mode=='active_owner_sponsored')
						{
							$count_val=$col_active_owner_sponsored_total[$col];
						}
						else
						{
							$count_val=$col_indlead_total[$col];
						}
						
						if($Rotation_Flg == 1)
							$pdfContent = '<div align="left" style="vertical-align:middle; float:none;">';
						else
							$pdfContent = '<div align="center" style="vertical-align:middle; float:none;">';
						
						if($Rotation_Flg == 1)
						{
							$extra_space = $Width_matrix[$col]['width'] - $Bold_Line_Height;
							//$pdfContent .= '<br style="line-height:'.((($extra_space* 72 / 96)/2)).'px;" />';
							$pdf->setCellPaddings(0.5, ($extra_space/2), 0, 0);
						}
						else
						{
							$current_NumLines=$pdf->getNumLines($val, $Width_matrix[$col]['width']);
							if($Max_entity2NumLines > $current_NumLines)
							{
								$extra_space = ($Max_entity2NumLines - $current_NumLines) * $Bold_Line_Height;
								//$pdfContent .= '<br style="line-height:'.((($extra_space* 72 / 96)/2)).'px;" />';
								$pdf->setCellPaddings(0, ($extra_space/2), 0, 0);
							}
						}
						
						//// This part added to add padding on both side of entity2 names as smaller entity2 name links get misplaced in PDF
						if($Rotation_Flg == 1)
						{
							while($pdf->getNumLines($val, ($Entity2_Row_height-0.5)) == 1)
							{
								if($pdf->getNumLines($val.'.', ($Entity2_Row_height-0.5)) == 1)
									$val=$val.'.';
								else
									break;
							}
							$val = str_replace('.','<font style="color:#ddddff">.</font>',$val);
							$val = '<font style="color:#000000">'.$val.'</font>';
						}
						////////End of part added for padding
						
						$pdfContent .= '<a style="color:#000000; text-decoration:none;" href="'. $CommonLinkForAll .'e2=' . $entity2Ids[$col]. $link_part . '" target="_blank" title="'. $caltTitle .'">'.$val.'</a></div>';
						
						if($Rotation_Flg == 1)
						{
							$pdf->StartTransform(); 
							$Place_Y = $Place_Y_Bk + $Entity2_Row_height;
							$pdf->Rotate(90,$Place_X, $Place_Y);
							$pdf->MultiCell($Entity2_Row_height, $Width_matrix[$col]['width'], $pdfContent, $border=0, $align='L', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Width_matrix[$col]['width']);
							$pdf->StopTransform();
							$Place_X = $Place_X + $Width_matrix[$col]['width'] + 0.5;
						}
						else
						{
							$pdf->MultiCell($Width_matrix[$col]['width'], $Entity2_Row_height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Entity2_Row_height);
							$Place_X = $Place_X + $Width_matrix[$col]['width'] + 0.5;
						}
						
						$pdf->setCellPaddings(0, 0, 0, 0);
					}
					else
					{
						if($Rotation_Flg == 1)
						{
							$pdf->StartTransform(); 
							$Place_Y = $Place_Y_Bk + $Entity2_Row_height;
							$pdf->Rotate(90,$Place_X, $Place_Y);
							$pdf->MultiCell($Entity2_Row_height, $Width_matrix[$col]['width'], '', $border=0, $align='L', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Width_matrix[$col]['width']);
							$pdf->StopTransform();
							$Place_X = $Place_X + $Width_matrix[$col]['width'] + 0.5;
						}
						else
						{
							$pdf->MultiCell($Width_matrix[$col]['width'], $Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Entity2_Row_height);
							$Place_X = $Place_X + $Width_matrix[$col]['width'] + 0.5;
						}
					}
				}
				//if total checkbox is selected
				if(isset($total_fld) && $total_fld == "1")
				{
					$pdf->getCellPaddings();
					$pdf->SetFillColor(221, 221, 255);
					if(!empty($entity1Ids) && !empty($entity2Ids))
					{
						if($mode=='active')
						{
							$count_val=$active_total;
						}
						elseif($mode=='total')
						{
							$count_val=$count_total;
						}
						elseif($mode=='active_owner_sponsored')
						{
							$count_val=$active_owner_sponsored_total;
						}
						else
						{
							$count_val=$indlead_total;
						}
						$entity1Ids = array_filter($entity1Ids);
						$entity2Ids = array_filter($entity2Ids);
						
						if($Rotation_Flg == 1)
							$pdfContent = '<div align="left" style="vertical-align:middle; float:none;">';
						else
							$pdfContent = '<div align="center" style="vertical-align:middle; float:none;">';
						
						if($Rotation_Flg == 1)
						{
							$extra_space = (strlen($count_val) * 2) + 2  - $Bold_Line_Height;
						}
						else
						{
							$extra_space = ($Max_entity2NumLines - 1) * $Bold_Line_Height;
						}
						
						//// This part added to add padding on both side of entity2 names as smaller entity2 name links get misplaced in PDF
						if($Rotation_Flg == 1)
						{
							while($pdf->getNumLines($count_val, ($Entity2_Row_height-0.5)) == 1)
							{
								if($pdf->getNumLines($count_val.'.', ($Entity2_Row_height-0.5)) == 1)
									$count_val=$count_val.'.';
								else
									break;
							}
							$count_val = str_replace('.','<font style="color:#ddddff">.</font>',$count_val);
							$count_val = '<font style="color:#000000">'.$count_val.'</font>';
						}
						////////End of part added for padding
				
						//$pdfContent .= '<br style="line-height:'.((($extra_space* 72 / 96)/2)).'px;" />';
						$pdf->setCellPaddings(0, ($extra_space/2), 0, 0);
						
						$pdfContent .= '<a style="color:#000000; text-decoration:none;" href="'. $CommonLinkForAll .'e1=' . implode(',', $entity1Ids) . '&e2=' . implode(',', $entity2Ids). $link_part . '" target="_blank" title="'. $title .'">'.$count_val.'</a></div>';
				
						if($Rotation_Flg == 1)
						{
							$pdf->StartTransform(); 
							$Place_Y = $Place_Y_Bk + $Entity2_Row_height;
							$pdf->Rotate(90,$Place_X, $Place_Y);
							$pdf->MultiCell($Entity2_Row_height, $Total_Col_width, $pdfContent, $border=0, $align='L', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Total_Col_width);
							$pdf->StopTransform();
							$Place_X = $Place_X + $Total_Col_width + 0.5;
						}
						else
						{
							$pdf->MultiCell($Total_Col_width, $Entity2_Row_height, $pdfContent, $border=0, $align='C', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Entity2_Row_height);
							$Place_X = $Place_X + $Total_Col_width + 0.5;
						}
						
						$pdf->setCellPaddings(0, 0, 0, 0);
					}
					else
					{
						if($Rotation_Flg == 1)
						{
							$pdf->StartTransform(); 
							$Place_Y = $Place_Y_Bk + $Entity2_Row_height;
							$pdf->Rotate(90,$Place_X, $Place_Y);
							$pdf->MultiCell($Entity2_Row_height, $Total_Col_width, '', $border=0, $align='L', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Total_Col_width);
							$pdf->StopTransform();
							$Place_X = $Place_X + $Total_Col_width + 0.5;
						}
						else
						{
							$pdf->MultiCell($Total_Col_width, $Entity2_Row_height, '', $border=0, $align='C', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$Entity2_Row_height);
							$Place_X = $Place_X + $Total_Col_width + 0.5;
						}
					}
				}
				
				$pdf->SetX($Main_X);
				$pdf->SetY(($Place_Y_Bk + $Entity2_Row_height + 0.5));
				
				$pdf->SetFont('freesans', ' ', 8, '', false); // Normal Font
				///End of header row	
							
			} elseif ((ceil($startY) + $entity1_row_height) + $dimensions['bm'] == floor($dimensions['hk'])) {
				//fringe case where this cell will just reach the page break
				//draw the cell with a bottom border as we cannot draw it otherwise
				
			} else {
				//normal cell
			}
			
			$Place_X = $pdf->GetX();
			$Place_Y = $pdf->GetY();
				
			$cat = (isset($rowsCategoryName[$row]) && $rowsCategoryName[$row] != '')? $rowsCategoryName[$row]:'Undefined';
			
			$pdf->SetFont('freesansb', 'B ', 8); // Bold Font
			if($RowsSpan[$row] > 0 && $cat != 'Undefined' && !$rowsCategoryNamePrint)
			{
				$Entity1_Rowcat_width = ($entity1_Col_Width + $All_Column_Width);
				$rowcount = $pdf->getNumLines($cat, $Entity1_Rowcat_width);
 				$Entity1_Rowcat_height = ($rowcount * $Bold_Line_Height);	
				PrintEntity1CategoryforPDFExport($dtt, $rowsCategoryEntityIds1[$cat], $last_entity2, $link_part, $cat, $Entity1_Rowcat_width, $Entity1_Rowcat_height, $Place_X, $Place_Y, $pdf);								
				$Place_Y = $Place_Y + $Entity1_Rowcat_height + 0.5;
				$pdf->setCellPaddings(0, 0, 0, 0);
			}
			$pdf->SetFont('freesans', ' ', 8, '', false); // Normal Font	
			
			$pdf->SetX($Main_X);
			$pdf->SetY($Place_Y);
			
			$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
			$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
			
			$pdf->SetFillColor(221, 221, 255);
        	$pdf->SetTextColor(0);
			$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));		
			if(isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL && !empty($entity2Ids))
			{
				if($mode=='active')
				{
					$count_val=$row_active_total[$row];
				}
				elseif($mode=='total')
				{
					$count_val=$row_count_total[$row];
				}
				elseif($mode=='active_owner_sponsored')
				{
					$count_val=$row_active_owner_sponsored_total[$row];
				}
				else
				{
					$count_val=$row_indlead_total[$row];
				}
				$pdfContent = '<a style="color:#000000; text-decoration:none;" href="'. $CommonLinkForAll .'e1=' . $entity1Ids[$row] . $link_part . '" target="_blank" title="'. $raltTitle .'">'.trim(formatBrandName($rowsDisplayName[$row], 'product')).$rowsCompanyName[$row].'</a>'.((trim($rowsTagName[$row]) != '') ? ' <font style="color:#120f3c;">['.$rowsTagName[$row].']</font>':'');
				
				$Place_X = $pdf->GetX();
				$Place_Y = $pdf->GetY();
				
				$pdf->setCellPaddings(0.5, 0, 0, 0);
				
				$pdf->MultiCell($entity1_Col_Width, $entity1_row_height, $pdfContent, $border=0, $align='L', $fill=1, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
				
				$pdf->setCellPaddings(0, 0, 0, 0);
			}
			else
			{
				$pdf->SetFillColor(221, 221, 255);
        		$pdf->SetTextColor(0);
				
				$Place_X = $pdf->GetX();
				$Place_Y = $pdf->GetY();
				
				$pdf->MultiCell($entity1_Col_Width, $entity1_row_height, ' ', $border=0, $align='C', $fill=1, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
			}
			$Place_X = $Place_X + $entity1_Col_Width + 0.5;
			
			
			foreach($columns as $col => $cid)
			{
				if(isset($total_fld) && $total_fld == "1")
				$ln=0;
				else if($dtt && $second_last_num == $col && $total_fld != "1")
				$ln=1;
				else if($max_column['num'] == $col && $total_fld != "1")
				$ln=1;
				else $ln=0;
					
				$pdf->getCellPaddings();
				$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(220,220,220)));
				$pdf->SetFillColor(245,245,245);
				
				if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL)
				{
					
					$pdf->getCellPaddings();
					$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(220,220,220)));
					$pdf->SetFillColor(220,220,220);
				
					if($mode=='active')
					{
						$count_val=$data_matrix[$rid][$cid]['active'];
						$count_val_prev=$data_matrix[$rid][$cid]['active_prev'];
					}
					elseif($mode=='total')
					{
						$count_val=$data_matrix[$rid][$cid]['total'];
						$count_val_prev=$data_matrix[$rid][$cid]['total_prev'];
					}
					elseif($mode=='active_owner_sponsored')
					{
						$count_val=$data_matrix[$rid][$cid]['active_owner_sponsored'];
						$count_val_prev=$data_matrix[$rid][$cid]['active_owner_sponsored_prev'];
					}
					else
					{
						$count_val=$data_matrix[$rid][$cid]['indlead'];
						$count_val_prev=$data_matrix[$rid][$cid]['indlead_prev'];
					}
					
					
					//pixels = point * 96 / 72
					$entity2_Col_Width_px = $entity2_Col_Width * 96 / 72;
					$height_px = $height * 96 / 72;
					
					$pdfContent ='';
					
					$annotation_text = '';
					if($data_matrix[$rid][$cid]['highest_phase_lastchanged_value']==1)
					$annotation_text .= "Highest Phase updated from: Phase ".$data_matrix[$rid][$cid]['highest_phase_prev']."\n";
					if($data_matrix[$rid][$cid]['bomb_explain'] != NULL && trim($data_matrix[$rid][$cid]['bomb_explain']) != '' && ($data_matrix[$rid][$cid]['bomb']['value'] == 'small' || $data_matrix[$rid][$cid]['bomb']['value'] == 'large')) 
					$annotation_text .= "Bomb details: ".$data_matrix[$rid][$cid]['bomb_explain']."\n";
					if($data_matrix[$rid][$cid]['filing'] != NULL && trim($data_matrix[$rid][$cid]['filing']) != '')
					$annotation_text .= "Filing details: ".$data_matrix[$rid][$cid]['filing']."\n";
					if($data_matrix[$rid][$cid]['phase_explain'] != NULL && trim($data_matrix[$rid][$cid]['phase_explain']) != '')
					$annotation_text .= "Phase explanation: ".$data_matrix[$rid][$cid]['phase_explain']."\n";
					
					
					$annotation_text2 = '';
					
					$Status_New_Trials_Flg=0;
					$Status_New_Trials = '';
					
					/*if($data_matrix[$rid][$cid]['new_trials'] > 0)
					{
						$Status_New_Trials_Flg=1;
						$Status_New_Trials = "New trials: ". $data_matrix[$rid][$cid]['new_trials'] ."\n";
					}*/
					
					if($Status_New_Trials_Flg==1)
					$annotation_text2 = $Status_New_Trials;
					
					$Status_Total_Flg=0;
					$Status_Total = "Status changes to:\n";
					
					$allTrialsStatusArray = array('not_yet_recruiting', 'recruiting', 'enrolling_by_invitation', 'active_not_recruiting', 'completed', 'suspended', 'terminated', 'withdrawn', 'available', 'no_longer_available', 'approved_for_marketing', 'no_longer_recruiting', 'withheld', 'temporarily_not_available', 'ongoing', 'not_authorized', 'prohibited');
			
					foreach($allTrialsStatusArray as $currentStatus)
					{
						if($data_matrix[$rid][$cid][$currentStatus] > 0)
						{
							$Status_Total_Flg=1;
							$Status_Total .= " \"".ucfirst(str_replace('_',' ',$currentStatus)) ."\": ". $data_matrix[$rid][$cid][$currentStatus] ."\n";
						}
					}
					
					
					if($Status_Total_Flg==1 && $mode=='total')
						$annotation_text2 .= $Status_Total;
					else
						$Status_Total_Flg=0;
					
					
					$Status_Active_Flg=0;
					$Status_Active = "Status changes to:\n";
					
					foreach($allTrialsStatusArray as $currentStatus)
					{
						if($data_matrix[$rid][$cid][$currentStatus.'_active'] > 0)
						{
							$Status_Active_Flg=1;
							$Status_Active .= " \"".ucfirst(str_replace('_',' ',$currentStatus)) ."\": ". $data_matrix[$rid][$cid][$currentStatus.'_active'] ."\n";
						}
					}

					if($Status_Active_Flg==1 && $mode=='active')
						$annotation_text2 .= $Status_Active;
					else
						$Status_Active_Flg=0;
					
					
					$Status_Indlead_Flg=0;
					$Status_Indlead = "Status changes to:\n";
					
					foreach($allTrialsStatusArray as $currentStatus)
					{
						if($data_matrix[$rid][$cid][$currentStatus.'_active_indlead'] > 0)
						{
							$Status_Indlead_Flg=1;
							$Status_Indlead .= " \"".ucfirst(str_replace('_',' ',$currentStatus))."\": ". $data_matrix[$rid][$cid][$currentStatus.'_active_indlead']."\n";
						}
					}

					if($Status_Indlead_Flg==1 && $mode=='indlead')
						$annotation_text2 .= $Status_Indlead;
					else
						$Status_Indlead_Flg=0;
						
					
					$Status_Active_Owner_Sponsored_Flg=0;
					$Status_Active_Owner_Sponsored = "Status changes to:\n";
					
					foreach($allTrialsStatusArray as $currentStatus)
					{
						if($data_matrix[$rid][$cid][$currentStatus.'_active_owner_sponsored'] > 0)
						{
							$Status_Active_Owner_Sponsored_Flg=1;
							$Status_Active_Owner_Sponsored .= " \"".ucfirst(str_replace('_',' ',$currentStatus))."\": ". $data_matrix[$rid][$cid][$currentStatus.'_active_owner_sponsored']."\n";
						}
					}

					if($Status_Active_Owner_Sponsored_Flg==1 && $mode=='active_owner_sponsored')
						$annotation_text2 .= $Status_Active_Owner_Sponsored;
					else
						$Status_Active_Owner_Sponsored_Flg=0;	
					
					if($data_matrix[$rid][$cid]['total'] != 0 && ($Status_New_Trials_Flg==1 || $Status_Total_Flg==1 || $Status_Active_Flg || $Status_Indlead_Flg || $Status_Active_Owner_Sponsored_Flg) && (date('Y-m-d H:i:s', strtotime($end_range, $now)) == date('Y-m-d H:i:s', strtotime('-1 Month', $now))))
					$annotation_text = $annotation_text.$annotation_text2;
					
					$annotation_text = htmlspecialchars_decode(strip_tags($annotation_text));	///Strip HTML tags then Convert special HTML entities back to characters like &amp; to &

					if($entity1_row_height == $Line_Height)	$entity1_row_height = $entity1_row_height + 0.6; /// For adjustment as when height is min, html causes issue
					
					$pdfContent .= '<div align="left" style="vertical-align:middle; float:none;">';
					$extra_space = $entity1_row_height - $Line_Height + 0.4;
					if($data_matrix[$rid][$cid]['update_flag'] == 1)
					$extra_space = $extra_space - 0.6;
					//$pdfContent .= '<br style="line-height:'.((($extra_space * 72 / 96)/2)+0.8).'px;" />';
					
					if(trim($annotation_text) != '')
					{
						$pdf->Annotation($Place_X, $Place_Y, ($Width_matrix[$col]['width']*3/4), ($entity1_row_height*4/5), $annotation_text, array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Details', 'Subj' => 'Information', 'C' => array()));	
					}
					
					if($data_matrix[$rid][$cid]['update_flag'] == 1)
					{ 
						$data_matrix[$rid][$cid]['bordercolor_code']='#FF0000';
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,0,0)));
						$pdf->SetFillColor(255,0,0);
						$pdf->MultiCell($Width_matrix[$col]['width'], $entity1_row_height, '', $border, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$entity1_row_height, 'M');
					}
					
					$Aq_L = 0;
					$Ex_L = $Wrd_L = 0;
					if($count_val != '' && $count_val != NULL && $data_matrix[$rid][$cid]['total'] != 0)
					{
						$Wrd_L = $pdf->GetStringWidth($count_val, 'freesans', '', 8) + 0.2;
						$Aq_L = $Aq_L + $Wrd_L;
					}
					if($data_matrix[$rid][$cid]['bomb']['value'] == 'small' || $data_matrix[$rid][$cid]['bomb']['value'] == 'large')
					$Aq_L = $Aq_L + 3.1 + 0.2;
					if($data_matrix[$rid][$cid]['filing'] != NULL && $data_matrix[$rid][$cid]['filing'] != '')
					$Aq_L = $Aq_L + 3.1;
					
					$Av_L = $Width_matrix[$col]['width'];
					
					$Ex_L = ($Av_L - $Aq_L)/2;
					
					if($data_matrix[$rid][$cid]['total'] != 0)
					$pdfContent .= '<a href="'. $CommonLinkForAll .'e1=' . $entity1Ids[$row] . '&e2=' . $entity2Ids[$col]. $link_part . '" target="_blank" title="'. $title .'" ><font style="color:#000000;" >'.$count_val.'</font></a>';
					
					if($data_matrix[$rid][$cid]['bomb']['value'] == 'small' || $data_matrix[$rid][$cid]['bomb']['value'] == 'large')
					$bomb_PR = 1;
					else
					$bomb_PR = 0;
					
					if($data_matrix[$rid][$cid]['filing'] != NULL && $data_matrix[$rid][$cid]['filing'] != '')
					$fill_PR = 1;
					else
					$fill_PR = 0;
					
					if($bomb_PR)
					{
						$bomb_x=($Place_X + $Ex_L + $Wrd_L);
						if($data_matrix[$rid][$cid]['update_flag'] == 1)
						$bomb_x = $bomb_x + 0.3;
					}
					
					if($fill_PR)
					{
						if($bomb_PR)
							$fill_x=($bomb_x + 3.1 + 0.2);
						else
							$fill_x=($Place_X + $Ex_L + $Wrd_L);
							
						if($data_matrix[$rid][$cid]['update_flag'] == 1)
						$fill_x = $fill_x + 0.3;
					}
					
					$y=($Place_Y+($entity1_row_height/2)-(3.1/2));
					/////// End of Space and co-ordinates pixel calculation
					
					if($data_matrix[$rid][$cid]['update_flag'] == 1)
					$Ex_L = $Ex_L - 0.3;
					
					$pdf->setCellPaddings($Ex_L, ($extra_space/2), 0, 0);
					
					if($data_matrix[$rid][$cid]['bomb']['value'] == 'small' || $data_matrix[$rid][$cid]['bomb']['value'] == 'large')
					{
						$pdf->Image('images/'.$data_matrix[$rid][$cid]['bomb']['src'], $bomb_x, $y, 3.1, 3.1, '', '', '', false, 300, '', false, false, 0, false, false, false);
					}
						
					if($data_matrix[$rid][$cid]['filing'] != NULL && $data_matrix[$rid][$cid]['filing'] != '')
					{
						$pdf->Image($data_matrix[$rid][$cid]['filing_image'], $fill_x, $y, 3.1, 3.1, '', '', '', false, 300, '', false, false, 0, false, false, false);
					}
						
					$pdfContent .= '</div>';
					
					if($data_matrix[$rid][$cid]['color_code']=='BFBFBF')
					{
						$pdf->SetFillColor(191,191,191);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(191,191,191)));
					}
					else if($data_matrix[$rid][$cid]['color_code']=='00CCFF')
					{
						$pdf->SetFillColor(0,204,255);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,204,255)));
					}
					else if($data_matrix[$rid][$cid]['color_code']=='99CC00')
					{
						$pdf->SetFillColor(153,204,0);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(153,204,0)));
					}
					else if($data_matrix[$rid][$cid]['color_code']=='FFFF00')
					{
						$pdf->SetFillColor(255,255,0);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,0)));
					}
					else if($data_matrix[$rid][$cid]['color_code']=='FF9900')
					{
						$pdf->SetFillColor(255,153,0);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,153,0)));
					}
					else if($data_matrix[$rid][$cid]['color_code']='FF0000')
					{
						$pdf->SetFillColor(255,0,0);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,0,0)));	
					}
					
					if($data_matrix[$rid][$cid]['total'] == 0)
					{
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(230,230,230)));
						$pdf->SetFillColor(230,230,230);
					}
					
					if($data_matrix[$rid][$cid]['update_flag'] == 1)
					{ 
						$data_matrix[$rid][$cid]['bordercolor_code']='#FFFFFF';
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,255)));
						//$pdf->SetFillColor(255,255,255);
					}
					
					if($data_matrix[$rid][$cid]['update_flag'] == 1)
					{
						$pdf->MultiCell($Width_matrix[$col]['width']-0.6, $entity1_row_height-0.6, $pdfContent, $border, $align='L', $fill=1, $ln, $Place_X+0.3, $Place_Y+0.3, $reseth=false, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$entity1_row_height-0.6, 'M');
					}
					else
					{
						$pdf->MultiCell($Width_matrix[$col]['width'], $entity1_row_height, $pdfContent, $border, $align='L', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$entity1_row_height, 'M');
					}
					
					$pdf->setCellPaddings(0, 0, 0, 0);
				}
				else
				{
					$pdfContent = '<div align="center" style="vertical-align:middle; float:none;">&nbsp;</div>';
					if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL)
					{
						if($data_matrix[$rid][$cid]['phase4_override'])
						{
							$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,0,0)));
							$pdf->SetFillColor(255,0,0);
						}
						else if($data_matrix[$rid][$cid]['preclinical'])
						{
							$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(174,211,220)));
							$pdf->SetFillColor(174,211,220);
						}
						else
						{
							$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(230,230,230)));
							$pdf->SetFillColor(230,230,230);
						}
					}
					else
					{
						$pdf->SetFillColor(221, 221, 255);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(221, 221, 255)));
					}
					$pdf->MultiCell($Width_matrix[$col]['width'], $entity1_row_height, $pdfContent, $border, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$entity1_row_height);
				}
				$Place_X = $Place_X + $Width_matrix[$col]['width'] + 0.5;
			}//column foreach ends
			//if total checkbox is selected
			if(isset($total_fld) && $total_fld == "1")
			{
				$pdf->SetFillColor(221, 221, 255);
        		$pdf->SetTextColor(0);
				$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(221, 221, 255)));	
				$pdf->MultiCell($Total_Col_width, $entity1_row_height, ' ', $border, $align='C', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$entity1_row_height);
				$Place_X = $Place_X + $Total_Col_width + 0.5;

			}
			$Place_Y = $Place_Y + $entity1_row_height + 0.5;
			$pdf->SetX($Main_X);
			$pdf->SetY($Place_Y);
		}//Row Foreach ends
		
		$startY = $pdf->GetY();
		$helpTabRow_Height = 5;
		if ((($startY + $helpTabRow_Height + 3) + $dimensions['bm'] + $CustomFooterSize) > ($dimensions['hk']))
		{
			//this row will cause a page break, draw the bottom border on previous row and give this a top border
			$BorderStop_X = $pdf->GetX();
			$BorderStop_Y = $pdf->GetY();
			
			/// Create Border Around Heatmap before going to new page
			$pdf->SetFillColor(0, 0, 128);
			$border = array('mode' => 'ext', 'LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,0,128)));
			$pdf->MultiCell(($entity1_Col_Width + $All_Column_Width + 0.1), ($BorderStop_Y - $BorderStart_Y + 0.5), '', $border, $align='C', $fill=0, $ln=0, $BorderStart_X, $BorderStart_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=false, $maxh=($BorderStop_Y - $BorderStart_Y + 0.5), 'T');
			
			if($CustomFooter)
			PrintFooterPDFExport($pdf, $BorderStart_X, ($dimensions['hk'] - $dimensions['bm'] - $CustomFooterSize + 0.5), ($CustomFooterSize-1), ($entity1_Col_Width + $All_Column_Width + 0.1), $PageNum);
			
			//we could force a page break and rewrite grid headings here
			$pdf->AddPage();
			$PageNum = $PageNum + 1;
			
			$BorderStart_X = $pdf->GetX();
			$BorderStart_Y = $pdf->GetY();
			$pdf->Ln(0.5);
		}
		
		$dimensions = $pdf->getPageDimensions();
		$newMarginWidth = (($dimensions['wk'] - (162))/2);
		//$pdf->SetRightMargin($newMarginWidth);
		//$pdf->SetLeftMargin($newMarginWidth);
		$updateTime = ' ('.date('Y/m/d', strtotime($end_range, $now)).' - '.date('Y/m/d', strtotime($start_range, $now)).')';
		$helpTabImage_Header = array('Discontinued', 'Filing', 'Updated'.$updateTime);
		$helpTabImages_Src = array('new_lbomb.png', 'new_file.png', 'outline.png');
		$helpTabImages_Desc = array('Bomb', 'Filing', 'Red Border');
		
		///we can not set margins using available pdf function multiple times so set X-cordinate value expicitly
		//$Place_X = $pdf->GetX();
		$Place_X = $newMarginWidth;
		
		$Place_Y = $pdf->GetY() + 3;
		
		$pdf->MultiCell(12, $helpTabRow_Height, 'Phase: ', $border=0, $align='C', $fill=0, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$helpTabRow_Height, 'T');
		$Place_X = $Place_X + 11;
		//get search results
		$phases = array('N/A', 'Phase 0', 'Phase 1', 'Phase 2', 'Phase 3', 'Phase 4');
		$phasenums = array(); foreach($phases as $k => $p)  $phasenums[$k] = str_ireplace(array('phase',' '),'',$p);
		$phase_legend_nums = array('N/A', '0', '1', '2', '3', '4');
		//$p_colors = array('DDDDDD', 'BBDDDD', 'AADDEE', '99DDFF', 'DDFF99', 'FFFF00', 'FFCC00', 'FF9900', 'FF7711', 'FF4422');
		$p_colors = array('BFBFBF', '00CCFF', '99CC00', 'FFFF00', 'FF9900', 'FF0000');
		$phase_legend_colors = array(array(191,191,191), array(0,204,255), array(153,204,0), array(255,255,0), array(255,153,0), array(255,0,0));
	
		foreach($p_colors as $key => $color)
		{
			$pdf->SetFillColor($phase_legend_colors[$key][0], $phase_legend_colors[$key][1], $phase_legend_colors[$key][2]);
			$pdf->MultiCell(8, $helpTabRow_Height, $phasenums[$key], $border=0, $align='C', $fill=1, $ln=0, $Place_X, $Place_Y-0.5, $reseth=false, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$helpTabRow_Height, 'M');
			$Place_X = $Place_X + 8 +1;
		}
		$Place_X = $Place_X + 5;
		foreach($helpTabImage_Header as $key => $Header)
		{
			$pdf->Image('images/'.$helpTabImages_Src[$key], $Place_X , $Place_Y+0.2, 3, 3, '', '', '', false, 300, '', false, false, 0, false, false, false);
			$Place_X = $Place_X + 3;
			$current_StringLength = $pdf->GetStringWidth($helpTabImage_Header[$key], 'freesans', ' ', 8) + 3;
			
			$pdf->MultiCell($current_StringLength, $helpTabRow_Height, $helpTabImage_Header[$key], $border=0, $align='C', $fill=0, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=$helpTabRow_Height, 'T');
			$Place_X = $Place_X + $current_StringLength + 3;
		}
		$BorderStop_X = $pdf->GetX();
		$BorderStop_Y = $pdf->GetY();
		
		
		/// Create Border Around Heatmap
		$pdf->SetFillColor(0, 0, 128);
		$border = array('mode' => 'ext', 'LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,0,128)));
		$pdf->MultiCell(($entity1_Col_Width + $All_Column_Width + 0.1), ($BorderStop_Y - $BorderStart_Y + $helpTabRow_Height + 0.5), '', $border, $align='C', $fill=0, $ln=0, $BorderStart_X, $BorderStart_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=false, $maxh=($BorderStop_Y - $BorderStart_Y + $helpTabRow_Height + 0.5), 'T');
		
		if($CustomFooter)
		PrintFooterPDFExport($pdf, $BorderStart_X, ($dimensions['hk'] - $dimensions['bm'] - $CustomFooterSize + 0.5), ($CustomFooterSize-1), ($entity1_Col_Width + $All_Column_Width + 0.1), $PageNum);
		
		ob_end_clean();
		//Close and output PDF document
		$pdf->Output(''. substr($Report_Name,0,20) .'_Heatmap_'. date("Y-m-d_H.i.s") .'.pdf', 'D');
	}//Pdf Functions Ends
	
		
	if($_POST['dwformat']=='exceldown' || isset($_GET['excel_x']))
	{
	  	$name = htmlspecialchars(strlen($name)>0?$name:('report '.$id.''));
		
		// Create excel file object
		$objPHPExcel = new PHPExcel();
	
		// Set properties
		$objPHPExcel->getProperties()->setCreator(SITE_NAME);
		$objPHPExcel->getProperties()->setLastModifiedBy(SITE_NAME);
		$objPHPExcel->getProperties()->setTitle(substr($Report_Name,0,20).' Heatmap');
		$objPHPExcel->getProperties()->setSubject(substr($Report_Name,0,20).' Heatmap');
		$objPHPExcel->getProperties()->setDescription(substr($Report_Name,0,20).' Heatmap');
		
		$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(8);
		$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('Verdana'); 
	
		// Build sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle(substr(str_replace('/',' ',stripslashes($Report_Name)),0,20).' Heatmap');
		//$objPHPExcel->getActiveSheet()->getStyle('A1:AA2000')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(36);
		
		$Excel_HMCounter = 0;
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$Excel_HMCounter, 'Report name:');
		$objPHPExcel->getActiveSheet()->getStyle('B' . $Excel_HMCounter)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => false));
		$objPHPExcel->getActiveSheet()->mergeCells('B' . $Excel_HMCounter . ':M' . $Excel_HMCounter);
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $Excel_HMCounter, $Report_Name.' Heatmap')->getStyle('B1')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$Excel_HMCounter, 'Display Mode:');
		$objPHPExcel->getActiveSheet()->getStyle('B' . $Excel_HMCounter)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => false));
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $Excel_HMCounter, $tooltip);
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$Excel_HMCounter, '');
		//freezepane
		//$entity2_Category_Presence decides over the position of freeze pane
		if($entity2_Category_Presence){
			$objPHPExcel->getActiveSheet()->freezePane('B6');
		}else{
			$objPHPExcel->getActiveSheet()->freezePane('B5');
		}
		
		
		if($entity2_Category_Presence)
		{
			$Excel_HMCounter++;
			foreach($columns as $col => $val)
			{
				if($ColumnsSpan[$col] > 0)
				{
					$from = num2char($col);
					$to = getColspanforExcelExport($from, $ColumnsSpan[$col]);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
					if($columnsCategoryName[$col] != 'Undefined')
					{
						$black_font['font']['color']['rgb'] = '000000';
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray($black_font);
					}
					else
					{
						$white_font['font']['color']['rgb'] = 'FFFFFF';
						$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray($white_font);
					}
					$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getAlignment()->applyFromArray(
      										array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      												'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     												'rotation'   => 0,
      												'wrap'       => false));
					$objPHPExcel->getActiveSheet()->setCellValue($from . $Excel_HMCounter, $columnsCategoryName[$col]);
					
					$styleThinBlackAreaCatBorderOutline = array(
						'borders' => array(
						'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
						'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
						'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
										),
					);	
					
					if($columnsCategoryName[$col] != 'Undefined')
					{
						$cellP = $from;
						for($i = 0; $i < $ColumnsSpan[$col]; $i++)
						{
							$objPHPExcel->getActiveSheet()->getStyle($cellP . $Excel_HMCounter)->applyFromArray($styleThinBlackAreaCatBorderOutline); 
							$cellP++;
						}
					}
					
				}
			}
		}
		
		
		$Excel_HMCounter++;
		foreach($columns as $col => $val)
		{
			$cell= num2char($col).$Excel_HMCounter;
					
			$styleThinBlackAreaBorderOutline = array(
				'borders' => array(
				'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
				'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
									),
			);	
				
			$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleThinBlackAreaBorderOutline); 
				
			if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
			{
				if($mode=='active')
				{
					$count_val=' ('. $col_active_total[$col] .')';
				}
				elseif($mode=='total')
				{
					$count_val=' ('. $col_count_total[$col] .')';
				}
				elseif($mode=='active_owner_sponsored')
				{
					$count_val=' ('. $col_active_owner_sponsored_total[$col] .')';
				}
				else
				{
					$count_val=' ('. $col_indlead_total[$col].')';
				}
				
				//TODO
				$val = $columnsDisplayName[$col].$columnsCompanyName[$col].((trim($columnsTagName[$col]) != '') ? ' ['.$columnsTagName[$col].']':'');
				$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
				$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
								
				$black_font['font']['color']['rgb'] = '000000';
				$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($black_font);
					
				$objPHPExcel->getActiveSheet()->setCellValue($cell, $val);
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?e2=' . $entity2Ids[$col].$link_part);
				
				if($cdesc)
				{
					$objPHPExcel->getActiveSheet()->getComment($cell)->setAuthor('Description:');
					$objCommentRichText = $objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun('Description:');
					$objCommentRichText->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun("\r\n");
					$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun($cdesc);					
				}
				
 			    $objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip);
				$objPHPExcel->getActiveSheet()->getColumnDimension(num2char($col))->setWidth(18);
				
				$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
			}
		}
		
		if(isset($total_fld) && $total_fld == "1")
		{
			$cell = num2char($col+1);
			$objPHPExcel->getActiveSheet()->getColumnDimension($cell)->setWidth(18);
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
		}
		
		
		foreach($rows as $row => $rid)
		{
			$cat = (isset($rowsCategoryName[$row]) && $rowsCategoryName[$row] != '')? $rowsCategoryName[$row]:'Undefined';
	
			if($rows_Span[$row] > 0 && $cat != 'Undefined')
			{
				$Excel_HMCounter++;
				$from = 'A';
				$to = getColspanforExcelExport($from, ((count($columns)+1)+(($total_fld)? 1:0)));
				$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
				$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
				$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getFill()->getStartColor()->setRGB('A2FF97');
				$objPHPExcel->getActiveSheet()->setCellValue($from . $Excel_HMCounter, $cat);
				$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
				if($dtt)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?e1=' . implode(',', $rowsCategoryEntityIds1[$cat]) . '&e2=' . $last_entity2 . $link_part);
				}
				
				$styleThinBlackAreaCatBorderOutline = array(
					'borders' => array(
					'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
					'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
										),
				);	
				
				$cellP = $from;
				for($i = 0; $i < ((count($columns)+1)+(($total_fld)? 1:0)); $i++)
				{
					$objPHPExcel->getActiveSheet()->getStyle($cellP . $Excel_HMCounter)->applyFromArray($styleThinBlackAreaCatBorderOutline); 
					$cellP++;
				}
			
			}
			
			$Excel_HMCounter++;
			
			$cell='A'.($Excel_HMCounter);
			
			$styleThinBlackProductBorderOutline = array(
				'borders' => array(
				'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
				'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
				'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
									),
			);	
			
			$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleThinBlackProductBorderOutline); 			    
				
				
			if(isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL && !empty($entity2Ids))
			{
				
				if($mode=='active')
				{
					$count_val=' ('. $row_active_total[$row] .')';
				}
				elseif($mode=='total')
				{
					$count_val=' ('. $row_count_total[$row] .')';
				}
				elseif($mode=='active_owner_sponsored')
				{
					$count_val=' ('. $row_active_owner_sponsored_total[$row] .')';
				}
				else
				{
					$count_val=' ('.$row_indlead_total[$row].')';
				}
				
				//TODO
				$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
				$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
				
				$objPHPExcel->getActiveSheet()->setCellValue($cell, $rowsDisplayName[$row].$rowsCompanyName[$row].((trim($rowsTagName[$row]) != '') ? ' ['.$rowsTagName[$row].']':''));
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?e1=' . $entity1Ids[$row] .$link_part); 
 			    $objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip);
 			    
 			    if($rdesc)
 			    {
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->setAuthor('Description:');
 			    	$objCommentRichText = $objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun('Description:');
 			    	$objCommentRichText->getFont()->setBold(true);
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun("\r\n");
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun($rdesc);
 			    }
				
				/*$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));*/
			}
			
			foreach($columns as $col => $cid)
			{
				$cell = num2char($col) . ($Excel_HMCounter);
				
				$styleThinRedBorderOutline = array(
					'borders' => array(
					'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000'),),
					'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000'),),
					'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000'),),
					'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000'),),
										),
				);	
					
				$styleThinBlackLeftBorderOutline = array(
					'borders' => array(
					'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
										),
				);
					
				$styleThinBlackTopBorderOutline = array(
					'borders' => array(
					'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
										),
				);
					
				$styleThinBlackRightBorderOutline = array(
					'borders' => array(
					'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
										),
				);
					
				$styleThinBlackBottomBorderOutline = array(
					'borders' => array(
					'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => '000000'),),
										),
				);
							
					
				if($data_matrix[$rid][$cid]['update_flag'] == 1)
				{
					$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleThinRedBorderOutline);
				}
				else
				{
					//Apply Left Border
					if(($col > 1  && $data_matrix[$rows[$row]][$columns[$col-1]]['update_flag'] != 1) || ($col == 1))
					$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleThinBlackLeftBorderOutline);
					
					//Apply Right Border
					if(($col >= 1  && $col < count($columns) && $data_matrix[$rows[$row]][$columns[$col+1]]['update_flag'] != 1) || ($col == count($columns)))
					$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleThinBlackRightBorderOutline);
					
					//Apply Top Border
					if(($row > 1  && ($data_matrix[$rows[$row-1]][$columns[$col]]['update_flag'] != 1 || (isset($rowsCategoryName[$row]) && $rowsCategoryName[$row] != '' && $rowsCategoryName[$row] != 'Undefined' && $rows_Span[$row] > 0))) || ($row == 1))
					$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleThinBlackTopBorderOutline);
					
					//Apply Bottom Border
					if(($row >= 1  && $row < count($rows) && ($data_matrix[$rows[$row+1]][$columns[$col]]['update_flag'] != 1 || (isset($rowsCategoryName[$row+1]) && $rowsCategoryName[$row+1] != '' && $rowsCategoryName[$row] != 'Undefined' && $rows_Span[$row+1] > 0))) || ($row == count($rows)))
					$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleThinBlackBottomBorderOutline);
						
				}
					
				if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL)
				{
					if($mode=='active')
					{
						$count_val=$data_matrix[$rid][$cid]['active'];
						$count_val_prev=$data_matrix[$rid][$cid]['active_prev'];
					}
					elseif($mode=='total')
					{
						$count_val=$data_matrix[$rid][$cid]['total'];
						$count_val_prev=$data_matrix[$rid][$cid]['total_prev'];
					}
					elseif($mode=='active_owner_sponsored')
					{
						$count_val=$data_matrix[$rid][$cid]['active_owner_sponsored'];
						$count_val_prev=$data_matrix[$rid][$cid]['active_owner_sponsored_prev'];
					}
					else
					{
						$count_val=$data_matrix[$rid][$cid]['indlead'];
						$count_val_prev=$data_matrix[$rid][$cid]['indlead_prev'];
					}
										
					$red_font['font']['color']['rgb'] = '000000';
					$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($red_font);
					
					if($data_matrix[$rid][$cid]['total'] != 0)	///In case of zero trials dont disply count
					$objPHPExcel->getActiveSheet()->setCellValue($cell, $count_val);
					
					//we require to set hyperlink to to zero trials cell as well cause without hyperlink we cant display mouseover text 
					// and it will give excel error
					$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?e1=' . $entity1Ids[$row] . '&e2=' . $entity2Ids[$col].$link_part); 
 			    	$annotation_text = '';
					if($data_matrix[$rid][$cid]['bomb_explain'] != NULL && trim($data_matrix[$rid][$cid]['bomb_explain']) != '' && ($data_matrix[$rid][$cid]['bomb']['value'] == 'small' || $data_matrix[$rid][$cid]['bomb']['value'] == 'large')) 
					$annotation_text .= "Bomb details: ".$data_matrix[$rid][$cid]['bomb_explain']."\n";
					if($data_matrix[$rid][$cid]['filing'] != NULL && trim($data_matrix[$rid][$cid]['filing']) != '')
					$annotation_text .= "Filing details: ".$data_matrix[$rid][$cid]['filing']."\n";
					if($data_matrix[$rid][$cid]['phase_explain'] != NULL && trim($data_matrix[$rid][$cid]['phase_explain']) != '')
					$annotation_text .= "Phase explanation: ".$data_matrix[$rid][$cid]['phase_explain']."\n";
					if($data_matrix[$rid][$cid]['highest_phase_lastchanged_value']==1)
					$annotation_text .= "Highest Phase updated from: Phase ".$data_matrix[$rid][$cid]['highest_phase_prev']."\n";
					
					
					$annotation_text2 = '';
					
					$Status_New_Trials_Flg=0;
					$Status_New_Trials = '';
					
					/*if($data_matrix[$rid][$cid]['new_trials'] > 0)
					{
						$Status_New_Trials_Flg=1;
						$Status_New_Trials = "New trials: ". $data_matrix[$rid][$cid]['new_trials'] ."\n";
					}*/
					
					if($Status_New_Trials_Flg==1)
					$annotation_text2 = $Status_New_Trials;
					
					$Status_Total_Flg=0;
					$Status_Total = "Status changes to:\n";
					
					$allTrialsStatusArray = array('not_yet_recruiting', 'recruiting', 'enrolling_by_invitation', 'active_not_recruiting', 'completed', 'suspended', 'terminated', 'withdrawn', 'available', 'no_longer_available', 'approved_for_marketing', 'no_longer_recruiting', 'withheld', 'temporarily_not_available', 'ongoing', 'not_authorized', 'prohibited');
			
					foreach($allTrialsStatusArray as $currentStatus)
					{
						if($data_matrix[$rid][$cid][$currentStatus] > 0)
						{
							$Status_Total_Flg=1;
							$Status_Total .= " \"".ucfirst(str_replace('_',' ',$currentStatus)) ."\": ". $data_matrix[$rid][$cid][$currentStatus] ."\n";
						}
					}
					
					if($Status_Total_Flg==1 && $mode=='total')
						$annotation_text2 .= $Status_Total;
					else
						$Status_Total_Flg=0;
					
					
					$Status_Active_Flg=0;
					$Status_Active = "Status changes to:\n";
					
					foreach($allTrialsStatusArray as $currentStatus)
					{
						if($data_matrix[$rid][$cid][$currentStatus.'_active'] > 0)
						{
							$Status_Active_Flg=1;
							$Status_Active .= " \"".ucfirst(str_replace('_',' ',$currentStatus)) ."\": ". $data_matrix[$rid][$cid][$currentStatus.'_active'] ."\n";
						}
					}
					
					if($Status_Active_Flg==1 && $mode=='active')
						$annotation_text2 .= $Status_Active;
					else
						$Status_Active_Flg=0;
					
					
					$Status_Indlead_Flg=0;
					$Status_Indlead = "Status changes to:\n";
					
					foreach($allTrialsStatusArray as $currentStatus)
					{
						if($data_matrix[$rid][$cid][$currentStatus.'_active_indlead'] > 0)
						{
							$Status_Indlead_Flg=1;
							$Status_Indlead .= " \"".ucfirst(str_replace('_',' ',$currentStatus))."\": ". $data_matrix[$rid][$cid][$currentStatus.'_active_indlead']."\n";
						}
					}
					
					
					if($Status_Indlead_Flg==1 && $mode=='indlead')
						$annotation_text2 .= $Status_Indlead;
					else
						$Status_Indlead_Flg=0;
						
						
					$Status_Active_Owner_Sponsored_Flg=0;
					$Status_Active_Owner_Sponsored = "Status changes to:\n";
					
					foreach($allTrialsStatusArray as $currentStatus)
					{
						if($data_matrix[$rid][$cid][$currentStatus.'_active_owner_sponsored'] > 0)
						{
							$Status_Active_Owner_Sponsored_Flg=1;
							$Status_Active_Owner_Sponsored .= " \"".ucfirst(str_replace('_',' ',$currentStatus))."\": ". $data_matrix[$rid][$cid][$currentStatus.'_active_owner_sponsored']."\n";
						}
					}
					
					
					if($Status_Active_Owner_Sponsored_Flg==1 && $mode=='active_owner_sponsored')
						$annotation_text2 .= $Status_Active_Owner_Sponsored;
					else
						$Status_Active_Owner_Sponsored_Flg=0;	
					
					if($data_matrix[$rid][$cid]['total'] != 0 && ($Status_New_Trials_Flg==1 || $Status_Total_Flg==1 || $Status_Active_Flg || $Status_Indlead_Flg || $Status_Active_Owner_Sponsored_Flg) && (date('Y-m-d H:i:s', strtotime($end_range, $now)) == date('Y-m-d H:i:s', strtotime('-1 Month', $now))))
					$annotation_text = $annotation_text.$annotation_text2;
					
					$annotation_text = htmlspecialchars_decode(strip_tags($annotation_text));	///Strip HTML tags then Convert special HTML entities back to characters like &amp; to &
					
					$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip(substr($annotation_text,0,255) );
					$bomb_PR = 0;
					if($data_matrix[$rid][$cid]['exec_bomb']['src'] != '' && $data_matrix[$rid][$cid]['exec_bomb']['src'] != NULL && $data_matrix[$rid][$cid]['exec_bomb']['src'] !='new_square.png')
					{
						$objDrawing = new PHPExcel_Worksheet_Drawing();
						$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						if($data_matrix[$rid][$cid]['total'] == 0)
						$objDrawing->setOffsetX(60);
						else
						$objDrawing->setOffsetX(80);
						$objDrawing->setOffsetY(1);
						
						$img = $data_matrix[$rid][$cid]['exec_bomb']['src'];
						if($data_matrix[$rid][$cid]['total'] != 0)
						$img .= '_'.$data_matrix[$rid][$cid]['color_code'];
						
						$objDrawing->setPath('images/'.$img.'.png');
						$objDrawing->setHeight(12);
						$objDrawing->setWidth(12); 
						$objDrawing->setDescription($data_matrix[$rid][$cid]['bomb']['title']);
						$objDrawing->setCoordinates($cell);
						$bomb_PR = 1;
					}
					
					if($data_matrix[$rid][$cid]['filing'] != NULL && $data_matrix[$rid][$cid]['filing'] != '')
					{
						if($bomb_PR)
						{
							if($data_matrix[$rid][$cid]['total'] == 0) $ptr_x = 80; else $ptr_x = 100;
						}
						else
						{
							if($data_matrix[$rid][$cid]['total'] == 0) $ptr_x = 60; else $ptr_x = 80;
						}
						$objDrawing = new PHPExcel_Worksheet_Drawing();
						$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						$objDrawing->setOffsetX($ptr_x);
						$objDrawing->setOffsetY(1);
						
						$img = $data_matrix[$rid][$cid]['exec_filing_image'];
						if($data_matrix[$rid][$cid]['total'] != 0)
						$img .= '_'.$data_matrix[$rid][$cid]['color_code'];
						
						$objDrawing->setPath($img.'.png');
						$objDrawing->setHeight(12);
						$objDrawing->setWidth(12); 
						$objDrawing->setDescription("Filing Details");
						$objDrawing->setCoordinates($cell);
					}
					
					if($data_matrix[$rid][$cid]['total'] != 0)
					{
						$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
						$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->getStartColor()->setRGB($data_matrix[$rid][$cid]['color_code']);
					}
					$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
				}
				else
				{
					/////// To avoid entity1 name overflow on side column when, first columns is empty - putting 0 value with background color
					$blank_cell_bgSet = false;
					if($data_matrix[$rid][$cid]['phase4_override'])
					{
						$blank_cell_font['font']['color']['rgb'] = $data_matrix[$rid][$cid]['color_code'];
						$blank_cell_bgcolor = $data_matrix[$rid][$cid]['color_code'];
						$blank_cell_bgSet = true;
					}
					else if($data_matrix[$rid][$cid]['preclinical'])
					{
						$blank_cell_font['font']['color']['rgb'] = 'aed3dc';
						$blank_cell_bgcolor = 'aed3dc';
						$blank_cell_bgSet = true;
					}
					else if($col == 1)
					{
						$blank_cell_font['font']['color']['rgb'] = 'FFFFFF';
					}
					
					if($col == 1)
					{
						$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($blank_cell_font);
						$objPHPExcel->getActiveSheet()->setCellValue($cell, '0');
					}
					
					if($blank_cell_bgSet)
					{
						$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
						$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->getStartColor()->setRGB($blank_cell_bgcolor);
					}
					
				}
			}
		}
		
		if(isset($total_fld) && $total_fld == "1")
		{
			if($mode=='active')
			{
				$count_val=$active_total;
			}
			elseif($mode=='total')
			{
				$count_val=$count_total;
			}
			elseif($mode=='active_owner_sponsored')
			{
				$count_val=$active_owner_sponsored_total;
			}
			else
			{
				$count_val=$indlead_total;
			}
					
			$cell = num2char(count($columns)+1).(($entity2_Category_Presence) ? '5':'4');
			$objPHPExcel->getActiveSheet()->setCellValue($cell, $count_val);
			$black_font['font']['color']['rgb'] = '000000';
			$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($black_font);
			$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?e1=' . implode(',', $entity1Ids) . '&e2=' . implode(',', $entity2Ids).$link_part);
			$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip);
		}
		
		++$Excel_HMCounter;
		++$Excel_HMCounter;
		
		$helpTabImage_Header = array('Discontinued', 'Filing details', '  Red border (record updated)');
		$helpTabImages_Src = array('new_lbomb.png', 'new_file.png', 'outline.png');
		$helpTabImages_Desc = array('Bomb', 'Filing', 'Red Border');
		
		foreach($helpTabImage_Header as $key => $Header)
		{
			$objDrawing = new PHPExcel_Worksheet_Drawing();
			$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
			$objDrawing->setOffsetX(5);
			$objDrawing->setOffsetY(2);
			$objDrawing->setPath('images/'.$helpTabImages_Src[$key]);
			$objDrawing->setHeight(12);
			$objDrawing->setWidth(12); 
			$objDrawing->setDescription($helpTabImages_Desc[$key]);
			$objDrawing->setCoordinates('B' . ++$Excel_HMCounter);
			if($key == 2)
			{
				$objPHPExcel->getActiveSheet()->mergeCells('B'. $Excel_HMCounter. ':C'. $Excel_HMCounter);
				$objPHPExcel->getActiveSheet()->getStyle('B' . $Excel_HMCounter)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			}
			else
			$objPHPExcel->getActiveSheet()->getStyle('B' . $Excel_HMCounter)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$objPHPExcel->getActiveSheet()->SetCellValue('B' . $Excel_HMCounter, $helpTabImage_Header[$key]);
			
		}
		
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . ++$Excel_HMCounter, 'Phase:  ');
		$objPHPExcel->getActiveSheet()->getStyle('B' . $Excel_HMCounter)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$col = 'B';
		//get search results
		$phases = array('N/A', 'Phase 0', 'Phase 1', 'Phase 2', 'Phase 3', 'Phase 4');
		$phasenums = array(); foreach($phases as $k => $p)  $phasenums[$k] = str_ireplace(array('phase',' '),'',$p);
		$phase_legend_nums = array('N/A', '0', '1', '2', '3', '4');
		//$p_colors = array('DDDDDD', 'BBDDDD', 'AADDEE', '99DDFF', 'DDFF99', 'FFFF00', 'FFCC00', 'FF9900', 'FF7711', 'FF4422');
		$p_colors = array('BFBFBF', '00CCFF', '99CC00', 'FFFF00', 'FF9900', 'FF0000');
		$phase_legend_colors = array('BFBFBF', '00CCFF', '99CC00', 'FFFF00', 'FF9900', 'FF0000');
	
		foreach($p_colors as $key => $color)
		{
			$cell = ++$col . $Excel_HMCounter;
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->getStartColor()->setRGB($color);
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getCell($cell)->setValueExplicit($phasenums[$key], PHPExcel_Cell_DataType::TYPE_STRING);
		}
			
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			
		ob_end_clean(); 
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . substr($Report_Name,0,20) . '_Heatmap_' . date('Y-m-d_H.i.s') . '.xlsx"');
			
		header("Content-Transfer-Encoding: binary ");
		$objWriter->save('php://output');
		@flush();
	} //Excel Function Ends
}

function getColspanforExcelExport($cell, $inc)
{
	for($i = 1; $i < $inc; $i++)
	{
		$cell++;
	}
	return $cell;
}

function PrintEntity1CategoryforPDFExport($dtt, $rowsCategoryEntityIds1, $last_entity2, $link_part, $cat, $Entity1_Rowcat_width, $Entity1_Rowcat_height, $Place_X, $Place_Y, &$pdf)
{
	$pdfContent = '';
	if($dtt)
	{
		$pdfContent = '<a href="'. $CommonLinkForAll .'e1=' . implode(',', $rowsCategoryEntityIds1) . '&e2=' . $last_entity2 . $link_part .'" target="_blank" style="color:#000000; text-decoration:none;">';
	}
	if($cat != 'Undefined')
	{
		$pdfContent .=''.$cat.'';
	}
	if($dtt)
	$pdfContent .= '</a>';
			
	$pdf->SetFillColor(162, 255, 151);
	$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));	
	//	$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(162, 255, 151)));
	$pdf->setCellPaddings(0.5, 0, 0, 0);
	$pdf->MultiCell($Entity1_Rowcat_width, $Entity1_Rowcat_height, $pdfContent, $border=0, $align='L', $fill=1, $ln=1, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
	$pdf->setCellPaddings(0, 0, 0, 0);					
}

function getNumLinesPDFExport($entity1Name, $OtherPart, $entity1_Col_Width, $Bold_Line_Height, $Line_Height, &$pdf)
{
	$entity1_Col_Width = $entity1_Col_Width - 0.5;
	$line = '';
	$numberOfLines = 0;
	$flgBold = true;
	$bracketDetect = false;
	$Height = 0;
	$ExtraSpFlg = false;
	$AvlblWidth = $entity1_Col_Width;
	$Minus = $pdf->GetStringWidth(' ', 'freesans', ' ', 8);
	$Return = array();
	
	///Process entity1 Name
	$data = explode(' ', $entity1Name);
	for($m=0; $m< count($data); $m++)	//// for loop of data ---- first level
	{
		if(strpos(' '.$data[$m],'('))	$bracketDetect = true;
		if($bracketDetect && !$ExtraSpFlg) 
		{ 
			$AvlblWidth = $AvlblWidth - 0; 
			$ExtraSpFlg = true;
		}
		
		if($flgBold && !$bracketDetect)
		{
			$pdf->SetFont('freesansb', 'B', 8); // Bold Font
			$current_Width = $pdf->GetStringWidth($data[$m], 'freesansb', 'B', 8);
			$Minus = $pdf->GetStringWidth(' ', 'freesansb', ' ', 8);
		}
		else
		{
			$pdf->SetFont('freesans', ' ', 8, '', false); // Bold Font
			$current_Width = $pdf->GetStringWidth($data[$m], 'freesans', ' ', 8);
			$Minus = $pdf->GetStringWidth(' ', 'freesans', ' ', 8);
		}
		
		if($current_Width < $AvlblWidth)
		{
			$line .= $data[$m].' ';
			$AvlblWidth = $AvlblWidth - $current_Width - $Minus;
		}
		else
		{
			$numberOfLines++;
			$Height = $Height + (($flgBold)? $Bold_Line_Height : $Line_Height);
			if($bracketDetect)
			{
				$flgBold = false;
			}
			$line = $data[$m].' ';
			$AvlblWidth = $entity1_Col_Width - $current_Width - $Minus;
		}
		//print $data[$m].'---'.$current_Width.'----'.$AvlblWidth.'<br/>';
	}
	
	///Process Other Part
	$data = explode(' ', $OtherPart);
	for($m=0;$m< count($data); $m++)	//// for loop of data ---- first level
	{
		$pdf->SetFont('freesans', ' ', 8, '', false); // Bold Font
		if(!$ExtraSpFlg) { $AvlblWidth = $AvlblWidth - 0; $ExtraSpFlg = true;}
		$current_Width = $pdf->GetStringWidth((($m == count($data)-1) ? $data[$m] : $data[$m]), 'freesans', '', 8);
		if ($m != count($data)-1) $Minus = $pdf->GetStringWidth(' ', 'freesans', ' ', 8); else $Minus = 0;
			
		if($current_Width < $AvlblWidth)
		{
			$line .= $data[$m].' ';
			$AvlblWidth = $AvlblWidth - $current_Width - $Minus;
		}
		else
		{
			$numberOfLines++;
			$Height = $Height + (($flgBold)? $Bold_Line_Height : $Line_Height);
			$line = $data[$m].' ';
			$flgBold = 0;
			$AvlblWidth = $entity1_Col_Width - $current_Width - $Minus;
		}
	}
	if(trim($line) != '') {$Height = $Height + (($flgBold)? $Bold_Line_Height : $Line_Height); $numberOfLines++;}
	if($numberOfLines <= 1)
	{
		$Height = $Bold_Line_Height;
		$numberOfLines=1;
	}
	//print $Height.'<br/>';
	$Return[0] = $numberOfLines;
	$Return[1] = $Height;
	return $Return;
}

function PrintFooterPDFExport(&$pdf, $Place_X, $Place_Y, $Height, $Width, $PageNum)
{
	$BkPlace_X = $Place_X;
	$pdf->SetFillColor(0, 0, 128);
	$border = array('mode' => 'ext', 'TB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,0,0)));
	$pdf->MultiCell($Width, $Height, '', $border, $align='C', $fill=0, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Height, 'T');
	
	$Place_X = $Place_X + 3;
	$Place_Y = $Place_Y + 2;
	$pdf->Image('images/larvol_logo.gif', $Place_X , $Place_Y, 14, 3.2, '', '', '', false, 300, '', false, false, 0, false, false, false);
	
	$Place_X = $Place_X + 14 + 3;
	$Place_Y = $Place_Y - 0.2;
	$copyRight = '&copy; The Larvol Group, 2012';
	$current_StringLength = $pdf->GetStringWidth($copyRight, 'freesans', ' ', 8) + 3;
	$pdf->MultiCell($current_StringLength, 3, '<font style="color:#332b66; font-size:25px; font-style:italic; font-weight:normal;">'.$copyRight.'</font>', $border=0, $align='L', $fill=0, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Height, 'T');
	
	$Text = 'For internal use only';
	$current_StringLength = $pdf->GetStringWidth($Text, 'freesans', ' ', 8) + 3;
	$Place_X = $BkPlace_X + $Width - $current_StringLength - 3;
	$pdf->MultiCell($current_StringLength, 3, '<font style="color:#332b66; font-size:25px; font-style:italic; font-weight:normal;">'.$Text.'</font>', $border=0, $align='L', $fill=0, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Height, 'T');
	
	$current_StringLength = ceil($pdf->GetStringWidth($PageNum, 'freesans', ' ', 8));
	$Place_X = $BkPlace_X + ($Width/2) - $current_StringLength;
	$pdf->MultiCell($current_StringLength, 3, '<font style="color:#000000; font-size:25px; font-style:italic; font-weight:normal;">'.$PageNum.'</font>', $border=0, $align='L', $fill=0, $ln=0, $Place_X, $Place_Y, $reseth=true, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Height, 'T');
}


//process POST for editor
function postEd()
{
	global $db;
	global $now;
	if(!isset($_POST['id'])) return;
	$id = mysql_real_escape_string($_POST['id']);
	if(!is_numeric($id)) return;
	
	$_GET['id'] = $id;	//This is so the editor will load the report we are about to (maybe?) save
	
	// block any user from modifying other peoples private reports and block non-admins from modifying global reports
	$query = 'SELECT user,shared FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting user for heatmap report id');
	$res = mysql_fetch_assoc($res);
	if($res === false) return;	///Replaced "Continue" by "Return" cause continue was giving "Cannot break/continue 1 level" error when report deleted and continue should only be used to escape through loop not function
	if(count($res)==0){ die('Not found.'); }
	$repoUser = $res['user'];
	$shared = $res['shared'];
	if($repoUser !== NULL && $repoUser != $db->user->id && !$shared && $db->user->userlevel != 'root') return;

	// "Copy into new" is the exception for non-admins sending POSTdata about global reports
	if(isset($_POST['reportcopy']))
	{
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		$query = 'SELECT name, footnotes, description, category, total, dtt FROM rpt_masterhm WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query getting old data');
		$res = mysql_fetch_array($res);
		if($res === false) return; //not found

		$oldname = mysql_real_escape_string($res['name']);
		$footnotes = mysql_real_escape_string($res['footnotes']);
		$description = mysql_real_escape_string($res['description']);
		$category = mysql_real_escape_string($res['category']);
		$total = mysql_real_escape_string($res['total']);
		$dtt = mysql_real_escape_string($res['dtt']);
		$query = 'INSERT INTO rpt_masterhm SET name="Copy of ' . (strlen($oldname) ? $oldname : ('report '.$id)) . '",user='
				. $db->user->id . ',footnotes="' . $footnotes . '",description="' . $description . '"' . ',category="'.$category.'" , total="'.$total.'" , dtt="'.$dtt.'"';
				
		mysql_query($query) or die('Bad SQL Query saving name');
		$newid = mysql_insert_id();
		$tables = array('rpt_masterhm_headers');
		
		foreach($tables as $tab)
		{
			$query = 'SELECT * FROM ' . $tab . ' WHERE report=' . $id;
			$res = mysql_query($query) or die('Bad SQL query getting report info');
			while($orow = mysql_fetch_assoc($res))
			{
				$orow['report'] = $newid;
				foreach($orow as $key => $value)
				{
					if($value === NULL)
					{
						$value = 'NULL';
					}else{
						$value = mysql_real_escape_string($value);
						if(!is_numeric($value)) $value = '"' . $value . '"';
					}
					if($key != 'id') $orow['`'.$key.'`'] = $value;
					unset($orow[$key]);
				}
				$query = 'INSERT INTO ' . $tab . '(' . implode(',', array_keys($orow)) . ') VALUES(' . implode(',', $orow) . ')';
				mysql_query($query) or die('Bad SQL query copying data ' . $query . mysql_error());
			}
		}
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
		$_GET['id'] = $newid;
	}
	
	
	$maxrow = 0;
	$maxcolumn = 0;
	$types = array('row','column');
	
	if(($repoUser === NULL && $db->user->userlevel != 'user') || ($repoUser !== NULL && $repoUser == $db->user->id) || $db->user->userlevel == 'root') 	///Restriction on editing
	{
		
		foreach($types as $t)
		{
			if(isset($_POST['insert_'.$t]) && is_array($_POST['insert_'.$t]))
			{
				foreach($_POST['insert_'.$t] as $ins=>$stat)
				{
					//after all delete columns reorder columns
					$query = "SELECT num FROM `rpt_masterhm_headers` WHERE `report` = $id AND `type` = '" . $t . "' ORDER BY `num` DESC";
					$result = mysql_query($query);
					$cnt = 0;
					$cnt = mysql_num_rows($result);
					$i=1;
					$upd_flg = 0;
					$ins_PT = $ins + 1;
					while($row = mysql_fetch_assoc($result))
					{
						if($row['num'] >= $ins_PT)
						{
							$query = "UPDATE `rpt_masterhm_headers` set `num` = `num`+1 WHERE `report` = $id and `type` = '" . $t . "' AND `num` = ".$row['num'];
							mysql_query($query) or die ('Bad SQL Query updating columns with new values after insert row/cols operation.<br/>'.$query);
						}
					}
					$query = 'INSERT INTO rpt_masterhm_headers SET report=' . $id . ',type="' . $t . '",num=' . ($ins_PT);
					mysql_query($query) or die('Bad SQL Query adding ' . $t);
				}	
			} // End of IF
		} // End of Foreach
	}	// End of add column / row IF
	
	
	if(isset($_POST['reportsave']) || $_POST['reportsave_flg']==1)
	{
		$footnotes = mysql_real_escape_string($_POST['footnotes']);
		$description = mysql_real_escape_string($_POST['description']);
		
		if(isset($_POST['total']) && $_POST['total']==1)
		$total_col=1;
		else
		$total_col=0;
		
		if(isset($_POST['dtt']) && $_POST['dtt']==1)
		$dtt=1;
		else
		$dtt=0;
		
		$category = mysql_real_escape_string($_POST['reportcategory']);
		
		if(($repoUser === NULL && $db->user->userlevel != 'user') || ($repoUser !== NULL && $repoUser == $db->user->id) || $db->user->userlevel == 'root') 	///Restriction on report saving
		{
		
			$originDT_query = 'SELECT `name`, `user`, `footnotes`, `description`, `category`, `shared`, `total`, `dtt`, `display_name` FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
			$originDT=mysql_query($originDT_query) or die ('Bad SQL Query getting Original Master Header Table Information Before Updating.<br/>'.$query);
			$originDT = mysql_fetch_array($originDT);
			
			
			if(isset($_POST['own']) && $_POST['own'] == 'global')
			{
				$owner='null'; $shared=0;
			} 
			else if(isset($_POST['own']) && $_POST['own'] == 'shared')
			{
				$owner=$db->user->id; $shared=1;
			} 
			else if(isset($_POST['own']) && $_POST['own'] == 'mine')
			{
				$owner=$db->user->id; $shared=0;
			}
			else if(isset($_POST['own']) && $_POST['own'] == 'shared_other' && ($db->user->userlevel == 'root' || $db->user->userlevel == 'admin'))
			{
				$owner=trim($originDT['user']); $shared=1;
			} 
			else if(isset($_POST['own']) && $_POST['own'] == 'mine_other' && $db->user->userlevel == 'root')
			{
				$owner=trim($originDT['user']); $shared=0;
			}
			else
			{
				$owner=trim($originDT['user']); $shared=0;
			}
			
		
			$change_flag=0;
			
			$query = 'UPDATE rpt_masterhm SET';
			
			if(trim($_POST['reportname']) != trim($originDT['name']))
			{
				$query .= ' `name`="' . mysql_real_escape_string($_POST['reportname']) . '",';
				$change_flag=1;
			}
			
			if(trim($_POST['report_displayname']) != trim($originDT['display_name']))
			{
				$query .= ' `display_name`="' . mysql_real_escape_string($_POST['report_displayname']) . '",';
				$change_flag=1;
			}
			
			if(trim($owner) != trim($originDT['user']))
			{
				$query .= ' `user`=' . $owner . ',';
				$change_flag=1;
			}
			
			if(trim($footnotes) != trim($originDT['footnotes']))
			{
				$query .= ' `footnotes`="' . $footnotes . '",';
				$change_flag=1;
			}
			
			if(trim($description) != trim($originDT['description']))
			{
				$query .= ' `description`="' . $description . '",';
				$change_flag=1;
			}
			
			if(trim($category) != trim($originDT['category']))
			{
				$query .= ' `category`="' . $category . '",';
				$change_flag=1;
			}
			
			if(trim($shared) != trim($originDT['shared']))
			{
				$query .= ' `shared`="' . $shared . '",';
				$change_flag=1;
			}
			
			if(trim($total_col) != trim($originDT['total']))
			{
				$query .= ' `total`=' . $total_col . ',';
				$change_flag=1;
			}
			
			if(trim($dtt) != trim($originDT['dtt']))
			{
				$query .= ' `dtt`=' . $dtt . ',';
				$change_flag=1;
			}
			
			if($change_flag)
			{
				$query = substr($query, 0, -1); //strip last comma
				$query .= ' WHERE id=' . $id . ' LIMIT 1';
				mysql_query($query) or die('Bad SQL Query saving report');
			}
			
		
		
			foreach($types as $t)
			{	
				foreach($_POST[$t."s"] as $num => $header)
				{
					if($header != "") 
					{
						if($_POST['type_'.$t][$num] != 'Product')
						$display_name=mysql_real_escape_string($_POST[$t.'s_display'][$num]);
						
						if($_POST['type_'.$t][$num] == 'Product')
						$tag=mysql_real_escape_string($_POST['tag_'.$t][$num]);
							
						$category=mysql_real_escape_string($_POST['category_'.$t][$num]);
						
						$query = "select id from `entities` where name='" . mysql_real_escape_string($header) . "' ";
						$row = mysql_fetch_assoc(mysql_query($query)) or die('Bad SQL Query getting ' . $t . ' names ');
					
						$originDT_query = 'SELECT `type_id`, `display_name`, `category`, `tag` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND num=' . $num . ' AND type="' . $t . '" LIMIT 1';
						$originDT=mysql_query($originDT_query) or die ('Bad SQL Query getting Original Master Header Table Information Before Updating.<br/>'.$query);
						$originDT = mysql_fetch_array($originDT);
						
						$change_flag=0;
						$query = 'UPDATE rpt_masterhm_headers SET';
						
						if(trim($row['id']) != trim($originDT['type_id']))
						{
							$query .= ' type_id="' . mysql_real_escape_string($row['id']) . '",';
							$change_flag=1;
						}
						if(trim($display_name) != trim($originDT['display_name']))
						{
							$query .= ' `display_name` = "' . $display_name . '",';
							$change_flag=1;
						}
						
						if(trim($category) != trim($originDT['category']))
						{
							$query .= ' `category` = "' . $category . '",';
							$change_flag=1;
						}
						
						if(trim($tag) != trim($originDT['tag']))
						{
							$query .= ' `tag` = "' . $tag . '",';
							$change_flag=1;
						}
						
						if($change_flag)
						{
							$query = substr($query, 0, -1); //strip last comma
							$query .= ' WHERE report=' . $id . ' AND num=' . $num . ' AND type="' . $t . '" LIMIT 1';
							mysql_query($query) or die('Bad SQL Query saving ' . $t . ' names '); 
						}
					}
				}
			}//exit;
		}///Restriction on report saving ends
		
		if(isset($_POST['cell_entity1']) && !empty($_POST['cell_entity1']))
		{
			foreach($_POST['cell_entity1'] as $row => $data)
			foreach($data as $col => $value)
			{
				$entity1=$_POST['cell_entity1'][$row][$col];
				$entity2=$_POST['cell_entity2'][$row][$col];
				
				$tidy_config = array(
                     'clean' => true,
                     'output-xhtml' => true,
                     'show-body-only' => true,
                     'wrap' => 0,
                    
                     );
				$tidy = new tidy();
				
				$tidy = tidy_parse_string($_POST['filing'][$row][$col], $tidy_config, 'UTF8');
				$tidy->cleanRepair(); 
				$filing=trim($tidy);
				$filing_presence=$_POST['filing_presence'][$row][$col];
				
				$bomb=trim($_POST['bomb'][$row][$col]);
				$tidy = tidy_parse_string($_POST['bomb_explain'][$row][$col], $tidy_config, 'UTF8');
				$tidy->cleanRepair(); 
				$bomb_explain=trim($tidy);
				
				$phaseexp_presence=$_POST['phaseexp_presence'][$row][$col];
				$tidy = tidy_parse_string($_POST['phase_explain'][$row][$col], $tidy_config, 'UTF8');
				$tidy->cleanRepair(); 
				$phase_explain=trim($tidy);
				
				$phase4_val=$_POST['phase4_val'][$row][$col];
				
				$preclinical_val=$_POST['preclinical_val'][$row][$col];
				
				$up_time=date('Y-m-d H:i:s', $now);
				
				$originDT_query = "SELECT `bomb`, `bomb_explain`, `filing`, `phase_explain`, `phase4_override`, `preclinical` FROM `rpt_masterhm_cells` WHERE (`entity1` = $entity1 AND `entity2` = $entity2) OR (`entity2` = $entity1 AND `entity1` = $entity2)";
				$originDT=mysql_query($originDT_query) or die ('Bad SQL Query getting Original Bomb and Filing Information Before Updating.<br/>'.$query);
				$originDT = mysql_fetch_array($originDT);
				
				$change_flag=0;
				
				$query = "UPDATE `rpt_masterhm_cells` set ";
				
				if($bomb != $originDT['bomb'] || trim($bomb_explain) != trim($originDT['bomb_explain']))
				{
					$query .="`bomb` = '$bomb', `bomb_explain` = '".mysql_real_escape_string($bomb_explain)."', `bomb_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				
				if((trim($filing) == '' && $originDT['filing'] == NULL) && $filing_presence == 1)
				{
					$query .="`filing` = ' ', `filing_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				else if((trim($filing) != trim($originDT['filing'])) && $filing_presence == 1)
				{
					$query .="`filing` = '".mysql_real_escape_string($filing)."', `filing_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				else if($originDT['filing'] != NULL && $filing_presence == 0)
				{
					$query .="`filing` = NULL, `filing_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				
				if((trim($phase_explain) == '' && $originDT['phase_explain'] == NULL) && $phaseexp_presence == 1)
				{
					$query .="`phase_explain` = ' ', `phase_explain_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				else if((trim($phase_explain) != trim($originDT['phase_explain'])) && $phaseexp_presence == 1)
				{
					$query .="`phase_explain` = '".mysql_real_escape_string($phase_explain)."', `phase_explain_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				else if($originDT['phase_explain'] != NULL && $phaseexp_presence == 0)
				{
					$query .="`phase_explain` = NULL, `phase_explain_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				
				if(trim($phase4_val) != trim($originDT['phase4_override']))
				{
					$query .="`phase4_override` = '".mysql_real_escape_string($phase4_val)."', `phase4_override_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				
				if(trim($preclinical_val) != trim($originDT['preclinical']))
				{
					$query .="`preclinical` = '".mysql_real_escape_string($preclinical_val)."', ";
					$change_flag=1;
				}
				
				$query .= "`last_update`= '$up_time' WHERE (`entity1` = $entity1 AND `entity2` = $entity2) OR (`entity2` = $entity1 AND `entity1` = $entity2)";
				
				if($change_flag) ///If there is change then only execute query
				mysql_query($query) or die ('Bad SQL Query updating Bomb and Filing Information.<br/>'.$query);
			}
		}
	}
	
	

	if($db->user->userlevel != 'root')
	if(($repoUser === NULL && $db->user->userlevel == 'user') || ($repoUser !== NULL && $repoUser != $db->user->id && !$shared)) return;	///Restriction on report saving
	
	if(isset($_POST['move_row_down']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_row_down'] = array_keys($_POST['move_row_down']);
		$_POST['move_row_down'] = $_POST['move_row_down'][0];
		
		$current_row=$_POST['move_row_down'];
		$next_row=$_POST['move_row_down']+1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_row AND type = 'row' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_row_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'row' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$current_row' WHERE num = '$next_row' AND type = 'row' AND id <> '$current_row_id' AND report = '$id'";
		$query = mysql_query($sql);
	}
	
	if(isset($_POST['move_row_up']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_row_up'] = array_keys($_POST['move_row_up']);
		$_POST['move_row_up'] = $_POST['move_row_up'][0];
		
		$current_row=$_POST['move_row_up'];
		$next_row=$_POST['move_row_up']-1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_row AND type = 'row' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_row_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'row' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$current_row' WHERE num = '$next_row' AND type = 'row' AND id <> '$current_row_id' AND report = '$id'";
		$query = mysql_query($sql);
	}

	if(isset($_POST['move_col_left']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_col_left'] = array_keys($_POST['move_col_left']);
		$_POST['move_col_left'] = $_POST['move_col_left'][0];
		
		$current_column=$_POST['move_col_left'];
		$next_column=$_POST['move_col_left']-1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_column AND type = 'column' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_column_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'column' AND report = '$id'";
		$query = mysql_query($sql);
		$sql 
		= "UPDATE `rpt_masterhm_headers` SET num = '$current_column' WHERE num = '$next_column' AND type = 'column' AND id <> '$current_column_id' AND report = '$id'";
		$query = mysql_query($sql);
	}
	
	if(isset($_POST['move_col_right']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_col_right'] = array_keys($_POST['move_col_right']);
		$_POST['move_col_right'] = $_POST['move_col_right'][0];
		
		$current_column=$_POST['move_col_right'];
		$next_column=$_POST['move_col_right']+1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_column AND type = 'column' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_column_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'column' AND report = '$id'";
		$query = mysql_query($sql);
		$sql 
		= "UPDATE `rpt_masterhm_headers` SET num = '$current_column' WHERE num = '$next_column' AND type = 'column' AND id <> '$current_column_id' AND report = '$id'";
		$query = mysql_query($sql);
	}
	
	if($db->user->userlevel != 'root')
	if(($repoUser === NULL && $db->user->userlevel == 'user') || ($repoUser !== NULL && $repoUser != $db->user->id)) return;	///Restriction on report saving
	
	if((isset($_POST['deleterow']) && is_array($_POST['deleterow'])) || (isset($_POST['deletecol']) && is_array($_POST['deletecol'])))
	{	
		mysql_query('BEGIN');
		if(isset($_POST['deleterow']) && is_array($_POST['deleterow']))
		{
			foreach($_POST['deleterow'] as $delRow=>$stat)
			{
				//delete the row
				$query = "DELETE FROM `rpt_masterhm_headers` WHERE report= $id AND `num` = $delRow AND `type` = 'row' ";
				mysql_query($query) or die ('Bad SQL Query removing column.');
				
			}
			//after all delete rows reorder rows
			$query = "SELECT num FROM `rpt_masterhm_headers` WHERE `report` = $id AND `type` = 'row' ORDER BY `num` ASC ";
			$result = mysql_query($query);
			$cnt = mysql_num_rows($result);
			if($cnt>0)
			{
				$i=1;
				while($row = mysql_fetch_assoc($result))
				{
					$query = "UPDATE `rpt_masterhm_headers` set `num` = $i WHERE `report` = $id and `type` = 'row' AND `num` = ".$row['num'];
					mysql_query($query) or die ('Bad SQL Query updating rows with new values after delete row/s operation.<br/>'.$query);
					
					$i++;
				}
			}
			
		}
		if(isset($_POST['deletecol']) && is_array($_POST['deletecol']))
		{
			foreach($_POST['deletecol'] as $delCol=>$stat)
			{
				//delete the column
				$query = "DELETE FROM `rpt_masterhm_headers` WHERE `report`= $id AND `num` = $delCol AND `type` = 'column' ";
				mysql_query($query) or die ('Bad SQL Query removing column.');
			}	
			//after all delete columns reorder columns
			$query = "SELECT num FROM `rpt_masterhm_headers` WHERE `report` = $id AND `type` = 'column' ORDER BY `num` ASC";
			$result = mysql_query($query);
			$cnt = 0;
			$cnt = mysql_num_rows($result);
			if($cnt>0)
			{
				$i=1;
				while($row = mysql_fetch_assoc($result))
				{
					$query = "UPDATE `rpt_masterhm_headers` set `num` = $i WHERE `report` = $id and `type` = 'column' AND `num` = ".$row['num'];
					mysql_query($query) or die ('Bad SQL Query updating columns with new values after delete row/s operation.<br/>'.$query);

					$i++;
				}
			}				
		}
		
		mysql_query('COMMIT');
	}
	
}

//processes POST for report list
function postRL()
{
	global $db;
	if(isset($_POST['makenew']))
	{ 
		mysql_query('INSERT INTO `rpt_masterhm` SET name="", user=' . $db->user->id) or die('Bad SQL query creating master heatmap report');
		$_GET['id'] = mysql_insert_id();
		$id = $_GET['id'];

		$types = array('row','column');
		foreach($types as $t)
		{
			$query = 'INSERT INTO `rpt_masterhm_headers` SET report=' . $id . ',type="' . $t . '",num=1';
			mysql_query($query) or die('Bad SQL Query adding ' . $t . ' in master heatmap report');
			$query = 'INSERT INTO `rpt_masterhm_headers` SET report=' . $id . ',type="' . $t . '",num=2';
			mysql_query($query) or die('Bad SQL Query adding ' . $t . ' in master heatmap report');
		}
	}
	if(isset($_POST['delrep']) && is_array($_POST['delrep']))
	{
		foreach($_POST['delrep'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			$query = 'SELECT user FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting userid for master heatmap report');
			$res = mysql_fetch_assoc($res);
			if($res === false) continue;
			$ru = $res['user'];
			if($ru == $db->user->id || ($db->user->userlevel != 'user' && $ru === NULL) || $db->user->userlevel == 'root')
				mysql_query('DELETE FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1') or die('Bad SQL query deleting master heatmap report');
		}
	}
}
?>