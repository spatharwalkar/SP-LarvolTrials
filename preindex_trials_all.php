<?php
require_once('db.php');
require_once('preindex_trial.php');
ini_set('max_execution_time', '360000'); //100 hours
ignore_user_abort(true);
//index all products
return ;
echo '<br><b>Indexing ALL products...<br></b>';
tindex(NULL,'products',NULL,NULL,NULL,NULL);
echo '<br>Done. <br>';

//index all areas
echo '<br><b>Indexing ALL areas...</b><br>';
tindex(NULL,'areas',NULL,NULL,NULL,NULL);
echo '<br>Indexed all areas. <br>';

?>  