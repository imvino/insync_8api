<?php
namespace Luracast\Restler;

use Luracast\Restler\Format\iFormat;
/**
 * Parses the PHPDoc comments for metadata. Inspired by `Documentor` code base.
 *
 * @category   Framework
 * @package    Restler
 * @subpackage Helper
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc4
 */
class CommentParser
{
    /**
     * name for the embedded data
     *
     * @var string
     */
    public static $embeddedDataName = 'properties';
    /**
     * Regular Expression pattern for finding the embedded data and extract
     * the inner information. It is used with preg_match.
     *
     * @var string
     */
    public static $embeddedDataPattern
        = '/```(\w*)[\s]*(([^`]*`{0,2}[^`]+)*)```/ms';
    /**
     * Pattern will have groups for the inner details of embedded data
     * this index is used to locate the data portion.
     *
     * @var int
     */
    public static $embeddedDataIndex = 2;
    /**
     * Delimiter used to split the array data.
     *
     * When the name portion is of the embedded data is blank auto detection
     * will be used and if URLEncodedFormat is detected as the data format
     * the character specified will be used as the delimiter to find split
     * array data.
     *
     * @var string
     */
    public static $arrayDelimiter = ',';

    /**
     * character sequence used to escape \@
     */
    public const escapedAtChar = '\\@';

    /**
     * character sequence used to escape end of comment
     */
    public const escapedCommendEnd = '{@*}';

    /**
     * Instance of Restler class injected at runtime.
     *
     * @var Restler
     */
    public $restler;
    /**
     * Comment information is parsed and stored in to this array.
     *
     * @var array
     */
    private $_data = [];

    /**
     * Parse the comment and extract the data.
     *
     * @static
     *
     * @param      $comment
     * @param bool $isPhpDoc
     *
     * @return array associative array with the extracted values
     */
    public static function parse($comment, $isPhpDoc = true)
    {
        $p = new self();
        if (empty($comment)) {
            return $p->_data;
        }

        if ($isPhpDoc) {
            $comment = self::removeCommentTags($comment);
        }

        $p->extractData($comment);
        return $p->_data;

    }

    /**
     * Removes the comment tags from each line of the comment.
     *
     * @static
     *
     * @param $comment PhpDoc style comment
     *
     * @return string comments with out the tags
     */
    public static function removeCommentTags($comment)
    {
        $pattern = '/(^\/\*\*)|(^\s*\**[ \/]?)|\s(?=@)|\s\*\//m';
        return preg_replace($pattern, '', $comment);
    }

    /**
     * Extracts description and long description, uses other methods to get
     * parameters.
     *
     * @param $comment
     *
     * @return array
     */
    private function extractData($comment)
    {
        //to use @ as part of comment we need to
        $comment = str_replace(
            [self::escapedCommendEnd, self::escapedAtChar],
            ['*/', '@'],
            $comment);

        $description = [];
        $longDescription = [];
        $params = [];

        $mode = 0; // extract short description;
        $comments = preg_split("/(\r?\n)/", $comment);
        // remove first blank line;
        array_shift($comments);
        $addNewline = false;
        foreach ($comments as $line) {
            $line = trim($line);
            $newParam = false;
            if (empty ($line)) {
                if ($mode == 0) {
                    $mode++;
                } else {
                    $addNewline = true;
                }
                continue;
            } elseif ($line[0] == '@') {
                $mode = 2;
                $newParam = true;
            }
            switch ($mode) {
                case 0 :
                    $description[] = $line;
                    if (count($description) > 3) {
                        // if more than 3 lines take only first line
                        $longDescription = $description;
                        $description[] = array_shift($longDescription);
                        $mode = 1;
                    } elseif (str_ends_with($line, '.')) {
                        $mode = 1;
                    }
                    break;
                case 1 :
                    if ($addNewline) {
                        $line = ' ' . $line;
                    }
                    $longDescription[] = $line;
                    break;
                case 2 :
                    $newParam
                        ? $params[] = $line
                        : $params[count($params) - 1] .= ' ' . $line;
            }
            $addNewline = false;
        }
        $description = implode(' ', $description);
        $longDescription = implode(' ', $longDescription);
        $description = preg_replace('/\s+/ms', ' ', $description);
        $longDescription = preg_replace('/\s+/ms', ' ', $longDescription);
        [$description, $d1]
            = $this->parseEmbeddedData($description);
        [$longDescription, $d2]
            = $this->parseEmbeddedData($longDescription);
        $this->_data = compact('description', 'longDescription');
        $d2 += $d1;
        if (!empty($d2)) {
            $this->_data[self::$embeddedDataName] = $d2;
        }
        foreach ($params as $key => $line) {
            [, $param, $value] = preg_split('/\@|\s/', $line, 3)
                + ['', '', ''];
            [$value, $embedded] = $this->parseEmbeddedData($value);
            $value = array_filter(preg_split('/\s+/ms', $value));
            $this->parseParam($param, $value, $embedded);
        }
        return $this->_data;
    }

