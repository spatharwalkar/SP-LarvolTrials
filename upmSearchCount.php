<?php
require_once('db.php');
function getTotalUpmCount()
{
global $db;
$query = "select count(id) as cnt from upm";
$res = mysql_query($query);
$count = mysql_fetch_row($res);
return $count[0];
}
if($_GET['web']==1):
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo(SITE_NAME); ?></title>
<style media="all" type="text/css">
body{
	border:0;margin:0;padding:0;
	font-family:Verdana, Geneva, sans-serif;
	font-size:small;
}
</style>
</head>
<body>
<?php echo getTotalUpmCount(); ?>
</body>
</html>
<?php endif;?>