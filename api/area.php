<?php
chdir('..');
require_once('db.php');
$id = mysql_real_escape_string($_GET['id']);
if(empty($id)) exit;
$query = 'SELECT id FROM areas WHERE LI_id="' . $id . '" LIMIT 1';
$res = mysql_query($query) or die('-1');
$res = mysql_fetch_assoc($res) or die('-1');
echo($res['id']);
?>