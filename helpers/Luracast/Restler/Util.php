<?php
namespace Luracast\Restler;

use Luracast\Restler\Format\AmfFormat;
use Luracast\Restler\Format\JsFormat;
use Luracast\Restler\Format\JsonFormat;
use Luracast\Restler\Format\HtmlFormat;
use Luracast\Restler\Format\PlistFormat;
use Luracast\Restler\Format\UrlEncodedFormat;
use Luracast\Restler\Format\XmlFormat;
use Luracast\Restler\Format\YamlFormat;
use Luracast\Restler\Filter\RateLimit;
use Luracast\Restler\Data\Object;
/**
 * Describe the purpose of this class/interface/trait
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc4
 */
class Util
{
    /**
     * @var Restler instance injected at runtime
     */
    public static $restler;

    public static $classAliases = [
        //Format classes
        'AmfFormat' => AmfFormat::class,
        'JsFormat' => JsFormat::class,
        'JsonFormat' => JsonFormat::class,
        'HtmlFormat' => HtmlFormat::class,
        'PlistFormat' => PlistFormat::class,
        'UrlEncodedFormat' => UrlEncodedFormat::class,
        'XmlFormat' => XmlFormat::class,
        'YamlFormat' => YamlFormat::class,
        //Filter classes
        'RateLimit' => RateLimit::class,
        //API classes
        'Resources' => Resources::class,
        //Cache classes
        'HumanReadableCache' => HumanReadableCache::class,
        //Utility classes
        'Object' => Object::class,
    ];

    /**
     * verify if the given data type string is scalar or not
     *
     * @static
     *
     * @param string $type data type as string
     *
     * @return bool true or false
     */
    public static function isObjectOrArray($type)
    {
        return !(boolean)strpos('|bool|boolean|int|float|string|', $type);
    }

    public static function getResourcePath($className,
                                           $resourcePath = null,
                                           $prefix = '')
    {
        if (is_null($resourcePath)) {
            if (Defaults::$autoRoutingEnabled) {
                $resourcePath = strtolower($className);
                if (false !== ($index = strrpos($className, '\\')))
                    $resourcePath = substr($resourcePath, $index + 1);
                if (false !== ($index = strrpos($resourcePath, '_')))
                    $resourcePath = substr($resourcePath, $index + 1);
            } else {
                $resourcePath = '';
            }
        } else
            $resourcePath = trim($resourcePath, '/');
        if (strlen($resourcePath) > 0)
            $resourcePath .= '/';
        return $prefix . $resourcePath;
    }

    /**
     * Compare two strings and remove the common
     * sub string from the first string and return it
     *
     * @static
     *
     * @param string $fromPath
     * @param string $usingPath
     * @param string $char
     *            optional, set it as
     *            blank string for char by char comparison
     *
     * @return string
     */
    public static function removeCommonPath($fromPath, $usingPath, $char = '/')
    {
        if (empty($fromPath))
            return '';
        $fromPath = explode($char, $fromPath);
        $usingPath = explode($char, $usingPath);
        while (count($usingPath)) {
            if ($fromPath[0] == $usingPath[0]) {
                array_shift($fromPath);
            } else {
                break;
            }
            array_shift($usingPath);
        }
        return implode($char, $fromPath);
    }

    /**
     * Parses the request to figure out the http request type
     *
     * @static
     *
     * @return string which will be one of the following
     *        [GET, POST, PUT, PATCH, DELETE]
     * @example GET
     */
    public static function getRequestMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        } elseif (isset($_GET['method'])) {
            // support for exceptional clients who can't set the header
            $m = strtoupper($_GET['method']);
            if ($m == 'PUT' || $m == 'DELETE' ||
                $m == 'POST' || $m == 'PATCH'
            ) {
                $method = $m;
            }
        }
        // support for HEAD request
        if ($method == 'HEAD') {
            $method = 'GET';
        }
        return $method;
    }

    /**
     * Pass any content negotiation header such as Accept,
     * Accept-Language to break it up and sort the resulting array by
     * the order of negotiation.
     *
     * @static
     *
     * @param string $accept header value
     *
     * @return array sorted by the priority
     */
    public static function sortByPriority($accept)
    {
        $acceptList = [];
        $accepts = explode(',', strtolower($accept));
        if (!is_array($accepts)) {
            $accepts = [$accepts];
        }
        foreach ($accepts as $pos => $accept) {
            $parts = explode(';q=', trim($accept));
            $type = array_shift($parts);
            $quality = count($parts) ?
                floatval(array_shift($parts)) :
                (1000 - $pos) / 1000;
            $acceptList[$type] = $quality;
        }
        arsort($acceptList);
        return $acceptList;
    }

    /**
     * Apply static and non-static properties for the instance of the given
     * class name using the method information metadata annotation provided,
     * creating new instance when the given instance is null
     *
     * @static
     *
     * @param string      $classNameOrInstance name or instance of the class
     *                                         to apply properties to
     * @param array       $metadata            properties as key value pairs
     *
     * @throws RestException
     * @internal param null|object $instance new instance is crated if set to null
     *
     * @return object instance of the specified class with properties applied
     */
    public static function initialize($classNameOrInstance, array $metadata = null)
    {
        if (is_object($classNameOrInstance)) {
            $instance = $classNameOrInstance;
            $instance->restler = self::$restler;
            $className = $instance::class;
        } else {
            $className = $classNameOrInstance;
            if (isset(self::$classAliases[$classNameOrInstance])) {
                $classNameOrInstance = self::$classAliases[$classNameOrInstance];
            }
            if (!class_exists($classNameOrInstance)) {
                throw new RestException(500, "Class '$classNameOrInstance' not found");
            }
            $instance = new $classNameOrInstance();
            $instance->restler = self::$restler;
        }
        if (
            isset($metadata['class'][$className]
            [CommentParser::$embeddedDataName])
        ) {
            $properties = $metadata['class'][$className]
            [CommentParser::$embeddedDataName];

            $objectVars = get_object_vars($instance);

            if (is_array($properties)) {
                foreach ($properties as $property => $value) {
                    if (property_exists($className, $property)) {
                        //if not a static property
                        array_key_exists($property, $objectVars)
                            ? $instance->{$property} = $value
                            : $instance::$$property = $value;
                    }
                }
            }
        }

        if ($instance instanceof iUseAuthentication) {
            $instance->__setAuthenticationStatus
                (self::$restler->_authenticated);
        }

        return $instance;
    }
}

