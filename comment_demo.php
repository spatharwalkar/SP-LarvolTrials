<?php
require_once('db.php');


?><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="stylesheet" type="text/css" href="comments/css/stylesheet.css"/>
</head>
<body>
page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content page content 
<?php if($db->loggedIn()) echo('<a href="sigma/login.php?logout=true">Logout</a><br>'); ?>
<br>
<br>
<br>
<br>
<br>

<?php
$cmtx_identifier = '1';
$cmtx_reference = 'Cancer';
$cmtx_path = 'comments/';
define('IN_COMMENTICS', 'true'); //no need to edit this line
require $cmtx_path . 'includes/commentics.php'; //no need to edit this line
?>

</body></html>