<?php
// this must be included on all pages to authenticate the user
require_once(__DIR__ . "/constants.php");
require_once(SITE_DOCUMENT_ROOT . "/auth/authSystem.php");
require_once(SITE_DOCUMENT_ROOT . "/helpers/uuid.php");
require_once(SITE_DOCUMENT_ROOT . "/helpers/webdb.php");
require_once(SITE_DOCUMENT_ROOT . "/helpers/networkHelper.php" );

$permissions = authSystem::ValidateUser();
// end

if (empty($permissions["adminUsers"]))
    die("Error: You do not have permission to access this page.");

function request_to_array() {
    $permissions = [];

    if(isset($_REQUEST["enabled"]) && $_REQUEST["enabled"] == 'true')
        $permissions[] = "enabled";
    
    if(isset($_REQUEST["reports"]) && $_REQUEST["reports"] == 'true')
        $permissions[] = "reports";
    
    if(isset($_REQUEST["cameras"]) && $_REQUEST["cameras"] == 'true')
        $permissions[] = "cameras";
    
    if(isset($_REQUEST["cameracontrols"]) && $_REQUEST["cameracontrols"] == 'true')
        $permissions[] = "cameracontrols";
    
    if(isset($_REQUEST["maintenance"]) && $_REQUEST["maintenance"] == 'true')
        $permissions[] = "maintenance";
    
    if(isset($_REQUEST["corridor"]) && $_REQUEST["corridor"] == 'true')
        $permissions[] = "corridor";
    
    if(isset($_REQUEST["configure"]) && $_REQUEST["configure"] == 'true')
        $permissions[] = "configure";
    
    if(isset($_REQUEST["manual"]) && $_REQUEST["manual"] == 'true')
        $permissions[] = "manual";
    
    if(isset($_REQUEST["admin"]) && $_REQUEST["admin"] == 'true')
        $permissions[] = "adminUsers";
    
    if(isset($_REQUEST["web"]) && $_REQUEST["web"] == 'true')
        $permissions[] = "web";
    
    if(isset($_REQUEST["api"]) && $_REQUEST["api"] == 'true')
        $permissions[] = "api";

    return $permissions;
}
if ( !isset($permissions["adminUsers"]))
	die("Error: You do not have permission to access this page.");

$action = "";
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];

