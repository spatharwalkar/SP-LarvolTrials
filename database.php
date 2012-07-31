<?php
//connect to Sphinx
if(!isset($sphinx) or empty($sphinx)) $sphinx = mysql_connect("127.0.0.1:9306") or die ("Couldn't connect to Sphinx server.");
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
// single trial REMAP using LARVOLID
if (isset($_POST['l_id'])) 
{
	require_once('remap_trials.php');
	remaptrials(null,$_POST['l_id'],null);
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
	$parameters['product']=$_POST['prod_id']; // for product
	calc_cells($parameters);
	return;
}
// RECALCULATE a area
if (isset($_POST['area_id'])) 
{
	require_once('calculate_hm_cells.php');
	$parameters=array();
	$parameters['area']=$_POST['area_id']; // for product
	calc_cells($parameters);
	return;
}
// RECALCULATE a area
if (isset($_POST['recalculate_all'])) 
{
	require_once('calculate_hm_cells.php');
	calc_cells(NULL);   // for all
	return;
}
// Update UPM status values
if (isset($_POST['select_status'])) 
{
	$st=mysql_real_escape_string($_POST['select_status']);
	require_once('upm_trigger.php');
	echo '<br><br>Recalculating UPM status values (for status=<b>' . $st . '</b>)<br><br>';
	if(!fire_upm_trigger_st($st)) echo '<br><b>Could complete Updating UPM status values, there was an error.<br></b>';
	else 
	{
		echo '<br><br><b>All done.</b><br><br>';
	}
	return;
}

if (isset($_POST['upm_s'])) 
{
	$st=mysql_real_escape_string($_POST['upm_s']);
	require_once('upm_trigger.php');
	echo '<br><br>Recalculating UPM status values (for <b> end_date in the past</b>)<br><br>';
	if(!fire_upm_trigger_dt()) echo '<br><b>Could complete Updating UPM status values, there was an error.<br></b>';
	else 
	{
		echo '<br><br><b>All done.</b><br><br>';
	}
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
	$out = '<br><div style="float:left;width:610px; padding:5px;"><fieldset class="schedule"><legend><b> SCRAPERS <font color="red">(NEW SCHEMA) </font> </b></legend>'
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
	$out .= '<div style="float:left;width:610px; padding:5px;"><fieldset class="schedule"><legend><b> SCRAPERS <font color="red">(OLD SCHEMA) </font> </b></legend>'
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
	
	$out .= '<div style="clear:both;"><br><hr style="height:2px;"></div>';
	// REMAPPING
	$out .= '<br><div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> REMAP TRIALS </b></legend>'
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
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> PREINDEXING </b></legend>'
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
			. '</form></div>';
			
		$out .= '<div style="clear:both;"><br><hr style="height:2px;"></div>';
	
	// RECALCULATE
	$out .= '<div style="width:610px; padding:5px;float:left;"><fieldset class="schedule"><legend><b> RECALCULATING </b></legend>'
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
			. '</form></div>';
			
	// UPM REFRESH (for a particular status)
	$out .= '<div style="width:610px; padding:5px;float:left;">'
			. '<form action="database.php" method="post">'
			. 'Recalculate Status of UPMs with status:
				<select name="select_status">
				<option value="Occurred" selected="selected">Occurred</option>
				<option value="Pending">Pending</option>
				<option value="Upcoming">Upcoming</option>
				<option value="Cancelled">Cancelled</option>
				</select>
				'
			. '<input type="submit" value="Recalculate" />'
			. '</form></div>';
	
	
	// UPM REFRESH (for end date in the past)			
	$out .= '<div style="width:610px; padding:5px;float:left;">'
			. '<form action="database.php" method="post">'
			. 'Recalculate Status of UPMs with end_date in the past: <input type="hidden" name="upm_s" id="upm_s" value="upm_s"/>'
			. ''
			. '<input type="submit" value="Recalculate" />'
			. '</form></div>';
			
	return $out;
	
	

}

?>