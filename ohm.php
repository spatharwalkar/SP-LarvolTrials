<?php
require_once('db.php');
require_once('include.ohm.php');

$auto = false;
$fullpage = true;
$direct = true;
$li = false;
if(strpos($_SERVER['HTTP_REFERER'], 'insight') || strpos($_SERVER['HTTP_REFERER'], 'delta')) $li = true;

ohm($_GET['id'], $auto, $fullpage, $direct, $li);

?>