switch($action)
{
	/**
	 * Retrieves markup for user table
	 */
	case "getUserTable":
	{
		$db = openWebDB();
		
		if(!$db)
			die("<tr><td>Error: Could not open database.</td></tr>");
		
		$count = 0;
		
		if ($result = pg_query($db, "SELECT u.name as name, p.name as permission FROM users u right outer join permissions p on u.id = p.user_id where u.hidden = false order by u.name")) 
		{
            $current_user = NULL;
            $current_permissions = [];

			while ($row = pg_fetch_assoc($result))
			{
                if ($current_user !== $row["name"])
                {
                    if ($current_user !== NULL)
                    {
?>                                            
                        <tr class="<?= isset($current_permissions["enabled"]) ? "user_enabled" : "user_disabled" ?>">
                        <td><?= $current_user ?></td>
                        <td class="users-table-options"><a href="#" class="edit" title="Edit User" onclick="editUser('<?= $current_user ?>')"><span class="icon-default"></span><span class="icon-hover"></span></a></td>
                        <td class="users-table-options">
                        <?php 
                        if($permissions['username'] != $current_user)
                            echo '<a href="#" class="delete" title="Delete User" onclick="deleteUser(\'' . $current_user . '\')"><span class="icon-default"></span><span class="icon-hover"></span></a>';
                        ?>
                        </td>
                        </tr>
<?php

                        $count++;
                    }
                    $current_user = $row["name"];
                    $current_permissions = [];
                }
                $current_permissions[] = $row["permission"];
			}
            
            if ($current_user !== NULL)
            {
?>                                            
                <tr class="<?= isset($current_permissions["enabled"]) ? "user_enabled" : "user_disabled" ?>">
                <td><?= $current_user ?></td>
                <td class="users-table-options"><a href="#" class="edit" title="Edit User" onclick="editUser('<?= $current_user ?>')"><span class="icon-default"></span><span class="icon-hover"></span></a></td>
                <td class="users-table-options">
                <?php 
                if($permissions['username'] != $current_user)
                    echo '<a href="#" class="delete" title="Delete User" onclick="deleteUser(\'' . $current_user . '\')"><span class="icon-default"></span><span class="icon-hover"></span></a>';
                ?>
                </td>
                </tr>
<?php
            }
			
			if($count == 0)
				echo "No users added yet.";
		}
		
		pg_close($db);
	}
	break;

	/**
	 * Adds a user to the database
	 */
	case "addUser":
	{
		$db = openWebDB();
		
		if(!$db)
			die("Error: Could not open database.");		
		
                pg_query($db, "BEGIN TRANSACTION");
		
		$username = "";
		if(isset($_REQUEST["username"]))
			$username = $_REQUEST["username"];
		
		if($username == "")
			die("Error: Empty Username.");
        
                if(preg_match('/[^a-zA-Z0-9_]/', $username) != 0)
                    die("Error: Invalid username.");

		$password = "";
		if(isset($_REQUEST["password"]))
			$password = $_REQUEST["password"];
		
		if($password == "")
			die("Error: Empty password.");
		
		$digestHash = md5("$username:InSyncAPI:$password");
		
		$hash = hash("sha256", $password);
		$salt = md5(uniqid(random_int(0, mt_getrandmax()), true));
		$hash = hash("sha256", $salt . $hash);
		
		$permissions = request_to_array();
		
        $new_uuid = str_uuid();
		if($result = pg_query_params($db, "INSERT INTO users (id,name,pass,salt,digest_password) VALUES ($1,$2,$3,$4,$5)",
                        [$new_uuid, $username, $hash, $salt, $digestHash]))
		{
			if($result == FALSE)
			{
                pg_query($db, "ROLLBACK TRANSACTION");
                pg_close($db);

                die("Error: Unable to add user.");
			}

            if (update_user_permissions_by_id($db, $new_uuid, $permissions)) 
            {
                $timeout = 15;
                if(isset($_REQUEST["timeout"]))
                    $timeout = $_REQUEST["timeout"];
                
                if($timeout < 0)
                    $timeout = 0;
                
                if($timeout > 540)
                    $timeout = 540;
                
                if(update_user_setting_by_id($db, $new_uuid, "timeout", $timeout))
                {
                    pg_query($db, "COMMIT TRANSACTION");
                    pg_close($db);

                    die("Success");
                }
            }
        }
		
		$err = pg_last_error($db);
        pg_query($db, "ROLLBACK TRANSACTION");
        pg_close($db);
		die("Error: " . $err);
	}
	break;
	
	/**
	 * Edits an existing user
	 */
	case "editUser":
	{
		$db = openWebDB();
		
		if(!$db)
			die("Error: Could not open database.");		

                pg_query($db, "BEGIN TRANSACTION");
		
		$username = "";
		if(isset($_REQUEST["username"]))
			$username = $_REQUEST["username"];
		
		if($username == "")
			die("Error: Empty Username.");
	
		$permissions = request_to_array();
        
        $password = "";
		if(isset($_REQUEST["password"]))
			$password = $_REQUEST["password"];
		
		$digestHash = md5("$username:InSyncAPI:$password");
		
		$hash = hash("sha256", $password);
		$salt = md5(uniqid(random_int(0, mt_getrandmax()), true));
		$hash = hash("sha256", $salt . $hash);

        if (update_user_permissions_by_name($db, $username, $permissions)) 
        {
            $timeout = 15;
            if(isset($_REQUEST["timeout"]))
                $timeout = $_REQUEST["timeout"];

            if($timeout < 0)
                $timeout = 0;

            if($timeout > 540)
                $timeout = 540;

            if(!update_user_setting_by_name($db, $username, "timeout", $timeout))
            {
                pg_query($db, "ROLLBACK TRANSACTION");
                pg_close($db);

                die("Error: Unable to edit user timeout setting.");
            }
            
            if($password != "")
            {
                if($result = pg_query_params($db, "UPDATE users SET pass=$1,salt=$2,digest_password=$3 WHERE name=$4",
                            [$hash, $salt, $digestHash, $username]
                        ))
                {
                    if($result == FALSE)
                    {
                        pg_query($db, "ROLLBACK TRANSACTION");
                        pg_close($db);

                        die("Error: Unable to edit user password.");
                    }
                }
            }

            pg_query($db, "COMMIT TRANSACTION");
            pg_close($db);

            die("Success");
		}
		
                pg_query($db, "ROLLBACK TRANSACTION");
                pg_close($db);
		die("Error: Unable to edit user.");
	}
	break;
	
	/**
	 * Retrieves permissions for a user
	 */
	case "viewUser":
	{
		$db = openWebDB();
		
		if(!$db)
			die("Error: Could not open database.");		
		
		$name = "";
		if(isset($_REQUEST["name"]))
			$name = $_REQUEST["name"];
		
		if($name == "")
			die("Error: Empty user Index.");
        
        $data = [];
        $data["name"] = $name;
        $data["permissions"] = "";
        $data["settings"] = [];
		
		if($result = pg_query_params($db, "SELECT u.name as name, p.name as permission FROM users u right outer join permissions p on u.id = p.user_id WHERE u.name=$1", [$name]))
		{
            while ($resultRow = pg_fetch_assoc($result))
            {
                if ($data["permissions"] !== "")
                    $data["permissions"] .= ",";

                $data["permissions"] .= $resultRow["permission"];
            }
            
            if($result = pg_query_params($db, "SELECT s.name as key, s.value as value FROM users u right outer join user_settings s on u.id = s.user_id WHERE u.name=$1", [$name]))
            {
                while ($resultRow = pg_fetch_assoc($result))
                {
                    if(isset($resultRow["key"]) && isset($resultRow["value"]))
                        $data["settings"][$resultRow["key"]] = $resultRow["value"];
                }
            }

            pg_close($db);
            die(json_encode($data));
		}
		
        pg_close($db);
		die("Error: Unable to find user.");
	}
	break;
	
	/**
	 * Deletes a user from the DB
	 */
	case "deleteUser":
	{
		$db = openWebDB();
		
		if(!$db)
			die("Error: Could not open database.");		
		
		$username = "";
		if(isset($_REQUEST["username"]))
			$username = $_REQUEST["username"];
		
		if($username == "")
			die("Error: Empty user Index.");
		
		if($result = pg_query_params($db, "DELETE FROM users WHERE name=$1", [$username]))
		{
			if($result == FALSE)
			{
                                pg_close($db);
				
				die("Error: Unable to delete user.");
			}
			
                        pg_close($db);
			
			die("Success");
		}
		
                pg_close($db);
		die("Error: Unable to find user.");
	}
	break;
    
    case "status":
    {
        $hash = "";
        if(isset($_REQUEST['hash']))
            $hash = $_REQUEST['hash'];
        
        if($hash == "")
            die("Error: No hash specified.");
        
        require_once("pathDefinitions.php");
        
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";
        
        $result = @readfile($statusFile);
        
        if($result == FALSE)
            die("Error: Cannot read status file.");
    }
    break;
    
    /**
	 * Handles requests to edit/add/delete user from another processor
	 */
	case "sync":
	{
        header("Content-Encoding: none");
        
		$receive = false;
		if(isset($_REQUEST["receive"]))
			$receive = $_REQUEST["receive"];
        
        if(!$receive)
        {
            // send list to all other procs
            
            require_once("pathDefinitions.php");
            require_once("rolling-curl/RollingCurl.php");

            // get all intersection IPs
            $intersectionArr = [];
            
			$Intersections = getCorridorIntersections();
			if($Intersections === FALSE)
			{
				die("Error: Unable to read Corridor.xml file!");
			}

			if ($Intersections)
			{
		        $data = getUserXML();
		        
		        $hash = md5($data);
		        
		        ob_end_clean();
		        header("Connection: close");
		        ignore_user_abort();
		        ob_start();
		        echo $hash;
		        $size = ob_get_length();
		        header("Content-Length: $size");
		        ob_end_flush();
		        flush();

                $protocol = "https://";

		        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $hash . ".txt";

		        // create status XML document
		        $statusXML = new SimpleXMLElement("<corridor></corridor>");	
		        $statusXML->addAttribute("status", "working");

				foreach ($Intersections as $IntIP => $name)
				{
					$intersectionArr[] = $protocol . $IntIP . "/helpers/editUserHelper.php";
	                $intersectionXML = $statusXML->addChild("intersection");
	                $intersectionXML->addAttribute("ip", $IntIP);
	                $intersectionXML->addAttribute("status", "working");
	            }
	
	            // set the PHP time limit to account for # of servers * 45. we should
	            // NEVER hit this limit due to the request pooling for Rolling-Curl
	            set_time_limit(count($intersectionArr) * 45);
	
	            @file_put_contents($statusFile, $statusXML->asXML());
	            
	            $postParams = ["action"=>"sync", "receive"=>"true", "data"=>$data, "u" => base64_encode("PEC"), "p" => base64_encode("lenpec4321")];
	            $collector = new PropagationCollector();
	            $collector->run($intersectionArr, $postParams, $hash);
			}
			else
			{
                die("Error: No Intersections in Management Group!");
			}
        }
        else 
        {
            // receive the user/permissions table from the remote host            
            $data = "";
            if(isset($_REQUEST["data"]))
                $data = $_REQUEST["data"];
            
            if($data == "")
                die("Error: No data received");
            
            $success = updateUserTables($data);
            
            if($success)
                die("Success");
            else
                die("Error: Unable to update user tables");
        }
	}
	break;
}

