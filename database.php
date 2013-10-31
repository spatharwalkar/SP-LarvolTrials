<?php
//connect to Sphinx
if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
require_once('db.php');

if(!$db->loggedIn() || ($db->user->userlevel!='root' && $db->user->userlevel!='admin'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require('header.php');
/**************/
ignore_user_abort(true);
// single trial refresh new schema
if (isset($_POST['nt_id'])) 
{
	require_once('include.import_new.php');
	require_once('nct_common.php');
	require_once('include.import.history_new.php');
    scrape_history($_POST['nt_id']);
	return;
}
// EudraCT trial refresh
if (isset($_POST['eudract_id'])) 
{
	require_once('include.import_new.php');
	require_once('eudract_common.php');
	require_once('include.import.eudract.history.php');
	$ids=getEudraIDs($_POST['eudract_id']);
	foreach ($ids as $key => $value) 
	{
		scrape_history($key , $value);
	}
	return;
}
// single trial refresh old schema
if (isset($_POST['ot_id'])) 
{
	require_once('include.import.php');
	require_once('nct_common.php');
	require_once('include.import.history.php');
    scrape_history($_POST['ot_id']);
	return;
}

//fetch from source new schema
if (isset($_POST['scraper_n']) and isset($_POST['days_n'])) 
{
	require_once('include.import_new.php');
	require_once('nct_common.php');
	require_once('include.import.history_new.php');
	require_once($_POST['scraper_n']);
	$update_id = 3;
	run_incremental_scraper($_POST['days_n']);
	return ;
}

//fetch Eudract from source 
if (isset($_POST['e_scraper_n']) and isset($_POST['days_n'])) 
{
	require_once('include.import_new.php');
	require_once('eudract_common.php');
	require_once('include.import.eudract.history.php');  
	require_once($_POST['e_scraper_n']);
	
	if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
	require_once('db.php');
	require_once('include.search.php');
	require_once('include.util.php');
	require_once('preindex_trial.php');
	require_once('db.php');
	require_once('include.import_new.php');
	require_once('eudract_common.php');
	require_once('include.import.eudract.history.php');
	ini_set('max_execution_time', '36000'); //10 hours
	ignore_user_abort(true);
	$update_id = 1;
	run_incremental_scraper($_POST['days_n']);
	return ;
}

//fetch from source old schema
if (isset($_POST['scraper_o']) and isset($_POST['days_o'])) 
{
require_once($_POST['scraper_o']);
run_incremental_scraper($_POST['days_o']);
return ;
}


// FULL refresh new schema
if (isset($_POST['nall']) and $_POST['nall']=='ALL') 
{
	echo '
	<form name="mode" action="fetch_nct_fullhistory_all_new.php" method="POST">
	<div align="center"><br><br><br><br><hr />
	<input type="radio" name="mode" value="db" checked> Use database for validating NCTIDs 
	&nbsp; &nbsp; &nbsp;
	<input type="radio" name="mode" value="web"> Use clinicaltrials.gov for validating NCTIDs
	&nbsp; &nbsp; &nbsp;
	<input type="submit" name="submit" value="Start Import" />
	<hr />
	</div>
	</form>'
	;
	exit;
}

// FULL refresh EudraCT 
if (isset($_POST['e_nall']) and $_POST['e_nall']=='ALL') 
{
	require_once('fetch_eudract_fullhistory_all.php');
	return;
}

// FULL refresh old schema
if (isset($_POST['oall']) and $_POST['oall']=='ALL') 
{
	echo '
		<form name="mode" action="fetch_nct_fullhistory_all.php" method="POST">
	<div align="center"><br><br><br><br><hr />
	<input type="radio" name="mode" value="db" checked> Use database for validating NCTIDs 
	&nbsp; &nbsp; &nbsp;
	<input type="radio" name="mode" value="web"> Use clinicaltrials.gov for validating NCTIDs
	&nbsp; &nbsp; &nbsp;
	<input type="submit" name="submit" value="Start Import" />
	<hr />
	</div>
	</form>'
	;
	exit;
}

// single trial REMAP using NCTID
if (isset($_POST['t_id'])) 
{
	require_once('remap_trials.php');
	remaptrials($_POST['t_id'],null,null);
	return;
}
// single trial REMAP using EUDRACTID
if (isset($_POST['e_t_id'])) 
{
	require_once('remap_trials.php');
	remaptrials($_POST['e_t_id'],null,null);
	return;
}
// single trial REMAP using LARVOLID
if (isset($_POST['l_id'])) 
{
	require_once('remap_trials.php');
	if(strpos($_POST['l_id'], ",")===false) 
	{
		remaptrials(null,$_POST['l_id'],null);
	}
	else
	{
		$listOfIds=explode( ',' , $_POST['l_id'] );
		if(!is_array($listOfIds)) return false;
		foreach ($listOfIds as $larvolId)
		{
			remaptrials(null,$larvolId,null);
		}
	}
	
	return;
}
// single trial INVESTIGATOR DETECTION using LARVOLID
if (isset($_POST['inv_l_id'])) 
{
	require_once('detect_investigator.php');
	if(strpos($_POST['inv_l_id'], ",")===false) 
	{
		detect_inv(null,$_POST['inv_l_id'],null);
	}
	else
	{
		$listOfIds=explode( ',' , $_POST['inv_l_id'] );
		if(!is_array($listOfIds)) return false;
		foreach ($listOfIds as $larvolId)
		{
			detect_inv(null,$larvolId,null);
		}
	}
	
	return;
}
// single trial INVESTIGATOR DETECTION using NCTID
if (isset($_POST['inv_t_id'])) 
{
	require_once('detect_investigator.php');
	detect_inv($_POST['inv_t_id'],null,null);
	return;
}
// INVESTIGATOR DETECTION for ALL trials.
if (isset($_POST['detect_source'])) 
{
	require_once('detect_investigator.php');
	detect_inv(null,null,$_POST['detect_source']);
	return;
}
// REMAP a source 
if (isset($_POST['map_source'])) 
{
	require_once('remap_trials.php');
	remaptrials(null,null,$_POST['map_source']);
	return;
}
// PREINDEX a trial
if (isset($_POST['i_id'])) 
{
	require_once('preindex_trial.php');
	tindex(padnct($_POST['i_id']),'products');
	tindex(padnct($_POST['i_id']),'areas');
	return;
}
// PREINDEX a product
if (isset($_POST['p_id'])) 
{
	require_once('preindex_trial.php');
	tindex(NULL,'products',NULL,NULL,NULL,$_POST['p_id']);
	return;
}
// PREINDEX an area
if (isset($_POST['a_id'])) 
{
	require_once('preindex_trial.php');
	tindex(NULL,'areas',NULL,NULL,NULL,$_POST['a_id']);
	return;
}
// PREINDEX ALL
if (isset($_POST['index_all']) and $_POST['index_all']=='ALL') 
{
	require_once('preindex_trial.php');
	//index all products
	echo '<br><b>Indexing ALL products...<br></b>';
	tindex(NULL,'products',NULL,NULL,NULL,NULL);
	echo '<br>Done. <br>';

	//index all areas
	echo '<br><b>Indexing ALL areas...</b><br>';
	tindex(NULL,'areas',NULL,NULL,NULL,NULL);
	echo '<br>Indexed all areas. <br>';
	return;
}
// RECALCULATE a product
if (isset($_POST['prod_id'])) 
{
	require_once('calculate_hm_cells.php');
	$parameters=array();
	$parameters['entity1']=$_POST['prod_id']; // for product
	calc_cells($parameters);
	return;
}
// RECALCULATE a area
if (isset($_POST['area_id'])) 
{
	require_once('calculate_hm_cells.php');
	$parameters=array();
	$parameters['entity2']=$_POST['area_id']; // for product
	calc_cells($parameters);
	return;
}
// RECALCULATE all cells
if (isset($_POST['recalculate_all'])) 
{
	require_once('calculate_hm_cells.php');
	calc_cells(NULL);   // for all
	return;
}
// Update UPM status values
if (isset($_POST['upm_status']) and $_POST['upm_status']=="1") 
{
	require_once('upm_trigger.php');
	echo '<br><br>Recalculating UPM status values (for <b>all records.</b>)<br><br>';
	if(!fire_upm_trigger()) echo '<br><b>Could complete Updating UPM status values, there was an error.<br></b>';
	else 
	{
		echo '<br><br><b>All done.</b><br><br>';
	}
	return;
}
if (isset($_POST['upm_status']) and $_POST['upm_status']=="2") 
{
	require_once('upm_trigger.php');
	echo '<br><br>Recalculating UPM status values (for <b> end_date in the past</b>)<br><br>';
	if(!fire_upm_trigger_dt()) echo '<br><b>Could complete Updating UPM status values, there was an error.<br></b>';
	else 
	{
		echo '<br><br><b>All done.</b><br><br>';
	}
	return;
}
if (isset($_POST['upm_status']) and $_POST['upm_status']=="3") 
{
	$st=mysql_real_escape_string($_POST['status']);
	require_once('upm_trigger.php');
	echo '<br><br>Recalculating UPM status values (for status=<b>' . $st . '</b>)<br><br>';
	if(!fire_upm_trigger_st($st)) echo '<br><b>Could complete Updating UPM status values, there was an error.<br></b>';
	else 
	{
		echo '<br><br><b>All done.</b><br><br>';
	}
	return;
}
if (isset($_POST['li_s']) and $_POST['li_s']=="1") 
{
	require_once('fetch_li_products.php');
	echo '<br><br>Importing all products from LI<br><br>';
	fetch_li_products("0");
	return;
}
if (isset($_POST['li_s']) and $_POST['li_s']=="2" and isset($_POST['updt_since']) ) 
{
	$upds=strtotime(mysql_real_escape_string($_POST['updt_since']));
	if(!isset($upds) or empty($upds)) 
	{
		echo '<br> Invalid date entered:'.$litd.' Unable to proceed. ';
		return false;
	}
	require_once('fetch_li_products.php');
	echo '<br><br>Importing products updated since '.$_POST['updt_since'].' from LI<br><br>';
	fetch_li_products($upds);
	return;
}

if (isset($_POST['li_s']) and $_POST['li_s']=="3" and ( isset($_POST['p_lt_id']) or isset($_POST['p_li_id']) ) ) 
{
	if( isset($_POST['p_li_id']) and !empty($_POST['p_li_id']) ) $liid=mysql_real_escape_string($_POST['p_li_id']);
	elseif(isset($_POST['p_lt_id']))
	{
		$litd=mysql_real_escape_string($_POST['p_lt_id']);
		
		$query = 'select lI_id from products where id="'.$litd.'" limit 1' ;
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
					$res = mysql_fetch_array($res) ;
					if(isset($res['lI_id']))
						$liid = $res['lI_id'];
					else
					{
						echo '<br> Could not find id '.$litd.' in products table.  ';
						return false;
					}
	}
	require_once('fetch_li_products.php');
	echo '<br><br>Importing product with LI id:'.$liid.'<br><br>';
	fetch_li_product_individual($liid);
	return;
}

if (isset($_POST['li_inst']) and $_POST['li_inst']=="1") 
{
	require_once('fetch_li_institutions.php');
	echo '<br><br>Importing all institutions from LI<br><br>';
	fetch_li_institutions("0");
	return;
}
if (isset($_POST['li_inst']) and $_POST['li_inst']=="2" and isset($_POST['inst_updt_since']) ) 
{
	$upds=strtotime(mysql_real_escape_string($_POST['inst_updt_since']));
	if(!isset($upds) or empty($upds)) 
	{
		echo '<br> Invalid date entered:'.$litd.' Unable to proceed. ';
		return false;
	}
	require_once('fetch_li_institutions.php');
	echo '<br><br>Importing institutions updated since '.$_POST['inst_updt_since'].' from LI<br><br>';
	fetch_li_institutions($upds);
	return;
}

if (isset($_POST['li_inst']) and $_POST['li_inst']=="3" and ( isset($_POST['inst_lt_id']) or isset($_POST['inst_li_id']) ) ) 
{
	if( isset($_POST['inst_li_id']) and !empty($_POST['inst_li_id']) ) $liid=mysql_real_escape_string($_POST['inst_li_id']);
	elseif(isset($_POST['inst_lt_id']))
	{
		$litd=mysql_real_escape_string($_POST['inst_lt_id']);
		
		$query = 'select lI_id from institutions where id="'.$litd.'" limit 1' ;
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
					$res = mysql_fetch_array($res) ;
					if(isset($res['lI_id']))
						$liid = $res['lI_id'];
					else
					{
						echo '<br> Could not find id '.$litd.' in institutions table.  ';
						return false;
					}
	}
	require_once('fetch_li_institutions.php');
	echo '<br><br>Importing institutions with LI id:'.$liid.'<br><br>';
	fetch_li_institution_individual($liid);
	return;
}

if (isset($_POST['li_moa']) and $_POST['li_moa']=="1") 
{
	require_once('fetch_li_moas.php');
	echo '<br><br>Importing all moas from LI<br><br>';
	fetch_li_moas("0");
	return;
}
if (isset($_POST['li_moa']) and $_POST['li_moa']=="2" and isset($_POST['moa_updt_since']) ) 
{
	$upds=strtotime(mysql_real_escape_string($_POST['moa_updt_since']));
	if(!isset($upds) or empty($upds)) 
	{
		echo '<br> Invalid date entered:'.$litd.' Unable to proceed. ';
		return false;
	}
	require_once('fetch_li_moas.php');
	echo '<br><br>Importing moas updated since '.$_POST['moa_updt_since'].' from LI<br><br>';
	fetch_li_moas($upds);
	return;
}

if (isset($_POST['li_moa']) and $_POST['li_moa']=="3" and ( isset($_POST['moa_lt_id']) or isset($_POST['moa_li_id']) ) ) 
{
	if( isset($_POST['moa_li_id']) and !empty($_POST['moa_li_id']) ) $liid=mysql_real_escape_string($_POST['moa_li_id']);
	elseif(isset($_POST['moa_lt_id']))
	{
		$litd=mysql_real_escape_string($_POST['moa_lt_id']);
		
		$query = 'select lI_id from moas where id="'.$litd.'" limit 1' ;
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
					$res = mysql_fetch_array($res) ;
					if(isset($res['lI_id']))
						$liid = $res['lI_id'];
					else
					{
						echo '<br> Could not find id '.$litd.' in moas table.  ';
						return false;
					}
	}
	require_once('fetch_li_moas.php');
	echo '<br><br>Importing moas with LI id:'.$liid.'<br><br>';
	fetch_li_moa_individual($liid);
	return;
}

if (isset($_POST['li_moacategory']) and $_POST['li_moacategory']=="1") 
{
	require_once('fetch_li_moacategories.php');
	echo '<br><br>Importing all moa categories from LI<br><br>';
	fetch_li_moacategories("0");
	return;
}
if (isset($_POST['li_moacategory']) and $_POST['li_moacategory']=="2" and isset($_POST['moacategory_updt_since']) ) 
{
	$upds=strtotime(mysql_real_escape_string($_POST['moacategory_updt_since']));
	if(!isset($upds) or empty($upds)) 
	{
		echo '<br> Invalid date entered:'.$litd.' Unable to proceed. ';
		return false;
	}
	require_once('fetch_li_moacategories.php');
	echo '<br><br>Importing moa categories updated since '.$_POST['moacategory_updt_since'].' from LI<br><br>';
	fetch_li_moacategories($upds);
	return;
}

if (isset($_POST['li_moacategory']) and $_POST['li_moacategory']=="3" and ( isset($_POST['moacategory_lt_id']) or isset($_POST['moacategory_li_id']) ) ) 
{
	if( isset($_POST['moacategory_li_id']) and !empty($_POST['moacategory_li_id']) ) $liid=mysql_real_escape_string($_POST['moacategory_li_id']);
	elseif(isset($_POST['moacategory_lt_id']))
	{
		$litd=mysql_real_escape_string($_POST['moacategory_lt_id']);
		
		$query = 'select lI_id from `entities` where id="'.$litd.'" and `class`="MOA_Category" limit 1' ;print $query;
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
					$res = mysql_fetch_array($res) ;
					if(isset($res['lI_id']))
						$liid = $res['lI_id'];
					else
					{
						echo '<br> Could not find id '.$litd.' in moa categories table.  ';
						return false;
					}
	}
	require_once('fetch_li_moacategories.php');
	echo '<br><br>Importing moa categories with LI id:'.$liid.'<br><br>';
	fetch_li_moacategory_individual($liid);
	return;
}

if (isset($_POST['li_disease']) and $_POST['li_disease']=="1") 
{
	require_once('fetch_li_diseases.php');
	echo '<br><br>Importing all diseases from LI<br><br>';
	fetch_li_diseases("0");
	return;
}
if (isset($_POST['li_disease']) and $_POST['li_disease']=="2" and isset($_POST['disease_updt_since']) ) 
{
	$upds=strtotime(mysql_real_escape_string($_POST['disease_updt_since']));
	if(!isset($upds) or empty($upds)) 
	{
		echo '<br> Invalid date entered:'.$litd.' Unable to proceed. ';
		return false;
	}
	require_once('fetch_li_diseases.php');
	echo '<br><br>Importing diseases updated since '.$_POST['disease_updt_since'].' from LI<br><br>';
	fetch_li_diseases($upds);
	return;
}

if (isset($_POST['li_disease']) and $_POST['li_disease']=="3" and ( isset($_POST['disease_lt_id']) or isset($_POST['disease_li_id']) ) ) 
{
	if( isset($_POST['disease_li_id']) and !empty($_POST['disease_li_id']) ) $liid=mysql_real_escape_string($_POST['disease_li_id']);
	elseif(isset($_POST['disease_lt_id']))
	{
		$litd=mysql_real_escape_string($_POST['disease_lt_id']);
		
		$query = 'select lI_id from `entities` where id="'.$litd.'" and `class` = "Disease" limit 1' ;
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
					$res = mysql_fetch_array($res) ;
					if(isset($res['lI_id']))
						$liid = $res['lI_id'];
					else
					{
						echo '<br> Could not find id '.$litd.' in entities table.  ';
						return false;
					}
	}
	require_once('fetch_li_diseases.php');
	echo '<br><br>Importing diseases with LI id:'.$liid.'<br><br>';
	fetch_li_disease_individual($liid);
	return;
}


//Import Mesh diseases
if (isset($_POST['import_mesh_diseases']) and $_POST['import_mesh_diseases']=="YES") 
{
	require_once('fetch_diseases.php');
	return;
}

//Import disease category
if (isset($_POST['import_disease_category']) and $_POST['import_disease_category']=="YES")
{
	require_once('fetch_disease_categories.php');
	return;
}

//Import Mesh diseases
if (isset($_POST['import_industries']) and $_POST['import_industries']=="YES")
{
	require_once('fetch_industries.php');
	return;
}

//Recalcualte Investigators (All)
if (isset($_POST['recalc_investigators']) and $_POST['recalc_investigators']=="YES")
{
	require_once('calc_Inv_all.php');
	return;
}

//Import Therapeutic Area
if (isset($_POST['li_therapeuticarea']) and $_POST['li_therapeuticarea']=="1") 
{
	require_once('fetch_li_therapeuticareas.php');
	echo '<br><br>Importing all therapeutic areas from LI<br><br>';
	fetch_li_therapeuticareas("0");
	return;
}
if (isset($_POST['li_therapeuticarea']) and $_POST['li_therapeuticarea']=="2" and isset($_POST['therapeuticarea_updt_since']) ) 
{
	$upds=strtotime(mysql_real_escape_string($_POST['therapeuticarea_updt_since']));
	if(!isset($upds) or empty($upds)) 
	{
		echo '<br> Invalid date entered:'.$litd.' Unable to proceed. ';
		return false;
	}
	require_once('fetch_li_therapeuticareas.php');
	echo '<br><br>Importing therapeutic areas updated since '.$_POST['therapeuticarea_updt_since'].' from LI<br><br>';
	fetch_li_therapeuticareas($upds);
	return;
}

if (isset($_POST['li_therapeuticarea']) and $_POST['li_therapeuticarea']=="3" and ( isset($_POST['therapeuticarea_lt_id']) or isset($_POST['therapeuticarea_li_id']) ) ) 
{
	if( isset($_POST['therapeuticarea_li_id']) and !empty($_POST['therapeuticarea_li_id']) ) $liid=mysql_real_escape_string($_POST['therapeuticarea_li_id']);
	elseif(isset($_POST['therapeuticarea_lt_id']))
	{
		$litd=mysql_real_escape_string($_POST['therapeuticarea_lt_id']);
		
		$query = 'select lI_id from `entities` where id="'.$litd.'" and `class` = "Therapeutic_Area" limit 1' ;
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						global $logger;
						$logger->error($log);
						echo $log;
						return false;
					}
					$res = mysql_fetch_array($res) ;
					if(isset($res['lI_id']))
						$liid = $res['lI_id'];
					else
					{
						echo '<br> Could not find id '.$litd.' in entities table.  ';
						return false;
					}
	}
	require_once('fetch_li_therapeuticareas.php');
	echo '<br><br>Importing Therapeutic Areas with LI id:'.$liid.'<br><br>';
	fetch_li_therapeuticarea_individual($liid);
	return;
}

/****************************/
echo(editor());
echo('</body></html>');
//return html for item editor
function editor()
{
	global $db;
//	if(!isset($_GET['id'])) return;
		$chkd="1";
		$id=1;
	//SCRAPER - NEW SCHEMA
	$out = '<style type="text/css">
		  formset {
		    padding:1em;
		    width:10em;
			}
			label, .show {display:block}
			.hide {display:none}
		</style>
	
	<script type="text/javascript">
		  toggles = new Array();
		  toggles1 = new Array();
		  toggles2 = new Array();
		  toggles3 = new Array();
		  toggles4 = new Array();
		  toggles5 = new Array();
		  toggles6 = new Array();
		  toggles7 = new Array();
		  if (document.getElementById) onload = function () {
		    document.getElementById (\'more\').className = \'hide\';
			document.getElementById (\'p_up_dt\').className = \'hide\';
			document.getElementById (\'p_sing\').className = \'hide\';
		    document.getElementById (\'inst_sing\').className = \'hide\';
			document.getElementById (\'inst_up_dt\').className = \'hide\';
			document.getElementById (\'moa_sing\').className = \'hide\';
			document.getElementById (\'moa_up_dt\').className = \'hide\';
			document.getElementById (\'moacategory_sing\').className = \'hide\';
			document.getElementById (\'moacategory_up_dt\').className = \'hide\';
			document.getElementById (\'disease_sing\').className = \'hide\';
			document.getElementById (\'disease_up_dt\').className = \'hide\';
			document.getElementById (\'therapeuticarea_sing\').className = \'hide\';
			document.getElementById (\'therapeuticarea_up_dt\').className = \'hide\';
		    var t = document.getElementsByTagName (\'input\');
		    for (var i = 0; i < t.length; i++) 
			{
				if (t[i].getAttribute (\'name\') == \'upm_status\') 
				{
					toggles.push (t[i]);
					t[i].onclick = function () 
					{
						document.getElementById (\'more\').className = toggles[toggles.length - 1].checked ? \'show\' : \'hide\';
					}
				}
				
				if (t[i].getAttribute (\'name\') == \'li_s\') 
				{
					toggles1.push (t[i]);
					t[i].onclick = function () 
					{
						document.getElementById (\'p_up_dt\').className = toggles1[toggles1.length - 2].checked ? \'show\' : \'hide\';
						document.getElementById (\'p_sing\').className = toggles1[toggles1.length - 1].checked ? \'show\' : \'hide\';
					}
				}
				
				if (t[i].getAttribute (\'name\') == \'li_inst\') 
				{
					toggles3.push (t[i]);
					t[i].onclick = function () 
					{
						document.getElementById (\'inst_up_dt\').className = toggles3[toggles3.length - 2].checked ? \'show\' : \'hide\';
						document.getElementById (\'inst_sing\').className = toggles3[toggles3.length - 1].checked ? \'show\' : \'hide\';
					}
				}
				
				if (t[i].getAttribute (\'name\') == \'li_moa\') 
				{
					toggles4.push (t[i]);
					t[i].onclick = function () 
					{
						document.getElementById (\'moa_up_dt\').className = toggles4[toggles4.length - 2].checked ? \'show\' : \'hide\';
						document.getElementById (\'moa_sing\').className = toggles4[toggles4.length - 1].checked ? \'show\' : \'hide\';
					}
				}
				
				if (t[i].getAttribute (\'name\') == \'li_moacategory\') 
				{
					toggles5.push (t[i]);
					t[i].onclick = function () 
					{
						document.getElementById (\'moacategory_up_dt\').className = toggles5[toggles5.length - 2].checked ? \'show\' : \'hide\';
						document.getElementById (\'moacategory_sing\').className = toggles5[toggles5.length - 1].checked ? \'show\' : \'hide\';
					}
				}
				
				if (t[i].getAttribute (\'name\') == \'li_disease\') 
				{
					toggles6.push (t[i]);
					t[i].onclick = function () 
					{
						document.getElementById (\'disease_up_dt\').className = toggles6[toggles6.length - 2].checked ? \'show\' : \'hide\';
						document.getElementById (\'disease_sing\').className = toggles6[toggles6.length - 1].checked ? \'show\' : \'hide\';
					}
				}
				
				if (t[i].getAttribute (\'name\') == \'li_therapeuticarea\') 
				{
					toggles7.push (t[i]);
					t[i].onclick = function () 
					{
						document.getElementById (\'therapeuticarea_up_dt\').className = toggles7[toggles7.length - 2].checked ? \'show\' : \'hide\';
						document.getElementById (\'therapeuticarea_sing\').className = toggles7[toggles7.length - 1].checked ? \'show\' : \'hide\';
					}
				}
				
			}
		  }
		  
		</script>
		<br><div style="float:left;width:610px; padding:5px;"><fieldset class="schedule"><legend><b> SCRAPERS <font color="red">(NCT - NEW SCHEMA) </font> </b></legend>'
			. '<form action="database.php" method="post">'
			. 'Enter NCT Id to refresh a Single Trial: <input type="text" name="nt_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Refresh Trial" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Enter no. of days (look back period) : <input type="text" name="days_n" value=""/>&nbsp;&nbsp;&nbsp;
				<input type="hidden" name="scraper_n" value="fetch_nct_new.php"/>
				'
			. ''
			. '<input type="submit" value="Fetch from source" />'
			. '</form>'
			. '<form action="database.php" method="post">'
			. '<input type="hidden" name="nall" value="ALL"/>'
			. 'Click <b>FULL Refresh</b> button to refresh all trials in the database &nbsp;'
			. '<input type="submit" name="alltrials" value="FULL Refresh" />'
			. '</form>'
			;
			
			
			
			
			
	$out .= '</fieldset></div>';
	
	//SCRAPER - OLD SCHEMA
	$out .= '<div style="float:left;width:610px; padding:5px;"><fieldset class="schedule"><legend><b> SCRAPERS <font color="red">(NCT - OLD SCHEMA) </font> </b></legend>'
			. '<form action="database.php" method="post">'
			. 'Enter NCT Id to refresh a Single Trial: <input type="text" name="ot_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Refresh Trial" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Enter no. of days (look back period) : <input type="text" name="days_o" value=""/>&nbsp;&nbsp;&nbsp;
				<input type="hidden" name="scraper_o" value="fetch_nct.php"/>
				'
			. ''
			. '<input type="submit" value="Fetch from source" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. '<input type="hidden" name="oall" value="ALL"/>'
			. 'Click <b>FULL Refresh</b> button to refresh all trials in the database &nbsp;'
			. '<input type="submit" name="alltrials" value="FULL Refresh" />'
			. '</form>'			;
			
			
	$out .= '</fieldset></div>';
	
	$out .= '<div style="clear:both;"><hr style="height:2px;"></div>';
	// REMAPPING
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> REMAP TRIALS <font color="red">(NCT) </font></b></legend>'
			. '<form action="database.php" method="post">'
			. 'Enter NCT Id to remap : <input type="text" name="t_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Remap Trial" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Enter Larvol Id to remap : <input type="text" name="l_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Remap Trial" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Click <b>Remap data_nct</b> button to remap all trials in data_nct &nbsp;&nbsp;'
			. ' <input type="hidden" name="map_source" value="nct"/>'
			. '<input type="submit" name="data_nct" value="Remap data_nct" />'
			. '</form>'
			
			
			. '<form action="database.php" method="post">'
			. 'Click <b>Remap ALL</b> button to remap all trials in the database &nbsp; &nbsp;&nbsp;&nbsp;'
			. ' <input type="hidden" name="map_source" value="ALL"/>'
			. '<input type="submit" name="data_all" value="Remap ALL" />'
			. '</form>';
			
	$out .= '</fieldset></div>';
	
	// PREINDEXING
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> PREINDEXING <font color="red">(NCT) </font></b></legend>'
			. '<form action="database.php" method="post">'
			. 'Enter NCT Id to preindex : <input type="text" name="i_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Index Trial" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Enter Product Id to preindex : <input type="text" name="p_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Index Product" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Enter Area Id to preindex : <input type="text" name="a_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Index Area" />'
			. '</form>'
			
		
			. '<form action="database.php" method="post">'
			. 'Click <b>Index ALL</b> button to index all trials in the database &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ' <input type="hidden" name="index_all" value="ALL"/>'
			. '<input type="submit" name="ind_all" value="Index ALL" />'
			. '</form></fieldset></div>';
			
		$out .= '<div style="clear:both;"><hr style="height:2px;"></div>';
		

		

	// EUDRACT
	$out .= '<div style="float:left;width:610px; padding:5px;"><fieldset class="schedule"><legend><b> SCRAPERS <font color="red">(EUDRACT) </font> </b></legend>'
			. '<form action="database.php" method="post">'
			. 'Enter EudraCT Id to refresh a Single Trial: <input type="text" name="eudract_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Refresh Trial" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Enter no. of days (look back period) : <input type="text" name="days_n" value=""/>&nbsp;&nbsp;&nbsp;
				<input type="hidden" name="e_scraper_n" value="fetch_eudract.php"/>
				'
			. ''
			. '<input type="submit" value="Fetch from source" />'
			. '</form>'
			. '<form action="database.php" method="post">'
			. '<input type="hidden" name="e_nall" value="ALL"/>'
			. 'Click <b>FULL Refresh</b> button to refresh all trials in the database &nbsp;'
			. '<input type="submit" name="alleudracttrials" value="FULL Refresh" />'
			. '</form>';
			
	$out .= '</fieldset></div>';
	
	
		// REMAPPING EUDRACT
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> REMAP TRIALS <font color="red">(EUDRACT) </font></b></legend>'
			. '<form action="database.php" method="post">'
			. 'Enter EUDRACT Id to remap : <input type="text" name="t_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Remap Trial" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Enter Larvol Id to remap : <input type="text" name="l_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="singletrial" value="Remap Trial" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Click <b>Remap</b> button to remap all eudract trials  &nbsp;&nbsp;'
			. ' <input type="hidden" name="map_source" value="eudract"/>'
			. '<input type="submit" name="data_eudract" value="Remap" />'
			. '</form>';
			
	$out 	.= '</fieldset></div>';
	
			$out .= '<div style="clear:both;"><hr style="height:2px;"></div>';
	
	
	// RECALCULATE
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> RECALCULATE HEATMAP CELLS </b></legend>'
			. '<form action="database.php" method="post">'
			. 'Enter Product id to recalculate : <input type="text" name="prod_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" value="Recalculate Product" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Enter Area id to recalculate : <input type="text" name="area_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" value="Recalculate Area" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Click <b>Recalc ALL</b> button to recalculate all trials in the database &nbsp;&nbsp;&nbsp;'
			. ' <input type="hidden" name="recalculate_all" value="ALL"/>'
			. '<input type="submit" name="reca_all" value="Recalc ALL" />'
			. '</form></fieldset></div>';
			
	// UPM REFRESH STATUS
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> RECALCULATE UPM STATUS </b></legend>'
			. '<formset><form action="database.php" method="post">'
			. '
			<input type="radio" name="upm_status" value="1" selected="selcted"> All<br>
			<input type="radio" name="upm_status" value="2"> UPMs with end date in the past<br>
			<input type="radio" name="upm_status" value="3"> Having status
		
			<select name="status" id="more">
			  <option>Occurred</option>
			  <option>Pending</option>
			  <option>Upcoming</option>
			  <option>Cancelled</option></select>
				'
			. '<br><input type="submit" value="Recalculate" />'
			. '</form></formset></fieldset></div>';
			$out .= '<div style="clear:both;"><hr style="height:2px;"></div>';
	
	// LI product scraper
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> IMPORT PRODUCTS FROM LI </b></legend>'
			. '<formset><form action="database.php" method="post">'
			. '
			<input type="radio" name="li_s" value="1" selected="selcted"> All<br>
			<input type="radio" name="li_s" value="2"> Products updated since <span id="p_up_dt" name="p_up_dt1">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enter date as yyyy-mm-dd <input type="text" name="updt_since" id="updt_since" value="" size="10" length="10" title="( yyyy-mm-dd )"/></span> <br>
			<input type="radio" name="li_s" value="3"> Single product (Either LT ID or LI ID) <span id="p_sing" name="p_sing1">LT ID: <input type="text" name="p_lt_id" id="p_ltid" value="" title="Enter LT id"/> LI ID: <input type="text" name="p_li_id" id="p_ltid" value="" title="Enter LI id"/></span><br>
		
				'
			. '<br><input type="submit" value="Import" />'
			. '</form></formset></fieldset></div>';

	// LI Institution scraper
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> IMPORT INSTITUTIONS FROM LI </b></legend>'
			. '<formset><form action="database.php" method="post">'
			. '
			<input type="radio" name="li_inst" value="1" selected="selcted"> All<br>
			<input type="radio" name="li_inst" value="2"> Institutions updated since <span id="inst_up_dt" name="inst_up_dt1">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enter date as yyyy-mm-dd <input type="text" name="inst_updt_since" id="inst_updt_since" value="" size="10" length="10" title="( yyyy-mm-dd )"/></span> <br>
			<input type="radio" name="li_inst" value="3"> Single institution (Either LT ID or LI ID) <span id="inst_sing" name="inst_sing1">LT ID: <input type="text" name="inst_lt_id" id="inst_ltid" value="" title="Enter LT id"/> LI ID: <input type="text" name="inst_li_id" id="inst_ltid" value="" title="Enter LI id"/></span><br>
		
				'
			. '<br><input type="submit" value="Import" />'
			. '</form></formset></fieldset></div>';
			
	$out .= '<div style="clear:both;"><hr style="height:2px;"></div>';
	
	// LI moa scraper
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> IMPORT MOA\'s FROM LI </b></legend>'
			. '<formset><form action="database.php" method="post">'
			. '
			<input type="radio" name="li_moa" value="1" selected="selcted"> All<br>
			<input type="radio" name="li_moa" value="2"> Moas updated since <span id="moa_up_dt" name="moa_up_dt1">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enter date as yyyy-mm-dd <input type="text" name="moa_updt_since" id="moa_updt_since" value="" size="10" length="10" title="( yyyy-mm-dd )"/></span> <br>
			<input type="radio" name="li_moa" value="3"> Single MOA (Either LT ID or LI ID) <span id="moa_sing" name="moa_sing1">LT ID: <input type="text" name="moa_lt_id" id="moa_ltid" value="" title="Enter LT id"/> LI ID: <input type="text" name="moa_li_id" id="moa_ltid" value="" title="Enter LI id"/></span><br>
		
				'
			. '<br><input type="submit" value="Import" />'
			. '</form></formset></fieldset></div>';

	// LI moa scraper
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> IMPORT MOA CATEGORIES FROM LI </b></legend>'
			. '<formset><form action="database.php" method="post">'
			. '
			<input type="radio" name="li_moacategory" value="1" selected="selcted"> All<br>
			<input type="radio" name="li_moacategory" value="2"> Moa categories updated since <span id="moacategory_up_dt" name="moacategory_up_dt1">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enter date as yyyy-mm-dd <input type="text" name="moacategory_updt_since" id="moacategory_updt_since" value="" size="10" length="10" title="( yyyy-mm-dd )"/></span> <br>
			<input type="radio" name="li_moacategory" value="3"> Single MOA Category (Either LT ID or LI ID) <span id="moacategory_sing" name="moacategory_sing1">LT ID: <input type="text" name="moacategory_lt_id" id="moacategory_ltid" value="" title="Enter LT id"/> LI ID: <input type="text" name="moacategory_li_id" id="moacategory_ltid" value="" title="Enter LI id"/></span><br>
		
				'
			. '<br><input type="submit" value="Import" />'
			. '</form></formset></fieldset></div>';

$out .= '<div style="clear:both;"><hr style="height:2px;"></div>';
	
	// LI disease scraper
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> IMPORT DISEASE\'s FROM LI </b></legend>'
			. '<formset><form action="database.php" method="post">'
			. '
			<input type="radio" name="li_disease" value="1" selected="selcted"> All<br>
			<input type="radio" name="li_disease" value="2"> Diseases updated since <span id="disease_up_dt" name="disease_up_dt1">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enter date as yyyy-mm-dd <input type="text" name="disease_updt_since" id="disease_updt_since" value="" size="10" length="10" title="( yyyy-mm-dd )"/></span> <br>
			<input type="radio" name="li_disease" value="3"> Single disease (Either LT ID or LI ID) <span id="disease_sing" name="disease_sing1">LT ID: <input type="text" name="disease_lt_id" id="disease_ltid" value="" title="Enter LT id"/> LI ID: <input type="text" name="disease_li_id" id="disease_ltid" value="" title="Enter LI id"/></span><br>
		
				'
			. '<br><input type="submit" value="Import" />'
			. '</form></formset></fieldset></div>';
	
	// MeSH Diseases from clinicaltrials.gov
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> Import MeSH Diseases </b></legend>'
			. '<formset><form action="database.php" method="post">'
			. '<input type="hidden" name="import_mesh_diseases" value="YES">'
			. '<br><input type="submit" value="Import" />'
			. '</form></formset></fieldset></div>';
	
$out .= '<div style="clear:both;"><hr style="height:2px;"></div>';
	
	// LI therapeutic area
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> IMPORT THERAPEUTIC AREA\'s FROM LI </b></legend>'
			. '<formset><form action="database.php" method="post">'
			. '
			<input type="radio" name="li_therapeuticarea" value="1" selected="selcted"> All<br>
			<input type="radio" name="li_therapeuticarea" value="2"> Therapeutic Areas updated since <span id="therapeuticarea_up_dt" name="therapeuticarea_up_dt1">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enter date as yyyy-mm-dd <input type="text" name="therapeuticarea_updt_since" id="therapeuticarea_updt_since" value="" size="10" length="10" title="( yyyy-mm-dd )"/></span> <br>
			<input type="radio" name="li_therapeuticarea" value="3"> Single Therapeutic Area (Either LT ID or LI ID) <span id="therapeuticarea_sing" name="therapeuticarea_sing1">LT ID: <input type="text" name="therapeuticarea_lt_id" id="therapeuticarea_ltid" value="" title="Enter LT id"/> LI ID: <input type="text" name="therapeuticarea_li_id" id="therapeuticarea_ltid" value="" title="Enter LI id"/></span><br>
		
				'
			. '<br><input type="submit" value="Import" />'
			. '</form></formset></fieldset></div>';
			
	// import industr institutions
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> IMPORT INDUSTRY INSTITUTIONS</b></legend>'
	. '<formset><form action="database.php" method="post">'
	. '<input type="hidden" name="import_industries" value="YES">'
	. '<br><input type="submit" value="Import" />'
	. '</form></formset></fieldset></div>';
	
	$out .= '<div style="clear:both;"><hr style="height:2px;"></div>';
	
	//  INVESTIGATOR DETECTION
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> INVESTIGATOR DETECTION <font color="red">(NCT) </font></b></legend>'
			. '<form action="database.php" method="post">'
			. 'Enter NCT Id : <input type="text" name="inv_t_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="inv_singletrial" value="Detect Investigator" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Enter Larvol Id : <input type="text" name="inv_l_id" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
			. ''
			. '<input type="submit" name="inv_singletrial" value="Detect Investigator" />'
			. '</form>'
			
			. '<form action="database.php" method="post">'
			. 'Click <b>Detect ALL</b> button to detect investigators of all trials (NCT) &nbsp; &nbsp;&nbsp;&nbsp;'
			. ' <input type="hidden" name="detect_source" value="ALL"/>'
			. '<input type="submit" name="detect_all" value="Detect ALL" />'
			. '</form></formset></fieldset></div>';
			
	// RECALCULATE INVESTIGATOR CELLS
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b>RECALCULATE INVESTIGATORS (ALL)</b></legend>'
	. '<formset><form action="database.php" method="post">'
	. '<input type="hidden" name="recalc_investigators" value="YES">'
	. '<br><input type="submit" value="Recalculate" />'
	. '</form></formset></fieldset></div>';
	
	$out .= '<div style="clear:both;"><hr style="height:2px;"></div>';
	// Diseases Category from clinicaltrials.gov
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> Import Disease Category </b></legend>'
			. '<formset><form action="database.php" method="post">'
			. '<input type="hidden" name="import_disease_category" value="YES">'
			. '<br><input type="submit" value="Import" />'
			. '</form></formset></fieldset></div>';
	
			
	$out .= '</fieldset></div><br /><br /><br />';
		
	return $out;

}

?>