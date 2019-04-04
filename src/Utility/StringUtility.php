<?php
/**
 * OpenSmart :
 * Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        masahiro ehara <masahiro.ehara@irona.co.jp>
 * @copyright     Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * @link          https://smart-consortium.org OpenSmart Project
 * @since         0.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Utility;

use Cake\Core\Configure;

/**
 * Class StringUtility
 * @package App\Utility
 */
class StringUtility
{
	/**
	 * Test target string has $needle string
	 *
	 * @param string $haystack search target
	 * @param string $needle search string
	 * @return bool TRUE if $haystack has a $needle. FALSE otherwise.
	 */
	public static function contains(string $haystack, string $needle): bool
	{
		return $needle === '' || strpos($haystack, $needle) != false;
	}

	/**
	 * Tests if this string starts with the specified prefix.
	 *
	 * @param string $haystack the prefix
	 * @param string $needle search target.
	 * @return bool TRUE if $haystack is a prefix of the $needle. FALSE otherwise.
	 */
	public static function startsWith(string $haystack, string $needle): bool
	{
		return $needle === '' || strpos($haystack, $needle) === 0;
	}

	/**
	 *Tests if this string ends with the specified suffix.
	 *
	 * @param string $haystack the suffix.
	 * @param string $needle search target.
	 * @return bool TRUE if $haystack is a suffix of the $needle. FALSE otherwise.
	 */
	public static function endsWith(string $haystack, string $needle): bool
	{
		return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
	}

	/**
	 * Get first character.
	 *
	 * @param string $str The input string.
	 * @return bool|string  the extracted part of string or false on failure.
	 */
	public static function first(string $str)
	{
		return substr($str, 0, 1);
	}

	/**
	 * Append character(s) if variable string is not end with same character(s).
	 *
	 * @param string $str Base string
	 * @param string $char Append character (or string)
	 * @return string String. End with $char
	 */
	public static function append_once(string $str, string $char): string
	{
		return StringUtility::endsWith($str, $char) ? $str : $str . $char;
	}

	public static function is_url($text)
	{
		return (preg_match("/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/", $text)) ? true : false;
	}

	public static function is_svid_url($url)
	{
		if (StringUtility::is_url($url)) {
			if (StringUtility::contains($url, "/svid/videos/")) {
				return true;
			}
		}
		return false;
	}

	public static function has_query($url)
	{
		return strpos($url, "?") != false;
	}

	public static function is_empty($str)
	{
		return $str == null || $str == "";
	}

	public static function create_url($relationalPath)
	{
		return Configure::read('App.fullBaseUrl') . '/' . $relationalPath;

	}

}
