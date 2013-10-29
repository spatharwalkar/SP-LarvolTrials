<?php
$cwd = getcwd();
chdir ("..");
require_once('db.php');
chdir ($cwd);

if(isset($_GET['logout']))
{
	$db->logout();
	header('Location: index.php');
	exit;
}

if(!$db->loggedIn())
{
	$config = '../hybridauth/config.php';
	require_once( "../hybridauth/Hybrid/Auth.php" );
	 
	try
	{
		$hybridauth = new Hybrid_Auth( $config );
		$linkedin = $hybridauth->authenticate( "LinkedIn" );
		$user_profile = $linkedin->getUserProfile();
		$exists = $db->linkedInLogin($user_profile->identifier);
		if(!$exists)
		{
			$query = 'INSERT INTO `users` SET `username`="' . mysql_real_escape_string($user_profile->displayName) . '",'
					. '`password`="a",`fingerprint`=NULL,'
					. '`email`="' . mysql_real_escape_string($user_profile->email) . '",'
					. '`userlevel`="public",'
					. '`realname`="' . mysql_real_escape_string($user_profile->firstName . ' ' . $user_profile->lastName) . '",'
					. '`country`="' . mysql_real_escape_string($user_profile->country) . '",'
					. '`linkedin_id`="' . mysql_real_escape_string($user_profile->identifier) . '",'
					. '`linkedin_url`="' . mysql_real_escape_string($user_profile->profileURL) . '"';
			$res = mysql_query($query);
			if($res)
			{
				$db->linkedInLogin($user_profile->identifier);
				echo("Larvol Sigma access granted.<br />");
			}else{
				echo("Sorry, there was a problem granting access based on LinkedIn authentication. Please try again at another time.<br />"
						. mysql_error() . '<br />');
			}
		}else{
			echo("Login successful.<br />");
		}
	}catch( Exception $e ){
		echo "Social auth error: " . $e->getMessage();
	}
	echo('Click <a href="' . $_SERVER['HTTP_REFERER'] . '">here</a> to return to where you were.');
}else{
	header('Location: profile.php');
}
?>