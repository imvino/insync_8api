<?php
namespace Luracast\Restler\Format;

/**
 * YAML Format for Restler Framework
 *
 * @category   Framework
 * @package    Restler
 * @subpackage format
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc4
 */
use Symfony\Component\Yaml\Yaml;
use Luracast\Restler\Data\Object;

class YamlFormat extends Format
{
    public const MIME = 'text/plain';
    public const EXTENSION = 'yaml';

    public function encode($data, $humanReadable = false)
    {
//      require_once 'sfyaml.php';
        return @Yaml::dump(Object::toArray($data));
    }

    public function decode($data)
    {
//      require_once 'sfyaml.php';
        return Yaml::parse($data);
    }
}

