<?php
require_once('db.php');

function ga($name)
{
	$query = 'SELECT ga_id,status FROM ga_profiles WHERE profile_name="' . $name . '" LIMIT 1';
	$res = mysql_query($query); // or die('Bad SQL query getting GA Profile');
	if($res)
	{
		$result = mysql_fetch_array($res);
		if($result['status'] == 1) {	
?>
<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(["_setAccount", "<?php echo $result['ga_id']; ?>"]);
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
}
?>