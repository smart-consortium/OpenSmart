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

use App\Service\Model\MediaInfo;
use App\Utility\ArrayUtility;
use App\Utility\FileSystem\File;
use App\Utility\Log;
use App\Utility\ParamsJsonUtility;
use Cake\Core\Configure;
use Exception;

/**
 * Class DeployService
 * @package App\Service
 */
class DeployService extends AppService
{
	private $thumbnail_size_ratio = 0.5;

	private $preview_size_ratio = 0.25;

	public function __construct(string $svid, string $username = '', string $auth_token = '')
	{
		parent::__construct($svid, $username, $auth_token);
		$this->thumbnail_size_ratio = Configure::read('Build.tasks.deploy.thumbnail.size_ratio');
		$this->preview_size_ratio = Configure::read('Build.tasks.deploy.preview.size_ratio');
	}

	/**
	 * Service main process
	 *
	 * @param array $videos Target videos (v.0.1 can use single video file only)
	 * @param array $options Service options
	 * @return bool TRUE on success, FALSE on failure
	 * @throws Exception Database transaction error
	 */
	public function main(array $videos, array $options)
	{
		$duration = $this->_total_duration($videos);
		$fps = NTSC_FPS;//$this->average_frame_rate();

		$cameras = [];
		foreach ($options[PARAMS_TAG_CAMERA] as $camera) {
			$stills = $this->_make_still_tag($duration, $fps, $this->_still_size($videos), $camera);
			if (empty($camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_TIMES])) {
				$thumbnails = $this->_make_thumbnail_tag($camera, $this->_thumbnail_size($videos));
			} else {
				$thumbnails = $camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_TIMES];
			}

