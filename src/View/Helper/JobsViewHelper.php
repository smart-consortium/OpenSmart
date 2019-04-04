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


class JobsViewHelper
{
	const STATUS_FAILED = '<span class="job_status status_failed">Failed</span>';

	const STATUS_WAITING = '<span class="job_status status_waiting">Waiting</span>';

	const STATUS_BUILDING = '<span class="job_status status_processing">Processing</span>';

	const STATUS_SUCCESS = '<span class="job_status status_success">Success</span>';

	const STATUS_INVALID = 'Invalid';

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
}