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
require('querybuilder.php');
$mode = "new";
if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))	//load search from Saved Search
{
	$mode = "edit";
	$ssid = mysql_real_escape_string($_REQUEST['id']);
	$query = 'SELECT * FROM saved_searches WHERE id=' . $ssid . ' AND (user=' . $db->user->id . ' or user IS NULL)'
	. ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting searchdata');
	$row = mysql_fetch_array($res);
	//if($row === false) return;	//In this case, either the ID is invalid or it doesn't belong to the current user.
	 
}

?>

<script type="text/javascript">
$(document).ready(function () {
	<?php 
	if($mode == 'edit')
	{
	   //$show_value = 'showSearchData("' . $_GET['id'] . '");';
	   //echo($show_value);
	   $data = unserialize(base64_decode($row['searchdata']));
	   echo("$('.sqlbuild').loadSQB('" . $data . "');");
	   echo("$('#txtSearchName').val('" . $row['name'] . "');");
	    echo("$('#lblId').text('" . $row['id'] . "');");
	}
	?>
	
});

function saveSearch()
   {
	var $tt= $('.sqlbuild')[0];
    var openAndClose = $('.sqlbuild').checkMatchingBraces();
    if (openAndClose != 0) {
        $($tt.opts.statusmsgdiv).html("Can't save the report.Please match all the opening braces with relevant closing braces in the conditions section");
        $("#3009").attr("style", "visibility:show");
        return false;
    }
    var name = $("#txtSearchName").val();
    var searchId = $("label[for=" + "lblId" + "]").text();
    $("#3009").attr("style", "visibility:show");
    
    if (jQuery.trim(searchId) == "") {//new search
        if (!name) 
        {
            $($tt.opts.statusmsgdiv).html("Can't save the report. Please enter search name");
            $("#3009").attr("style", "visibility:show");
        	return false;
        }
        $.ajaxSetup({ cache: false });
        $.ajax({
            type: 'POST',
            url: $tt.opts.reporthandler + '?op=savenew&reportid=' + $tt.opts.reportid + '&reportname=' + name,
            data: 'querytosave=' + $('.sqldata', $('.sqlbuild')).html(),
            error: function () { if ($tt.opts.statusmsgdiv) $($tt.opts.statusmsgdiv).html("Can't save the report sql"); },
            success: function (data) { 
            	if ($tt.opts.statusmsgdiv) $($tt.opts.statusmsgdiv).html( name + " saved...."); 
            	$("#txtSearchName").val(name);
            	var newItem='<li class="item"> <a onclick="showSearchData(\''
                    + data + '\',\'' + name + '\');return false;" href="#">' + name + '</a> </li>';
            	$('ul.treeview li.list').addClass('expanded').find('>ul').append(newItem);
                $('#3009').show();
            	}
        });

        return true;
    }
    else {
        $.ajaxSetup({ cache: false });

        var queryData = getQueryData();
        alert(queryData);
        $.ajax({
            type: 'POST',
            url: $tt.opts.reporthandler + '?op=saveexists&searchId=' + searchId + '&reportname=' + name,
            data: 'querytosave=' + queryData,
            error: function () { if ($tt.opts.statusmsgdiv) $($tt.opts.statusmsgdiv).html("Can't save the report sql"); },
            success: function (data) 
            { 
            	if ($tt.opts.statusmsgdiv) $($tt.opts.statusmsgdiv).html(data);
                $("#txtSearchName").val(name);
                $("#lblId").text(searchId);
                $('#3009').show();

            }
        });
        return true;
    }
   }
   function newSearch()
   {
	   //location.reload(true);
	   window.location.href="newsearch.php";
   }

   function showSearchData(id)
    {
	    window.location.href="newsearch.php?id=" + id;
     }
    function getSearchData()
    {
       return $('.sqldata', $('.sqlbuild')).html();
    }

    
    function testSQL()
    {
//        var isSaved =  saveSearch();
//        if(!isSaved)
//        {
//            return false;
//        }
        var jsonData = getSearchData();   
        //alert(jsonData);
          $.ajax({
					type: 'GET',
					url:  'searchhandler.php' + '?op=testQuery',
					data: 'data=' + jsonData,
					success: function (data) {
        					//alert(data);
        					$("#3009").html(data);
        		            $("#3009").attr("style", "visibility:show");
        		        	
					}
        	});
        return false;
    		
  }
    
    function runSQL()
    {
//    	 var isSaved =  saveSearch();
//         if(!isSaved)
//         {
//             return false;
//         }
        var jsonData = getSearchData();   
        var url = 'queryresults.php' + '?op=runQuery&data=' + jsonData;
        window.location.href=url;
        return false;
    }
  </script>

