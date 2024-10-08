<?php
namespace Luracast\Restler;

use Luracast\Restler\Data\ApiMethodInfo;
use stdClass;
use Reflection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use InvalidArgumentException;
use Luracast\Restler\Format\iFormat;
use Luracast\Restler\Format\JsonFormat;
use Luracast\Restler\Format\UrlEncodedFormat;
use Luracast\Restler\Data\iValidate;
use Luracast\Restler\Data\Validator;
use Luracast\Restler\Data\ValidationInfo;

/**
 * REST API Server. It is the server part of the Restler framework.
 * inspired by the RestServer code from
 * <http://jacwright.com/blog/resources/RestServer.txt>
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc4
 */
class Restler extends EventEmitter
{

    // ==================================================================
    //
    // Public variables
    //
    // ------------------------------------------------------------------

    public const VERSION = '3.0.0rc4';

    /**
     * URL of the currently mapped service
     *
     * @var string
     */
    public $url;

    /**
     * Http request method of the current request.
     * Any value between [GET, PUT, POST, DELETE]
     *
     * @var string
     */
    public $requestMethod;

    /**
     * Requested data format.
     * Instance of the current format class
     * which implements the iFormat interface
     *
     * @var iFormat
     * @example jsonFormat, xmlFormat, yamlFormat etc
     */
    public $requestFormat;

    /**
     * Data sent to the service
     *
     * @var array
     */
    public $requestData = [];

    /**
     * Used in production mode to store the routes and more
     *
     * @var iCache
     */
    public $cache;

    /**
     * method information including metadata
     *
     * @var ApiMethodInfo
     */
    public $apiMethodInfo;

    /**
     * Response data format.
     *
     * Instance of the current format class
     * which implements the iFormat interface
     *
     * @var iFormat
     * @example jsonFormat, xmlFormat, yamlFormat etc
     */
    public $responseFormat;

    // ==================================================================
    //
    // Private & Protected variables
    //
    // ------------------------------------------------------------------

    /**
     * Base URL currently being used
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Associated array that maps formats to their respective format class name
     *
     * @var array
     */
    protected $formatMap = [];

    /**
     * Instance of the current api service class
     *
     * @var object
     */
    protected $apiClassInstance;

    /**
     * Name of the api method being called
     *
     * @var string
     */
    protected $apiMethod;

    /**
     * list of filter classes
     *
     * @var array
     */
    protected $filterClasses = [];
    protected $filterObjects = [];

    /**
     * list of authentication classes
     *
     * @var array
     */
    protected $authClasses = [];

    /**
     * list of error handling classes
     *
     * @var array
     */
    protected $errorClasses = [];

    /**
     * Caching of url map is enabled or not
     *
     * @var boolean
     */
    protected $cached;

    protected $apiVersion = 1;
    protected $requestedApiVersion = 1;
    protected $apiMinimumVersion = 1;

    protected $log = [];
    protected $startTime;
    protected $authenticated = false;

    // ==================================================================
    //
    // Public functions
    //
    // ------------------------------------------------------------------

    /**
     * Constructor
     *
     * @param boolean $productionMode
     *                              When set to false, it will run in
     *                              debug mode and parse the class files
     *                              every time to map it to the URL
     *
     * @param bool    $refreshCache will update the cache when set to true
     */
    public function __construct(/**
     * When set to false, it will run in debug mode and parse the
     * class files every time to map it to the URL
     */
    protected $productionMode = false, $refreshCache = false)
    {
        $this->startTime = time();
        Util::$restler = $this;
        if (is_null(Defaults::$cacheDirectory)) {
            Defaults::$cacheDirectory = dirname($_SERVER['SCRIPT_FILENAME']) .
                DIRECTORY_SEPARATOR . 'cache';
        }
        $this->cache = new Defaults::$cacheClass();
        // use this to rebuild cache every time in production mode
        if ($this->productionMode && $refreshCache) {
            $this->cached = false;
        }
    }

    /**
     * Store the url map cache if needed
     */
    public function __destruct()
    {
        if ($this->productionMode && !$this->cached) {
            $this->cache->set('routes', Routes::toArray());
        }
    }

