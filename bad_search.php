<?php
require_once('db.php');
ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
require('searchhandler.php');

if($_GET['table_type'] == 'areas')
{
$table_name = 'Area';
$table = 'areas';
$id = 'Area ID';
$name = 'Area Name';
$disp_name = 'Display Name';
$searchdata = 'Search Data';
}
else
{
$table_name = 'Products';
$table = 'products';
$id = 'Product ID';
$name = 'Product Name';
$disp_name = 'Display Name';
$searchdata = 'Search Data';
}

$query = 'SELECT id, name, display_name, searchdata FROM ' . $table . '';

$res=mysql_query($query);

$Flg=0;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>List of Bad Searches from <?php print $table_name ?> Table</title>
<style type="text/css">
body { font-family:Verdana; font-color:black; font-size: 14px;}
td, th {vertical-align:top; padding-top:10px; padding-left:10px; border-right: 1px solid; border-left:1px solid; border-top: 1px solid; border-bottom:1px solid;}
tr {border-right: 1px solid; border-left: 1px solid; border-top: 1px solid; border-bottom: 1px solid;}
</style>
</head>

<body>
<fieldset class="code"><legend>Select Table</legend>
<table  style="width:200px; border:none; border-color:#000000; border-collapse:collapse;">
	<tr style="border:none;">
    	<td style="width:100px; border:none;"><input type="radio" name="table" onchange="window.location.href='bad_search.php?table_type='+this.value+''" value="products" <?php print (($table_name == 'Products') ? 'checked="checked"':''); ?> />Product</td>
        <td style="width:100px; border:none;"><input type="radio" name="table" onchange="window.location.href='bad_search.php?table_type='+this.value+''" value="areas" <?php print (($table_name == 'Area') ? 'checked="checked"':''); ?> />Area</td>
   </tr>
</table>
</fieldset>
<br />
<fieldset><legend>List of Bad Searches from <b>"<?php print $table_name; ?>"</b> table </legend>
<table style="width:1200px; border-color:#000000; border-collapse:collapse;">
	<tr>
    	<td style="width:100px;"><?php print $id; ?></td>
        <td style="width:300px;"><?php print $name; ?></td>
        <td style="width:300px;"><?php print $disp_name; ?></td>
        <td style="width:600px;">Error / Exception Message</td>
    </tr>
<?php
while ($row = mysql_fetch_array($res))
{
	$jsonData = $row['searchdata'];
	$Exception='';
	try 
	{
		$actual_query= buildQuery($jsonData, false);
	}
	catch(Exception $e)
	{
	$Exception = $e->getMessage();
	}
	
	$result = mysql_query($actual_query." LIMIT 0");
	
	if (mysql_errno() || $Exception != '') 
	{
		if(!$Flg) $Flg=1;
		
		if(mysql_errno()) 
			$error = "MySQL error ".mysql_errno().": ".mysql_error()."";
		else
			$error = "";
?>
	<tr>
    	<td><?php print $row['id']; ?></td>
        <td><?php print $row['name']; ?></td>
        <td><?php print $row['display_name']; ?></td>
        <td><?php if($Exception != '') print $Exception.'<br/>'; print $error; ?></td>
    </tr>
<?php
	}
}
?>
</table>
</fieldset>
<br/>
<?php if(!$Flg) print 'No Error / Exception Found...</br>'; ?>
Done.
</body>
</html>
