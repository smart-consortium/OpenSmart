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
 * @since         0.1.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Service;

use App\Exception\FileNotFoundException;
use App\Exception\ImageCreateException;
use App\Exception\InvalidArgumentException;
use App\Service\Model\Process;
use App\Utility\FileSystem\File;
use App\Utility\Log;
use Cake\Core\Configure;

class ImageService extends AppService
{
	const IMAGE_CREATION_WAIT_SECONDS = 0.2;

	/**
	 * Make image from video
	 *
	 * @param int    $time_ms Extract time milli second
	 * @param string $ext Output image extension.
	 * @param string $mode Image creation mode : 'still' or 'thumbnail'
	 * @return string Image path on success, Empty on failure.
	 */
	public function make(int $time_ms, string $ext, string $mode): string
	{
		if ($this->_has_forwarding_file()) {
			$forward = new File($this->video_dir . TIME_FORWARD_JSON_FILE_NAME);
			$times = $forward->read_as_json();

			if (array_key_exists($time_ms, $times)) {
				return $times[$time_ms][$mode];
			} else {
				throw new FileNotFoundException('Picture file not found at ', $time_ms, 'ms');
			}
		} else {
			return $this->_make($time_ms, $ext, $mode);
		}
	}

	public function make_images(string $input, string $size, array $times, string $ext, string $mode)
	{
		foreach ($times as $time) {
			$this->_make_image($input, $size, $time, $ext, $mode);
			sleep(self::IMAGE_CREATION_WAIT_SECONDS);
		}
	}

	public function make_preview_image(string $input, string $size)
	{
		return $this->_make_image($input, $size, 0, DEFAULT_IMG_EXT, API_PICTURE_MODE_PREVIEW);
	}

	public function copy_preview_image(string $dest_dir, int $index): bool
	{
		$path = $this->preview_dir . '0' . '.' . DEFAULT_IMG_EXT;
		$file = new File($path);
		if ($file->exists()) {
			return $file->copy($dest_dir . $index . '.' . DEFAULT_IMG_EXT, true);
		}
		return false;
	}


	private function _has_forwarding_file(): bool
	{
		$f = new File($this->video_dir . TIME_FORWARD_JSON_FILE_NAME);
		return $f->exists();
	}

	/**
	 * @param int    $time_ms
	 * @param string $ext
	 * @param string $mode
	 * @return string
	 */
	private function _make(int $time_ms, string $ext, string $mode): string
	{
		$image_dir_name = self::_image_dir_name($mode);
		$output_dir = $this->video_dir . $image_dir_name . DS;
		$output = $output_dir . $time_ms . '.' . $ext;

		if (file_exists($output)) {
			return $output;
		} else {
			$service = new ParameterService($this->svid);
			$params = $service->read(true);

			$input = $this->video_dir . $params['videos'][0];
			$size = self::_image_size($params, $mode);

			return $this->__make_image($input, $size, $time_ms, $output_dir, $output);
		}
	}

	private function _make_image(string $input, string $size, int $time_ms, string $ext, string $mode)
	{
		$image_dir_name = self::_image_dir_name($mode);
		$output_dir = $this->video_dir . $image_dir_name . DS;
		$output = $output_dir . $time_ms . '.' . $ext;

		return $this->__make_image($input, $size, $time_ms, $output_dir, $output);
	}

	/**
	 * @param string $input
	 * @param string $size
	 * @param int    $time_ms
	 * @param string $output_dir
	 * @param string $output
	 * @return string
	 */
	private function __make_image(string $input, string $size, int $time_ms, string $output_dir, string $output): string
	{
		try {
			$tmp = tempnam($output_dir, TMP_PREFIX);
			$time = self::_time_format($time_ms);
			$process = new Process(FFMPEG_MAKE_IMAGE, [$time, $input, 1, $size, $tmp]);
			$process->start(false, false);

			if (File::rename($tmp, $output)) {
				Log::debug('Image created at : {0}', $output);
				return $output;
			}
		} finally {
			if (file_exists($tmp)) {
				unlink($tmp);
			}
		}
		throw new ImageCreateException(__('Image create failed : {0}', $tmp));
	}

	private static function _image_dir_name(string $mode): string
	{
		switch ($mode) {
			case API_PICTURE_MODE_STILL:
				return STILL_DIR;
			case API_PICTURE_MODE_THUMBNAIL:
				return THUMBNAIL_DIR;
			case API_PICTURE_MODE_PREVIEW:
				return PREVIEW_DIR;
			default:
				throw new InvalidArgumentException('Illegal image mode => ' . $mode);
		}
	}


	private static function _image_size(array $params, string $mode): string
	{
		$camera = $params[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0];
		switch ($mode) {
			case API_PICTURE_MODE_STILL:
				return $camera[PARAMS_TAG_STILL][PARAMS_TAG_SIZE];
			case API_PICTURE_MODE_THUMBNAIL:
				return $camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_SIZE];
			case API_PICTURE_MODE_PREVIEW:
				return Configure::read('Build.tasks.deploy.preview.size');
			default:
				throw new InvalidArgumentException('Illegal image mode => ' . $mode);
		}
	}

	private static function _time_format(int $ms)
	{
		return $ms / 1000;
	}
}