    /**
     * Provides backward compatibility with older versions of Restler
     *
     * @param int $version restler version
     *
     * @throws \OutOfRangeException
     */
    public function setCompatibilityMode($version = 2)
    {
        if ($version <= intval(self::VERSION) && $version > 0) {
            require_once "restler{$version}.php";
            return;
        }
        throw new \OutOfRangeException();
    }

    /**
     * @param int $version                 maximum version number supported
     *                                     by  the api
     * @param int $minimum                 minimum version number supported
     * (optional)
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setAPIVersion($version = 1, $minimum = 1)
    {
        if (!is_int($version) && $version < 1) {
            throw new InvalidArgumentException
            ('version should be an integer greater than 0');
        }
        $this->apiVersion = $version;
        if (is_int($minimum)) {
            $this->apiMinimumVersion = $minimum;
        }
    }

    /**
     * Call this method and pass all the formats that should be
     * supported by the API.
     * Accepts multiple parameters
     *
     * @param string ,... $formatName class name of the format class that
     *               implements iFormat
     *
     * @example $restler->setSupportedFormats('JsonFormat', 'XmlFormat'...);
     * @throws Exception
     */
    public function setSupportedFormats($format = null /*[, $format2...$farmatN]*/)
    {
        $args = func_get_args();
        $extensions = [];
        foreach ($args as $className) {

            $obj = Util::initialize($className);

            if (!$obj instanceof iFormat)
                throw new Exception('Invalid format class; must implement ' .
                    'iFormat interface');

            foreach ($obj->getMIMEMap() as $mime => $extension) {
                if (!isset($this->formatMap[$extension]))
                    $this->formatMap[$extension] = $className;
                if (!isset($this->formatMap[$mime]))
                    $this->formatMap[$mime] = $className;
                $extensions[".$extension"] = true;
            }
        }
        $this->formatMap['default'] = $args[0];
        $this->formatMap['extensions'] = array_keys($extensions);
    }

    /**
     * Add api classes through this method.
     *
     * All the public methods that do not start with _ (underscore)
     * will be will be exposed as the public api by default.
     *
     * All the protected methods that do not start with _ (underscore)
     * will exposed as protected api which will require authentication
     *
     * @param string $className
     *            name of the service class
     * @param string $resourcePath
     *            optional url prefix for mapping, uses
     *            lowercase version of the class name when not specified
     *
     * @throws Exception when supplied with invalid class name
     */
    public function addAPIClass($className, $resourcePath = null)
    {
        $this->loadCache();
        if (isset(Util::$classAliases[$className])) {
            $className = Util::$classAliases[$className];
        }
        if (!$this->cached) {
            $foundClass = [];
            if (class_exists($className)) {
                $foundClass[$className] = $className;
            }

            //versioned api
            if (false !== ($index = strrpos($className, '\\'))) {
                $name = substr($className, 0, $index)
                    . '\\v{$version}' . substr($className, $index);
            } else if (false !== ($index = strrpos($className, '_'))) {
                $name = substr($className, 0, $index)
                    . '_v{$version}' . substr($className, $index);
            } else {
                $name = 'v{$version}\\' . $className;
            }

            for ($version = $this->apiMinimumVersion;
                 $version <= $this->apiVersion;
                 $version++) {

                $versionedClassName = str_replace('{$version}', $version,
                    $name);
                if (class_exists($versionedClassName)) {
                    $this->generateMap($versionedClassName,
                        Util::getResourcePath(
                            $className,
                            $resourcePath,
                            "v{$version}/"
                        )
                    );
                    $foundClass[$className] = $versionedClassName;
                } elseif (isset($foundClass[$className])) {
                    $this->generateMap($foundClass[$className],
                        Util::getResourcePath(
                            $className,
                            $resourcePath,
                            "v{$version}/"
                        )
                    );
                }
            }

        }
    }

    /**
     * Classes implementing iFilter interface can be added for filtering out
     * the api consumers.
     *
     * It can be used for rate limiting based on usage from a specific ip
     * address or filter by country, device etc.
     *
     * @param $className
     */
    public function addFilterClass($className)
    {
        $this->filterClasses[] = $className;
    }

