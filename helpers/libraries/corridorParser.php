<?php
use \Exception;
require_once __DIR__ . '/../constants.php';
require_once SITE_DOCUMENT_ROOT . 'helpers/pathDefinitions.php';

class CorridorParser {
    private $corridor = null;

    function __construct() {
	$this->corridor = @simplexml_load_file(CORRIDOR_CONF_FILE);

	if($this->corridor == FALSE)
            throw new Exception("Unable to load Corridor.xml. " . CORRIDOR_CONF_FILE);
    }
	
    function getIntersectionIPs() {
	// get all intersection IPs
	$intersectionArr = [];
	foreach($this->corridor->Intersection as $Intersection) 
    {
        if((string)$Intersection["IP"] != "" && (string)$Intersection["IP"] != "127.0.0.1" && ip2long((string)$Intersection["IP"]) !== false)
            $intersectionArr[] = $Intersection["IP"];
	}
	
        return $intersectionArr;
    }

    /**
     * Return the parsed corridor.
     * 
     * The results of this call should not be modified.
     * 
     * @return SimpleXmlElement the SimpleXmlElement
     */
    function getReadOnlySimpleXml() {
	return $this->corridor;
    }
}
?>
