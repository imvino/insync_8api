<?php

/**
 * REST implentation to get user permissions
 */
if(isset($_REQUEST["u"]) && isset($_REQUEST["p"]) && isset($_REQUEST["action"]) && isset($_REQUEST["permission"]))
{
	if($_REQUEST["action"] == "checkpermission")
	{
		$permission = $_REQUEST["permission"];
		
		$result = authSystem::ValidateUser();

		foreach($result as $key=>$value)
		{
			if($key == $permission && $value == 1)
				die("true");
		}
		
		die("false");
	}
	
	exit;
}

/**
 * Authentication class for InSync WebUI
 */
class authSystem
{
	/**
	 * Called by every page to validate the users session, or redirect to the login form if not logged in
	 * @return boolean True if user is validated, will exit and not complete execution otherse
	 */
	public static function ValidateUser()
	{
		// User already authenticated with Apache
		if (isset($_SERVER["PHP_AUTH_USER"]))
		{
			return authSystem::GetUserPermissions($_SERVER["PHP_AUTH_USER"]);
		}

		// user wants to login from this page
		if(isset($_REQUEST['u']) && isset($_REQUEST['p']))
		{
			$session = true;
			
			if(isset($_REQUEST['session']))
				$session = $_REQUEST['session'];

			if($session == "true")
				$session = true;
			else
				$session = false;
			
			return authSystem::ValidateLogin(base64_decode($_REQUEST['u']), base64_decode($_REQUEST['p']), $session);
		}

		session_start();
		
		// user has already logged in
		if(isset($_SESSION["validLogin"]) && $_SESSION["validLogin"] == 1)
		{			
			if(isset($_SESSION["IP"]))
			{
				 if($_SESSION["IP"] != $_SERVER['REMOTE_ADDR'])
					 authSystem::TerminateSession();
			}
			else
				authSystem::TerminateSession();
			
			if(isset($_SESSION["last_activity"]))
			{
                $timeout = authSystem::getUserTimeout($_SESSION["username"]);
                
				if(time()-$_SESSION["last_activity"] < $timeout*60)
				{
					$permissionArray = authSystem::GetUserPermissions($_SESSION["username"]);
                    
                    if(!isset($permissionArray["enabled"]))
                        authSystem::TerminateSession();
                    
                    // this is good. user is validated.
					$_SESSION["last_activity"] = time();
					session_write_close();
					return $permissionArray;
				}
				else
					authSystem::TerminateSession();
			}
		}
		
		session_write_close();
		
		authSystem::TerminateSession();
	}
    
    public static function getUserTimeout($username)
    {
        $db = authSystem::prepareDB();
		
		if(!$db)
			return 30;
			
		$result = pg_query_params("SELECT s.value FROM users u INNER JOIN user_settings s ON (u.id = s.user_id) WHERE u.name=$1", [$username]);
        $resultRow = pg_fetch_assoc($result);
        
        // no key found, default to 30
        if($resultRow == FALSE)
        {
            pg_close($db);
            return 30;
        }

        // no key found, default to 30
        if(!isset($resultRow["value"]))
        {
            pg_close($db);
            return 30;
        }

        pg_close($db);
        
        if($resultRow["value"] == 0)
            return PHP_INT_MAX;
        else
            return $resultRow["value"];
    }
	
	/**
	 * Clears all session data for a user and redirects them to a login page
	 */
	public static function TerminateSession()
	{
		authSystem::InvalidateUser();
		header('HTTP/1.1 401 Unauthorized');
		header("Location: /auth/login.php?target=" . rawurlencode($_SERVER['REQUEST_URI']));
		exit;
	}
	
	/**
	 * Clears all session data for a user
	 */
	public static function InvalidateUser()
	{
		@session_start();
		
		//destroy all session variables
		$_SESSION = [];
		session_destroy();
		@session_write_close();
	}
	
	/**
	 * Checks to see if a supplied username/login is valid.
	 * @param type $username Username
	 * @param type $password Password
	 * @return boolean True if valid login, false otherwise
	 */
	public static function ValidateLogin($username, $password, $session)
	{
		$db = authSystem::prepareDB();
		
		if(!$db)
			return false;
		
		$result = pg_query_params($db, "SELECT u.pass, u.salt FROM users u WHERE u.name=$1 AND EXISTS (SELECT name FROM permissions p WHERE p.user_id=u.id AND name='enabled')", [$username]);
        $resultRow = pg_fetch_assoc($result);

        // username not found or user not enabled, return false
        if($resultRow == FALSE)
        {
                // store bad login attempt in invalid_logins
                authSystem::logInvalid($db, $username);

                pg_close($db);
                return false;
        }

        $hash = hash("sha256", $resultRow["salt"] . hash("sha256", $password));

        // passwords don't match
        if($hash != $resultRow["pass"])
        {
                // store bad login attempt in invalid_logins
                authSystem::logInvalid($db, $username);

                pg_close($db);
                return false;
        }

        // if we made it this far, user is validated!
        pg_close($db);

        if($session)
        {
                session_start();
                session_regenerate_id();

                $_SESSION["validLogin"] = 1;
                $_SESSION["last_activity"] = time();
                $_SESSION["username"] = $username;
                $_SESSION["permissions"] = authSystem::GetUserPermissions($username);
                $_SESSION["IP"] = $_SERVER['REMOTE_ADDR'];

                session_write_close();

                return $_SESSION["permissions"];
        }
        else
                return authSystem::GetUserPermissions($username);
	}
	
	/**
	 * Stores invalid login attempt in invalid_logins table, and culls out 
	 * oldest if table is over 30 entries
	 * @param type $username Username used in invalid login attempt
	 */
	public static function logInvalid($db, $username)
    {
        $usernameToLog = substr($username, 0, 40);
        if($usernameToLog == "")
        {
            //Do not log invalid access with no username
            return;
        }
        // cull db if over 30 entries
        pg_query($db, "DELETE FROM invalid_logins WHERE ctid not in (select ctid from invalid_logins order by timestamp desc limit 30)");

        // insert invalid log
        $ip = $_SERVER['REMOTE_ADDR'];
        pg_query_params($db,'INSERT INTO "invalid_logins" ("timestamp","user","ip") VALUES (now() at time zone \'UTC\', $1, $2)', [$usernameToLog, $ip]);
    }
	
	/**
	 * Retrieves a users authentication level
	 * @param type $username Username
	 * @return mixed int representing user level, or false if error or no level
	 */
	public static function GetUserPermissions($username)
	{
		$db = authSystem::prepareDB();
		
		if(!$db)
			return false;
			
		$result = pg_query_params("SELECT p.name FROM users u INNER JOIN permissions p ON u.id = p.user_id WHERE u.name=$1", [$username]);
        // username not found or 0 permissions, return false
        if($result == FALSE)
        {
                pg_close($db);
                return false;
        }

        $permissionsArray = [];

        while ($resultRow = pg_fetch_assoc($result))
                $permissionsArray[$resultRow['name']] = true;

        pg_close($db);

        if(session_status() == PHP_SESSION_ACTIVE)
            $permissionsArray["sessionID"] = session_id();

        $permissionsArray["username"] = $username;

        return $permissionsArray;
	}
	
	/**
	 * Prepares a DB connection for use
	 * @return boolean|\mysqli A database object or false
	 */
	public static function prepareDB()
	{
		$db = pg_connect('host=127.0.0.1 dbname=insync user=web password=qey8xUf9 connect_timeout=30')
                        or false;
		
		return $db;
	}
}
?>
