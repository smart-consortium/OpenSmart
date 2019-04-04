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
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Utility;


use Cake\Core\Configure;
use Cake\Routing\Router;

class Server
{
	public static function encoding_server(): string
	{
		if (Server::is_encoding_server_mode()) {
			return Router::fullBaseUrl();
		} else {
			return Configure::read('System.encoding_server');
		}
	}

	public static function is_encoding_server_mode(): bool
	{
		return Server::_check_mode('System.encoding_server');
	}

	public static function storage_server(): string
	{
		if (Server::is_storage_server_mode()) {
			return Router::fullBaseUrl();
		} else {
			return Configure::read('System.storage_server');
		}
	}

	public static function is_storage_server_mode(): bool
	{
		return Server::_check_mode('System.storage_server');
	}

	public static function web_server(): string
	{
		if (Server::is_web_server_mode()) {
			return Router::fullBaseUrl();
		} else {
			return Configure::read('System.web_server');
		}
	}

	public static function is_web_server_mode(): bool
	{
		return Server::_check_mode('System.web_server');
	}

	/**
	 * @param string $mode
	 * @return bool
	 */
	private static function _check_mode(string $mode): bool
	{
		$host = Router::fullBaseUrl();
		$target = Configure::read($mode);
		return empty($target) || $host === $target;
	}
}