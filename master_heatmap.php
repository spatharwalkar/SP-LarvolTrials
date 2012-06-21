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
				WHERE report=' . $id . ' and type="product" ORDER BY num ASC';

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
				WHERE report=' . $id . ' and type="area" ORDER BY num ASC';

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
	$parameters['area']=$aval;
	$parameters['product']=$pval;
	calc_cells($parameters);
	return true;
}

/***Recalculation of cells end*/


if($_POST['dwformat'])
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
			var pattern1 =/products/g;
			var pattern2 =/areas/g;
			
			if(pattern1.test(fieldID))
			{	
				var a = $('#'+fieldID).autocomplete({
							serviceUrl:'autosuggest.php',
							params:{table:'products', field:'name'},
							minChars:3 
				});
			}
			else if(pattern2.test(fieldID))
			{
				var a = $('#'+fieldID).autocomplete({
							serviceUrl:'autosuggest.php',
							params:{table:'areas', field:'name'},
							minChars:3 
				});
			}
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
	flag=0; phase4_flag=0; bomb_flag=0; filing_flag=0; phaseexp_flag=0; data=''; ele='';
	for(pt1=1; pt1<=rows; pt1++)
	{
		for(pt2=1; pt2<=cols; pt2++)
		{
			var area_ele = document.getElementById('areas'+pt2);
			var product_ele = document.getElementById('products'+pt2);
			
			if((area_ele != null && area_ele !='') && (product_ele != null && product_ele !=''))
			{
				var area = area_ele.value; var product = product_ele.value;
			}
			
			var element = document.getElementById('phase4_val_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_phase4_val_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if(element.value == 0 && bk_element.value==1)
			{
				flag=1; phase4_flag=1;
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
			if(bomb_flag) { if(ele != '') ele=ele+', '; ele=ele+'bomb'};
			if(filing_flag) { if(ele != '') ele=ele+', '; ele=ele+'filing'};
			if(phaseexp_flag) { if(ele != '') ele=ele+', '; ele=ele+'phase explain'};
			if(phase4_flag || bomb_flag || filing_flag || phaseexp_flag)
			data=data+' <font style="color:red">'+ele+'</font> of <font style="color:blue">product '+product +' X '+ 'area '+ area +'</font>; '
			
			phase4_flag=0; bomb_flag=0; filing_flag=0; phaseexp_flag=0; ele='';
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

///Function refreshes the report such that if multiple instaces present of same product X area combination then its data made same
function refresher(row, col, tot_rows, tot_cols)
{
	var product_ele=document.getElementById("cell_prod_"+row+"_"+col);
	var area_ele=document.getElementById("cell_area_"+row+"_"+col);
	product=product_ele.value.replace(/\s+/g, '');
	area=area_ele.value.replace(/\s+/g, '');
	
	for(pt1=1; pt1<=tot_rows; pt1++)
	{
		for(pt2=1; pt2<=tot_cols; pt2++)
		{

			var current_product_ele=document.getElementById("cell_prod_"+pt1+"_"+pt2);
			var current_area_ele=document.getElementById("cell_area_"+pt1+"_"+pt2);
			
			if((current_product_ele != null && current_product_ele != '') && (current_area_ele != '' && current_area_ele != null) && (row != pt1 || col != pt2))
			{
				current_product=current_product_ele.value.replace(/\s+/g, '');
				current_area=current_area_ele.value.replace(/\s+/g, '');
				
				if(current_product == product && current_area == area)
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
				} //if check for same product area ends
			} //if check for product or area element existence ends
		} //for llop of columns
	} //for loop of rows
}

</script>
<link href="css/popup_form.css" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="css/chromestyle2.css" />
<script type="text/javascript" src="scripts/popup-window.js"></script>
<script type="text/javascript" src="scripts/chrome.js"></script>
<link type="text/css" href="css/confirm_box.css" rel="stylesheet" />
<script type="text/javascript" src="scripts/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="scripts/jquery-ui-1.8.20.custom.min.js"></script>
<script type="text/javascript" src="scripts/autosuggest/jquery.autocomplete-min.js"></script>
<script type="text/javascript" src="progressbar/jquery.progressbar.js"></script>
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
		
		if(!in_array($update_pids[$i],$running_pids))
		{
			$err[$i]='yes';
		}
		else
		{
			$err[$i]='no';
		}
	}
	
	for($i=0;$i < $count_upids; $i++)
	{
			if( !in_array($update_pids[$i],$running_pids) and $err[$i]=='yes')
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
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report'.$query);
	$res = mysql_fetch_array($res) or die('Report not found.');
	$rptu = $res['user'];
	$shared = $res['shared'];
	$total_fld=$res['total'];
	$dtt_fld=$res['dtt'];
	$Report_DisplayName=$res['display_name'];
	if($rptu !== NULL && $rptu != $db->user->id && !$shared) return;	//prevent anyone from viewing others' private reports
	$name = $res['name'];
	$footnotes = htmlspecialchars($res['footnotes']);
	$description = htmlspecialchars($res['description']);
	$category = $res['category'];
	
	if($shared && $rptu !== NULL)
	$owner_type="shared";
	else if($rptu !== NULL && $rptu == $db->user->id)
	$owner_type="mine";
	else if($rptu === NULL)
	$owner_type="global";
	
	$query = 'SELECT `num`,`type`,`type_id`, `display_name`, `category` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' ORDER BY num ASC';
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers'.$query);
	$rows = array();
	$columns = array();
	$areaIds = array();
	$productIds = array();
	while($header = mysql_fetch_array($res))
	{
		if($header['type'] == 'area')
		{
			if($header['type_id'] != NULL)
			{
				$result =  mysql_fetch_assoc(mysql_query("SELECT id, name FROM `areas` WHERE id = '" . $header['type_id'] . "' "));
				$columns[$header['num']] = $result['name'];
				$columnsDisplayName[$header['num']] = $header['display_name']; ///Display name from master hm header table
				$columnsCategoryName[$header['num']] = $header['category'];
			}
			else
			{
				$columns[$header['num']] = $header['type_id'];
			}
			$areaIds[$header['num']] = $header['type_id'];
		}
		else
		{
			if($header['type_id'] != NULL)
			{
				$result =  mysql_fetch_assoc(mysql_query("SELECT id, name FROM `products` WHERE id = '" . $header['type_id'] . "' "));
				$rows[$header['num']] = $result['name'];
				$rowsCategoryName[$header['num']] = $header['category'];
			}
			else
			{
				$rows[$header['num']] = $header['type_id'];
			}
			$productIds[$header['num']] = $header['type_id'];
		}
	}
	// SELECT MAX ROW AND MAX COL
	$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'product\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_row = mysql_fetch_array($res);
	
	$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'area\'';
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
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
			{
				$cell_query = 'SELECT * FROM rpt_masterhm_cells WHERE `product`=' . $productIds[$row] . ' AND `area`='. $areaIds[$col] .'';
				$cell_res = mysql_query($cell_query) or die(mysql_error());
				$cell_data = mysql_fetch_array($cell_res);
				$col_active_total[$col]=$cell_data['count_active']+$col_active_total[$col];
				$row_active_total[$row]=$cell_data['count_active']+$row_active_total[$row];
				$col_count_total[$col]=$cell_data['count_total']+$col_count_total[$col];
				$row_count_total[$row]=$cell_data['count_total']+$row_count_total[$row];
				$col_indlead_total[$col]=$cell_data['count_active_indlead']+$col_indlead_total[$col];
				$row_indlead_total[$row]=$cell_data['count_active_indlead']+$row_indlead_total[$row];
				$active_total=$cell_data['count_active']+$active_total;
				$count_total=$cell_data['count_total']+$count_total;
				$indlead_total=$cell_data['count_active_indlead']+$indlead_total;
				
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
				
				$data_matrix[$row][$col]['phase_explain']=$cell_data['phase_explain'];

				$data_matrix[$row][$col]['bomb_explain']=trim($cell_data['bomb_explain']);
				
				$data_matrix[$row][$col]['phase4_override']=$cell_data['phase4_override'];
				
				if($cell_data['bomb_auto'] == 'small')
				{
					$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$row][$col]['bomb_auto']['src']='sbomb.png';
					$data_matrix[$row][$col]['bomb_auto']['alt']='Small Bomb';
					$data_matrix[$row][$col]['bomb_auto']['style']='width:9px; height:11px;';
					$data_matrix[$row][$col]['bomb_auto']['title']='Suggested';
				}
				elseif($cell_data['bomb_auto'] == 'large')
				{
					$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$row][$col]['bomb_auto']['src']='lbomb.png';
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
				
			}
			else
			{
				$data_matrix[$row][$col]['active']=0;
				$data_matrix[$row][$col]['total']=0;
				$col_active_total[$col]=0+$col_active_total[$col];
				$row_active_total[$row]=0+$row_active_total[$row];
				$col_count_total[$col]=0+$col_count_total[$col];
				$row_count_total[$row]=0+$row_count_total[$row];
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
	else
	{
		$title="Active industry lead sponsor trials";
		$view_tp='indlead';
		$link_part = '&list=1&itype=0&hm=' . $id;
	}

	$out = '<br/>&nbsp;&nbsp;<b>View type: </b> <select id="view_type" name="view_type" onchange="window.location.href=\'master_heatmap.php?id='.$_GET['id'].'&view_type=\'+this.value+\'\'">'
			. '<option value="indlead"'.(($view_tp=='indlead')? "selected=\"selected\"":"").'>Active Industry trials</option>'
			. '<option value="active" '.(($view_tp=='active')? "selected=\"selected\"":"").'>Active trials</option>'
			. '<option value="total" '.(($view_tp=='total')? "selected=\"selected\"":"").'>All trials</option></select><br/>';
			
	$out .= '<form action="master_heatmap.php" method="post">'
			. '<fieldset><legend>Download Option</legend>'
			. '<input type="hidden" name="id" value="' . $id . '" />';
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
		. '<option value="active" '.(($view_tp=='active')? "selected=\"selected\"":"").'>Active trials</option>'
		. '<option value="total" '.(($view_tp=='total')? "selected=\"selected\"":"").'>All trials</option></select><br/><br/><input type="submit" name="download" value="Download" title="Download" />'
		. '</fieldset></form>';	
		
	/*$out .='<input type="image" name="htmldown[]" src="images/html.png" title="HTML Download" />&nbsp;&nbsp;'
		. '<input type="image" name="pdfdown[]" src="images/pdf.png" title="PDF Download" />&nbsp;&nbsp;'
		. '<input type="image" name="exceldown[]" src="images/excel_new.png" title="Excel Download" /></div></form>';		*/
	$disabled=0;
	if(($owner_type == 'shared' && $rptu != $db->user->id) || ($owner_type == 'global' && $db->user->userlevel == 'user'))
	$disabled=1;
	
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
	
	
	$out .= '<br clear="both" /><form action="master_heatmap.php" name="master_heatmap" onsubmit="return validate('.count($rows).','.count($columns).');" method="post"><fieldset><legend>Edit report ' . $id . '</legend>'
		. '<input type="hidden" name="id" value="' . $id . '" />'
		. '<label>Name: <input type="text" '.(($disabled) ? ' readonly="readonly" ':'').' '
		. 'name="reportname" value="' . htmlspecialchars($name) . '"/></label>'
		. '<label>Display name: <input type="text" '.(($disabled) ? ' readonly="readonly" ':'').' '
		. 'name="report_displayname" value="' . htmlspecialchars($Report_DisplayName) . '"/></label>'
		. '<label>Category: <input type="text" '.(($disabled) ? ' readonly="readonly" ':'').' '
		. 'name="reportcategory" value="' . htmlspecialchars($category)
		. '"/></label>';		
	if($db->user->userlevel != 'user')
	{
		$out .= ' Ownership: '
			. '<label><input type="radio" name="own" value="shared" '
			. ($owner_type == 'shared' ? 'checked="checked"' : '')
			. (($owner_type == 'shared' && $rptu != $db->user->id) ? ' disabled="disabled" ':'')
			. '/>Shared</label> '
			. '<label><input type="radio" name="own" value="global" '
			. ($owner_type == 'global' ? 'checked="checked"' : '')
			. (($owner_type == 'shared' && $rptu != $db->user->id) ? ' disabled="disabled" ':'')
			. '/>Global</label> '
			. '<label><input type="radio" name="own" value="mine" '
			. ($owner_type == 'mine' ? 'checked="checked"' : '')
			. (($owner_type == 'shared' && $rptu != $db->user->id) ? ' disabled="disabled" ':'')
			. '/>Mine</label>';
	}else{
		$out .= ' Ownership: '
			. ($owner_type == 'shared' ? 'Shared' : '')
			. ($owner_type == 'global' ? 'Global' : '')
			. ($owner_type == 'mine' ? 'Mine' : '');
	}
	
	//total column checkbox
	$out .= ' <label><input '.(($disabled) ? ' disabled="disabled" ':'').' type="checkbox" name="total"  value="1" ' . (($total_fld) ? 'checked="checked"' : '') . ' />Total</label>';
	$out .= ' <label><input '.(($disabled) ? ' disabled="disabled" ':'').' type="checkbox" name="dtt"  value="1" ' . (($dtt_fld) ? 'checked="checked"' : '') . ' />Last column is DTT</label>';
	
	$out .= '<br clear="all"/>';
	
	$out .= '<input type="submit" name="reportsave" value="Save edits" /><input type="hidden" id="reportsave_flg" name="reportsave_flg" value="0" /> | ';
	
	if($db->user->userlevel != 'user' || $rptu !== NULL)
	{
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $rptu == $db->user->id))
		$out .= '<input type="submit" name="addproduct" value="More rows" /> | '
				. '<input type="submit" name="addarea" value="More columns" /> | ';
	}
	$out .= '<input type="submit" name="reportcopy" value="Copy into new" /> | '
			. '<a href="masterhm_report_inputcheck.php?id=' . $id . '">Input check</a>'
			. '<br /><table class="reportcell"><tr><th></th>';
			
	foreach($columns as $col => $val)
	{
		$out .= '<th valign="top">Area:<br/><input type="text" id="areas' . $col . '" name="areas[' . $col . ']" value="' . $val . '" autocomplete="off" '
				. ' onkeyup="javascript:autoComplete(\'areas'.$col.'\')" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
				
		$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:'';
		$cat = (isset($columnsCategoryName[$col]) && $columnsCategoryName[$col] != '')?$columnsCategoryName[$col]:'';
		$out .= 'Display name: <br/><input type="text" id="areas_display' . $col . '" name="areas_display[' . $col . ']" value="' . $val . '" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
		$out .= 'Category name: <br/><input type="text" id="category_area' . $col . '" name="category_area[' . $col . ']" value="' . $cat . '" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
				
		$out .= 'Column : '.$col.' ';
		
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $rptu == $db->user->id))
		{
			// LEFT ARROW?
			if($col > 1) $out .= ' <input type="image" name="move_col_left[' . $col . ']" src="images/left.png" title="Left"/>';
			// RIGHT ARROW?
			if($col < $max_column['num']) $out .= ' <input type="image" name="move_col_right[' . $col . ']" src="images/right.png" title="Right" />';
				
			$out .='&nbsp;&nbsp;';	
			$out .= '<label class="lbldeln"><input type="checkbox" name="deletecol[' . $col . ']" title="Delete Column '.$col.'"/></label>';
		}
		$out .='<br/>';
		if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
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
			$out .= '<a href="intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col] . $link_part .'" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a>';
		
		
		}
		$out .='<br/>';
		$out .= '</th>';
	}
	//if total checkbox is selected
	if($total_fld)
	{
		$out .= '<th width="150px">';
		if(!empty($productIds) && !empty($areaIds))
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
				
			$productIds = array_filter($productIds);
			$areaIds = array_filter($areaIds);
			$out .= '<a href="intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). $link_part . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a>';
		}
		$out .= '</th>';
	}
	$out .= '</tr>';
	foreach($rows as $row => $rval)
	{
		$cat = (isset($rowsCategoryName[$row]) && $rowsCategoryName[$row] != '')?$rowsCategoryName[$row]:'';
		$out .= '<tr><th>Product:<br/><input type="text" id="products' . $row . '"  name="products[' . $row . ']" value="' . $rval . '" autocomplete="off" '
				. ' onkeyup="javascript:autoComplete(\'products'.$row.'\')" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
				
		$out .= 'Category name: <br/><input type="text" id="category_product' . $row . '" name="category_product[' . $row . ']" value="' . $cat . '" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
		
		$out .= 'Row : '.$row.' ';
		
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $rptu == $db->user->id))
		{
			// UP ARROW?
			if($row > 1) $out .= ' <input type="image" name="move_row_up[' . $row . ']" src="images/asc.png" title="Up"/>';
			// DOWN ARROW?
			if($row < $max_row['num']) $out .= ' <input type="image" name="move_row_down[' . $row . ']" src="images/des.png" title="Down"/>';
			
			$out .='&nbsp;&nbsp;';	
			$out .= '<label class="lbldeln"><input type="checkbox" name="deleterow[' . $row . ']" title="Delete Column '.$row.'" /></label>';
		}
		$out .='<br/>';
		if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
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
				
			$out .= '<a href="intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). $link_part . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a>';
		}
		$out .='<br/>';
		$out .= '</th>';
		
		foreach($columns as $col => $cval)
		{
			$out .= '<td valign="middle" align="center" style="text-align:center;'.$data_matrix[$row][$col]['color'].'"><br>';
			
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
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
					
				$out .= '<a href="intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col] . $link_part . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a><br/><br/>';
				
				$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['phase4_override']) ? '1':'0') . ' " name="phase4_val['.$row.']['.$col.']" id="phase4_val_'.$row.'_'.$col.'" />';
				$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['phase4_override']) ? '1':'0') . ' " name="bk_phase4_val['.$row.']['.$col.']" id="bk_phase4_val_'.$row.'_'.$col.'" />';
				
				$out .= '<input type="hidden" id="cell_prod_'.$row.'_'.$col.'" name="cell_prod['.$row.']['.$col.']" value="'. $productIds[$row] .'" />'
						.'<input type="hidden" id="cell_area_'.$row.'_'.$col.'" name="cell_area['.$row.']['.$col.']" value="' . $areaIds[$col] . '" />'
						.'<input type="hidden" name="filing_presence['.$row.']['.$col.']" id="filing_presence_'.$row.'_'.$col.'" value="' . (($data_matrix[$row][$col]['filing'] != NULL)? 1:0) . '" />'
						.'<input type="hidden" name="phaseexp_presence['.$row.']['.$col.']" id="phaseexp_presence_'.$row.'_'.$col.'" value="' . (($data_matrix[$row][$col]['phase_explain'] != NULL)? 1:0) . '" />'
						.'<input type="hidden" name="bk_filing_presence['.$row.']['.$col.']" id="bk_filing_presence_'.$row.'_'.$col.'" value="' . (($data_matrix[$row][$col]['filing'] != NULL)? 1:0) . '" />'
						.'<input type="hidden" name="bk_phaseexp_presence['.$row.']['.$col.']" id="bk_phaseexp_presence_'.$row.'_'.$col.'" value="' . (($data_matrix[$row][$col]['phase_explain'] != NULL)? 1:0) . '" />';
				
				
				$out .= '<div style="float:left;"><font id="phase4_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['phase4_override'])
				$out .= '<img align="middle" id="phase4img_'.$row.'_'.$col.'" title="Red cell override" src="images/phase4.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Phase4_Override"/>&nbsp;';
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
				
				
				$out .= '<div align="right" style="height:25px; vertical-align: bottom; cursor:pointer;" class="chromestyle" id="chromemenu_'.$row.'_'.$col.'"><ul><li><a rel="dropmenu_'.$row.'_'.$col.'"><b>+<img src="images/down.gif" border="0" style="width:9px; height:5px;" /><b></a></li></ul></div>';
				
				
				
				$out .= '<div id="dropmenu_'.$row.'_'.$col.'" class="dropmenudiv" style="width: 180px;">'
					 .'<a style="vertical-align:bottom;"><input  id="bombopt_'.$row.'_'.$col.'"  name="bombopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png') ? 'checked="checked"':'') . ' onchange="update_icons(\'bomb\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" />Small/Large bomb&nbsp;<img align="right" src="images/lbomb.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="filingopt_'.$row.'_'.$col.'"  name="filingopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['filing'] != NULL) ? 'checked="checked"':'') . '  onchange="update_icons(\'filing\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" />Filing&nbsp;<img align="right" src="images/file.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="phase4opt_'.$row.'_'.$col.'"  name="phase4opt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['phase4_override']) ? 'checked="checked"':'') . '  onchange="update_icons(\'phase4\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" />Red cell override&nbsp;<img align="right" src="images/phase4.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="phaseexpopt_'.$row.'_'.$col.'"  name="phaseexpopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['phase_explain'] != NULL) ? 'checked="checked"':'') . '  onchange="update_icons(\'phaseexp\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" />Phase explain&nbsp;<img align="right" src="images/phaseexp.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
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
		
		$out .= '</tr>';
	}
	$out .= '</table>'
			. '<fieldset><legend>Footnotes</legend><textarea '.(($disabled) ? ' readonly="readonly" ':'').' name="footnotes" cols="45" rows="5">' 
			. $footnotes . '</textarea></fieldset>'
			. '<fieldset><legend>Description</legend><textarea '.(($disabled) ? ' readonly="readonly" ':'').' name="description" cols="45" rows="5">' . $description
			. '</textarea></fieldset>';
	$out .='<div id="dialog" title="Confirm"></div>';
	if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $rptu == $db->user->id))
	{
		$out .= '<br clear="all"/><div align="left" style="vertical-align:bottom; float:left;"><fieldset style="margin-top:50px; padding:8px;"><legend>Advanced</legend>'
				. '<label class="lbldeln"><input class="delrepe" type="checkbox" id="delrep" name="delrep['.$id.']" title="Delete" /></label>' 
				. '&nbsp;&nbsp;&nbsp;&nbsp;Delete this master heatmap report</fieldset></div>';
	};
	$out .= '</form>';

	return $out;
}

