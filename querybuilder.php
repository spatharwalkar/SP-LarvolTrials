<?php
require_once('db.php');
?>
      <head>
      <script type="text/javascript" src="scripts/jquery-1.3.2.js"></script>

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
    showcolumn: true,
    showsort: true,
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

    });

    function getQueryData()
    {
       return $('.sqldata', $('.sqlbuild')).html();
    }
    
  </script>

