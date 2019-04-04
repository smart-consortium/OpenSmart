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

namespace App\Service\Model;

use App\Service\Validation\PostFileValidator;
use App\Exception\InvalidArgumentException;

class PostFileIterator
{
	/**
	 * @var int Counter
	 */
	private $index = -1;

	/**
	 * @var array POST file fields
	 */
	private $files = [];

	/**
	 * @var array POST file video types
	 */
	private $video_types = [];

	public function __construct()
	{
		foreach (array_keys($_FILES) as $type) {
			foreach ($_FILES[$type][REQUEST_FILE_KEY_ERROR] as $index => $error) {
				if (PostFileValidator::is_success($type, $index)) {
					foreach (array_keys($_FILES[$type]) as $field) {
						$this->files[$type][$field][] = $_FILES[$type][$field][$index];
					}
				}
			}
		}
		$this->video_types = array_keys($this->files);
	}

	public function video_types()
	{
		return $this->video_types;
	}

	/**
	 * Check has next file
	 *
	 * @param string $type Video type
	 * @return bool TRUE on has next post file, FALSE on last index.
	 * @throws InvalidArgumentException
	 */
	public function has_next(string $type = ''): bool
	{
		if (empty($type)) {
			foreach (array_keys($this->files) as $_type) {
				if ($this->_has_next_index($_type)) {
					return true;
				}
			}
			return false;
		} else {
			return $this->_has_next_index($type);
		}
	}

	public function next_index(string $type = ''): int
	{
		if (empty($type)) {
			$_index = $this->index;
			foreach (array_keys($this->files) as $_type) {
				$this->index = $_index;
				$next = $this->_next_index($_type);
				if ($next >= 0) {
					return $next;
				}
			}
			return -1;
		} else {
			return $this->_next_index($type);
		}
	}

	/**
	 * Get next file index
	 *
	 * @param string $type Video type
	 * @return int Next file index. If has no next file return -1.
	 */
	private function _next_index(string $type): int
	{
		while (self::_has_next_index($type)) {
			if (PostFileValidator::is_success($type, ++$this->index)) {
				return $this->index;
			}
		}
		return -1;
	}

	/**
	 * @param string $type
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	private function _has_next_index(string $type): bool
	{
		if (array_key_exists($type, $this->files)) {
			return count($this->files[$type][REQUEST_FILE_KEY_ERROR]) > $this->index + 1;
		}
		self::_throw_invalid_type_error();
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private static function _throw_invalid_type_error(): void
	{
		throw new InvalidArgumentException(__('Invalid video type'));
	}
}