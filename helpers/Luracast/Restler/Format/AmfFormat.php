<?php
namespace Luracast\Restler\Format;

/**
 * AMF Binary Format for Restler Framework.
 * Native format supported by Adobe Flash and Adobe AIR
 * @category   Framework
 * @package    Restler
 * @subpackage format
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc4
 */
use ZendAmf\Parser\OutputStream;
use ZendAmf\Parser\InputStream;
use ZendAmf\Parser\Amf3\Serializer;
use ZendAmf\Parser\Amf3\Deserializer;

class AmfFormat extends Format
{
    public const MIME = 'application/x-amf';
    public const EXTENSION = 'amf';

    public function encode($data, $humanReadable = false)
    {

        $stream = new OutputStream();
        $serializer = new Serializer($stream);
        $serializer->writeTypeMarker($data);

        return $stream->getStream();
    }

    public function decode($data)
    {
        $stream = new InputStream(substr($data, 1));
        $deserializer = new Deserializer($stream);

        return $deserializer->readTypeMarker();
    }
}

