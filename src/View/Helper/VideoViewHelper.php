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

namespace App\View\Helper;


use App\Service\AuthTrait;
use App\Utility\Server;

class VideoViewHelper
{

	use AuthTrait;

	const STATUS_FAILED = '<span class="build_status status_failed">Failed</span>';

	const STATUS_WAITING = '<span class="build_status status_waiting">Waiting</span>';

	const STATUS_BUILDING = '<span class="build_status status_building">Building</span>';

	const STATUS_SUCCESS = '<span class="build_status status_success">Success</span>';

	const STATUS_INVALID = 'Invalid';

	const LABEL_CAMERA_MODE_SINGLE = 'Single';

	const LABEL_CAMERA_MODE_TRICK = 'Trick';

	const LABEL_CAMERA_MODE_MULTI = 'Multi';

	const LABEL_CAMERA_MODE_INVALID = 'Invalid';

	public function preview($video, int $time, int $status, string $view_url = ''): string
	{
		$path = $video->path . PREVIEW_DIR . DS . $time . '.' . DEFAULT_IMG_EXT;
		$url = Server::storage_server() . $path;
		if ($this->is_auth_enabled()) {
			$url = $this->with_auth_token($url);
		}
		if (@fopen($url, 'r')) {
			return '<div class="img_box"><a href="' . $view_url . '"><img class="preview_image" src="' . $url . '"/></a></div>';
		} else {
			return '<div class="img_box img_processing">'. self::status($status).'</div>';
		}
	}

	public static function status(int $status): string
	{
		switch ($status) {
			case BUILD_STATUS_FAILED:
				return __(self::STATUS_FAILED);
			case BUILD_STATUS_WAITING:
				return __(self::STATUS_WAITING);
			case BUILD_STATUS_BUILDING:
				return __(self::STATUS_BUILDING);
			case BUILD_STATUS_SUCCESS:
				return __(self::STATUS_SUCCESS);
			default:
				return __(self::STATUS_INVALID);
		}
	}

	public static function mode(int $mode): string
	{
		switch ($mode) {
			case CAMERA_MODE_SINGLE:
				return __(self::LABEL_CAMERA_MODE_SINGLE);
			case CAMERA_MODE_TRICK:
				return __(self::LABEL_CAMERA_MODE_TRICK);
			case CAMERA_MODE_MULTI:
				return __(self::LABEL_CAMERA_MODE_MULTI);
			default:
				return __(self::LABEL_CAMERA_MODE_INVALID);
		}
	}
}