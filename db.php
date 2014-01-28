<?php
require_once('settings.php');
require_once('include.util.php');
ini_set('error_reporting', E_ALL ^ E_NOTICE);
$db = new DatabaseManager();
$db->loggedIn();	//check login status and load userinfo
$now = strtotime('now');

//initiate logging actions
require_once dirname(__FILE__).'/log4php/Logger.php';
Logger::configure(dirname(__FILE__).'/setup/log.properties');
$logger = Logger::getLogger('tlg');

/* Abstracts mundane database access and manages login. 
	You shouldn't instantiate this class on your own -- an instance is made when it is included.
*/
class DatabaseManager
{
	public $db_link = 0;
	public $set = array(); // array that holds settings from the "settings" table in the DB
	public $user = NULL; //NULL = login status unknown. false = Not logged in. [User]object = logged in
	
	// On making an instance, connect to the database.
	public function __construct()
	{		
		if(SIGMA == 1 && defined('DB_USER_SIGMA'))
		{
			$this->db_link = mysql_connect(DB_SERVER, DB_USER_SIGMA, DB_PASS_SIGMA) or die("Error connecting to database server!");
			mysql_select_db(DB_NAME) or die(mysql_error());
		}
		else
		{
			$this->db_link = mysql_connect(DB_SERVER, DB_USER, DB_PASS) or die("Error connecting to database server!");
			mysql_select_db(DB_NAME) or die(mysql_error());
		}
		mysql_query('SET SESSION group_concat_max_len = 1000000') or die("Couldn't set group_concat_max_len");
		oldurlPath();	//update cache if necessary
		$this->reloadSettings();
	}
	
	/* Check if the current user is logged in, and to what account.
		This function is the basis of the site's security.
		RETURNS a boolean for if the user is logged in.
	*/
	public function loggedIn()
	{
		// If we already checked all this, just return the result.
		if($this->user !== NULL)
		{
			if($this->user === false) return false;
			return true;
		}

		/* A logged in user will have a cookie with their userid in it.
			If they don't have one or if it isn't a number, stop here.
		*/
		if(!isset($_COOKIE['qw_login']) || !is_numeric($_COOKIE['qw_login']))
		{
			$this->user = false;
			return false;
		}

		/* Check the database to see if the user's fingerprint matches the one we saved
			from their last successful login. If not, then they are not who they say
			they are. Either they moved the cookie (portable PC or portable browser)
			or they are an evil hacker. We don't discriminate yet -- just deny the login.
		*/
		$id = (int)$_COOKIE['qw_login'];
		$query = 'SELECT username,userlevel,email,realname,country,linkedin_id,linkedin_url FROM users WHERE id=' . $id
					. ' AND fingerprint="' . genPrint() . '" LIMIT 1';
		$res = mysql_query($query);
		if($res === false) return $this->user = false; //If the SQL query is bad here, just deny login instead of dying
		$res = mysql_fetch_array($res);
		if($res === false) return $this->user = false;
		
		// If they make it through the gauntlet, they are logged in.
		$this->user = new User();
		$this->user->id = $id;
		$this->user->username = $res['username'];
		$this->user->email = $res['email'];
		$this->user->userlevel = $res['userlevel'];
		$this->user->realname = $res['realname'];
		$this->user->country = $res['country'];
		$this->user->linkedin_id = $res['linkedin_id'];
		$this->user->linkedin_url = $res['linkedin_url'];
		
		$query = 'SELECT `name`,`level`,`user` FROM user_permissions AS ap LEFT JOIN '
			. '(SELECT `user`,permission FROM user_grants WHERE `user`=' . $id . ') AS ug ON ug.permission=ap.id '
			. 'ORDER BY `name`,`level`';
		$res = mysql_query($query) or die("Couldn't load permissions");
		while($row = mysql_fetch_assoc($res))
		{
			if(!isset($this->user->per[$row['name']])) $this->user->per[$row['name']] = 0;
			if( ($row['user'] !== NULL || $this->user->userlevel == 'root') && ((int)$row['level']) > $this->user->per[$row['name']])
				$this->user->per[$row['name']] = (int)$row['level'];
		}

		return true;
	}

