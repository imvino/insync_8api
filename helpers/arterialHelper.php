<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

if (empty($permissions["corridor"]))
   die("Error: You do not have permission to access this page.");

require_once("rolling-curl/RollingCurl.php");
require_once("pathDefinitions.php");
require_once("insyncInterface.php");

$action = "";
if(isset($_REQUEST['action']))
   $action = $_REQUEST['action'];

switch($action)
{
   /**
    * Retrieves current Corridor.xml file
    */
   case "getcurrent":
   {
      if(file_exists(CORRIDOR_CONF_FILE))
      {
         set_time_limit(1800);
         
         header('Content-Type: application/xml');
         header("Cache-Control: no-store, no-cache, must-revalidate");
         header('Content-Length: ' . filesize(CORRIDOR_CONF_FILE));

          ob_clean();
          flush();
         readfile(CORRIDOR_CONF_FILE);
      }
      else
         ErrorExit("No Corridor.xml found.");
   }
   break;
   
   /**
    * Retrieves hash of currently running Corridor.xml for user
    */
   case "getcurrenthash":
   {
      $insync = new InSyncInterface();
   
      $retval = $insync->getCorridorHash();
      if($retval == "No data from InSync." || strlen($retval) != 32)
      {
         ErrorExit("Invalid Data from InSync");
      }
      else
      {
         die($retval);
      }

   }
   break;
   
   /**
    * Retrieves the compressed archive specified by $hash
    */
   case "getarchivecompressed":
   {
      $hash = "";
      if(isset($_REQUEST['hash']))
         $hash = $_REQUEST['hash'];

      if($hash == "")
         ErrorExit("No hash specified.");

      getArchive($hash, true);
   }
   break;
   
   /**
    * Retrieves the uncompressed archive specified by $hash
    */
   case "getarchive":
   {
      $hash = "";
      if(isset($_REQUEST['hash']))
         $hash = $_REQUEST['hash'];

      if($hash == "")
         ErrorExit("No hash specified.");

      getArchive($hash);
   }
   break;
    
    /**
    * Checks if an archive exists on a processor
    */
   case "checkarchive":
   {
      $hash = "";
      if(isset($_REQUEST['hash']))
         $hash = $_REQUEST['hash'];

      if($hash == "")
         ErrorExit("No hash specified.");
        
      if(!archiveExists($hash))
         die("no");
      else
         die("yes");
   }
   break;
   
   /**
    * Retrieves a list of all Management Group archives on the box
    */
   case "getarchivelist":
   {
      getArchiveList();
   }
   break;

   /**
    * Receives a Management Group archive upload from another proc
    */
   case "receivefile":
   {
      receiveFile($_FILES['file']['name'], $_FILES['file']['tmp_name']);
   }
   break;

   /**
    * Restores an archive specified by $hash
    */
   case "restore":
   {
      $hash = "";
      if(isset($_REQUEST['hash']))
         $hash = $_REQUEST['hash'];

      restoreCorridor($hash);

      die("Success");
   }
   break;

   case "commonsVersion":
   {
	die('{ "version": "'. exec(INSYNC_EXE . " /commonsVersion") . '" }');
   }
   break;


   default:
      header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
      exit;
}

/**
 * Restores Management Group from archive
 * @param MD5 $hash hash of archive to restore
 */
function restoreCorridor($hash)
{
   if($hash == "")
      ErrorExit("No hash specified");
   
   // find path to archive with this hash
   $archive = archiveExists($hash);
   
   if(!$archive)
      ErrorExit("No archive for hash $hash");
   
   $contents = "";
   
   // read Management Group from gzip
   $handle = gzopen($archive, "rb");
   while (!gzeof($handle))
      $contents .= gzread($handle, 8192);
   gzclose($handle);
   
   // write out temp .new file
   file_put_contents(CORRIDOR_CONF_FILE . ".new.temp", $contents);
   if(file_exists(CORRIDOR_CONF_FILE . ".new"))
   {
	   unlink(CORRIDOR_CONF_FILE . ".new");
   }
   rename(CORRIDOR_CONF_FILE . ".new.temp", CORRIDOR_CONF_FILE . ".new");
   
   // restart InSync
   exec(APPS_ROOT . "/RestartInSync.exe");
}