function updateUserTables($data)
{
    $xmlDoc = @simplexml_load_string($data);
    
    if(!$xmlDoc)
        return false;
    
    $db = openWebDB();
		
    if(!$db)
        return false;
    
    pg_query($db, "BEGIN TRANSACTION");
    
    if(!pg_query($db, "DELETE FROM users"))
    {
        pg_query($db, "ROLLBACK TRANSACTION");
        pg_close($db);
        return false;
    }
    
    if(!pg_query($db, "DELETE FROM permissions"))
    {
        pg_query($db, "ROLLBACK TRANSACTION");
        pg_close($db);
        return false;
    }
    
    if(!pg_query($db, "DELETE FROM user_settings"))
    {
        pg_query($db, "ROLLBACK TRANSACTION");
        pg_close($db);
        return false;
    }
    
    // insert users
    foreach($xmlDoc->usertable->row as $user)
    {
        $id = base64_decode((string)$user["id"]);
        $name = base64_decode((string)$user["name"]);
        $pass = base64_decode((string)$user["pass"]);
        $salt = base64_decode((string)$user["salt"]);
        $digest_password = base64_decode((string)$user["digest_password"]);
        $hidden = base64_decode((string)$user["hidden"]);
        if(!pg_query_params($db, "INSERT INTO users (id,name,pass,salt,digest_password,hidden) VALUES ($1,$2,$3,$4,$5,$6)",
                        [$id, $name, $pass, $salt, $digest_password, $hidden]
                ))
        {
            pg_query($db, "ROLLBACK TRANSACTION");
            pg_close($db);
            return false;
        }
    }
    
    // insert permissions
    foreach($xmlDoc->permissiontable->row as $permission)
    {
        $user_id = base64_decode((string)$permission["user_id"]);
        $name = base64_decode((string)$permission["name"]);

        if(!pg_query_params($db, "INSERT INTO permissions (user_id,name) VALUES ($1,$2)",
                            [$user_id, $name]
                ))
        {
            pg_query($db, "ROLLBACK TRANSACTION");
            pg_close($db);
            return false;
        }
    }
    
    // insert settings
    foreach($xmlDoc->settingstable->row as $setting)
    {
        $user_id = base64_decode((string)$setting["user_id"]);
        $name = base64_decode((string)$setting["name"]);
        $value = base64_decode((string)$setting["value"]);

        if(!pg_query_params($db, "INSERT INTO user_settings (user_id,name,value) VALUES ($1,$2,$3)",
                            [$user_id, $name, $value]
                ))
        {
            pg_query($db, "ROLLBACK TRANSACTION");
            pg_close($db);
            return false;
        }
    }
    
    pg_query($db, "COMMIT TRANSACTION");
    pg_close($db);
    
    return true;
}

