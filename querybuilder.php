<?php
require_once('db.php');
$show_sort_res = false;
 if(isset($show_sort_res_bool))
 {
 	$show_sort_res = $show_sort_res_bool;
 }
?>
      <head>
      <script type="text/javascript" src="scripts/jquery.sqlbuilder-0.06.js"></script>

      <link rel="stylesheet" type="text/css" href="css/jquery.sqlbuilder.css" />
      <style type="text/css">
        #sqlreport
        {
        /* border: 1px solid #ccc;*/
        position: relative;
        width: 1200px;
        height: 600px;
        margin: 5px;
        padding: 5px;
        font-family: "Verdana" , "Tahoma" , Arial;
        font-size: 12px;
        overflow: auto;
        }
        .sqlbuild
        {
        /*       border: 1px solid #ccc; /*	float:left;*/
        position: relative;
        width: 800px;
        margin: 5px;
        padding: 5px;
        font-family: "Verdana" , "Tahoma" , Arial;
        font-size: 12px;
        overflow: auto;
        }
        .sqlsyntaxhelp
        {
        font-family: "Verdana" , "Tahoma" , Arial;
        font-size: 10px;
        color: #FF0000;
        }
      </style>
    </head>

  <script type="text/javascript">
   $(document).ready(function () {

   $('.sqlbuild').sqlquerybuilder({  
	fields: [ 
   <?php
   
    $my_table = 'data_trials';
    $query = "select * from information_schema.columns where table_name='data_trials';";
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
            url: $tt.opts.reporthandler + '?op=savenew&reportid=' + $tt.opts.reportid + '&reportname=' + name,
            data: 'querytosave=' + $('.sqldata', $('.sqlbuild')).html(),
            error: function () { 
            if ($tt.opts.statusmsgdiv) 
                $($tt.opts.statusmsgdiv).html("Can't save the report sql"); 
                return false;
            },
            success: function (data) { 
            	if ($tt.opts.statusmsgdiv) $($tt.opts.statusmsgdiv).html( name + " saved...."); 
            	$("#txtSearchName").val(name);
            	var newItem='<li class="item"> <a onclick="showSearchData(\''
                    + data + ');return false;" href="#">' + name + '</a> </li>';
            	$('ul.treeview li.list').addClass('expanded').find('>ul').append(newItem);
                $('#3009').show();
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
            url: $tt.opts.reporthandler + '?op=saveexists&searchId=' + searchId + '&reportname=' + name,
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
	   loadQueryData(' ');
	   
   }

   function showSearchData(id)
    {
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
    }
    
  </script>
<body>
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
				<td style="padding-left: 30px;"><input type="submit"
					style="width: 100px" onclick="saveSearch();return false" value="Save"
					id="btnsave" /></td>
			</tr>

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
		$out .= "<li class='item'> <a href='#' onclick='showSearchData(\"" . $ss['id'] . "\");return false;'>" . htmlspecialchars($ss['name']) . "</a></li>";
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
</div>
</body>

