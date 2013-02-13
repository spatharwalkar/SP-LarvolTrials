<!-- SearchBox & Logo -->
<form action="trialzilla.php" method="post" name="trialzillaFrm" id="trialzillaFrm">
<table width="100%" border="0" style="padding-top:2px;">
<tr>
<td width="300px">
<a style="text-decoration:none;" href="trialzilla.php"><img src="images/larvol_sigma_logo.gif" width="300" height="50" style="border: none;" /></a>
</td>
<td width="600px" style="vertical-align:bottom; padding-left:20px;" align="left">
<input class="SearchBox" type="text" value="<?php echo htmlspecialchars($globalOptions['TzSearch']); ?>" autocomplete="off" style="font-weight:bold;" name="TzSearch" id="TzSearch" />
</td>
<td width="105px" style="vertical-align:bottom; padding-left:10px;" align="left">
<input type="submit" name="Search" title="Search" value="Search" style="vertical-align:bottom;" class="SearchBttn1" />
</td>
<td style="vertical-align:middle; padding-left:10px;padding-top:15px;" align="left">
<a href="trialzilla.php?Disease=true" style="text-decoration:underline;">List Diseases</a>
</td>
</tr>
<tr>
<td width="300px">&nbsp;</td>
<td width="600px" style="font-weight:bold; padding-left:0px;" align="center">
<font class="searchTypes" style="color:#666666;">Search for Company / Product / MOA</font>
</td>
</tr>
</table>
</form>
<style type="text/css">
.SearchBox
{
	/*outline:none;*/
	height:27px;
	width:600px;
}
.SearchBox:focus
{
	box-shadow:inset 0px 1px 2px rgba(0,0,0,0.3);
	-moz-box-shadow:inset 0px 1px 2px rgba(0,0,0,0.3);
	-webkit-box-shadow:inset 0px 1px 2px rgba(0,0,0,0.3);
	-moz-border-radius:1px;
	-webkit-border-radius:1px;
	border-radius:1px;
	border:1px solid #4d90fe;
	outline:none;
	height:27px;
}

.searchTypes
{
	font-weight:bold;
	font-size:12px;
}

.autocomplete-w1 
{ 
	background:url(./images/shadow.png) no-repeat bottom right; 
	position:absolute; 
	top:0px; 
	left:0px; 
	margin:8px 0 0 6px; 
	/* IE6 fix: */ _background:none; _margin:0; 
}
.autocomplete 
{ 
	border:1px solid #999; 
	background:#FFF; 
	cursor:default; 
	text-align:left; 
	max-height:350px; 
	overflow:auto; 
	margin:-6px 6px 6px -6px; 
	/* IE6 specific: */ 
	_height:350px;  
	_margin:0;
	_overflow-x:hidden;
}
.autocomplete .selected { 
	background:#F0F0F0; 
}
.autocomplete div { 
	padding:2px 5px; 
	white-space:wrap;
}
.autocomplete strong { 
	font-weight:normal; 
	color:#3399FF; 
}
</style>
<script type="text/javascript" src="scripts/autosuggest/jquery.autocomplete-min.js"></script>
<script type="text/javascript">
	$(function() 
	{
		$('body').keydown(function(e)
		{	
			if (e.keyCode == 13) 
			{
			  $('#trialzillaFrm').submit();
			} 
		});
	}); 
	
</script>
<script type="text/javascript">
function autoComplete(fieldID)
{	
	$(function()
	{
		if($('#'+fieldID).length > 0)
		{	
			var a = $('#'+fieldID).autocomplete({
					serviceUrl:'autosuggest.php',
					params:{table:'trialzilla', field:'name'},
					minChars:3,
					width:600
			});
		}
	});
}
</script>