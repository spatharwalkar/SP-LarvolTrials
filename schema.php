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
		echo("Couldn't import DB from file! Bad query: ");
		var_dump($stat);
		exit;
	}
}
mysql_close();
echo('Done.<br />Changes needed to make the DB in use the same as the recorded schema for the revision of your working copy:<br /><br /><fieldset class="code"><legend>SQL</legend>');
$dbsync = new DBSync();
$dbsync->SetHomeDatabase(DB_TEMP, 'mysql', DB_SERVER, DB_USER, DB_PASS);
$dbsync->AddSyncDatabase(DB_NAME, 'mysql', DB_SERVER, DB_USER, DB_PASS);
$dbsync->Sync();

mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
mysql_query('DROP DATABASE ' . DB_TEMP) or die("Couldn't drop database: " . mysql_error());
mysql_close();
echo('</fieldset><br />Done. <ul><li>If these differences were caused by an update, compare the above changes to what is called for by recent code commits, and if correct, execute them.</li><li>If these differences are due to your own schema changes, update the setup script (setup/schema.sql) to include them, then re-run this page to ensure there are no differences before you commit your code.</li></ul>');
?>
</body>
</html>