    /**
     * protected methods will need at least one authentication class to be set
     * in order to allow that method to be executed
     *
     * @param string $className
     *            of the authentication class
     * @param string $resourcePath
     *            optional url prefix for mapping
     */
    public function addAuthenticationClass($className, $resourcePath = null)
    {
        $this->authClasses[] = $className;
        $this->addAPIClass($className, $resourcePath);
    }

    /**
     * Add class for custom error handling
     *
     * @param string $className
     *            of the error handling class
     */
    public function addErrorClass($className)
    {
        $this->errorClasses[] = $className;
    }

    /**
     * Convenience method to respond with an error message.
     *
     * @param int    $statusCode   http error code
     * @param string $errorMessage optional custom error message
     *
     * @return null
     */
    public function handleError($statusCode, $errorMessage = null)
    {
        $method = "handle$statusCode";
        $handled = false;
        foreach ($this->errorClasses as $className) {
            if (method_exists($className, $method)) {
                $obj = Util::initialize($className);
                $obj->$method ();
                $handled = true;
            }
        }
        if ($handled)
            return null;
        if (!isset($this->responseFormat))
            $this->responseFormat = Util::initialize('JsonFormat');
        $this->sendData(null, $statusCode, $errorMessage);
    }

    /**
     * An initialize function to allow use of the restler error generation
     * functions for pre-processing and pre-routing of requests.
     */
    public function init()
    {
        if (Defaults::$crossOriginResourceSharing
            && $this->requestMethod == 'OPTIONS'
        ) {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header('Access-Control-Allow-Methods: '
                    . Defaults::$accessControlAllowMethods);

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header('Access-Control-Allow-Headers: '
                    . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
            exit(0);
        }
        if (empty($this->formatMap)) {
            $this->setSupportedFormats('JsonFormat');
        }
        $this->url = $this->getPath();
        $this->requestMethod = Util::getRequestMethod();
        $this->responseFormat = $this->getResponseFormat();
        $this->requestFormat = $this->getRequestFormat();
        $this->responseFormat->restler = $this;
        if (is_null($this->requestFormat)) {
            $this->requestFormat = $this->responseFormat;
        } else {
            $this->requestFormat->restler = $this;
        }
        if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $found = false;
            $charList = Util::sortByPriority($_SERVER['HTTP_ACCEPT_CHARSET']);
            foreach ($charList as $charset => $quality) {
                if (in_array($charset, Defaults::$supportedCharsets)) {
                    $found = true;
                    Defaults::$charset = $charset;
                    break;
                }
            }
            if (!$found) {
                if (str_contains($_SERVER['HTTP_ACCEPT_CHARSET'], '*')) {
                    //use default charset
                } else {
                    $this->handleError(406, 'Content negotiation failed. '
                        . "Requested charset is not supported");
                }
            }
        }
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $found = false;
            $langList = Util::sortByPriority($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($langList as $lang => $quality) {
                foreach (Defaults::$supportedLanguages as $supported) {
                    if (strcasecmp($supported, $lang) == 0) {
                        $found = true;
                        Defaults::$language = $supported;
                        break 2;
                    }
                }
            }
            if (!$found) {
                if (str_contains($_SERVER['HTTP_ACCEPT_LANGUAGE'], '*')) {
                    //use default language
                } else {
                    //ignore
                }
            }
        }
    }

