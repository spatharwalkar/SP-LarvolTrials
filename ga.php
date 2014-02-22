<?php
//function takes GA profile as argument and outputs the GA code

function ga($name)
{
	if (defined('GA_PROFILE_'.$name) && defined('GA_PROFILE_'.$name.'_STATUS') && constant('GA_PROFILE_'.$name.'_STATUS') == true)
	{
?>
<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(["_setAccount", "<?php echo constant('GA_PROFILE_'.$name); ?>"]);
	_gaq.push(["_trackPageview"]);
	
	(function() {
		var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true;
		ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";
		var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);
	})();
</script>
<?php
	}
}
?>