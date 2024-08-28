<?php
if(!isset($loggedIn) || !$loggedIn)
{
	// this must be included on all pages to authenticate the user
	require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
	$permissions = authSystem::ValidateUser();
	// end
}

$action = "";
if(isset($_GET["action"]))
	$action = $_GET["action"];
if(isset($_POST["action"]))
	$action = $_POST["action"];
	
if(isset($action))
{
	// ex: GET databaseInterface.php?action=gethash&name=Intersection.xml,Corridor.xml,etc
	// returns the hash, or -1 for file that doesn't exist
	if($action == "gethash")
		if(isset($_GET["name"]))
			echo getHash($_GET["name"]);

	// ex: GET databaseInterface.php?action=getfile&name=Intersection.xml
	// returns the file contents, or error string
	if($action == "getfile")
	{
		if(isset($_GET["name"]))
		{
			$fileContents = getFile($_GET["name"]);
			header("Content-Length: " . strlen($fileContents));
			echo $fileContents;
		}
	}
	
	// ex: GET databaseInterface.php?action=getfilebyhash&hash=0db352f9fba077ca183670038d4fd958
	// returns the file contents, or error string
	if($action == "getfilebyhash")
		if(isset($_GET["hash"]))
			echo getFileByHash($_GET["hash"]);

	// ex: POST databaseInterface.php with file under part 'contents', and 'name'=filename
	// return MD5 hash for input file
	if($action == "putfile")
		echo putFile($_FILES['contents']['name'], $_FILES['contents']['tmp_name']);
}

/**
 * Returns file contents from database via a hash value
 * @param type $hash hash to retrieve
 * @return string file contents
 */
function getFileByHash($hash)
{	
	$db = openDB();
	
	if($result = pg_query_params($db, "SELECT contents FROM configurations WHERE hash=$1", [$hash]))
	{
		$resultRow = pg_fetch_assoc($result);		
		if($resultRow == FALSE)
		{
                        pg_close($db);
			return "Error: No entry found for $hash";
		}

                $contents = base64_decode($resultRow["contents"]);
		
                pg_close($db);
		header("Content-Length: " . strlen($contents));
		return $contents;
	}

        pg_close($db);
	return "Error: Could not read from database.";
}

/**
 * Gets the hash(es) of file(s) in the DB
 * Takes a CSV list of filenames for multiple hash retrieval
 * @param string $names CSV list of file names
 * @return string CSV list of hashes
 */
function getHash($names)
{	
	$db = openDB();
	
	$outputString = "";
	
	$nameArray = explode(",", $names);
	
	foreach($nameArray as $name)
	{		
		if($result = pg_query_params($db, "SELECT hash FROM configurations WHERE name=$1", [$name]))
		{
			$resultRow = pg_fetch_assoc($result);

			if($resultRow == FALSE)
			{
				$outputString .= "-1,";
				continue;
			}

			$outputString .= $resultRow["hash"] . ",";
		}
	}
	
        pg_close($db);
	return trim($outputString, ",");
}

/**
 * Inserts a file into the DB from a string
 * @param string $targetName File name to use in DB
 * @param string $contents File contents
 * @return string hash of inserted data
 */
function putFileFromString($targetName, $contents)
{	
	$db = openDB();
	
	$hash = md5($contents);

        $base64 = base64_encode($contents);

	// update existing (single transaction per request
        pg_query($db, "BEGIN TRANSACTION");
        pg_query_params($db, "UPDATE configurations set hash = $2, contents = $3 where name = $1;",
                [$targetName, $hash, $base64]);
        pg_query_params($db, "INSERT INTO configurations (name, hash, contents) select $1,$2,$3 where not exists (select 1 from configurations where name = $4);",
                [$targetName, $hash, $base64, $targetName]);
        
        pg_query($db, "COMMIT TRANSACTION");

	pg_close($db);
	
	return $hash;
}
	
/**
 * Inserts files into the DB from a POST submission
 * @param type $name
 * @param type $tmp_name
 * @return string CSV list of hashes of inserted files
 */
function putFile($name, $tmp_name)
{	
	$numFiles = count($name);
	$hashOutput = "";
	
	$db = openDB();
	
	pg_query($db, "BEGIN TRANSACTION");
	
	for($i=0; $i < $numFiles; $i++)
	{
		if($name[$i] == "" || $tmp_name[$i] == "")
			continue;
			
		if(str_contains($name[$i], '/'))
			continue;
		if(str_contains($name[$i], '\\'))
			continue;
		
		$targetName = $name[$i];
		$contents = file_get_contents($tmp_name[$i]);
		
		if($contents === FALSE)
			continue;
			
		$hash = md5($contents);
		
                $base64 = base64_encode($contents);

                // update existing (single transaction per request
                pg_query($db, "BEGIN TRANSACTION");
                pg_query_params($db, "UPDATE configurations set hash = $2, contents = $3 where name = $1;",
                        [$targetName, $hash, $base64]);
                pg_query_params($db, "INSERT INTO configurations (name, hash, contents) select $1,$2,$3 where not exists (select 1 from configurations where name = $1::character varying);",
                        [$targetName, $hash, $base64]);
                pg_query($db, "COMMIT TRANSACTION");
		
                $hashOutput .= $hash . ",";
	}
	
	pg_query($db, "COMMIT TRANSACTION");	
	pg_close($db);
	
	return trim($hashOutput, ",");
}

/**
 * Returns the contents of a file given a file name
 * @param string $name File name
 * @return string File contents
 */
function getFile($name)
{	
	$db = openDB();
	
	if($result = pg_query_params($db, "SELECT contents FROM configurations WHERE name=$1", [$name]))
	{
		$resultRow = pg_fetch_assoc($result);

		if($resultRow == FALSE)
		{
                        pg_close($db);
			return "Error: No entry found for $name";
		}
		
                pg_close($db);
		return base64_decode($resultRow["contents"]);
	}

        pg_close($db);
	return "Error: Could not read from database.";
}

/**
 * Helper function to manage database connection
 * @return
 */
function openDB()
{
        $db = pg_connect('host=127.0.0.1 dbname=insync user=config password=8runesWA connect_timeout=30')
		or die("Error: Could not connect to database: " . pg_last_error());
	
	return $db;
}
?>