function getUserXML()
{
    $data = "<tables><usertable>\r\n";        

    $db = openWebDB();

    if(!$db)
        return false; 

    if($result = pg_query($db, "SELECT id, name, pass, salt, digest_password, hidden FROM users"))
    {
        while($resultRow = pg_fetch_assoc($result))
        {
            $data .= "<row ";
            
            foreach($resultRow as $key => $value)
                $data .= $key . '="' . base64_encode($value) . '" ';
            
            $data .= "/>\r\n";
        }        
    }
    
    $data .= "</usertable>";
    $data .= "<permissiontable>";
    
    if($result = pg_query($db, "SELECT user_id, name FROM permissions"))
    {
        while($resultRow = pg_fetch_assoc($result))
        {
            $data .= "<row ";
            
            foreach($resultRow as $key => $value)
                $data .= $key . '="' . base64_encode($value) . '" ';
            
            $data .= "/>\r\n";
        }        
    }
    
    $data .= "</permissiontable>";
    
    $data .= "<settingstable>";
    
    if($result = pg_query($db, "SELECT user_id, name, value FROM user_settings"))
    {
        while($resultRow = pg_fetch_assoc($result))
        {
            $data .= "<row ";
            
            foreach($resultRow as $key => $value)
                $data .= $key . '="' . base64_encode($value) . '" ';
            
            $data .= "/>\r\n";
        }        
    }
    
    $data .= "</settingstable>";
    
    $data .= "</tables>";

    pg_close($db);
    
    return $data;
}

