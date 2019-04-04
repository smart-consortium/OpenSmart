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

namespace App\Utility\FileSystem;

use App\Utility\Log;
use Cake\Filesystem\File as _File;

/**
 * Class File
 * @package App\Utility\FileSystem
 */
class File extends _File
{

	/**
	 * File constructor.
	 * @param string $path
	 * @param bool   $create
	 * @param int    $mode
	 */
	public function __construct(string $path, bool $create = false, int $mode = 0755)
	{
		parent::__construct($path, $create, $mode);
	}

	/**
	 * Append given data string to this file with EOL
	 *
	 * @param string $data Data to write
	 * @param bool   $force Force the file to open
	 * @return bool Success
	 */
	public function append($data, $force = false)
	{
		return parent::append($data . PHP_EOL, $force);
	}

	public function move(string $dest, bool $overwrite = false): string
	{
		if ($this->copy($dest, $overwrite)) {
			if($this->delete()){
				return $dest;
			}
		}
		return '';
	}

	/**
	 * @param array $array
	 * @return bool
	 */
	public function write_as_json(array $array)
	{
		$json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json == false) {
			Log::error('JSON encode failed');
			Log::debug($array);
			return false;
		}
		$json = str_replace(['\r\n', '\r', '\n'], PHP_EOL, $json);
		return $this->write($json);
	}

	/**
	 * @return mixed
	 */
	public function read_as_json()
	{
		$data = $this->read();
		return json_decode($data, true);
	}

	public static function rename(string $old_name, string $new_name): bool
	{
		if (file_exists($old_name)) {
			return rename($old_name, $new_name);
		}
		return false;
	}

}