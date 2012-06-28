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
$show_sort_res_bool = true;
require('querybuilder.php');

?>

<script type="text/javascript">

$(document).ready(function () {
	
	<?php 
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))	//load search from Saved Search
    {
	
	   $ssid = mysql_real_escape_string($_REQUEST['id']);
	   $show_value = 'showSearchData("' . $ssid . '");';
	   echo($show_value);

	}
	
	if(isset($_REQUEST['data']))	//load search from Saved Search
    {
	   $data = $_REQUEST['data'];
	   //$show_value = 'loadQueryData("' . $data . '");';
	   $show_value = "loadQueryData('" . $data . "');";
	   echo($show_value);

	}
	
	?>
	
});

    function testSQL()
    {
		 var jsonData = getQueryData();   
		if(jsonData.length > 500)
		{
			requestType = 'POST';
		}
		else
		{
			requestType = 'GET';
		}  		 
         $.ajax({
					type: requestType,
					url:  'searchhandler.php' + '?op=testQuery',
					data: 'data=' + jsonData,
					success: function (data) {
        					//alert(data);
        					$("#3009").html(data);
        		            $("#3009").attr("style", "visibility:show");
        		        	
					}
        	});
        return;
    }
    
    function runSQL()
    {
//    	 var isSaved =  saveSearch();
//         if(!isSaved)
//         {
//             return;
//         }
        var jsonData = getQueryData();
    	var ermsg = 'Null search data not allowed.';
    	if(jsonData =='')
    	{
			alert(ermsg);
			return false;        	
    	}
    	var jsonDataArr = eval('('+jsonData+')');
    	if(jsonDataArr.wheredata == '')
    	{
			alert(ermsg);
			return false;
    	}        
		if(jsonData.length > 500)
		{
			requestType = 'POST';
		}
		else
		{
			requestType = 'GET';
		}
		$('#larvolTestJsonStat').val('0');
        $.ajax({
            async: false,
			type: requestType,
			url:  'searchhandler.php' + '?op=testQuery&jsonOp=1',
			data: 'data=' + jsonData,
			beforeSend:function(){
				$("#3009").html('');
				},
			success: function (data) {
		           var testJson = eval("(" + data + ')');
		   			$('#larvolTestJsonStat').val(testJson.status);
		           if(testJson.status!=1)
		           {
						$("#3009").html(testJson.message);
				        $("#3009").attr("style", "visibility:show");
		           }
		        	
			}
		}); 
        if($('#larvolTestJsonStat').val() !=1)
        {
        	return;     
        }
		     
        var url = 'searchhandler.php' + '?op=runQuery&data=' + jsonData;
        //window.location.href=url;
        if(jsonData.length > 500)
        {
            $('#runSqlForm_op').val('runQuery');
            $('#runSqlForm_data').val(jsonData);
            document.runSqlForm.submit();
        }
        else
        {
			window.open(url,'_blank')
        }
        return;
    }

    function submitSearch()
    {
    	var jsonData = getQueryData(); 
    	alert(jsonData);
    }
  </script>

<body>
<table>

	<tr>

		<td>
		<div class="querybuilder"></div>
		</td>

		<td valign="top" style="padding-top: 15px">
		<table width="200px">
			<tr>
				<td class="graybk" style="text-align: center; font-weight: bold">
				Actions</td>
			</tr>
			<tr>
				<td style="padding-left: 30px;"><input type="submit"
					style="width: 100px" onClick="testSQL();return false;"
					value="Test Query" id="btnTest" /></td>
			</tr>
			<tr>
				<td style="padding-left: 30px;"><input type="submit"
					style="width: 100px" onClick="runSQL();return false"
					value="Run Query" id="btnRun" /></td>
			</tr>

		</table>
		</td>

	</tr>
</table>
<form target="_blank" name="runSqlForm" id="runSqlForm" method="POST" action="intermediary.php">
<input type="hidden" name="data" id="runSqlForm_data"/>
<input type="hidden" name="op" id="runSqlForm_op"/>
<input type="hidden" name="p"/>
<input type="hidden" name="a"/>
<input type="hidden" name="forcePost" value="1"/>
</form>
<input type="hidden" id="larvolTestJsonStat" value="0"/>
<input type="hidden" id="larvolTestJsonMessage" value=""/>
</body>
