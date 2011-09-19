<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Revision Manager</title>
<style type="text/css" media="all">
.cmd{
	background-color:#444;
	color:#FFF;
}
</style>
</head>
<body>
<?php
if(isset($_POST['update']))
{
	echo('<fieldset><legend>Output</legend><pre>');
	$rev = '';
	if(strlen($_POST['revision']) && is_numeric($_POST['revision']))
		$rev = ' -r' . (int)$_POST['revision'];
	$cmd = 'svn update' . $rev;
	echo('<span class="cmd">' . $cmd . '</span>' . "\n");
	//svn update command error goes to stderr . So forcing it to output from stderr to stdout.
	$cmd .= " 2>&1";
	system($cmd);
	echo('</pre></fieldset>');
}
?>
<fieldset><legend>Status</legend><pre><?php
echo('Repository '); system('svn info -r HEAD | grep -i "Last Changed Rev"');
echo('Live site  '); system('svn info | grep -i "Last Changed Rev"');
echo("\n");
system('svn status');
?>
</pre></fieldset>
<form method="post" action="svn.php">
<fieldset><legend>Commands</legend>
  <input type="submit" name="update" value="Update" /> <input type="text" name="revision" /><br />
</fieldset>
</form>
</body></html>