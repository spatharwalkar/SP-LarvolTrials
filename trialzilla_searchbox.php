<!-- SearchBox & Logo -->
<form action="trialzilla.php" method="post" name="trialzillaFrm" id="trialzillaFrm">
<table width="100%" border="0" style="padding-top:2px;">
<tr>
<td width="300px">
<a style="text-decoration:none;" href="trialzilla.php"><img src="images/Larvol-Trial-Logo-notag.png" width="300" height="47" /></a>
</td>
<td width="600px" style="vertical-align:bottom; padding-left:20px;" align="left">
<input class="SearchBox" type="text" value="<?php echo htmlspecialchars($globalOptions['TzSearch']); ?>" autocomplete="off" style="font-weight:bold;" name="TzSearch" id="TzSearch" onkeyup="javascript:autoComplete('TzSearch')" />
</td>
<td style="vertical-align:bottom; padding-left:10px;" align="left">
<input type="submit" name="Search" title="Search" value="Search" style="vertical-align:bottom;" class="SearchBttn1" />
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
</style>