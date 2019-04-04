<?php
/**
 * OpenSmart :
 * Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      masahiro ehara <masahiro.ehara@irona.co.jp>
 * @copyright   Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * @link        https://smart-consortium.org OpenSmart Project
 * @since       1.0.0
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Utility;

/**
 * Class ArrayUtility
 * @package App\Utility
 */
class ArrayUtility
{

	/**
	 * Check has next element
	 *
	 * @param array $array Target array
	 * @param int   $index index
	 * @return bool TRUE on has next element, FALSE on last.
	 */
	public static function has_next(array $array, int $index): bool
	{
		return ($index + 1) < count($array);
	}

	/**
	 * Get Last item
	 *
	 * @param array $array Array
	 * @return mixed Last item
	 */
	public static function last(array $array)
	{
		return $array[count($array) - 1];
	}

	/**
	 * Checks if the given keys exists in the array
	 *
	 * @param array $keys An array values to check.
	 *                    $keys are declared in the order of depth
	 *                    ex) $array = array['1']['2']['3']
	 *                        ->  array_keys_exists(['1','2','3'], $array) = true
	 * @param array $search An array with keys to check.
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public static function array_keys_exists(array $keys, array $search): bool
	{
		$_search = $search;
		foreach ($keys as $key) {
			if (!array_key_exists($key, $_search)) {
				return false;
			}
			$_search = $_search[$key];
		}
		return true;
	}
}
