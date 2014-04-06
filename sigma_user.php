<?php

require_once('db.php');
class sigmaUser
{
	public $sigmaUser = NULL; //NULL = login status unknown. false = Not logged in. [User]object = logged in
	
	
	
	/* Check if the current sigma user is logged in, and to what account.
		This function is the basis of the site's security.
		RETURNS a boolean for if the user is logged in.
	*/
	public function sigmaLoggedIn()
	{
		// If we already checked all this, just return the result.
		
		if($this->sigmaUser !== NULL)
		{
			if($this->sigmaUser === false) return false;
			return true;
		}

		/* A logged in user will have a cookie with their userid in it.
			If they don't have one or if it isn't a number, stop here.
		*/
		
		if(!isset($_COOKIE['qw_login_sigma']) || !is_numeric($_COOKIE['qw_login_sigma']))
		{
			
			$this->sigmaUser = false;
			return false;
		}

		/* Check the database to see if the user's fingerprint matches the one we saved
			from their last successful login. If not, then they are not who they say
			they are. Either they moved the cookie (portable PC or portable browser)
			or they are an evil hacker. We don't discriminate yet -- just deny the login.
		*/
		$id = (int)$_COOKIE['qw_login_sigma'];
		$query = 'SELECT username,userlevel,email,realname,country,linkedin_id,linkedin_url FROM users WHERE id=' . $id
					. ' AND fingerprint="' . genPrint() . '" LIMIT 1';
		$res = mysql_query($query);
		if($res === false) return $this->sigmaUser = false; //If the SQL query is bad here, just deny login instead of dying
		$res = mysql_fetch_array($res);
		if($res === false) return $this->sigmaUser = false;
		
		// If they make it through the gauntlet, they are logged in.
		$this->sigmaUser = new User();
		$this->sigmaUser->id = $id;
		$this->sigmaUser->username = $res['username'];
		$this->sigmaUser->email = $res['email'];
		$this->sigmaUser->userlevel = $res['userlevel'];
		$this->sigmaUser->realname = $res['realname'];
		$this->sigmaUser->country = $res['country'];
		$this->sigmaUser->linkedin_id = $res['linkedin_id'];
		$this->sigmaUser->linkedin_url = $res['linkedin_url'];
		
		$query = 'SELECT `name`,`level`,`user` FROM user_permissions AS ap LEFT JOIN '
			. '(SELECT `user`,permission FROM user_grants WHERE `user`=' . $id . ') AS ug ON ug.permission=ap.id '
			. 'ORDER BY `name`,`level`';
		$res = mysql_query($query) or die("Couldn't load permissions");
		while($row = mysql_fetch_assoc($res))
		{
			if(!isset($this->sigmaUser->per[$row['name']])) $this->sigmaUser->per[$row['name']] = 0;
			if( ($row['user'] !== NULL || $this->sigmaUser->userlevel == 'root') && ((int)$row['level']) > $this->sigmaUser->per[$row['name']])
				$this->sigmaUser->per[$row['name']] = (int)$row['level'];
		}

		return true;
	}

	/* Attempts to login the user with the supplied credentials. Doesn't assume correct info.
		Call this before sending any of the page because we need to send a cookie here (on success)
		RETURNS a boolean representing the resulting login status.
	*/
	public function sigmaLogin($username, $password)
	{
		// Make sure we have something to work with
		if(!strlen($username) || !strlen($password)) return $this->sigmaUser = false;
		
		// Check who the user is and if the password is right
		$username = mysql_real_escape_string($username);
		$password = hash(HASH_ALGO, $password . $username);
		$query = 'SELECT id,username,userlevel,email,realname,country,linkedin_id,linkedin_url FROM users WHERE username="' . $username
					. '" AND password="' . $password . '" LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query on login attempt');
		$res = mysql_fetch_array($res);
		if($res === false) return $this->sigmaUser = false;

		// Credentials OK. Approve the login.
		$this->sigmaUser = new User();
		$this->sigmaUser->id = $res['id'];
		$this->sigmaUser->username = $res['username'];
		$this->sigmaUser->email = $res['email'];
		$this->sigmaUser->userlevel = $res['userlevel'];
		$this->sigmaUser->realname = $res['realname'];
		$this->sigmaUser->country = $res['country'];
		$this->sigmaUser->linkedin_id = $res['linkedin_id'];
		$this->sigmaUser->linkedin_url = $res['linkedin_url'];
		
		$query = 'UPDATE users SET fingerprint="' . genPrint() . '" WHERE id=' . $this->sigmaUser->id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query on login approval');
		setcookie('qw_login_sigma', $this->sigmaUser->id, time()+60*60*24*365, '/');
		return true;
	}
	
	public function sigmaLinkedInLogin($linkedin_id)
	{
		// Check who the user is and if the password is right
		$linkedin_id = mysql_real_escape_string($linkedin_id);
		$query = 'SELECT id,username,userlevel,email,realname,country,linkedin_id,linkedin_url FROM users WHERE linkedin_id="' . $linkedin_id
			. '" LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query on login attempt'.$query);
		$res = mysql_fetch_array($res);
		if($res === false) return $this->sigmaUser = false;

		// Credentials OK. Approve the login.
		$this->sigmaUser = new User();
		$this->sigmaUser->id = $res['id'];
		$this->sigmaUser->username = $res['username'];
		$this->sigmaUser->email = $res['email'];
		$this->sigmaUser->userlevel = $res['userlevel'];
		$this->sigmaUser->realname = $res['realname'];
		$this->sigmaUser->country = $res['country'];
		$this->sigmaUser->linkedin_id = $res['linkedin_id'];
		$this->sigmaUser->linkedin_url = $res['linkedin_url'];
		
		$query = 'UPDATE users SET fingerprint="' . genPrint() . '" WHERE id=' . $this->sigmaUser->id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query on login approval');
		setcookie('qw_login_sigma', $this->sigmaUser->id, time()+60*60*24*365, '/');		

		return true;
	}
	
	// Logs out the current user from sigma. Does nothing if they're not logged in.
	public function sigmaLogout()
	{
		if(!$this->sigmaLoggedIn()) return;
		$query = 'UPDATE users SET fingerprint=NULL WHERE id=' . $this->sigmaUser->id . ' LIMIT 1';
		mysql_query($query);
		setcookie('qw_login_sigma', '', time()-60*60*24, '/');
		setcookie('tree_grid_cookie', '', time()-60*60*24, '/');
		$this->sigmaUser = false;
	}
	
	
}


?>
