<?php
require_once('utf.php');
require_once('include.util.php');
$data = implode('+OR+',array_map('padnct',utf8ToUnicode(gzinflate(base64_decode($_SERVER['QUERY_STRING'])))));
header('Location: http://www.clinicaltrials.gov/ct2/results?id=' . $data);
exit;
?>