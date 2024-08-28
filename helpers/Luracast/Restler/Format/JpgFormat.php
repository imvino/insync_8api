<?php

namespace Luracast\Restler\Format;

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
class JpgFormat extends Format {

	public const MIME = 'image/jpeg';
	public const EXTENSION = 'jpg';

	public function encode($data, $humanReadable = false) {
		if (isset($data) && is_string($data) && strlen($data) > 4 && str_starts_with($data, "\xff\xd8\xff\xe0")) {
			return $data;
		} else {
			if ($humanReadable)
			{
				ob_start();
				print_r($data);
				$text = ob_get_clean();
			}
			else
			{
				$text = (string)$data;
			}

			// Set font size
			$font_size = 3;

			$ts = explode("\n", $text);
			$max_width = 0;
			foreach ($ts as $k => $string) { //compute width
				$max_width = max($max_width, strlen($string));
			}

			// Create image width dependant on width of the string
			$width = imagefontwidth($font_size) * $max_width;
			// Set height to that of the font
			$height = imagefontheight($font_size) * count($ts);
			$el = imagefontheight($font_size);
			// Create the image pallette
			$img = imagecreatetruecolor($width, $height);
			// Dark red background
			$bg = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
			imagefilledrectangle($img, 0, 0, $width, $height, $bg);
			// White font color
			$color = imagecolorallocate($img, 0, 0, 0);

			foreach ($ts as $k => $string) {
				// Loop through the string
				imagestring($img, $font_size, 0, $k * $el, $string, $color);
			}
			// Return the image
			ob_start();
			imagejpeg($img);
			$bytes = ob_get_clean();
			// Remove image
			imagedestroy($img);

			return $bytes;
		}
	}

	public function decode($data) {
		return $data;
	}

}

