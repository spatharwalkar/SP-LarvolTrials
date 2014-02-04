<?php
require_once('db.php');
if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require('header.php');
$error = array();
$db_name = "ga_profiles";

if(isset($_POST['ga_edit']) && is_array($_POST['ga_edit']))
{
	foreach($_POST['ga_edit'] as $key => $val)
	{
		$profile = $ga_id = $status = "";
		$id = mysql_real_escape_string($key);
		
		if(isset($_POST['profile'][$id]) && trim($_POST['profile'][$id]) != ""){
			  $profile = mysql_real_escape_string(trim($_POST['profile'][$id]));
		}else{
			$error['profile'] = true;
		}
		if(isset($_POST['ga_id'][$id]) && trim($_POST['ga_id'][$id]) != ""){
			 $ga_id = mysql_real_escape_string(trim($_POST['ga_id'][$id]));
		}else{
			$error['ga_id'] = true;
		}
		if(isset($_POST['status'][$id]) && $_POST['status'][$id] == "on"){
			$subquery = ', status="1"';
		}else{
			$subquery = ', status="0"';
		}

		if(is_numeric($id) && isset($_POST['delete'][$id]) && $_POST['delete'][$id] == "on"){
			$query = 'DELETE FROM '.$db_name.' WHERE id='.$id;
			mysql_query($query) or die('Bad SQL query for profile delete');
		}elseif(is_numeric($id) && $profile != "" && $ga_id != ""){
			$query = 'UPDATE '.$db_name.' SET profile_name="'.$profile.'", ga_id="'.$ga_id.'"'.$subquery.' WHERE id='.$id;
			mysql_query($query) or die('Bad SQL query for saving edits');
		}
	}
}

if(isset($_POST['ga_save']))
{
	$profile = $ga_id = $status = "";
	
	if(isset($_POST['profile']) && trim($_POST['profile']) != ""){
		  $profile = mysql_real_escape_string(trim($_POST['profile']));
	}else{
		$error['newprofile'] = true;
	}
	if(isset($_POST['ga_id']) && trim($_POST['ga_id']) != ""){
		 $ga_id = mysql_real_escape_string(trim($_POST['ga_id']));
	}else{
		$error['newga_id'] = true;
	}
	if(isset($_POST['status']) && $_POST['status'] == "on"){
		  $subquery = ", status='1'";
	}
	
	if($profile != "" && $ga_id != "")
	{
		$query = 'INSERT INTO '.$db_name.' SET profile_name="'.$profile.'", ga_id="'.$ga_id.'"'.$subquery;
		mysql_query($query) or die('Bad SQL query for new saving');
	}
}
	
$query = 'SELECT id,profile_name,ga_id,status FROM '.$db_name.' ORDER BY id ASC';
$res = mysql_query($query) or die('Bad SQL query getting GA profile list');
?>
<style>
.errors{
border-color:red;
}
.error{
width:auto !important;
margin:0px;
}
</style>
<script>
function verify_delete(profile_id)
{
	if(document.getElementById("delete["+profile_id+"]").checked)
	{
		return confirm("Are you sure to delete profile "+document.getElementById("profile["+profile_id+"]").value);
	}
}
</script>
<form action="admin_ga.php" method="post" name="gaform">
	<fieldset>
		<legend>Google Analytics Profile Management</legend>
		<p class="error">
<?php
		if($error['profile'] == true && $error['ga_id'] == true)
		{
			echo "Profile and GA Id are required";
		}elseif($error['profile'] == true){
			echo "Profile is required";
		}elseif($error['ga_id'] == true){
			echo "GA Id is required";
		}
		echo "</p>";
		if(mysql_num_rows($res) > 0)
		{
?>
			<table>
				<tr>
					<th>id</th><th>Profile</th><th>GA Id</th><th>Status</th><th>Delete</th><th>Save</th>
				</tr>
<?php
				while($row = mysql_fetch_assoc($res))
				{
					echo '<tr>'
							.'<td>'.$row['id'].'</td><td><input type="text" name="profile['.$row['id'].']" id="profile['.$row['id'].']" value="'.htmlspecialchars($row['profile_name']).'" '.(($id == $row['id'] && $error["profile"] == true)? "class='errors'":"").' /></td><td><input type="text" name="ga_id['.$row['id'].']" value="'.htmlspecialchars($row['ga_id']).'" '.(($id == $row['id'] && $error["ga_id"] == true)? "class='errors'":"").' /></td><td><input type="checkbox" name="status['.$row['id'].']" '.((htmlspecialchars($row['status']) == 1)?"CHECKED":"").' /></td><td><input type="checkbox" name="delete['.$row['id'].']" id="delete['.$row['id'].']" /></td><td><input type="submit" name="ga_edit['.$row['id'].']" value="Save" onclick="return verify_delete('.$row['id'].')" /></td>'
						.'</tr>';			
				}
			echo '</table>';
		}else{
			echo '<p style="text-align:center;">No GA profile found</p>';
		}
?>
	</fieldset>
</form>

<form style="float:right;" name="newprofile" method="post" action="admin_ga.php">
	<fieldset>
		<legend>Create New Profile</legend>
		<p class="error">
		<?php
		if($error['newprofile'] == true && $error['newga_id'] == true)
		{
			echo "Profile and GA Id are required";
		}elseif($error['newprofile'] == true){
			echo "Profile is required";
		}elseif($error['newga_id'] == true){
			echo "GA Id is required";
		}
		?>
		</p>
		<label>Profile:<br>
			<input type="text" value="" name="profile" <?php echo ($error["newprofile"] == true)? "class='errors'":""; ?> />
		</label>
		<br clear="all">
		<label>GA Id:<br>
			<input type="text" value="" name="ga_id" <?php echo ($error["newga_id"] == true)? "class='errors'":""; ?> />
		</label>
		<br clear="all">
		<label>Status:<br />
			<input type="checkbox" name="status" />
		</label>
		<br clear="all">
		<input type="submit" value="Submit" name="ga_save">
	</fieldset>
</form>

</body>
</html>