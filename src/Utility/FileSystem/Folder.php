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

use Cake\Filesystem\Folder as _Folder;

/**
 * Class Folder
 * @package App\Utility\FileSystem
 */
class Folder extends _Folder
{

	/**
	 * Folder constructor.
	 * @param string|null $path
	 * @param bool        $create
	 * @param bool        $mode
	 */
	public function __construct(string $path = null, bool $create = false, $mode = false)
	{
		parent::__construct($path, $create, $mode);
	}

	/**
	 * Create a directory structure.
	 *
	 * @param string    $path
	 * @param int|false $mode Mode (CHMOD) to apply to created folder, false to ignore
	 * @return bool TRUE on success, FALSE on failure.
	 */
	public static function mkdir(string $path, int $mode = 0755)
	{
		$d = new _Folder();
		return $d->create($path, $mode);
	}

	/**
	 * Get directory name
	 *
	 * @return string
	 */
	public function name(): string
	{
		return basename($this->path);
	}
}