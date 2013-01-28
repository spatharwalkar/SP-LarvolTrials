<?php
	require_once('db.php');
	require_once('product_tracker.php');
	if($_REQUEST['CompanyId'] != NULL && $_REQUEST['CompanyId'] != '' && isset($_REQUEST['CompanyId']))
	{
		$CompanyId = $_REQUEST['CompanyId'];
		$query = 'SELECT `name`, `id` FROM `institutions` WHERE `id`=' . mysql_real_escape_string($CompanyId);
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$CompanyId = $header['id'];
		$CompanyName = $header['name'];				
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Larvol Trials :: Trialzilla</title>
<style type="text/css">
body
{
	font-family:Arial;
	font-size:14px;
	color:#000000;
}

a {color:#1122cc;}      /* unvisited link */
a:visited {color:#6600bc;}  /* visited link */
/*a:hover {color:#FF00FF;}  /* mouse over link */
/*a:active {color:#0000FF;}  /* selected link */

.ReportHeading
{
	color: rgb(83, 55, 130);
	font-size: xx-large;
	font-weight: bold;
}

.SearchBttn1
{
	width:100px;
	height:35px;
	background-color:#4f2683;
	font-weight:bold;
	color:#FFFFFF;
}

.FoundResultsTb
{
	background-color:#aa8ece;
	border:0;
	border-top:#4f2683 solid 2px;
}
</style>
<style type="text/css">

/* As in IE6 hover css does not works, below htc file is added which contains js script which will be executed only in IE, the script convert simple as well as complex hover css into compatible format for IE6 by replacing hover by class css - this file is used so that help tab as well as product selector will work in IE6 without any changes of code as well as css code and script can be also useful for making other css to work in IE6 like :hover and :active for IE6+, and additionally :focus for IE7 and IE8. */
ul, li, slideout { behavior:url("css/csshover3.htc"); }
img { behavior: url("css/iepngfix.htc"); }

a, a:hover{ height:100%; width:100%; display:block; text-decoration:none;}

.report_name {
	font-weight:bold;
	font-size:18px;
}

.controls td{
	border-bottom:1px solid #44F;
	border-right:1px solid #44F;
	padding: 10px 0 0 3px;
    vertical-align: top;
}
.controls th{
	font-weight:normal;
	border-bottom: 1px solid #4444FF;
    border-right: 1px solid #4444FF;
}
.right{
	border-right:0px !important;
}

.bottom{
	border-bottom:0px !important;
}
.controls input{
	margin:0.1em;
}

#slideout {
	position: fixed;
	_position:absolute;
	top: 200px;
	right: 0;
	margin: 12px 0px 0 0;
}

.slideout_inner {
	position:absolute;
	top: 200px;
	right: -255px;
	display:none;
}

#slideout:hover .slideout_inner{
	display : block;
	position:absolute;
	top: 2px;
	right: 0px;
	width: 280px;
	z-index:10;
}

.table-slide{
	border:1px solid #000;
	height:100px;
	width:280px;
}
.table-slide td{
	border-right:1px solid #000;
	padding:8px;
	padding-right:20px;
	border-bottom:1px solid #000;
}

.gray {
	background-color:#CCCCCC;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.blue {
	background-color:#00ccff;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.green {
	background-color:#99cc00;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.yellow {
	background-color:#ffff00;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.orange {
	background-color:#ff9900;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.red {
	background-color:#ff0000;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.downldbox {
	height:auto;
	width:310px;
	font-weight:bold;
}

.downldbox ul{
	list-style:none;
	margin:5px;
	padding:0px;
}

.downldbox ul li{
	width: 130px;
	float:left;
	margin:2px;
}

.dropmenudiv{
	position:absolute;
	top: 0;
	border: 1px solid #DDDDDD; /*THEME CHANGE HERE*/
	/*border-bottom-width: 0;*/
	font:normal 12px Verdana;
	line-height:18px;
	z-index:100;
	background-color: white;
	width: 50px;
	visibility: hidden;
}

.break_words{
	word-wrap: break-word;
}

.tag {
	color:#120f3c;
	font-weight:bold;
}

.graph_bottom {
	border-bottom:1px solid #CCCCCC;
}

th { 
	font-weight:normal; 
}

.last_tick_height {
	height:4px;
}

.last_tick_width {
	width:4px;
}

.graph_top {
	border-top:1px solid #CCCCCC;
}

.graph_right {
	border-right:1px solid #CCCCCC;
}

.graph_rightWhite {
	border-right:1px solid #FFFFFF;
}

.prod_col {
	width:420px;
	max-width:420px;
	word-wrap: break-word;
}

.side_tick_height {
	height:1px;
	line-height:1px;
}

.graph_gray {
	background-color:#CCCCCC;
}

.graph_blue {
	background-color:#00ccff;
}

.graph_green {
	background-color:#99cc00;
}

.graph_yellow {
	background-color:#ffff00;
}

.graph_orange {
	background-color:#ff9900;
}

.graph_red {
	background-color:#ff0000;
}

.Link {
height:20px;
min-height:20px;
max-height:20px;
padding:0px;
margin:0px;
_height:20px;
}

.tag {
color:#120f3c;
font-weight:normal;
}
</style>
<script src="scripts/jquery-1.7.1.min.js"></script>
<script src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
<script type="text/javascript" src="scripts/chrome.js"></script>
<script type="text/javascript" src="scripts/iepngfix_tilebg.js"></script>
<script language="javascript" type="text/javascript">
function change_view()
{
	var limit = document.getElementById('Tot_rows').value;
	var dwcount = document.getElementById('dwcount');
	
	var i=0;
	for(i=0;i<limit;i++)
	{
		if(dwcount.value == 'active')
		{
			var row_type = document.getElementById('active_Graph_Row_A_'+i);
			if(row_type != null && row_type != '')
			{
				<?php if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') == FALSE) { ?>
				document.getElementById("active_Graph_Row_A_"+i).style.display = "table-row";
				document.getElementById("active_Graph_Row_B_"+i).style.display = "table-row";
				document.getElementById("active_Graph_Row_C_"+i).style.display = "table-row";
				<? } else { ?>
				document.getElementById("active_Graph_Row_A_"+i).style.display = "inline";
				document.getElementById("active_Graph_Row_B_"+i).style.display = "inline";
				document.getElementById("active_Graph_Row_C_"+i).style.display = "inline";
				<?php } ?>
				document.getElementById("total_Graph_Row_A_"+i).style.display = "none";
				document.getElementById("total_Graph_Row_B_"+i).style.display = "none";
				document.getElementById("total_Graph_Row_C_"+i).style.display = "none";
				document.getElementById("indlead_Graph_Row_A_"+i).style.display = "none";
				document.getElementById("indlead_Graph_Row_B_"+i).style.display = "none";
				document.getElementById("indlead_Graph_Row_C_"+i).style.display = "none";
			}
		}
		else if(dwcount.value == 'total')
		{
			var row_type = document.getElementById('total_Graph_Row_A_'+i);
			if(row_type != null && row_type != '')
			{
				document.getElementById("active_Graph_Row_A_"+i).style.display = "none";
				document.getElementById("active_Graph_Row_B_"+i).style.display = "none";
				document.getElementById("active_Graph_Row_C_"+i).style.display = "none";
				<?php if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') == FALSE) { /// IE does not support table-row and moziall does not support inline ?>
				document.getElementById("total_Graph_Row_A_"+i).style.display = "table-row";
				document.getElementById("total_Graph_Row_B_"+i).style.display = "table-row";
				document.getElementById("total_Graph_Row_C_"+i).style.display = "table-row";
				<? } else { ?>
				document.getElementById("total_Graph_Row_A_"+i).style.display = "inline";
				document.getElementById("total_Graph_Row_B_"+i).style.display = "inline";
				document.getElementById("total_Graph_Row_C_"+i).style.display = "inline";
				<?php } ?>
				document.getElementById("indlead_Graph_Row_A_"+i).style.display = "none";
				document.getElementById("indlead_Graph_Row_B_"+i).style.display = "none";
				document.getElementById("indlead_Graph_Row_C_"+i).style.display = "none";
			}
		}
		else
		{
			var row_type = document.getElementById('indlead_Graph_Row_A_'+i);
			if(row_type != null && row_type != '')
			{
				document.getElementById("active_Graph_Row_A_"+i).style.display = "none";
				document.getElementById("active_Graph_Row_B_"+i).style.display = "none";
				document.getElementById("active_Graph_Row_C_"+i).style.display = "none";
				document.getElementById("total_Graph_Row_A_"+i).style.display = "none";
				document.getElementById("total_Graph_Row_B_"+i).style.display = "none";
				document.getElementById("total_Graph_Row_C_"+i).style.display = "none";
				<?php if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') == FALSE) { ?>
				document.getElementById("indlead_Graph_Row_A_"+i).style.display = "table-row"; 
				document.getElementById("indlead_Graph_Row_B_"+i).style.display = "table-row";
				document.getElementById("indlead_Graph_Row_C_"+i).style.display = "table-row";
				<? } else { ?>
				document.getElementById("indlead_Graph_Row_A_"+i).style.display = "inline";
				document.getElementById("indlead_Graph_Row_B_"+i).style.display = "inline";
				document.getElementById("indlead_Graph_Row_C_"+i).style.display = "inline";
				<?php } ?>
			}
		}
			
	}	
}
</script>


