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

use App\Exception\InvalidArgumentException;
use Cake\Core\Configure;

/**
 * Class ParamsJsonUtility
 * Utility class of params.json
 * @package App\Utility
 */
class ParamsJsonUtility
{
	/**
	 * Get params.json template
	 *
	 * @param int    $camera_mode Camera mode
	 * @param string $version Template version
	 * @return mixed|null JSON
	 */
	public static function template(int $camera_mode = CAMERA_MODE_SINGLE, string $version = '')
	{
		if (empty($version)) {
			$version = Configure::read('Params.default_version');
		}

		$json = file_get_contents(self::_template_path($camera_mode, $version));
		if (empty($json)) {
			return null;
		}
		return json_decode($json, true);
	}

	/**
	 * Get params.json template path
	 *
	 * @param int    $camera_mode Camera mode
	 * @param string $version Template version string
	 * @return string params.json Template path
	 */
	private static function _template_path(int $camera_mode, string $version): string
	{
		$path = CONFIG_OPEN_SMART_PARAMS_DIR . $version . DS;
		switch ($camera_mode) {
			case CAMERA_MODE_TRICK:
				$path .= PARAMS_JSON_FILE_NAME_TRICK;
				break;
			case CAMERA_MODE_MULTI:
				$path .= PARAMS_JSON_FILE_NAME_MULTI;
				break;
			default:
				$path .= PARAMS_JSON_FILE_NAME_DEFAULT;
				break;
		}
		return $path;
	}

	/**
	 * @param string $mode
	 * @param string $svid
	 * @return string
	 */
	public static function make_picture_url(string $mode, string $svid)
	{
		if ($mode !== API_PICTURE_MODE_THUMBNAIL && $mode !== API_PICTURE_MODE_STILL) {
			Log::error(__('Invalid picture mode => ' . $mode));
			throw new InvalidArgumentException('mode', $mode);
		}
		if (empty($svid)) {
			Log::error(__('Invalid svid %s', $svid));
			throw new InvalidArgumentException('svid', $svid);
		}
		return sprintf('%s?mode=%s&svid=%s&ms=/', API_PICTURE, $mode, $svid);
	}

	public static function to_camelcase_video_type(string $snake_case): string
	{
		switch ($snake_case) {
			case SHELL_OPTION_TARGET_PLAY_FORWARD:
				return PARAMS_TAG_PLAY_FORWARD;
			case SHELL_OPTION_TARGET_SLOW_FORWARD:
				return PARAMS_TAG_SLOW_FORWARD;
			case SHELL_OPTION_TARGET_PLAY_REVERSE:
				return PARAMS_TAG_PLAY_REVERSE;
			case SHELL_OPTION_TARGET_SLOW_REVERSE:
				return PARAMS_TAG_SLOW_REVERSE;
			default:
				throw new InvalidArgumentException(__('Invalid video type: %s', $snake_case));
		}
	}
}