<body>
<table>
	<tr>
		<td>
		<table border="0" width="100%">
			<tr>
				<td>
				<p id="3009" visible="false"></p>
				</td>
			</tr>

			<tr>
				<td valign="top">

				<table border="0">
					<tr>

						<td valign="top" style="border: 1px solid #ccc; width: 200px;">
						<p id="6000">
<?php 
	global $db;

	$out = "<ul class='treeview' id='treeview_9000'>";
	$out .= "<li class='list'>" . "Load saved search";
	//$out .= "</li>";
	$out .= '<ul style="display:block;">';
	$query = 'SELECT id,name,user FROM saved_searches WHERE user=' . $db->user->id . ' OR user IS NULL ORDER BY user';
	$res = mysql_query($query) or die('Bad SQL query getting saved search list');
	while($ss = mysql_fetch_assoc($res))
	{
		$out .= "<li class='item'> <a href='#' onclick='showSearchData(\"" . $ss['id'] . "\",\"" . htmlspecialchars($ss['name']) . "\");return false;'>" . htmlspecialchars($ss['name']) . "</a></li>";
		//$out .= "<li class='item'> <a href='javascript:void();return false;'>" . htmlspecialchars($ss['name']) . "</a></li>";
		//$out .= "<li class='item'>"  . htmlspecialchars($ss['name']) . "</li>";
		//$out .= "<li>"  . htmlspecialchars($ss['name']) . "</li>";

	}
	$out .= "</ul>";
	$out .= "</li>";
	//$out .= "</ul>";
	echo($out);
?>						
						</p>
						</td>

						<td>
						<table style="border: 1px solid #ccc;">
							<tr>
							</tr>
							<tr>
								<td style="padding-left: 20px;"><label id="lblSearch"
									style="font-weight: bold; color: Gray">Name </label> <input
									id="txtSearchName" name="txtSearchName" type="text" value="" />
								<label id="lblId" for="lblId" style="visibility: hidden;"> </label>
								</td>
							</tr>

							<tr>
								<td>
								<div class="sqlbuild"></div>
								</td>
							</tr>
						</table>
						</td>
					</tr>
				</table>
				</td>
				<td valign="top" align="left"></td>
			</tr>
			<tr>
			</tr>
			<tr>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td colspan="2">
				<p id="3001"></p>
				<br>

				</td>
			</tr>
			<tr>
				<td colspan="2">
				<p id="4000"></p>
				</td>
			</tr>

		</table>
		</td>
		<td valign="top" style="padding-top:15px">
		<table width="200px">
			<tr>
				<td class="graybk" style="text-align:center; font-weight : bold">
				Actions</td>
			</tr>
			<tr>
				<td style="padding-left: 30px; padding-top:20px"><input type="submit"
					onclick="newSearch();" style="width: 100px" value="New" id="btnNew" /></td>
			</tr>
			<tr>
				<td style="padding-left: 30px;"><input type="submit"
					style="width: 100px" onclick="saveSearch();" value="Save"
					id="btnsave" /></td>
			</tr>
			<tr>
				<td style="padding-left: 30px;"><input type="submit"
					style="width: 100px" onclick="testSQL();" value="Test Query"
					id="btnTest" /></td>
			</tr>
			<tr>
				<td style="padding-left: 30px;"><input type="submit"
					style="width: 100px" onclick="runSQL();" value="Run Query"
					id="btnRun" /></td>
			</tr>
		</table>
		</td>

	</tr>
</table>
</body>
