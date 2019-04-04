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

namespace App\Service;

use App\Utility\StringUtility;
use Cake\Core\Configure;

trait AuthTrait
{
	function with_auth_token(string $url)
	{
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			return $url;
		}
		if ($this->is_auth_enabled() && $this->is_mod_auth_token_enabled()) {
			if (!$this->has_protected_url($url)) {
				return $url;
			}
			if ($this->has_auth_token_url($url)) {
				return $url;
			}

			$secret = Configure::read('Auth.mod_auth_token_client_secret');
			$hexTime = dechex(time());

			$paths = explode(CAKE_AUTH_PREFIX, $url, 2);
			$protected = $paths[0] . CAKE_AUTH_PREFIX;
			$file = DS . $paths[1];
			$token = md5($secret . $file . $hexTime);
			return $protected . $token . DS . $hexTime . $file;
		}
		return $url;
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	function has_protected_url(string $url): bool
	{
		return StringUtility::contains($url, CAKE_AUTH_PREFIX);
	}

	function has_auth_token_url(string $url): bool
	{
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			return false;
		}
		if (!$this->is_auth_enabled()) {
			return false;
		}
		if (!$this->has_protected_url($url)) {
			return false;
		}
		$paths = explode(CAKE_AUTH_PREFIX, $url, 2);
		$suffixes = explode(DS, $paths[1]);
		if (count($suffixes) <= 6) {
			return false;
		}
		//$token = $suffixes[0];
		//$hexTime = $suffixes[1];
		//$user = $suffixes[2];
		$yyyy = $suffixes[3];
		$mmdd = $suffixes[4];
		//$ms = $suffixes[5];
		if (preg_match("/^\d{4}$/", $yyyy) === 1 &&
			preg_match("/^\d{4}$/", $mmdd) === 1) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	function is_auth_enabled(): bool
	{
		if (Configure::read('Auth.enabled') == true) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	function is_mod_auth_token_enabled(): bool
	{
		if (Configure::read('Auth.mod_auth_token_client_secret') !== '') {
			return true;
		}
		return false;
	}

}