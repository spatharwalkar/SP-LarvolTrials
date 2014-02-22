<?php
include("comments.php");
$cwd = getcwd();
chdir ("..");
include_once('ga.php');
chdir ($cwd);
?>
<style type="text/css">
#footer-spacer {
    height: 36px;
}
#footer {
    border-top: 1px solid #333;
    height: 20px;
    position:fixed;
    width:99%;
    bottom:0;
    background:#eee;
    font-size: 12px;
}

#footer a {
display:inline;
}
			
</style>
<div id="footer" style="display:none">
	<span style="float:left"> &copy;&nbsp;<?php echo date('Y'); ?> The Larvol Group, LLC | </span><span style="padding: 0 0 0 5px; float:left;"><a href="http://www.larvol.com" target="_blank">About Us</a> | </span>
    <span style="padding: 0 0 0 5px; float:left;"><a href="tos.php" target="_blank">Terms of Use</a></span>
</div> 
<script type="text/javascript">
//The TZ footer should always appear at the end of all content instead of always staying on screen while scrolling.
//However, if there is not enough content to fill the page, then the footer should snap to the bottom of the window like it does now instead of appearing in the middle of the window directly after the content.
$(document).ready(function(){
	if($(window).height() == $(document).height()){
		$("#footer").css('display', 'block');
	}
});
$(window).scroll(function(){  
    if($(window).scrollTop() + $(window).height() == $(document).height()) {   
          $("#footer").css('display', 'block'); 
    }else{
    	$("#footer").css('display', 'none');
	} 
});  
</script>
<?php
echo ga("SIGMA");
?>