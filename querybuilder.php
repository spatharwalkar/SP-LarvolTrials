<?php
require_once('db.php');
$show_sort_res = false;
 if(isset($show_sort_res_bool))
 {
 	$show_sort_res = $show_sort_res_bool;
 }
 
if(isset($_POST['delsch']) && is_array($_POST['delsch']))
{
	foreach($_POST['delsch'] as $ssid => $coord)
	{
		$query = 'DELETE FROM saved_searches WHERE id=' . mysql_real_escape_string($ssid) . ' AND (user='
					. $db->user->id . ($db->user->userlevel != 'user' ? ' OR user IS NULL' : '') . ') LIMIT 1';
		mysql_query($query) or die('Bad SQL query deleting saved search');
	}
}
?>

  <script type="text/javascript">
   $(document).ready(function () {

   $('.sqlbuild').sqlquerybuilder({
	fields: [
   <?php

    $my_table = 'data_trials';
    $query = "SELECT * FROM `information_schema`.`columns` WHERE `table_name`='data_trials' AND `table_schema`='".DB_NAME."';";
    $res = mysql_query($query) or die('Bad SQL query getting searchdata');
    $field_str='';
	while ($row = mysql_fetch_array($res, MYSQL_BOTH)) {
	    //printf ("ID: %s  Name: %s", $row[0], $row["name"]);
	    $name_val = $row["COLUMN_NAME"];
	    $data_type = $row["DATA_TYPE"];
	    $column_type = $row["COLUMN_TYPE"];
	    $default_value = $row["COLUMN_DEFAULT"];
	    if($data_type === 'tinyint')
	    {
	    	$column_type = "enum('0','1')";
	    	$data_type = "enum";

	    }
	    //printf ("Name: %s", $name_val);
	    $field_str .="{ field: '$name_val', name: '$name_val', id: 2, ftype: 'string', defaultval: '$default_value', type: '$data_type' , values:\"$column_type\"},";
     }
	 if($show_sort_res)
	 {
	 	$field_str .="{ field: 'product', name: 'product', id: 2, ftype: 'string', defaultval: '', type: 'product' , values:\"varchar(255)\"},";
	 	$field_str .="{ field: 'area', name: 'area', id: 2, ftype: 'string', defaultval: '', type: 'area' , values:\"varchar(255)\"},";
	}
	$field_str .="{ field: 'All', name: 'All', id: 2, ftype: 'string', defaultval: '', type: 'sphinx' , values:\"varchar(255)\"},";
     $field_str = substr($field_str, 0, -1); //strip last comma
     //$field_str .= "],";
     echo($field_str);
    ?>
    ],
    reportid: 9000,
    sqldiv: $('p#3000'),
    //presetlistdiv: $('p#6000'),
    reporthandler: 'searchhandler.php',
    datadiv: $('p#3001'),
    statusmsgdiv: $('p#3009'),
    showgroup: false,
    showcolumn: <?php echo $show_sort_res ? 'true' : 'false';?>,
    showsort: <?php echo $show_sort_res ? 'true' : 'false'; ?>,
    showwhere: true,
    joinrules: [
    { table1: 'INVCARDS', table2: 'INVTRANS', rulestr: 'JOIN INVTRANS ON INVCARDS.CODE=INVTRANS.CODE' },
    { table1: 'INVTRANS', table2: 'INVCARDS', rulestr: 'JOIN INVCARDS ON INVCARDS.CODE=INVTRANS.CODE' }
    ],
    onchange: function (type) {
    //alert('sqlbuilder:'+type);
    },

    onselectablelist: function (slotid, fieldid, operatorid, chainid) {
    var vals = 'XX,YY,ZZ,'; //+slotid+','+fieldid+','+operatorid+','+chainid;
    switch (fieldid) {

    case '3': //invcards unit
    vals += 'UN,KG,GR,TN';
    break;


    }
    return vals;
    }
    });



    $('a#5000').click(function () {
    $.ajax({
    type: 'POST',
    url: 'query.php',
    data: 'querytorun=' + $('p#3000').html(),
    error: function () { $('p#4000').html("Can run sql"); },
    success: function (data) { $('p#4000').html(data); }
    });
    return false;
    });


    $('a#5003').click(function () {
    alert($('.sqlbuild').getSQBClause('all'));
    return false;
    });


    $('.builder').appendTo('.querybuilder');

    });

   function saveSearch()
   {
	var $tt= $('.sqlbuild')[0];
    var openAndClose = $('.sqlbuild').checkMatchingBraces();
    if (openAndClose != 0) {
        $($tt.opts.statusmsgdiv).html("Can't save the search.Please match all the opening braces with relevant closing braces in the conditions section");
        $("#3009").attr("style", "visibility:show");
        return false;
    }
    var name = $("#txtSearchName").val();
    var searchId = $("label[for=" + "lblId" + "]").text();
    $("#3009").attr("style", "visibility:show");
	
	var mine_present=document.getElementById('mine_search');
	if(mine_present != null && mine_present != '')
	{
		if(document.getElementById('global_search').checked)
			var search_type='global';
		else if(document.getElementById('shared_search').checked)
			var search_type='shared';
		else
			var search_type='mine';
	}
	else
		var search_type='mine';

    if (jQuery.trim(searchId) == "") {//new search
        if (!name)
        {
            $($tt.opts.statusmsgdiv).html("Can't save the search. Please enter search name");
            $("#3009").attr("style", "visibility:show");
        	return false;
        }
        $.ajaxSetup({ cache: false });
        $.ajax({
            type: 'POST',
            async: false,
            url: $tt.opts.reporthandler + '?op=savenew&reportid=' + $tt.opts.reportid + '&reportname=' + name + '&search_type=' + search_type,
            data: 'querytosave=' + $('.sqldata', $('.sqlbuild')).html(),
            error: function () {
            if ($tt.opts.statusmsgdiv)
                $($tt.opts.statusmsgdiv).html("Can't save the report sql");
                return false;
            },
            success: function (data) {
            	if ($tt.opts.statusmsgdiv) $($tt.opts.statusmsgdiv).html( name + " saved....");
            	$("#txtSearchName").val(name);
            	var newItem='<li class="item"><table style="border:none; border-collapse:collapse;"><tr><td><a onclick="showSearchData(\''
                    + data + '\');return false;" href="#">' + name + '</a></td><td><input type="image" src="images/not.png" name="delsch[' +data+ ']" alt="delete" title="delete"/></td></tr></table></li>';
            	$('ul.treeview li.list').addClass('expanded').find('>ul').append(newItem);
                $('#3009').show();
				$("#copy_bttn").html('<input type="submit" style="width: 100px" onclick="copySearch();return false" value="Copy Search" id="btncopy" />');
            	}
        });

        return true;
    }
    else {
        $.ajaxSetup({ cache: false });

        var queryData = getQueryData();
        //alert(queryData);
        $.ajax({
            type: 'POST',
            async: false,
            url: $tt.opts.reporthandler + '?op=saveexists&searchId=' + searchId + '&reportname=' + name + '&search_type=' + search_type,
            data: 'querytosave=' + queryData,
            error: function () {
            if ($tt.opts.statusmsgdiv)
                $($tt.opts.statusmsgdiv).html("Can't save the search sql");
                return false;
            },
            success: function (data)
            {
            	if ($tt.opts.statusmsgdiv) $($tt.opts.statusmsgdiv).html(data);
                $("#txtSearchName").val(name);
				
				if(searchData.user_level == 'user')
				{
					if(searchData.search_type == 'mine')
					$("#ownership").html('Mine');
					else if(searchData.search_type == 'shared')
					$("#ownership").html('Shared');
					else
					$("#ownership").html('Global');
				}
				else
				{
					$("#ownership").html('<label><input type="radio" name="own" id="shared_search" value="shared" checked="" />Shared</label>'
                                    +'<label><input type="radio" name="own" id="global_search" value="global" checked="" />Global</label>'
                                    +'<label><input type="radio" name="own" id="mine_search" value="mine" checked="checked" />Mine</label>');
					
					if(searchData.search_type == 'mine')
					document.getElementById('mine_search').checked = true;
					else if(searchData.search_type == 'shared')
					document.getElementById('shared_search').checked = true;
					else
					document.getElementById('global_search').checked = true;
				}
				
				if(searchData.search_type == 'mine' || (searchData.search_type == 'global' && searchData.user_level != 'user') || (searchData.search_type == 'shared' && searchData.current_user == searchData.search_user))
				{
					$("#save_bttn").html('<input type="submit" style="width: 100px" onclick="saveSearch();return false" value="Save" id="btnsave" />');
					var own_radio = document.getElementById('shared_search');
					if(own_radio != null && own_radio != '')
					{
						document.getElementById('shared_search').disabled = false;
						document.getElementById('global_search').disabled = false;
						document.getElementById('mine_search').disabled = false;
					}
					document.getElementById('txtSearchName').disabled = false;
				}
				else
				{
					$("#save_bttn").html('');
					var own_radio = document.getElementById('shared_search');
					if(own_radio != null && own_radio != '')
					{
						document.getElementById('shared_search').disabled = true;
						document.getElementById('global_search').disabled = true;
						document.getElementById('mine_search').disabled = true;
					}
					document.getElementById('txtSearchName').disabled = true;
				}
				
				$("#copy_bttn").html('<input type="submit" style="width: 100px" onclick="copySearch();return false" value="Copy Search" id="btncopy" />');
				
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
	   //window.location.href="newsearch.php";
	   $('#txtSearchName').val('');
	   $('#lblId').text('');
	   loadQueryData('');
	   $("#save_bttn").html('<input type="submit" style="width: 100px" onclick="saveSearch();return false" value="Save" id="btnsave" />');
	   var own_radio = document.getElementById('shared_search');
		if(own_radio != null && own_radio != '')
		{
			document.getElementById('shared_search').disabled = false;
			document.getElementById('global_search').disabled = false;
			document.getElementById('mine_search').disabled = false;
		}
		document.getElementById('txtSearchName').disabled = false;
		$("#copy_bttn").html('');

   }

   function showSearchData(id)
    {
       $('#3009').hide();
	   var $tt= $('.sqlbuild')[0];
	    //window.location.href="newsearch.php?id=" + id;
       $.ajax({
           type: 'POST',
           async: false,
           url: $tt.opts.reporthandler + '?op=getsearchdata&id=' + id,
           data: 'querytosave=',
           error: function () {
           if ($tt.opts.statusmsgdiv)
               $($tt.opts.statusmsgdiv).html("Can't get the search data");
               return false;
           },
           success: function (data)
           {
        	   var searchData = eval('(' + data + ')');
               loadQueryData(searchData.searchdata);
               $("#txtSearchName").val(searchData.name);
               $("#lblId").text(searchData.id);
			   
			   if(searchData.user_level == 'user')
				{
					if(searchData.search_type == 'mine')
					$("#ownership").html('Mine');
					else if(searchData.search_type == 'shared')
					$("#ownership").html('Shared');
					else
					$("#ownership").html('Global');
				}
				else
				{
					$("#ownership").html('<label><input type="radio" name="own" id="shared_search" value="shared" checked="" />Shared</label>'
                                    +'<label><input type="radio" name="own" id="global_search" value="global" checked="" />Global</label>'
                                   +'<label><input type="radio" name="own" id="mine_search" value="mine" checked="checked" />Mine</label>');
					
					if(searchData.search_type == 'mine')
					document.getElementById('mine_search').checked = true;
					else if(searchData.search_type == 'shared')
					document.getElementById('shared_search').checked = true;
					else
					document.getElementById('global_search').checked = true;
				}
				
				if(searchData.search_type == 'mine' || (searchData.search_type == 'global' && searchData.user_level != 'user') || (searchData.search_type == 'shared' && searchData.current_user == searchData.search_user))
				{
					$("#save_bttn").html('<input type="submit" style="width: 100px" onclick="saveSearch();return false" value="Save" id="btnsave" />');
					var own_radio = document.getElementById('shared_search');
					if(own_radio != null && own_radio != '')
					{
						document.getElementById('shared_search').disabled = false;
						document.getElementById('global_search').disabled = false;
						document.getElementById('mine_search').disabled = false;
					}
					document.getElementById('txtSearchName').disabled = false;
				}
				else
				{
					$("#save_bttn").html('');
					var own_radio = document.getElementById('shared_search');
					if(own_radio != null && own_radio != '')
					{
						document.getElementById('shared_search').disabled = true;
						document.getElementById('global_search').disabled = true;
						document.getElementById('mine_search').disabled = true;
					}
					document.getElementById('txtSearchName').disabled = true;
				}
				$("#copy_bttn").html('<input type="submit" style="width: 100px" onclick="copySearch();return false" value="Copy Search" id="btncopy" />');
			}
       });
       return false;
    }

    function getQueryData()
    {
       return $('.sqldata', $('.sqlbuild')).html();
    }

    function loadQueryData(data)
    {
    	$("#txtSearchName").val('');
        $("#lblId").text('');
    	$('.sqlbuild').loadSQB(data);
		
		<?php
			global $db;
			if($db->user->userlevel == 'user')
			print '$("#ownership").html(\'Mine\');';
			else
			print 'document.getElementById(\'mine_search\').checked = true;'
		?>
    }
	
	function autoComplete(type, fieldID)
	{	
		$(function()
		{
			if($('#'+fieldID).length > 0)
			{	
				var pattern1 =/products/g;
				var pattern2 =/areas/g;
				
				if(type=='products')
				{	
					var a = $('#'+fieldID).autocomplete({
								serviceUrl:'autosuggest.php',
								params:{table:'products', field:'name'},
								minChars:3 
					});
				}
				else if(type=='areas')
				{
					var a = $('#'+fieldID).autocomplete({
								serviceUrl:'autosuggest.php',
								params:{table:'areas', field:'name'},
								minChars:3 
					}); 
				}
				else if(type=='sphinx')
				{
					var a = $('#'+fieldID).autocomplete({
								serviceUrl:'sphinx_autosuggest.php',
								params:{table:'areas', field:'name'},
								minChars:3
					}); 
				}
			}
		});
	}
	
	function PadNCT_JSFn(fieldID)
	{	
		var Field_value = $('#'+fieldID).val();
		
		if(Field_value != '' && Field_value != null)
		{	
			var Field_value = Field_value.replace(/\s+/g, '') ;
			
			var Field_value_Arr = Field_value.split(',');
			
			for(var i=0; i<Field_value_Arr.length; i++)
			{
				fld = Field_value_Arr[i];
				if(fld.substring(0, 3) != 'NCT')
				{
					fld=("00000000" + fld).slice(-8);
					Field_value_Arr[i] = 'NCT'+fld;
				}
			}
			$('#'+fieldID).val(Field_value_Arr.join(', '));
		}
	}
	
	function copySearch()
	{
		var $tt= $('.sqlbuild')[0];
		var name = $("#txtSearchName").val();
    	var searchId = $("label[for=" + "lblId" + "]").text();
    	$("#3009").attr("style", "visibility:show");
		
		$.ajax({
            type: 'POST',
            async: false,
            url: $tt.opts.reporthandler + '?op=copySearch&reportid=' + searchId,
            data: '',
            error: function () {
            if ($tt.opts.statusmsgdiv)
                $($tt.opts.statusmsgdiv).html("Can't Copy the report...");
                return false;
            },
            success: function (data) {
            	if ($tt.opts.statusmsgdiv) $($tt.opts.statusmsgdiv).html( "Search\""+name+"\" Copied Successfully....");
            	var newItem='<li class="item"> <table style="border:none; border-collapse:collapse;"><tr><td><a onclick="showSearchData(\''
                    + data + '\');return false;" href="#">Copy Of ' + name + '</a></td><td><input type="image" src="images/not.png" name="delsch[' +data+ ']" alt="delete" title="delete"/></td></tr></table></li>';
            	$('ul.treeview li.list').addClass('expanded').find('>ul').append(newItem);
                showSearchData(data);
				$('#3009').show();
            	}
        });
	}
	

  </script>
<div class="builder">
<table>
	<tr>
		<td>
		<table border="0" width="100%">
			<tr>
				<td colspan="2">
				<p id="3009" visible="false"></p>
				</td>
			</tr>

			<tr>
		<td valign="top">
		<table border="0">
		 	<tr>
				<td style="padding-left: 30px;"><input type="submit"
					onclick="newSearch();" style="width: 100px" value="New" id="btnNew" /></td>
			</tr>
			<tr>
				<td id="save_bttn" style="padding-left: 30px;"><input type="submit"
					style="width: 100px" onclick="saveSearch();return false" value="Save"
					id="btnsave" /></td>
			</tr>
            <tr>
				<td id="copy_bttn" style="padding-left: 30px;"></td>
			</tr>


	         <tr>

						<td valign="top" style="border: 1px solid #ccc; width: 200px;">
                        <form method="post" action="newsearch.php" class="lisep">
						<p id="6000">
<?php
	global $db;

	$out = "<ul class='treeview' id='treeview_9000'>";
	$out .= "<li class='list'>" . "Load saved search";
	//$out .= "</li>";
	$out .= '<ul style="display:block;">';
	$query = 'SELECT id,name,user FROM saved_searches WHERE user=' . $db->user->id . ' OR user IS NULL OR shared=1 ORDER BY user';
	$res = mysql_query($query) or die('Bad SQL query getting saved search list');
	while($ss = mysql_fetch_assoc($res))
	{
		$out .= "<li class='item'><table style=\"border:none; border-collapse:collapse;\"><tr><td><a href='#' onclick='showSearchData(\"" . $ss['id'] . "\");return false;'>" . htmlspecialchars($ss['name']) . "</a></td><td> ";
		$out .= ((($ss['user'] === NULL && $db->user->userlevel != 'user') || ($ss['user'] !== NULL && $ss['user'] == $db->user->id)) ? '<input type="image" src="images/not.png" name="delsch[' . $ss['id'] . ']" alt="delete" title="delete"/>':'');
		$out .= "</td></tr></table></li>";
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
					</form>	</td>
                  </tr>
                  </table>
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
                                    <label style="font-weight: bold; color: Gray">Ownership: </label>
                                    <font id="ownership" style="color:#333333;">
									<?php print (($db->user->userlevel == 'user') ? 'Mine' : '<label><input type="radio" name="own" id="shared_search" value="shared" />Shared</label>
                                    <label><input type="radio" name="own" id="global_search" value="global" />Global</label>
                                    <label><input type="radio" name="own" id="mine_search" value="mine" checked="checked" />Mine</label>') ?></font>
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


	</tr>
</table>
<!-- pre cache loader -->
<img style="display:none" src="images/loading.gif" alt="loading" title="loading"/>
</div>
