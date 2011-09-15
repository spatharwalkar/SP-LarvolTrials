<?php
require_once('settings.php');
require_once('class.dbsync.php');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title><?php echo(SITE_NAME); ?></title>
<style type="text/css" media="all">
.code{font-family:"Courier New", Courier, monospace;}
</style>
</head><body>
<?php
echo('Loading schema... ');
@flush();
mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
mysql_query('DROP DATABASE IF EXISTS ' . DB_TEMP) or die("Couldn't drop database: " . mysql_error());
mysql_query('CREATE DATABASE ' . DB_TEMP) or die("Couldn't create database: " . mysql_error());
mysql_select_db(DB_TEMP) or die("Could not find database on server!");
$setupscript = file_get_contents('setup/schema.sql');
$setupscript = explode(';',$setupscript);
foreach($setupscript as $stat)
{
	$stat = trim($stat);
	if(empty($stat)) continue;
	$res = mysql_query($stat);
	if($res === false)
	{
		echo("Warning! Bad query: ");
		var_dump($stat);
	}
}
mysql_close();
echo('Done.<br />Changes needed to make the DB in use the same as the recorded schema for the revision of your working copy:<br /><br /><fieldset class="code"><legend>SQL</legend>');
$dbsync = new DBSync();
$dbsync->SetHomeDatabase(DB_TEMP, 'mysql', DB_SERVER, DB_USER, DB_PASS);
$dbsync->AddSyncDatabase(DB_NAME, 'mysql', DB_SERVER, DB_USER, DB_PASS);
$dbsync->Sync();
echo('</fieldset><br />Done. <ul><li>If these differences were caused by an update, compare the above changes to what is called for by recent code commits, and if correct, execute them.</li><li>If these differences are due to your own schema changes, update the setup script (setup/schema.sql) to include them, then re-run this page to ensure there are no differences before you commit your code.</li></ul>');
?>
<br/>
Data changes based on data.sql
<br/>
<fieldset class="code"><legend>SQL</legend>
<?php 
//data.sql import
mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
mysql_select_db(DB_TEMP) or die("Could not find database on server!");
$dataScript = file_get_contents('setup/data.sql');
$dataScript = explode(';',$dataScript);
foreach($dataScript as $data)
{
	$data = trim($data);
	if(empty($data)) continue;
	$res = mysql_query($data);
	if($res === false)
	{
		echo("Warning -- Bad query: ");
		var_dump($stat);
		exit;
	}
}
mysql_close();
echo '<pre>';
$dbsync->syncDataTables('set',array('data_categories','data_fields','data_enumvals','user_permissions'));
$dbsync->syncData();

?>
</fieldset>
<br />Done. <ul><li>Re-run the script again to make sure no more related data are needed to be inserted or not.</li></ul>

</body>
</html>
<?php 
//delete temp db created.
mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
mysql_query('DROP DATABASE ' . DB_TEMP) or die("Couldn't drop database: " . mysql_error());
mysql_close();

?>