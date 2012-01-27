<?php
header('P3P: CP="CAO PSA OUR"');
require_once('krumo/class.krumo.php');
require_once('db.php');
if(!$db->loggedIn())
{
	require('index.php');
	exit;
}
require('header.php');

$jsonData=$_REQUEST['data'];
$filterData = json_decode($jsonData, true, 10);
$select_columns=$filterData["columndata"];
$colNames='';
$colModel='';

$colNames .= "'larvol_id',";
$colModel .= "{name:'larvol_id',jsonmap:'larvol_id',width:50},";
$colNames .= "'source_id',";
$colModel .= "{name:'source_id',jsonmap:'source_id',width:50},";
$colNames .= "'view',";
$colModel .= "{name:'view',jsonmap:'view',width:10},";
$colNames .= "'edit',";
$colModel .= "{name:'edit',jsonmap:'edit',width:10},";
if(!empty($select_columns))
{
foreach($select_columns as $selectcolumn)
{
	$colName = $selectcolumn["columnname"];
	$colAlias= $selectcolumn["columnas"];
	if($colAlias != '')
	{
		$colName=$colAlias;
	}
	$colNames .= "'" . $colName . "',";
	$colModel .= "{name:'" . $colName . "',jsonmap:'" . $colName . "',width:90},";

}
}
$colNames = substr($colNames, 0, -1); //strip last comma
$colModel = substr($colModel, 0, -1); //strip last comma
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Search Results</title>

<link rel="stylesheet" type="text/css" media="screen"
	href="css/themes/ui-lightness/jquery-ui-1.8.16.custom.css" />
<link rel="stylesheet" type="text/css" media="screen"
	href="css/jqgrid/ui.jqgrid.css" />
<style>
html,body {
	margin: 0;
	padding: 0;
	font-size: 75%;
}
</style>
<script src="scripts/jqgrid/jquery-1.5.2.min.js" type="text/javascript"></script>
<script src="scripts/jqgrid/i18n/grid.locale-en.js" type="text/javascript"></script>
<script src="scripts/jqgrid/jquery.jqGrid.min.js" type="text/javascript"></script>
<script type="text/javascript">
   $(document).ready(function () {
	   jQuery("#list2").jqGrid({
		   	url:'searchhandler.php?&op=runQuery&data=' + <?php echo(json_encode($jsonData)); ?>,
			datatype: "json",
		   	colNames:[<?php echo($colNames);?>],
		   	colModel:[<?php echo($colModel);?>
		   	],
		   	rowNum:10,
		   	rownumbers: true,
		   	rowList:[10,20,30],
		   	pager: '#pager2',
		   	sortname: 'name',
		    viewrecords: true,
		    sortorder: "desc",
		    caption:"Search Results",
		    width:1000,
		    style:'padding-left:200; padding-top:200',
		    jsonReader:{
		    	  root: "rows",
		    	  page: "page",
		    	  total: "total",
		    	  records: "records",
		    	  repeatitems: false,
		    	  id: "0"
		    	},
		        loadComplete: function () {
		            //alert("OK");
		        },
		        loadError: function (jqXHR, textStatus, errorThrown) {
//		            alert('HTTP status code: ' + jqXHR.status + '\n' +
//		                  'textStatus: ' + textStatus + '\n' +
//		                  'errorThrown: ' + errorThrown);
//		            alert('HTTP message body (jqXHR.responseText): ' + '\n' + jqXHR.responseText);

   					$("#3009").html(jqXHR.responseText);
		            $("#3009").show();
		        }
		});
		jQuery("#list2").jqGrid('navGrid','#pager2',{edit:false,add:false,del:false});
		//jQuery("#list2").jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false});

});

   function goBack()
   {
	   var url = "newsearch.php?data=" + <?php echo("'" . $jsonData) . "'"; ?>;
	   window.location.href= url;
   }

</script>
</head>
<body>
<table>

            <tr>
			<td style="background-color:#fff;padding-left: 50px;padding-top: 50px"><input type="submit"
					style="width: 100px" onclick="goBack();return false;"
					value="Back" id="btnGoBack" /></td>
			</tr>
			<tr >
				<td style="background-color:#fff;padding-left: 50px;">
				<p id="3009" style="color:red" visible="false"></p>
				</td>
			</tr>
	    <tr>
		<td style="background-color:#fff;padding-left: 50px; ">
		<table id="list2"></table>
		<div id="pager2"></div>

		</td>
	</tr>
</table>
</body>
</html>
