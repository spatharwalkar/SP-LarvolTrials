<?php
// Use sessions instead of cookies
//setcookie('li_user',$_GET['u'],time()+(3600*24*365*31),'/','.larvoltrials.com'); //31 years
// Start session 
@session_start();
// store session data
$_SESSION['li_user']=$_GET['u'];

header('Content-Type: image/gif');
readfile('../images/beacon.gif');
?>