    /**
     * Main function for processing the api request
     * and return the response
     *
     * @throws Exception     when the api service class is missing
     * @throws RestException to send error response
     */
    public function handle()
    {
        try {
            $this->init();
            foreach ($this->filterClasses as $filterClass) {
                /**
                 * @var iFilter
                 */
                $filterObj = Util::initialize($filterClass);
                if (!$filterObj instanceof iFilter) {
                    throw new RestException (
                        500, 'Filter Class ' .
                        'should implement iFilter');
                } else if (!($ok = $filterObj->__isAllowed())) {
                    if (is_null($ok)
                        && $filterObj instanceof iUseAuthentication
                    ) {
                        //handle at authentication stage
                        $this->filterObjects[] = $filterObj;
                        continue;
                    }
                    throw new RestException(403); //Forbidden
                }
            }
            Util::initialize($this->requestFormat);

            $this->requestData = $this->getRequestData();

            //parse defaults
            foreach ($_GET as $key => $value) {
                if (isset(Defaults::$aliases[$key])) {
                    $_GET[Defaults::$aliases[$key]] = $value;
                    unset($_GET[$key]);
                    $key = Defaults::$aliases[$key];
                }
                if (in_array($key, Defaults::$overridables)) {
                    Defaults::setProperty($key, $value);
                }
            }

            $this->apiMethodInfo = $o = $this->mapUrlToMethod();
            if (isset($o->metadata)) {
                foreach (Defaults::$fromComments as $key => $defaultsKey) {
                    if (array_key_exists($key, $o->metadata)) {
                        $value = $o->metadata[$key];
                        Defaults::setProperty($defaultsKey, $value);
                    }
                }
            }

            $result = null;
            if (!isset($o->className)) {
                $this->handleError(404);
            } else {
                try {
                    $accessLevel = max(Defaults::$apiAccessLevel,
                        $o->accessLevel);
                    if ($accessLevel || count($this->filterObjects)) {
                        if (!count($this->authClasses)) {
                            throw new RestException(401);
                        }
                        foreach ($this->authClasses as $authClass) {
                            $authObj = Util::initialize(
                                $authClass, $o->metadata
                            );
                            if (!method_exists($authObj,
                                Defaults::$authenticationMethod)
                            ) {
                                throw new RestException (
                                    500, 'Authentication Class ' .
                                    'should implement iAuthenticate');
                            } elseif (
                                !$authObj->{Defaults::$authenticationMethod}()
                            ) {
                                throw new RestException(401);
                            }
                        }
                        $this->authenticated = true;
                    }
                } catch (RestException $e) {
                    if ($accessLevel > 1) { //when it is not a hybrid api
                        $this->handleError($e->getCode(), $e->getMessage());
                    } else {
                        $this->authenticated = false;
                    }
                }
                try {
                    foreach ($this->filterObjects as $filterObj) {
                        Util::initialize($filterObj, $o->metadata);
                    }
                    $preProcess = '_' . $this->requestFormat->getExtension() .
                        '_' . $o->methodName;
                    $this->apiMethod = $o->methodName;
                    $object = $this->apiClassInstance = null;
                    // TODO:check if the api version requested is allowed by class
                    if (Defaults::$autoValidationEnabled) {
                        foreach ($o->metadata['param'] as $index => $param) {
                            $info = & $param [CommentParser::$embeddedDataName];
                            if (!isset ($info['validate'])
                                || $info['validate'] != false
                            ) {
                                if (isset($info['method'])) {
                                    if (!isset($object)) {
                                        $object = $this->apiClassInstance
                                            = Util::initialize($o->className);
                                    }
                                    $info ['apiClassInstance'] = $object;
                                }
                                //convert to instance of ValidationInfo
                                $info = new ValidationInfo($param);
                                $valid = Validator::validate(
                                    $o->parameters[$index], $info);
                                $o->parameters[$index] = $valid;
                            }
                        }
                    }
                    if (!isset($object)) {
                        $object = $this->apiClassInstance
                            = Util::initialize($o->className);
                    }
                    if (method_exists($o->className, $preProcess)) {
                        call_user_func_array([$object, $preProcess], $o->parameters);
                    }
                    switch ($accessLevel) {
                        case 3 : //protected method
                            $reflectionMethod = new ReflectionMethod(
                                $object,
                                $o->methodName
                            );
                            $reflectionMethod->setAccessible(true);
                            $result = $reflectionMethod->invokeArgs(
                                $object,
                                $o->parameters
                            );
                            break;
                        default :
                            $result = call_user_func_array([$object, $o->methodName], $o->parameters);
                    }
                } catch (RestException $e) {
                    $this->handleError($e->getCode(), $e->getMessage());
                }
            }
            $this->sendData($result);
        } catch (RestException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        } catch (\Exception $e) {
            $this->log[] = $e->getMessage();
            if ($this->productionMode) {
                $this->handleError(500);
            } else {
                $this->handleError(500, $e->getMessage());
            }
        }
    }

