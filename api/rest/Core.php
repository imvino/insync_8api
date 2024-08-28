<?php
require_once(__DIR__ . '/../../helpers/constants.php');
require_once SITE_DOCUMENT_ROOT . 'helpers/libraries/corridorParser.php';
require_once SITE_DOCUMENT_ROOT . 'helpers/rolling-curl/RollingCurl.php';

$corridor = new CorridorParser();
global $corridorIPs;
$corridorIPs = $corridor->getIntersectionIPs();

/**
 * This is the public facing API for interacting with InSync from remote
 * applications.  Information about the status of the InSync information
 * can be collected from this API to provide intergration with third-party
 * applications.
 *
 * @author Rhythm Engineering
 */
class Core {
        /**
         *
         * @var DOMDocument 
         */
        private $result_dom;
        /**
         *
         * @var DOMNode
         */
        private $results_node;

        private function getIntersectionList($intersections) {
            $list = [];
            if ($intersections == 'all') {
                global $corridorIPs;
                $list = $corridorIPs;
            } else {
                $list = explode(',', $intersections);
            }

            return $list;
        }

        /**
         * 
         * @param multi $response
         * @param array $info
         * @param RollingCurlRequest $request
         * @access private
         */
        public function request_callback($response, $info, $request) {
            if ($info['retried']) {
                return;
            }
            $new_result = $this->result_dom->createElement('result');
            $new_result->setAttribute('ip', $request->options[CURLOPT_PRIVATE]);
            $new_result->setAttribute('http_code', $info['http_code']);
            $updated_response = false;
            try {
                if (strlen(trim($response)) > 0 && str_starts_with(trim($response), '<')) {
                    $result_doc = new DOMDocument("1.0", "UTF-8");
                    if ($result_doc->loadXML($response)) {
                        if ($result_doc->documentElement != null) {
                            $result_fragment = $this->result_dom->importNode($result_doc->documentElement, TRUE);
                            $new_result->appendChild($result_fragment);
                            $updated_response = true;
                        }
                    }
                }
            }
            catch (Exception) {
                $result_text = $this->result_dom->createCDATASection($response);
                $new_result->appendChild($result_text);
                $updated_response = true;
            }
            if (!$updated_response) {
                $result_text = $this->result_dom->createCDATASection($response);
                $new_result->appendChild($result_text);
            }
            $this->results_node->appendChild($new_result);
        }

        private function distributeRequest($intersections, $action, $params) {
            ob_start();
            $list = $this->getIntersectionList($intersections);
            $this->result_dom = new DOMDocument("1.0", "UTF-8");
            $this->results_node = $this->result_dom->createElement('results');
            $this->result_dom->appendChild($this->results_node);
            $rc = new RollingCurl([$this, "request_callback"]);
            $rc->options = [CURLOPT_USERPWD => 'insync:keC2eswe', CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_CONNECTTIMEOUT => 1, CURLOPT_TIMEOUT => 5];
            $rc->window_size = 10;

            foreach ($list as $intersection) {
                if (preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $intersection)) {
                    $request = new RollingCurlRequest('https://' . $intersection . SITE_PREFIX . '/corridor_api/CorridorAPI.php');
                    $request->options = [CURLOPT_PRIVATE => $intersection, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => array_merge(['action' => $action], $params), CURLOPT_SSL_VERIFYHOST => FALSE, CURLOPT_SSL_VERIFYPEER => FALSE];
                    $fallback_request = new RollingCurlRequest('http://' . $intersection . SITE_PREFIX . '/corridor_api/CorridorAPI.php');
                    $fallback_request->options = $request->options;
                    $request->fallback_request = $fallback_request;
                    $rc->add($request);
                }
            }
            $rc->execute();
            