    /**
     * Parse parameters that begin with (at)
     *
     * @param       $param
     * @param array $value
     * @param array $embedded
     */
    private function parseParam($param, array $value, array $embedded)
    {
        $data = &$this->_data;
        $allowMultiple = false;
        switch ($param) {
            case 'param' :
                $value = $this->formatParam($value);
                $allowMultiple = true;
                break;
            case 'return' :
                $value = $this->formatReturn($value);
                break;
            case 'class' :
                $data = &$data[$param];
                [$param, $value] = $this->formatClass($value);
                break;
            case 'access' :
                $value = $value [0];
                break;
            case 'expires' :
            case 'status' :
                $value = intval($value[0]);
                break;
            case 'throws' :
                $value = $this->formatThrows($value);
                $allowMultiple = true;
                break;
            case 'header' :
                $allowMultiple = true;
                break;
            case 'author':
                $value = $this->formatAuthor($value);
                $allowMultiple = true;
                break;
            case 'link':
            case 'example':
            case 'todo':
                $allowMultiple = true;
            //don't break, continue with code for default:
            default :
                $value = implode(' ', $value);
        }
        if (!empty($embedded)) {
            if (is_string($value)) {
                $value = ['description' => $value];
            }
            $value[self::$embeddedDataName] = $embedded;
        }
        if (empty ($data[$param])) {
            if ($allowMultiple) {
                $data[$param] = [$value];
            } else {
                $data[$param] = $value;
            }
        } elseif ($allowMultiple) {
            $data[$param][] = $value;
        } elseif ($param == 'param') {
            $arr = [$data[$param], $value];
            $data[$param] = $arr;
        } else {
            if (!is_string($value) && isset($value[self::$embeddedDataName])
                && isset($data[$param][self::$embeddedDataName])
            ) {
                $value[self::$embeddedDataName]
                    += $data[$param][self::$embeddedDataName];
            }
            $data[$param] = $value + $data[$param];
        }
    }

    /**
     * Parses the inline php doc comments and embedded data.
     *
     * @param $subject
     *
     * @return array
     * @throws Exception
     */
    private function parseEmbeddedData($subject)
    {
        $data = [];

        while (preg_match('/{@(\w+)\s([^}]*)}/ms', $subject, $matches)) {
            $subject = str_replace($matches[0], '', $subject);
            if ($matches[2] == 'true' || $matches[2] == 'false') {
                $matches[2] = $matches[2] == 'true';
            }
            if ($matches[1] != 'pattern'
                && str_contains($matches[2], static::$arrayDelimiter)
            ) {
                $matches[2] = explode(static::$arrayDelimiter, $matches[2]);
            }
            $data[$matches[1]] = $matches[2];
        }

        while (preg_match(self::$embeddedDataPattern, $subject, $matches)) {
            $subject = str_replace($matches[0], '', $subject);
            $str = $matches[self::$embeddedDataIndex];
            if (isset ($this->restler)
                && self::$embeddedDataIndex > 1
                && !empty ($matches[1])
            ) {
                $extension = $matches[1];
                if (isset ($this->restler->formatMap[$extension])) {
                    /**
                     * @var iFormat
                     */
                    $format = $this->restler->formatMap[$extension];
                    $format = new $format();
                    $data = $format->decode($str);
                }
            } else { // auto detect
                if ($str[0] == '{') {
                    $d = json_decode($str, true);
                    if (json_last_error() != JSON_ERROR_NONE) {
                        throw new Exception('Error parsing embedded JSON data'
                            . " $str");
                    }
                    $data = $d + $data;
                } else {
                    parse_str($str, $d);
                    //clean up
                    $d = array_filter($d);
                    foreach ($d as $key => $val) {
                        $kt = trim($key);
                        if ($kt != $key) {
                            unset($d[$key]);
                            $key = $kt;
                            $d[$key] = $val;
                        }
                        if (is_string($val)) {
                            if ($val == 'true' || $val == 'false') {
                                $d[$key] = $val == 'true' ? true : false;
                            } else {
                                $val = explode(self::$arrayDelimiter, $val);
                                if (count($val) > 1) {
                                    $d[$key] = $val;
                                } else {
                                    $d[$key] =
                                        preg_replace('/\s+/ms', ' ',
                                            $d[$key]);
                                }
                            }
                        }
                    }
                    $data = $d + $data;
                }
            }
        }
        return [$subject, $data];
    }

    private function formatThrows(array $value)
    {
        $r = ['exception' => array_shift($value)];
        $r['code'] = count($value) && is_numeric($value[0])
            ? intval(array_shift($value)) : 500;
        $reason = implode(' ', $value);
        $r['reason'] = empty($reason) ? '' : $reason;
        return $r;
    }

    private function formatClass(array $value)
    {
        $param = array_shift($value);

        if (empty($param)) {
            $param = 'Unknown';
        }
        $value = implode(' ', $value);
        return [$param, ['description' => $value]];
    }

    private function formatAuthor(array $value)
    {
        $r = [];
        $email = end($value);
        if ($email[0] == '<') {
            $email = substr($email, 1, -1);
            array_pop($value);
            $r['email'] = $email;
        }
        $r['name'] = implode(' ', $value);
        return $r;
    }

    private function formatReturn(array $value)
    {
        $data = explode('|', array_shift($value));
        $r = ['type' => count($data) == 1 ? $data[0] : $data];
        $r['description'] = implode(' ', $value);
        return $r;
    }

    private function formatParam(array $value)
    {
        $r = [];
        $data = array_shift($value);
        if (empty($data)) {
            $r['type'] = 'mixed';
        } elseif ($data[0] == '$') {
            $r['name'] = substr($data, 1);
            $r['type'] = 'mixed';
        } else {
            $data = explode('|', $data);
            $r['type'] = count($data) == 1 ? $data[0] : $data;

            $data = array_shift($value);
            if (!empty($data) && $data[0] == '$') {
                $r['name'] = substr($data, 1);
            }
        }
        if ($value) {
            $r['description'] = implode(' ', $value);
        }
        return $r;
    }
}

