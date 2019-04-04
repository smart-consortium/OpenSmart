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

/**
 * Created by PhpStorm.
 * User: masahiro
 * Date: 2018/04/21
 * Time: 16:22
 */

namespace App\Utility;


use Cake\Routing\Router;

class SvidUtility
{
	public static function is_svid(string $item): bool
	{
		return StringUtility::startsWith($item, self::svid_base_url());
	}

	/**
	 * @return string SVID url base
	 */
	public static function svid_base_url()
	{
		return Router::fullBaseUrl() . DS . SVID . DS;
	}
}