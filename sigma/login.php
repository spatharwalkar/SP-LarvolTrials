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
	
	$reffererUrl = $_SERVER['HTTP_REFERER'];
	
	try
	{
		$hybridauth = new Hybrid_Auth( $config );
		
		if(!$hybridauth->storage()->get("reffererUrl") || $hybridauth->storage()->get("reffererUrl") == '') {
			$hybridauth->storage()->set("reffererUrl", $reffererUrl);
		}		
		$linkedin = $hybridauth->authenticate( "LinkedIn" );
		
		if(!$linkedin->isUserConnected())
		{
			echo("LinkedIn authorization failed.<br />");
		}else{
		
			$user_profile = $linkedin->getUserProfile();
			$linkedinlogin = $db->linkedInLogin($user_profile->email);
			if(!$linkedinlogin)
			{
				$query = 'SELECT id FROM `users` WHERE`linkedin_id`="' . mysql_real_escape_string($user_profile->email) . '"';
				$res = mysql_query($query);
				if($res === false)
				{
					echo('There was a problem checking for the existence of your profile. Please try again at another time.<br />');
				}else{
					$res = mysql_fetch_assoc($res);
					if($res !== false)
					{
						echo('There was a problem in the login system. Please try again at another time.<br />');
					}else{
						$query = 'INSERT INTO `users` SET `username`="' . mysql_real_escape_string($user_profile->displayName) . '",'
								. '`password`="a",`fingerprint`=NULL,'
								. '`email`="' . mysql_real_escape_string($user_profile->email) . '",'
								. '`userlevel`="public",'
								. '`realname`="' . mysql_real_escape_string($user_profile->firstName . ' ' . $user_profile->lastName) . '",'
								. '`country`="' . mysql_real_escape_string($user_profile->country) . '",'
								. '`linkedin_id`="' . mysql_real_escape_string($user_profile->email) . '",'
								. '`linkedin_url`="' . mysql_real_escape_string($user_profile->profileURL) . '"';
						$res = mysql_query($query);
						if($res)
						{
							$db->linkedInLogin($user_profile->email);
							echo("Larvol Sigma access granted.<br />");
						}else{
							echo("Sorry, there was a problem granting access based on LinkedIn authentication. Please try again at another time.<br />"
									. mysql_error() . '<br />');
						}
					}
				}
			} else {
				if(!$hybridauth->storage()->get("reffererUrl") || $hybridauth->storage()->get("reffererUrl") == '')
					header('Location: profile.php');
				else {
					$redirectUrl  = $hybridauth->storage()->get("reffererUrl");
					$hybridauth->storage()->set("reffererUrl", '');
					header('Location:'.$redirectUrl);
				}
			}
		}
	}catch( Exception $e ){
		echo "Social auth error: " . $e->getMessage();
	}
}else{
	header('Location: profile.php');
}
?>