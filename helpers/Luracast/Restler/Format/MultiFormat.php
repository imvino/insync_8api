<?php
namespace Luracast\Restler\Format;

use Luracast\Restler\Restler;
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
abstract class MultiFormat implements iFormat, \Stringable
{
    /**
     * override in the extending class
     */
    public const MIME = 'text/plain,text/html';
    /**
     * override in the extending class
     */
    public const EXTENSION = 'txt,html';
    /**
     * @var string charset encoding defaults to UTF8
     */
    protected $charset='utf-8';
    public static $mime;
    public static $extension;
    /**
     * Injected at runtime
     *
     * @var Restler
     */
    public $restler;
    /**
     * Get MIME type => Extension mappings as an associative array
     *
     * @return array list of mime strings for the format
     * @example array('application/json'=>'json');
     */
    public function getMIMEMap()
    {
        $extensions = explode(',',static::EXTENSION);
        $mimes = explode(',',static::MIME);
        $count = max(count($extensions), count($mimes));
        $extensions += array_fill(0, $count, end($extensions));
        $mimes += array_fill(0, $count, end($mimes));
        return array_combine($mimes,$extensions);
    }
    /**
     * Set the selected MIME type
     *
     * @param string $mime
     *            MIME type
     */
    public function setMIME($mime)
    {
        static::$mime = $mime;
    }
    /**
     * Content-Type field of the HTTP header can send a charset
     * parameter in the HTTP header to specify the character
     * encoding of the document.
     * This information is passed
     * here so that Format class can encode data accordingly
     * Format class may choose to ignore this and use its
     * default character set.
     *
     * @param string $charset
     *            Example utf-8
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }
    /**
     * Content-Type accepted by the Format class
     *
     * @return string $charset Example utf-8
     */
    public function getCharset()
    {
        return $this->charset;
    }
    /**
     * Get selected MIME type
     */
    public function getMIME()
    {
        return static::$mime;
    }
    /**
     * Set the selected file extension
     *
     * @param string $extension
     *            file extension
     */
    public function setExtension($extension)
    {
        static::$extension = $extension;
    }
    /**
     * Get the selected file extension
     *
     * @return string file extension
     */
    public function getExtension()
    {
        return static::$extension;
    }
    public function __toString(): string
    {
        return $this->getExtension();
    }
}