function Download_reports()
{
	ob_start();
	global $db;
	global $now;
	if(!isset($_POST['id'])) return;
	$id = mysql_real_escape_string(htmlspecialchars($_POST['id']));
	if(!is_numeric($id)) return;
	$query = 'SELECT `name`, `user`, `footnotes`, `description`, `category`, `shared`, `total`, `dtt`, `display_name` FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report'.$query);
	$res = mysql_fetch_array($res) or die('Report not found.');
	$rptu = $res['user'];
	$shared = $res['shared'];
	$total_fld=$res['total'];
	$dtt = $res['dtt'];
	$name = $res['name'];
	$Report_DisplayName=$res['display_name'];
	$footnotes = htmlspecialchars($res['footnotes']);
	$description = htmlspecialchars($res['description']);
	$category = $res['category'];
	
	$query = 'SELECT `num`,`type`,`type_id`, `display_name`, `category` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' ORDER BY num ASC';
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers'.$query);
	$rows = array();
	$columns = array();
	$areaIds = array();
	$productIds = array();
	$columnsDisplayName = array();
	$rowsDisplayName = array();
	
	while($header = mysql_fetch_array($res))
	{
		if($header['type'] == 'area')
		{
			if($header['type_id'] != NULL)
			{
				$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, display_name, description FROM `areas` WHERE id = '" . $header['type_id'] . "' "));
				$columns[$header['num']] = $result['name'];
				//$columnsDisplayName[$header['num']] = $result['display_name'];
				$columnsDescription[$header['num']] = $result['description'];
				$columnsDisplayName[$header['num']] = $header['display_name'];	///Display name from master hm header table
				$columnsCategoryName[$header['num']] = $header['category'];
			}
			else
			{
				$columns[$header['num']] = $header['type_id'];
			}
			$areaIds[$header['num']] = $header['type_id'];
			
			$last_num = $header['num'];
			$last_area = $header['type_id'];
		}
		else
		{
			if($header['type_id'] != NULL)
			{
				$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description, company FROM `products` WHERE id = '" . $header['type_id'] . "' "));
				$rows[$header['num']] = $result['name'];
				if($result['company'] != NULL && trim($result['company']) != '')
				{
					$result['company']=str_replace(',',', ',$result['company']);
					$result['company']=str_replace(',  ',', ',$result['company']);
					$rows[$header['num']] = $result['name'].' / '.$result['company'];
				}
				$rowsDisplayName[$header['num']] = '';
				$rowsDescription[$header['num']] = $result['description'];
				$rowsCategoryName[$header['num']] = $header['category'];
			}
			else
			{
				$rows[$header['num']] = $header['type_id'];
			}
			$productIds[$header['num']] = $header['type_id'];
		}
	}
	
	/////Remove last column at start only //////////
	$new_columns = array();
	foreach($columns as $col => $cval)
	{
		if($dtt && $last_num == $col)
		array_pop($areaIds); //In case of DTT enable skip last column vaules
		else
		$new_columns[$col]=$cval;
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
		else
			$start_range = '-' . (($start_range == '1 quarter') ? '3 months' : $start_range);
		
		$end_range = trim(str_replace('ago', '', $_REQUEST['er']));
		if($end_range == 'now')
			$end_range = 'now';
		else
			$end_range = '-' . (($end_range == '1 quarter') ? '3 months' : $end_range);
	}
	else
	{
		$start_range = 'now';
		$end_range = '-1 week';
		$sr = 'now';
		$er = '1 week ago';
	}