	/* Attempts to login the user with the supplied credentials. Doesn't assume correct info.
		Call this before sending any of the page because we need to send a cookie here (on success)
		RETURNS a boolean representing the resulting login status.
	*/
	public function login($username, $password)
	{
		// Make sure we have something to work with
		if(!strlen($username) || !strlen($password)) return $this->user = false;
		
		// Check who the user is and if the password is right
		$username = mysql_real_escape_string($username);
		$password = hash(HASH_ALGO, $password . $username);
		$query = 'SELECT id,username,userlevel,email,realname,country,linkedin_id,linkedin_url FROM users WHERE username="' . $username
					. '" AND password="' . $password . '" LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query on login attempt');
		$res = mysql_fetch_array($res);
		if($res === false) return $this->user = false;

		// Credentials OK. Approve the login.
		$this->user = new User();
		$this->user->id = $res['id'];
		$this->user->username = $res['username'];
		$this->user->email = $res['email'];
		$this->user->userlevel = $res['userlevel'];
		$this->user->realname = $res['realname'];
		$this->user->country = $res['country'];
		$this->user->linkedin_id = $res['linkedin_id'];
		$this->user->linkedin_url = $res['linkedin_url'];
		
		$query = 'UPDATE users SET fingerprint="' . genPrint() . '" WHERE id=' . $this->user->id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query on login approval');
		setcookie('qw_login', $this->user->id, time()+60*60*24*365, '/');
		return true;
	}
	
	public function linkedInLogin($linkedin_id)
	{
		// Check who the user is and if the password is right
		$linkedin_id = mysql_real_escape_string($linkedin_id);
		$query = 'SELECT id,username,userlevel,email,realname,country,linkedin_id,linkedin_url FROM users WHERE linkedin_id="' . $linkedin_id
			. '" LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query on login attempt'.$query);
		$res = mysql_fetch_array($res);
		if($res === false) return $this->user = false;

		// Credentials OK. Approve the login.
		$this->user = new User();
		$this->user->id = $res['id'];
		$this->user->username = $res['username'];
		$this->user->email = $res['email'];
		$this->user->userlevel = $res['userlevel'];
		$this->user->realname = $res['realname'];
		$this->user->country = $res['country'];
		$this->user->linkedin_id = $res['linkedin_id'];
		$this->user->linkedin_url = $res['linkedin_url'];
		
		$query = 'UPDATE users SET fingerprint="' . genPrint() . '" WHERE id=' . $this->user->id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query on login approval');
		
		setcookie('qw_login', $this->user->id, time()+60*60*24*365, '/');		

		return true;
	}
	
	// Logs out the current user. Does nothing if they're not logged in.
	public function logout()
	{
		if(!$this->loggedIn()) return;
		$query = 'UPDATE users SET fingerprint=NULL WHERE id=' . $this->user->id . ' LIMIT 1';
		mysql_query($query);
		setcookie('qw_login', '', time()-60*60*24, '/');
		setcookie('tree_grid_cookie', '', time()-60*60*24, '/');
		$this->user = false;
	}
	
	/* Attempts to create a new account. Does its own validation.
		RETURNS true on full success, a password string on success without email, or an array of error messages on failure.
			(Array indices = parameter names.)
	*/
	public function register($username, $email, $userlevel)
	{
		$errors = array();
		if(strlen($username) < 3 || strlen($username) > 31)
		{
			$errors['username'] = 'Error: username must be within 3 to 31 characters long';
		}else if(!ctype_print($username)){
			$errors['username'] = 'Error: username must only contain printable characters';
		}
		if(!strlen($email))
		{
			$errors['email'] = 'Error: email is required';
		}else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			$errors['email'] = 'Error: invalid email';
		}
		if(!in_array($userlevel,getEnumValues('users','userlevel')))
		{
			$errors['userlevel'] = 'Error: invalid userlevel';
		}

		if(count($errors)) return $errors;
		
		$username = mysql_real_escape_string(stripslashes($username));
		$email = mysql_real_escape_string(stripslashes($email));
		$userlevel = mysql_real_escape_string(stripslashes($userlevel));
		
		mysql_query('BEGIN') or die("Couldn't begin transaction in account creation");
		$query = 'SELECT id FROM users WHERE username="' . $username . '" LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query checking for existing username');
		$res = mysql_fetch_array($res);
		if($res !== false)
		{
			$errors['username'] = 'Username already taken; choose another';
		}
		$query = 'SELECT id FROM users WHERE email="' . $email . '" LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query checking for existing email');
		$res = mysql_fetch_array($res);
		if($res !== false)
		{
			$errors['email'] = 'Account email address already in use; choose another';
		}
		
		if(count($errors)) return $errors;
		
		//At this point validation is done and we can create the account
		$password = generateCode();	//make a random password
		$query = 'INSERT INTO users SET username="' . $username . '",password="' . hash(HASH_ALGO, $password . $username)
					. '",email="' . $email . '",userlevel="' . $userlevel . '"';
		$res = mysql_query($query) or die('Bad SQL Query creating new account');
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction on account creation");
		$mailmsg = "Welcome to " . SITE_NAME . "\r\nYour login information is as follows:\r\nUsername: " . $username
					. "\r\nPassword: " . $password . "\r\nYou may change your password after logging in.\r\n\r\n";
		$headers = 'From: ' . SITE_NAME . ' <no-reply@' . $_SERVER['SERVER_NAME'] . '>' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
		$res = mail($email, SITE_NAME.' Registration Information', $mailmsg, $headers);
		if($res) return true;
		return $password;
	}
	
	/* Attempts to change the current user's password. Built-in input validation.
		RETURNS true on success, an array of errors on failure. Indices = param names.
	*/
	public function changePassword($oldp, $newp, $newpcon)
	{
		$errors = array();
		if(!strlen($oldp))		$errors['oldp'] = 'Error: old password required';
		if(strlen($newp) < 5)	$errors['newp'] = 'Error: new password must be at least 5 characters';
		if($newp !== $newpcon)	$errors['newpcon'] = 'Error: new password and confirmation do not match';
		
		$hash = hash(HASH_ALGO, $oldp.$this->user->username);
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction to change password");
		$query = 'SELECT id FROM users WHERE id=' . $this->user->id . ' AND password="' . $hash . '" LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query checking old password');
		if(mysql_fetch_array($res) === false && !isset($errors['oldp'])) $errors['oldp'] = 'Error: old password is incorrect';
		if(count($errors)) return $errors;
		
		$query = 'UPDATE users SET password="' . hash(HASH_ALGO, $newp.$this->user->username)
					. '" WHERE id=' . $this->user->id . ' LIMIT 1';
		mysql_query($query) or die('Bad SQL Query updating password');
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction to update password");
		return true;
	}
	
	/* Attempts to update the current user's personal info. Built-in input validation.
		RETURNS true on success, an array of errors on failure. Indices = param names.
	*/
	public function setPersonalInfo($email)
	{
		$errors = array();
		if(!strlen($email))
		{
			$errors['email'] = 'Error: email is required';
		}else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			$errors['email'] = 'Error: invalid email';
		}

		if(count($errors)) return $errors;
		
		$email = mysql_real_escape_string(stripslashes($email));
		
		$query = 'UPDATE users SET email="' . $email . '" WHERE id=' . $this->user->id . ' LIMIT 1';
		mysql_query($query) or die('Bad SQL Query in updating personal info');
		return true;
	}
	
	/* Attempts to perform a password reset based on a user-input username and email.
		Input validation is built-in. Username and email must correspond or the operation fails.
		If the email cannot be sent, the operation fails -- we can't just echo the password for security reasons.
		RETURNS:
			true when the password is reset and mailed to user
			an error string on failure
	*/
	public function resetPassword($username,$email)
	{
		$unescaped_email = $email;
		$unescaped_username = $username;
		if(!strlen($username = mysql_real_escape_string($username)) || !strlen($email = mysql_real_escape_string($email)))
			return false;
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction for attempted password reset");
		$query = 'SELECT id FROM users WHERE username="' . $username . '" AND email="' . $email . '" LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query looking up user');
		$res = mysql_fetch_array($res);
		if($res === false) return 'Bad username/email combination.';		
		//Now we know the combination is valid, so proceed with the reset
		$password = generateCode();
		//send email
		$headers = 'From: ' . SITE_NAME . ' <no-reply@' . $_SERVER['SERVER_NAME'] . '>' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
		$mailmsg = 'Your password on ' . SITE_NAME . " has been reset. Here are your new credentials:\r\n"
					. 'Username: ' . $unescaped_username . "\r\nPassword: " . $password . "\r\n";		
		if(!MAIL_ENABLED || $this->loggedIn()) //Used when Admin Resets Passwords && Mail Not Enabled
		{	
			global $now;
			$filename='PW_Reset_'.date('Y-m-d_H.i.s',$now);
			$MyText  = 'To:'. $unescaped_email ."\r\n";
			$MyText .= 'Subject:'.SITE_NAME . ' Password Reset ' .''. "\r\n\r\n";
			$MyText .=  $mailmsg."\r\n\r\n";
			
			if(!is_dir('logs/email_files')) mkdir("logs/email_files") or die("could not create directory to write.");
			$myFile = 'logs/email_files/'.$filename.'.txt';
			$fh = fopen($myFile, 'w') or die("can't open file");
			fwrite($fh, $MyText);
			fclose($fh);
		}
		else
		{	//For Forgot Password - Reset		
			$mailres = mail($unescaped_email, SITE_NAME.' Password Reset', $mailmsg, $headers);
			if($mailres === false) return 'Email could not be sent sue to server error, so password was not reset.';
		}
		//email was succesful at this point, so make the change.		
		$query = 'UPDATE users SET password="' . hash(HASH_ALGO, $password.$username) . '" WHERE id=' . $res['id']
					. ' LIMIT 1';
		mysql_query($query) or die('Bad SQL query resetting password');
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction for attempted password reset");
		return true;
	}
	
	public function reloadSettings()
	{
		$defaults = array('results_per_page' => '200');
		$good = true;
		mysql_query('BEGIN') or die("Couldn't start SQL transaction");
		$res = mysql_query('SELECT name,value FROM settings') or die('Bad SQL query getting settings');
		while($row = mysql_fetch_assoc($res))
		{
			$this->set[$row['name']] = $row['value'];
		}
		foreach($defaults as $name => $value)
		{
			if(!isset($this->set[$name]))
			{
				$name = '"' . mysql_real_escape_string($name) . '"';
				$value = '"' . mysql_real_escape_string($value) . '"';
				mysql_query('INSERT INTO settings SET name=' . $name . ',value=' . $value) or die("Couldn't repair settings");
				$good = false;
			}
		}
		mysql_query('COMMIT') or die("Couldn't end SQL transaction");
		if($good === false) reloadSettings();
	}
}

class User
{
	public $id = NULL;
	public $username = NULL;
	public $userlevel = NULL;
	public $email = NULL;
	
	public $realname = NULL;
	public $country = NULL;
	public $linkedin_id = NULL;
	public $linkedin_url = NULL;
	
	public $per = array();
	
	public function getName()
	{
		if($this->realname === NULL || empty($this->realname))
		{
			return $this->username;
		}else{
			return $this->realname;
		}
	}
}

class Result
{
	public $num;
	public $color = NULL;
	public $link;
}

//pass an array of these to the search function
class SearchParam
{
	public $field;	// field name -- for non-global fields, the ID form is needed, ex. "_24"
	public $action;	// "search" (search), "ascending" (sort ascending), "descending" (sort descending), "require" (is not null)
	public $value;	// the value to search for
	public $negate = false;	// exclude value from a search rather than include
	public $strong = true;	//strength of Negation
}

?>