    /**
     * Encodes the response in the preferred format and sends back.
     *
     * @param mixed       $data array or scalar value or iValueObject or null
     * @param int         $statusCode
     * @param string|null $statusMessage
     */
    public function sendData(mixed $data, $statusCode = 0, $statusMessage = null)
    {
        //$this->log []= ob_get_clean ();
        //only GET method should be cached if allowed by API developer
        $expires = $this->requestMethod == 'GET' ? Defaults::$headerExpires : 0;
        $cacheControl = Defaults::$headerCacheControl[0];
        if ($expires > 0) {
            $cacheControl = $this->apiMethodInfo->accessLevel
                ? 'private, ' : 'public, ';
            $cacheControl .= end(Defaults::$headerCacheControl);
            $cacheControl = str_replace('{expires}', $expires, $cacheControl);
            $expires = gmdate('D, d M Y H:i:s \G\M\T', time() + $expires);
        }
        @header('Cache-Control: ' . $cacheControl);
        @header('Expires: ' . $expires);
        @header('X-Powered-By: Luracast Restler v' . Restler::VERSION);

        if (Defaults::$crossOriginResourceSharing
            && isset($_SERVER['HTTP_ORIGIN'])
        ) {
            header('Access-Control-Allow-Origin: ' .
                    (Defaults::$accessControlAllowOrigin == '*'
                        ? $_SERVER['HTTP_ORIGIN']
                        : Defaults::$accessControlAllowOrigin)
            );
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        if (isset($this->apiMethodInfo->metadata['header'])) {
            foreach ($this->apiMethodInfo->metadata['header'] as $header)
                @header($header, true);
        }

        /**
         *
         * @var iRespond DefaultResponder
         */
        $responder = Util::initialize(
            Defaults::$responderClass, $this->apiMethodInfo->metadata ?? null
        );
        $this->responseFormat->setCharset(Defaults::$charset);
        $charset = $this->responseFormat->getCharset()
            ? : Defaults::$charset;
        @header('Content-Type: ' . (
            Defaults::$useVendorMIMEVersioning
                ? 'application/vnd.'
                . Defaults::$apiVendor
                . "-v{$this->requestedApiVersion}"
                . '+' . $this->responseFormat->getExtension()
                : $this->responseFormat->getMIME())
                . '; charset=' . $charset
        );
        @header('Content-Language: ' . Defaults::$language);
        if ($statusCode == 0) {
            if (isset($this->apiMethodInfo->metadata['status'])) {
                $this->setStatus($this->apiMethodInfo->metadata['status']);
            }
            $data = $responder->formatResponse($data);
            $data = $this->responseFormat->encode($data,
                !$this->productionMode);
            $postProcess = '_' . $this->apiMethod . '_' .
                $this->responseFormat->getExtension();
            if (isset($this->apiClassInstance)
                && method_exists(
                    $this->apiClassInstance,
                    $postProcess
                )
            ) {
                $data = call_user_func([$this->apiClassInstance, $postProcess], $data);
            }
        } else {
            if (isset(RestException::$codes[$statusCode])) {
                $message = RestException::$codes[$statusCode] .
                    (empty($statusMessage) ? '' : ': ' . $statusMessage);
            } else {
                trigger_error("Non standard http status codes [currently $statusCode] are discouraged", E_USER_WARNING);
                $message = $statusMessage;

            }
            $this->setStatus($statusCode);
            $data = $this->responseFormat->encode(
                $responder->formatError($statusCode, $message),
                !$this->productionMode);
        }
        //handle throttling
        if (Defaults::$throttle) {
            $elapsed = time() - $this->startTime;
            if (Defaults::$throttle / 1e3 > $elapsed) {
                usleep(1e6 * (Defaults::$throttle / 1e3 - $elapsed));
            }
        }
        die($data);
    }

    /**
     * Sets the HTTP response status
     *
     * @param int $code
     *            response code
     */
    public function setStatus($code)
    {
        if (Defaults::$suppressResponseCode) {
            $code = 200;
        }
        @header("{$_SERVER['SERVER_PROTOCOL']} $code " .
            RestException::$codes[$code]);
    }

    /**
     * Magic method to expose some protected variables
     *
     * @param string $name name of the hidden property
     *
     * @return null|mixed
     */
    public function __get($name)
    {
        if ($name[0] == '_') {
            $hiddenProperty = substr($name, 1);
            if (isset($this->$hiddenProperty)) {
                return $this->$hiddenProperty;
            }
        }
        return null;
    }

    // ==================================================================
    //
    // Protected functions
    //
    // ------------------------------------------------------------------

    /**
     * Parses the request url and get the api path
     *
     * @return string api path
     */
    protected function getPath()
    {
        $fullPath = urldecode($_SERVER['REQUEST_URI']);
        $path = Util::removeCommonPath(
            $fullPath,
            $_SERVER['SCRIPT_NAME']
        );
        $baseUrl = isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
        if ($_SERVER['SERVER_PORT'] != '80') {
            $baseUrl .= $_SERVER['SERVER_NAME'] . ':'
                . $_SERVER['SERVER_PORT'];
        } else {
            $baseUrl .= $_SERVER['SERVER_NAME'];
        }
        $this->baseUrl = $baseUrl . rtrim(substr(
            $fullPath,
            0,
            strlen($fullPath) - strlen($path)
        ), '/');

        $path = preg_replace('/(\/*\?.*$)|(\/$)/', '', $path);
        $path = str_replace($this->formatMap['extensions'], '', $path);
        if (Defaults::$useUrlBasedVersioning
            && strlen($path) && $path[0] == 'v'
        ) {
            $version = intval(substr($path, 1));
            if ($version && $version <= $this->apiVersion) {
                $this->requestedApiVersion = $version;
                $path = explode('/', $path, 2);
                $path = $path[1];
            }
        } else {
            $this->requestedApiVersion = $this->apiMinimumVersion;
        }
        return $path;
    }

    /**
     * Parses the request to figure out format of the request data
     *
     * @return iFormat any class that implements iFormat
     * @example JsonFormat
     */
    protected function getRequestFormat()
    {
        $format = null;
        // check if client has sent any information on request format
        if (!empty($_SERVER['CONTENT_TYPE'])) {
            $mime = $_SERVER['CONTENT_TYPE'];
            if (false !== $pos = strpos($mime, ';')) {
                $mime = substr($mime, 0, $pos);
            }
            if ($mime == UrlEncodedFormat::MIME)
                $format = Util::initialize('UrlEncodedFormat');
            elseif (isset($this->formatMap[$mime])) {
                $format = Util::initialize($this->formatMap[$mime]);
                $format->setMIME($mime);
            } else {
                $this->handleError(403, "Content type `$mime` is not supported.");
                return null;
            }
        }
        return $format;
    }

    /**
     * Parses the request to figure out the best format for response.
     * Extension, if present, overrides the Accept header
     *
     * @return iFormat any class that implements iFormat
     * @example JsonFormat
     */
    protected function getResponseFormat()
    {
        // check if client has specified an extension
        /**
         *
         * @var iFormat
         */
        $format = null;
        $extensions = explode(
            '.',
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
        );
        while ($extensions) {
            $extension = array_pop($extensions);
            $extension = explode('/', $extension);
            $extension = array_shift($extension);
            if ($extension && isset($this->formatMap[$extension])) {
                $format = Util::initialize($this->formatMap[$extension]);
                $format->setExtension($extension);
                // echo "Extension $extension";
                return $format;
            }
        }
        // check if client has sent list of accepted data formats
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $acceptList = Util::sortByPriority($_SERVER['HTTP_ACCEPT']);
            foreach ($acceptList as $accept => $quality) {
                if (isset($this->formatMap[$accept])) {
                    $format = Util::initialize($this->formatMap[$accept]);
                    //TODO: check if the string verfication above is needed
                    $format->setMIME($accept);
                    //echo "MIME $accept";
                    // Tell cache content is based on Accept header
                    @header('Vary: Accept');

                    return $format;
                } elseif (false !== ($index = strrpos($accept, '+'))) {
                    $mime = substr($accept, 0, $index);
                    if (is_string(Defaults::$apiVendor)
                        && 0 === stripos($mime,
                            'application/vnd.'
                                . Defaults::$apiVendor . '-v')
                    ) {
                        $extension = substr($accept, $index + 1);
                        if (isset($this->formatMap[$extension])) {
                            //check the MIME and extract version
                            $version = intVal(substr($mime,
                                18 + strlen(Defaults::$apiVendor)));
                            if ($version > 0 && $version <= $this->apiVersion) {
                                $this->requestedApiVersion = $version;
                                $format = Util::initialize(
                                    $this->formatMap[$extension]
                                );
                                $format->setExtension($extension);
                                // echo "Extension $extension";
                                Defaults::$useVendorMIMEVersioning = true;
                                @header('Vary: Accept');

                                return $format;
                            }
                        }
                    }

                }
            }
        } else {
            // RFC 2616: If no Accept header field is
            // present, then it is assumed that the
            // client accepts all media types.
            $_SERVER['HTTP_ACCEPT'] = '*/*';
        }
        if (str_contains($_SERVER['HTTP_ACCEPT'], '*')) {
            if (str_contains($_SERVER['HTTP_ACCEPT'], 'application/*')) {
                $format = Util::initialize('JsonFormat');
            } elseif (str_contains($_SERVER['HTTP_ACCEPT'], 'text/*')) {
                $format = Util::initialize('XmlFormat');
            } elseif (str_contains($_SERVER['HTTP_ACCEPT'], '*/*')) {
                $format = Util::initialize($this->formatMap['default']);
            }
        }
        if (empty($format)) {
            // RFC 2616: If an Accept header field is present, and if the
            // server cannot send a response which is acceptable according to
            // the combined Accept field value, then the server SHOULD send
            // a 406 (not acceptable) response.
            $format = Util::initialize($this->formatMap['default']);
            $this->responseFormat = $format;
            $this->handleError(406, 'Content negotiation failed. '
                . 'Try \'' . $format->getMIME() . '\' instead.');
        } else {
            // Tell cache content is based at Accept header
            @header("Vary: Accept");
            return $format;
        }
    }