</head>

<body>
<?php include "trialzilla_searchbox.php";?>
<!-- Number of Results -->
<br/>
<table width="100%" border="0" class="FoundResultsTb">
	<tr>
    	<td width="50%" style="border:0; font-weight:bold; padding-left:5px;" align="left">
        	Product Tracker for "<?php print $CompanyName; ?>"
        </td>
    </tr>
</table>

<!-- Displaying Records -->
<br/>
<table width="100%" border="0" style="">
<tr><td>
<?php print showProductTracker($CompanyId, 'CT'); ?>
</td></tr>
</table>
</body>
</html>
<script type="text/javascript">
        var currentFixedHeader;
        var currentGhost;
		var ScrollOn = false;
		
		//Start - Header recreation in case of window resizing
		$(window).resize(function() {
			$.fn.reverse = [].reverse;
			var createGhostHeader = function (header, topOffset, leftOffset) {
                // Recreate heaaderin case of window resizing even if there is current ghost header exists
              if (currentGhost)
                    $(currentGhost).remove();
                
                var realTable = $(header).parents('#ProdTrackerTable');
                
                var headerPosition = $(header).offset();
                var tablePosition = $(realTable).offset();
                
                var container = $('<table border="0" cellspacing="0" cellpadding="0" style="vertical-align:middle; background-color:#FFFFFF;" id="ProdTrackerTable1"></table>');
                
                // Copy attributes from old table (may not be what you want)
                for (var i = 0; i < realTable[0].attributes.length; i++) {
                    var attr = realTable[0].attributes[i];
					//We are not manually copying table attributes so below line is commented cause it does not work in IE6 and IE7
                    //container.attr(attr.name, attr.value);
                }
                                
                // Set up position of fixed row
                container.css({
                    position: 'fixed',
                    top: -topOffset,
                    left: (-$(window).scrollLeft() + leftOffset),
                    width: $(realTable).outerWidth()
                });
                
                // Create a deep copy of our actual header and put it in our container
                var newHeader = $(header).clone().appendTo(container);
                
                var collection2 = $(newHeader).find('td');
                
                // TODO: Copy the width of each <td> manually
                $(header).find('td').each(function () {
                    var matchingElement = $(collection2.eq($(this).index()));
                    $(matchingElement).width(this.offsetWidth + 0.5);
                });
				
                currentGhost = container;
                currentFixedHeader = header;
                
                // Add this fixed row to the same parent as the table
                $(table).parent().append(currentGhost);
                return currentGhost;
            };

            var currentScrollTop = $(window).scrollTop();

            var activeHeader = null;
            var table = $('#ProdTrackerTable').first();
            var tablePosition = table.offset();
            var tableHeight = table.height();
            
            var lastHeaderHeight = $(table).find('thead').last().height();
            var topOffset = 0;
            
            // Check that the table is visible and has space for a header
            if (tablePosition.top + tableHeight - lastHeaderHeight >= currentScrollTop)
            {
                var lastCheckedHeader = null;
                // We do these in reverse as we want the last good header
                var headers = $(table).find('thead').reverse().each(function () {
                    var position = $(this).offset();
                    
                    if (position.top <= currentScrollTop)
                    {
                        activeHeader = this;
                        return false;
                    }
                    
                    lastCheckedHeader = this;
                });
                
                if (lastCheckedHeader)
                {
                    var offset = $(lastCheckedHeader).offset();
                    if (offset.top - currentScrollTop < $(activeHeader).height())
                        topOffset = $(activeHeader).height() - (offset.top - currentScrollTop) + 1;
                }
            }
            // No row is needed, get rid of one if there is one
            if (activeHeader == null && currentGhost)

            {
                currentGhost.remove();

                currentGhost = null;
                currentFixedHeader = null;
            }
            
            // We have what we need, make a fixed header row
            if (activeHeader)
			{
                createGhostHeader(activeHeader, topOffset, ($('#ProdTrackerTable').offset().left));
			}
		});
		//End - Header recreation in case of window resizing
		
        ///Start - Header creation or align header incase of scrolling
		$(window).scroll(function() {
            $.fn.reverse = [].reverse;
			if(!ScrollOn)
			{
            	ScrollOn = true;
			}
            var createGhostHeader = function (header, topOffset, leftOffset) {
                // Don't recreate if it is the same as the current one
                if (header == currentFixedHeader && currentGhost)
                {
                    currentGhost.css('top', -topOffset + "px");
					currentGhost.css('left',(-$(window).scrollLeft() + leftOffset) + "px");
                    return currentGhost;
                }
                
                if (currentGhost)
                    $(currentGhost).remove();
                
                var realTable = $(header).parents('#ProdTrackerTable');
                
                var headerPosition = $(header).offset();
                var tablePosition = $(realTable).offset();
                
                var container = $('<table border="0" cellspacing="0" cellpadding="0" style="vertical-align:middle; background-color:#FFFFFF;" id="ProdTrackerTable1"></table>');
                
                // Copy attributes from old table (may not be what you want)
                for (var i = 0; i < realTable[0].attributes.length; i++) {
                    var attr = realTable[0].attributes[i];
					//We are not manually copying table attributes so below line is commented cause it does not work in IE6 and IE7
                    //container.attr(attr.name, attr.value);
                }
                                
                // Set up position of fixed row
                container.css({
                    position: 'fixed',
                    top: -topOffset,
                    left: (-$(window).scrollLeft() + leftOffset),
                    width: $(realTable).outerWidth()
                });
                
                // Create a deep copy of our actual header and put it in our container
                var newHeader = $(header).clone().appendTo(container);
                
                var collection2 = $(newHeader).find('td');
                
                // TODO: Copy the width of each <td> manually
                $(header).find('td').each(function () {
                    var matchingElement = $(collection2.eq($(this).index()));
                    $(matchingElement).width(this.offsetWidth + 0.5);
                });
				
                currentGhost = container;
                currentFixedHeader = header;
                
                // Add this fixed row to the same parent as the table
                $(table).parent().append(currentGhost);
                return currentGhost;
            };

            var currentScrollTop = $(window).scrollTop();

            var activeHeader = null;
            var table = $('#ProdTrackerTable').first();
            var tablePosition = table.offset();
            var tableHeight = table.height();
            
            var lastHeaderHeight = $(table).find('thead').last().height();
            var topOffset = 0;
            
            // Check that the table is visible and has space for a header
            if (tablePosition.top + tableHeight - lastHeaderHeight >= currentScrollTop)
            {
                var lastCheckedHeader = null;
                // We do these in reverse as we want the last good header
                var headers = $(table).find('thead').reverse().each(function () {
                    var position = $(this).offset();
                    
                    if (position.top <= currentScrollTop)
                    {
                        activeHeader = this;
                        return false;
                    }
                    
                    lastCheckedHeader = this;
                });
                
                if (lastCheckedHeader)
                {
                    var offset = $(lastCheckedHeader).offset();
                    if (offset.top - currentScrollTop < $(activeHeader).height())
                        topOffset = $(activeHeader).height() - (offset.top - currentScrollTop) + 1;
                }
            }
            // No row is needed, get rid of one if there is one
            if (activeHeader == null && currentGhost)

            {
                currentGhost.remove();

                currentGhost = null;
                currentFixedHeader = null;
            }
            
            // We have what we need, make a fixed header row
            if (activeHeader)
			{
                createGhostHeader(activeHeader, topOffset, ($('#ProdTrackerTable').offset().left));
			}
        });
		///End - Header creation or align header incase of scrolling
</script>