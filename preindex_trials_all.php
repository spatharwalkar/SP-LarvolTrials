<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('preindex_trial.php');
global $logger;

/*
//Get studycats
$query = 'SELECT id FROM data_cats_in_study WHERE category=1';
if(!$resu = mysql_query($query))
{
	$log='Bad SQL query getting studycats FROM data_cats_in_study .<br>Query=' . $query;
	$logger->fatal($log);
	echo $log;
	exit;
}
while($studycatz[]=mysql_fetch_array($resu));
if(!count($studycatz)>0) die('No valid NCTIDs found!');
*/
if(isset($_GET['productid'])) $and = ' and id=' . $_GET['productid'] ;
else $and = '';
$productz=array();
$query = 'SELECT id,name,searchdata from products where searchdata IS NOT NULL ' . $and ;
if(!$resu = mysql_query($query))
{
	$log='Bad SQL query getting  details from products table.<br>Query=' . $query;
	$logger->fatal($log);
	echo $log;
	exit;
}
while($productz[]=mysql_fetch_array($resu));
/***************************************/
if(isset($_GET['areaid'])) $and = ' and id=' . $_GET['areaid'] ;
else $and = '';
$areaz=array();
$query = 'SELECT id,name,searchdata from areas where searchdata IS NOT NULL ' . $and ;
if(!$resu = mysql_query($query))
{
	$log='Bad SQL query getting  details from areas table.<br>Query=' . $query;
	$logger->fatal($log);
	echo $log;
	exit;
}
while($areaz[]=mysql_fetch_array($resu));
/***************************************/

echo('<br>Associating trials with all PRODUCTS ..<br><br>');
tindex(NULL,'products',$productz);

echo('<br>Associating trials with all AREAS ..<br><br>');
tindex(NULL,'areas',$areaz);


