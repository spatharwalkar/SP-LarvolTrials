<?php
setcookie('li_user',$_GET['u'],time()+(3600*24*365*31),'/'); //31 years
header('Content-Type: image/gif');
readfile('../images/beacon.gif');
?>