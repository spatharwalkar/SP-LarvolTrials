<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once 'include.page.php';

//Allow only for users already logged in.
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
	
global $db,$page,$deleteFlag,$searchFormData;

require('header.php');
//reset header controller
//unset($_GET['header']);

//echo('<script type="text/javascript" src="delsure.js"></script>');

?>

<?php 
//Start controller area
//delete controller should come above save controller if delete box is added in the add edit form

//set docs per list
$limit = 50;
$totalCount = getTotalCount($table);
$maxPage = $totalCount%$limit;
if(!isset($_GET['oldval'])) {
	$page=0;
}

$trial_count=0;
$sql = "
	SELECT 
            e.id, e.name, e.class, dt.larvol_id, dt.brief_title, dt.official_title, dt.intervention_type, dt.intervention_name, dt.intervention_description
	FROM data_trials dt 
	LEFT JOIN entity_trials et ON et.trial = dt.larvol_id
	LEFT JOIN entities e ON e.id = et.entity AND e.class = 'Product' AND e.id IS NULL
	#LIMIT 0, 200
";
$result_sql = mysql_query($sql);
if($result_sql && mysql_num_rows($result_sql)) {
	echo '
            <h3>List of Trials</h3>
            <table border="1">
		<thead>
			<tr><th>#</th>
                            <!-- <th>No. of linked products</th> -->
                            <th>Products</th>
                            <th>Linked Trial Name</th>
                    </tr>
		</thead>';
	while($trial = mysql_fetch_assoc($result_sql)) {
		$trial_count++;
                
                //if($trial['name'] != NULL) {
                  //  continue;
                //}
                
		echo '<tr>';
		
		echo '<td>';
		echo $trial_count.'';
		echo '</td>';
                
		//echo '<td>';echo $row['name'].'';echo '</td>';
		
		//echo '<td>';
		$products_str = $trial['intervention_name'];
		$products = explode('`', $products_str);
		$products_num = count($products);
		//echo $products_num.' products. ';
		//echo '</td>';
                
		echo '<td><ol><li>';
                echo implode('</li><li>', $products);
		//echo $products_str;
		echo '</li></ol></td>';
                
		echo '<td>';
		echo $trial['brief_title'];
		echo '</td>';
		
		//echo $trial['intervention_description'];
		echo '</tr>';
	}
	echo '</table>';
}

echo '<br/>';
echo '<div class="clr">';
echo '</div>';
?>
<div id="inline_outer" >
<div id="inline_content">

</div>