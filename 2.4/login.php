<?php
require_once('db.php');
$error='';
if(isset($_POST['username']) && strlen($_POST['username']) && isset($_POST['password']) && strlen($_POST['password']))
{
	if(!$db->login($_POST['username'],$_POST['password'])) $error = 'Incorrect username or password';
}

if($db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}

$reset_message='';
$reset_error='';
if($_GET && $_GET['page'] == 'reset' && $_POST && strlen($_POST['reset_username']) && strlen($_POST['reset_email']))
{
	$res = $db->resetPassword($_POST['reset_username'],$_POST['reset_email']);
	if($res === true)
	{
		$reset_message = 'Password reset successful. To login again, check your email for your password.';
	}else{
		$reset_error = $res;
	}
}

require_once('header.php');

if($_GET && $_GET['page'] == 'reset' || strlen($reset_error))
{
?>
<form name="password_reset_form" method="post" action="login.php" style="float:none;width:350px;margin:0 auto 0 auto;">
  <fieldset style="width:auto;margin:0;">
    <legend>Reset Password</legend>
    <span class="info">Enter the username and email address registered to your account. A newly generated password will be assigned to your account and emailed to you.</span><br /><br />
    <label>Username:
      <input type="text" name="reset_username" value="<?php if(isset($_POST['reset_username'])) { echo(htmlspecialchars($_POST['reset_username'])); } ?>" />
    </label>
    <br />
    <label>email:
      <input type="text" name="reset_email" value="<?php if(isset($_POST['reset_email'])) { echo(htmlspecialchars($_POST['reset_email'])); } ?>" />
    </label>
    <br />
    <div class="error"><?php echo($reset_error); ?></div>
    <input type="submit" name="submit" value="Reset" />
  </fieldset>
</form>
<?php
}else if(strlen($reset_message)){
echo($reset_message);
}else{
?>
<form action="login.php" method="post" name="form1" id="form1" style="float:none;"><fieldset style="width:175px;"><legend>Login</legend>
  <label>Username:<br />
    <input name="username" type="text" id="username" value="<?php if(isset($_POST['username'])) { echo($_POST['username']); } ?>" />
  </label><br />
  <label>Password:<br />
    <input name="password" type="password" id="password" value="<?php if(isset($_POST['password'])) { echo($_POST['password']); } ?>" />
  </label>
  <br />
  <div class="error"><?php echo($error); ?></div>
  <input type="submit" name="submit" id="submit" value="Submit" />
  </fieldset>
</form>
<br />
<a href="login.php?page=reset">Lost password? </a>
<?php } ?>
</body>
</html>