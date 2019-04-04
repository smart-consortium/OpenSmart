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

namespace App\Service\Validation;


use Cake\Core\Configure;
use App\Exception\InvalidArgumentException;

class PostFileValidator
{
	/**
	 * Check POST error status
	 *
	 * @param string $type Target video type
	 * @param int    $index Target file index
	 * @return bool TRUE on post success, FALSE on failure.
	 * memo) PHP Core defined POST error status code
	 *                   0 : UPLOAD_ERR_OK
	 *                   1 : UPLOAD_ERR_INI_SIZE
	 *                   2 : UPLOAD_ERR_FORM_SIZE
	 *                   3 : UPLOAD_ERR_PARTIAL
	 *                   4 : UPLOAD_ERR_NO_FILE
	 *                   5 : -
	 *                   6 : UPLOAD_ERR_NO_TMP_DIR
	 *                   7 : UPLOAD_ERR_CANT_WRITE
	 *                   8 : UPLOAD_ERR_EXTENSION
	 */
	public static function is_success(string $type, int $index): bool
	{
		if (!array_key_exists($type, $_FILES)) {
			return false;
		}
		$error = $_FILES[$type][REQUEST_FILE_KEY_ERROR][$index];
		switch ($error) {
			case UPLOAD_ERR_OK:
				return true;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
			case UPLOAD_ERR_EXTENSION:
			default:
				return false;
		}
	}

	/**
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public static function validate(): bool
	{
		if (PostFileValidator::_empty_files()) {
			throw new InvalidArgumentException('Empty video files');
		}
		if (!PostFileValidator::is_valid_file_paring()) {
			throw new InvalidArgumentException('Invalid video paring');
		}
		return true;
	}

	public static function is_valid_file_paring(): bool
	{
		$cameras = self::_create_post_status_matrix();
		return self::_check_video_pair($cameras);
	}

	private static function _empty_files(): bool
	{
		foreach (REQUEST_VIDEO_TYPE_LIST as $video_type) {
			for ($i = 0; $i < Configure::read('System.max_camera'); $i++) {
				if (self::is_success($video_type, $i)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Create post status matrix of camera x video
	 * @return array Each element has post status. TRUE on success, FALSE on failure or no file.
	 */
	private static function _create_post_status_matrix(): array
	{
		$cameras = [];
		foreach (REQUEST_VIDEO_TYPE_LIST as $video_type) {
			for ($i = 0; $i < Configure::read('System.max_camera'); $i++) {
				$cameras[$i][$video_type] = self::is_success($video_type, $i);
			}
		}
		return $cameras;
	}

	/**
	 * Check all cameras has same type video files.
	 * ex) Error : camera1 has slow video, but camera2 has'nt.
	 *
	 * @param $cameras
	 * @return bool
	 */
	private static function _check_video_pair($cameras): bool
	{
		$base = [];
		for ($i = 0; $i < Configure::read('System.max_camera'); $i++) {
			// skip empty camera
			if (!self::_has_video($cameras[$i])) {
				continue;
			}
			// select check base camera
			if (empty($base)) {
				$base = $cameras[$i];
				continue;
			}
			// check same video files
			if ($base != $cameras[$i]) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check camera has posted video file
	 * @param array $camera
	 * @return bool
	 */
	private static function _has_video(array $camera): bool
	{
		if (empty($camera)) {
			return false;
		}

		foreach ($camera as $video_type) {
			if ($video_type) {
				return true;
			}
		}
		return false;
	}
}