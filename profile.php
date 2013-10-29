<?php
if(isset($_GET['user']))
{
	header('Location: sigma/profile.php?' . $_SERVER['QUERY_STRING']);
	exit;
}

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
	$getupdatesPost  = (isset($_POST['getupdates']))?$_POST['getupdates']:null;
	$result = $db->setPersonalInfo($_POST['email'], $getupdatesPost);
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
      <?php $errorsEmail = (isset($errors['email']))?$errors['email']:'';?>
      <div class="error"><?php echo $errorsEmail; ?></div>
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
    	<?php $oldpPost = (isset($_POST['oldp']))?$_POST['oldp']:'';?>
      <input name="oldp" type="password" id="oldp" value="<?php echo $oldpPost; ?>" />
      <?php $oldpErrors = (isset($errors['oldp']))?$errors['oldp']:'';?>
      <div class="error"><?php echo $oldpErrors; ?></div>
    </label><br />
    <label>New Password:<br />
    <?php $newpPost = (isset($_POST['newp']))?$_POST['newp']:'';?>
      <input name="newp" type="password" id="newp" value="<?php echo $newpPost; ?>" />
       <?php $newpErrors = (isset($errors['newp']))?$errors['newp']:'';?>
      <div class="error"><?php echo $newpErrors; ?></div>
    </label><br />
    <label>New Password (confirm):<br />
    <?php $newpconPost = (isset($_POST['newpcon']))?$_POST['newpcon']:'';?>
    <?php $newpconErrors = (isset($errors['newpcon']))?$errors['newpcon']:'';?>
      <input name="newpcon" type="password" id="newpcon" value="<?php echo $newpconPost; ?>" />
      <div class="error"><?php echo $newpconErrors; ?></div>
    </label><br />
    <input type="submit" name="submit2" id="submit2" value="Submit" />
<?php
}
?>
  </fieldset>
</form>

</body>
</html>