<?php
require_once('db.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
$errors = array();
if(isset($_POST['submit2']))
{
	$result = $db->changePassword($_POST['oldp'], $_POST['newp'], $_POST['newpcon']);
	if($result !== true) $errors = $result;
}
if(isset($_POST['submit']))
{
	$result = $db->setPersonalInfo($_POST['email'], $_POST['getupdates']);
	if($result !== true) $errors = $result;
}

require_once('header.php');
?>
<form id="form1" name="form1" method="post" action="profile.php" style="width:270px;">
  <fieldset><legend>Update Personal Information</legend>
<?php
if(isset($_POST['submit']) && !count($errors))
{
	echo('Personal info update successful!');
}else{
?>
    <label>Account email address:<br />
      <input name="email" type="text" value="<?php echo($db->user->email); ?>" />
      <div class="error"><?php echo($errors['email']); ?></div>
    </label><br />
    <input type="submit" name="submit" id="submit" value="Submit" />
<?php
}
?>
  </fieldset>
</form>
<form id="form2" name="form2" method="post" action="profile.php" style="width:195px;">
  <fieldset><legend>Change Password</legend>
<?php
if(isset($_POST['submit2']) && !count($errors))
{
	echo('Password change successful!');
}else{
?>
    <label>Old Password:<br />
      <input name="oldp" type="password" id="oldp" value="<?php echo($_POST['oldp']); ?>" />
      <div class="error"><?php echo($errors['oldp']); ?></div>
    </label><br />
    <label>New Password:<br />
      <input name="newp" type="password" id="newp" value="<?php echo($_POST['newp']); ?>" />
      <div class="error"><?php echo($errors['newp']); ?></div>
    </label><br />
    <label>New Password (confirm):<br />
      <input name="newpcon" type="password" id="newpcon" value="<?php echo($_POST['newpcon']); ?>" />
      <div class="error"><?php echo($errors['newpcon']); ?></div>
    </label><br />
    <input type="submit" name="submit2" id="submit2" value="Submit" />
<?php
}
?>
  </fieldset>
</form>

</body>
</html>