class PropagationCollector 
{
    private $rc;
    private $statusHash;

    function __construct()
    {
        $this->rc = new RollingCurl([$this, 'processResponse']);
        $this->rc->window_size = 10;
    }

    function processResponse($response, $info, $request)
    {
        if ($info['retried']) {
            return;
        }
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
            return;
        
        $intersection = &$statusXML->xpath("//intersection[@ip='" . $info["primary_ip"] . "']");
        
        if($intersection == FALSE)
            return;
        
        if($response == "Success")
            $intersection[0]["status"] = "completed";
        else
            $intersection[0]["status"] = "error";
        
        @file_put_contents($statusFile, $statusXML->asXML());
    }

    function run($urls, $postParams, $hash)
    {
        $this->statusHash = $hash;
        
        foreach ($urls as $url)
        {
            $request = new RollingCurlRequest($url);
            $request->options = [CURLOPT_POST => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_CONNECTTIMEOUT => 25, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 45, CURLOPT_POSTFIELDS => $postParams];
            $fallback_request = new RollingCurlRequest(preg_replace('/^https:/i', 'http:', $url));
            $fallback_request->options = $request->options;
            $request->fallback_request = $fallback_request;
            $this->rc->add($request);
        }
        
        $this->rc->execute();
        
        // after updating the intersections, check to see if ALL intersections 
        // are complete or any returned an error
        $statusFile = TEMP_ROOT . "/" . "propagationStatus-" . $this->statusHash . ".txt";
        $statusXML = @simplexml_load_file($statusFile);
        
        if($statusXML === FALSE)
            return;
        
        $intersections = &$statusXML->xpath("//intersection");
        $errors = 0;
        
        foreach($intersections as $intersection)
            if($intersection["status"] == "error")
                $errors++;
            
        if($errors > 0)
            $statusXML["status"] = "error";
        else
            $statusXML["status"] = "completed";
        
        file_put_contents($statusFile, $statusXML->asXML());
    }
}

function update_user_setting_by_id($db, $user_id, $setting, $value)
{
    if (pg_query_params($db, 'delete from user_settings where user_id = $1 and name = $2', [$user_id, $setting])) 
    {
        if (!pg_query_params($db, 'insert into user_settings (user_id, name, value) values ($1, $2, $3)',
                [$user_id, $setting, $value]))
            die("Error: Failed to add new setting." . pg_last_error($db));

        return true;
    }

    die("Error: Failed to remove old permissions." . $db->error);
}

function update_user_setting_by_name($db, $user_name, $setting, $value)
{
    if (pg_query_params($db, 'delete from user_settings where user_id in (select id from users where name = $1) and name = $2', [$user_name, $setting])) 
    {
        if (!pg_query_params($db, "insert into user_settings (user_id, name, value) select id, $2, $3 from users where name = $1",
                    [$user_name, $setting, $value]))
            die("Error: Failed to add new setting." . pg_last_error($db));

        return true;
    }

    die("Error: Failed to remove old permissions." . $db->error);
}

function update_user_permissions_by_id($db, $user_id, $permissions) {
    if (pg_query_params($db, 'delete from permissions where user_id = $1', [$user_id])) {
        foreach ($permissions as $permission) {
            if (!pg_query_params($db, 'insert into permissions (user_id, name) values ($1, $2)',
                    [$user_id, $permission])) {
                die("Error: Failed to add new permissions." . pg_last_error($db));
            }
        }

        return true;
    }

    die("Error: Failed to remove old permissions." . $db->error);
}

function update_user_permissions_by_name($db, $user_name, $permissions) 
{
    if (pg_query_params($db, 'delete from permissions where user_id in (select id from users where name = $1)',
            [$user_name])) 
    {
        foreach ($permissions as $permission) 
        {
            if (!pg_query_params($db, "insert into permissions (user_id, name) select id, $2 from users where name = $1",
                    [$user_name, $permission]))
            {
                die("Error: Failed to add new permissions.");
            }
        }
        return true;
    }

    die("Error: Failed to remove old permissions.");
}
?>
