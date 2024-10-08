<?php
namespace Luracast\Restler\Format;

use Luracast\Restler\Restler;
/**
 * Abstract class to implement common methods of iFormat
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc4
 */
abstract class Format implements iFormat, \Stringable
{
    /**
     * override in the extending class
     */
    public const MIME = 'text/plain';
    /**
     * override in the extending class
     */
    public const EXTENSION = 'txt';
    /**
     * @var string charset encoding defaults to UTF8
     */
    protected $charset='utf-8';
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
        return [static::MIME =>  static::EXTENSION];
    }
    /**
     * Set the selected MIME type
     *
     * @param string $mime
     *            MIME type
     */
    public function setMIME($mime)
    {
        //do nothing
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
        return static::MIME;
    }
    /**
     * Set the selected file extension
     *
     * @param string $extension
     *            file extension
     */
    public function setExtension($extension)
    {
        //do nothing;
    }
    /**
     * Get the selected file extension
     *
     * @return string file extension
     */
    public function getExtension()
    {
        return static::EXTENSION;
    }
    public function __toString(): string
    {
        return $this->getExtension();
    }
}