function cleanArchives()
{
   $archivefiles = glob(CORRIDOR_CONF_ARCHIVE_ROOT . '/*.corridor');

   //Maintain at least 1 Management Group in the archive
   if(is_array($archivefiles) && count($archivefiles) > 1)
   {
      $creationtimes = [];
      foreach($archivefiles as $filename)
      {
         $creationtimes[$filename] = @filemtime($filename);
      }
      asort($creationtimes, SORT_NUMERIC);

      $deletionlist = [];

      foreach ($creationtimes as $name => $date)
      {
         if(count($deletionlist) < count($creationtimes) - 1)
         {
            //Delete after 30 24 hour periods.
            if( (time() - $date) > 2592000)
            {
               $deletionlist[$name] = $date;
            }
         }
      }

      foreach ($deletionlist as $name => $date)
      {
         //Delete the Management Group
         @unlink($name);
      }
   }    
}

/**
 * Checks to see if intersections have file already
 * @param type $intersectionArr
 * @param type $hash
 */
function getTargetIntersections($intersectionArr, $hash)
{    
   $postParams = ["action"=>"checkarchive", "hash"=>$hash, "u" => base64_encode("PEC"), "p" => base64_encode("lenpec4321")];
    
   $collector = new CheckArchiveCollector();
   return $collector->run($intersectionArr, $postParams, $hash);
}

/**
 * Check if an archive exists
 * @param type $hash hash value to check
 * @return boolean false if no archive for $hash, otherwise full path to archive file
 */
function archiveExists($hash)
{
   // attempt to create Management Group archive dir if it doesnt exist
   if(!is_dir(CORRIDOR_CONF_ARCHIVE_ROOT))
      @mkdir(CORRIDOR_CONF_ARCHIVE_ROOT, 0777, true);
   
   $dirHandle = @opendir(CORRIDOR_CONF_ARCHIVE_ROOT);
         
   if($dirHandle == FALSE)
      ErrorExit("Could not open management group archive directory.");
   
   // loop thru files in directory
   while (($file = readdir($dirHandle)) !== false) 
   {
      if($file == "." || $file == "..")
         continue;

      $parts = pathinfo($file);

      // skip anything not ending in .corridor
      if($parts["extension"] != "corridor")
         continue;

      $fileInfo = explode("-", $parts['filename']);

      if(count($fileInfo) != 2)
         continue;

      // return this file name, if the hashes match
      if($fileInfo[0] == $hash)
         return CORRIDOR_CONF_ARCHIVE_ROOT . "/" . $file;
   }

   closedir($dirHandle);   
   return false;
}

/**
 * Receives a file from another machine
 * @param type $name POST name
 * @param type $tmp_name POST tmp_name
 */
function receiveFile($name, $tmp_name)
{   
   if($name == "" || $tmp_name == "")
      ErrorExit("Empty parameter");
   if(str_contains($name, '/'))
      ErrorExit("Illegal character in file name.");
   if(str_contains($name, '\\'))
      ErrorExit("Illegal character in file name.");

   if (!is_dir(CORRIDOR_CONF_ARCHIVE_ROOT))
   {
      mkdir(CORRIDOR_CONF_ARCHIVE_ROOT);
   }

   $verification = exec(INSYNC_EXE . " /verify " . $tmp_name);

   if($verification == "ACCEPTED")
   {
       if (!move_uploaded_file($tmp_name, CORRIDOR_CONF_ARCHIVE_ROOT . "/" . $name))
       {
          ErrorExit("Unable to move file to " . CORRIDOR_CONF_ARCHIVE_ROOT);
       }
       die("Success");
   }
   else
   {
       ErrorExit($verification);
   }

}

/**
 * Retrieves the contents of an archive
 * @param string $hash hash of archive to retrieve
 */