    /**
     * Parses the request data and returns it
     *
     * @return array php data
     */
    public function getRequestData()
    {
        if ($this->requestMethod == 'PUT'
            || $this->requestMethod == 'PATCH'
            || $this->requestMethod == 'POST'
        ) {
            if (!empty($this->requestData)) {
                return $this->requestData;
            }
            try {
                $r = file_get_contents('php://input');
                if (is_null($r)) {
                    return [];
                }
                $r = $this->requestFormat->decode($r);
                return is_null($r) ? [] : $r;
            } catch (RestException $e) {
                $this->handleError($e->getCode(), $e->getMessage());
            }
        }
        return [];
    }

    /**
     * Find the api method to execute for the requested Url
     *
     * @return ApiMethodInfo
     */
    public function mapUrlToMethod()
    {
        if (!is_array($this->requestData)) {
            $this->requestData = [Defaults::$fullRequestDataName => $this->requestData];
            $this->requestData += $_GET;
            $params = $this->requestData;
        } else {
            $this->requestData += $_GET;
            $params = [Defaults::$fullRequestDataName => $this->requestData];
            $params = $this->requestData + $params;

        }
        $currentUrl = 'v' . $this->requestedApiVersion;
        if (!empty($this->url))
            $currentUrl .= '/' . $this->url;
        return Routes::find($currentUrl, $this->requestMethod, $params);
    }

    /**
     * Load routes from cache
     *
     * @return null
     */
    protected function loadCache()
    {
        if ($this->cached !== null)
            return null;
        $this->cached = false;
        if ($this->productionMode) {
            $routes = $this->cache->get('routes');
            if (isset($routes) && is_array($routes)) {
                Routes::fromArray($routes);
                $this->cached = true;
            }
        }
    }

    /**
     * Generates cacheable url to method mapping
     *
     * @param string $className
     * @param string $resourcePath
     */
    protected function generateMap($className, $resourcePath = '')
    {
        Routes::addAPIClass($className, $resourcePath);
    }
}