            ob_clean();
            return simplexml_import_dom($this->result_dom);
        }

	/**
	 * Return a string representation of the current web UI version.
	 * 
	 * Gets the version of the web UI from the version information file.
	 * 
         * @param string $intersections
	 * @return string The code version of the current InSync web UI.
	 */
	function getWebUIVersion($intersections = "") {
            if (strlen($intersections) == 0) {
                return file_get_contents(SITE_DOCUMENT_ROOT . "includes/version.txt");
            } else {
                return $this->distributeRequest($intersections, "webuiversion", []);
            }
	}
    
    /**
	 * Return a string representation of the intersection name
	 * 
	 * Gets the name from the Intersection.xml in the DB
	 * 
         * @param string $intersections
	 * @return string The code version of the current InSync web UI.
	 */
	function getIntersectionName($intersections = "") 
    {
        $loggedIn = true;
        require_once(SITE_DOCUMENT_ROOT . "helpers/databaseInterface.php");
        
        if (strlen($intersections) == 0)
        {        
            $rawXML = getFile("Intersection.xml");
            $xmlDoc = @simplexml_load_string($rawXML);

            if($xmlDoc === FALSE)
                 return "Error: Unable to load name";

            if(isset($xmlDoc->Intersection[0]["name"]))
                return (string)$xmlDoc->Intersection[0]["name"];
            else
                return "Error: Unable to load name";
        }
        else 
            return $this->distributeRequest($intersections, "intersectionname", []);
	}

	/**
	 * Return an InSync camera image.
	 * 
	 * Get the current camera image from InSync and return it to the user
	 * in the specified size, quality and format.
	 * 
	 * @param string $camera_name
	 * The name of the camera for which to retrieve an image.
	 * @param string $filter
	 * The filter effect to use on the image (normal or edge).
	 * @param integer $quality
	 * The JPG image quality parameter
	 * @param integer $width
	 * The width in pixels of the image to return
	 * @param integer $height
	 * The height in pixels of the image to return
	 * @param string $mode
	 * The mode of the image
	 * @return image The created jpg image data
	 */
	function getCameraImage($camera_name, $filter = 'normal', $quality = 75, $width = 320, $height = 240, $mode = 'simple', $intersections = '') {
            if (strlen($intersections) == 0) {
		require_once(SITE_DOCUMENT_ROOT . "auth/authSystem.php");

		// set this variable to true, so insyncInterface doesnt try to authenticate
		$loggedIn = true;
		require_once(SITE_DOCUMENT_ROOT . "helpers/insyncInterface.php");
		$insync = new InSyncInterface();

		if (authSystem::ValidateUser()) {
			if (isset($camera_name) && isset($filter)
				&& isset($quality) && isset($width)
				&& isset($height) && isset($mode)) {
				return $insync->getImage($camera_name, $filter,
					$quality, $width, $height, $mode);
			} else {
				return $insync->drawErrorImage("Invalid request.", $width, $height);
			}
		} else {
			return $insync->drawErrorImage("Authorization Required.", $width, $height);
		}
            } else {
                $images = $this->distributeRequest($intersections, "cameraimage", ['camera_name' => $camera_name, 'filter' => $filter, 'quality' => $quality, 'width' => $width, 'height' => $height, 'mode' => $mode]);
                if (count(explode(',', $intersections)) == 1
                        && $intersections != 'all') {
                    return base64_decode((string)$images->result);
                }
                return $images;
            }
	}

        /**
         * Get current light and tunnel status information
         * 
         */
	function getLightState($intersections = '') {
            if (strlen($intersections) == 0) {
                try {
                    require_once(SITE_DOCUMENT_ROOT . "auth/authSystem.php");

                    // set this variable to true, so insyncInterface doesnt try to authenticate
                    $loggedIn = true;
                    require_once(SITE_DOCUMENT_ROOT . "helpers/insyncInterface.php");
                    $insync = new InSyncInterface();

                    if (authSystem::ValidateUser()) {
                            return simplexml_load_string($insync->getLightState());
                    } else {
                            return simplexml_load_string('<Error>Authentication Required</Error>');
                    }
                }
                catch (Exception) {
                    return simplexml_load_string('<Error>Unexpected error</Error>');
                }
            } else {
                return $this->distributeRequest($intersections, "lightstate", []);
            }
	}

        /**
         * Get current light and tunnel status information
         * 
         */
	function getLightStateXml($intersections = '') {
            if (strlen($intersections) == 0) {
                try {
                    require_once(SITE_DOCUMENT_ROOT . "auth/authSystem.php");

                    // set this variable to true, so insyncInterface doesnt try to authenticate
                    $loggedIn = true;
                    require_once(SITE_DOCUMENT_ROOT . "helpers/insyncInterface.php");
                    $insync = new InSyncInterface();

                    if (authSystem::ValidateUser()) {
                            return $insync->getLightState();
                    } else {
                            return '<Error>Authentication Required</Error>';
                    }
                }
                catch (Exception) {
                    return simplexml_load_string('<Error>Unexpected error</Error>');
                }
            } else {
                return $this->distributeRequest($intersections, "lightstate", []);
            }
	}
}
?>