function getArchive($hash, $compressed = false)
{
   if(is_dir(CORRIDOR_CONF_ARCHIVE_ROOT))
   {
      $dirHandle = @opendir(CORRIDOR_CONF_ARCHIVE_ROOT);
         
      if($dirHandle == FALSE)
         ErrorExit("Could not open management group archive directory.");
      
      while (($file = readdir($dirHandle)) !== false) 
      {
         if($file == "." || $file == "..")
            continue;
         
         $parts = pathinfo($file);

         if($parts["extension"] != "corridor")
            continue;
         
         $fileInfo = explode("-", $parts['filename']);
         
         if(count($fileInfo) != 2)
            continue;
         
         if($fileInfo[0] == $hash)
         {
            if(!$compressed)
            {
               header('Content-Type: application/xml');
               header("Cache-Control: no-store, no-cache, must-revalidate");

               $gzfile = gzopen(CORRIDOR_CONF_ARCHIVE_ROOT . "/" . $file, "rb");
               $contents = "";

               while (!gzeof($gzfile))
                  $contents .= gzread($gzfile, 8192);

               gzclose($gzfile);
               
               echo $contents;
            }
            else
            {
               header('Content-Type: gzip');
               header("Cache-Control: no-store, no-cache, must-revalidate");
               
               readfile(CORRIDOR_CONF_ARCHIVE_ROOT . "/" . $file);
            }
         
            exit;
         }
      }
      
      closedir($dirHandle);
   }
}

/**
 * Retrieves XML list of archives on system
 */
function getArchiveList()
{
   if(is_dir(CORRIDOR_CONF_ARCHIVE_ROOT))
   {
      $dirHandle = @opendir(CORRIDOR_CONF_ARCHIVE_ROOT);
         
      if($dirHandle == FALSE)
         ErrorExit("Could not open management group archive directory.");
      
      $doc = new DOMDocument();
      $root = $doc->createElement('Archive');
      $root = $doc->appendChild($root);

      while (($file = readdir($dirHandle)) !== false) 
      {
         if($file == "." || $file == "..")
            continue;
         
         $parts = pathinfo($file);

         if($parts["extension"] != "corridor")
            continue;
         
         $fileInfo = explode("-", $parts['filename']);
         
         if(count($fileInfo) != 2)
            continue;
         
         $CorridorElem = $doc->createElement('Corridor');
         $CorridorElem = $root->appendChild($CorridorElem);

         $NameAttribute = $doc->createAttribute('hash');
         $NameAttribute->value = $fileInfo[0];
         $CorridorElem->appendChild($NameAttribute);

         $DateAttribute = $doc->createAttribute('date');
         $DateAttribute->value = date("F d Y H:i:s", $fileInfo[1]);
         $CorridorElem->appendChild($DateAttribute);
      }
      
      closedir($dirHandle);
      
      header('Content-Type: application/xml');
      header("Cache-Control: no-store, no-cache, must-revalidate");
      echo $doc->saveXML();
   }
    else
    {
        if(!@mkdir(CORRIDOR_CONF_ARCHIVE_ROOT, 0777, true))
            ErrorExit("Could not create management group archive directory!");
        
        $doc = new DOMDocument();
        $root = $doc->createElement('Archive');
        $root = $doc->appendChild($root);
        
        header('Content-Type: application/xml');
        header("Cache-Control: no-store, no-cache, must-revalidate");
        echo $doc->saveXML();        
    }
}

/**
 * Helper function to return an error header and spit out an error string
 * @param type $message Error message
 */
function ErrorExit($message)
{
   header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
   die("Error: " . $message);
}

class CheckArchiveCollector 
{
   private $rc;
   private $statusArr;

   function __construct()
   {
      $this->rc = new RollingCurl([$this, 'processResponse']);
      $this->rc->window_size = 10;
      $this->statusArr = [];
   }

   function processResponse($response, $info, $request)
   {        
      if ($info['retried']) 
      {
         return;
      }
      if($response == "yes")
         $this->statusArr[$info["primary_ip"]] = true;
      else
         $this->statusArr[$info["primary_ip"]] = false;
   }

   function run($urls, $postParams, $hash)
   {        
      foreach ($urls as $url)
      {
         $request = new RollingCurlRequest($url);
         $request->options = [CURLOPT_POST => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 15, CURLOPT_POSTFIELDS => $postParams];
         $fallback_request = new RollingCurlRequest(preg_replace('/^https:/i', 'http:', $url));
         $fallback_request->options = $request->options;
         $request->fallback_request = $fallback_request;
         $this->rc->add($request);
      }
        
      $this->rc->execute();
        
      return $this->statusArr;
   }
}

?>
