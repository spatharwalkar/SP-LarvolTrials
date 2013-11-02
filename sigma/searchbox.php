<!--feedback button-->
<?php include "feedback.php";?>
<!-- SearchBox & Logo -->
<form action="index.php" method="post" name="trialzillaFrm" id="trialzillaFrm">
<table width="100%" border="0" style="padding-top:2px;">
<tr>
<td width="300px">
<a style="text-decoration:none;" href="."><img src="../images/larvol_sigma_logo_beta.jpg" width="300" height="58" style="border: none;margin-top:-8px;" /></a>
</td>
<td width="600px" style="vertical-align:bottom; padding-left:20px;" align="left">
<input class="SearchBox" type="text" value="<?php echo htmlspecialchars($globalOptions['TzSearch']); ?>" autocomplete="off" style="font-weight:bold;" name="TzSearch" id="TzSearch" />
</td>
<td width="105px" style="vertical-align:bottom; padding-left:10px;" align="left">
<input type="submit" name="Search" title="Search" value="Search" style="vertical-align:bottom;" class="SearchBttn1" />
</td>
<td style="vertical-align:middle; padding-left:10px;padding-top:15px;" align="left">&nbsp;
</td>
</tr>
<tr>
<td width="300px">&nbsp;</td>
<td width="600px" style="font-weight:bold; padding-left:0px;" align="center">
<font class="searchTypes" style="color:#666666;"><a href="index.php?class=Institution" style="text-decoration:underline; display:inline; color:#1122cc;">Companies</a> / <a href="index.php?class=Product" style="text-decoration:underline; display:inline; color:#1122cc;">Products</a> / <a href="index.php?class=MOA" style="text-decoration:underline; display:inline; color:#1122cc;">Mechanisms of Action</a> / <a href="index.php?class=Disease" style="text-decoration:underline; display:inline; color:#1122cc;">Diseases</a> / <a href="index.php?class=Investigator" style="text-decoration:underline; display:inline; color:#1122cc;">Investigators</a></font>
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
	background:url(../images/shadow.png) no-repeat bottom right; 
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
					serviceUrl:'../autosuggest.php',
					params:{table:'trialzilla', field:'name'},
					minChars:3,
					width:600
			});
		}
	});
}
</script>