			$vtrack = self::_make_vtrack(PARAMS_TAG_PLAY_FORWARD, [0, $duration]);
			$cameras[] = [PARAMS_TAG_NAME      => $camera[PARAMS_TAG_NAME],
			              PARAMS_TAG_STILL     => $stills,
			              PARAMS_TAG_THUMBNAIL => $thumbnails,
			              PARAMS_TAG_VTRACK    => $vtrack];
			$service = new ImageService($this->svid);
			$service->make_images($this->video_dir . $videos[0],
			                      $thumbnails[PARAMS_TAG_SIZE],
			                      $thumbnails[PARAMS_TAG_TIMES],
			                      DEFAULT_IMG_EXT,
			                      API_PICTURE_MODE_THUMBNAIL);
			$service->make_preview_image($this->video_dir . $videos[0], $this->_preview_size($videos));
		}
		$output = [PARAMS_TAG_CAMERA   => $cameras,
		           PARAMS_TAG_DURATION => $duration,
		           PARAMS_TAG_HLS      => HLS_DIR . DS . HLS_MAIN_FILE,
		           PARAMS_TAG_STATUS   => true];

		// update parameter table for build_out container
		$this->_update_parameter_table($this->svid, $output);

		$service = new ParameterService($this->svid);
		$service->dump();
		return CODE_SUCCESS;
	}

	/**
	 * Calculate video duration
	 *
	 * @param array $videos
	 * @return int
	 */
	private function _total_duration(array $videos)
	{
		$duration = 0;
		foreach ($videos as $video) {
			$info = new MediaInfo($this->video_dir . $video, true);
			if (($_d = $info->duration()) > 0) {
				$duration += $_d;
			} else {
				return -1;
			}
		}
		return $duration;
	}


	private function _make_still_tag(int $duration, int $fps, string $size, array $camera): array
	{
		$still = [];
		if (empty($camera[PARAMS_TAG_STILL][PARAMS_TAG_SIZE])) {
			$still[PARAMS_TAG_SIZE] = $size;
		} else {
			$still[PARAMS_TAG_SIZE] = $camera[PARAMS_TAG_STILL][PARAMS_TAG_SIZE];
		}

		$time_list_path = $this->video_dir . TIME_LIST_JSON_FILE_NAME;
		$time_list = empty($camera[PARAMS_TAG_STILL][PARAMS_TAG_TIMES]) ? null : $camera[PARAMS_TAG_STILL][PARAMS_TAG_TIMES];
		self::_make_time_list_json($time_list_path, $duration, $fps, $time_list);
		$still[PARAMS_TAG_TIMES] = TIME_LIST_JSON_FILE_NAME;

		if (empty($camera[PARAMS_TAG_STILL][PARAMS_TAG_SOURCE])) {
			$still[PARAMS_TAG_SOURCE] = ParamsJsonUtility::make_picture_url(API_PICTURE_MODE_STILL, $this->svid);
		} else {
			$still[PARAMS_TAG_SOURCE] = $camera[PARAMS_TAG_STILL][PARAMS_TAG_SOURCE];
		}
		return $still;
	}

	private static function _make_time_list_json(string $output_path, int $duration, int $fps = NTSC_FPS, array $time_list = null): bool
	{
		$time_list_file = new File($output_path, true, 0666);
		$result = $time_list_file->write_as_json((function () use ($duration, $fps, $time_list) {

			if (empty($time_list)) {
				$list = [];
				$frame_ms = 1000 / $fps;
				$duration_sec = $duration / 1000;

				for ($i = 0; $i <= $duration_sec; ++$i) {
					$ms = $i * 1000;
					for ($j = 0; $j < $fps; ++$j) {
						$time = $ms + round($frame_ms * $j);
						if ($time >= $duration) {
							break;
						}
						$list[] = $time;
					}
				}
				if (ArrayUtility::last($list) != $duration) {
					$list[] = $duration;
				}
				return $list;
			}
			return $time_list;
		})());
		return $result;
	}

	/**
	 * @param array  $camera
	 * @param string $default_size
	 * @return array
	 * @throws Exception
	 */
	private function _make_thumbnail_tag(array $camera, string $default_size)
	{
		$thumbnail = [];
		$size = $camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_SIZE];
		$thumbnail[PARAMS_TAG_SIZE] = empty($size) ? $default_size : $size;
		if (isset($camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_OPTIONS])) {
			$thumbnail[PARAMS_TAG_OPTIONS] = $camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_OPTIONS];
		}

		$thumbnail[PARAMS_TAG_TIMES] = $this->_time_list($camera);

		if (empty($camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_SOURCE])) {
			$thumbnail[PARAMS_TAG_SOURCE] = ParamsJsonUtility::make_picture_url(API_PICTURE_MODE_THUMBNAIL, $this->svid);
		} else {
			$thumbnail[PARAMS_TAG_SOURCE] = $camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_SOURCE];
		}

		return $thumbnail;
	}

	/**
	 * @param array $camera
	 * @return mixed
	 * @throws Exception
	 */
	private function _time_list(array $camera)
	{
		switch ($camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_METHOD]) {
			case TIME_LIST_METHOD_MANUAL:
				if (empty($times = $camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_TIMES])) {
					throw new Exception(__('Illegal parameter setting'));
				}
				return $times;
			case TIME_LIST_METHOD_COMPUTE:
				// Fall through
			default:
				$file = new File($this->video_dir . TIME_LIST_JSON_FILE_NAME);
				$time_list = $file->read_as_json();
				$options = $camera[PARAMS_TAG_THUMBNAIL][PARAMS_TAG_OPTIONS];
				return self::__make_time_list($time_list, $options[PARAMS_TAG_RANGE], $options[PARAMS_TAG_TOTAL]);
		}
	}

	private static function __make_time_list($list, $range, $count)
	{
		$min = $range[0];
		$max = $range[1];
		$length = count($list);
		Log::debug('make_list:' . sprintf("min:%d, max:%d, len:%d", $min, $max, $length));

		$i = 0;
		for (; $i < $length; ++$i) {
			if ($min <= $list[$i]) {
				break;
			}
		}
		$start = $i;
		for (; $i < $length; ++$i) {
			if ($max <= $list[$i]) {
				break;
			}
		}
		$end = $i;
		$inc = max(ceil(($end - $start + 1) / $count), 1);
		$ret = [];
		for ($i = $start; $i < $end; $i += $inc) {
			$ret[] = $list[$i];
		}
		return $ret;
	}

	/**
	 * Make "vtrack"
	 * @param string $play_type 'playForward', 'playReverse', 'slowForward', 'slowReverse'
	 * @param array  $range_src Vtrack source time range.
	 * @param array  $range_dst Vtrack destination time range.
	 *               if $range_dst is NULL, Set destination range = source range.
	 * @return array
	 */
	private static function _make_vtrack(string $play_type, array $range_src, array $range_dst = null): array
	{
		if (empty($range_dst)) {
			$range_dst = $range_src;
		}
		$src = [$range_src[0], $range_src[1]];
		$dst = [$range_dst[0], $range_dst[1]];
		return [PARAMS_TAG_duration => $range_dst[1],
		        $play_type          => [
			        [PARAMS_TAG_SRC_RANGE => $src,
			         PARAMS_TAG_DST_RANGE => $dst]
		        ]
		];
	}

	private function _still_size(array $videos)
	{
		return $this->__resize($videos, 1);
	}

	private function _thumbnail_size(array $videos)
	{
		return $this->__resize($videos, $this->thumbnail_size_ratio);
	}

	private function _preview_size(array $videos)
	{
		return $this->__resize($videos, $this->preview_size_ratio);
	}

	private function __resize(array $videos, float $ratio)
	{
		list($width, $height) = $this->__video_size($videos);
		$_width = round($width * $ratio);
		$_height = round($height * $ratio);
		return $_width . SIZE_DELIMITER . $_height;
	}

	private function __video_size(array $videos): array
	{
		$_width = -1;
		$_height = -1;
		foreach ($videos as $video) {
			$info = new MediaInfo($this->video_dir . $video, true);
			if ($_width < 0) {
				$_width = $info->width();
			} else {
				if ($_width < $info->width()) {
					$_width = $info->width();
				}
			}

			if ($_height < 0) {
				$_height = $info->height();
			} else {
				if ($_height < $info->height()) {
					$_height = $info->height();
				}
			}
		}
		return [$_width, $_height];
	}

	/**
	 * Add deploy information to parameter table.
	 * @param string $svid SVID
	 * @param array  $output
	 * @throws Exception Transaction error
	 */
	private static function _update_parameter_table(string $svid, array $output): void
	{
		$service = new ParameterService($svid);

		$service->update(function ($body) use ($output) {
			if (array_key_exists(PARAMS_TAG_DEPLOY, $body)) {
				array_merge($body, $output);
			} else {
				$body[PARAMS_TAG_DEPLOY] = $output;
			}
			return $body;
		});
	}
}