/* 	echo '<pre>';
	print_r($rows);
	print_r($columnsDisplayName);
	print_r($columnsDescription);
	print_r($rowsDisplayName);
	print_r($rowsDescription);
	die; */
	// SELECT MAX ROW AND MAX COL
	$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'product\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_row = mysql_fetch_array($res);
	
	$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'area\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_column = mysql_fetch_array($res);
	
	$row_total=array();
	$col_total=array();
	$active_total=0;
	$count_total=0;
	$data_matrix=array();
	foreach($rows as $row => $rval)
	{
		foreach($columns as $col => $cval)
		{
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
			{
				$cell_query = 'SELECT * FROM rpt_masterhm_cells WHERE `product`=' . $productIds[$row] . ' AND `area`='. $areaIds[$col] .'';
				$cell_res = mysql_query($cell_query) or die(mysql_error());
				$cell_data = mysql_fetch_array($cell_res);
				$col_active_total[$col]=$cell_data['count_active']+$col_active_total[$col];
				$row_active_total[$row]=$cell_data['count_active']+$row_active_total[$row];
				$col_count_total[$col]=$cell_data['count_total']+$col_count_total[$col];
				$row_count_total[$row]=$cell_data['count_total']+$row_count_total[$row];
				$col_indlead_total[$col]=$cell_data['count_active_indlead']+$col_indlead_total[$col];
				$row_indlead_total[$row]=$cell_data['count_active_indlead']+$row_indlead_total[$row];
				$active_total=$cell_data['count_active']+$active_total;
				$count_total=$cell_data['count_total']+$count_total;
				$indlead_total=$cell_data['count_active_indlead']+$indlead_total;
				
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
				
				$data_matrix[$row][$col]['bomb_explain']=trim($cell_data['bomb_explain']);
				
				$data_matrix[$row][$col]['phase_explain']=trim($cell_data['phase_explain']);
				
				
				if($cell_data['bomb_auto'] == 'small')
				{
					$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$row][$col]['bomb_auto']['src']='sbomb.png';
					$data_matrix[$row][$col]['bomb_auto']['alt']='Small bomb';
					$data_matrix[$row][$col]['bomb_auto']['style']='width:10px; height:11px;';
					$data_matrix[$row][$col]['bomb_auto']['title']='Suggested';
				}
				elseif($cell_data['bomb_auto'] == 'large')
				{
					$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$row][$col]['bomb_auto']['src']='lbomb.png';
					$data_matrix[$row][$col]['bomb_auto']['alt']='Large bomb';
					$data_matrix[$row][$col]['bomb_auto']['style']='width:18px; height:20px;';
					$data_matrix[$row][$col]['bomb_auto']['title']='Suggested';
				}
				else
				{
					$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$row][$col]['bomb_auto']['src']='trans.gif';
					$data_matrix[$row][$col]['bomb_auto']['alt']='None';
					$data_matrix[$row][$col]['bomb_auto']['style']='width:10px; height:11px;';
					$data_matrix[$row][$col]['bomb_auto']['title']='';
				}
				
				
				$data_matrix[$row][$col]['last_update']=$cell_data['last_update'];
				$data_matrix[$row][$col]['count_lastchanged']=$cell_data['count_lastchanged'];
				$data_matrix[$row][$col]['bomb_lastchanged']=$cell_data['bomb_lastchanged'];
				$data_matrix[$row][$col]['filing_lastchanged']=$cell_data['filing_lastchanged'];
				$data_matrix[$row][$col]['phase_explain_lastchanged']=$cell_data['phase_explain_lastchanged'];
				$data_matrix[$row][$col]['highest_phase_prev']=$cell_data['highest_phase_prev'];
				$data_matrix[$row][$col]['highest_phase_lastchanged']=$cell_data['highest_phase_lastchanged'];
				
				
				$data_matrix[$row][$col]['active_prev']=$cell_data['count_active_prev'];
				$data_matrix[$row][$col]['total_prev']=$cell_data['count_total_prev'];
				$data_matrix[$row][$col]['indlead_prev']=$cell_data['count_active_indlead_prev'];
				
				
				$data_matrix[$row][$col]['update_flag'] = 0;
				if(date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['count_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now)) && date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['count_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now)))
				{
					if($_POST['dwcount']=='active')
					{
						if($data_matrix[$row][$col]['active'] != $data_matrix[$row][$col]['active_prev'])
						{
							$data_matrix[$row][$col]['count_lastchanged_value']=1;
						}
					}
					elseif($_POST['dwcount']=='total')
					{
						if($data_matrix[$row][$col]['total'] != $data_matrix[$row][$col]['total_prev'])
						{
							$data_matrix[$row][$col]['count_lastchanged_value']=1;
						}
					}
					else
					{
						if($data_matrix[$row][$col]['indlead'] != $data_matrix[$row][$col]['indlead_prev'])
						{
							$data_matrix[$row][$col]['count_lastchanged_value']=1;
						}
					}
				}
				
				if(date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['filing_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now)) && date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['filing_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now)))
				{
					$data_matrix[$row][$col]['filing_image']='images/newred_file.png';
					$data_matrix[$row][$col]['update_flag'] = 1;
				}
				else
				$data_matrix[$row][$col]['filing_image']='images/new_file.png';
				
				if(date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now)) && date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now)))
				{
					$data_matrix[$row][$col]['phase_explain_image']='images/phaseexp_red.png';
					$data_matrix[$row][$col]['update_flag'] = 1;
				}
				else
				$data_matrix[$row][$col]['phase_explain_image']='images/phaseexp.png';
				
				if((date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['highest_phase_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now))) && (date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['highest_phase_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now))) && ($data_matrix[$row][$col]['highest_phase_prev'] != NULL && $data_matrix[$row][$col]['highest_phase_prev'] != ''))
				{
					$data_matrix[$row][$col]['highest_phase_lastchanged_value']=1;
					$data_matrix[$row][$col]['update_flag'] = 1;
				}
				
				if(trim($cell_data['bomb']) == 'small')
				{
					$data_matrix[$row][$col]['bomb']['value']=trim($cell_data['bomb']);
					
					if(date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now)) && date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now)))
					{
						$data_matrix[$row][$col]['bomb']['src']='newred_sbomb.png';
						$data_matrix[$row][$col]['exec_bomb']['src']='newred_sbomb.png'; //Excel bomb image
						$data_matrix[$row][$col]['update_flag'] = 1;
					}
					else
					{
						$data_matrix[$row][$col]['bomb']['src']='new_sbomb.png';
						$data_matrix[$row][$col]['exec_bomb']['src']='new_sbomb.png'; //Excel bomb image
					}
					$data_matrix[$row][$col]['bomb']['alt']='Small bomb';
					$data_matrix[$row][$col]['bomb']['style']='width:11px; height:11px;';
					$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
				}
				elseif(trim($cell_data['bomb']) == 'large')
				{
					$data_matrix[$row][$col]['bomb']['value']=trim($cell_data['bomb']);
					
					if((date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])) <= date('Y-m-d H:i:s', strtotime($start_range, $now))) && (date('Y-m-d H:i:s', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])) >= date('Y-m-d H:i:s', strtotime($end_range, $now))))
					{
						$data_matrix[$row][$col]['bomb']['src']='newred_lbomb.png';
						$data_matrix[$row][$col]['exec_bomb']['src']='newred_lbomb.png';
						$data_matrix[$row][$col]['update_flag'] = 1;
					}
					else
					{
						$data_matrix[$row][$col]['bomb']['src']='new_lbomb.png';
						$data_matrix[$row][$col]['exec_bomb']['src']='new_lbomb.png';
					}
					$data_matrix[$row][$col]['bomb']['alt']='Large bomb';
					$data_matrix[$row][$col]['bomb']['style']='width:11px; height:11px;';
					$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
				}
				else
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='new_square.png';
					$data_matrix[$row][$col]['exec_bomb']['src']='new_square.png';
					$data_matrix[$row][$col]['bomb']['alt']='None';
					$data_matrix[$row][$col]['bomb']['style']='width:11px; height:11px;';
					$data_matrix[$row][$col]['bomb']['title']='Bomb details';
				}
				
				$data_matrix[$row][$col]['filing']=trim($cell_data['filing']);
				
				$data_matrix[$row][$col]['phase4_override']=$cell_data['phase4_override'];
				
				
				
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
					$data_matrix[$row][$col]['color_code']='FF9900';
				}
				else if($cell_data['highest_phase'] == '4' || $cell_data['highest_phase'] == '3/4' || $cell_data['highest_phase'] == '3b/4')
				{
					$data_matrix[$row][$col]['color']='background-color:#FF0000;';
					$data_matrix[$row][$col]['color_code']='FF0000';	
				}
				
				if($cell_data['phase4_override'])
				{
					$data_matrix[$row][$col]['color']='background-color:#FF0000;';
					$data_matrix[$row][$col]['color_code']='FF0000';
				}
				
				$data_matrix[$row][$col]['not_yet_recruiting']=$cell_data['not_yet_recruiting'];
				$data_matrix[$row][$col]['recruiting']=$cell_data['recruiting'];
				$data_matrix[$row][$col]['enrolling_by_invitation']=$cell_data['enrolling_by_invitation'];
				$data_matrix[$row][$col]['active_not_recruiting']=$cell_data['active_not_recruiting'];
				$data_matrix[$row][$col]['completed']=$cell_data['completed'];
				$data_matrix[$row][$col]['suspended']=$cell_data['suspended'];
				$data_matrix[$row][$col]['terminated']=$cell_data['terminated'];
				$data_matrix[$row][$col]['withdrawn']=$cell_data['withdrawn'];
				$data_matrix[$row][$col]['available']=$cell_data['available'];
				$data_matrix[$row][$col]['no_longer_available']=$cell_data['no_longer_available'];
				$data_matrix[$row][$col]['approved_for_marketing']=$cell_data['approved_for_marketing'];
				$data_matrix[$row][$col]['no_longer_recruiting']=$cell_data['no_longer_recruiting'];
				$data_matrix[$row][$col]['withheld']=$cell_data['withheld'];
				$data_matrix[$row][$col]['temporarily_not_available']=$cell_data['temporarily_not_available'];
				$data_matrix[$row][$col]['ongoing']=$cell_data['ongoing'];
				$data_matrix[$row][$col]['not_authorized']=$cell_data['not_authorized'];
				$data_matrix[$row][$col]['prohibited']=$cell_data['prohibited'];
				$data_matrix[$row][$col]['new_trials']=$cell_data['new_trials'];
			}
			else
			{
				$data_matrix[$row][$col]['active']=0;
				$data_matrix[$row][$col]['total']=0;
				$col_active_total[$col]=0+$col_active_total[$col];
				$row_active_total[$row]=0+$row_active_total[$row];
				$col_count_total[$col]=0+$col_count_total[$col];
				$row_count_total[$row]=0+$row_count_total[$row];
				$data_matrix[$row][$col]['bomb_auto']['src']='';
				$data_matrix[$row][$col]['bomb']['src']='';
				$data_matrix[$row][$col]['bomb_explain']='';
				$data_matrix[$row][$col]['filing']='';
				$data_matrix[$row][$col]['color']='background-color:#DDF;';
				$data_matrix[$row][$col]['color_code']='DDF';
				$data_matrix[$row][$col]['update_flag'] = 0;
			}
		}
	}
	
	$count_fillbomb=0;	
	if($_POST['dwcount']=='active')
	{
		$tooltip=$title="Active trials";
		$pdftitle="Active trials";
		$link_part = '&list=1&sr='.$sr.'&er='.$er.'&hm=' . $id;
	}
	elseif($_POST['dwcount']=='total')
	{
		$pdftitle=$tooltip=$title="All trials (Active + Inactive)";
		$link_part = '&list=2&sr='.$sr.'&er='.$er.'&hm=' . $id;
	}
	else
	{
		$tooltip=$title="Active industry lead sponsor trials";
		$pdftitle="Active industry lead sponsor trials";
		$link_part = '&list=1&itype=0&sr='.$sr.'&er='.$er.'&hm=' . $id;
	}
	$link_part=str_replace(' ','+',$link_part);	
	
	$Report_Name = htmlspecialchars((trim($Report_DisplayName) != '' && $Report_DisplayName != NULL)? trim($Report_DisplayName):'report '.$id.'');
	
	if($_POST['dwformat']=='pdfdown')
	{
	
		require_once('tcpdf/config/lang/eng.php');
		require_once('tcpdf/tcpdf.php');  
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set document information
		//$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Larvol Trials');
		$pdf->SetTitle('Larvol Trials');
		$pdf->SetSubject('Larvol Trials');
		$pdf->SetKeywords('Larvol Trials Master Heatmap, Larvol Trials Master Heatmap PDF Export');
		$pdf->SetFont('verdana', '', 8);
		$pdf->setFontSubsetting(false);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		//// Landscape page orientation
		$pdf->setPageOrientation('l');
			
		// remove default header/footer
		$pdf->setPrintHeader(false);
		//set some language-dependent strings
		$pdf->setLanguageArray($l);
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->AddPage();
		//ini_set('pcre.backtrack_limit',strlen($pdfContent));	
		$name = htmlspecialchars(strlen($name)>0?$name:('report '.$id.''));
		
		$pdf->SetFillColor(192, 196, 254);
        $pdf->SetTextColor(0);
		$pdf->setCellPaddings(1, 1, 1, 1);
		$pdf->setCellMargins(0, 0, 0, 0);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));
		$pdf->writeHTMLCell(137, 13, '', '', '<b>Name: </b>'. $Report_Name, $border, $ln=0, $fill=1, $reseth=true, $align='L', $autopadding=true);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));
		$pdf->writeHTMLCell(137, 13, '', '', '<b>Category: </b>'. htmlspecialchars($category), $border, $ln=0, $fill=1, $reseth=true, $align='L', $autopadding=true);
		$pdf->Ln(13);
		
		$pdf->setCellPaddings(1, 1, 1, 1);
		$pdf->writeHTMLCell(274, 8, '', '', '<b>Display Mode: </b>'. $pdftitle, $border, $ln=0, $fill=1, $reseth=true, $align='L', $autopadding=true);
		$pdf->Ln(20);
		
		$product_Col_Width = 25;
		$area_Col_Width=20;
		
		//// Give product column required maximum width when available to prevent wrapping
		$Avail_Prod_Col_width = 274-((count($columns)+((isset($total_fld) && $total_fld == "1")? 1:0))*($area_Col_Width+2));
		$Current_product_Col_Width = $product_Col_Width;
		if($Avail_Prod_Col_width > 25)
		{
			foreach($rows as $row => $rval)
			{
				if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
				{
					$Min_productNumLines=0;
					while($Min_productNumLines != 1)	///Check while we we dont get mimimum lines to display product name
					{
						$current_NumLines=$pdf->getNumLines($rval, $Current_product_Col_Width);	//get number of lines
						if($current_NumLines == 1)	//if 1 line then stop processing, take next product
						$Min_productNumLines = $current_NumLines;
						else if($current_NumLines >= 1)	/// if more lines required to display text
						{
							if($Current_product_Col_Width < $Avail_Prod_Col_width)	/// if possible to increase width then increase it
							$Current_product_Col_Width++;
							if($Current_product_Col_Width >= $Avail_Prod_Col_width)	///if NOT possible to increase then stop execution take next product
							$Min_productNumLines = 1;
						}else if($current_NumLines < 1) $Min_productNumLines = 1;	/// if required line below range then stop and take next product
					}
				}
			}
			$product_Col_Width = $Current_product_Col_Width;	///new width
		}
		///Calculate height for area row
		$Max_areaNumLines=0;
		foreach($columns as $col => $val)
		{
			$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
			$current_NumLines=$pdf->getNumLines($val, $area_Col_Width);
			else $current_NumLines = 0;
			if($Max_areaNumLines < $current_NumLines)
			$Max_areaNumLines = $current_NumLines;
		}
		$Area_Row_height = $Max_areaNumLines * 4.5;
		
		$pdf->SetFillColor(255, 255, 255);
		$pdf->setCellMargins(1, 1, 1, 1);
		$pdf->writeHTMLCell($product_Col_Width, $Area_Row_height, '', '', '', $border=0, $ln=0, $fill=1, $reseth=true, $align='L', $autopadding=true);
		
		
		foreach($columns as $col => $val)
		{
			$pdf->setCellMargins(1, 1, 1, 1);
			
			$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
			$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
			$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
				
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
			{
				if($_POST['dwcount']=='active')
				{
					$count_val=$col_active_total[$col];
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=$col_count_total[$col];
				}
				else
				{
					$count_val=$col_indlead_total[$col];
				}
				$pdfContent = '<a style="color:#000000;text-decoration:none;" href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]. $link_part . '" target="_blank" title="'. $caltTitle .'">'.$val.'</a>';
				$pdf->writeHTMLCell($area_Col_Width, $Area_Row_height, '', '', $pdfContent, $border=1, $ln=0, $fill=1, $reseth=true, $align='C', $autopadding=true);
			}
			else
			{
				$pdf->writeHTMLCell($area_Col_Width, $Area_Row_height, '', '', '', $border=1, $ln=0, $fill=1, $reseth=true, $align='C', $autopadding=true);
			}
		}
		//if total checkbox is selected
		if(isset($total_fld) && $total_fld == "1")
		{
			$pdf->getCellPaddings();
			$pdf->setCellMargins(1, 1, 1, 1);
				
			if(!empty($productIds) && !empty($areaIds))
			{
				if($_POST['dwcount']=='active')
				{
					$count_val=$active_total;
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=$count_total;
				}
				else
				{
					$count_val=$indlead_total;
				}
				$productIds = array_filter($productIds);
				$areaIds = array_filter($areaIds);
				$pdfContent = '<a style="color:#000000;text-decoration:none;" href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). $link_part . '" target="_blank" title="'. $title .'">'.$count_val.'</a>';
				$pdf->MultiCell($area_Col_Width, $Area_Row_height, $pdfContent, $border, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
			}
			else
			{
				$pdf->MultiCell($area_Col_Width, $Area_Row_height, '', $border, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
			}
		}
		$pdf->Ln($Area_Row_height+2);
		
		foreach($rows as $row => $rval)
		{
			$dimensions = $pdf->getPageDimensions();
			//Height calculation depending on product name
			$rowcount = 0;
 			//work out the number of lines required
			$rowcount = $pdf->getNumLines($rval, $product_Col_Width);
 			$startY = $pdf->GetY();
 			$height = ((($rowcount * 4.5) <15) ? 15:($rowcount * 4.5));	//15 is minimum height to accomodate images and other data
			
			if (($startY + $height) + $dimensions['bm'] > ($dimensions['hk'])) {
				//this row will cause a page break, draw the bottom border on previous row and give this a top border
				//we could force a page break and rewrite grid headings here
				$pdf->AddPage();
				///Add the header row again at new page
				$pdf->SetFillColor(255, 255, 255);
				$pdf->setCellMargins(1, 1, 1, 1);
				$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));
				$pdf->writeHTMLCell($product_Col_Width, $Area_Row_height, '', '', '', $border, $ln=0, $fill=1, $reseth=true, $align='L', $autopadding=true);
				
				foreach($columns as $col => $val)
				{
					$pdf->setCellMargins(1, 1, 1, 1);
					$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
					$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
					$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
				
					if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
					{
						if($_POST['dwcount']=='active')
						{
							$count_val=$col_active_total[$col];
						}
						elseif($_POST['dwcount']=='total')
						{
							$count_val=$col_count_total[$col];
						}
						else
						{
							$count_val=$col_indlead_total[$col];
						}
						$pdfContent = '<a style="color:#000000;text-decoration:none;" href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]. $link_part . '" target="_blank" title="'. $caltTitle .'">'.$val.'</a>';
						$pdf->writeHTMLCell($area_Col_Width, $Area_Row_height, '', '', $pdfContent, $border=1, $ln=0, $fill=1, $reseth=true, $align='C', $autopadding=true);
					}
					else
					{
						$pdf->writeHTMLCell($area_Col_Width, $Area_Row_height, '', '', '', $border=1, $ln=0, $fill=1, $reseth=true, $align='C', $autopadding=true);
					}
				}
				//if total checkbox is selected
				if(isset($total_fld) && $total_fld == "1")
				{
					$pdf->getCellPaddings();
					$pdf->setCellMargins(1, 1, 1, 1);
				
					if(!empty($productIds) && !empty($areaIds))
					{
						if($_POST['dwcount']=='active')
						{
							$count_val=$active_total;
						}
						elseif($_POST['dwcount']=='total')
						{
							$count_val=$count_total;
						}
						else
						{
							$count_val=$indlead_total;
						}
						$productIds = array_filter($productIds);
						$areaIds = array_filter($areaIds);
						$pdfContent = '<a style="color:#000000;text-decoration:none;" href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). $link_part . '" target="_blank" title="'. $title .'">'.$count_val.'</a>';
						$pdf->MultiCell($area_Col_Width, $Area_Row_height, $pdfContent, $border, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
					}
					else
					{
						$pdf->MultiCell($area_Col_Width, $Area_Row_height, '', $border, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
					}
				}
				$pdf->Ln($Area_Row_height+2);
				///End of header row				
			} elseif ((ceil($startY) + $height) + $dimensions['bm'] == floor($dimensions['hk'])) {
				//fringe case where this cell will just reach the page break
				//draw the cell with a bottom border as we cannot draw it otherwise
				
			} else {
				//normal cell
			}
			//$rowcount = $pdf->getStringHeight(30,$rval,$reseth = true,$autopadding = true,$cellpadding = '',$border) ;	
			
			$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
			$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
			
			$pdf->SetFillColor(255, 255, 255);
        	$pdf->SetTextColor(0);
			$pdf->setCellMargins(1, 1, 1, 1);
			$pdf->getCellPaddings();
			$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));		
			if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
			{
				if($_POST['dwcount']=='active')
				{
					$count_val=$row_active_total[$row];
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=$row_count_total[$row];
				}
				else
				{
					$count_val=$row_indlead_total[$row];
				}
				$pdfContent = '<a style="color:#000000;text-decoration:none;" href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). $link_part . '" target="_blank" class="ottlink" title="'. $raltTitle .'">'.$rval.'</a>';
				
				
				$pdf->MultiCell($product_Col_Width, $height, $pdfContent, $border, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
			}
			else
			{
				$pdf->SetFillColor(255, 255, 255);
        		$pdf->SetTextColor(0);
				$pdf->setCellMargins(1, 1, 1, 1);
				$pdf->getCellPaddings();
			
				$dimensions = $pdf->getPageDimensions();
				$startY = $pdf->GetY();
				$height = 15;	//12 is default height
				if (($startY + $height) + $dimensions['bm'] > ($dimensions['hk'])) {
					//this row will cause a page break, draw the bottom border on previous row and give this a top border
					//we could force a page break and rewrite grid headings here
					$pdf->AddPage();					
				}
				$pdf->MultiCell($product_Col_Width, $height, ' ', $border, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
			}
			
		
			foreach($columns as $col => $cval)
			{
				$pdf->getCellPaddings();
				$pdf->setCellMargins(1, 1, 1, 1);
				$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(220,220,220)));
				$pdf->SetFillColor(245,245,245);
				
				if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL && $data_matrix[$row][$col]['total'] != 0)
				{
					
					$pdf->getCellPaddings();
					$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(220,220,220)));
					$pdf->SetFillColor(220,220,220);
				
					if($_POST['dwcount']=='active')
					{
						$count_val=$data_matrix[$row][$col]['active'];
						$count_val_prev=$data_matrix[$row][$col]['active_prev'];
					}
					elseif($_POST['dwcount']=='total')
					{
						$count_val=$data_matrix[$row][$col]['total'];
						$count_val_prev=$data_matrix[$row][$col]['total_prev'];
					}
					else
					{
						$count_val=$data_matrix[$row][$col]['indlead'];
						$count_val_prev=$data_matrix[$row][$col]['indlead_prev'];
					}
					
					if($data_matrix[$row][$col]['color_code']=='BFBFBF')
					{
						$pdf->SetFillColor(191,191,191);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(191,191,191)));
					}
					else if($data_matrix[$row][$col]['color_code']=='00CCFF')
					{
						$pdf->SetFillColor(0,204,255);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,204,255)));
					}
					else if($data_matrix[$row][$col]['color_code']=='99CC00')
					{
						$pdf->SetFillColor(153,204,0);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(153,204,0)));
					}
					else if($data_matrix[$row][$col]['color_code']=='FFFF00')
					{
						$pdf->SetFillColor(255,255,0);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,0)));
					}
					else if($data_matrix[$row][$col]['color_code']=='FF9900')
					{
						$pdf->SetFillColor(255,153,0);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,153,0)));
					}
					else if($data_matrix[$row][$col]['color_code']='FF0000')
					{
						$pdf->SetFillColor(255,0,0);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,0,0)));	
					}
					
					if($data_matrix[$row][$col]['update_flag'] == 1)
					{ 
						$data_matrix[$row][$col]['bordercolor_code']='#FF0000';
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,0,0)));
						//$pdf->SetFillColor(255,255,255);
					}
					
					if(($data_matrix[$row][$col]['total'] == 0))
					{ 
						$data_matrix[$row][$col]['color_code']='f5f5f5'; 
						$pdf->SetFillColor(245,245,245);
						$data_matrix[$row][$col]['bordercolor_code']='blue'; 
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(245,245,245)));
					}
					
					//pixels = point * 96 / 72
					$area_Col_Width_px = $area_Col_Width * 96 / 72;
					$height_px = $height * 96 / 72;
					
					$pdfContent ='';
					
					$annotation_text = '';
					if($data_matrix[$row][$col]['count_lastchanged_value']==1)
					$annotation_text .= "Count updated from: ".$count_val_prev."\n";
					if($data_matrix[$row][$col]['highest_phase_lastchanged_value']==1)
					$annotation_text .= "Highest Phase updated from: Phase ".$data_matrix[$row][$col]['highest_phase_prev']."\n";
					if($data_matrix[$row][$col]['bomb_explain'] != NULL && trim($data_matrix[$row][$col]['bomb_explain']) != '' && ($data_matrix[$row][$col]['bomb']['value'] == 'small' || $data_matrix[$row][$col]['bomb']['value'] == 'large')) 
					$annotation_text .= "Bomb details: ".$data_matrix[$row][$col]['bomb_explain']."\n";
					if($data_matrix[$row][$col]['filing'] != NULL && trim($data_matrix[$row][$col]['filing']) != '')
					$annotation_text .= "Filing details: ".$data_matrix[$row][$col]['filing']."\n";
					if($data_matrix[$row][$col]['phase_explain'] != NULL && trim($data_matrix[$row][$col]['phase_explain']) != '')
					$annotation_text .= "Phase explanation: ".$data_matrix[$row][$col]['phase_explain']."\n";
					
					$Status_List_Flg=0;
					$annotation_text2 = '';
					if($data_matrix[$row][$col]['not_yet_recruiting'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Not yet recruiting\" status: ". $data_matrix[$row][$col]['not_yet_recruiting'] ." \n";
					}
					
					if($data_matrix[$row][$col]['recruiting'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Recruiting\" status: ". $data_matrix[$row][$col]['recruiting'] ." \n";
					}
			
					if($data_matrix[$row][$col]['enrolling_by_invitation'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Enrolling by invitation\" status: ". $data_matrix[$row][$col]['enrolling_by_invitation'] ." \n";
					}
			
					if($data_matrix[$row][$col]['active_not_recruiting'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Active not recruiting\" status: ". $data_matrix[$row][$col]['active_not_recruiting'] ." \n";
					}
			
					if($data_matrix[$row][$col]['completed'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Completed\" status: ". $data_matrix[$row][$col]['completed'] ." \n";
					}
			
					if($data_matrix[$row][$col]['suspended'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Suspended\" status: ". $data_matrix[$row][$col]['suspended'] ." \n";
					}
			
					if($data_matrix[$row][$col]['terminated'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Terminated\" status: ". $data_matrix[$row][$col]['terminated'] ." \n";
					}
			
					if($data_matrix[$row][$col]['withdrawn'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Withdrawn\" status: ". $data_matrix[$row][$col]['withdrawn'] ." \n";
					}
			
					if($data_matrix[$row][$col]['available'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Available\" status: ". $data_matrix[$row][$col]['available'] ." \n";
					}
			
					if($data_matrix[$row][$col]['no_longer_available'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"No longer available\" status: ". $data_matrix[$row][$col]['no_longer_available'] ." \n";
					}
			
					if($data_matrix[$row][$col]['approved_for_marketing'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Approved for marketing\" status: ". $data_matrix[$row][$col]['approved_for_marketing'] ." \n";
					}
			
					if($data_matrix[$row][$col]['no_longer_recruiting'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"No longer recruiting\" status: ". $data_matrix[$row][$col]['no_longer_recruiting'] ." \n";
					}
			
					if($data_matrix[$row][$col]['withheld'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Withheld\" status: ". $data_matrix[$row][$col]['withheld'] ." \n";
					}
			
					if($data_matrix[$row][$col]['temporarily_not_available'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Temporarily not available\" status: ". $data_matrix[$row][$col]['temporarily_not_available'] ." \n";
					}
			
					if($data_matrix[$row][$col]['ongoing'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"On going\" status: ". $data_matrix[$row][$col]['ongoing'] ." \n";
					}
			
					if($data_matrix[$row][$col]['not_authorized'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Not authorized\" status: ". $data_matrix[$row][$col]['not_authorized'] ." \n";
					}
			
					if($data_matrix[$row][$col]['prohibited'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Prohibited\" status: ". $data_matrix[$row][$col]['prohibited'] ." \n";
					}
			
					if($data_matrix[$row][$col]['new_trials'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "New trials: ". $data_matrix[$row][$col]['new_trials'] ." \n";
					}
					
			
					if($Status_List_Flg==1 && ($er == 'now' || $er == '1 week ago'))
					$annotation_text = $annotation_text.$annotation_text2;
					
					$annotation_text = strip_tags($annotation_text);	///Strip HTML tags

					
					if(trim($annotation_text) != '')
					{
						$pdf->Annotation('', '', $area_Col_Width-4, $height-4, $annotation_text, array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Details', 'Subj' => 'Information', 'C' => array()));	
					}
					
					$pdfContent .= '<a href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. $link_part . '" target="_blank" title="'. $title .'" style="'.((trim($data_matrix[$row][$col]['color_code']) == 'FF0000' && $data_matrix[$row][$col]['count_lastchanged_value']==1) ? 'background-color:#FFFFFF;':'').' text-decoration:none;"><font style="'. (($data_matrix[$row][$col]['count_lastchanged_value']==1) ? 'color:#FF0000;':'color:#000000;').'" >'.$count_val.'</font></a>';
					
					
					
					$pdfContent  .='<br/>';
					
					if($data_matrix[$row][$col]['bomb']['value'] == 'small' || $data_matrix[$row][$col]['bomb']['value'] == 'large')
					{
						$pdfContent .= '&nbsp;<img align="right" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="images/'.$data_matrix[$row][$col]['bomb']['src'].'" style="'. $data_matrix[$row][$col]['bomb']['style'] .' vertical-align:bottom; padding-right:10px; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb']['alt'].'" />';
					}
						
					if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
					{
						$pdfContent .= '&nbsp;<img align="right" title="Filing details" src="'.$data_matrix[$row][$col]['filing_image'].'" style="width:11px; height:11px; vertical-align:bottom; cursor:pointer;" alt="Filing" />';
					}
						
					$pdfContent .= '';
					$pdf->MultiCell($area_Col_Width, $height, $pdfContent, $border, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
				}
				else
				{
					$pdf->getCellPaddings();
					if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
					{
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(245,245,245)));
						$pdf->SetFillColor(245,245,245);
					}
					else
					{
						$pdf->SetFillColor(192, 196, 254);
						$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));
					}
					$pdf->MultiCell($area_Col_Width, $height, ' ', $border, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);

				}
			}//column foreach ends
			//if total checkbox is selected
			if(isset($total_fld) && $total_fld == "1")
			{
				$pdf->SetFillColor(192, 196, 254);
        		$pdf->SetTextColor(0);
				$pdf->getCellPaddings();
				$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));	
				$pdf->MultiCell($area_Col_Width, $height, ' ', $border, $align='C', $fill=1, $ln=0, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);

			}
		$pdf->Ln($height+2);
		}//Row Foreach ends
		
		
		if(($footnotes != NULL && trim($footnotes) != '') || ($description != NULL && trim($description) != ''))
		{
			$pdf->Ln(''); $pdf->Ln('');
			$pdf->SetFillColor(192, 196, 254);
        	$pdf->SetTextColor(0);
			$pdf->getCellPaddings();
			$pdf->setCellMargins(0, 0, 0, 0);
			$border = array('mode' => 'ext', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,13,223)));
			$pdf->writeHTMLCell(137, '', '', '', '<b>Footnotes: </b><br/>'. $footnotes, $border=1, $ln=0, $fill=1, $reseth=true, $align='L', $autopadding=true);
			$pdf->writeHTMLCell(137, '', '', '', '<b>Description: </b><br/>'. $description, $border=1, $ln=0, $fill=1, $reseth=true, $align='L', $autopadding=true);
		}
		
		
						
		ob_end_clean();
		//Close and output PDF document
		$pdf->Output('Larvol_'. substr($Report_Name,0,20) .'_PDF_Report_'. date("Y-m-d_H.i.s") .'.pdf', 'D');
	}//Pdf Functions Ends
	
		
	if($_POST['dwformat']=='exceldown')
	{
	  	$name = htmlspecialchars(strlen($name)>0?$name:('report '.$id.''));
		
		// Create excel file object
		$objPHPExcel = new PHPExcel();
	
		// Set properties
		$objPHPExcel->getProperties()->setCreator(SITE_NAME);
		$objPHPExcel->getProperties()->setLastModifiedBy(SITE_NAME);
		$objPHPExcel->getProperties()->setTitle(substr($name,0,20));
		$objPHPExcel->getProperties()->setSubject(substr($name,0,20));
		$objPHPExcel->getProperties()->setDescription(substr($name,0,20));
		
		$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(8);
		$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('Verdana'); 
	
		// Build sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle(substr($name,0,20));
		//$objPHPExcel->getActiveSheet()->getStyle('A1:AA2000')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(36);
		
		
		foreach($columns as $col => $val)
		{
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
			{
				if($_POST['dwcount']=='active')
				{
					$count_val=' ('. $col_active_total[$col] .')';
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=' ('. $col_count_total[$col] .')';
				}
				else
				{
					$count_val=' ('. $col_indlead_total[$col].')';
				}
				
				$cell= num2char($col).'1';
				//TODO
				$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
				$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
				$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
								
				$objPHPExcel->getActiveSheet()->setCellValue($cell, $val);
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col].$link_part);
				
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
			$objPHPExcel->getActiveSheet()->getColumnDimension(num2char($col+1))->setWidth(18);
			$objPHPExcel->getActiveSheet()->getStyle(num2char($col+1))->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
		}
		
		foreach($rows as $row => $rval)
		{
			if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
			{
				
				if($_POST['dwcount']=='active')
				{
					$count_val=' ('. $row_active_total[$row] .')';
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=' ('. $row_count_total[$row] .')';
				}
				else
				{
					$count_val=' ('.$row_indlead_total[$row].')';
				}
				
				$cell='A'.($row+1);
				//TODO
				//$rval = (isset($rowsDisplayName[$row]) && $rowsDisplayName[$row] != '')?$rowsDisplayName[$row]:$rval;
				$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
				$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
				
				$objPHPExcel->getActiveSheet()->setCellValue($cell, $rval);
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds).$link_part); 
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
			
			foreach($columns as $col => $cval)
			{
				$cell = num2char($col) . ($row + 1);
				if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL  && $data_matrix[$row][$col]['total'] != 0)
				{
					if($_POST['dwcount']=='active')
					{
						$count_val=$data_matrix[$row][$col]['active'];
						$count_val_prev=$data_matrix[$row][$col]['active_prev'];
					}
					elseif($_POST['dwcount']=='total')
					{
						$count_val=$data_matrix[$row][$col]['total'];
						$count_val_prev=$data_matrix[$row][$col]['total_prev'];
					}
					else
					{
						$count_val=$data_matrix[$row][$col]['indlead'];
						$count_val_prev=$data_matrix[$row][$col]['indlead_prev'];
					}
					
					$styleThinRedBorderOutline = array(
						'borders' => array(
						'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000'),),
						'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000'),),
											),
						);
						
					if($data_matrix[$row][$col]['update_flag'] == 1)
					$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($styleThinRedBorderOutline);
					
					if($data_matrix[$row][$col]['count_lastchanged_value']==1)
					{
						if($data_matrix[$row][$col]['color_code'] != 'FF0000')
						$red_font['font']['color']['rgb'] = 'FF0000';
						else
						$red_font['font']['color']['rgb'] = 'FFFFFF';
						$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($red_font);
					}
					else
					{
						$red_font['font']['color']['rgb'] = '000000';
						$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($red_font);
					}
					
					$objPHPExcel->getActiveSheet()->setCellValue($cell, $count_val);
					$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col].$link_part); 
 			    	$annotation_text = '';
					if($data_matrix[$row][$col]['count_lastchanged_value']==1)
					$annotation_text .= "Count updated from: ".$count_val_prev."\n";
					if($data_matrix[$row][$col]['bomb_explain'] != NULL && trim($data_matrix[$row][$col]['bomb_explain']) != '' && ($data_matrix[$row][$col]['bomb']['value'] == 'small' || $data_matrix[$row][$col]['bomb']['value'] == 'large')) 
					$annotation_text .= "Bomb details: ".$data_matrix[$row][$col]['bomb_explain']."\n";
					if($data_matrix[$row][$col]['filing'] != NULL && trim($data_matrix[$row][$col]['filing']) != '')
					$annotation_text .= "Filing details: ".$data_matrix[$row][$col]['filing']."\n";
					if($data_matrix[$row][$col]['phase_explain'] != NULL && trim($data_matrix[$row][$col]['phase_explain']) != '')
					$annotation_text .= "Phase explanation: ".$data_matrix[$row][$col]['phase_explain']."\n";
					if($data_matrix[$row][$col]['highest_phase_lastchanged_value']==1)
					$annotation_text .= "Highest Phase updated from: Phase ".$data_matrix[$row][$col]['highest_phase_prev']."\n";
					
					
					$Status_List_Flg=0;
					$annotation_text2 = '';
					if($data_matrix[$row][$col]['not_yet_recruiting'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Not yet recruiting\" status: ". $data_matrix[$row][$col]['not_yet_recruiting'] ." \n";
					}
					
					if($data_matrix[$row][$col]['recruiting'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Recruiting\" status: ". $data_matrix[$row][$col]['recruiting'] ." \n";
					}
			
					if($data_matrix[$row][$col]['enrolling_by_invitation'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Enrolling by invitation\" status: ". $data_matrix[$row][$col]['enrolling_by_invitation'] ." \n";
					}
			
					if($data_matrix[$row][$col]['active_not_recruiting'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Active not recruiting\" status: ". $data_matrix[$row][$col]['active_not_recruiting'] ." \n";
					}
			
					if($data_matrix[$row][$col]['completed'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Completed\" status: ". $data_matrix[$row][$col]['completed'] ." \n";
					}
			
					if($data_matrix[$row][$col]['suspended'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Suspended\" status: ". $data_matrix[$row][$col]['suspended'] ." \n";
					}
			
					if($data_matrix[$row][$col]['terminated'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Terminated\" status: ". $data_matrix[$row][$col]['terminated'] ." \n";
					}
			
					if($data_matrix[$row][$col]['withdrawn'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Withdrawn\" status: ". $data_matrix[$row][$col]['withdrawn'] ." \n";
					}
			
					if($data_matrix[$row][$col]['available'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Available\" status: ". $data_matrix[$row][$col]['available'] ." \n";
					}
			
					if($data_matrix[$row][$col]['no_longer_available'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"No longer available\" status: ". $data_matrix[$row][$col]['no_longer_available'] ." \n";
					}
			
					if($data_matrix[$row][$col]['approved_for_marketing'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Approved for marketing\" status: ". $data_matrix[$row][$col]['approved_for_marketing'] ." \n";
					}
			
					if($data_matrix[$row][$col]['no_longer_recruiting'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"No longer recruiting\" status: ". $data_matrix[$row][$col]['no_longer_recruiting'] ." \n";
					}
			
					if($data_matrix[$row][$col]['withheld'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Withheld\" status: ". $data_matrix[$row][$col]['withheld'] ." \n";
					}
			
					if($data_matrix[$row][$col]['temporarily_not_available'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Temporarily not available\" status: ". $data_matrix[$row][$col]['temporarily_not_available'] ." \n";
					}
			
					if($data_matrix[$row][$col]['ongoing'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"On going\" status: ". $data_matrix[$row][$col]['ongoing'] ." \n";
					}
			
					if($data_matrix[$row][$col]['not_authorized'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Not authorized\" status: ". $data_matrix[$row][$col]['not_authorized'] ." \n";
					}
			
					if($data_matrix[$row][$col]['prohibited'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "Trials changed to \"Prohibited\" status: ". $data_matrix[$row][$col]['prohibited'] ." \n";
					}
			
					if($data_matrix[$row][$col]['new_trials'] > 0)
					{
						$Status_List_Flg=1;
						$annotation_text2 .= "New trials: ". $data_matrix[$row][$col]['new_trials'] ." \n";
					}
					
			
					if($Status_List_Flg==1 && ($er == 'now' || $er == '1 week ago'))
					$annotation_text = $annotation_text.$annotation_text2;
					
					$annotation_text = strip_tags($annotation_text);	///Strip HTML tags
					
					$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip(substr($annotation_text,0,255) );
					
					if($data_matrix[$row][$col]['exec_bomb']['src'] != '' && $data_matrix[$row][$col]['exec_bomb']['src'] != NULL && $data_matrix[$row][$col]['exec_bomb']['src'] !='new_square.png')
					{
						$objDrawing = new PHPExcel_Worksheet_Drawing();
						$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						$objDrawing->setOffsetX(100);
						$objDrawing->setOffsetY(0);
						$objDrawing->setPath('images/'.$data_matrix[$row][$col]['exec_bomb']['src']);
						$objDrawing->setHeight(15);
						$objDrawing->setWidth(15); 
						$objDrawing->setDescription($data_matrix[$row][$col]['bomb']['title']);
						$objDrawing->setCoordinates($cell);
					}
					
					$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
					$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->getStartColor()->setRGB($data_matrix[$row][$col]['color_code']);
					$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
				}
				else
				{
					/////// To avoid product name overflow on side column when, first are columns is empty - putting 0 value with white color
					if($col == 1)
					{
						$white_font['font']['color']['rgb'] = 'FFFFFF';
						$objPHPExcel->getActiveSheet()->getStyle($cell)->applyFromArray($white_font);
						$objPHPExcel->getActiveSheet()->setCellValue($cell, '0');
					}
				}
			}
		}
		
		if(isset($total_fld) && $total_fld == "1")
		{
			if($_POST['dwcount']=='active')
			{
				$count_val=$active_total;
			}
			elseif($_POST['dwcount']=='total')
			{
				$count_val=$count_total;
			}
			else
			{
				$count_val=$indlead_total;
			}
					
			$cell = num2char(count($columns)+1).'1';
			$objPHPExcel->getActiveSheet()->setCellValue($cell, $count_val);
			$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds).$link_part);
			$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip);
		}
		
		$row = count($rows) + 1;
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, '');
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Report name:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, substr($Report_Name,0,250));
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Display Mode:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $tooltip);
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Footnotes:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $footnotes);
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Description:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $description);
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Runtime:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row++, date("Y-m-d H:i:s", $now));
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . $row, 'Legend:');
		$col = 'A';
		//get search results
		$phases = array('N/A', 'Phase 0', 'Phase 0/Phase 1', 'Phase 1', 'Phase 1a', 'Phase 1b', 'Phase 1a/1b', 'Phase 1c', 'Phase 1/Phase 2', 'Phase 1b/2', 
						'Phase 1b/2a', 'Phase 2','Phase 2a', 'Phase 2a/2b', 'Phase 2a/b', 'Phase 2b', 'Phase 2/Phase 3', 'Phase 2b/3','Phase 3', 'Phase 3a', 
						'Phase 3b', 'Phase 3/Phase 4', 'Phase 3b/4', 'Phase 4');
		$phasenums = array(); foreach($phases as $k => $p)  $phasenums[$k] = str_ireplace(array('phase',' '),'',$p);
		$phase_legend_nums = array('N/A', '0', '0/1', '1', '1/2', '2', '2/3', '3', '3/4', '4');
		//$p_colors = array('DDDDDD', 'BBDDDD', 'AADDEE', '99DDFF', 'DDFF99', 'FFFF00', 'FFCC00', 'FF9900', 'FF7711', 'FF4422');
		$p_colors = array('BFBFBF', '00CCFF', '99CC00', '99CC00', '99CC00', '99CC00', '99CC00', '99CC00', 'FFFF00', 'FFFF00', 'FFFF00', 'FFFF00', 'FFFF00', 'FFFF00', 
		'FFFF00', 'FFFF00', 'FF9900', 'FF9900', 'FF9900', 'FF9900', 'FF9900', 'FF0000', 'FF0000', 'FF0000');
		$phase_legend_colors = array('BFBFBF', '00CCFF', '99CC00', '99CC00', 'FFFF00', 'FFFF00', 'FF9900', 'FF9900', 'FF0000', 'FF0000');
	
		foreach($p_colors as $key => $color)
		{
			$cell = ++$col . $row;
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
		header('Content-Disposition: attachment;filename="Larvol_' . substr($Report_Name,0,20) . '_Excel_Report_' . date('Y-m-d_H.i.s') . '.xlsx"');
			
		header("Content-Transfer-Encoding: binary ");
		$objWriter->save('php://output');
		@flush();
	} //Excel Function Ends
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
	$res = mysql_query($query) or die('Bad SQL query getting user for master heatmap report id');
	$res = mysql_fetch_assoc($res);
	if($res === false) return;	///Replaced "Continue" by "Return" cause continue was giving "Cannot break/continue 1 level" error when report deleted and continue should only be used to escape through loop not function
	if(count($res)==0){ die('Not found.'); }
	$rptu = $res['user'];
	$shared = $res['shared'];
	if($rptu !== NULL && $rptu != $db->user->id && !$shared) return;

	// "Copy into new" is the exception for non-admins sending POSTdata about global reports
	if(isset($_POST['reportcopy']))
	{
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		$query = 'SELECT name,footnotes,description,category FROM rpt_masterhm WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query getting old data');
		$res = mysql_fetch_array($res);
		if($res === false) return; //not found

		$oldname = mysql_real_escape_string($res['name']);
		$footnotes = mysql_real_escape_string($res['footnotes']);
		$description = mysql_real_escape_string($res['description']);
		$category = mysql_real_escape_string($res['category']);
		$query = 'INSERT INTO rpt_masterhm SET name="Copy of ' . (strlen($oldname) ? $oldname : ('report '.$id)) . '",user='
				. $db->user->id . ',footnotes="' . $footnotes . '",description="' . $description . '"' . ',category="'.$category.'"';
				
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
	$types = array('product','area');
	
	if(($rptu === NULL && $db->user->userlevel != 'user') || ($rptu !== NULL && $rptu == $db->user->id)) 	///Restriction on editing
	{
		
		foreach($types as $t)
		{
			$maxvar = 'max' . $t;
			if(isset($_POST['add'.$t]) || isset($_POST['del'.$t]))
			{
				$query = 'SELECT MAX(num) AS "prevnum" FROM rpt_masterhm_headers WHERE report=' . $id
						. ' AND type="' . $t . '" GROUP BY report LIMIT 1';
				$res = mysql_query($query) or die('Bad SQL query getting max ' . $t . ' number');
				$res = mysql_fetch_array($res);
				if($res !== false) $$maxvar = $res['prevnum'];
			}
			if(isset($_POST['add'.$t]))
			{
				$query = 'INSERT INTO rpt_masterhm_headers SET report=' . $id . ',type="' . $t . '",num=' . ($$maxvar + 1);
				mysql_query($query) or die('Bad SQL Query adding ' . $t);
			}
			if(isset($_POST['del'.$t]))
			{
				$query = 'DELETE FROM rpt_masterhm_headers WHERE report=' . $id . ' AND num=' . $$maxvar . ' AND type="' . $t . '" LIMIT 1';
				mysql_query($query) or die('Bad SQL Query removing ' . $t);
				}
		}
	}
	
	
	if(isset($_POST['reportsave']) || $_POST['reportsave_flg']==1)
	{
		$footnotes = mysql_real_escape_string($_POST['footnotes']);
		$description = mysql_real_escape_string($_POST['description']);
		
		if(isset($_POST['own']) && $_POST['own'] == 'global')
		{
			$owner='NULL'; $shared=0;
		} else if(isset($_POST['own']) && $_POST['own'] == 'shared')
		{
			$owner=$db->user->id; $shared=1;
		} else
		{
			$owner=$db->user->id; $shared=0;
		}
		
		if(isset($_POST['total']) && $_POST['total']==1)
		$total_col=1;
		else
		$total_col=0;
		
		if(isset($_POST['dtt']) && $_POST['dtt']==1)
		$dtt=1;
		else
		$dtt=0;
		
		$category = mysql_real_escape_string($_POST['reportcategory']);
		
		if(($rptu === NULL && $db->user->userlevel != 'user') || ($rptu !== NULL && $rptu == $db->user->id)) 	///Restriction on report saving
		{
		
			$originDT_query = 'SELECT `name`, `user`, `footnotes`, `description`, `category`, `shared`, `total`, `dtt`, `display_name` FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
			$originDT=mysql_query($originDT_query) or die ('Bad SQL Query getting Original Master Header Table Information Before Updating.<br/>'.$query);
			$originDT = mysql_fetch_array($originDT);
		
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
						if($t == 'area')
						$display_name=mysql_real_escape_string($_POST['areas_display'][$num]);
						else
						$display_name='NULL';
							
						$category=mysql_real_escape_string($_POST['category_'.$t][$num]);
						
						$query = "select id from " . $t . "s where name='" . mysql_real_escape_string($header) . "' ";
						$row = mysql_fetch_assoc(mysql_query($query)) or die('Bad SQL Query getting ' . $t . ' names ');
					
						$originDT_query = 'SELECT `type_id`, `display_name`, `category` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND num=' . $num . ' AND type="' . $t . '" LIMIT 1';
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
		
		if(isset($_POST['cell_prod']) && !empty($_POST['cell_prod']))
		{
			foreach($_POST['cell_prod'] as $row => $data)
			foreach($data as $col => $value)
			{
				$prod=$_POST['cell_prod'][$row][$col];
				$area=$_POST['cell_area'][$row][$col];
				$filing=trim(mysql_real_escape_string($_POST['filing'][$row][$col]));
				$bomb=trim($_POST['bomb'][$row][$col]);
				$filing_presence=$_POST['filing_presence'][$row][$col];
				$phaseexp_presence=$_POST['phaseexp_presence'][$row][$col];
				$bomb_explain=mysql_real_escape_string($_POST['bomb_explain'][$row][$col]);
				$phase_explain=trim(mysql_real_escape_string($_POST['phase_explain'][$row][$col]));
				$phase4_val=mysql_real_escape_string($_POST['phase4_val'][$row][$col]);
				
				$up_time=date('Y-m-d H:i:s', $now);
				
				$originDT_query = "SELECT `bomb`, `bomb_explain`, `filing`, `phase_explain`, `phase4_override` FROM `rpt_masterhm_cells` WHERE `product` = $prod AND `area` = $area";
				$originDT=mysql_query($originDT_query) or die ('Bad SQL Query getting Original Bomb and Filing Information Before Updating.<br/>'.$query);
				$originDT = mysql_fetch_array($originDT);
				
				$change_flag=0;
				
				$query = "UPDATE `rpt_masterhm_cells` set ";
				
				if($bomb != $originDT['bomb'] || $_POST['bomb_explain'][$row][$col] != $originDT['bomb_explain'])
				{
					$query .="`bomb` = '$bomb', `bomb_explain` = '$bomb_explain', `bomb_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				
				if((trim($filing) == '' && $originDT['filing'] == NULL) && $filing_presence == 1)
				{
					$query .="`filing` = ' ', `filing_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				else if((trim($filing) != trim($originDT['filing'])) && $filing_presence == 1)
				{
					$query .="`filing` = '$filing', `filing_lastchanged`= '$up_time', ";
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
					$query .="`phase_explain` = '$phase_explain', `phase_explain_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				else if($originDT['phase_explain'] != NULL && $phaseexp_presence == 0)
				{
					$query .="`phase_explain` = NULL, `phase_explain_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				
				if(trim($phase4_val) != trim($originDT['phase4_override']))
				{
					$query .="`phase4_override` = '$phase4_val', `phase4_override_lastchanged`= '$up_time', ";
					$change_flag=1;
				}
				
				$query .= "`last_update`= '$up_time' WHERE `product` = $prod AND `area` = $area";
				
				if($change_flag) ///If there is change then only execute query
				mysql_query($query) or die ('Bad SQL Query updating Bomb and Filing Information.<br/>'.$query);
			}
		}
	}
	
	

	if(($rptu === NULL && $db->user->userlevel == 'user') || ($rptu !== NULL && $rptu != $db->user->id)) return;	///Restriction on report saving
	
	if(isset($_POST['move_row_down']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_row_down'] = array_keys($_POST['move_row_down']);
		$_POST['move_row_down'] = $_POST['move_row_down'][0];
		
		$current_row=$_POST['move_row_down'];
		$next_row=$_POST['move_row_down']+1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_row AND type = 'product' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_row_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'product' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$current_row' WHERE num = '$next_row' AND type = 'product' AND id <> '$current_row_id' AND report = '$id'";
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
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_row AND type = 'product' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_row_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'product' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$current_row' WHERE num = '$next_row' AND type = 'product' AND id <> '$current_row_id' AND report = '$id'";
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
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_column AND type = 'area' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_column_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'area' AND report = '$id'";
		$query = mysql_query($sql);
		$sql 
		= "UPDATE `rpt_masterhm_headers` SET num = '$current_column' WHERE num = '$next_column' AND type = 'area' AND id <> '$current_column_id' AND report = '$id'";
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
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_column AND type = 'area' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_column_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'area' AND report = '$id'";
		$query = mysql_query($sql);
		$sql 
		= "UPDATE `rpt_masterhm_headers` SET num = '$current_column' WHERE num = '$next_column' AND type = 'area' AND id <> '$current_column_id' AND report = '$id'";
		$query = mysql_query($sql);
	}
	
	if((isset($_POST['deleterow']) && is_array($_POST['deleterow'])) || (isset($_POST['deletecol']) && is_array($_POST['deletecol'])))
	{	
		mysql_query('BEGIN');
		if(isset($_POST['deleterow']) && is_array($_POST['deleterow']))
		{
			foreach($_POST['deleterow'] as $delRow=>$stat)
			{
				//delete the row
				$query = "DELETE FROM `rpt_masterhm_headers` WHERE report= $id AND `num` = $delRow AND `type` = 'product' ";
				mysql_query($query) or die ('Bad SQL Query removing column.');
				
			}
			//after all delete rows reorder rows
			$query = "SELECT num FROM `rpt_masterhm_headers` WHERE `report` = $id AND `type` = 'product' ORDER BY `num` ASC ";
			$result = mysql_query($query);
			$cnt = mysql_num_rows($result);
			if($cnt>0)
			{
				$i=1;
				while($row = mysql_fetch_assoc($result))
				{
					$query = "UPDATE `rpt_masterhm_headers` set `num` = $i WHERE `report` = $id and `type` = 'product' AND `num` = ".$row['num'];
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
				$query = "DELETE FROM `rpt_masterhm_headers` WHERE `report`= $id AND `num` = $delCol AND `type` = 'area' ";
				mysql_query($query) or die ('Bad SQL Query removing column.');
			}	
			//after all delete columns reorder columns
			$query = "SELECT num FROM `rpt_masterhm_headers` WHERE `report` = $id AND `type` = 'area' ORDER BY `num` ASC";
			$result = mysql_query($query);
			$cnt = 0;
			$cnt = mysql_num_rows($result);
			if($cnt>0)
			{
				$i=1;
				while($row = mysql_fetch_assoc($result))
				{
					$query = "UPDATE `rpt_masterhm_headers` set `num` = $i WHERE `report` = $id and `type` = 'area' AND `num` = ".$row['num'];
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

		$types = array('product','area');
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
			if($ru == $db->user->id || ($db->user->userlevel != 'user' && $ru === NULL))
				mysql_query('DELETE FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1') or die('Bad SQL query deleting master heatmap report');
		}
	}
}
?>